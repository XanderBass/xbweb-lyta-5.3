<?php
    /** @noinspection PhpUnusedParameterInspection */
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Decimal field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/decimal
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\Field;

    /**
     * Class Decimal
     */
    class Decimal extends Field {
        const BASE_TYPE = self::T_DECIMAL;

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data) {
            $data = parent::__correct($data);
            if (empty($data['data']['type'])) $data['data']['type'] = 'float';
            switch ($data['data']['type']) {
                case 'double' : $data['base_type'] = self::T_DOUBLE; break;
                case 'decimal': $data['base_type'] = self::T_DECIMAL; break;
                default       : $data['base_type'] = self::T_FLOAT; break;
            }
            $data['data']['min'] = empty($data['data']['min']) ? 0 : floatval($data['data']['min']);
            $data['data']['max'] = empty($data['data']['max']) ? 0 : floatval($data['data']['max']);
            foreach (array('length', 'precision') as $k)
                if (!empty($data['data'][$k])) $data['data'][$k] = intval($data['data'][$k]);
            if (empty($data['default'])) $data['default'] = 0;
            return $data;
        }

        /**
         * Pack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __pack(array $data, $value) {
            return self::__value($data, $value);
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __unpack(array $data, $value) {
            return floatval($value);
        }

        /**
         * Validate value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return bool|string
         */
        protected static function __valid(array $data, $value) {
            $value = floatval($value);
            if (!empty($data['data']['min'])) if ($value < $data['data']['min']) return 'small';
            if (!empty($data['data']['max'])) if ($value > $data['data']['max']) return 'big';
            return true;
        }

        /**
         * Get corrected value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __value(array $data, $value) {
            return floatval($value);
        }
    }