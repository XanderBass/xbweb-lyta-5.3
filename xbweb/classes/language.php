<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Language
     * @category     Basic components
     * @link         https://xbweb.ru/doc/dist/classes/language
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Class Language
     */
    class Language
    {
        protected static $_allowed    = null;
        protected static $_current    = null;
        protected static $_dictionary = null;
        protected static $_accepted   = null;
        protected static $_supported  = array('title', 'description', 'placeholder');
        protected static $_paths      = null;

        /**
         * Get language paths
         * @return array
         */
        public static function paths()
        {
            if (empty(self::$_paths)) {
                self::$_paths = array();
                if ($mods = scandir(Paths\CORE.'data/lang')) {
                    foreach ($mods as $m) {
                        if (in_array($m, array('.', '..'))) continue;
                        self::$_paths[] = Paths\CORE.'data/lang/'.$m.'/';
                    }
                }
                $mods = \xbweb::modules();
                foreach ($mods as $m) {
                    $p = Paths\MODULES.$m.'/data/lang/';
                    if (!is_dir($p)) continue;
                    self::$_paths[] = $p;
                }
            }
            return self::$_paths;
        }

        /**
         * Get accepted languages
         * @param bool   $renew  Renew
         * @param string $def    Default language
         * @return array|null
         */
        public static function accepted($renew = false, $def = 'en')
        {
            static $ret = null;
            if (is_array($ret) && !$renew) return $ret;
            if (empty($def)) $def = 'en'; // Foolproof
            $ret = array();
            $hal = empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? false : strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if (!empty($hal))
                if (preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/', $hal, $l)) {
                    $tmp = array_combine($l[1], $l[2]);
                    foreach ($tmp as $n => $v) {
                        $k = explode('-', $n);
                        $k = $k[0];
                        $ret[$k] = $v ? $v : 1;
                    }
                    arsort($ret, SORT_NUMERIC);
                    $ret = array_keys($ret);
                }
            if  (empty($ret)) $ret = \xbweb::arg($def);
            if (!empty(self::$_allowed)) {
                $tmp = $ret;
                foreach ($ret as $k => $l) if (!in_array($l, self::$_allowed)) unset($tmp[$k]);
                $ret = array_values($tmp);
            }
            self::$_accepted = empty($ret) ? $def : $ret[0];
            return $ret;
        }

        /**
         * Set/get allowed languages
         * @param mixed $v  New value
         * @return mixed
         */
        public static function allowed($v = null)
        {
            if ($v === null) return self::$_allowed;
            if (empty($v)) {
                self::$_allowed = null;
                return null;
            }
            $v = \xbweb::arg($v);
            $a = array();
            foreach ($v as $li) if (ctype_alnum($li)) $a[] = $li;
            if (empty($a)) return false;
            self::$_allowed = $a;
            self::accepted(true, self::$_allowed[0]);
            return self::$_allowed;
        }

        /**
         * Get/set current language
         * @param mixed $v  Language sign
         * @return bool|null
         */
        public static function current($v = null)
        {
            if (self::$_accepted === null) self::accepted();
            if ($v === null) {
                if (self::$_current === null) return self::current(self::$_accepted);
                return self::$_current;
            }
            if (self::set($v)) return self::$_current;
            return false;
        }

        /**
         * Load language
         * @param mixed $v  Language sign
         * @return bool|null
         */
        public static function load($v = null)
        {
            if (self::$_accepted === null) self::accepted();
            if ($v === null) {
                if (self::$_current === null) return self::current(self::$_accepted);
                return self::current(self::$_current);
            }
            return self::current($v);
        }

        /**
         * Get dictionary
         * @return array
         */
        public static function dictionary()
        {
            if (empty(self::$_dictionary)) self::load();
            return self::$_dictionary;
        }

        /**
         * Translate string
         * @param string $k    Language ID
         * @param mixed  $def  Default value
         * @return string
         */
        public static function translate($k, $def = true)
        {
            if (empty(self::$_dictionary)) self::load();
            $k = explode('/', $k);
            $p = empty($k[1]) ? 'title' : $k[1];
            if (!in_array($p, self::$_supported)) $p = 'title';
            $k = array_shift($k);
            if (empty(self::$_dictionary[$k][$p])) {
                if (($def === true) || empty($def)) {
                    $k = strtr($k, '_-', '  ');
                    return $def ? ucfirst($k) : $k;
                } else {
                    return $def;
                }
            }
            return self::$_dictionary[$k][$p];
        }

        /**
         * Set current language
         * @param mixed $v  Language sign
         * @return bool
         */
        protected static function set($v)
        {
            if (empty(self::$_allowed)) {
                if (!ctype_alnum($v)) return false;
            } else {
                if (!in_array($v, self::$_allowed)) return false;
            }
            self::$_current = $v;
            $paths = self::paths();
            $dict  = array();
            foreach ($paths as $path) {
                $f = $path.self::$_current.'.lng';
                if (!file_exists($f)) continue;
                $data = file($f);
                foreach ($data as $s) {
                    $str = trim($s);
                    if (empty($str)) continue;
                    $a = explode('|', $str);
                    $k = trim(array_shift($a));
                    foreach (self::$_supported as $p => $e) {
                        $d = isset($a[$p]) ? trim($a[$p]) : '';
                        if (($d == '') && isset($dict[$k][$e])) $d = $dict[$k][$e];
                        $dict[$k][$e] = $d;
                    }
                }
            }
            self::$_dictionary = PipeLine::invoke('languageLoad', $dict, self::$_current);
            return true;
        }

        /**
         * Add translate information
         * @param string $k     Key
         * @param array  $data  Some data
         * @return array
         */
        public static function field($k, array $data)
        {
            foreach (self::$_supported as $p) {
                if (empty(self::$_dictionary[$k][$p])) {
                    $data[$p] = $p == 'title' ? ucfirst(strtr($k, '_-', '  ')) : '';
                } else {
                    $data[$p] = self::$_dictionary[$k][$p];
                }
            }
            return $data;
        }

        /**
         * Get action translate
         * @param string $action  Action
         * @param bool   $def     Default
         * @return string
         */
        public static function action($action, $def = true)
        {
            return Language::translate(strtr(trim($action, '/'), '/', '-'), $def);
        }
    }