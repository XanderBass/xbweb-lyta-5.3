<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Insert query
     * @category     DB queries
     * @link         https://xbweb.ru/doc/dist/classes/db/insert
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\DB;
    use xbweb\DBError;
    use xbweb\Field;
    use xbweb\Model;

    /**
     * Class Insert
     * @property-read $rows  Rows
     */
    class Insert extends Query {
        protected $_rows = array();

        /**
         * Constructor
         * @param Model  $model  Model
         * @param string $name   Query name
         */
        public function __construct(Model $model, $name = null) {
            $this->_opts = array(
                'on_duplicate_key_update' => true,
                'auto_create_table'       => false,
                'wrap_in_transaction'     => true,
                'stop_on_row_error'       => true,
                'low_priority'            => false,
                'delayed'                 => false,
                'ignore'                  => false
            );
            parent::__construct($model, $name);
        }

        /**
         * Insert query
         * @param array $row  Row data
         * @return string
         */
        protected function _insert($row) {
            $pk = $this->_model->primary;
            $op = $this->_opts('low_priority', 'delayed', 'ignore');
            if (empty($this->_opts['on_duplicate_key_update'])) {
                $keys = '`'.implode('`,`', array_keys($row)).'`';
                $vals = implode(',', array_values($row));
                return "insert {$op} into `{$this->_table}` ({$keys}) values ({$vals})";
            } else {
                $keys = array();
                $vals = array();
                $upd  = array();
                foreach ($row as $k => $v) {
                    $keys[] = "`{$k}`";
                    $vals[] = $v;
                    if ($k == $pk) continue;
                    $upd[]  = "`{$k}` = values(`{$k}`)";
                }
                $keys = implode(',', $keys);
                $vals = implode(',', $vals);
                $upd  = implode(',', $upd);
                return "insert {$op} into `{$this->_table}` ({$keys}) values ({$vals}) on duplicate key update {$upd}";
            }
        }

        /**
         * Add row
         * @param array $row  Data row
         * @param mixed $id   Custom ID
         * @return $this
         */
        public function row($row, $id = null) {
            $cl = array();
            $rv = array();
            $pk = $this->_model->primary;
            if ($id === null) if (isset($row[$pk])) $id = $row[$pk];
            if ($this->_fields === null) {
                foreach ($this->_model->fields as $fn => $F) {
                    if (!isset($row[$fn])) continue;
                    $cl[] = $fn;
                    $rv[$fn] = Field::pack($F, $row[$fn]);
                }
            } else {
                foreach ($this->_fields as $fn) {
                    if (!isset($row[$fn])) $row[$fn] = null;
                    if (isset($this->_model->fields[$fn])) {
                        $cl[] = $fn;
                        $rv[$fn] = Field::pack($this->_model->fields[$fn], $row[$fn]);
                    }
                }
            }
            $r = array();
            foreach ($cl as $li) $r[] = "`{$li}`";
            $pid = implode(',', $r);
            if (empty($this->_rows[$pid])) $this->_rows[$pid] = array();
            if ($id === null) {
                $this->_rows[$pid][] = $rv;
            } else {
                $this->_rows[$pid][$id] = $rv;
            }
            return $this;
        }

        /**
         * Define fields to add
         * @param[] mixed  Field name(s)
         * @return $this
         */
        public function fields() {
            $fl = func_get_args();
            if (count($fl) == 1) {
                if (is_array($fl[0])) {
                    $fl = $fl[0];
                } elseif ($fl[0] == '*') {
                    $fl = array_keys($this->_model->fields);
                } else {
                    $fl = explode(',', $fl[0]);
                }
            }
            $this->_fields = empty($fl) ? null : $fl;
            return $this;
        }

        /**
         * Get SQL query
         * @return array
         */
        public function sql() {
            $ret = array();
            foreach ($this->_rows as $clist => $values) {
                foreach ($values as $id => $row) {
                    $ret[] = $this->_insert($row);
                }
            }
            return $ret;
        }

        /**
         * Execute query
         * @return array
         * @throws \Exception
         */
        public function execute() {
            $point  =  empty($this->_name) ? __METHOD__ : $this->_name;
            $trans  = !empty($this->_opts['wrap_in_transaction']);
            $sore   = !empty($this->_opts['stop_on_row_error']);
            $ctable =  empty($this->_opts['auto_create_table']) ? null : $this->_model->tableSQL();
            $tname  = \xbweb::id($point);
            $IDs    = array();
            $errors = array();
            if ($trans) DB::startTransaction($tname);
            try {
                foreach ($this->_rows as $clist => $values) {
                    foreach ($values as $id => $row) {
                        $q = $this->_insert($row);
                        if ($result = DB::query($q, $ctable, $point)) {
                            if ($result->success) {
                                $IDs[$id] = $result->id;
                            } else {
                                if ($sore) throw new DBError('Row not inserted ('.$id.')');
                                $errors[] = $id;
                            }
                        } else {
                            if ($sore) throw new DBError('Insert error ('.$id.')');
                            $errors[] = $id;
                        }
                    }
                }
                if ($trans) DB::applyTransaction($tname);
            } catch (\Exception $e) {
                if ($trans) DB::cancelTransaction($tname);
                throw $e;
            }
            return array('ids' => array_values($IDs), 'errors' => $errors);
        }
    }