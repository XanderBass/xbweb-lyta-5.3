<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Session component prototype
     * @category     Session components
     * @link         https://xbweb.org/doc/dist/classes/acl
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Session component prototype class
     * @property-read array $data          Session DB data
     * @property      bool  $blocked       Block flag
     * @property-read bool  $authorized    Authorization flag
     * @property      mixed $user          User ID
     * @property-read bool  $renew         Renew session data
     * @property-read bool  $blockExpired  Block expired
     * @property-read bool  $IPValid       IP valid
     * @property-read bool  $brutforce     Brutforce flag
     */
    abstract class Session {
        const DEFCLASS  = 'Files';
        const TABLE     = 'users_sessions';
        const NAME      = 'XBWEBSESSID';
        const BLOCKTIME = 3600;
        const LIFETIME  = 86400;
        const FAILCOUNT = 3;
        const BFTH      = 3;

        protected static $_session = null;

        protected $_table   = '';
        protected $_config  = null;
        protected $_ready   = false;
        protected $_data    = null;
        protected $_isNew   = false;
        protected $_domain  = null;
        protected $_renew   = false;

        /**
         * Constructor
         * @throws Error
         */
        public function __construct() {
            $this->_table  = Config::get('session/table', static::TABLE);
            $this->_config = array(
                'name'            => Config::get('session/name', static::NAME),
                'failcount'       => intval(Config::get('session/failcount', static::FAILCOUNT)),
                'brutforce_th'    => intval(Config::get('session/brutforce_th', static::BFTH)),
                'blocktime'       => intval(Config::get('session/blocktime', static::BLOCKTIME)),
                'lifetime'        => intval(Config::get('session/lifetime', static::LIFETIME)),
                'cookie_secure'   => filter_var(Config::get('session/cookie_secure', true), FILTER_VALIDATE_BOOLEAN),
                'cookie_httponly' => filter_var(Config::get('session/cookie_httponly', true), FILTER_VALIDATE_BOOLEAN),
                'crossdomain'     => filter_var(Config::get('session/crossdomain', false), FILTER_VALIDATE_BOOLEAN),
            );
            if (!Request::isHTTPS()) $this->_config['cookie_secure'] = false;
            $this->_domain = $this->_config['crossdomain'] ? '.'.(Request::RSN()) : Request::domain();
            $INI = array(
                'name'              => $this->_config['name'],
                'cookie_secure'     => $this->_config['cookie_secure'],
                'cookie_lifetime'   => $this->_config['lifetime'],
                'cookie_httponly'   => $this->_config['cookie_httponly'],
                'gc_maxlifetime'    => $this->_config['lifetime'],
                'serialize_handler' => 'php_serialize'
            );
            foreach ($INI as $k => $v) if (ini_set('session.'.$k, $v) === false) throw new Error(array(
                'message' => 'Cannot set session INI parameter',
                'name'    => $k
            ));
            session_set_cookie_params(
                $this->_config['lifetime'], '/', $this->_domain,
                !empty($this->_config['cookie_secure']),
                !empty($this->_config['cookie_httponly'])
            );
            session_name($this->_config['name']);
            $this->_ready = true;
        }

        /**
         * Getter
         * @param string $name  Property name
         * @return mixed
         */
        public function __get($name) {
            $IP = Request::IP();
            $IT = Request::IPType();
            $T  = self::tableName();
            switch ($name) {
                case 'data'        : return $this->_data;
                case 'blocked'     : return !empty($this->_data['blockuntil']);
                case 'authorized'  : return !empty($this->_data['user']);
                case 'user'        : return empty($this->_data['user']) ? null : intval($this->_data['user']);
                case 'blockExpired':
                    if (!empty($this->_data['blockuntil'])) {
                        $now = new \DateTime();
                        $nx  = new \DateTime($this->_data['blockuntil']);
                        return ($now >= $nx);
                    }
                    return false;
                case 'IPValid':
                    if (empty($this->_data[$IT])) return false;
                    if ($this->_data[$IT] == $IP) return true;
                    return (!Events::invoke('sessionIPMismatch'));
                case 'brutforce':
                    if ($IP != '127.0.0.1') {
                        $IP = inet_pton($IP);
                        $q  = <<<sql
select count(*) as `count` from `{$T}`
where (`blockuntil` is not null) and (`{$IT}` = '{$IP}')
sql;
                        if ($row = DB::row($q, __METHOD__.'.'.$name)) {
                            $c = empty($row['count']) ? 0 : intval($row['count']);
                            if (($c > $this->_config['brutforce_th'])) return Events::invoke('attackBrutforce');
                        }
                    }
                    return false;

            }
            return null;
        }

        /**
         * Setter
         * @param string $name   Parameter name
         * @param mixed  $value  Value
         * @return mixed
         * @throws \Exception
         */
        public function __set($name, $value) {
            if (method_exists($this, "set_{$name}")) {
                $ret = $this->{"set_{$name}"}($value);
                $this->_data['last'] = null;
                return $ret;
            }
            return null;
        }

        /**
         * "blocked" setter
         * @param mixed $value  Value
         * @return bool
         * @throws \Exception
         */
        protected function set_blocked($value) {
            if (empty($value)) {
                $this->_data['blockuntil'] = null;
                $this->_data['failcount']  = 0;
            } else {
                if (empty($this->_data['blockuntil'])) {
                    $nx  = new \DateTime();
                    $nx->add(new \DateInterval('PT'.$this->_config['blocktime'].'S'));
                    $this->_data['blockuntil'] = $nx->format('Y-m-d H:i:s');
                }
                $this->_data['failcount'] = 0;
            }
            $this->_data['last'] = null;
            return !empty($this->_data['blockuntil']);
        }

        /**
         * "user" setter
         * @param mixed $value User ID
         * @param bool $safe
         * @return int
         */
        protected function set_user($value, $safe = false) {
            $value   = intval($value);
            $current = intval($this->_data['user']);
            if ($value == $current) return $current;
            if (empty($value)) {
                $this->_data['user'] = null;
            } else {
                $this->_data['user']      = intval($value);
                $this->_data['failcount'] = 0;
                $this->_data['safe']      = !empty($safe);
            }
            $this->_data['last'] = null;
            return $this->_data['user'];
        }

        /**
         * "Failed" signal
         */
        public function failed() {
            $this->_data['failcount'] = intval($this->_data['failcount']) + 1;
            $this->_data['last']      = null;
        }

        /**
         * Save current session data
         * @return bool
         * @throws \Exception
         */
        public function save() {
            $renew = false;
            if (empty($this->_data['sid'])) return false;
            if (!$this->_isNew) {
                if ($this->_data['failcount'] >= $this->_config['failcount']) {
                    $this->set_blocked(true);
                    $renew = true;
                }
                if ($this->blockExpired) {
                    $this->set_blocked(false);
                    $renew = true;
                }
            }
            if (empty($this->_data['last'])) $renew = true;
            if ($this->_renew && !$renew) $renew = false;
            $this->_data['last'] = $renew ? null : \xbweb::now();
            // Process
            $table = self::tableName();
            $IP = Request::IP();
            $IT = Request::IPType();
            $UA = Request::userAgent();
            // Query
            $Q = $this->_data;
            $Q['useragent'] = empty($UA) ? 'null' : "'".DB::escape($UA)."'";
            foreach (array('last', 'blockuntil') as $k) $Q[$k] = empty($Q[$k]) ? 'null' : "'{$Q[$k]}'";
            foreach (array(4, 6) as $k) $Q['ipv'.$k] = ($IT == 'ipv'.$k) ? "('".inet_pton($IP)."')" : 'null';
            $Q['user']       = empty($Q['user'])      ? 'null' : intval($Q['user']);
            $Q['failcount']  = empty($Q['failcount']) ? 0      : intval($Q['failcount']);
            $sql = <<<sql
insert into `{$table}` values(
  '{$Q['sid']}' , {$Q['user']}      ,
  {$Q['ipv4']}  , {$Q['ipv6']}      , {$Q['useragent']}, 
  {$Q['last']}  , {$Q['failcount']} , {$Q['blockuntil']}
) on duplicate key update
  `user`      = values(`user`)      ,
  `ipv4`      = values(`ipv4`)      , `ipv6` = values(`ipv6`) , `useragent`  = values(`useragent`) ,
  `failcount` = values(`failcount`) , `last` = values(`last`) , `blockuntil` = values(`blockuntil`)
sql;
            if ($result = DB::query($sql, self::table(), __METHOD__)) {
                if ($result->success) {
                    if (!empty($this->_data['safe'])) {
                        $sql = <<<sql
update `{$table}` set `last` = null, `user` = null where `user` = {$Q['user']}
sql;
                        DB::query($sql, true, __METHOD__);
                    }
                    return Events::invoke('sessionSaveData');
                }
            }
            return false;
        }

        /**
         * Read session data
         * @param $SID
         */
        public function select($SID) {
            $table = self::tableName();
            $sql = "select * from `{$table}` where `sid` = '{$SID}'";
            if ($row = DB::row($sql, __METHOD__)) {
                $this->_data = self::correct($row, true);
                if (empty($this->_data['last'])) {
                    $this->_renew = true;
                    $this->_data['last'] = \xbweb::now();
                }
            }
        }

        /**
         * Delete session
         * @param mixed $SID  Session IDs
         * @return bool
         */
        public function delete($SID) {
            $table = self::tableName();
            $SID = is_array($SID) ? implode("','", $SID) : $SID;
            $sql = "delete from `{$table}` where `sid` in ('{$SID}')";
            if ($result = DB::query($sql, true, __METHOD__)) return $result->success;
            return true;
        }

        /**
         * Update timestamp of sessions
         * @param mixed $SID  Session IDs
         * @return bool
         */
        public function touch($SID) {
            $table = self::tableName();
            $sql = "update `{$table}` set `last` = now() where `sid` = '{$SID}'";
            if ($result = DB::query($sql, true, __METHOD__)) return $result->success;
            return true;
        }

        /**
         * Write new session
         * @param string $SID  Session ID
         * @return bool
         */
        public function create($SID) {
            $table = self::tableName();
            $IP = Request::IP();
            $IT = Request::IPType();
            $UA = Request::userAgent();
            if (empty($IP)) $IP = '127.0.0.1';
            $IP = inet_pton($IP);
            $IP = DB::escape($IP);
            $Q  = array(
                'ipv4'      => ($IT == 'ipv4' ? "'{$IP}'" : 'null'),
                'ipv6'      => ($IT == 'ipv6' ? "'{$IP}'" : 'null'),
                'useragent' => (empty($UA)    ? "'{$UA}'" : 'null'),
            );
            $sql = <<<sql
insert into `{$table}` values(
  '{$SID}', null, {$Q['ipv4']}, {$Q['ipv6']}, {$Q['useragent']}, null, 0, null
) on duplicate key update
  `user` = null, `failcount` = 0, `last` = null, `blockuntil` = null,
  `ipv4` = values(`ipv4`), `ipv6` = values(`ipv6`), `useragent` = values(`useragent`)
sql;
            if ($result = DB::query($sql, self::table(), __METHOD__)) return $result->success;
            return false;
        }

        /**
         * Purge session table
         */
        public function purge() {
            // TODO
        }

        /**
         * Open session
         * @return bool
         */
        abstract public function open();

        /**
         * Close the session
         * @return bool
         */
        abstract public function close();

        /**
         * Read session data
         * @param string $SID  The session id to read data for.
         * @return string
         * @throws Error
         */
        abstract public function read($SID);

        /**
         * Write session data
         * @param string $SID   The session id.
         * @param string $data  Data string
         * @return bool
         * @throws Error
         */
        abstract public function write($SID, $data);

        /**
         * Destroy a session
         * @param string $SID  The session ID being destroyed.
         * @return bool
         * @throws DBError
         * @throws Error
         */
        abstract public function destroy($SID);

        /**
         * Cleanup old sessions
         * @param int $maxlifetime  Max lifetime
         * @return bool
         * @throws DBError
         * @throws Error
         */
        abstract public function gc($maxlifetime);

        /**
         * Create new SID
         * @return string
         * @throws Error
         */
        abstract public function create_sid();

        /**
         * Validate session ID
         * @param string $SID  Session ID
         * @return bool
         */
        abstract public function validate_sid($SID);

        /**
         * Update timestamp
         * @param string $SID  Session ID
         * @return bool
         * @throws DBError
         * @throws Error
         */
        abstract public function update_timestamp($SID);

        /**
         * Correct session data
         * @param array $data    Session data
         * @param bool  $fromdb  Data from DB
         * @return array
         */
        public static function correct($data = null, $fromdb = false) {
            $IP = Request::IP();
            $IT = Request::IPType();
            $def = array(
                'sid'        => null,
                'user'       => null,
                'last'       => null,
                'ipv4'       => $data === null ? ($IT == 'ipv4' ? $IP : null) : null,
                'ipv6'       => $data === null ? ($IT == 'ipv6' ? $IP : null) : null,
                'useragent'  => $data === null ? Request::userAgent() : null,
                'failcount'  => 0,
                'blockuntil' => null
            );
            $ret = empty($data) ? array() : $data;
            foreach ($def as $k => $d) if (empty($ret[$k])) $ret[$k] = $d;
            if ($fromdb && !empty($ret['ipv4'])) $ret['ipv4'] = inet_ntop($ret['ipv4']);
            if ($fromdb && !empty($ret['ipv6'])) $ret['ipv6'] = inet_ntop($ret['ipv6']);
            $ret['user']      = empty($ret['user']) ? null : intval($ret['user']);
            $ret['failcount'] = intval($ret['failcount']);
            return $ret;
        }

        /**
         * Session object instance
         * @return Session
         * @throws Error
         */
        public static function instance() {
            $cn = '\\xbweb\\Sessions\\'.Config::get('session/class', static::DEFCLASS);
            if (empty(self::$_session)) {
                self::$_session = new $cn();
                if (!session_set_save_handler(
                    array(self::$_session, 'open'),
                    array(self::$_session, 'close'),
                    array(self::$_session, 'read'),
                    array(self::$_session, 'write'),
                    array(self::$_session, 'destroy'),
                    array(self::$_session, 'gc'),
                    array(self::$_session, 'create_sid')
                )) throw new Error('Cannot set session handler');
                register_shutdown_function(array(self::$_session, 'save'));
            }
            return self::$_session;
        }

        /**
         * Initialize session object
         * @throws Error
         * @return bool
         */
        public static function init() {
            static $started = false;
            if ($started && (self::$_session instanceof self)) return true;
            $session = static::instance();
            if (session_start()) {
                if ($session->brutforce) throw new ErrorForbidden('Brutforce attack detected');
                if (!$session->IPValid) $session->_data['user'] = null;
                $started = true;
                session_write_close();
                return true;
            }
            return false;
        }

        /**
         * Get value by key
         * @param string $key  Key
         * @param mixed  $def  Default value
         * @return mixed
         */
        public static function get($key, $def = null) {
            return lib\Arrays::get($_SESSION, $key, $def);
        }

        /**
         * Set session value by key
         * @param string $key  Key
         * @param mixed  $val  Value
         * @return mixed
         * @throws Error
         */
        public static function set($key, $val = null) {
            $key = trim($key, '/');
            if (empty($key)) return $_SESSION;
            session_start();
            if (is_array($key)) {
                $r = $key;
                foreach ($key as $k => $v)
                    $r[$k] = lib\Arrays::set($_SESSION, $k, $v);
            } else {
                $r = lib\Arrays::set($_SESSION, $key, $val);
                if ($key == 'user') {
                    self::instance()->set_user(empty($val['id']) ? null : intval($val['id']));
                }
            }
            session_write_close();
            return $r;
        }

        /**
         * Set user
         * @param array $data User data
         * @param bool $safe
         * @return mixed
         * @throws Error
         */
        public static function setUser($data, $safe = false) {
            session_start();
            $_SESSION['user'] = $data;
            self::instance()->set_user(empty($data['id']) ? null : intval($data['id'], $safe));
            session_write_close();
            return $data;
        }

        /**
         * Table name
         * @return string
         */
        public static function tableName() {
            static $t = null;
            if ($t === null) $t = DB::table(Config::get('session/table', static::TABLE));
            return $t;
        }

        /**
         * SQL table
         * @return string
         */
        public static function table() {
            $ts = self::tableName();
            $tu = DB::table('users');
            return <<<sql
create table if not exists `{$ts}` (
  `sid`        char(32) binary not null,
  `user`       bigint null,
  `ipv4`       varbinary(4) null,
  `ipv6`       varbinary(16) null,
  `useragent`  text null,
  `last`       datetime null,
  `failcount`  tinyint not null default '0',
  `blockuntil` datetime null,
  primary key (`sid`),
  foreign key (`user`) references `{$tu}`(`id`) on update set null on delete set null
) engine = InnoDB comment = 'Users sessions'
sql;
        }
    }