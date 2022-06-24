<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Response components
     * @category     Basic components
     * @link         https://xbweb.org/doc/dist/classes/response
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Class Response
     */
    class Response {
        /**
         * Return "ERROR" result
         * @param mixed  $e      Errors array or error string
         * @param string $title  Page title
         * @return array
         */
        public static function error($e, $title = null) {
            $ret = array(
                'status' => 'error',
                'errors' => is_array($e) ? $e : array($e)
            );
            if ($title !== null) $ret['title'] = $title;
            return $ret;
        }

        /**
         * Return "FORM" result
         * @param array $form    Form fields
         * @param array $values  Current values
         * @param array $errors  Form errors
         * @return array
         */
        public static function form($form = null, $values =  null, $errors = null) {
            $ret = array(
                'form'   => $form,
                'values' => $values,
                'status' => 'success'
            );
            if (!empty($errors)) {
                $ret['errors'] = $errors;
                $ret['status'] = 'error';
            }
            return $ret;
        }

        /**
         * Return "OK" result
         * @param mixed  $data   Result data
         * @param string $title  Page title
         * @return array
         */
        public static function success($data = null, $title = null) {
            $ret = array('status' => 'success');
            if ($data  !== null) $ret['result'] = $data;
            if ($title !== null) $ret['title']  = $title;
            return $ret;
        }

        /**
         * Return "MESSAGE" result
         * @param string $msg  Message
         * @param string $url  Redirect URL
         * @return array
         */
        public static function message($msg, $url = null) {
            return array(
                'status'   => 'redirect',
                'template' => '/message',
                'message'  => $msg,
                'url'      => $url
            );
        }

        /**
         * Return "DIALOG" result
         * @param string $status  Status
         * @param array  $query   Query
         * @return array
         */
        public static function dialog($status, $query) {
            return array(
                'status'  => $status,
                'window'  => empty($query['window']) ? true             : $query['window'],
                'title'   => empty($query['title'])  ? ucfirst($status) : $query['title'],
                'content' => empty($query[$status])  ? ucfirst($status) : $query[$status]
            );
        }

        /**
         * Redirect to previous URL
         * @return null
         */
        public static function redirectBack() {
            $url = Config::get('redirects/login_'.Request::get('context'));
            if (empty($url)) $url = Request::CTX_ADMIN == Request::get('context') ? '/admin' : '/';
            \xbweb::redirect($url); // TODO
            return null; // Fix for IDE
        }
    }