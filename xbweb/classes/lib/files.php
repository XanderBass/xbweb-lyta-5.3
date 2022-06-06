<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Filesystem functions library
     * @category     Basic libraries
     * @link         https://xbweb.org/doc/dist/classes/lib/files
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\lib;

    use xbweb;

    /**
     * Filesystem functions library
     */
    class Files {
        const R_COPIED     = 0700;
        const R_CREATED    = 0700;
        const REX_PHP_TAGS = '~^(\<\?php|\<\?)(.*)(\?\>|)$~siu';

        /**
         * Get PHP code from file
         * @param string $fn  File name
         * @return string
         */
        public static function getPHPCode($fn) {
            return preg_replace(self::REX_PHP_TAGS, '\2', file_get_contents($fn));
        }

        /**
         * Validate image
         * @param string $fn    Image file name
         * @param array  $data  Data
         * @return bool
         */
        public static function isValidImage($fn, &$data) {
            $ret = @getimagesize($fn);
            if (!$ret) return false;
            $data = array(
                'width'    => $ret[0],
                'height'   => $ret[1],
                'type'     => $ret[2],
                'mime'     => $ret['mime'],
                'bits'     => $ret['bits'],
                'channels' => $ret['channels']
            );
            return true;
        }

        /**
         * Read file nodes
         * @param string $dir      Source directory
         * @param string $ext      File extension
         * @param array  $exclude  Exclude nodes names
         * @return mixed
         */
        public static function nodes($dir, $ext = null, $exclude = null) {
            $exclude = empty($exclude) ? array() : xbweb::arg($exclude);
            if ($dh = scandir($dir)) {
                $ret = array();
                foreach ($dh as $nn) {
                    if (($nn == '.') || ($nn == '..')) continue;
                    if (is_file($dir.'/'.$nn)) {
                        $k = basename($nn, $ext);
                        if (in_array($k, $exclude)) continue;
                        if (isset($ret[$k])) {
                            $ret[$k][''] = true;
                        } else {
                            $ret[$k] = true;
                        }
                    } elseif (is_dir($dir.'/'.$nn)) {
                        $f = isset($ret[$nn]);
                        $ret[$nn] = self::nodes($dir.'/'.$nn, $ext);
                        if ($f) $ret[$nn][''] = true;
                    }
                }
                return $ret;
            }
            return false;
        }

        /**
         * Create directory if neccesary
         * @param string $dir     Directory
         * @param int    $rights  Rights for new directory
         * @param bool   $safe    Deny directory
         * @return bool
         */
        public static function dir($dir, $rights = self::R_CREATED, $safe = false) {
            $dir = rtrim(xbweb::slash($dir), '/');
            if (!is_dir($dir)) {
                if (!mkdir($dir, $rights, true)) return false;
                if ($safe) {
                    $_ = <<<APACHE
order deny,allow
deny from all
APACHE;
                    return (file_put_contents($dir.'/.htaccess', trim($_)) !== false);
                }
            }
            return true;
        }

        /**
         * Copy files
         * @param string $src     Source
         * @param string $dst     Destination
         * @param bool   $over    Overwrite
         * @param array  $errors  Files with errors
         * @return bool
         */
        public static function copy($src, $dst, $over = false, &$errors = array()) {
            if (is_file($src)) return self::copy_($src, $dst, $over, $errors);
            if (!is_dir($dst)) if (!mkdir($dst, self::R_COPIED, true)) {
                $errors[$dst] = false;
                return false;
            }
            if ($handle = opendir($src)) {
                $ret = true;
                while (false !== ($file = readdir($handle))) {
                    if (in_array($file, array('.', '..'))) continue;
                    $path = $src.'/'.$file;
                    if (is_file($path)) {
                        $ret &= self::copy_($path, $dst.'/'.$file, $over, $errors);
                    } elseif (is_dir($path)) {
                        if (!is_dir($dst.'/'.$file)) if (!mkdir($dst.'/'.$file, self::R_COPIED, true)) {
                            $errors[$dst.'/'.$file] = false;
                            continue;
                        }
                        $ret &= self::copy($path, $dst.'/'.$file, $over, $errors);
                    }
                }
                closedir($handle);
                return $ret;
            } else {
                return false;
            }
        }

        /**
         * Internal copy file function
         * @param string $src     Source
         * @param string $dst     Destination
         * @param bool   $over    Overwrite
         * @param array  $errors  Copy file errors
         * @return bool
         */
        protected static function copy_($src, $dst, $over = false, &$errors = array()) {
            if ($over || !file_exists($dst)) {
                if (copy($src, $dst)) return true;
                $errors[$src] = $dst;
                return false;
            }
            return true;
        }

        /**
         * Remove files and directories
         * @param string $fn  Files path
         * @return bool
         */
        public static function remove($fn) {
            if (is_file($fn)) return unlink($fn);
            if ($c = glob($fn.'/{.[!.],}*', GLOB_BRACE))
                foreach ($c as $i) is_dir($i) ? self::remove($i) : unlink($i);
            return rmdir($fn);
        }

        /**
         * Get MIME by extension
         * @param string $name  File name
         * @return string
         */
        public static function getMIMEByExt($name) {
            $name = explode('.', $name);
            $ext  = array_pop($name);
            return \xbweb::MIMEType($ext);
        }
    }