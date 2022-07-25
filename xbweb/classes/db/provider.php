<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Database provider
     * @category     DB components
     * @link         https://xbweb.org/doc/dist/classes/db/provider
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    use xbweb\DBError;
    use xbweb\DuplicateError;
    use xbweb\Error;
    use xbweb\NoTableError;

    /**
     * Database provider prototype class
     * @property-read array  $config  Configuration
     * @property-read string $type    DB type
     * @property-read bool   $ready   Ready flag
     * @property-read string $prefix  Prefix
     * @property-read array  $log     Log
     */
    class Provider
    {
        protected $_config = array();
        protected $_type   = null;
        protected $_ready  = false;
        protected $_prefix = '';
        protected $_log    = array();
        protected $_ph     = array();
        protected $_db     = null;

        /**
         * Constructor
         * @param array $config Configuration
         * @throws DBError
         */
        protected function __construct(array $config)
        {
            $this->_config = static::config($config);
            $this->_type   = 'MySQL';
            $this->_prefix = empty($config['prefix']) ? '' : $config['prefix'];
            foreach ($this->_config as $k => $v) $this->_ph['[+'.$k.'+]'] = $v;
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $this->_db = new \mysqli(
                    $this->_config['host'],
                    $this->_config['user'],
                    $this->_config['pass'],
                    $this->_config['name'],
                    $this->_config['port']
                );
                $this->_db->set_charset($this->_config['charset']);
            } catch (\mysqli_sql_exception $e) {
                throw new DBError($e->getMessage(), $e->getCode());
            }
            $this->_ready = true;
        }

        /**
         * Getter
         * @param string $name  Parameter name
         * @return mixed
         */
        public function __get($name)
        {
            return property_exists($this, "_{$name}") ? $this->{"_{$name}"} : null;
        }

        /**
         * Destructor
         */
        public function __destruct()
        {
            $this->_db->close();
        }

        /**
         * Execute SQL Query
         * @param string $q      SQL query
         * @param Query  $obj    Query object
         * @param string $point  Point name
         * @return \xbweb\DB\Result
         * @throws DBError
         * @throws DuplicateError
         * @throws NoTableError
         */
        public function do_query($q, $obj = null, $point = null)
        {
            try {
                $R = $this->_db->query(strtr($q, $this->_ph));
                if ($obj instanceof Select) {
                    if ($obj->option('sql_calc_found_rows')) {
                        $q = 'select found_rows() as `total`';
                        if ($T = $this->_db->query($q)) {
                            $cqk = $this->log($q, $point);
                            $T = $T->fetch_assoc();
                            $total = $T['total'];
                            $this->log_result($total, $cqk);
                        }
                    } elseif ($obj->option('get_total_rows')) {
                        $q = $obj->sql_count();
                        if ($T = $this->_db->query($q)) {
                            $cqk = $this->log($q, $point);
                            $T = $T->fetch_assoc();
                            $total = $T['total'];
                            $this->log_result($total, $cqk);
                        }
                    }
                }
                return new Result($this, array(
                    'id'     => $this->_db->insert_id,
                    'count'  => $this->_db->affected_rows,
                    'total'  => empty($total) ? 0 : $total,
                    'state'  => $this->_db->sqlstate,
                    'result' => $R
                ));
            } catch (\mysqli_sql_exception $e) {
                if (intval($e->getCode()) == 1062) throw new DuplicateError($e->getMessage());
                if (intval($e->getCode()) == 1146) throw new NoTableError($e->getMessage());
                throw new DBError($e->getMessage(), $e->getCode());
            }
        }

        /**
         * Escape string
         * @param string $v  Value
         * @return mixed
         */
        public function escape($v)
        {
            return $this->_db->real_escape_string($v);
        }

        /**
         * Start transaction
         * @param string $name  Transaction point name
         * @return bool
         */
        public function startTransaction($name = null)
        {
            if ($this->_db->autocommit(false)) {
                return $this->_db->begin_transaction(0, $name);
            }
            return false;
        }

        /**
         * Apply (commit) current transaction
         * @param string $name  Transaction point name
         * @return bool
         */
        public function applyTransaction($name = null)
        {
            if ($this->_db->commit(0, $name)) {
                return $this->_db->autocommit(true);
            }
            return false;
        }

        /**
         * Cancel (rollback) current transaction
         * @param string $name  Transaction point name
         * @return bool
         */
        public function cancelTransaction($name = null)
        {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            if ($this->_db->rollback(0, $name)) {
                return $this->_db->autocommit(true);
            }
            return false;
        }

        /**
         * Execute query (full wrapper)
         * @param string $q        SQL query string
         * @param mixed  $install  SQL query string for creating missing table
         * @param string $point    Debug point information
         * @return bool|Result
         * @throws DBError
         * @throws DuplicateError
         * @throws Error
         */
        public function query($q, $install = null, $point = null)
        {
            $sql  = ($q instanceof Query) ? $q->sql() : $q;
            $obj  = ($q instanceof Query) ? $q        : null;
            $qkey = null;
            for ($E = 2; $E > 0; $E--) {
                $qkey = $this->log($sql, $point, $qkey);
                try {
                    $R = $this->do_query($sql, $obj, $point);
                    $this->log_result($R->success, $qkey);
                    return $R;
                } catch (DuplicateError $e) {
                    $this->log_result($e->getMessage(), $qkey);
                    throw $e;
                } catch (NoTableError $e) {
                    if ($install === true) {
                        $this->log_result(false, $qkey);
                        return false;
                    }
                    if (empty($install)) throw $e;
                    $tkey = self::log($install, $point);
                    $R    = $this->do_query($install, null, $point);
                    $this->log_result($R->success, $tkey);
                } catch (DBError $e) {
                    $this->log_result($e->getMessage(), $qkey);
                    throw $e;
                }
            }
            throw new Error('Emergency_counter in DB query');
        }

        /**
         * Table with prefix
         * @param string $n  Table name
         * @return string
         */
        public function table($n)
        {
            return $this->_prefix.$n;
        }

        /**
         * Get one row
         * @param string $q      SQL query
         * @param string $point  Query point
         * @return array|bool
         * @throws DBError
         * @throws DuplicateError
         * @throws Error
         */
        public function row($q, $point = null)
        {
            if ($result = $this->query($q, true, $point)) return $result->row();
            return false;
        }

        /**
         * Get multiple rows
         * @param string   $q        SQL query
         * @param string   $primary  Primary row key
         * @param callable $cb       Callback on each row (rowa; returns row)
         * @param string   $point    Debug point
         * @return bool|array
         * @throws DBError
         * @throws Error
         */
        public function rows($q, $primary = null, $cb = null, $point = null)
        {
            if ($result = $this->query($q, true, $point)) return $result->rows($primary, $cb);
            return false;
        }

        /**
         * Get multiple rows and apply them to data
         * @param string   $q      SQL query
         * @param array    $data   Data
         * @param callable $cb     Callback for each row (data, row; returns data)
         * @param string   $point  Debug point
         * @return mixed
         * @throws DBError
         * @throws Error
         */
        public function apply($q, $data, $cb, $point = null)
        {
            if ($result = $this->query($q, true, $point)) return $result->apply($data, $cb);
            return $data;
        }

        /**
         * Log query
         * @param string $q      SQL query
         * @param string $point  Query point
         * @param string $key    Query key
         * @return string
         */
        public function log($q, $point = null, $key = null)
        {
            if ($key === null) $key = \xbweb::id('sqllog');
            $this->_log[$key] = array('query' => $q, 'point' => $point);
            return $key;
        }

        /**
         * Log query result
         * @param mixed  $result  Result
         * @param string $key     Query key
         * @return string
         */
        public function log_result($result, $key = null)
        {
            if ($key === null) $key = \xbweb::id('sqllog');
            $this->_log[$key]['result'] = $result;
            return $key;
        }

        /**
         * Get instance of DB provider
         * @param array $config  Configuration
         * @return Provider
         */
        public static function create(array $config)
        {
            $cn = '\\xbweb\\DB\\Provider';
            return new $cn($config);
        }

        /**
         * Correct configuration
         * @param array $config  Configuration
         * @return array
         */
        public static function config(array $config)
        {
            $r = array();
            $d = array(
                'user'    => '',
                'pass'    => '',
                'name'    => '',
                'prefix'  => '',
                'host'    => '127.0.0.1',
                'port'    => 3306,
                'charset' => str_replace('-', '', \xbweb\Config::get('charset', 'utf-8'))
            );
            foreach ($d as $k => $v) $r[$k] = isset($config[$k]) ? $config[$k] : $v;
            $d['type'] = 'MySQL';
            return $r;
        }
    }