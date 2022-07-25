<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Errors
     * @category     Framework parts
     * @link         https://xbweb.org/doc/dist/errors
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    /**
     * Standart exception
     */
    class Error extends \Exception
    {
        const T_PHP_ERROR        = 0x00000001;
        const T_PHP_EXCEPTION    = 0x00000001;
        const T_XBWEB_ERROR      = 0x00010001;
        const T_XBWEB_ERROR_PAGE = 0x00010002;
        const T_XBWEB_DB_ERROR   = 0x00020001;
        const T_XBWEB_NO_TABLE   = 0x00020002;
        const T_XBWEB_DUPLICATE  = 0x00040003;
        const T_XBWEB_NODE_ERROR = 0x00080001;
        const T_XBWEB_DATA_ERROR = 0x00080002;

        protected $httpCode = 500;
        protected $data     = array();
        protected $type     = 0;

        /**
         * Constructor
         * @param mixed $msg   Message string or full error data array
         * @param int   $code  Integer code
         * @param int   $lvl   Trace level
         */
        public function __construct($msg, $code = 0, $lvl = null)
        {
            if (empty($this->type)) $this->type = static::T_XBWEB_ERROR;
            if (is_array($msg)) {
                if (isset($msg['type'])) $this->type = $msg['type'];
                if (isset($msg['code'])) $this->code = $msg['code'];
                if (isset($msg['file'])) $this->file = $msg['file'];
                if (isset($msg['line'])) $this->line = $msg['line'];
                $this->data = $msg;
                $msg = empty($msg['message']) ? 'Error occured' : $msg['message'];
            }
            if ($lvl !== null) {
                $lvl = intval($lvl);
                $trace = $this->getTrace();
                if (!empty($trace[$lvl])) {
                    $this->line = $trace['line'];
                    $this->file = $trace['file'];
                }
            }
            $this->data['message'] = $msg;
            parent::__construct($msg, $code);
        }

        /**
         * Get HTTP code
         * @return int
         */
        public function getHTTPCode()
        {
            return $this->httpCode;
        }

        /**
         * Get full data
         * @return array
         */
        public function getData()
        {
            return $this->data;
        }

        /**
         * Get error type
         * @return int
         */
        public function getType()
        {
            return $this->type;
        }

        /**
         * Get result array
         * @param \Exception $e  Any Exception
         * @return array
         */
        public static function getResponse(\Exception $e)
        {
            $ret = array(
                'status'        => 'error',
                'type'          => self::T_PHP_ERROR,
                'message'       => $e->getMessage(),
                'code'          => $e->getCode(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTrace(),
                'traceAsString' => $e->getTraceAsString(),
                'data'          => array(),
                'HTTPCode'      => 500
            );
            if ($e instanceof Error) {
                $ret['type']       = $e->getType();
                $ret['HTTPCode']   = $e->getHTTPCode();
                $ret['data']       = $e->getData();
            }
            return $ret;
        }
    }

    /**
     * Data error
     */
    class DataError extends Error
    {
        /**
         * Constructor
         * @param mixed $msg   Error data, full data array or string
         * @param mixed $data  Data
         * @param int   $code  Integer code
         */
        public function __construct($msg, $data = array(), $code = 0)
        {
            $this->type = static::T_XBWEB_DATA_ERROR;
            parent::__construct(array(
                'message' => $msg,
                'data'    => $data
            ), $code);
        }
    }

    /**
     * Node error
     */
    class NodeError extends Error
    {
        /**
         * Constructor
         * @param mixed  $msg   Error data, full data array or string
         * @param string $path  Node path
         * @param int    $code  Integer code
         */
        public function __construct($msg, $path, $code = 0)
        {
            $this->type = static::T_XBWEB_NODE_ERROR;
            parent::__construct(array(
                'message' => $msg,
                'path'    => $path
            ), $code);
        }
    }

    /**
     * Field error
     */
    class FieldError extends DataError
    {
        /**
         * Constructor
         * @param mixed  $msg    Error data, full data array or string
         * @param string $field  Field name
         */
        public function __construct($msg, $field)
        {
            parent::__construct($msg, array('name' => $field), 0);
        }
    }

    /**
     * Database error
     */
    class DBError extends Error
    {
        /**
         * Constructor
         * @param mixed $msg   Error data, full data array or string
         * @param int   $code  Integer code
         */
        public function __construct($msg, $code = 0)
        {
            $this->type = static::T_XBWEB_DB_ERROR;
            parent::__construct($msg, $code);
        }
    }

    /**
     * "No table" error
     */
    class NoTableError extends DBError
    {
        /**
         * Constructor
         * @param mixed $msg   Error data, full data array or string
         * @param int   $code  Integer code
         */
        public function __construct($msg, $code = 0)
        {
            $this->type = static::T_XBWEB_NO_TABLE;
            parent::__construct($msg, $code);
        }
    }

    /**
     * Duplicate entity error
     */
    class DuplicateError extends Error
    {
        /**
         * Constructor
         * @param mixed $msg   Error data, full data array or string
         * @param int   $code  Integer code
         */
        public function __construct($msg, $code = 0)
        {
            $this->type = static::T_XBWEB_DUPLICATE;
            parent::__construct($msg, $code);
        }
    }

    /**
     * HTTP error page
     */
    class ErrorPage extends Error
    {
        /**
         * Constructor
         * @param mixed $msg   Message string or full error data array
         * @param int   $code  Integer code
         */
        public function __construct($msg, $code = 0)
        {
            $this->type     = static::T_XBWEB_ERROR_PAGE;
            $this->httpCode = intval($code);
            parent::__construct($msg, $this->httpCode);
        }
    }

    /**
     * HTTP/403 Forbiddeb
     */
    class ErrorForbidden extends ErrorPage
    {
        /**
         * Constructor
         * @param mixed $msg  Message string or full error data array
         * @param null  $id   ID or URL
         */
        public function __construct($msg, $id = null)
        {
            $this->data['id'] = $id;
            parent::__construct($msg, 403);
        }
    }

    /**
     * HTTP/404 Not Found
     */
    class ErrorNotFound extends ErrorPage
    {
        /**
         * Constructor
         * @param mixed $msg  Message string or full error data array
         * @param null  $id   ID or URL
         */
        public function __construct($msg, $id = null)
        {
            $this->data['id'] = $id;
            parent::__construct($msg, 404);
        }
    }

    /**
     * HTTP/410 Gone
     */
    class ErrorDeleted extends ErrorPage
    {
        /**
         * Constructor
         * @param mixed $msg  Message string or full error data array
         * @param null  $id   ID or URL
         */
        public function __construct($msg, $id = null)
        {
            $this->data['id'] = $id;
            parent::__construct($msg, 410);
        }
    }