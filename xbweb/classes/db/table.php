<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Create table query
     * @category     DB queries
     * @link         https://xbweb.ru/doc/dist/classes/db/table
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\DB;
    use xbweb\Field;
    use xbweb\Model;

    /**
     * Class Table
     */
    class Table extends Query {
        /**
         * Constructor
         * @param Model  $model  Model object
         * @param string $name   Query name
         */
        public function __construct(Model $model, $name = null) {
            $this->_opts = array(
                'if_not_exists' => true
            );
            parent::__construct($model, $name);
        }

        /**
         * Get field string
         * @param array $data  Field data
         * @return bool
         */
        protected function _field($data) {
            $ai       = in_array('auto_increment', $data['attributes']) ? ' auto_increment' : '';
            $isnull   = $ai ? false : in_array('isnull', $data['attributes']);
            $unsigned = in_array('unsigned', $data['attributes']) ? ' unsigned' : '';
            $binary   = in_array('binary', $data['attributes']) ? ' binary' : '';
            $default  = $isnull ? '' : ($data['default'] === null ? '' : " default '{$data['default']}'");
            $length   = empty($data['data']['length'])    ? null : intval($data['data']['length']);
            $pre      = empty($data['data']['precision']) ? null : intval($data['data']['precision']);
            $isnull   = ($isnull ? '' : ' not').' null';
            switch ($data['base_type']) {
                case Field::T_BYTE:
                case Field::T_WORD:
                case Field::T_INT:
                case Field::T_INT_BIG:
                    $length = empty($length) ? '' : "({$length})";
                    switch ($data['base_type']) {
                        case Field::T_BYTE    : return 'tinyint'.$length.$unsigned.$isnull.$ai.$default;
                        case Field::T_WORD    : return 'smallint'.$length.$unsigned.$isnull.$ai.$default;
                        case Field::T_INT_BIG : return 'bigint'.$length.$unsigned.$isnull.$ai.$default;
                        default               : return 'int'.$length.$unsigned.$isnull.$ai.$default;
                    }
                case Field::T_FLOAT:
                case Field::T_DOUBLE:
                case Field::T_DECIMAL:
                    $length   = empty($length) ? '' : (empty($pre) ? "({$length})" : "({$length},{$pre})");
                    switch ($data['base_type']) {
                        case Field::T_FLOAT  : return 'float'.$length.$unsigned.$isnull.$default;
                        case Field::T_DOUBLE : return 'double'.$length.$unsigned.$isnull.$default;
                        default              : return 'decimal'.$length.$unsigned.$isnull.$default;
                    }
                case Field::T_SERIAL      : return 'bigint not null auto_increment';
                case Field::T_BOOL        : return 'char(0) null';
                case Field::T_VAR         : $length = empty($length) ? '' : "({$length})"; return 'varchar'.$length.$binary.$isnull.$default;
                case Field::T_STR         : $length = empty($length) ? '' : "({$length})"; return 'char'.$length.$binary.$isnull.$default;
                case Field::T_TEXT        : return 'text'.$isnull;
                case Field::T_TEXT_MEDIUM : return 'mediumtext'.$isnull;
                case Field::T_TEXT_TINY   : return 'tinytext'.$isnull;
                case Field::T_TEXT_LONG   : return 'longtext'.$isnull;
                case Field::T_BLOB        : return 'blob'.$isnull;
                case Field::T_BLOB_MEDIUM : return 'mediumblob'.$isnull;
                case Field::T_BLOB_TINY   : return 'tinyblob'.$isnull;
                case Field::T_BLOB_LONG   : return 'longblob'.$isnull;
                case Field::T_DATE        : return 'date'.$isnull;
                case Field::T_TIME        : return 'time'.$isnull;
                case Field::T_DATETIME    : return 'datetime'.$isnull;
                default: return false;
            }
        }

        /**
         * SQL query
         * @return string
         */
        public function sql() {
            $fl = array();
            $pk = array();
            $un = array();
            $ix = array();
            $ll = array();
            $ml = 0;
            foreach ($this->_model->fields as $fn => $fd) {
                $field = $this->_field($fd);
                if (empty($field)) continue;
                $fl[$fn] = $field;
                if (strlen($fn) > $ml) $ml = strlen($fn);
                if (in_array('primary', $fd['attributes'])) {
                    $pk[] = "`{$fn}`";
                } elseif (in_array('node', $fd['attributes'])) {
                    $un[] = "`{$fn}`";
                } elseif (in_array('index', $fd['attributes'])) {
                    $ix[] = ",\r\n  index (`{$fn}`)";
                } elseif (in_array('unique', $fd['flags'])) {
                    $ix[] = ",\r\n  unique index (`{$fn}`)";
                }
                if (!empty($fd['link'])) {
                    $t = DB::table($fd['link']['table']);
                    $f = $fd['link']['field'];
                    $u = $fd['link']['update'];
                    $d = $fd['link']['delete'];
                    $ll[] = ",\r\n  foreign key (`{$fn}`) references `{$t}`(`{$f}`) on update {$u} on delete {$d}";
                }
            }

            $ne = empty($this->_opts['if_not_exists']) ? '' : 'if not exists';
            $fr = array();
            foreach ($fl as $fn => $field) {
                $fr[] = '  '.str_pad("`{$fn}`", $ml + 2, ' ', STR_PAD_RIGHT).' '.$field;
            }
            $fl = implode(",\r\n", $fr);
            $pk = empty($pk) ? '' : ",\r\n  ".'primary key ('.implode(',', $pk).')';
            $un = empty($un) ? '' : ",\r\n  ".'unique index `node`('.implode(',', $un).')';
            $ix = empty($ix) ? '' : implode('', $ix);
            $ll = empty($ll) ? '' : implode('', $ll);
            return <<<sql
create table {$ne} `{$this->_table}` (
{$fl}{$pk}{$un}{$ix}{$ll}
) engine = InnoDB
sql;
        }

        /**
         * Execute query
         * @return bool
         */
        public function execute() {
            $sql = $this->sql();
            if ($result = DB::query($sql, null, $this->_name)) return $result->success;
            return false;
        }

        /**
         * Define fields to add
         * @param[] mixed  Field name(s)
         * @return $this
         */
        public function fields() {
            return $this;
        }
    }