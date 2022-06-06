<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  PHPMailer
     * @category     Mailers
     * @link         https://xbweb.ru/doc/dist/classes/fields/sendmail
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\mailers;

    use xbweb\Error;
    use xbweb\lib\Files;
    use xbweb\Mailer;

    /**
     * Class Sendmail
     */
    class Sendmail extends Mailer {
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
            foreach ($this->_to  as $email) $rec[] = $email;
            foreach ($this->_cc  as $email) $rec[] = $email;
            foreach ($this->_bcc as $email) $rec[] = $email;
            if (empty($rec)) throw new Error('No mail addresses');

            $headers = $this->get_headers($subject);

            $data['config']  = $this->_config;
            $data['subject'] = $subject;

            $msg = $this->message(self::letter($template, $data));
            if (is_array($files)) if (count($files) > 0) foreach ($files as $fn) $msg.= "\r\n".$this->file($fn);
            $msg.= "\r\n{$this->_splitter}--";

            $_ = array();
            foreach ($headers as $k => $v) $_[] = "$k: $v";
            $headers = implode("\r\n", $_);

            $body = "{$headers}\r\n\r\n{$msg}\r\n.\r\n";
            if (Files::dir(\xbweb\Paths\RUNTIME.'mail')) {
                file_put_contents(\xbweb\Paths\RUNTIME.'mail/'.$this->_splitter.'.mail', $body);
            }
            return mail(implode(', ', $rec), $subject, $msg, $headers);
        }

        /**
         * Configuration
         * @param mixed $config Configuration data
         * @return array
         */
        public function config($config) {
            return $config;
        }
    }