<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Request components
     * @category     Basic components
     * @link         https://xbweb.org/doc/dist/classes/request
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Request component
     */
    class Request {
        protected static $_route = null;
        protected static $_path  = null;
        protected static $_map   = null;

        const KEY_AJAX  = 'is-ajax';
        const KEY_TOKEN = 'form-token';
        const KEY_API   = 'api-key';
        const CTX_ADMIN = 'admin';
        const CTX_API   = 'api';
        const CTX_WWW   = 'www';

        /**
         * AJAX request flag
         * @return bool
         */
        public static function isAJAX() {
            static $key = null;
            if ($key === null) $key = Config::get('request/keys/is_ajax', static::KEY_AJAX);
            return isset($_REQUEST[$key]) ? filter_var($_REQUEST[$key], FILTER_VALIDATE_BOOLEAN) : false;
        }

        /**
         * Check requested by CLI
         * @return bool
         */
        public static function isCLI() { return (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg'); }

        /**
         * HTTPS flag
         * @return bool
         */
        public static function isHTTPS() { return !empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off'); }

        /**
         * Check if requested by search bot
         * @return string|bool
         */
        public static function isBot() {
            $bots = array(
                'Google', 'Yahoo', 'Rambler', 'Yandex', 'Mail',
                'Bot', 'Spider', 'Snoopy', 'Crawler', 'Finder', 'curl'
            );
            if (empty($_SERVER['HTTP_USER_AGENT'])) return false;
            $rex = '~('.implode('|', $bots).')~i';
            if (preg_match($rex, $_SERVER['HTTP_USER_AGENT'])) return preg_replace($rex, '\1', $_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        /**
         * Check if request method is post
         * @return bool
         */
        public static function isPost() { return (strtolower($_SERVER['REQUEST_METHOD']) == 'post'); }

        /**
         * Current type of IP address
         * @param mixed $ip  IP address. NULL for current.
         * @return bool
         */
        public static function IPType($ip = null) {
            static $current = null;
            if ($current === null) $current = self::IP();
            if ($ip === null) $ip = $current;
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return 'ipv4';
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return 'ipv6';
            return false;
        }

        /**
         * Current IP address
         * @return mixed
         */
        public static function IP() {
            return $_SERVER['REMOTE_ADDR'];
        }

        /**
         * Get current host name
         * @param bool $rWWW  Return without "WWW"
         * @return string
         */
        public static function domain($rWWW = false) {
            static $domain = null;
            if (empty($domain)) $domain = Config::get('server/name');
            if (empty($domain)) if (!empty($_SERVER['SERVER_NAME'])) $domain = $_SERVER['SERVER_NAME'];
            if (empty($domain)) $domain = php_uname('n');
            if ($rWWW) if (preg_match('~^www\.(.+)$~si', $domain)) return preg_replace('~^www\.(.+)$~si', '\1', $domain);
            return $domain;
        }

        /**
         * Get current root host name
         * @return string
         */
        public static function RSN() {
            static $rsn = null;
            if (($rsn === null)) {
                $rsn = Config::get('server/rootname', false);
                if (empty($rsn)) {
                    $_   = explode('.', static::domain(true));
                    $rsn = $_[count($_) - 2].'.'.$_[count($_) - 1];
                }
            }
            return $rsn;
        }

        /**
         * Get subdomain
         * @return bool|string
         */
        public static function subdomain() {
            $rsn = self::RSN();
            $csn = self::domain();
            $rex = '~^(.+)\.'.preg_quote($rsn).'$~si';
            if (preg_match($rex, $csn)) return preg_replace($rex, '\1', $csn);
            return false;
        }

        /**
         * Get request variable
         * @param string $k  Key
         * @param mixed  $d  Default value
         * @param string $x  Source array
         * @return null
         */
        public static function variable($k, $d = null, $x = null) {
            if ($x == 'post') return isset($_POST[$k]) ? $_POST[$k] : $d;
            if ($x == 'get')  return isset($_GET[$k])  ? $_GET[$k]  : $d;
            return isset($_REQUEST[$k]) ? $_REQUEST[$k] : $d;
        }

        /**
         * Transform path to route array
         * @param string $path  Path (without query string)
         * @return array
         */
        public static function route($path = null) {
            $cpath = empty($path);
            $path  = parse_url($cpath ? $_SERVER['REQUEST_URI'] : $path, PHP_URL_PATH);
            $path  = trim(preg_replace('#(\/{2,})#si', '/', $path), '/');
            $route = array('contentType' => 'html', 'template' => 'index', 'page' => 'index');
            $path  = empty($path) ? array() : explode('/', $path);
            // contentType
            if (count($path)) {
                $P = $path[count($path) - 1];
                $route['contentType'] = self::contentType($P);
                $path[count($path) - 1] = $P;
            }
            $route['path'] = implode('/', $path);
            // Context
            $contexts = PipeLine::invoke('contexts', array(static::CTX_ADMIN, static::CTX_API, static::CTX_WWW));
            $route['context'] = self::read($path, $contexts, static::CTX_WWW);
            $route['context'] = PipeLine::invoke('context', $route['context']);
            // File or action
            if (!empty($path) && (($path[0] == 'xbweb') || ($path[0] == 'modules'))) {
                $route['folder'] = array_shift($path);
                $route['file']   = empty($path) ? null : implode('/', $path);
            } else {
                $route['module'] = self::read($path, \xbweb::modules(true));
                $controllers = PipeLine::invoke('controllers', \xbweb::controllers($route['module']), $route['module']);
                $route['controller'] = self::readNodes($path, $controllers);
                if (empty($route['controller']) && !empty($route['module'])) {
                    if ($controllers['controller'] === true) $route['controller'] = true;
                }
                // Action, id, alias
                $page = null;
                if (empty($route['controller'])) {
                    $route['page'] = implode('/', $path);
                } elseif (isset($path[0])) {
                    if (is_numeric($path[0])) {
                        $route['action'] = 'get';
                        $route['id']     = intval(array_shift($path));
                    } else {
                        $route['action'] = array_shift($path);
                        if (isset($path[0])) if (is_numeric($path[0])) $route['id'] = intval(array_shift($path));
                    }
                    if (!empty($_POST['action'])) {
                        $route['action']      = $_POST['action'];
                        $route['post_action'] = $_POST['action'];
                    }
                    if (!empty($_POST['id'])) $route['id'] = intval($_POST['id']);
                    $route['alias']    = empty($path) ? null : implode('/', $path);
                    $ctrl = $route['controller'] === true ? '' : $route['controller'];
                    $route['template'] = $ctrl;
                    $route['page']     = (empty($ctrl) ? '' : $ctrl.'/').$route['action'];
                } else {
                    $ctrl = $route['controller'] === true ? '' : $route['controller'];
                    $route['template'] = $ctrl;
                    $route['page']     = (empty($ctrl) ? '' : $ctrl.'/').'index';
                }
            }
            foreach (array('module', 'controller', 'action', 'id', 'alias') as $k) if (!isset($route[$k])) $route[$k] = null;
            $ctrl = empty($route['controller']) ? '' : ($route['controller'] === true ? '' : '/'.$route['controller']);
            $route['node'] = $route['module'].$ctrl;
            return $route;
        }

        /**
         * Get token entity
         * @return mixed
         */
        public static function token() {
            static $keys = null;
            if ($keys === null) $keys = array(
                'form' => Config::get('request/keys/token', static::KEY_TOKEN),
                'api'  => Config::get('request/keys/api'  , static::KEY_API)
            );
            foreach ($keys as $k => $v)
            if (!empty($_REQUEST[$v])) return array('for' => $k, 'value' => $_REQUEST[$v]);
            return false;
        }

        /**
         * Get Request value
         * @param string $k  Key or NULL for getting full information
         * @return mixed
         */
        public static function get($k = null) {
            if (self::$_route === null) self::current();
            if ($k === null) return self::$_route;
            return isset(self::$_route[$k]) ? self::$_route[$k] : false;
        }

        /**
         * Get or set current route
         * @param string $path  Current path string
         * @param mixed  $data  Data
         * @return string
         */
        public static function current($path = null, $data = null) {
            $refresh = false;
            if (self::$_path === null) {
                self::$_path = $path === null ? null : $path;
                $refresh     = true;
            } elseif (($path !== null) && (self::$_path != $path)) {
                if ($path === false) {
                    self::$_path = null;
                    $refresh     = true;
                } else {
                    self::$_path = $path;
                    $refresh     = true;
                }
            } elseif (self::$_route === null) {
                $refresh = true;
            }
            if ($refresh) {
                self::$_route = PipeLine::invoke('getRoute', self::route(self::$_path), true);
                if (self::$_path === null) self::$_path = $_SERVER['REQUEST_URI'];
                if (is_array($data)) foreach ($data as $k => $v) self::$_route[$k] = $v;
            }
            return self::$_path;
        }

        /**
         * Get current full action path
         * @return string
         */
        public static function action() {
            static $cache = null;
            if ($cache === null) {
                $req   = self::get();
                $cache = array();
                foreach (array('module', 'controller', 'action') as $k)
                    $cache[] = empty($req[$k]) ? '' : $req[$k];
                $cache = implode('/', $cache);
            }
            return $cache;
        }

        /**
         * Get content type for requested file
         * @param string $page  Requested file
         * @return string
         */
        public static function contentType(&$page) {
            $allowed = array('html', 'htm', 'txt', 'json', 'xml');
            $ret  = 'html';
            $page = explode('.', $page);
            if ((count($page) > 1) && in_array($page[count($page) - 1], $allowed)) {
                $ret = array_pop($page);
                if ($ret == 'htm') $ret = 'html';
            }
            $page = implode('.', $page);
            return $ret;
        }

        /**
         * Normalize URL
         * @param string $url  URL
         * @param string $ctx  Context
         * @return string
         */
        public static function URL($url, $ctx = null) {
            if ($ctx === null) $ctx = self::get('context');
            $url = (empty($url) ? '' : ltrim($url, '/'));
            return '/'.((empty($ctx) || ($ctx == 'www')) ? $url : $ctx.'/'.$url);
        }

        /**
         * Canonical URL
         * @param string $url  URL
         * @param string $ctx  Context
         * @return string
         */
        public static function canonical($url = null, $ctx = null) {
            if ($url === null) $url = $_SERVER['REQUEST_URI'];
            $U = 'http' . (self::isHTTPS() ? 's' : '') . '://';
            return $U . self::domain() . self::URL($url, $ctx);
        }

        /**
         * Mailbox
         * @param string $name  Mailbox
         * @return string
         */
        public static function mailbox($name) {
            return $name.'@'.self::domain(true);
        }

        /**
         * Fetch files from request
         * @param string $name      Field name
         * @param bool   $multiple  Get multiple files
         * @return array
         */
        public static function files($name, $multiple = false) {
            $ret = array();
            if (empty($_FILES[$name]['name'])) return array();
            $fld = $_FILES[$name];
            if (is_array($fld['name'])) {
                foreach ($fld['name'] as $fid => $fn) {
                    if (empty($fn) || empty($fld['tmp_name'][$fid])) continue;
                    $ret[] = array(
                        'name'     => $fn,
                        'tmp_name' => $fld['tmp_name'][$fid],
                        'size'     => empty($fld['size'][$fid])  ? false : intval($fld['size'][$fid]),
                        'type'     =>       $fld['type'][$fid]   ? false :        $fld['type'][$fid],
                        'error'    => empty($fld['error'][$fid]) ? 0     : intval($fld['error'][$fid]),
                    );
                    if (!$multiple) break;
                }
            } else {
                if (empty($fld['name']) || empty($fld['tmp_name'])) return array();
                $ret[] = array(
                    'name'     => $fld['name'],
                    'tmp_name' => $fld['tmp_name'],
                    'size'     => empty($fld['size'])  ? false : intval($fld['size']),
                    'type'     =>       $fld['type']   ? false :        $fld['type'],
                    'error'    => empty($fld['error']) ? 0     : intval($fld['error']),
                );
            }
            return $ret;
        }

        /**
         * Read value from exploded path
         * @param array $r  Exploded path
         * @param array $a  Allowed values
         * @param mixed $d  Default value
         * @return mixed
         */
        protected static function read(&$r, $a, $d = null) {
            return empty($r[0]) ? $d : (in_array($r[0], $a) ? array_shift($r) : $d);
        }

        /**
         * Read multiple nodes from exploded path
         * @param array $r  Exploded path
         * @param array $a  Allowed values
         * @return mixed
         */
        protected static function readNodes(&$r, $a) {
            if (empty($r[0])) return '';
            if (!array_key_exists($r[0], $a)) return '';
            $k = array_shift($r);
            if ($a[$k] === true) return $k;
            $n = self::readNodes($r, $a[$k]);
            return empty($n) ? $k : $k.'/'.$n;
        }

        /**
         * Get user agent
         * @return string|bool
         */
        public static function userAgent() {
            // TODO
            return false;
        }
    }