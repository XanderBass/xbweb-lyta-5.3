<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Primary field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/primary
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\DB;
    use xbweb\Field;

    /**
     * Class Primary
     */
    class Primary extends Field {
        const ATTRIBUTES  = 'primary, auto_increment, binary, unsigned';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data) {
            if (empty($data['name']))   $data['name']   = 'id';
            if (empty($data['access'])) $data['access'] = 'read';
            $data = parent::__correct($data);
            if (!in_array('primary', $data['attributes'])) $data['attributes'][] = 'primary';
            if (empty($data['data']['type'])) $data['data']['type'] = 'serial';
            $data['base_type'] = \xbweb::v(
                array(
                    'int'  => self::T_INT,
                    'guid' => self::T_STR,
                    'ssid' => self::T_STR,
                ),
                $data['data']['type'],
                self::T_SERIAL
            );
            return $data;
        }

        /**
         * Pack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __pack(array $data, $value) {
            if (in_array($data['base_type'], array(self::T_SERIAL, self::T_INT))) return intval($value);
            $value = DB::escape($value);
            return "'{$value}'";
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __unpack(array $data, $value) {
            if (in_array($data['base_type'], array(self::T_SERIAL, self::T_INT))) return intval($value);
            return $value;
        }

        /**
         * Validate value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return bool|string
         */
        protected static function __valid(array $data, $value) {
            if (empty($data['data']['regexp'])) return true;
            if (preg_match($data['data']['regexp'], $value)) return true;
            return 'invalid';
        }

        /**
         * Get corrected value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __value(array $data, $value) {
            if (!empty($data['type']['strip'])) $value = preg_replace($data['type']['strip'], '', $value);
            return $value;
        }
    }