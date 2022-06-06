<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  User entity
     * @category     CMF components
     * @link         https://xbweb.org/doc/dist/classes/user
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\lib\Users;
    use xbweb\lib\Access;

    /**
     * User entity class
     * @property      array  $data         User data
     * @property      int    $id           User ID
     * @property-read bool   $authorized   Authorized
     * @property-read string $role         Role
     * @property-read bool   $canModerate  Can moderate
     */
    class User {
        /** @var User */
        protected static $_current = null;

        protected $_data = array();

        /**
         * Constructor
         * @param mixed $id  User ID or data
         * @throws Error
         */
        public function __construct($id = null) {
            if (empty($id)) {
                // Anonimous
                $this->_data = Users::correct();
            } elseif (is_array($id)) {
                // Custom by data
                $this->_data = Users::correct($id);
            } else {
                // By intval ID
                $id      = intval($id);
                $current = intval(Session::instance()->user);
                if (($current == $id) && (!Session::instance()->renew)) {
                    $this->_data = Users::correct(Session::get('user'));
                } else {
                    $this->_data = Users::getByID($id);
                }
            }
        }

        /**
         * Getter
         * @param string $name  Property name
         * @return mixed
         */
        public function __get($name) {
            switch ($name) {
                case 'authorized'  : return !empty($this->_data['id']);
                case 'role'        : return Users::role($this->_data);
                case 'id'          : return intval($this->_data['id']);
                case 'canModerate' :
                    $role = Users::role($this->_data);
                    return in_array($role, array('root', 'neo', 'admin', 'moderator'));
            }
            if (property_exists($this, "_{$name}")) return $this->{"_{$name}"};
            return null;
        }

        /**
         * Setter
         * @param string $name   Property name
         * @param mixed  $value  Value
         * @return mixed
         * @throws Error
         * @throws ErrorNotFound
         */
        public function __set($name, $value) {
            switch ($name) {
                case 'id':
                    $value = intval($value);
                    if ($value === intval($this->_data['id'])) return $value;
                    if (empty($value)) {
                        $this->_data = Users::correct();
                    } else {
                        $this->_data = Users::getByID($value);
                    }
                    return $value;
                case 'data':
                    $this->_data = Users::correct($value);
                    return $this->_data;
            }
            return null;
        }

        /**
         * Get/set current user object
         * @param mixed $data  Data to set
         * @param bool  $safe  Safe authorization
         * @return User
         * @throws Error
         */
        public static function current($data = null, $safe = false) {
            if (empty(self::$_current)) {
                if ($data === null) {
                    $id = intval(Session::instance()->user);
                } elseif (empty($data)) {
                    $id = 0;
                } else {
                    $id = $data;
                }
                self::$_current = new self($id);
            } elseif (is_array($data)) {
                self::$_current->_data = Users::correct($data);
                Session::setUser(self::$_current->_data, $safe);
            } else {
                if ($data !== null) {
                    self::$_current->id = intval($data);
                    Session::setUser(self::$_current->_data, $safe);
                }
            }
            return self::$_current;
        }

        /**
         * Check if user authorized. If not redirect to login URL
         * @return bool
         * @throws Error
         */
        public static function checkAuthorized() {
            if (Request::get('context') == Request::CTX_API) {

            } else {
                $p = Request::get('context') == Request::CTX_ADMIN ? '/admin' : '';
                if (static::current()->authorized) return true;
                \xbweb::redirect($p.\xbweb\URLs\LOGIN);
            }
            return false;
        }

        /**
         * Check if user has access to administration panel
         * @return bool
         * @throws Error
         * @throws ErrorForbidden
         */
        public static function checkAdminAllowed() {
            if (static::checkAuthorized()) {
                $g = static::current()->role;
                if (in_array($g, array(
                    lib\Access::GN_SYSTEM, lib\Access::GN_ROOT,
                    lib\Access::GN_ADMIN , lib\Access::GN_MODERATOR,
                ))) return true;
                throw new ErrorForbidden('You have no access to administration panel');
            }
            return false;
        }

        /**
         * Check if CRUS for field granted
         * @param mixed $r  Rights
         * @param mixed $f  Value to check
         * @return bool
         * @throws Error
         */
        public static function CRUSGranted($r, $f) {
            static $ug = null;
            if ($ug === null) $ug = static::current()->role;
            return Access::CRUSGranted($ug, $r, $f);
        }
    }