<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Events component
     * @category     Basic components
     * @link         https://xbweb.org/doc/dist/classes/events
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Events component class
     */
    class Events
    {
        const REX  = '/^on(\w+)$/si';
        const TYPE = 'event';
        const KEY  = 'events';

        protected static $handlers = null;
        protected static $map      = null;

        /**
         * Set event handler
         * @param string   $service  Service name
         * @param string   $name     Event name
         * @param callable $func     Handler
         * @param bool     $status   Event status
         * @return bool
         */
        public static function handler($service, $name, $func, $status = true)
        {
            if (empty($service) || empty($name) || !is_callable($func)) return false;
            $name = explode(',', $name);
            foreach ($name as $name_i) {
                $name_i = lcfirst(trim($name_i));
                self::$handlers[static::KEY][$name_i][$service] = $func;
                if (isset(self::$map[static::KEY][$name_i][$service])) continue;
                self::$map[static::KEY][$name_i][$service] = filter_var($status, FILTER_VALIDATE_BOOLEAN);
            }
            return true;
        }

        /**
         * Turn on|off event handling
         * @param string $name     Event name
         * @param string $service  Service name
         * @param bool   $status   Status value
         * @return bool
         */
        protected static function _status($name, $service = null, $status = false)
        {
            if (empty($name)) return false;
            $status = filter_var($status, FILTER_VALIDATE_BOOLEAN);
            if (empty($service)) {
                if (empty(self::$handlers[static::KEY][$name])) return false;
                if (is_array(self::$handlers[static::KEY][$name]))
                    foreach (self::$handlers[static::KEY][$name] as $service => $fn)
                        self::$map[static::KEY][$name][$service] = $status;
            } else {
                if (empty(self::$handlers[static::KEY][$name][$service])) return false;
                self::$map[static::KEY][$name][$service] = $status;
            }
            return true;
        }

        /**
         * Turn on event handling
         * @param string $name     Event name
         * @param string $service  Service name
         * @return bool
         */
        public static function on($name, $service = null)
        {
            return static::_status($name, $service, true);
        }

        /**
         * Turn off event handling
         * @param string $name     Event name
         * @param string $service  Service name
         * @return bool
         */
        public static function off($name, $service = null)
        {
            return static::_status($name, $service, false);
        }

        /**
         * Set event status map
         * @param string $map  Map
         * @return array
         */
        public static function setMap($map = null)
        {
            self::$map[static::KEY] = is_array($map) ? $map : array();
            return static::getMap(true);
        }

        /**
         * Get event status map
         * @param bool $refresh  Force refresh map
         * @return array
         */
        public static function getMap($refresh = false)
        {
            static $indexed = false;
            if ($refresh) $indexed = false;
            if (empty(self::$handlers[static::KEY])) return array();
            if (empty(self::$map[static::KEY]))     self::$map[static::KEY] = array();
            if (!is_array(self::$map[static::KEY])) self::$map[static::KEY] = array();
            if ($indexed) return self::$map[static::KEY];
            $map = array();
            if (empty(self::$map[static::KEY])) {
                foreach (self::$handlers[static::KEY] as $EN => $SL)
                    if (is_array($SL)) foreach ($SL as $SN => $fn)
                        if (is_callable($fn)) $map[$EN][$SN] = true;
            } else {
                foreach (self::$map[static::KEY] as $EN => $SL) {
                    if (empty(self::$handlers[static::KEY][$EN])) continue;
                    if (is_array($SL)) foreach ($SL as $SN => $st) {
                        if (empty(self::$handlers[static::KEY][$EN][$SN])) continue;
                        if (!is_callable(self::$handlers[static::KEY][$EN][$SN])) continue;
                        $map[$EN][$SN] = filter_var($st, FILTER_VALIDATE_BOOLEAN);
                    }
                }
                foreach (self::$handlers[static::KEY] as $EN => $SL)
                    if (is_array($SL)) foreach ($SL as $SN => $fn) {
                        if (isset($map[$EN][$SN])) continue;
                        if (!is_callable($fn)) continue;
                        $map[$EN][$SN] = true;
                    }
            }
            $indexed = true;
            self::$map[static::KEY] = $map;
            return self::$map[static::KEY];
        }

        /**
         * Event/pipe name
         * @param string $name  Event/pipe name
         * @param string $path  Path
         * @return string
         */
        public static function name($name, $path = '')
        {
            return $name.strtr(ucwords($path, '/'), array('/' => ''));
        }

        /**
         * Invoke event. First argument - event name. Another arguments - additional data
         * @return bool
         */
        public static function invoke()
        {
            $M = static::getMap();
            $a = func_get_args();
            if (empty($a[0])) throw new \BadMethodCallException('Empty event name');
            $n = lcfirst(array_shift($a));
            if (empty(self::$handlers[static::KEY][$n]) || empty($M[$n])) return true;
            if (!is_array($M[$n])) return true;
            foreach ($M[$n] as $service => $status) {
                if (!$status || empty(self::$handlers[static::KEY][$n][$service])) continue;
                $f = self::$handlers[static::KEY][$n][$service];
                if (is_callable($f)) if (!call_user_func_array($f, $a)) return false;
            }
            return true;
        }
    }