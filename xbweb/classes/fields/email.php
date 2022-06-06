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
     * @description  E-mail field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/email
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    /**
     * Class Email
     */
    class Email extends Str {
        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data) {
            if (empty($data['data']['regexp'])) $data['data']['regexp'] = \xbweb::REX_EMAIL;
            return parent::__correct($data);
        }
    }