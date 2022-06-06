<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Conditioned query prototype
     * @category     DB prototypes
     * @link         https://xbweb.ru/doc/dist/classes/db/conditioned
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\Model;
    use xbweb\Request;

    /**
     * Class Conditioned
     * @property-read Where $where
     * @property-read array $order
     * @property-read int   $limit
     * @property-read int   $offset
     */
    abstract class Conditioned extends Query {
        protected $_where  = null;
        protected $_order  = array();
        protected $_limit  = null;
        protected $_offset = 0;
        protected $_joins  = array();
        protected $_groups = array();
        protected $_page   = 1;

        /**
         * Constructor
         * @param Model  $model  Model
         * @param string $name   Query name
         */
        public function __construct(Model $model, $name = null) {
            parent::__construct($model, $name);
        }

        /**
         * Add order condition
         * @param string $field  Field name
         * @param string $dir    Direction
         * @return $this
         */
        public function order($field, $dir = 'asc') {
            if (!$this->_model->hasField($field)) return $this;
            if ($dir != 'desc') $dir = 'asc';
            $this->_order[$field] = $dir;
            return $this;
        }

        /**
         * Add where condition
         * @param Where $where  Condition object
         * @return $this
         */
        public function where(Where $where) {
            $this->_where = $where;
            return $this;
        }

        /**
         * Add limit
         * @param int $limit   Limit
         * @param int $offset  Offset
         * @return $this
         */
        public function limit($limit, $offset = null) {
            $this->_limit  = intval($limit);
            $this->_offset = intval($offset);
            return $this;
        }

        /**
         * Get limit from REQUEST
         * @return Conditioned
         */
        public function limitFromRequest() {
            $page = intval(Request::get('id'));
            if (!empty($_REQUEST['page'])) $page = intval($_REQUEST['page']);
            if (empty($page)) $page = 1;
            $limit = empty($_REQUEST['limit']) ? $this->_model->limit : intval($_REQUEST['limit']);
            $this->_page = $page;
            return $this->limit($limit, ($page - 1) * $limit);
        }

        /**
         * Get order from REQUEST
         * @return Conditioned
         */
        public function orderFromRequest() {
            $field = empty($_REQUEST['order']) ? ''    : $_REQUEST['order'];
            $dir   = empty($_REQUEST['dir'])   ? 'asc' : $_REQUEST['dir'];
            if (!$this->_model->hasField($field)) return $this;
            return $this->order($field, $dir);
        }

        /**
         * Join table
         * @param Model $model  Model
         * @param array $on     ON conditions
         * @return Join
         */
        public function join($model, $on = array()) {
            $join = new Join($this->_model, $model, $on);
            $this->_joins[] = $join;
            return $join;
        }

        /**
         * Add GROUP information
         * @param string $field  Field
         * @return $this
         * @throws \xbweb\NodeError
         */
        public function group($field) {
            $this->_groups[] = $this->_model->field($field);
            return $this;
        }

        /**
         * Get ORDER string
         * @return string
         */
        protected function _order() {
            $A     = $this->_model->alias;
            $order = array();
            foreach ($this->_order as $fn => $dir) $order[] = "{$A}.`{$fn}` {$dir}";
            $order = implode(',', $order);
            return empty($order) ? '' : ' order by '.$order;
        }

        /**
         * Get WHERE string
         * @return string
         */
        protected function _where() {
            $where = ($this->_where instanceof Where) ? strval($this->_where) : '';
            return empty($where) ? '' : ' where '.$where;
        }

        /**
         * Get JOIN string
         * @return string
         */
        protected function _joins() {
            $joins = array();
            foreach ($this->_joins as $join) {
                $joins[] = strval($join);
            }
            return implode("\r\n", $joins);
        }
    }