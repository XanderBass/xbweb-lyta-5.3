<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Mailer prototype
     * @category     Mailers
     * @link         https://xbweb.ru/doc/dist/classes/mailer
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb;

    use xbweb\lib\Files as LibFiles;

    use xbweb\lib\Content;

    /**
     * Class Mailer
     * @property-read array  $config    Configuration
     * @property-read string $from      "From" mail
     * @property-read string $reply     "Reply-to" mail
     * @property-read array  $to        "To" mails
     * @property-read array  $cc        "CC" mails
     * @property-read array  $bcc       "BCC" mails
     * @property-read string $splitter  Splitter
     * @property      bool   $logData   If TRUE, log data
     * @property-read array  $log       Log
     * @property-read bool   $writeLog  If TRUE, write log
     *
     * @method $this to($mail, $name = null)
     * @method $this cc($mail, $name = null)
     * @method $this bcc($mail, $name = null)
     */
    abstract class Mailer extends BasicObject {
        const MIME_VERSION = '1.0';

        protected $_config   = null;
        protected $_from     = null;
        protected $_reply    = null;
        protected $_to       = array();
        protected $_cc       = array();
        protected $_bcc      = array();
        protected $_splitter = '';
        protected $_logData  = false;
        protected $_log      = array();
        protected $_writeLog = false;

        /**
         * Constructor
         * @param array $config  Configuration
         */
        protected function __construct(array $config) {
            $def = array(
                'version' => static::MIME_VERSION,
                'type'    => 'html',
                'charset' => 'utf-8'
            );
            foreach ($def as $k => $d) if (!isset($config[$k])) $config[$k] = $d;
            $this->_config = $this->config($config);
            $dtn = new \DateTime();
            $this->_splitter = "boundary-".md5($dtn->format('Y-m-d H:i:s.u'));
            $this->_writeLog = Config::get('mailer/log', false);
        }

        /**
         * logData setter
         * @param mixed $v  Value
         * @return bool
         */
        protected function set_logData($v) {
            $this->_logData = !empty($v);
            return $this->_logData;
        }

        /**
         * Log something
         * @param string $name  Item name
         * @param mixed  $data  Data
         * @return array
         */
        protected function _log($name = null, $data = null) {
            static $time = null;
            static $file = null;
            if ($time === null) {
                $time = microtime(true);
                $file = Paths\RUNTIME.'mail/send-'.\xbweb::id().'.txt';
            }
            $nt = microtime(true);
            $li = array(
                'name' => empty($name) ? 'log_'.count($this->_log) : $name,
                'time' => round(($nt - $time) * 1000, 3),
                'data' => $data
            );
            $this->_log[] = $li;
            $time = $nt;
            if ($this->_writeLog && !empty($file)) {
                $line = $li['name'] . ': ' . $li['data'] . ' (' . $li['time'] . ")\r\n";
                file_put_contents($file, $line, FILE_APPEND);
            }
            return $this->_log;
        }

        /**
         * Call
         * @param string $method     Method name
         * @param array  $arguments  Arguments
         * @return $this
         * @throws Error
         */
        public function __call($method, $arguments) {
            switch ($method) {
                case 'to':
                case 'cc':
                case 'bcc':
                    if (empty($arguments[0])) throw new Error('Empty mail address');
                    $mail = $arguments[0];
                    $name = empty($arguments[1]) ? null : $arguments[1];
                    $addr = $this->address($mail, $name);
                    if (empty($addr)) throw new Error('Invalid mail address: '.$mail);
                    $this->{"_{$method}"}[] = $addr;
                    return $this;

            }
            return $this;
        }

        /**
         * Get headers
         * @param string $subject  Subject
         * @return array
         */
        protected function get_headers($subject) {
            $dto = new \DateTime();
            $headers = array(
                'Content-Type' => 'multipart/mixed; boundary="'.trim($this->_splitter).'"',
                'Content-Transfer-Encoding' => '7bit',
                'Subject'      => $this->encode($subject),
                'From'         => $this->_from,
                'MIME-Version' => $this->_config['version'],
                'To'           => implode(',', $this->_to),
                'Message-ID'   => '<'.$this->messageID($subject).'>',
                'Date'         => $dto->format('D, d M Y H:i:s')." UT",
                'Reply-To'     => empty($this->_reply) ? $this->_from : $this->_reply,
            );
            if (!empty($this->_cc))  $headers['CC']  = implode(',',$this->_cc);
            if (!empty($this->_bcc)) $headers['BCC'] = implode(',',$this->_bcc);
            $headers['X-Mailer']   = 'XBWeb CMF mailer';
            $headers['X-Priority'] = empty($this->_config['priority']) ? 3 : $this->_config['priority'];
            return $headers;
        }

        /**
         * Get message ID
         * @param string $subject  Subject
         * @return string
         */
        public function messageID($subject) {
            $d = new \DateTime();
            $s = $this->_from.' '.implode(',', $this->_to).' '.$subject;
            return $d->format('YmdHis').'.'.md5($s);
        }

        /**
         * Set "from" mail address
         * @param string $mail  Address
         * @param string $name  Sender name
         * @return $this
         * @throws Error
         */
        public function from($mail, $name = null) {
            $address = trim($mail);
            if (false === strpos($mail, '@')) throw new Error('Invalid from mail address');
            if (empty($name)) {
                $this->_from = $address;
            } else {
                $name = trim(preg_replace('/[\r\n]+/', '', $name));
                $this->_from = $this->encode($name)." <{$address}>";
            }
            return $this;
        }

        /**
         * Set "reply-to" mail address
         * @param string $mail  Address
         * @return $this
         * @throws Error
         */
        public function reply($mail) {
            $address = trim($mail);
            if (false === strpos($mail, '@')) throw new Error('Invalid reply-to mail address');
            $this->_reply = $address;
            return $this;
        }

        /**
         * Normalize address
         * @param string $mail  Address
         * @param string $name  Name
         * @return bool|string
         */
        public function address($mail, $name = null) {
            $address = trim($mail);
            if (false === strpos($address, '@')) return false;
            $address = "<{$address}>";
            if (empty($name)) return $address;
            $name = trim(preg_replace('/[\r\n]+/', '', $name));
            return $this->encode($name).' '.$address;
        }

        /**
         * Encode something
         * @param string $s  String
         * @return string
         */
        public function encode($s) {
            $s = base64_encode($s);
            return "=?{$this->_config['charset']}?B?{$s}?=";
        }

        /**
         * Get message part
         * @param string $html  HTML
         * @return string
         */
        public function message($html) {
            $msg = '--'.$this->_splitter."\r\n";
            $msg.= 'Content-Type: text/'.$this->_config['type'].'; charset='.$this->_config['charset']."\r\n";
            $msg.= "Content-Transfer-Encoding: base64\r\n\r\n";
            $msg.= chunk_split(base64_encode("\r\n\r\n\r\n\r\n\r\n\r\n".$html));
            return $msg;
        }

        /**
         * Get file part
         * @param string $fn  Filename
         * @return string
         */
        public function file($fn) {
            $mt  = LibFiles::getMIMEByExt($fn);
            $fid = basename($fn);
            $f   = fopen($fn, "rb");
            $msg = '--'.$this->_splitter."\r\n";
            $msg.= 'Content-Type: '.$mt.'; name="'.$fid.'"'."\r\n";
            $msg.= "Content-Transfer-Encoding: base64\r\n";
            $msg.= 'Content-Disposition: attachment; filename="'.$fid.'"'."\r\n\r\n";
            $msg.= chunk_split(base64_encode(fread($f, filesize($fn))));
            fclose($f);
            return $msg;
        }

        /**
         * Normalize config
         * @param mixed $config  Config
         * @return mixed
         */
        abstract public function config($config);

        /**
         * Send mail
         * @param string $template  Template
         * @param string $subject   Subject
         * @param array  $data      Data
         * @param array  $files     Files
         * @return mixed
         */
        abstract public function send($template, $subject, $data = array(), $files = null);

        /**
         * Compose letter
         * @param string $template  Template
         * @param array  $data      Data
         * @return bool|string
         */
        public static function letter($template, $data) {
            $template = explode('/', $template);
            $module   = array_shift($template);
            $template = implode('/', $template);
            $fnt = Content::file($template.'.'.Content::EXT_TPL, 'templates/mail', $module, false, $fl);
            if (empty($fnt)) return true;
            return Content::render($fnt, $data, $fl);
        }

        /**
         * Create instance of mailer
         * @param array $config  Config
         * @return Mailer
         * @throws Error
         */
        public static function create($config = null) {
            if ($config === null) $config = Config::get('mailer');
            if (empty($config)) throw new Error('No mailer configuration');
            $type = empty($config['class']) ? 'sendmail' : $config['class'];
            $cn = '\\xbweb\\mailers\\'.$type;
            return new $cn($config);
        }
    }