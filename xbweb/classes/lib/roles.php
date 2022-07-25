<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Flags functions library
     * @category     Logic libraries
     * @link         https://xbweb.org/doc/dist/classes/lib/flags
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\lib;

    use xbweb;

    /**
     * Flags functions library
     */
    class Roles
    {
        const ROLES = 'r01,r02,r03,r04,moderator,admin,root,neo';

        /**
         * Flags value to int
         * @param mixed $value  Input value
         * @return int
         */
        public static function toInt($value)
        {
            if (is_numeric($value)) return intval($value);
            $ret  = 0;
            $keys = self::keyValues();
            $val  = is_array($value) ? $value : explode(',', $value);
            foreach ($val as $k) {
                if (empty($keys[$k])) continue;
                $ret |= $keys[$k];
            }
            return $ret;
        }

        /**
         * Flags value to array
         * @param mixed $value  Input value
         * @return array
         */
        public static function toArray($value)
        {
            $keys = self::keyValues();
            $ret  = array();
            if (is_numeric($value)) {
                $value = intval($value);
                foreach ($keys as $key => $flag)
                    if ($value & $flag) $ret[] = $key;
            } else {
                $value = xbweb::arg($value);
                foreach ($value as $key) {
                    if (empty($keys[$key])) continue;
                    $ret[] = $key;
                }
            }
            return $ret;
        }

        /**
         * Check flag is set
         * @param mixed $key    All checked flags
         * @param mixed $value  Input value
         * @return bool
         */
        public static function is($key, $value)
        {
            $keys = self::keyValues();
            if (empty($keys[$key])) return false;
            if (is_numeric($value)) {
                $key = $keys[$key];
                if ($key & $value) return true;
            } else {
                $value = xbweb::arg($value);
                return in_array($key, $value);
            }
            return false;
        }

        /**
         * Check one of flags is set (OR)
         * @param mixed $key    All checked flags
         * @param mixed $value  Input value
         * @return bool
         */
        public static function oneOf($key, $value)
        {
            $keys = self::keyValues();
            $key  = xbweb::arg($key);
            if (is_numeric($value)) {
                foreach ($key as $key_i) {
                    if (empty($keys[$key_i])) continue;
                    $v = $keys[$key_i];
                    if ($v & $value) return true;
                }
            } else {
                $value = xbweb::arg($value);
                foreach ($key as $key_i) {
                    if (empty($keys[$key_i])) continue;
                    if (in_array($key_i, $value)) return true;
                }
            }
            return false;
        }

        /**
         * Check all of flags is set (AND)
         * @param mixed $key    All checked flags
         * @param mixed $value  Input value
         * @return bool
         */
        public static function allOf($key, $value)
        {
            $keys = self::keyValues();
            $key  = xbweb::arg($key);
            $ec   = 0;
            if (is_numeric($value)) {
                foreach ($key as $key_i) {
                    if (empty($keys[$key_i])) continue;
                    $ec++;
                    $v = $keys[$key_i];
                    if (($v & $value) == 0) return false;
                }
            } else {
                $value = xbweb::arg($value);
                foreach ($key as $key_i) {
                    if (empty($keys[$key_i])) continue;
                    $ec++;
                    if (!in_array($key_i, $value)) return false;
                }
            }
            return ($ec > 0);
        }

        /**
         * Check flag is allowed
         * @param mixed $key  All checked flags
         * @return bool
         */
        public static function in($key)
        {
            $keys = self::keyValues();
            if (empty($key)) return false;
            return in_array($key, $keys);
        }

        /**
         * Correct flags keylist
         * @return array
         */
        public static function keyValues()
        {
            static $ret = null;
            if ($ret === null) {
                $keys = explode(',', static::ROLES);
                $ret  = array();
                foreach ($keys as $p => $v) $ret[$v] = 0x01 << (intval($p));
            }
            return $ret;
        }

        /**
         * Int value of key list
         * @param mixed $key  All checked flags
         * @return int
         */
        public static function keyValue($key)
        {
            $keys = self::keyValues();
            $key  = xbweb::arg($key);
            $ret  = 0;
            foreach ($key as $key_i) $ret |= empty($keys[$key_i]) ? 0 : $keys[$key_i];
            return $ret;
        }
    }