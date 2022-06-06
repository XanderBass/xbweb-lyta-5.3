<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Join
     * @category     DB queries
     * @link         https://xbweb.ru/doc/dist/classes/db/join
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\BasicObject;
    use xbweb\DB;
    use xbweb\Model;

    /**
     * Class Join
     * @property-read array  $conditions  Conditions list
     * @property-read Model  $modelA      Model
     * @property-read Model  $modelB      Model
     * @property-read Int    $type        Join type
     */
    class Join extends BasicObject {
        const JT_INNER = 0;
        const JT_LEFT  = 1;
        const JT_RIGHT = 2;

        protected $_conditions = array();
        protected $_modelA     = null;
        protected $_modelB     = null;
        protected $_type       = self::JT_INNER;

        /**
         * Constructor
         * @param Model $modelA  Model A
         * @param Model $modelB  Model B
         * @param array $on      Conditions
         * @param int   $type    Join type
         */
        public function __construct(Model $modelA, Model $modelB , $on = array(), $type = self::JT_INNER) {
            $this->_modelA = $modelA;
            $this->_modelB = $modelB;
            $this->_type   = $type;
            if (!empty($on)) {
                foreach ($on as $cond) {
                    if (empty($cond['A']) || empty($cond['B'])) continue;
                    $o = empty($cond['operation']) ? '='  : $cond['operation'];
                    $this->on($cond['A'], $cond['B'], $o);
                }
            }
        }

        /**
         * Add compare condition
         * @param string $fieldA     Field from table A
         * @param string $fieldB     Field from table B
         * @param string $operation  Operation
         * @return $this
         */
        public function on($fieldA, $fieldB, $operation = '=') {
            $A = $this->_modelA->alias;
            $B = $this->_modelB->alias;
            if (!$this->_modelA->hasField($fieldA)) return $this;
            if (!$this->_modelB->hasField($fieldB)) return $this;
            $operation = DB::operation($operation, '=');
            $this->_conditions[] = array(
                'field'     => "{$A}.`{$fieldA}`",
                'value'     => "{$B}.`{$fieldB}`",
                'operation' => $operation
            );
            return $this;
        }

        /**
         * Add simple where
         * @param Where $where  Where block
         * @return $this
         */
        public function where($where) {
            if ($where instanceof Where) $this->_conditions[] = $where;
            return $this;
        }

        /**
         * String value
         * @return string
         */
        public function __toString() {
            $ret = array();
            foreach ($this->_conditions as $cond) {
                if ($cond instanceof Where) {
                    $cond = strval($cond);
                    $ret[] = "({$cond})";
                } else {
                    $ret[] = "({$cond['field']} {$cond['operation']} {$cond['value']})";
                }
            }
            $ret = implode(" and ", $ret);
            $t   = $this->_modelB->table;
            $a   = $this->_modelB->alias;
            switch ($this->_type) {
                case self::JT_LEFT : return  "left join `{$t}` as {$a} on ({$ret})";
                case self::JT_RIGHT: return "right join `{$t}` as {$a} on ({$ret})";
                default            : return "inner join `{$t}` as {$a} on ({$ret})";
            }
        }

        /**
         * Static create
         * @param Model $modelA  Model A
         * @param Model $modelB  Model B
         * @param array $on      Conditions
         * @return Join
         */
        public static function create(Model $modelA, Model $modelB, $on = array()) {
            return new self($modelA, $modelB, $on);
        }
    }