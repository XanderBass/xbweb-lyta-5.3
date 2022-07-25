<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Conditions container
     * @category     Database components
     * @link         https://xbweb.org/doc/dist/classes/db/where
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\lib\Flags as LibFlags;

    use xbweb\BasicObject;
    use xbweb\DB;
    use xbweb\Model;

    /**
     * Class Where
     * @property-read array  $conditions  Conditions list
     * @property-read Model  $model       Model
     * @property-read string $operation   Conjuncting operation
     */
    class Where extends BasicObject
    {
        protected $_conditions = array();
        protected $_model      = null;
        protected $_operation  = 'and';

        /**
         * Constructor
         * @param Model  $model       Model
         * @param string $operation   Operation
         * @param array  $conditions  Conditions
         * @throws \xbweb\NodeError
         */
        public function __construct(Model $model, $operation = 'and', $conditions = array())
        {
            $this->_model     = $model;
            $this->_operation = $operation;
            if (!empty($conditions)) {
                foreach ($conditions as $cond) {
                    if (empty($cond['field'])) continue;
                    $o = empty($cond['operation']) ? '='  : $cond['operation'];
                    $v = empty($cond['value'])     ? null : $cond['value'];
                    $this->condition($cond['field'], $v, $o);
                }
            }
        }

        /**
         * Set condition
         * @param string $field      Field name
         * @param mixed  $value      Value
         * @param string $operation  Operation
         * @return $this
         * @throws \xbweb\NodeError
         */
        public function condition($field, $value = null, $operation = '=')
        {
            $A = $this->_model->alias;
            if ($field instanceof Where) {
                $this->_conditions[] = $field;
                return $this;
            }
            if (!$this->_model->hasField($field)) return $this;
            switch ($operation) {
                case 'is':
                    return $this->_bit($field, $value, '=', false);
                case 'is_not':
                case 'isnot':
                    return $this->_bit($field, $value, '=', true);
                case 'oneof':
                case 'one_of':
                    return $this->_bit($field, $value, '>', true);
            }
            $operation = DB::operation($operation, '=');
            if (is_array($value)) {
                switch ($operation) {
                    case '=' : $operation = 'in'; break;
                    case '<>': $operation = 'not in'; break;
                    default  : $operation = 'in';
                }
            } else {
                if ($value === null) {
                    if ($operation == '=')  $operation = 'is null';
                    if ($operation == '<>') $operation = 'is not null';
                }
            }
            switch ($operation) {
                case 'in':
                case 'not in':
                    $value = is_array($value) ? $value : explode(',', $value);
                    $value_a = array();
                    foreach ($value as $value_i) $value_a[] = $this->_model->pack($field, $value_i);
                    $value = '('.implode(',', $value_a).')';
                    break;
                default:
                    $value = $this->_model->pack($field, $value);
            }
            $this->_conditions[] = array(
                'field'     => "{$A}.`{$field}`",
                'value'     => $value,
                'operation' => $operation
            );
            return $this;
        }

        /**
         * Compare condition
         * @param string $field1     Field name
         * @param string $field2     External field
         * @param string $operation  Operation
         * @return $this
         */
        public function compare($field1, $field2, $operation = '=')
        {
            $A = $this->_model->alias;
            if (!$this->_model->hasField($field1)) return $this;
            $operation = DB::operation($operation, '=');
            $this->_conditions[] = array(
                'field'     => "{$A}.`{$field1}`",
                'value'     => $field2,
                'operation' => $operation
            );
            return $this;
        }

        /**
         * Set condition by POST field
         * @param string $name       Field name
         * @param array  $values     Allowed values
         * @param string $operation  Operation
         * @return $this
         * @throws \xbweb\NodeError
         */
        public function field($name, $values = null, $operation = '=')
        {
            if (!$this->_model->hasField($name) || !isset($_POST[$name])) return $this;
            $value = $_POST[$name];
            if ($values !== null) {
                $values = \xbweb::arg($values);
                if (!in_array($value, $values)) return $this;
            }
            return $this->condition($name, $value, $operation);
        }

        /**
         * Bit condition
         * @param string $f  Field name
         * @param mixed  $v  Value
         * @param string $o  Operation
         * @param bool   $n  Operate with zero
         * @return $this
         */
        protected function _bit($f, $v, $o, $n = false)
        {
            $A  = $this->_model->alias;
            $fd = $this->_model->fields[$f];
            $fk = empty($fd['data']['values']) ? array() : $fd['data']['values'];
            $fv = LibFlags::keyValue($fk, $v);
            $this->_conditions[] = array(
                'field'     => "({$A}.`{$f}` & {$fv})",
                'value'     => $n ? 0 : $fv,
                'operation' => $o
            );
            return $this;
        }

        /**
         * String value
         * @return string
         */
        public function __toString()
        {
            $ret = array();
            foreach ($this->_conditions as $cond) {
                if ($cond instanceof Where) {
                    $cond = strval($cond);
                    if (!empty($cond)) $ret[] = "({$cond})";
                } else {
                    switch ($cond['operation']) {
                        case 'is null':
                        case 'is not null':
                            $ret[] = "({$cond['field']} {$cond['operation']})";
                            break;
                        default:
                            $ret[] = "({$cond['field']} {$cond['operation']} {$cond['value']})";
                    }
                }
            }
            return implode(" {$this->_operation} ", $ret);
        }

        /**
         * Static create
         * @param Model  $model       Model
         * @param string $operation   Operation
         * @param array  $conditions  Conditions
         * @return Where
         * @throws \xbweb\NodeError
         */
        public static function create(Model $model, $operation = 'and', $conditions = array())
        {
            return new self($model, $operation, $conditions);
        }
    }