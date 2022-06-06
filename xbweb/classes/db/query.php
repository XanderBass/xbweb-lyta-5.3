<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Database query prototype
     * @category     DB components
     * @link         https://xbweb.org/doc/dist/classes/db/query
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\DB;
    use xbweb\Model;

    /**
     * Class Query
     * @property-read string $name    Name
     * @property-read Model  $model   Model
     * @property-read array  $opts    Options
     * @property-read array  $fields  Fields
     * @property-read string $table   Table name
     */
    abstract class Query {
        protected $_name   = null;
        protected $_model  = null;
        protected $_opts   = array();
        protected $_fields = null;
        protected $_table  = null;

        /**
         * Constructor
         * @param Model  $model  Model object
         * @param string $name   Query name
         */
        public function __construct(Model $model, $name = null) {
            $this->_name  = $name;
            $this->_model = $model;
            $this->_table = DB::table($this->_model->table);
        }

        /**
         * Getter
         * @param string $name  Parameter name
         * @return mixed
         */
        public function __get($name) {
            return property_exists($this, "_{$name}") ? $this->{"_{$name}"} : null;
        }

        /**
         * Set option
         * @param string $k  Option name
         * @param mixed  $v  Option value
         * @return $this
         */
        public function option($k, $v = null) {
            if ($v === null) return isset($this->_opts[$k]) ? $this->_opts[$k] : null;
            if (isset($this->_opts[$k])) {
                if (is_int($this->_opts[$k])) {
                    $this->_opts[$k] = intval($v);
                } elseif (is_bool($this->_opts[$k])) {
                    $this->_opts[$k] = boolval($v);
                } else {
                    $this->_opts[$k] = $v;
                }
            }
            return $this;
        }

        /**
         * Get options
         * @return string
         */
        protected function _opts() {
            $l = func_get_args();
            $r = array();
            foreach ($l as $li) if (!empty($this->_opts[$li])) $r[] = $li;
            return implode(' ', $r);
        }

        /**
         * Define fields to add
         * @param[] mixed  Field name(s)
         * @return $this
         */
        abstract public function fields();

        /**
         * Execute query
         * @return mixed
         */
        abstract public function execute();

        /**
         * Get SQL query
         * @return mixed
         */
        abstract public function sql();

        /**
         * Get limit from request
         * @param int $limit  Default limit
         * @return string
         */
        public static function getLimitFromRequest($limit = 30) {
            $page  = empty($_REQUEST['page'])  ? 1      : intval($_REQUEST['page']);
            $limit = empty($_REQUEST['limit']) ? $limit : intval($_REQUEST['limit']);
            return ($page * $limit).','.$limit;
        }
    }