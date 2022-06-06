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
     * @description  User field
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/fields/user
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Fields;

    /**
     * Class User
     */
    class User extends Link {
        const BASE_TYPE = self::T_INT_BIG;
        const FLAGS     = 'required, sortable, table';

        /**
         * Correct field
         * @param array $data  Field data
         * @return array
         * @throws \xbweb\Error
         */
        protected static function __correct($data) {
            $data['link']['table'] = 'users';
            $data['link']['field'] = 'id';
            if (empty($data['name'])) $data['name'] = 'user';
            $data = parent::__correct($data);
            return $data;
        }
    }