<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Model functions library
     * @category     CMF libraries
     * @link         https://xbweb.ru/doc/dist/classes/lib/models
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\lib;

    use xbweb\Field;
    use xbweb\User;

    /**
     * Class Models
     */
    class Models {
        /**
         * Get fields from REQUEST
         * @param array  $fields     Fields
         * @param string $operation  Operation
         * @return array
         * @throws \xbweb\Error
         */
        public static function request($fields, $operation) {
            $ug = User::current()->role;
            $errors = array();
            $values = array();
            foreach ($fields as $key => $field) {
                if (Field::allowed($field, $operation, $ug)) {
                    if (!isset($_POST[$key]) && ($operation == 'update')) continue;
                    $value = isset($_POST[$key]) ? $_POST[$key] : null;
                } else {
                    if (in_array('system', $field['attributes']) && !empty($field['default'])) {
                        $value = $field['default'];
                    } else {
                        continue;
                    }
                }
                $error = false;
                if (self::validate($field, $value, $error)) {
                    $values[$key] = $value;
                } else {
                    $errors[$key] = $error;
                }
            }
            return array('errors' => $errors, 'values' => $values);
        }

        /**
         * Validate field value
         * @param array $field  Field data
         * @param mixed $value  Field value
         * @param mixed $error  Error
         * @return bool
         */
        public static function validate($field, $value, &$error = false) {
            $value = Field::value($field, $value);
            if (empty($value)) {
                if (!in_array('required', $field['flags'])) return true;
                $error = 'empty';
            } else {
                $error = Field::valid($field, $value);
                if ($error === true) {
                    $error = false;
                    return true;
                }
            }
            return false;
        }

        /**
         * Get form fields
         * @param array  $fields     Model fields
         * @param string $operation  Operation
         * @param array  $row        Values
         * @return array
         * @throws \xbweb\Error
         */
        public static function form($fields, $operation, $row = null) {
            $ug  = User::current()->role;
            $ret = array();
            foreach ($fields as $key => $field) {
                if (!Field::allowed($field, $operation, $ug)) continue;
                $field['value']     = isset($row[$key]) ? $row[$key] : null;
                $field['operation'] = $operation;
                unset($field['model']);
                $ret[$key] = $field;
            }
            return $ret;
        }

        /**
         * Data row
         * @param array $fields  Model fields
         * @param array $row     Values row
         * @param bool  $unpack  Unpack values
         * @return array
         * @throws \xbweb\Error
         */
        public static function row($fields, $row, $unpack = true) {
            $ug   = User::current()->role;
            $data = array();
            foreach ($fields as $key => $field) {
                if (!Field::allowed($field, 'read', $ug)) continue;
                $value = isset($row[$key]) ? $row[$key] : null;
                if ($unpack) {
                    $data[$key] = Field::unpack($field, $value);
                } else {
                    $data[$key] = Field::value($field, $value);
                }
            }
            return $data;
        }

        /**
         * Get table fields
         * @param array $fields  Model fields
         * @return array
         * @throws \xbweb\Error
         */
        public static function tableFields($fields) {
            $ug   = User::current()->role;
            $data = array();
            foreach ($fields as $key => $field) {
                if (!Field::allowed($field, 'read', $ug)) continue;
                if (!in_array('table', $field['flags'])) continue;
                $data[$key] = $field;
            }
            return $data;
        }
    }