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
     * @description  Integer field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/integer
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\Field;

    /**
     * Class Integer
     */
    class Integer extends Field
    {
        const BASE_TYPE = self::T_INT;
        const FLAGS     = 'required, sortable, table';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data)
        {
            $data = parent::__correct($data);
            if (empty($data['data']['type'])) $data['data']['type'] = 'int';
            switch ($data['data']['type']) {
                case 'byte': $data['base_type'] = self::T_BYTE; break;
                case 'word': $data['base_type'] = self::T_WORD; break;
                case 'big' : $data['base_type'] = self::T_INT_BIG; break;
                default    : $data['base_type'] = self::T_INT; break;
            }
            $data['data']['min'] = empty($data['data']['min']) ? 0 : intval($data['data']['min']);
            $data['data']['max'] = empty($data['data']['max']) ? 0 : intval($data['data']['max']);
            if (empty($data['default'])) $data['default'] = 0;
            return $data;
        }

        /**
         * Pack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __pack(array $data, $value)
        {
            return self::__value($data, $value);
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return int
         */
        protected static function __unpack(array $data, $value)
        {
            return intval($value);
        }

        /**
         * Validate value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return bool|string
         */
        protected static function __valid(array $data, $value)
        {
            $value = intval($value);
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
        protected static function __value(array $data, $value)
        {
            return intval($value);
        }
    }