<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Main framework class
     * @category     Main
     * @link         https://xbweb.org/doc/dist/xbweb
     * @core         Lyta
     * @subcore      5.3
     */

    class xbweb {
        const EMERGENCY_COUNTER = 30;
        const RANDOM_SYMBOLS    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        const REX_PHONE = '~^([\-\+]?)(\d{11})$~si';
        const REX_EMAIL = '~^([\w\.\-]+)\@([\w\.\-]+)$~siu';
        const REX_LOGIN = '~^([\p{Latin}])([\p{Latin}\d\_\-\.\s]+)([\p{Latin}\d])$~si';

        protected static $_generic = array();

        /**
         * Convert any argument to array
         * @param mixed $v  Input argument
         * @param bool  $i  Return integer values
         * @return array
         */
        public static function arg($v, $i = false) {
            if (empty($v)) return array();
            $r = is_array($v) ? $v : explode(',', strval($v));
            $r = array_map('trim', $r);
            if ($i) $r = array_map('intval', $r);
            return $r;
        }

        /**
         * Simple switch-case
         * @param array $l  Variants
         * @param mixed $v  Value
         * @param mixed $d  Default
         * @return mixed
         */
        public static function v(array $l, $v, $d) {
            foreach ($l as $k => $kv) if ($v == $k) return $kv;
            return $d;
        }

        /**
         * Regexp valid
         * @param string $v  Regexp
         * @param int    $e  Error code
         * @return bool
         */
        public static function rexValid($v, &$e = 0) {
            try {
                preg_match($v, null);
                $e = preg_last_error();
                return (PREG_NO_ERROR == $e);
            } catch (\Exception $e) {
                $e = preg_last_error();
                return false;
            }
        }

        /**
         * Generates string of random symbols
         * @param int $c  Symbols count
         * @return string
         */
        public static function key($c = 32) {
            $sym = self::RANDOM_SYMBOLS;
            for ($_ = 0, $r = ''; $_ < $c; $_++) $r.= $sym[mt_rand(0, 61)];
            return $r;
        }

        /**
         * Generate string-based unique ID
         * @param string $s  Input string
         * @return string
         */
        public static function id($s = '') {
            return md5($s.(\xbweb::now()).(\xbweb::key()));
        }

        /**
         * Correct any slashes in string
         * @param string $v  Input string
         * @return string
         */
        public static function slash($v) {
            return preg_replace('~([\/]{2,})~', '/', strtr($v, '\\', '/'));
        }

        /**
         * Correct path string
         * @param string $v  Input string
         * @param bool   $l  To lower case
         * @return string
         */
        public static function path($v, $l = true) {
            $v = rtrim(self::slash($v), '/').'/';
            return $l ? strtolower($v) : $v;
        }

        /**
         * Converts rights string to integer value
         * @param string $s  Rights string
         * @return int
         */
        public static function rights($s) {
            if (strlen($s) < 3) return 0;
            $z = floor(strlen($s) / 3);
            $v = 0;
            for ($g = 0; $g < $z; $g++) {
                $r  = substr($s, -3 - (3 * $g), 3);
                $v += (($r[2] == 'r' ? 4 : 0) + ($r[1] == 'w' ? 2 : 0) + ($r[0] == 'x' ? 1 : 0)) << (3 * $g);
            }
            return $v;
        }

        /**
         * Get NOW datetime string
         * @param string $t  Format
         * @return string
         */
        public static function now($t = null) {
            if ($t == null) $t = 'Y-m-d H:i:s';
            $dto = new DateTime();
            return $dto->format($t);
        }

        /**
         * Redirect to URL
         * @param string $url   URL
         * @param bool   $h301  Send 301
         */
        public static function redirect($url, $h301 = false) {
            if ($h301) header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');
            header('Location: '.$url);
            exit;
        }

        /**
         * Get HTTP status string by HTTP status code
         * @param int $key  HTTP code
         * @return string
         */
        public static function HTTPStatus($key) {
            static $data = null;
            if (empty($data)) $data = self::data_('http');
            return empty($data[$key]) ? 'Unknown' : $data[$key];
        }

        /**
         * Get MIME-type by file extension
         * @param string $key  Extension
         * @return string
         */
        public static function MIMEType($key) {
            static $data = null;
            if (empty($data)) $data = self::data_('mime');
            return empty($data[$key]) ? 'application/octet-stream' : $data[$key];
        }

        /**
         * Get CMF icon
         * @param bool $base64  Return base64 data
         * @return string
         */
        public static function icon($base64 = null) {
            if ($base64 === null) $base64 = xbweb\Paths\COREINWEB;
            if ($base64) return 'data:image/png;base64,'.base64_encode(file_get_contents(xbweb\Paths\CORE.'content/logo.png'));
            return '/xbweb/content/logo.png';
        }

        /**
         * Get modules
         * @param bool $reload  Reload
         * @return array
         */
        public static function modules($reload = false) {
            static $modules = null;
            if (($modules === null) || $reload) {
                defined('xbweb\\CONFIG\\Modules') or define('xbweb\\CONFIG\\Modules', true);
                $path = xbweb\Paths\MODULES;
                if (empty($path) || !is_dir($path)) return array();
                $ML   = xbweb\CONFIG\Modules;
                $incl = ($ML === true) ? true : array_map('trim', explode(',', $ML));
                $modules = array();
                if ($files = scandir($path)) foreach ($files as $i) {
                    if (is_file($path.'/'.$i) || ($i == '.') || ($i == '..')) continue;
                    if (is_array($incl)) if (!in_array($i, $incl)) continue;
                    if (strpos($i, '.') !== false) continue;
                    $modules[] = $i;
                }
            }
            return $modules;
        }

        /**
         * Get controllers for module or system
         * @param string $module  Name of module. NULL for system
         * @return array
         */
        public static function controllers($module = null) {
            $ret = self::nodes('controller', $module, (empty($module) ? array(
                'entity', 'table', 'fields', 'fieldsets'
            ) : array(
                'entity', 'table'
            )));
            if (!empty(self::$_generic['controller'])) {
                if (empty($module)) $module = 'system';
                if (empty(self::$_generic['controller'][$module])) return $ret;
                foreach (self::$_generic['controller'][$module] as $path => $data) {
                    $p = explode('/', $path);
                    self::_a($ret, $p);
                }
            }
            return $ret;
        }

        /**
         * Parse path parts
         * @param array $ret   Return
         * @param array $path  Path
         */
        protected static function _a(&$ret, &$path) {
            if (count($path) > 1) {
                $f = array_shift($path);
                if (empty($ret[$f])) $ret[$f] = array();
                self::_a($ret[$f], $path);
            } else {
                $path = $path[0];
                if (empty($ret[$path])) $ret[$path] = true;
            }
        }

        /**
         * Get class name by node path and include class
         * @param string $path  Node path
         * @param string $type  Node type
         * @return string
         * @throws Exception
         */
        public static function uses($path, $type = null) {
            $cn = self::realClass($path, $type, $fn);
            if (!class_exists($cn, false)) {
                if (!file_exists($fn)) {
                    throw new Exception("No class file for '{$cn}' in '{$fn}'");
                }
                require $fn;
                if (!class_exists($cn, false)) {
                    throw new Exception("No class '{$cn}' in '{$fn}'");
                }
            }
            return $cn;
        }

        /**
         * Real class name
         * @param string $path  Class path
         * @param string $type  Class type
         * @param mixed  $fn    File name
         * @return string
         */
        public static function realClass($path, $type = null, &$fn = false) {
            static $cache = null;
            if ($cache === null) $cache = array();
            $type = empty($type) ? 'Controller' : $type;
            if (!empty($cache[$type][$path])) {
                $fn = $cache[$type][$path][1];
                return $cache[$type][$path][0];
            }
            $P = explode('/', $path);
            $mn   = array_shift($P);
            $fn   = (empty($mn) ? xbweb\Paths\CORE : xbweb\Paths\MODULES.strtolower($mn).'/').'classes/';
            $cn   = array('xbweb');
            if (!empty($mn)) {
                $cn[] = 'Modules';
                $cn[] = $mn;
            }
            if (empty($P)) {
                $fn  .= strtolower($type).'.php';
                $cn[] = ucfirst($type);
            } else {
                if (strtolower($P[0]) == strtolower($type)) {
                    $fn  .= strtolower($type).'.php';
                    $cn[] = ucfirst($type);
                } else {
                    $fn  .= strtolower($type.'s/'.implode('/', $P)).'.php';
                    $cn[] = ucfirst($type).'s';
                    foreach ($P as $i) $cn[] = $i;
                }
            }
            $cache[$type][$path] = array('\\'.implode('\\', $cn), $fn);
            return $cache[$type][$path][0];
        }

        /**
         * Get nodes for module or system
         * @param string $type     Node type
         * @param string $module   Module name or NULL for system
         * @param array  $exclude  Exclude list
         * @return array
         */
        public static function nodes($type, $module = null, $exclude = array()) {
            $path = (empty($module) ? xbweb\Paths\CORE : xbweb\Paths\MODULES.strtolower($module).'/').'classes/';
            $type = strtolower($type);
            if (!is_dir($path)) return array();
            $ret = array();
            if (file_exists($path.$type.'.php')) $ret[$type] = true;
            if (is_dir($path.$type.'s')) {
                $nodes = self::nodes_($path.$type.'s', $exclude);
                foreach ($nodes as $k => $v) $ret[$k] = $v;
            }
            return $ret;
        }

        /**
         * Internal function for getNodes
         * @param string $path     Root path
         * @param array  $exclude  Exclude list
         * @return array
         */
        protected static function nodes_($path, $exclude = array()) {
            $nodes = array();
            if ($files = scandir($path)) foreach ($files as $i) {
                if (($i == '.') || ($i == '..')) continue;
                $n = strtr($i, array('.php' => ''));
                if (!empty($exclude) && is_array($exclude)) if (in_array($n, $exclude)) continue;
                if (is_dir($path.'/'.$i)) {
                    $nodes[$n] = self::nodes_($path.'/'.$i);
                } else {
                    if (isset($nodes[$n])) continue;
                    $nodes[$n] = true;
                }
            }
            return $nodes;
        }

        /**
         * Get data file content
         * @param string $name  Name of data file
         * @return mixed
         */
        protected static function data_($name) {
            $filename = xbweb\Paths\CORE . 'data/' . $name . '.json';
            if (!file_exists($filename)) return false;
            return json_decode(file_get_contents($filename), true);
        }

        /**
         * Get generic map
         * @return array
         */
        public static function getMap() {
            return self::$_generic;
        }

        /**
         * Get registered generic
         * @param string $type  Type
         * @param string $path  Path
         * @return bool
         */
        public static function getGeneric($type, $path) {
            list($m, $p) = self::MN($type, $path);
            return empty(self::$_generic[$type][$m][$p]) ? false : self::$_generic[$type][$m][$p];
        }

        /**
         * Register generic
         * @param string $type  Class type
         * @param string $path  Class path
         * @param mixed  $data  Generic data
         * @return bool
         */
        public static function registerGeneric($type, $path, $data = null) {
            list($m, $p) = self::MN($type, $path);
            self::$_generic[$type][$m][$p] = empty($data) ? true : $data;
            return true;
        }

        /**
         * Extract module name
         * @param string $type  Type
         * @param string $path  Path
         * @return array
         */
        public static function MN($type, $path) {
            $p = explode('/', $path);
            $m = array_shift($p) ; if (empty($m)) $m = 'system';
            $p = implode('/', $p); if (empty($p)) $p = $type;
            return array($m, $p);
        }

        /**
         * Simple placeholders replace
         * @param string $html  HTML
         * @param array  $data  Data
         * @param string $pref  Prefix
         * @return string
         */
        public static function placeholders($html, $data, $pref = '') {
            foreach ($data as $k => $v) {
                $k = $pref.$k;
                if (is_array($v)) {
                    $html = self::placeholders($html, $data, $k.'.');
                } else {
                    $html = str_replace("[+{$k}+]", $v, $html);
                }
            }
            return $html;
        }
    }