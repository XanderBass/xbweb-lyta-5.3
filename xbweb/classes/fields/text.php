<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Text field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/text
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    class Text extends Str {
        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data) {
            $data = parent::__correct($data);
            if (empty($data['data']['type'])) $data['data']['type'] = '';
            switch ($data['data']['type']) {
                case 'tiny'  : $data['base_type'] = self::T_TEXT_TINY; break;
                case 'medium': $data['base_type'] = self::T_TEXT_MEDIUM; break;
                case 'long'  : $data['base_type'] = self::T_TEXT_LONG; break;
                default      : $data['base_type'] = self::T_TEXT; break;
            }
            return $data;
        }
    }