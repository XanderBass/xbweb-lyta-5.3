<?php
    /**
     * CURL request
     *
     * @author    Xander Bass
     * @copyright Xander Bass
     * @license   https://opensource.org/licenses/mit-license.php MIT License
     */

    namespace xbweb\CURL;

    /**
     * Class Request
     */
    class Request {
        protected $_url     = null;
        protected $_data    = null;
        protected $_method  = 'GET';
        protected $_headers = array();
        protected $_cookie  = null;
        protected $_proxy   = null;
        protected $_toJSON  = false;
        protected $_options = null;

        /**
         * Constructor
         * @param string $url     URL
         * @param array  $data    REQUEST data
         * @param string $method  Method
         * @param bool   $toJSON  Convert POST data to JSON
         */
        public function __construct($url, $data = null, $method = 'GET', $toJSON = false) {
            $this->_url    = $url;
            $this->_data   = $data;
            $this->_method = $method;
            $this->_toJSON = !empty($toJSON);
        }

        /**
         * Set header
         * @param string $name   Header name
         * @param string $value  Header value
         * @return $this
         */
        public function header($name, $value = null) {
            if ($value === null) {
                unset($this->_headers[$name]);
            } else {
                $this->_headers[$name] = $value;
            }
            return $this;
        }

        /**
         * Cookie file
         * @param string $path  Cookie filename
         * @return $this
         */
        public function cookie($path = null) {
            $this->_cookie = $path;
            return $this;
        }

        /**
         * Set proxy
         * @param string $address  Proxy address
         * @return $this
         */
        public function proxy($address = null) {
            $this->_proxy = $address;
            return $this;
        }

        /**
         * CURL additional options
         * @param array $options  CURL options
         * @return $this
         */
        public function options($options = null) {
            $this->_options = $options;
            return $this;
        }

        /**
         * Execute request
         * @return Response
         */
        public function execute() {
            $curl = curl_init();
            if ($this->_method == 'GET') {
                curl_setopt($curl, CURLOPT_URL, self::buildURL($this->_url, $this->_data));
            } else {
                curl_setopt($curl, CURLOPT_URL, $this->_url);
                if ($this->_method == 'POST') {
                    curl_setopt($curl, CURLOPT_POST, 1);
                } else {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->_method);
                }
                if (!empty($this->_data) && is_array($this->_data)) {
                    $data = $this->_toJSON ? json_encode($this->_data) : http_build_query($this->_data);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
            }

            if (!empty($this->_headers)) {
                $headers = array();
                foreach ($this->_headers as $name => $value) $headers[] = "{$name}: {$value}";
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);

            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

            if (!empty($this->_cookie)) curl_setopt($curl, CURLOPT_COOKIEJAR, $this->_cookie);
            if (!empty($this->_proxy))  curl_setopt($curl, CURLOPT_PROXY, $this->_proxy);
            if (!empty($this->_options) && is_array($this->_options)) curl_setopt_array($curl, $this->_options);
            $response = new Response($curl);
            curl_close($curl);
            return $response;
        }

        /**
         * Correct and build URL
         * @param string $url   URL
         * @param array  $data  REQUEST data
         * @return bool|string
         */
        public static function buildURL($url = null, $data = null) {
            if (empty($url)) return false;
            $url = parse_url($url);
            $url['data'] = empty($url['query']) ? array() : parse_str($url['query']);
            if (is_array($data)) foreach ($data as $k => $v) $url['data'][$k] = $v;
            $ret = $url['scheme'].'://';
            if (!empty($url['user'])) {
                $ret.= $url['user'];
                if (!empty($url['pass'])) $ret.= ':'.$url['pass'];
                $ret.= '@';
            }
            $ret.= $url['host'];
            if (!empty($url['port'])) $ret.= ':'.$url['port'];
            $ret.= '/';
            if (!empty($url['path']))   $ret.= ltrim($url['path'], '/');
            if (!empty($url['data']))   $ret.= '?'.http_build_query($url['data']);
            if (!empty($url['anchor'])) $ret.= '#'.$url['anchor'];
            return $ret;
        }
    }