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
     * @description  Datetime field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/datetime
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\Field;

    /**
     * Class Datetime
     */
    class Datetime extends Field
    {
        const BASE_TYPE = self::T_DATETIME;

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data)
        {
            $data = parent::__correct($data);
            if (empty($data['data']['type'])) $data['data']['type'] = 'datetime';
            $data['base_type'] = \xbweb::v(array(
                'date' => self::T_DATE,
                'time' => self::T_TIME
            ), $data['data']['type'], self::T_DATETIME);
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
            if ($value === true) switch ($data['base_type']) {
                case self::T_TIME: return 'current_date()';
                case self::T_DATE: return 'current_date()';
                default          : return 'now()';
            }
            return "'{$value}'";
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return mixed
         */
        protected static function __unpack(array $data, $value)
        {
            return $value;
        }

        /**
         * Validate value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return bool|string
         */
        protected static function __valid(array $data, $value)
        {
            try {
                new \DateTime($value);
                return true;
            } catch (\Exception $e) {
                return 'invalid';
            }
        }

        /**
         * Get corrected value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __value(array $data, $value)
        {
            if ($value === true) $value = 'now';
            $dtz = empty($data['data']['timezone']) ? null : new \DateTimeZone($data['date']['timezone']);
            $dto = new \DateTime($value, $dtz);
            switch ($data['base_type']) {
                case self::T_TIME: return $dto->format('H:i:s');
                case self::T_DATE: return $dto->format('Y-m-d');
                default          : return $dto->format('Y-m-d H:i:s');
            }
        }
    }