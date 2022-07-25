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

    use xbweb\DB;

    /**
     * Class Key
     */
    class Key extends Str
    {
        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data)
        {
            if (empty($data['name'])) $data['name'] = 'key';
            $data = parent::__correct($data);
            if (empty($data['data']['length'])) $data['data']['length'] = 32;
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
            $value = DB::escape(self::__value($data, $value));
            return "'{$value}'";
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __value(array $data, $value)
        {
            if ($value === true) return \xbweb::key($data['data']['length']);
            return parent::__value($data, $value);
        }
    }