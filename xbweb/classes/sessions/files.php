<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Sessions components - files
     * @category     Sessions components
     * @link         https://xbweb.org/doc/dist/classes/sessions/files
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Sessions;

    use xbweb\Error;
    use xbweb\Config;
    use xbweb\PipeLine;
    use xbweb\Request;
    use xbweb\Events;

    /**
     * Session component - files class
     * @property-read string $path  Session files path
     */
    class Files extends \xbweb\Session {
        const FOLDER = 'sessions';

        protected $_path = null;

        /**
         * Constructor
         * @throws Error
         */
        public function __construct() {
            $this->_path = Config::get('session/savepath', \xbweb\Paths\RUNTIME.(static::FOLDER).'/');
            if (!\xbweb\lib\Files::dir($this->_path, 0700, true)) throw new Error(array(
                'message' => 'Session save path error',
                'path'    => $this->_path
            ));
            if (!session_save_path($this->_path)) throw new Error(array(
                'message' => 'Cannot set session save path',
                'path'    => $this->_path
            ));
            parent::__construct();
        }

        /**
         * Get session file
         * @param string $SID  Session ID
         * @return string
         */
        protected function _file($SID) {
            return $this->_path.implode('/', str_split($SID, 2)).'.sess';
        }

        /**
         * Open session
         * @return bool
         */
        public function open() {
            if (!$this->_ready) return false;
            if (Request::isCLI()) return true;
            return Events::invoke('sessionOpen');
        }

        /**
         * Close the session
         * @return bool
         */
        public function close() {
            return Events::invoke('sessionClose');
        }

        /**
         * Read session data
         * @param string $SID  The session id to read data for.
         * @return string
         * @throws Error
         */
        public function read($SID) {
            if ($this->_data === null) $this->select($SID);
            $this->_isNew = empty($this->_data['sid']);
            if ($this->_isNew) $this->_data['sid'] = $SID;
            $fn = $this->_file($SID);
            if (!file_exists($fn)) return '';
            $data = file_get_contents($fn);
            if ($data === false) throw new Error('Cannot read session file');
            return PipeLine::invoke('sessionRead', $data);
        }

        /**
         * Write session data
         * @param string $SID   The session id.
         * @param string $data  Data string
         * @return bool
         * @throws Error
         */
        public function write($SID, $data) {
            $fn = $this->_file($SID);
            if (!\xbweb\lib\Files::dir(dirname($fn), 0700)) throw new Error(array(
                'message' => 'Session ID file error (write)',
                'path'    => $this->_path
            ));
            if (file_put_contents($fn, $data) !== false) return Events::invoke('sessionWrite');
            throw new Error('Cannot write session file');
        }

        /**
         * Destroy a session
         * @param string $SID The session ID being destroyed.
         * @return bool
         */
        public function destroy($SID) {
            if ($this->delete($SID)) return unlink($this->_file($SID));
            return false;
        }

        /**
         * Cleanup old sessions
         * @param int $maxlifetime  Max session lifetime
         * @return bool
         */
        public function gc($maxlifetime) {
            $sfl = glob($this->_path.'??/??/??/??/??/??/??/??/??/??/??/??/??/??/??/??.sess');
            $sid = array();
            $now = time();
            $rex = '~^(.*)([\w\/]{48})\.sess$~siu';
            foreach ($sfl as $sf) {
                $sft = filectime($sf);
                if (empty($sft)) continue;
                if (($sft + $maxlifetime) > $now) continue;
                $sid[] = strtr(preg_replace($rex, '\2', $sf), '/', '');
                unlink($sf);
                if (count($sid) > 10) {
                    $this->delete($sid);
                    $sid = array();
                }
            }
            if (count($sid)) $this->delete($sid);
            return true;
        }

        /**
         * Create SID
         * @return string
         * @throws Error
         */
        public function create_sid() {
            if (Request::isCLI())        return 'session_cli';
            if ($bot = Request::isBot()) return 'session_bot_'.strtolower($bot);
            $IP = Request::IP();
            if (empty($IP)) $IP = '127.0.0.1';
            for ($c = 0; $c < 10; $c++) {
                $id = \xbweb::id($IP);
                $fn = $this->_file($id);
                if (!file_exists($fn)) {
                    if (!\xbweb\lib\Files::dir(dirname($fn), 0700, true)) throw new Error(array(
                        'message' => 'Session ID file error (create)',
                        'path'    => $this->_path
                    ));
                    if (file_put_contents($fn, '') === false) throw new Error(array(
                        'message' => 'Cannot create session file (create_sid) '.$id,
                        'path'    => $fn
                    ));
                    $this->create($id);
                    return $id;
                }
            }
            throw new Error('Cannot generate session ID');
        }

        /**
         * Validate session ID
         * @param string $SID  Session ID
         * @return bool
         */
        public function validate_sid($SID) {
            if (!ctype_alnum(str_replace('_', '', $SID))) return false;
            $fn = $this->_file($SID);
            return file_exists($fn);
        }

        /**
         * @param string $SID Session ID
         * @return bool
         */
        public function update_timestamp($SID) {
            if ($this->touch($SID)) return touch($this->_file($SID));
            return false;
        }
    }