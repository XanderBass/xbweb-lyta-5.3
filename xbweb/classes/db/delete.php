<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Delete query
     * @category     DB queries
     * @link         https://xbweb.ru/doc/dist/classes/db/delete
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\DB;
    use xbweb\Model;

    /**
     * Class Delete
     */
    class Delete extends Conditioned {
        /**
         * Constructor
         * @param Model  $model  Model
         * @param string $name   Query name
         */
        public function __construct(Model $model, $name = null) {
            $this->_opts = array(
                'low_priority' => false,
                'quick'        => false
            );
            parent::__construct($model, $name);
        }

        /**
         * Define fields to add
         * @param[] mixed  Field name(s)
         * @return $this
         */
        public function fields() {
            return $this;
        }

        /**
         * Get SQL string
         * @return string
         */
        public function sql() {
            $A     = $this->_model->alias;
            $opts  = $this->_opts('low_priority', 'quick');
            $where = $this->_where();
            $order = $this->_order();
            $limit = empty($this->_limit) ? '' : " limit {$this->_limit}";
            return <<<sql
delete {$opts} from `{$this->_table}` as {$A} {$where}{$order}{$limit}
sql;
        }

        /**
         * Execute query
         * @return mixed
         */
        public function execute() {
            return DB::query($this->sql());
        }
    }