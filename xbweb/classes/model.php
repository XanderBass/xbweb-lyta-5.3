<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Model prototype
     * @category     Models
     * @link         https://xbweb.org/doc/dist/classes/model
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\lib\Flags as LibFlags;

    use xbweb\DB\Table;
    use xbweb\lib\Models;

    /**
     * Model prototype class
     * @property-read array  $fields   Model fields
     * @property-read string $table    Main table
     * @property-read string $alias    Main table alias
     * @property-read int    $limit    Limit per page
     * @property-read mixed  $primary  Primary key
     * @property-read array  $options  Model options
     */
    abstract class Model extends Node
    {
        const NODE_TYPE  = 'Model';
        const OPTIONS    = 'deleted410,norows204';
        const LIMIT      = 20;
        const FIELDS_KEY = 'fields';

        protected static $_models = array();

        protected $_fields   = array();
        protected $_table    = null;
        protected $_alias    = null;
        protected $_limit    = self::LIMIT;
        protected $_primary  = null;
        protected $_options  = array();
        protected $_settings = array();

        /**
         * Constructor
         * @param string $path  Model path
         * @param array  $data  Fields data
         * @throws NodeError
         * @throws FieldError
         */
        protected function __construct($path, array $data = null)
        {
            parent::__construct($path);
            if (!is_array($data))      throw new NodeError('Model data incorrect', $path);
            if (empty($data['table'])) throw new NodeError('No table specified', $path);
            $this->_table   = $data['table'];
            $this->_alias   = 't_'.ucwords($this->_table, '_');
            $this->_options = empty($data['options']) ? array() : LibFlags::toArray(static::OPTIONS, $data['options']);
            $this->_limit   = empty($data['limit']) ? static::LIMIT : intval($data['limit']);
            $rows = empty($data['fields']) ? false : $data['fields'];
            $rows = PipeLine::invoke($this->pipeName('fields'), $rows);
            if (empty($rows)) throw new NodeError('There are no valid fields', $path);
            $this->_fields = Models::fields($rows, $this, $pkey);
            if (!empty($pkey)) $this->_primary = $pkey;
        }

        /**
         * Getter
         * @param string $name  Property name
         * @return mixed
         */
        public function __get($name)
        {
            switch ($name) {
                case 'fields':
                    $ret = array();
                    foreach ($this->_fields as $fn => $field) {
                        $ret[$fn] = $field;
                        unset($ret[$fn]['model']);
                    }
                    return $ret;
            }
            return parent::__get($name);
        }

        /**
         * Check field exists
         * @param string $name  Field name
         * @return bool
         */
        public function hasField($name)
        {
            return !empty($this->_fields[$name]);
        }

        /**
         * Get create table SQL
         * @return string
         */
        public function tableSQL()
        {
            $query = new Table($this);
            return $query->sql();
        }

        /**
         * Validate field value
         * @param string $field  Field name
         * @param mixed  $value  Field value
         * @param mixed  $error  Error
         * @return mixed
         * @throws NodeError
         */
        public function validate($field, $value, &$error = false)
        {
            if (empty($this->_fields[$field])) throw new NodeError('No field ', $field);
            return Models::validate($this->_fields[$field], $value, $error);
        }

        /**
         * Pack field value
         * @param string $field  Field name
         * @param mixed  $value  Field value
         * @return mixed
         * @throws NodeError
         */
        public function pack($field, $value)
        {
            if (empty($this->_fields[$field])) throw new NodeError('No field ', $field);
            $value = Field::value($this->_fields[$field], $value);
            return Field::pack($this->_fields[$field], $value);
        }

        /**
         * Unpack field value
         * @param string $field  Field name
         * @param mixed  $value  Field value
         * @return mixed
         * @throws NodeError
         */
        public function unpack($field, $value)
        {
            if (empty($this->_fields[$field])) throw new NodeError('No field ', $field);
            return Field::unpack($this->_fields[$field], $value);
        }

        /**
         * Get request
         * @param string $operation  Operation
         * @param string $action     Action
         * @param bool   $forlist    Return for list
         * @return array
         * @throws Error
         */
        public function request($operation, $action = null, $forlist = false)
        {
            if ($action === null) $action = $operation;
            $ret = Models::request($this->_fields, $operation);
            if ($operation == 'update') {
                if (empty($ret['values'][$this->_primary]))
                    $ret['values'][$this->_primary] = $this->getID();
            }
            $ret = PipeLine::invoke($this->pipeName('request'), array(
                'request' => $ret['values'],
                'errors'  => $ret['errors']
            ), $operation, $action);
            return $forlist ? array_values($ret) : $ret;
        }

        /**
         * Get ID from REQUEST
         * @return mixed
         */
        public function getID()
        {
            if (empty($_POST[$this->_primary])) return Request::get('id');
            $value = $_POST[$this->_primary];
            return Field::value($this->_fields[$this->_primary], $value);
        }

        /**
         * Get form fields
         * @param string $operation  Operation
         * @param array  $row        Values
         * @return array
         * @throws Error
         */
        public function form($operation, $row = null)
        {
            return PipeLine::invoke(
                $this->pipeName('form'),
                Models::form($this->_fields, $operation, $row),
                $row, $operation
            );
        }

        /**
         * Correct row
         * @param array $row     Row
         * @param bool  $unpack  Unpack
         * @return array
         * @throws Error
         */
        public function row($row, $unpack = true)
        {
            return Models::row($this->_fields, $row, $unpack);
        }

        /**
         * Get table fields
         * @return array
         * @throws Error
         */
        public function tableFields()
        {
            return Models::tableFields($this->_fields);
        }

        /**
         * Check if value exists
         * @param string $field  Field name
         * @param mixed  $value  Field value
         * @return bool
         */
        public function exists($field, $value)
        {
            $value = Field::value($this->_fields[$field], $value);
            $table = DB::table($this->_table);
            $curid = $this->getID();
            $priid = $this->_primary;
            $f_id  = empty($curid) ? '' : " and ({$priid} <> {$curid})";
            $sql   = "select * from `{$table}` where (`{$field}` = '{$value}'){$f_id}";
            if ($rows = DB::query($sql)) if ($row = $rows->row()) return true;
            return false;
        }

        /**
         * Get full field data
         * @param string $field  Field name
         * @return string
         * @throws NodeError
         */
        public function field($field)
        {
            if (empty($this->_fields[$field])) throw new NodeError('No field ', $field);
            return $this->_alias.".`{$field}`";
        }

        /**
         * Get one
         * @param mixed $id   Row ID
         * @param bool  $acl  Get only allowed
         * @return mixed
         */
        abstract public function getOne($id, $acl = true);

        /**
         * Get many by IDs
         * @param mixed $ids    IDs
         * @param bool  $acl    Get only allowed
         * @param int   $total  Total found
         * @return mixed
         */
        abstract public function getByIDs($ids, $acl = true, &$total = null);

        /**
         * Get many
         * @param string   $name   Filter name
         * @param bool     $acl    Get only allowed fields
         * @param DB\Query $query  Query object
         * @param int      $total  Total found
         * @return mixed
         */
        abstract public function get($name = '', $acl = true, DB\Query &$query = null, &$total = null);

        /**
         * Add row
         * @param array $row  Row
         * @return mixed
         */
        abstract public function add($row);

        /**
         * Update row
         * @param array $row  Row
         * @param mixed $id   Row ID
         * @return mixed
         */
        abstract public function update($row, $id);

        /**
         * Save row
         * @param array $row  Row
         * @param bool  $n    Add if TRUE
         * @return bool|null
         */
        public function save($row, $n = null)
        {
            $id = empty($row[$this->_primary]) ? null : $row[$this->_primary];
            if (empty($id) || $n) return $this->add($row);
            return $this->update($row, $id) ? $id : false;
        }

        /**
         * Creates instance of Model by path or data
         * @param mixed $data  Initialization data or path
         * @return Model
         * @throws Error
         * @throws ErrorNotFound
         */
        public static function create($data)
        {
            try {
                $cn   = \xbweb::uses($data, static::NODE_TYPE);
                $path = $data;
                $data = null;
            } catch (\Exception $e) {
                $cn = '\\xbweb\\models\\Table';
                if (is_array($data)) {
                    if (empty($data['path'])) throw new Error('Model has no path');
                    $path = $data['path'];
                } else {
                    $fn = static::file($data);
                    if (!file_exists($fn)) throw new ErrorNotFound('Model not found', $data);
                    $path = $data;
                    $data = json_decode(file_get_contents($fn), true);
                }
            }
            if (empty(self::$_models[$path])) self::$_models[$path] = new $cn($path, $data);
            return self::$_models[$path];
        }

        /**
         * Model file
         * @param string $path  Model path
         * @return string
         */
        public static function file($path)
        {
            $_      = explode('/', $path);
            $module = array_shift($_);
            $model  = empty($_) ? 'table' : implode('/', $_);
            return (empty($module) ? Paths\CORE : Paths\MODULES.$module.'/').'data/tables/'.$model.'.json';
        }
    }