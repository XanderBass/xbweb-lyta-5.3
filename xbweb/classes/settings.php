<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Settings processor
     * @category     Models
     * @link         https://xbweb.ru/doc/dist/classes/settings
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\lib\Models;

    /**
     * Class Settings
     */
    class Settings {
        protected static $_cache = array();

        const TABLE = <<<SQL
create table if not exists `[+prefix+]settings` (
    `id`     varchar(32)  not null,
    `module` varchar(128) not null,
    `name`   varchar(128) not null,
    `value`  text not null,
    primary key (`id`),
    index (`module`),
    unique key `node`(`module`, `name`)
) engine = InnoDB
SQL;

        public static function allFields() {
            return self::$_cache;
        }

        /**
         * Get setting ID
         * @param string $path  Unified path
         * @return string
         */
        public static function id($path) {
            return md5($path);
        }

        /**
         * Get setting from database
         * @param string $module  Module
         * @return array
         * @throws FieldError
         */
        public static function get($module) {
            $fields = self::_fields($module);
            $values = array();
            if ($rows = DB::rows("select * from `[+prefix+]settings` where `module` = '{$module}'")) {
                foreach ($rows as $row) {
                    $fn = $row['name'];
                    if (empty($fields[$fn])) continue;
                    $values[$fn] = Field::unpack($fields[$fn], $row['value']);
                }
            }
            return $values;
        }

        /**
         * Save settings from request
         * @param string $module  Module
         * @param array  $values  Values
         * @param array  $errors  Errors
         * @return bool
         * @throws Error
         * @throws FieldError
         */
        public static function save($module, &$values = null, &$errors = null) {
            if (empty($_POST[$module])) return true;
            $fields  = self::_fields($module);
            $REQ     = Models::request($fields, 'update', $_POST[$module]);
            $values  = $REQ['values'];
            $errors  = $REQ['errors'];
            if (!empty($errors)) return false;
            $rows    = array();
            foreach ($values as $name => $value) {
                if (empty($fields[$name])) continue;
                $id    = self::id($module.'/'.$name);
                $value = trim(Field::pack($fields[$name], $value), "'");
                if ($value == 'null') continue;
                $rows[] = "('{$id}', '{$module}', '{$name}', '{$value}')";
            }
            if (empty($rows)) return true;
            $rows = implode(',', $rows);
            $q = <<<sql
insert into `[+prefix+]settings` values {$rows}
on duplicate key update `value` = values(`value`)
sql;
            if ($result = DB::query($q, self::TABLE)) return true;
            return false;
        }

        /**
         * Get settings form
         * @param string $module  Module
         * @return mixed
         * @throws Error
         * @throws FieldError
         */
        public static function form($module) {
            $fields = Models::form(self::_fields($module), 'update', self::get($module));
            $form   = array();
            foreach ($fields as $fn => $field) {
                $cat = empty($field['category']) ? 'main' : $field['category'];
                $form[$cat][$fn]         = $field;
                $form[$cat][$fn]['name'] = "{$module}[{$fn}]";
            }
            return $form;
        }

        /**
         * Get fields for module
         * @param string $module  Module
         * @return array
         * @throws FieldError
         */
        protected static function _fields($module) {
            if (!isset(self::$_cache[$module])) {
                $fields = PipeLine::invoke('settings'.ucfirst($module), array());
                self::$_cache[$module] = Models::fields($fields);
            }
            return self::$_cache[$module];
        }
    }