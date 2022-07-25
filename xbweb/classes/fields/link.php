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
     * @description  Link field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/link
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\Field;
    use xbweb\Model;

    /**
     * Class Link
     */
    class Link extends Field
    {
        const BASE_TYPE = self::T_INT_BIG;
        const FLAGS     = 'required, sortable, table, items';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data)
        {
            $data = parent::__correct($data);
            $data['base_type'] = self::T_INT_BIG;
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
            $ret = self::__value($data, $value);
            return empty($ret) ? 'null' : $ret;
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
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

        /**
         * Get items list
         * @param array $field  Field data
         * @return array
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        public static function items(array $field)
        {
            if (in_array('items', $field['flags']) && !empty($field['link']['model'])) {
                $model = Model::create($field['link']['model']);
                return $model->get('items', false);
            }
            return array();
        }
    }