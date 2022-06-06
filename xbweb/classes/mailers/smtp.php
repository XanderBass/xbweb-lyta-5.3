<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  SMTP mailer
     * @category     Mailers
     * @link         https://xbweb.ru/doc/dist/classes/fields/smtp
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\mailers;

    use xbweb\Error;
    use xbweb\lib\Files;
    use xbweb\Mailer;

    /**
     * Class SMTP
     */
    class SMTP extends Mailer {
        /**
         * Socket operation
         * @param resource $socket  Socket
         * @param string   $resp    Expected response
         * @param string   $str     Error string
         * @param mixed    $d       Data
         * @return bool
         * @throws Error
         */
        protected function _socket($socket, $resp, $str = '', $d = null) {
            $sresp = null;
            $data  = empty($d) ? '' : ' ('.htmlspecialchars($d).')';
            while (@substr($sresp, 3, 1) != ' ') {
                $sresp = fgets($socket, 256);
                $this->_log('fgets', $sresp);
                if (!$sresp) {
                    throw new Error('SMTP error: ' . $str . '('.$data.') -> ' . $sresp);
                }
            }
            if (!(substr($sresp, 0, 3) == $resp)) {
                throw new Error('SMTP error: ' . $sresp . '('.$data.')');
            }
            return true;
        }

        /**
         * Send mail
         * @param string $template  Template
         * @param string $subject   Subject
         * @param array  $data      Variables
         * @param array  $files     Files
         * @return bool
         * @throws Error
         */
        public function send($template, $subject, $data = array(), $files = null) {
            $rec = array();
            foreach ($this->_to  as $email) if (!in_array($email, $rec)) $rec[] = $email;
            foreach ($this->_cc  as $email) if (!in_array($email, $rec)) $rec[] = $email;
            foreach ($this->_bcc as $email) if (!in_array($email, $rec)) $rec[] = $email;
            if (empty($rec)) throw new Error('No mail addresses');

            $socket = fsockopen(
                $this->_config['host'],
                $this->_config['port'],
                $en, $es,
                $this->_config['timeout']
            );
            $this->_log('fsockopen');
            if (!$socket) {
                throw new Error('SMTP error: '.$es);
            }
            $this->_socket($socket, '220', 'no socket', '220');

            $lines = array(
                array('EHLO '.$this->_config['host'], '250', 'no response'),
                array('AUTH LOGIN', '334', 'no auth'),
                array(base64_encode($this->_config['user']), '334', 'no login'),
                array(base64_encode($this->_config['pass']), '235', 'no pass'),
                array('MAIL FROM: '.$this->_from, '250', 'no from')
            );

            foreach ($rec as $mtoi) {
                $lines[] = array('RCPT TO: '.$mtoi, '250', 'invalid address');
            }

            $lines[] = array('DATA', '354', 'invalid data');

            $headers = $this->get_headers($subject);

            $data['config']  = $this->_config;
            $data['subject'] = $subject;

            $msg = $this->message(self::letter($template, $data));
            if (is_array($files)) if (count($files) > 0) foreach ($files as $fn) $msg.= "\r\n".$this->file($fn);

            $_ = array();
            foreach ($headers as $k => $v) $_[] = "$k: $v";
            $headers = implode("\r\n", $_);

            $body = "{$headers}\r\n\r\n{$msg}\r\n--{$this->_splitter}--\r\n.\r\n";
            if ($this->_logData) {
                $file = \xbweb\Paths\RUNTIME.'mail/'.\xbweb::now('Y-m-d-H-i-s').'-'.$this->_splitter.'.txt';
                if (Files::dir(dirname($file))) {
                    file_put_contents($file, $body);
                }
            }
            $lines[] = array($body, '250', 'mail not sent');
            $this->_log('data formation');
            foreach ($lines as $line) {
                fputs($socket, $line[0]."\r\n");
                $this->_log('fputs', $line[0]);
                if (!$this->_socket($socket, $line[1], $line[2], $line[0])) {
                    fclose($socket);
                    $this->_log('fclose');
                    return false;
                }
            }

            fputs($socket,"QUIT\r\n");
            $this->_log('fputs', 'QUIT');
            fclose($socket);
            $this->_log('fclose');
            return true;
        }

        /**
         * Configuration
         * @param mixed $config  Configuration data
         * @return array
         * @throws Error
         */
        public function config($config) {
            if (!is_array($config)) throw new Error('Invalid SMTP config');
            $ret = $config;
            foreach (array('host', 'user', 'pass') as $k) {
                if (empty($config[$k])) throw new Error('No SMTP '.$k);
            }
            $def = array('priority' => 3, 'port' => 25, 'timeout' => 30);
            foreach ($def as $k => $d) if (!isset($config[$k])) $ret[$k] = $d;
            return $ret;
        }
    }