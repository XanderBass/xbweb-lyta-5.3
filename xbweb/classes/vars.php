<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Simple variables processor
     * @category     CMF components
     * @link         https://xbweb.ru/doc/dist/classes/vars
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\lib\Files;

    /**
     * Class Vars
     */
    class Vars {
        protected static $_vars = array();

        /**
         * Load variables from file
         * @param string $file  File name
         * @return bool
         */
        public static function load($file) {
            if (file_exists($file)) {
                self::$_vars = json_decode(file_get_contents($file), true);
                self::$_vars['__status'] = 'loaded from '.$file;
                return true;
            }
            return false;
        }

        /**
         * Save variables to file
         * @param string $file  File name
         * @return bool
         */
        public static function save($file) {
            if (Files::dir(dirname($file))) return (file_put_contents($file, json_encode(self::$_vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false);
            return false;
        }

        /**
         * Set variable
         * @param string $name   Name
         * @param mixed  $value  Value
         * @return mixed
         */
        public static function set($name, $value) {
            if (is_array($name)) {
                self::$_vars = $name;
                return self::$_vars;
            }
            $name = trim($name, '/');
            if (empty($name)) {
                self::$_vars = $value;
            } else {
                $k = explode('/', $name);
                if (count($k) < 1) return false;
                if (self::$_vars === null) self::$_vars = array();
                $R = self::$_vars;
                $_ = &$R;
                $l = count($k) - 1;
                foreach ($k as $c => $i) {
                    if (!isset($_[$i]) || !is_array($_[$i])) $_[$i] = array();
                    if (($c === $l) && ($value === null)) {
                        unset($_[$i]);
                        unset($_);
                        self::$_vars = $R;
                        return null;
                    }
                    $_ = &$_[$i];
                }
                $_ = $value;
                unset($_);
                self::$_vars = $R;
            }
            return $value;
        }

        /**
         * Get variable
         * @param string $name  Name
         * @param mixed  $def   Default value
         * @return mixed
         */
        public static function get($name = null, $def = null) {
            $name = trim($name, '/');
            if (empty($name)) return self::$_vars;
            $k = explode('/', $name);
            $v = self::$_vars;
            $f = true;
            foreach ($k as $i) {
                if (!isset($v[$i])) {
                    $f = false;
                    break;
                }
                $v = $v[$i];
            }
            if (($v === null) || !$f) {
                if ($def === null) return 'no var: '.$name;
                return $def;
            }
            return $v;
        }

        /**
         * Get special attribute for EoP templates
         * @param string $name  Variable name
         * @param mixed  $cond  Allowed or not
         * @return string
         */
        public static function v($name, $cond = null) {
            if ($cond === null) {
                try {
                    $cond = ACL::granted('/vars/set');
                } catch (\Exception $e) {
                    $cond = false;
                }
            }
            return empty($cond) ? '' : 'data-variable="'.$name.'"';
        }

        /**
         * Wrap variable in SPAN
         * @param string $name   Variable name
         * @param string $class  CSS class
         * @return string
         */
        public static function span($name, $class = null) {
            return self::tag('span', $name, $class);
        }

        /**
         * Wrap variable in DIV
         * @param string $name   Variable name
         * @param string $class  CSS class
         * @return string
         */
        public static function div($name, $class = null) {
            return self::tag('div', $name, $class);
        }

        /**
         * Wrap variable in P
         * @param string $name   Variable name
         * @param string $class  CSS class
         * @return string
         */
        public static function p($name, $class = null) {
            return self::tag('p', $name, $class);
        }

        /**
         * Wrap variable in tag
         * @param string $tag    HTML tag
         * @param string $name   Variable name
         * @param string $class  CSS class
         * @return string
         */
        public static function tag($tag, $name, $class = null) {
            $class = empty($class) ? '' : ' class="'.$class.'"';
            return '<'.$tag.' '.$class.self::v($name).'>'.self::get($name).'</'.$tag.'>';
        }
    }