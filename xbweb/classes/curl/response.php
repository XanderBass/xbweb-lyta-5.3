<?php
    /**
     * CURL response
     *
     * @author    Xander Bass
     * @copyright Xander Bass
     * @license   https://opensource.org/licenses/mit-license.php MIT License
     */

    namespace xbweb\CURL;

    /**
     * Class Response
     * @property-read array  $info
     * @property-read mixed  $body
     * @property-read string $error
     * @property-read int    $errno
     * @property-read int    $status
     */
    class Response
    {
        protected $_info   = null;
        protected $_body   = null;
        protected $_error  = null;
        protected $_errno  = null;
        protected $_status = 0;

        /**
         * Constructor
         * @param resource $curl  CURL resource
         */
        public function __construct($curl = null)
        {
            $this->_body  = curl_exec($curl);
            $this->_info  = curl_getinfo($curl);
            $this->_errno = curl_errno($curl);
            $this->_error = curl_error($curl);
            if (empty($this->_body)) $this->_body = '';
            if (!empty($this->_info['http_code'])) $this->_status = intval($this->_info['http_code']);
        }

        /**
         * Getter
         * @param string $name  Property name
         * @return mixed
         */
        public function __get($name)
        {
            return property_exists($this, "_{$name}") ? $this->{"_{$name}"} : null;
        }

        /**
         * toString
         * @return string
         */
        public function __toString()
        {
            return $this->_body;
        }

        /**
         * Get JSON
         * @return array
         */
        public function getJSON()
        {
            return json_decode($this->_body, true);
        }

        /**
         * Return success status
         * @return bool
         */
        public function success()
        {
            return $this->_status == 200;
        }
    }