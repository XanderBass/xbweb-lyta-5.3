<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Configuration component
     * @category     Basic components
     * @link         https://xbweb.org/doc/dist/classes/config
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Configuration component class
     */
    class Config {
        protected static $_registry = array();

        /**
         * Get configuration value
         * @param string $name  Value path
         * @param mixed  $def   Default value
         * @return mixed
         */
        public static function get($name = null, $def = null) {
            $name = trim($name, '/');
            if (empty($name)) return self::$_registry;
            $k = explode('/', $name);
            $v = self::$_registry;
            $f = true;
            foreach ($k as $i) {
                if (!isset($v[$i])) {
                    $f = false;
                    break;
                }
                $v = $v[$i];
            }
            if (($v === null) || !$f) {
                $ck = __NAMESPACE__.'\\Config\\'.strtoupper(strtr($name, '/', '\\'));
                if (defined($ck)) return self::set($name, constant($ck));
                return $def;
            } else {
                return $v;
            }
        }

        /**
         * Set configuration value
         * @param mixed $name   Path
         * @param mixed $value  Value
         * @return mixed
         */
        public static function set($name, $value = null) {
            if (is_array($name)) {
                if (empty($value)) {
                    self::$_registry = array_merge_recursive(self::$_registry, $name);
                } else {
                    self::$_registry = $name;
                }
                return self::$_registry;
            }
            $name = trim($name, '/');
            if (empty($name)) {
                self::$_registry = $value;
            } else {
                $k = explode('/', $name);
                if (count($k) < 1) return false;
                if (self::$_registry === null) self::$_registry = array();
                $R = self::$_registry;
                $_ = &$R;
                $l = count($k) - 1;
                foreach ($k as $c => $i) {
                    if (!isset($_[$i]) || !is_array($_[$i])) $_[$i] = array();
                    if (($c === $l) && ($value === null)) {
                        unset($_[$i]);
                        unset($_);
                        self::$_registry = $R;
                        return null;
                    }
                    $_ = &$_[$i];
                }
                $_ = $value;
                unset($_);
                self::$_registry = $R;
            }
            return $value;
        }

        /**
         * Load JSON configuration file
         * @param string $fn  File path
         * @return array
         * @throws Error
         */
        public static function load($fn) {
            if (!file_exists($fn)) throw new Error(array(
                'message' => 'No configuration file',
                'file'    => $fn
            ));
            $data = file_get_contents($fn);
            if (empty($data)) {
                if ($data === false) throw new Error(array(
                    'message' => error_get_last(),
                    'file'    => $fn
                ));
                throw new Error(array(
                    'message' => 'No configuration content',
                    'file'    => $fn
                ));
            }
            $data = json_decode($data, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                self::$_registry = $data;
                return self::$_registry;
            }
            throw new Error(array(
                'message' => 'Configuration JSON decode error',
                'error'   => json_last_error_msg(),
                'file'    => $fn
            ));
        }

        /**
         * Save JSON configuration file
         * @param string $fn  File path
         * @return null|string
         * @throws Error
         */
        public static function save($fn) {
            $data = json_encode(self::$_registry);
            if (json_last_error() == JSON_ERROR_NONE) {
                $dn = dirname($fn);
                if (!is_dir($dn)) if (!mkdir($dn, 0700, true)) throw new Error(array(
                    'message' => 'Cannot create directory',
                    'name'    => $dn
                ));
                return file_put_contents($fn, $data);
            }
            throw new Error(array(
                'message' => 'Configuration JSON encode error',
                'error'   => json_last_error_msg()
            ));
        }
    }
