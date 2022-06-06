<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Access functions library
     * @category     Logic libraries
     * @link         https://xbweb.org/doc/dist/classes/lib/access
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\lib;

    /**
     * Access functions library
     */
    class Access {
        const AP_DEFAULT  = 0;
        const AP_DISALLOW = 2;
        const AP_ALLOW    = 3;

        const FACL_ALL      = 0x7fffffff;
        const FACL_ALL_READ = 0x71111111;
        const FACL_ALL_CP   = 0x7111ff11;
        const FACL_ADMIN    = 0x7111f111;
        const FACL_SYSTEM   = 0x70000000;

        const GN_ANONIMOUS = 'anonimous';
        const GN_USER      = 'user';
        const GN_MODERATOR = 'moderator';
        const GN_ADMIN     = 'admin';
        const GN_INACTIVE  = 'inactive';
        const GN_BLOCKED   = 'blocked';
        const GN_DELETED   = 'deleted';
        const GN_SYSTEM    = 'system';
        const GN_ROOT      = 'root';

        protected static $_groups     = null;
        protected static $_rights     = null;
        protected static $_access     = null;
        protected static $_map        = null;
        protected static $_gmap       = null;
        protected static $_priorities = null;

        /**
         * Library initialization
         * @return bool
         */
        public static function __init() {
            if (is_array(self::$_map)) return true;
            self::$_groups = array(
                static::GN_ANONIMOUS,
                static::GN_USER,
                static::GN_MODERATOR,
                static::GN_ADMIN,
                static::GN_INACTIVE,
                static::GN_BLOCKED,
                static::GN_DELETED,
                static::GN_SYSTEM
            );
            self::$_rights = array('read', 'create', 'update', 'save');
            self::$_access = array(
                'all_read' => static::FACL_ALL_READ,
                'all_cp'   => static::FACL_ALL_CP,
                'admin'    => static::FACL_ADMIN,
                'system'   => static::FACL_SYSTEM,
            );
            self::$_map  = array();
            self::$_gmap = array();
            foreach (self::$_groups as $gi => $g) {
                self::$_gmap[$g] = (1 << $gi);
                foreach (self::$_rights as $ri => $r) self::$_map[$g][$r] = (1 << (($gi * count(self::$_rights)) + $ri));
            }
            self::$_priorities = array(
                static::GN_SYSTEM    => 5, static::GN_ROOT    => 4, static::GN_ADMIN     => 3,
                static::GN_MODERATOR => 2, static::GN_USER    => 1, static::GN_INACTIVE  => 0,
                static::GN_BLOCKED   => 0, static::GN_DELETED => 0, static::GN_ANONIMOUS => 0
            );
            return true;
        }

        /**
         * Convert to integer variant of action value
         * @param mixed $v  Input value
         * @return int
         */
        public static function actionToInt($v) {
            if (is_numeric($v)) $v = intval($v);
            if (is_int($v)) return ($v & 0x7f);
            if ($v == 'all') return 0x7f;
            $v   = \xbweb::arg($v);
            $ret = 0;
            foreach (self::$_groups as $gi => $g) if (in_array($g, $v)) $ret |= (1 << $gi);
            return $ret;
        }

        /**
         * Convert to array variant of action value
         * @param mixed $v  Input value
         * @return array
         */
        public static function actionToArray($v) {
            if (is_numeric($v)) $v = intval($v);
            if ($v == 'all') return self::$_groups;
            $ret = array();
            if (is_int($v)) {
                foreach (self::$_gmap as $gi => $g) if (($v & $g) != 0) $ret[] = $gi;
            } else {
                $v = \xbweb::arg($v);
                foreach (self::$_groups as $gi => $g) if (in_array($g, $v)) $ret[] = $g;
            }
            return $ret;
        }

        /**
         * Check if action granted for basic group
         * @param string $g  Basic group name
         * @param mixed  $v  Action value
         * @return bool
         */
        public static function actionGranted($g, $v = 0) {
            if (empty($g)) return false;
            if (($g == static::GN_ROOT) || ($g == static::GN_SYSTEM)) return true;
            $v = self::actionToInt($v);
            if (in_array($g, self::$_groups)) return ((self::$_gmap[$g] & $v) != 0);
            return false;
        }

        /**
         * Redefine personal ACL
         * @param int  $v  Input value
         * @param bool $d  Basic value
         * @return bool
         */
        public static function actionPersonal($v = 0, $d = false) {
            $o = ($v & 0x03);
            if ($o == static::AP_DEFAULT) return $d;
            if ($o == static::AP_ALLOW)   return true;
            return false;
        }

        /**
         * Convert to array variant of CRUS single group value
         * @param mixed $v  Input value
         * @return array
         */
        public static function CRUSValue($v) {
            if (is_array($v)) return array_values(array_intersect(self::$_rights, $v));
            switch ($v) {
                case 'read'        : return array('read');
                case 'full'        : return self::$_rights;
                case 'premoderated': return array('read', 'create', 'update');
            }
            if (is_numeric($v)) {
                $r = array();
                $v = intval($v) & 0x0f;
                foreach (self::$_rights as $p => $n) if ($v & ($p << 1)) $r[] = $n;
                return $r;
            } else {
                return array_values(array_intersect(self::$_rights, array_map('trim', explode(',', $v))));
            }
        }

        /**
         * Convert to integer variant of CRUS value
         * @param mixed $v  Input value
         * @return int
         */
        public static function CRUSToInt($v) {
            if (is_numeric($v)) $v = intval($v);
            if (is_int($v)) return ($v & static::FACL_ALL);
            $ret = 0;
            if (is_array($v)) {
                $go = empty($v['others']) ? array() : self::CRUSValue($v['others']);
                foreach (self::$_groups as $gi => $g) {
                    $ge = true;
                    if (!empty($v[$g])) {
                        $gv = self::CRUSValue($v[$g]);
                        foreach (self::$_rights as $ri => $r) {
                            if (in_array($r, $gv)) {
                                $ge   = false;
                                $ret |= self::$_map[$g][$r];
                            }
                        }
                    }
                    if ($ge) foreach (self::$_rights as $ri => $r) {
                        if (in_array($r, $go)) $ret |= self::$_map[$g][$r];
                    }
                }
            } elseif (array_key_exists($v, self::$_access)) {
                return self::$_access[$v];
            } else {
                $v = self::CRUSValue($v);
                foreach (self::$_groups as $g) {
                    foreach (self::$_rights as $r)
                        if (in_array($r, $v)) $ret |= self::$_map[$g][$r];
                }
            }
            return $ret;
        }

        /**
         * Convert to array variant of CRUS value
         * @param mixed $v  Input value
         * @return array
         */
        public static function CRUSToArray($v) {
            if (is_numeric($v)) $v = intval($v);
            $ret = array();
            if (is_array($v)) {
                $go = empty($v['others']) ? array() : self::CRUSValue($v['others']);
                foreach (self::$_groups as $gi => $g) {
                    if (!empty($v[$g])) {
                        $gv = self::CRUSValue($v[$g]);
                        $ret[$g] = array_intersect(self::$_rights, $gv);
                    }
                    if (empty($ret[$g])) $ret[$g] = $go;
                }
            } else {
                if (!is_int($v)) $v = self::CRUSToInt($v);
                if (empty($v)) return array();
                foreach (self::$_map as $gn => $g)
                    foreach ($g as $rn => $r) if (($v & $r) != 0) $ret[$gn][] = $rn;
            }
            return $ret;
        }

        /**
         * Check access of CRUS entity
         * @param string $g  Basic group
         * @param mixed  $r  Operations
         * @param mixed  $v  Input value
         * @return bool
         */
        public static function CRUSGranted($g, $r, $v = 0) {
            if (($g == static::GN_ROOT) || ($g == static::GN_SYSTEM)) return true;
            $v   = self::CRUSToInt($v);
            $ret = false;
            if (in_array($g, self::$_groups)) {
                $r   = \xbweb::arg($r);
                $ret = true;
                foreach (self::$_map[$g] as $rn => $rv)
                    if (in_array($rn, $r)) $ret &= (($v & $rv) != 0);
            }
            return $ret;
        }

        /**
         * Check if editor has priority above entity
         * @param string $editor  Basic group of editor
         * @param string $entity  Basic group of entity
         * @return bool
         */
        public static function hasPriority($editor, $entity) {
            if ($editor == static::GN_SYSTEM) return true;
            if ($editor == static::GN_ROOT)   return ($entity != 'system');
            return (self::$_priorities[$entity] < self::$_priorities[$editor]);
        }
    }

    Access::__init();