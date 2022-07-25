<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Queue controller
     * @category     Controllers
     * @link         https://xbweb.ru/doc/dist/classes/controllers/queue
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\controllers;

    use xbweb\Config;
    use xbweb\PipeLine;
    use xbweb\Session;
    use xbweb\Controller;

    /**
     * Class Queue
     */
    class Queue extends Controller
    {
        const STATUS_IDLE   = 'idle';
        const STATUS_RELOAD = 'reload';
        const STATUS_OK     = 'ok';
        const STATUS_ERROR  = 'error';
        const STATUS_NOTICE = 'notice';
        const STATUS_UPDATE = 'update';

        const DELAY_TIME = 500;
        const STEPS      = 10;

        /**
         * Execute action
         * @param string $action  Action
         * @param string $method  Method
         * @return mixed
         * @throws \Exception
         */
        public function execute($action = null, $method = null)
        {
            $dt = Config::get('queue/delaytime', self::DELAY_TIME);
            $st = Config::get('queue/steps', self::STEPS);
            $response = array(
                'status'  => self::STATUS_IDLE,
                'channel' => 'xbweb_queue_'.Session::instance()->data['sid']
            );
            for ($c = 0; $c < $st; $c++) {
                try {
                    $response = PipeLine::invoke('queue', $response, $action, $method);
                } catch (\Exception $e) {
                    $response['status']  = 'error';
                    $response['message'] = $e->getMessage();
                }
                if ($response['status'] != self::STATUS_IDLE) return $response;
                usleep($dt * 1000);
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            exit;
        }
    }