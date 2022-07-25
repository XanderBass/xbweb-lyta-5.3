<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Basic object class
     * @category     Basic classes
     * @link         https://xbweb.ru/doc/dist/classes/basicobject
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Class BasicObject
     */
    abstract class BasicObject
    {
        /**
         * Getter
         * @param string $name  Parameter name
         * @return mixed
         */
        function __get($name)
        {
            return property_exists($this, '_'.$name) ? $this->{'_'.$name} : null;
        }

        /**
         * Setter
         * @param string $name   Parameter name
         * @param mixed  $value  Parameter value
         * @return mixed
         */
        function __set($name, $value)
        {
            if (method_exists($this, "set_{$name}")) return $this->{"set_{$name}"}($value);
            return null;
        }
    }