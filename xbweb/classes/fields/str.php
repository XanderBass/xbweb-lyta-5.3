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
     * @description  String field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/str
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\DB;
    use xbweb\Field;
    use xbweb\FieldError;
    use xbweb\Model;

    /**
     * Class Str
     */
    class Str extends Field
    {
        const ATTRIBUTES  = 'primary, isnull, binary, index, node, fixed, system';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data)
        {
            $data = parent::__correct($data);
            $data['base_type'] = in_array('fixed', $data['attributes']) ? self::T_STR : self::T_VAR;
            if (!empty($data['data']['regexp'])) if (!\xbweb::rexValid($data['data']['regexp']))
                throw new FieldError('Invalid regular expression', $data['name']);

            $data['data']['length'] = empty($data['data']['length']) ? 0 : intval($data['data']['length']);
            $data['data']['min']    = empty($data['data']['min'])    ? 0 : intval($data['data']['min']);
            if ($data['data']['length'] > 250) $data['data']['length'] = 250;
            if ($data['data']['length'] < $data['data']['min']) $data['data']['min'] = 0;
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
            $value = DB::escape($value);
            return "'{$value}'";
        }

        /**
         * Unpack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
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
            if (in_array('unique', $data['flags']))
                if ($data['model'] instanceof Model)
                    if ($data['model']->exists($data['name'], $value)) return 'exists';
            if (!empty($data['data']['length'])) if (strlen($value) > $data['data']['length']) return 'long';
            if (strlen($value) < $data['data']['min']) return 'short';
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
        protected static function __value(array $data, $value)
        {
            if (!empty($data['type']['strip'])) $value = preg_replace($data['type']['strip'], '', $value);
            return $value;
        }
    }