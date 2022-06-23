<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Basic view system
     * @category     View components
     * @link         https://xbweb.org/doc/dist/classes/view
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\lib\Content;
    use xbweb\lib\Template;

    /**
     * Basic view system class
     */
    class View {
        protected static $_fn = null;

        /**
         * Register function
         * @param string   $name  Function method
         * @param callable $fn    Function
         * @return bool
         */
        public static function fn_set($name, $fn) {
            if (self::$_fn === null) self::$_fn = array();
            if (!is_callable($fn)) return false;
            self::$_fn[$name] = $fn;
            return true;
        }

        /**
         * Execute function
         * @return mixed
         */
        public static function fn() {
            $args = func_get_args();
            if (count($args) < 1) return false;
            $name = array_shift($args);
            if (!isset(self::$_fn[$name])) return false;
            if (!is_callable(self::$_fn[$name])) return false;
            return call_user_func_array(self::$_fn[$name], $args);
        }

        /**
         * Get template file
         * @param string $path  Request path
         * @param mixed  $data  Data
         * @param bool   $sys   Include system content folder
         * @return string
         */
        public static function template($path = null, $data = null, $sys = false) {
            $req = ($path === null) ? Request::get() : Request::route($path);
            if (empty($data['template'])) {
                $fn  = empty($req['template']) ? 'index' : $req['template'];
                $mod = $req['module'];
                if (($req['controller'] == 'users') && ($req['action'] == 'login')) {
                    $fn  = array($fn.'.'.Content::EXT_TPL, 'not-logged.'.Content::EXT_TPL);
                    $sys = true;
                }
                if ($req['controller'] == 'install') $sys = true;
            } else {
                $fn  = explode('/', $data['template']);
                $mod = array_shift($fn);
                $fn  = implode('/', $fn);
                if ($fn == 'message') $sys = true;
            }
            if (!is_array($fn)) $fn .= '.'.Content::EXT_TPL;
            $fn  = Content::file($fn, 'templates', $mod, $sys, $_fl);
            return Content::render($fn, $data, $_fl);
        }

        /**
         * Get template file
         * @param string $path      Request path
         * @param mixed  $data      Data
         * @param bool   $sys       Include system content folder
         * @param string $template  Template
         * @return string
         */
        public static function content($path = null, $data = null, $sys = false, &$template = null) {
            if (CMF::isError()) return self::_error($data, $template);
            $req = ($path === null) ? Request::get() : Request::route($path);
            if (
                (($req['controller'] == 'users') && ($req['action'] == 'login'))
                ||
                ($req['controller'] == 'install')
            ) $sys = true;
            $fn  = empty($req['page']) ? 'index' : $req['page'];
            $fn  = trim($fn, '/');
            $cfl = array($fn.'.'.Content::EXT_PAGE);
            if (empty($data['window'])) {
                if ($req['context'] == Request::CTX_ADMIN) {
                    $action = empty($req['action']) ? 'index' : $req['action'];
                    $cfl[]  = 'admin/entity/'.$action.'.'.Content::EXT_PAGE;
                }
            } else {
                $tpl = $data['window'] === true ? (
                    empty($data['status']) ? 'success' : $data['status']
                ) : $data['window'];
                $cfl[] = 'dialogs/'.$tpl.'.'.Content::EXT_PAGE;
            }
            $fn = Content::file($cfl, 'pages', $req['module'], $sys, $_fl);
            return Content::render($fn, $data, $_fl, $template);
        }

        /**
         * Get template file
         * @param string $path  Request path
         * @param mixed  $data  Data
         * @param bool   $sys   Include system content folder
         * @return string
         */
        public static function chunk($path, $data = null, $sys = false) {
            $fn  = Content::chunk($path, $sys, $_fl);
            return Content::render($fn, $data, $_fl);
        }

        /**
         * Rows
         * @param array  $rows  Rows
         * @param string $tpl   Template
         * @return string
         */
        public static function rows($rows, $tpl) {
            $ret = array();
            foreach ($rows as $name => $row) {
                $r = str_replace('[+name+]', $name, $tpl);
                foreach ($row as $k => $v) $r = str_replace('[+'.$k.'+]', $v, $r);
                $ret[] = $r;
            }
            return implode("\r\n", $ret);
        }

        /**
         * Menu
         * @param string $place  Menu placement
         * @param mixed  $tpls   Templates
         * @return string
         * @throws Error
         */
        public static function menu($place, $tpls = null) {
            $data = array();
            switch ($place) {
                case 'adminleft':
                case 'userprofile':
                    $fn = Paths\CORE.'data/menu/'.$place.'.json';
                    if (file_exists($fn)) $data = json_decode(file_get_contents($fn), true);
                    $mods = \xbweb::modules();
                    foreach ($mods as $m) {
                        $fn = Paths\MODULES.$m.'/data/menu/'.$place.'.json';
                        if (!file_exists($fn)) continue;
                        $menu = json_decode(file_get_contents($fn), true);
                        if (empty($menu)) continue;
                        $data = array_merge_recursive($data, $menu);
                    }
                    break;
            }
            if (empty($data)) return '';
            // Fix for moving SYSTEM menu to the end. Dirty, but fast and effective
            $system = empty($data['system']) ? array() : $data['system'];
            unset($data['system']);
            if (!empty($system)) $data['system'] = $system;
            $data = PipeLine::invoke('menu', $data, $place);
            return Template::menu($data, $tpls, $place);
        }

        /**
         * Render view
         * @param mixed  $data  Variables
         * @param string $path  Render path
         * @return string
         */
        public static function render($data = null, $path = null) {
            $isAdmin = (Request::get('context') == Request::CTX_ADMIN);
            if (CMF::isError()) {
                render_error:
                // Content
                $content = self::_error($data, $template);
                if (Request::isAJAX()) return $content;
                // Template
                try {
                    $templates = array();
                    if (!empty($template)) $templates[] = $template.'.'.Content::EXT_TPL;
                    $templates[] = 'errors/'.http_response_code().'.'.Content::EXT_TPL;
                    $templates[] = 'errors/500.'.Content::EXT_TPL;
                    $templates[] = 'error.'.Content::EXT_TPL;
                    $fnt = Content::file($templates, 'templates', Request::get('module'), true, $_fl_tpl);
                } catch (\Exception $e) {
                    die($e->getMessage());
                }
                $tpl = ($fnt === false) ? Paths\CORE.'content/templates/error.'.Content::EXT_TPL : $fnt;
                $data['content'] = $content;
                return Content::render($tpl, $data, $_fl_tpl);
            } else {
                try {
                    $content = self::content($path, $data, $isAdmin, $template);
                    if (Request::isAJAX()) return $content;
                    $data['content'] = $content;
                    return self::template($path, $data, $isAdmin);
                } catch (\Exception $e) {
                    $data = Error::getResponse($e);
                    $data['error_page'] = true;
                    if (empty($data['status'])) $data['status'] = 'error';
                    goto render_error;
                }
            }
        }

        /**
         * Converts seconds to other units
         * @param mixed  $v  Input value
         * @param string $u  Units
         * @return string
         */
        public static function seconds($v, $u = '') {
            $v = floatval($v);
            switch ($u) {
                case 'ms': $v *= 1000; break;
                case 'us': $v *= 1000000; break;
                case 'ns': $v *= 1000000000; break;
            }
            return strval(round($v, 2));
        }

        /**
         * Converts bytes to more higher units
         * @param mixed  $v  Input value
         * @param string $u  Units
         * @return string
         */
        public static function bytes($v, $u = '') {
            $v = intval($v);
            switch ($u) {
                case 'kb': $v /= 1024; break;
                case 'mb': $v /= 1048576; break;
                case 'gb': $v /= 1073741824; break;
            }
            return strval(round($v, 2));
        }

        /**
         * Error
         * @param mixed  $data      Data
         * @param string $template  Template
         * @return string
         */
        protected static function _error($data = null, &$template = null) {
            // Current vars
            $fnc = Content::file(array(
                'errors/'.http_response_code().'.'.Content::EXT_PAGE,
                'errors/500.'.Content::EXT_PAGE,
                'error.'.Content::EXT_PAGE
            ), 'pages', Request::get('module'), true, $_fl_cnt);
            $cnt = ($fnc === false) ? Paths\CORE.'content/pages/error.'.Content::EXT_PAGE : $fnc;
            return Content::render($cnt, $data, $_fl_cnt, $template);
        }

        /**
         * callStatic
         * @param string $name       Method name
         * @param array  $arguments  Arguments
         * @return mixed
         */
        public static function __callStatic($name, $arguments) {
            if (!isset(self::$_fn[$name])) return false;
            if (!is_callable(self::$_fn[$name])) return false;
            return call_user_func_array(self::$_fn[$name], func_get_args());
        }
    }