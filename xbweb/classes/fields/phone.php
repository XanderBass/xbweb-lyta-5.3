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
     * @description  Phone field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/phone
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\Field;

    /**
     * Class Phone
     */
    class Phone extends Field {
        const BASE_TYPE  = self::T_INT_BIG;
        const ATTRIBUTES = 'primary, isnull, index, node';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data) {
            return parent::__correct($data);
        }

        /**
         * Pack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __pack($data, $value) {
            return self::__value($data, $value);
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __unpack($data, $value) {
            return $value;
        }

        /**
         * Validate value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return bool|string
         */
        protected static function __valid($data, $value) {
            if (preg_match(\xbweb::REX_PHONE, $value)) return true;
            return 'invalid';
        }

        /**
         * Get corrected value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __value($data, $value) {
            $value = preg_replace('~([^\-\+\d]+)~si', '', $value);
            return intval($value);
        }
    }