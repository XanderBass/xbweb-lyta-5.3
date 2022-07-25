<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Update query
     * @category     DB queries
     * @link         https://xbweb.ru/doc/dist/classes/db/update
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\DB;
    use xbweb\DBError;
    use xbweb\Model;

    /**
     * Class Update
     * @property-read array $rows  Rows
     * @property-read array $row   Row
     */
    class Update extends Conditioned
    {
        protected $_rows = array();
        protected $_row  = null;

        /**
         * Constructor
         * @param Model  $model  Model
         * @param string $name   Query name
         */
        public function __construct(Model $model, $name = null)
        {
            $this->_fields = array();
            $this->_opts   = array(
                'low_priority' => false,
                'ignore'       => false
            );
            parent::__construct($model, $name);
        }

        /**
         * Define fields to add
         * @param[] mixed  Field name(s)
         * @return $this
         */
        public function fields()
        {
            return $this;
        }

        /**
         * Set field value
         * @param string $field  Field name
         * @param mixed  $value  Field value
         * @return $this
         * @throws \xbweb\NodeError
         */
        public function field($field, $value)
        {
            if (!$this->_model->hasField($field)) return $this;
            $this->_fields[$field] = $this->_model->pack($field, $value);
            return $this;
        }

        /**
         * Set cell value
         * @param mixed  $id     Row ID
         * @param string $field  Field name
         * @param mixed  $value  Fielde value
         * @return $this
         * @throws \xbweb\NodeError
         */
        public function cell($id, $field, $value)
        {
            if (!$this->_model->hasField($field)) return $this;
            $this->_rows[$field][$id] = $this->_model->pack($field, $value);
            return $this;
        }

        /**
         * Set row values
         * @param array $row  Row
         * @param mixed $id   Row ID
         * @return Update
         * @throws \xbweb\NodeError
         */
        public function row(array $row, $id)
        {
            $this->_row = array();
            foreach ($row as $field => $value) {
                if (!$this->_model->hasField($field)) continue;
                $this->_row[$field] = $this->_model->pack($field, $value);
            }
            $this->_where = new Where($this->_model);
            $this->_where->condition($this->_model->primary, $id);
            return $this;
        }

        /**
         * Get SQL string
         * @return string
         * @throws DBError
         */
        public function sql()
        {
            if (!empty($this->_row)) {
                $S = array();
                foreach ($this->_row as $field => $value) $S[] = "`{$field}` = {$value}";
                $S = implode(',', $S);
                $where = $this->_where();
            } else {
                $ids = array();
                $S   = array();
                $pk  = $this->_model->primary;
                foreach ($this->_fields as $field => $value) $S[] = "`{$field}` = {$value}";
                foreach ($this->_rows as $field => $items) {
                    $I = array();
                    foreach ($items as $id => $value) {
                        if (!in_array($id, $ids)) $ids[] = $id;
                        $I = "when '{$id}' then {$value}";
                    }
                    $I   = implode(' ', $I);
                    $S[] = "`{$field}` = case `{$pk}` {$I} else `{$field}` end";
                }
                if (empty($this->_fields) && empty($this->_where)) {
                    if (empty($ids)) throw new DBError('No data to update');
                    $where = " where `{$pk}` in ('".implode("','", $ids)."')";
                } else {
                    $where = $this->_where();
                }
                $S = implode(',', $S);
            }
            $A     = $this->_model->alias;
            $opts  = $this->_opts();
            $order = $this->_order();
            $limit = empty($this->_limit) ? '' : ' limit '.$this->_limit;
            return <<<sql
update {$opts} `{$this->_table}` as {$A} set {$S} {$where}{$order}{$limit}
sql;
        }

        /**
         * Execute query
         * @return bool|mixed|Result
         * @throws DBError
         */
        public function execute()
        {
            return DB::query($this->sql());
        }
    }