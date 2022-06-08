<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  CMF class
     * @category     Global classes
     * @link         https://xbweb.org/doc/dist/classes/cmf
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Main CMF class
     */
    class CMF extends BasicObject {
        protected static $_instance = null;

        protected $_routes  = null;
        protected $_modules = null;

        /**
         * Constructor
         * @throws Error
         */
        protected function __construct() {
            $this->_routes = PipeLine::invoke('getRoutes', array(
                'login'      => '/users/login',
                'logout'     => '/users/logout',
                'profile'    => '/users/profile',
                'register'   => '/users/register',
                'activation' => '/users/activation',
                'remainpass' => '/users/remainpass',
                'changepass' => '/users/changepass',
            ));
            $this->_modules = \xbweb::modules(true);
            foreach ($this->_modules as $module) {
                if ($module == 'app') continue;
                self::loadModule($module);
            }
            self::loadModule('app');
            $cfg = Settings::getAll();
            if (!empty($cfg)) Config::set($cfg);
            if (INSTALLED) Session::init();
        }

        /**
         * Invoke
         * @param string $path  Path
         * @param array  $data  Data (overrides all)
         * @return array
         * @throws Error
         * @throws ErrorForbidden
         * @throws ErrorNotFound
         * @throws ErrorPage
         */
        public function __invoke($path = null, $data = null) {
            return static::execute($path, $data);
        }

        /**
         * Get corrected route
         * @param string $page  Page path
         * @return array
         * @throws ErrorNotFound
         * @throws Error
         */
        public static function route($page) {
            $CMF    = self::get();
            $routes = $CMF->_routes;
            $page   = ltrim($page, '/');
            if (empty($routes[$page])) return self::page(null, true);
            if (is_array($routes[$page])) {
                $routes[$page] = array_values($routes[$page]);
                if (empty($routes[$page][0])) throw new ErrorNotFound('Invalid route (empty)');
                $route = $routes[$page][0];
                $data  = empty($routes[$page][1]) ? null : $routes[$page][1];
            } else {
                if (empty($routes[$page])) throw new ErrorNotFound('Invalid route (empty)');
                $route = $routes[$page];
                $data  = array('context' => Request::get('context'));
            }
            return array('route' => $route, 'data' => $data);
        }

        /**
         * "Static" page processing
         * @param string $path  Page path
         * @param bool   $sys   Include system path
         * @return array
         * @throws ErrorNotFound
         * @throws Error
         */
        public static function page($path = null, $sys = false) {
            $page   = empty($path) ? Request::get('page') : $path;
            $module = Request::get('module');
            if (empty($page)) $page = 'index';
            $fn = lib\Content::file($page.'.'.lib\Content::EXT_PAGE, 'pages', $module, $sys, $_fl);
            if (empty($fn)) {
                $fd = PipeLine::invoke('pageNotFound', array(), $page);
                if (empty($fd)) throw new ErrorNotFound('Page not found', $page);
                return $fd;
            }
            return array('module' => $module, 'page' => $page, 'status' => 'success');
        }

        /**
         * Get file
         * @param string $file  File path
         * @throws ErrorNotFound
         */
        public static function file($file) {
            if (empty($file)) throw new ErrorNotFound('XBWeb file not set');
            $folder = Request::get('folder');
            if (($folder == 'xbweb') && ((trim($file) == 'css/xbvcl') || (trim($file) == 'css/xbvcl/index.php'))) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $fontroot = '/xbweb/css/xbvcl/';
                require Paths\CORE.'content/css/xbvcl/index.php';
                exit;
            }
            $file = ($folder == 'xbweb' ? Paths\CORE.'content/' : Paths\MODULES).$file;
            $mime = lib\Files::getMIMEByExt($file);
            if (!file_exists($file)) throw new ErrorNotFound('XBWeb file not found', Request::get('file'));
            header("Content-type: {$mime}; charset=".Config::get('charset', 'utf-8'));
            readfile($file);
            exit;
        }

        /**
         * Check for 503
         * @throws ErrorPage
         */
        public static function check503() {
            if (Config::get('debug') && Config::get('503')) {
                $ips = \xbweb::arg(Config::get('debug_ips'));
                if (!in_array('127.0.0.1', $ips)) $ips[] = '127.0.0.1';
                $cip = empty($_SERVER['REMOTE_HOST']) ? '127.0.0.1' : $_SERVER['REMOTE_HOST'];
                if (!in_array($cip, $ips)) throw new ErrorPage('Service is unavailable', 503);
            }
        }

        /**
         * Check if result is error
         * @return bool
         */
        public static function isError() {
            return (http_response_code() > 399);
        }

        /**
         * Execute request
         * @param string $path  Path
         * @param array  $data  Data (overrides all)
         * @return array
         * @throws Error
         * @throws ErrorForbidden
         * @throws ErrorNotFound
         * @throws ErrorPage
         * @throws \Exception
         */
        public static function execute($path = null, $data = null) {
            if ($path !== null) {
                try {
                    $R = static::route(Request::get('page'));
                    $D = $R['data'];
                    $route = $R['route'];
                    if (is_array($data)) foreach ($data as $k => $v) $D[$k] = $v;
                    $data = $D;
                } catch (\Exception $e) {
                    $route = $path;
                }
                Request::current($route, $data);
                return Controller::executeAction(Request::get('node'), Request::get('action'));
            }
            $file = Request::get('file');
            if ($file !== false) self::file($file);
            self::check503();
            $controller = Request::get('controller');
            if (empty($controller)) {
                if (Request::get('context') == Request::CTX_ADMIN) {
                    if (Request::get('page') == '') User::checkAdminAllowed();
                }
                $route = static::route(Request::get('page'));
                if (empty($route['route'])) return $route;
                Request::current($route['route'], $route['data']);
                return Controller::executeAction(Request::get('node'), Request::get('action'));
            } else {
                return Controller::executeAction();
            }
        }

        /**
         * Load module
         * @param $name
         */
        public static function loadModule($name) {
            $l = Paths\MODULES.'/'.$name.'/loader.php';
            if (file_exists($l)) require $l;
        }

        /**
         * Init CMF
         * @throws Error
         */
        public static function get() {
            if (self::$_instance instanceof self) return self::$_instance;
            self::$_instance = new self();
            return self::$_instance;
        }

        /**
         * Log error
         * @param \Exception  $e         Exception
         * @param string      $filename  File name
         * @return bool|int
         */
        public static function logError(\Exception $e, $filename = null) {
            if (empty($filename)) $filename = Paths\RUNTIME.'error-log/'.\xbweb::id('error').'.log';
            $response = Error::getResponse($e);
            unset($response['traceAsString']);
            $response = lib\Arrays::safeDump($response);
            if (lib\Files::dir(dirname($filename)))
                return file_put_contents($filename, var_export($response, true));
            return false;
        }
    }