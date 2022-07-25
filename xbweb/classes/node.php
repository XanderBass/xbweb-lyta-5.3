<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  CMF node prototype
     * @category     Prototypes
     * @link         https://xbweb.org/doc/dist/classes/node
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * CMF node prototype class
     * @property-read string $path  Node path
     */
    abstract class Node extends BasicObject
    {
        const NODE_TYPE = '';

        protected $_path = null;

        /**
         * Constructor
         * @param string $path  Node path
         */
        protected function __construct($path)
        {
            $this->_path = $path;
        }

        /**
         * Property: mid
         * @return string
         */
        public function getMID()
        {
            return strtr(ucwords($this->_path, '/'), array('/' => ''));
        }

        /**
         * Pipe name
         * @param string $name  Pipe name
         * @return string
         */
        public function pipeName($name)
        {
            return PipeLine::name($name, $this->_path);
        }

        /**
         * Create node
         * @param string $path  Real node path
         * @return Node
         * @throws \Exception
         */
        public static function create($path)
        {
            $cn = \xbweb::uses($path, static::NODE_TYPE);
            return new $cn($path);
        }
    }