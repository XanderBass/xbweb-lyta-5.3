<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  PipeLine component
     * @category     Basic components
     * @link         https://xbweb.org/doc/dist/classes/pipeline
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Pipeline component class
     */
    class PipeLine extends Events
    {
        const REX  = '/^pipe(\w+)$/si';
        const TYPE = 'pipe';
        const KEY  = 'pipeline';

        /**
         * Process pipeline. First argument - pipe name. Another arguments - additional data
         * @return mixed
         */
        public static function invoke()
        {
            static $c = 0;
            $M = static::getMap();
            $a = func_get_args();
            if (empty($a[0])) throw new \BadMethodCallException('Empty pipe name');
            $n = lcfirst(array_shift($a));
            $c++;
            if (count($a) == 0) array_unshift($a, false);
            if (empty(self::$handlers[static::KEY][$n]) || empty($M[$n])) return $a[0];
            if (!is_array($M[$n])) return $a[0];
            foreach ($M[$n] as $service => $status) {
                if (!$status || empty(self::$handlers[static::KEY][$n][$service])) continue;
                $f = self::$handlers[static::KEY][$n][$service];
                if (is_callable($f)) $a[0] = call_user_func_array($f, $a);
            }
            return $a[0];
        }
    }