<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Current DB provider layer component
     * @category     DB components
     * @link         https://xbweb.org/doc/dist/classes/db
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\DB\Provider;

    /**
     * Database component class
     * @method static string         table($name)
     * @method static bool|DB\Result query($q, $install = null, $point = null)
     * @method static bool|array     row($q, $point = null)
     * @method static bool|array     rows($q, $primary = null, $cb = null, $point = null)
     * @method static array          apply($q, $data, $cb, $point = null)
     * @method static bool           startTransaction($name = null)
     * @method static bool           applyTransaction($name = null)
     * @method static bool           cancelTransaction($name = null)
     * @method static string         escape($v)
     * @method static string         log($query, $point = '', $key = null)
     * @method static string         log_result($result, $key = null)
     */
    class DB
    {
        const REX_ENTITY = '~^([a-zA-Z])(\w*)([a-zA-Z\d])$~si';

        /**
         * Get instance of provider
         * @return Provider
         */
        public static function get()
        {
            static $provider = null;
            if ($provider === null) $provider = Provider::create(self::config());
            return $provider;
        }

        /**
         * Return type of DB
         * @return string
         */
        public static function getType()
        {
            return self::get()->type;
        }

        /**
         * Get log
         * @return array
         */
        public static function getLog()
        {
            return self::get()->log;
        }

        /**
         * Get configuration
         * @return array
         */
        public static function config()
        {
            static $config = null;
            if ($config === null) $config = Config::get('db');
            return $config;
        }

        /**
         * Call static
         * @param string $name  Name
         * @param array  $args  Arguments
         * @return mixed
         * @throws \Exception
         */
        public static function __callStatic($name, $args)
        {
            $db = self::get();
            return call_user_func_array(array($db, $name), $args);
        }

        /**
         * Operation
         * @param string $name  Operation
         * @param bool $def
         * @return bool|string
         */
        public static function operation($name, $def = false)
        {
            $ops = array(
                'eq'       => '=',
                'neq'      => '<>',
                'lt'       => '<',
                'gt'       => '>',
                'lte'      => '<=',
                'gte'      => '>=',
                'in'       => 'in',
                'not_in'   => 'not in',
                'like'     => 'like',
                'not_like' => 'not like',
                'null'     => 'is null',
                'not_null' => 'is not null', 'not null' => 'is not null'
            );
            if (in_array($name, $ops)) return $name;
            return empty($ops[$name]) ? $def : $ops[$name];
        }
    }