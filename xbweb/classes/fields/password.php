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
     * @description  Password field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/password
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    use xbweb\DB;

    /**
     * Class Password
     */
    class Password extends Str {
        const FLAGS = 'required';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data) {
            if (empty($data['name'])) $data['name'] = 'password';
            $data = parent::__correct($data);
            if (empty($data['data']['length'])) $data['data']['length'] = 64;
            return $data;
        }

        /**
         * Pack field value
         * @param array $data   Field data
         * @param mixed $value  Field value
         * @return string
         */
        protected static function __pack(array $data, $value) {
            $value = DB::escape(\xbweb\password($value));
            return "'{$value}'";
        }
    }