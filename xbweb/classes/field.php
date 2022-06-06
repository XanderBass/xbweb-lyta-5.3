<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Field prototype
     * @category     Fields
     * @link         https://xbweb.ru/doc/dist/classes/field
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\lib\Access as LibAccess;
    use xbweb\lib\Flags  as LibFlags;

    /**
     * Class Field
     * @method static correct(array $data) array
     * @method static pack(array $data, $value) mixed
     * @method static unpack(array $data, $value) mixed
     * @method static valid(array $data, $value) bool
     * @method static value(array $data, $value) mixed
     */
    abstract class Field {
        const T_SERIAL      = 0x01;
        const T_BOOL        = 0x02;
        const T_TIME        = 0x04;
        const T_DATE        = 0x05;
        const T_DATETIME    = 0x06;
        const T_BYTE        = 0x10;
        const T_WORD        = 0x11;
        const T_INT         = 0x12;
        const T_INT_BIG     = 0x13;
        const T_FLOAT       = 0x14;
        const T_DOUBLE      = 0x15;
        const T_DECIMAL     = 0x16;
        const T_STR         = 0x20;
        const T_VAR         = 0x21;
        const T_TEXT_TINY   = 0x30;
        const T_TEXT        = 0x31;
        const T_TEXT_MEDIUM = 0x32;
        const T_TEXT_LONG   = 0x33;
        const T_BLOB_TINY   = 0x34;
        const T_BLOB        = 0x35;
        const T_BLOB_MEDIUM = 0x36;
        const T_BLOB_LONG   = 0x37;

        const FLAGS       = 'required, unique, sortable, table';
        const ATTRIBUTES  = 'primary, auto_increment, isnull, binary, unsigned, index, node, system';

        const REX_CLASS   = '~^([\w\/]+)$~si';
        const DEF_CLASS   = '/str';

        const BASE_TYPE   = self::T_VAR;


        /**
         * Basic field correction
         * @param array $data  Field data
         * @return mixed
         * @throws Error
         * @throws \Exception
         */
        public static function field($data) {
            $data['base_type'] = static::BASE_TYPE;
            if (empty($data['name'])) throw new DataError('No field name');
            if (!preg_match('~^(\w+)$~si', $data['name'])) throw new DataError('Invalid field name');
            if (empty($data['class'])) $data['class'] = self::DEF_CLASS;
            if (!preg_match(static::REX_CLASS, $data['class'])) throw new FieldError('Invalid class', $data['name']);
            if (empty($data['classname'])) {
                if ($c = \xbweb::uses($data['class'], 'field')) {
                    $data['classname'] = $c;
                } else {
                    throw new DataError('Error loading field class', $data['class']);
                }
            }
            if (empty($data['input'])) $data['input'] = $data['class'];
            foreach (array('default', 'title', 'description', 'unique', 'index') as $k)
                $data[$k] = isset($data[$k]) ? $data[$k] : null;
            foreach (array('access', 'attributes', 'flags') as $k)
                $data[$k] = empty($data[$k]) ? 0 : $data[$k];
            $data['access']     = LibAccess::CRUSToArray($data['access']);
            $data['attributes'] = LibFlags::toArray(static::ATTRIBUTES, $data['attributes']);
            $data['flags']      = LibFlags::toArray(static::FLAGS, $data['flags']);
            $data['nullable']   = $data['default'] === null;
            $data['data']       = empty($data['data']) ? array() : (
                is_array($data['data']) ? $data['data'] : json_decode($data['data'], true)
            );
            $data['link'] = isset($data['link']) ? $data['link'] : null;
            if (is_array($data['link'])) {
                if (empty($data['link']['field'])) $data['link']['field'] = 'id';
                if (empty($data['link']['table'])) throw new DataError('No table for link', $data['name']);
                foreach (array('update', 'delete') as $k) {
                    $data['link'][$k] = empty($data['link'][$k]) ? 'set null' : (
                        in_array($data['link'][$k], array('cascade', 'restrict')) ? $data['link'][$k] : 'set null'
                    );
                    if ($data['link'][$k] == 'set null') $data['default'] = null;
                }
            }
            return $data;

        }

        /**
         * Correct field data
         * @param array $data  Field data
         * @return mixed
         * @throws Error
         */
        protected static function __correct($data) {
            return self::field($data);
        }

        /**
         * Call static
         * @param string $name  Function name
         * @param mixed  $args  Arguments
         * @return mixed
         * @throws Error
         * @throws \Exception
         */
        public static function __callStatic($name, $args) {
            if (empty($args[0]))     throw new FieldError('No field data', $name);
            if (!is_array($args[0])) throw new FieldError('Invalid field data', $name);
            $path = empty($args[0]['class']) ? self::DEF_CLASS : $args[0]['class'];
            if (empty($args[0]['classname'])) {
                if ($c = \xbweb::uses($path, 'field')) {
                    $args[0]['classname'] = $c;
                } else {
                    throw new DataError('Error loading field class', $path);
                }
            }
            if (!method_exists($args[0]['classname'], '__'.$name)) throw new DataError('Method does not realized', $path.':'.$name);
            return call_user_func_array(array($args[0]['classname'], '__'.$name), $args);
        }

        /**
         * Allowed or not
         * @param array  $data       Field data
         * @param string $operation  Operation
         * @param string $ug         User group
         * @return bool
         * @throws Error
         */
        public static function allowed($data, $operation, $ug = null) {
            if ($ug === null) $ug = User::current()->role;
            if (in_array('system', $data['attributes']) && ($operation != 'read')) return false;
            if ($ug == 'root') return true;
            return LibAccess::CRUSGranted($ug, $operation, $data['access']);
        }

        /**
         * Standart field sequences
         * @param array $field  Field data
         * @return array
         */
        public static function std($field) {
            $std = empty($field['std']) ? '' : $field['std'];
            switch ($std) {
                case 'created':
                    $ret = array(
                        'name'       => 'created',
                        'class'      => '/datetime',
                        'access'     => 'create,read',
                        'default'    => true,
                        'attributes' => 'system'
                    );
                    break;
                case 'updated':
                    $ret = array(
                        'name'       => 'updated',
                        'class'      => '/datetime',
                        'access'     => 'read,update',
                        'default'    => true,
                        'attributes' => 'system,isnull'
                    );
                    break;
                case 'deleted':
                    $ret = array(
                        'name'       => 'deleted',
                        'class'      => '/datetime',
                        'access'     => 'read',
                        'attributes' => 'system,isnull'
                    );
                    break;
                case 'rank':
                    $ret = array(
                        'name'       => 'rank',
                        'class'      => '/integer',
                        'access'     => 'read',
                        'attributes' => 'system'
                    );
                    break;
                case 'flags':
                    $ret = array(
                        'name'       => 'flags',
                        'class'      => '/flags',
                        'access'     => 'read',
                        'attributes' => 'system'
                    );
                    break;
                default: return $field;
            }
            foreach ($field as $k => $v) $ret[$k] = $v;
            return $ret;
        }
    }