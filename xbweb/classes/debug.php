<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Debug component
     * @category     Debug functionality
     * @link         https://xbweb.org/doc/dist/classes/debug
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Debug component
     */
    class Debug
    {
        protected static $ps   = null;
        protected static $data = null;

        /**
         * Write timing point
         * @param string $name  Point name
         * @return array
         */
        public static function p($name)
        {
            static $t = null;
            if ($t === null) {
                $t = microtime(true);
                return array('time' => 0, 'ram' => memory_get_usage());
            }
            if (empty(self::$ps)) self::$ps = array();
            $c = microtime(true);
            self::$ps[$name] = array('time' => round(($c - $t) * 1000, 3), 'ram' => memory_get_usage());
            $t = $c;
            return self::$ps[$name];
        }

        /**
         * Get all timing points
         * @return array
         */
        public static function ps()
        {
            if (empty(self::$ps)) self::$ps = array();
            return self::$ps;
        }

        /**
         * Set debug data
         * @param string $key   Key
         * @param mixed  $data  Data
         */
        public static function set($key, $data)
        {
            self::$data[$key] = $data;
        }

        /**
         * Get debug data
         * @param string $key  Key (NULL for all data)
         * @return mixed
         */
        public static function get($key = null)
        {
            if (empty(self::$data)) self::$data['__state'] = 'initialized';
            return empty($key) ? self::$data : (isset(self::$data[$key]) ? self::$data[$key] : null);
        }

        /**
         * Log something
         * @param string $fn    Filename
         * @param mixed  $data  Data
         */
        public static function log($fn, $data)
        {
            file_put_contents($fn, var_export($data, true)."\r\n\r\n", FILE_APPEND);
        }
    }

    Debug::p('start');