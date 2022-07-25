<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Entity controller prototype
     * @category     Controllers prototypes
     * @link         https://xbweb.ru/doc/dist/classes/controllers/entity
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Controllers;

    use xbweb\Config;
    use xbweb\Language;
    use xbweb\PipeLine;
    use xbweb\Request;

    use xbweb\ErrorPage;
    use xbweb\ErrorNotFound;
    use xbweb\ErrorDeleted;

    use xbweb\Model;
    use xbweb\Controller;

    use xbweb\DB;
    use xbweb\DB\Where;

    use xbweb\Mailer;
    use xbweb\Response;
    use xbweb\Settings;

    /**
     * Entity controller prototype class
     * @property-read Where $fuse
     */
    class Entity extends Controller
    {
        const MODEL  = '/table';
        const ENTITY = 'entity';

        protected $_fuse   = null;
        protected $_model  = null;
        protected $_entity = null;

        /**
         * Constructor
         * @param string $path   Controller path
         * @param string $model  Model path
         * @param null $entity
         * @throws \xbweb\Error
         */
        protected function __construct($path, $model = null, $entity = null) {
            if ($model === null) $model = static::MODEL;
            parent::__construct($path, $model);
            $this->_entity = empty($entity) ? static::ENTITY : $entity;
            if (method_exists($this, 'onConstruct')) $this->onConstruct();
            $where = $this->_fused_where();
            $this->_queries['delete'] = array(
                'title'   => Language::translate($this->_entity.'_delete', 'Delete '.$this->_entity),
                'action'  => 'index',
                'error'   => Language::translate($this->_entity.'_delete_error/description', 'Error delete '.$this->_entity),
                'confirm' => Language::translate($this->_entity.'_delete/description', 'Confirm delete '.$this->_entity.'?'),
                'query'   => 'update `[+table+]` set `deleted` = now() where ' . $where
            );
            $this->_queries['restore'] = array(
                'title'   => Language::translate($this->_entity.'_restore', 'Restore '.$this->_entity),
                'action'  => 'trash',
                'error'   => Language::translate($this->_entity.'_restore_error/description', 'Error restore '.$this->_entity),
                'confirm' => Language::translate($this->_entity.'_restore/description', 'Confirm restore '.$this->_entity.'?'),
                'query'   => 'update `[+table+]` set `deleted` = null where ' . $where
            );
            $this->_queries['remove'] = array(
                'title'   => Language::translate($this->_entity.'_remove', 'Remove '.$this->_entity),
                'action'  => 'trash',
                'error'   => Language::translate($this->_entity.'_remove_error/description', 'Error remove '.$this->_entity),
                'confirm' => Language::translate($this->_entity.'_remove/description', 'Confirm remove '.$this->_entity.'?'),
                'query'   => 'delete from `[+table+]` where ' . $where
            );
            $where = $this->_fused_where('`deleted` is not null');
            $this->_queries['clean'] = array(
                'title'   => Language::translate($this->_entity.'_clean', 'Clean'),
                'action'  => 'trash',
                'error'   => Language::translate($this->_entity.'_clean_error/description', 'Trash clean error'),
                'confirm' => Language::translate($this->_entity.'_clean/description', 'Confirm clean trash?'),
                'query'   => 'delete from `[+table+]` where ' . $where
            );
        }

        /**
         * Get multiple entities
         * @param string $name  Index filter name
         * @return array
         * @throws ErrorNotFound
         * @throws ErrorPage
         * @throws \xbweb\Error
         * @action ./index
         */
        public function do_index($name = null) {
            $model = Model::create($this->_modelPath);
            if (empty($name)) $name  = empty($_POST['index-filter']) ? '' : $_POST['index-filter'];
            $rows = $model->get($name, true, $query, $total);
            if (!$rows) if (in_array('norows204', $model->options)) throw new ErrorPage('No rows', 204);
            $result = Response::success(PipeLine::invoke($this->pipeName('data'), $rows, 'index'));
            $result['pages'] = ceil($total / $query->limit);
            $result['page']  = $query->page;
            $result['order'] = $query->order;
            $result['total'] = $total;
            return $result;
        }

        /**
         * Search
         * @return array
         * @throws ErrorNotFound
         * @throws ErrorPage
         * @throws \xbweb\Error
         * @action ./search
         */
        public function do_search() {
            return $this->do_index('search');
        }

        /**
         * Get one entity
         * @return array
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         * @action ./get
         */
        public function do_get() {
            $model = Model::create($this->_modelPath);
            $item  = $model->getOne(Request::get('id'));
            if ($item === false) $this->_notfound();
            if (!empty($item['deleted'])) {
                if (in_array('deleted410', $model->options)) {
                    throw new ErrorDeleted(ucfirst($this->_entity).' deleted', Request::get('id'));
                } else {
                    $this->_notfound();
                }
            }
            return Response::success(PipeLine::invoke($this->pipeName('data'), $item, 'get'));
        }

        /**
         * Create new entity
         * @return array
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         * @throws \Exception
         * @action ./create
         */
        public function do_create() {
            $model  = Model::create($this->_modelPath);
            $result = $this->_form('create', $values, $errors);
            if (Request::isPost() && $result) $this->_index();
            return Response::form($model->form('create', $values), $values, $errors);
        }

        /**
         * Edit existing entity
         * @return array
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         * @action ./edit
         */
        public function do_edit() {
            $model  = Model::create($this->_modelPath);
            $result = $this->_form('update', $values, $errors);
            if (Request::isPost() && $result) {
                $sa = empty($_POST['method']) ? 'edit' : $_POST['method'];
                switch ($sa) {
                    case 'save': break;
                    default    : $this->_index();
                }
            }
            return Response::form($model->form('update', $values), $values, $errors);
        }

        /**
         * Edit existing entity or create new
         * @return array
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         * @action ./save
         */
        public function do_save() {
            $model  = Model::create($this->_modelPath);
            $id     = $model->getID();
            $op     = empty($id) ? 'create' : 'update';
            $result = $this->_form($op, $values, $errors);
            if (Request::isPost() && $result) $op = 'update';
            return Response::form($model->form($op, $values), $values, $errors);
        }

        /**
         * Get "deleted" entities
         * @return array
         * @throws ErrorNotFound
         * @throws ErrorPage
         * @throws \xbweb\Error
         * @action ./trash
         */
        public function do_trash() {
            $model = Model::create($this->_modelPath);
            $rows  = $model->get('trash');
            if (!$rows) if (in_array('norows204', $model->options)) throw new ErrorPage('No rows', 204);
            return Response::success(PipeLine::invoke($this->pipeName('data'), $rows, 'trash'));
        }

        /**
         * Test create table SQL
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         * @action ./table_test
         */
        public function do_table_test() {
            $model = Model::create($this->_modelPath);
            header('Content-type: text/plain; charset=utf-8');
            die($model->tableSQL());
        }

        /**
         * Create table
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         * @action ./table_create
         */
        public function do_table_create() {
            $model = Model::create($this->_modelPath);
            $sql   = $model->tableSQL();
            DB::query($sql);
            die($sql);
        }

        /**
         * Mailer test
         * @throws \xbweb\Error
         * @action ./mail_test
         */
        public function do_mail_test() {
            if (!Mailer::create()
                ->from(Config::get('mailer/from', Request::mailbox('no-reply')))
                ->to(Config::get('mailer/test'))
                ->send('/test', 'Testing mail', array(), array(
                    \xbweb\Paths\WEBROOT.'www/templates/mail/mime.zip'
                ))) die('Mail not sent');
            die('Mail sent properly');
        }

        /**
         * Settings
         * @return array
         * @throws \xbweb\Error
         * @throws \xbweb\FieldError
         * @action ./settings
         */
        public function do_settings() {
            $mid = lcfirst($this->getMID());
            if (Request::isPost()) {
                Settings::save($mid, $values, $errors);
            } else {
                $values = Settings::get($mid);
                $errors = null;
            }
            return Response::form(Settings::form($mid), $values, $errors);
        }

        /**
         * Handle form
         * @param string $op      Operation
         * @param mixed  $values  Values
         * @param mixed  $errors  Errors
         * @return bool
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         */
        protected function _form($op = 'update', &$values = null, &$errors = null) {
            $model = Model::create($this->_modelPath);
            if ($op == 'update') {
                $row = $model->getOne($model->getID(), false);
                if (empty($row)) $this->_notfound();
            } else {
                $row = null;
            }
            $errors = null;
            $values = $row;
            if (!Request::isPost()) return true;
            list($values, $errors) = $model->request($op, null, true);
            if (empty($errors)) {
                $id = $model->save($values);
                if (!empty($id)) {
                    $values = PipeLine::invoke($this->pipeName($op), $model->getOne($id, false), $values);
                    return true;
                }
                $errors = 'Unable to save ' . $this->_entity;
            }
            return false;
        }

        /**
         * Not found exception
         * @param mixed $id  ID
         * @throws ErrorNotFound
         */
        protected function _notfound($id = null) {
            if ($id === null) $id = Request::get('id');
            throw new ErrorNotFound(ucfirst($this->_entity).' not found', $id);
        }

        /**
         * Redirect to index
         */
        protected function _index() {
            $url = '/'.trim($this->_path, '/').'/index';
            if (Request::isAJAX()) $url.= '?is-ajax=true';
            \xbweb::redirect(Request::URL($url));
        }

        /**
         * Get fused where
         * @param string $w  Where
         * @return string
         */
        protected function _fused_where($w = '`[+primary+]` [+ids+]') {
            $where = '('.$w.')';
            if ($this->_fuse instanceof Where) $where.= ' and ('.strval($this->_fuse).')';
            return $where;
        }
    }