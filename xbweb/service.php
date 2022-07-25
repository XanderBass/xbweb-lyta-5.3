<?php
    /** @noinspection PhpUnusedParameterInspection */
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Main service
     * @category     CMF parts
     * @link         https://xbweb.org/doc/dist/service
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    PipeLine::handler('cmf', 'rowUsers', function($data) {
        unset($data['password']);
        return $data;
    });

    PipeLine::handler('cmf', 'requestUsers', function($data, $operation) {
        switch ($operation) {
            case 'update':
                if (empty($data['request']['password'])) unset($data['errors']['password']);
                break;
        }
        return $data;
    });

    PipeLine::handler('cmf', 'formUsers', function($data, $row, $operation) {
        switch ($operation) {
            case 'update':
                $data['password']['flags'] = array();
                break;
        }
        return $data;
    });

    PipeLine::handler('cmf', 'settingsUsers', function($data) {
        $data['activation'] = array(
            'name'   => 'activation',
            'class'  => '/integer',
            'input'  => '/select',
            'access' => array(
                'admin'  => 'full',
                'others' => 'read'
            ),
            'data' => array(
                'items' => array(
                    0 => array('name' => Language::translate('no-activation')),
                    1 => array('name' => Language::translate('by-email')),
                    2 => array('name' => Language::translate('by-phone')),
                    3 => array('name' => Language::translate('by-both')),
                    4 => array('name' => Language::translate('manual-activation')),
                )
            ),
        );
        return $data;
    });