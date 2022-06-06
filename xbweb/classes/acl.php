<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Access control lists component
     * @category     CMF components
     * @link         https://xbweb.org/doc/dist/classes/acl
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * ACL component class
     */
    class ACL {
        protected static $_cache = null;

        /**
         * Check action granted
         * @param string $action  Action (full path with module) or NULL for current action
         * @param User   $user    User object (NULL for current user)
         * @param bool   $drop    Drop exceptions
         * @return bool
         * @throws Error
         * @throws ErrorNotFound
         * @throws \Exception
         */
        public static function granted($action = null, $user = null, $drop = false) {
            if ($action === null) {
                $module = Request::get('module');
                $action = Request::get('controller').'/'.Request::get('action');
            } else {
                $_ = explode('/', $action);
                $module = array_shift($_);
                $action = count($_) > 1 ? implode('/', $_) : 'controller/'.implode('/', $_);
            }
            $group = lib\Access::GN_ANONIMOUS;
            if ($user === null) try {
                $user = User::current();
                $group = $user->role;
                if ($group === 'root') return true;
            } catch (\Exception $e) {
                if ($drop) throw $e;
            }
            $acl = self::module($module);
            if (!isset($acl[$action])) {
                if (!$drop) return false;
                throw new ErrorNotFound('Action not found (ACL)', $module.'/'.$action);
            }
            return lib\Access::actionGranted($group, $acl[$action]);
        }

        /**
         * Get module ACL
         * @param string $name  Module name
         * @return mixed
         */
        public static function module($name = null) {
            if (self::$_cache === null) self::$_cache = array();
            if (empty($name)) $name = 'system';
            if (empty(self::$_cache[$name])) {
                if (empty($name)) $name = 'system';
                $R  = array();
                $fn = ($name == 'system' ? Paths\CORE : Paths\MODULES.$name.'/').'data/acl.json';
                if (file_exists($fn)) if ($R = json_decode(file_get_contents($fn), true)) {
                    array_walk($R, function(&$v) {
                        $v = lib\Access::actionToInt($v);
                        return $v;
                    });
                }
                self::$_cache[$name] = $R;
            }
            return self::$_cache[$name];
        }
    }