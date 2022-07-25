<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Database result prototype
     * @category     DB components
     * @link         https://xbweb.org/doc/dist/classes/db/result
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\DB;

    /**
     * Class Result
     * @property-read Provider $provider  DB Provider
     * @property-read string   $state     SQL state
     * @property-read int      $count     Row count
     * @property-read int      $total     Total row count
     * @property-read mixed    $id        Last inserted ID
     * @property-read bool     $success   Success flag
     * @property-read array    $cache     Current cache
     * @property-read array    $all       All rows
     */
    class Result
    {
        protected $_provider = null;
        protected $_state    = null;
        protected $_count    = null;
        protected $_total    = null;
        protected $_id       = null;
        protected $_success  = false;
        protected $_cache    = null;
        protected $_result   = null;

        /**
         * Constructor
         * @param Provider $provider  DB provider
         * @param mixed    $result    Result data
         */
        public function __construct(Provider $provider, $result = null)
        {
            $this->_provider = $provider;
            $this->_state    = empty($result['state']) ? null : $result['state'];
            $this->_count    = empty($result['count']) ? 0    : intval($result['count']);
            $this->_total    = empty($result['total']) ? 0    : intval($result['total']);
            $this->_id       = empty($result['id'])    ? null : $result['id'];
            if (!empty($result['result'])) {
                $this->_success = true;
                if ($result['result'] instanceof \mysqli_result) {
                    $this->_result = $result['result'];
                }
            }
        }

        /**
         * Getter
         * @param string $name  Parameter name
         * @return mixed
         */
        public function __get($name)
        {
            switch ($name) {
                case 'all':
                    return $this->get_all();
                default:
                    return property_exists($this, "_{$name}") ? $this->{"_{$name}"} : null;
            }
        }

        /**
         * Get all rows
         * @param string $primary  Primary key
         * @return mixed
         */
        protected function get_all($primary = null)
        {
            if ($this->_cache === null) {
                $this->_cache = $this->rows($primary);
            }
            return $this->_cache;
        }

        /**
         * Validate result object
         * @return bool
         */
        protected function _no_result()
        {
            return (!($this->_result instanceof \mysqli_result));
        }

        /**
         * Get row
         * @return array|bool
         */
        public function row()
        {
            if ($this->_no_result()) return false;
            return $this->_result->fetch_assoc();
        }

        /**
         * Get multiple rows
         * @param string   $primary  Primary key
         * @param callable $cb       Callback for each row
         * @return array|bool
         */
        public function rows($primary = null, $cb = null)
        {
            if ($this->_no_result()) return false;
            $ret = array();
            $this->_result->data_seek(0);
            while ($row = $this->_result->fetch_assoc()) {
                if (is_callable($cb)) $row = $cb($row);
                if (empty($primary)) {
                    $ret[] = $row;
                } else {
                    if (empty($row[$primary])) continue;
                    $ret[$row[$primary]] = $row;
                }
            }
            return $ret;
        }

        /**
         * Get multiple rows and apply them to data
         * @param array    $data  Data
         * @param callable $cb    Callback for each row
         * @return array
         */
        public function apply($data, $cb)
        {
            if ($this->_no_result()) return $data;
            if (!is_callable($cb)) return $data;
            $this->_result->data_seek(0);
            while ($row = $this->_result->fetch_assoc()) $data = $cb($data, $row);
            return $data;
        }

        /**
         * Get IDs
         * @param string $field  Primary key
         * @param bool   $int    Key is integer
         * @return array|bool
         */
        public function ids($field, $int = false)
        {
            static $cache = null;
            if ($this->_no_result()) return false;
            if ($cache === null) $cache = array();
            if (!empty($cache[$field])) return $cache[$field];
            $ret = array();
            $this->_result->data_seek(0);
            while ($row = $this->_result->fetch_assoc()) {
                if (empty($row[$field])) continue;
                $ret[] = $int ? intval($row[$field]) : $row[$field];
            }
            $cache[$field] = $ret;
            return $ret;
        }

        /**
         * Set pointer to begin
         * @return bool
         */
        public function reset()
        {
            if ($this->_no_result()) return false;
            return $this->_result->data_seek(0);
        }
    }