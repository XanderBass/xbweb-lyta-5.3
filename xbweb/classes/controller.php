<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Controller prototype
     * @category     Controller prototypes
     * @link         https://xbweb.org/doc/dist/classes/controller
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Controller prototype class
     * @property-read string $modelPath  Model path
     * @property-read bool   $isGeneric  Controller is generic
     */
    abstract class Controller extends Node
    {
        const NODE_TYPE      = 'Controller';
        const DEFAULT_ACTION = 'index';

        protected static $_map = array();

        protected $_queries   = array();
        protected $_modelPath = null;
        protected $_allowed   = array();
        protected $_gdata     = null;
        protected $_settings  = array();

        /**
         * Constructor
         * @param mixed $path   Controller path
         * @param mixed $model  Model path
         * @throws Error
         */
        protected function __construct($path, $model = null)
        {
            if (is_array($path)) {
                $this->_gdata = $path;
                if (!isset($path['path'])) throw new Error(Language::translate('invalid_generic_data'));
                $path = $path['path'];
            }
            parent::__construct($path);
            $this->_modelPath = empty($this->_gdata['model']) ? (
                empty($model) ? $this->_path : $model
            ) : $this->_gdata['model'];
        }

        /**
         * Getter
         * @param string $name  Property name
         * @return mixed
         */
        public function __get($name)
        {
            switch ($name) {
                case 'isGeneric': return !empty($this->_gdata);
            }
            return parent::__get($name);
        }

        /**
         * Execute action
         * @param string $action  Action (NULL for current)
         * @param string $method  Request method
         * @return mixed
         * @throws Error
         * @throws ErrorForbidden
         */
        public function execute($action = null, $method = null)
        {
            // Prepare params
            if ($action === null) {
                $action = Request::get('action');
                $method = strtolower($_SERVER['REQUEST_METHOD']);
            } else {
                if (empty($method)) $method = 'get';
            }
            $method = strtolower($method);
            if (empty($action)) $action = static::DEFAULT_ACTION;
            // Execute
            if (method_exists($this, 'do_'.$action) && $this->_a($action)) return $this->{'do_'.$action}();
            if (!empty($this->_queries[$action]) && $this->_a($action)) {
                $Q = $this->_queries[$action];
                if ($method != 'post') return Response::dialog('confirm', $Q);
                if (method_exists($this, 'query_'.$action)) {
                    try {
                        return $this->{"query_{$action}"}();
                    } catch (\Exception $e) {
                        $Q['error'] = $e->getMessage();
                        return Response::dialog('error', $Q);
                    }
                } elseif (!empty($Q['query'])) {
                    $T = empty($Q['table']) ? true : $Q['table'];
                    if ($sql = $this->_q($Q['query'])) {
                        if ($R = DB::query($sql, $T)) {
                            if ($R->success) {
                                if (empty($Q['action'])) {
                                    return Response::dialog('success', $Q);
                                } else {
                                    $this->r($Q['action']);
                                }
                            }
                        }
                    }
                }
                return Response::dialog('error', $Q);
            }
            throw new ErrorNotFound(Language::translate('action_not_found'), $this->_path.'/'.$action);
        }

        /**
         * Check if action is allowed and drop exception or redirect
         * @param string $action  Action (only action)
         * @return bool
         * @throws Error
         * @throws ErrorForbidden
         */
        protected function _a($action)
        {
            if (in_array($action, $this->_allowed)) return true;
            $a = $this->a($action);
            if (!ACL::granted($a)) {
                if (User::checkAuthorized())
                    throw new ErrorForbidden('You have no rights for this action', $a);
            }
            return true;
        }

        /**
         * Generic SQL query
         * @param string $sql  Input SQL query string
         * @return string
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         */
        protected function _q($sql)
        {
            $model = Model::create($this->_modelPath);
            $ids   = empty($_POST['id']) ? Request::get('id') : $_POST['id'];
            $ret   = array();
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    if (!$model->validate($model->primary, $id)) continue;
                    $ret[] = $id;
                }
            } else {
                if ($model->validate($model->primary, $ids)) $ret[] = $ids;
            }
            $ids = empty($ret) ? false : (count($ret) > 1 ? " in ('".implode("','", $ret)."')" : " = '{$ret[0]}'");
            return empty($ids) ? false :  strtr($sql, array(
                '[+table+]'   => DB::table($model->table),
                '[+primary+]' => $model->primary,
                '[+ids+]'     => $ids,
                '[+id+]'      => Request::get('id')
            ));
        }

        /**
         * Get full action path
         * @param string $action  Action
         * @return string
         */
        public function a($action)
        {
            return $this->_path.'/'.$action;
        }

        /**
         * Redirect
         * @param string $action  Action
         */
        public function r($action = 'index')
        {
            $URL = Request::URL($this->a($action));
            if (Request::isAJAX()) $URL .= '?is-ajax=true';
            \xbweb::redirect($URL);
        }

        /**
         * Create controller and execute some action
         * @param string $controller  Controller path
         * @param string $action      Action
         * @param string $method      Request method
         * @return array
         * @throws \Exception
         */
        public static function executeAction($controller = null, $action = null, $method = null)
        {
            if ($controller === null) $controller = Request::get('node');
            /** @var Controller $obj */
            $obj = self::create($controller);
            return $obj->execute($action, $method);
        }

        /**
         * Get correct action for path
         * @param mixed  $path    Controller path or data
         * @param string $action  Action
         * @return string
         */
        public static function correctAction($path, $action = null)
        {
            $cn = static::classname($path);
            /** @var self $cn */
            if (empty($action)) return $path.'/'.$cn::DEFAULT_ACTION;
            return $path.'/'.$action;
        }

        /**
         * Register generic controller route
         * @param string $path    Virtual path
         * @param string $type    Generic type
         * @param string $model   Model path
         * @param string $entity  Entity name
         * @return bool
         */
        public static function registerGeneric($path, $type = null, $model = null, $entity = null)
        {
            if (is_array($path)) {
                $type   = empty($path['type'])   ? null : $path['type'];
                $model  = empty($path['model'])  ? null : $path['model'];
                $entity = empty($path['entity']) ? null : $path['entity'];
            }
            if (empty($type)) $type = 'entity';
            $allowed = array('entity', 'ranked');
            if (!in_array($type, $allowed)) return false;
            $data = array(
                'type'   => $type,
                'model'  => empty($model) ? $path : $model,
                'entity' => $entity
            );
            return \xbweb::registerGeneric('controller', $path, $data);
        }

        /**
         * Controller file
         * @param string $path  Model path
         * @return string
         */
        public static function genericFile($path)
        {
            $_      = explode('/', $path);
            $module = array_shift($_);
            $model  = empty($_) ? 'main' : implode('/', $_);
            return (empty($module) ? Paths\CORE : Paths\MODULES.$module.'/').'data/controllers/'.$model.'.json';
        }

        /**
         * Get controller class
         * @param string $path     Controller path or data
         * @param mixed  $generic  Generic data
         * @return string
         */
        public static function classname($path, &$generic = null)
        {
            if (is_array($path)) {
                if (empty($path['path'])) $path['path'] = 'entity';
                $generic = $path;
                $path    = $path['path'];
                goto generic;
            }
            try {
                $cn = \xbweb::uses($path, static::NODE_TYPE);
            } catch (\Exception $e) {
                generic:
                $gf = self::genericFile($path);
                if (file_exists($gf)) {
                    $generic = json_decode(file_get_contents($gf), true);
                } else {
                    $generic = \xbweb::getGeneric('controller', $path);
                }
                if (empty($generic['type'])) $generic['type'] = 'entity';
                $cn = '\\xbweb\\Controllers\\'.ucfirst($generic['type']);
            }
            return $cn;
        }

        /**
         * Create controller object
         * @param mixed  $path   Controller path
         * @param string $model  Model path
         * @return mixed
         */
        public static function create($path, $model = null)
        {
            $cn = self::classname($path, $generic);
            if (empty($generic)) return new $cn($path, $model);
            $mn  = empty($generic['model'])  ? $model : $generic['model'];
            $en  = empty($generic['entity']) ? null   : $generic['entity'];
            return new $cn($path, $mn, $en);
        }
    }