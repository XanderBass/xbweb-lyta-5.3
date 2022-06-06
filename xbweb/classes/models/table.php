<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Standart table model
     * @category     Models
     * @link         https://xbweb.ru/doc/dist/classes/models/table
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\models;

    use xbweb\PipeLine;
    use xbweb\DB;
    use xbweb\Model;

    use xbweb\DB\Select as QuerySelect;
    use xbweb\DB\Insert as QueryInsert;
    use xbweb\DB\Update as QueryUpdate;
    use xbweb\DB\Table  as QueryTable;
    use xbweb\DB\Where  as Where;

    /**
     * Class Table
     */
    class Table extends Model {
        /**
         * Get one
         * @param mixed $id   ID
         * @param bool  $acl  Return only allowed fields
         * @return array|bool
         * @throws \xbweb\Error
         */
        public function getOne($id, $acl = true) {
            $table = DB::table($this->_table);
            $pkey  = $this->primary;
            $sql   = "select * from `{$table}` where `{$pkey}` = '{$id}'";
            if ($rows = DB::query($sql, true)) {
                if ($row = $rows->row()) {
                    $result = $acl ? $this->row($row) : $row;
                    return PipeLine::invoke($this->pipeName('row'), $result, 'one');
                }
            }
            return false;
        }

        /**
         * Get many by IDs
         * @param mixed $ids    IDs
         * @param bool  $acl    Return only allowed fields
         * @param mixed $total  Total found
         * @return bool|mixed
         * @throws \xbweb\Error
         * @throws \xbweb\NodeError
         */
        public function getByIDs($ids, $acl = true, &$total = null) {
            $ids = \xbweb::arg($ids);
            /** @var QuerySelect $query */
            $query = new QuerySelect($this);
            $query->option('get_total_rows', true);
            $query->option('ignore_no_table', true);
            $query->where(Where::create($this)->condition($this->_primary, $ids));
            $result = array();
            if ($rows = $query->execute()) {
                $total = $rows->total;
                while ($row = $rows->row()) {
                    $id          = $row[$this->primary];
                    $result[$id] = $acl ? $this->row($row) : $row;
                    $result[$id] = PipeLine::invoke($this->pipeName('row'), $result[$id], 'many');
                }
                return PipeLine::invoke($this->pipeName('rows'), $result);
            }
            return false;

        }

        /**
         * Get many
         * @param string   $name   Filter name
         * @param bool     $acl    Return only allowed fields
         * @param DB\Query $query  Query object
         * @param int      $total  Total found
         * @return array|bool
         * @throws \xbweb\Error
         * @throws \xbweb\NodeError
         */
        public function get($name = '', $acl = true, DB\Query &$query = null, &$total = null) {
            /** @var QuerySelect $query */
            $query = new QuerySelect($this);
            $query->option('get_total_rows', true);
            if ($name == 'items') $query->option('ignore_no_table', true);
            $query = PipeLine::invoke($this->pipeName('select'), $query, $name);
            $old   = $query->where;
            $op    = ($name == 'trash') ? '<>' : '=';
            $where = Where::create($this)->condition('deleted', null, $op);
            if ($old instanceof Where) $where->condition($old);
            $query
                ->where($where)
                ->limitFromRequest()
                ->orderFromRequest();
            if ($this->hasField('rank')) {
                if (!isset($query->order['rank'])) $query->order('rank', 'asc');
            }
            $result = array();
            if ($rows = $query->execute()) {
                $total = $rows->total;
                while ($row = $rows->row()) {
                    $id          = $row[$this->primary];
                    $result[$id] = $acl ? $this->row($row) : $row;
                    $result[$id] = PipeLine::invoke($this->pipeName('row'), $result[$id], 'many');
                }
                return PipeLine::invoke($this->pipeName('rows'), $result);
            }
            return false;
        }

        /**
         * Add row to database
         * @param array $row  Data row
         * @return array|bool
         * @throws \Exception
         */
        public function add($row) {
            $query = new QueryInsert($this);
            $query->row($row);
            if ($result = $query->execute()) {
                if (empty($result['ids'])) return false;
                return array_shift($result['ids']);
            }
            return false;
        }

        /**
         * Update one
         * @param array $row  Row
         * @param mixed $id   Record ID
         * @return bool
         * @throws \xbweb\NodeError
         * @throws \xbweb\DBError
         */
        public function update($row, $id) {
            $query = new QueryUpdate($this);
            $query->row($row, $id);
            if ($result = $query->execute()) return $result->success;
            return false;
        }

        /**
         * Create table
         * @return bool
         */
        public function table() {
            $query = new QueryTable($this);
            return $query->execute();
        }
    }