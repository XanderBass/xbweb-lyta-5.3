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
     * @description  Role field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/role
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\lib\Roles as LibRoles;

    use xbweb\Field;

    /**
     * Class Role
     */
    class Role extends Field
    {
        const BASE_TYPE = self::T_BYTE;
        const FLAGS     = 'required, sortable';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data)
        {
            if (empty($data['name'])) $data['name'] = 'role';
            $data = parent::__correct($data);
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
         * @return array
         */
        protected static function __unpack(array $data, $value)
        {
            return LibRoles::toArray($value);
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
            return LibRoles::toInt($value);
        }
    }