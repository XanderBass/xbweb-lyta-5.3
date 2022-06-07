<?php
    namespace xbweb {
        Config::set(array(
            'db' => array(
                'host' => '127.0.0.1',
                'user' => 'nebrosaerf',
                'pass' => 'UkfuytFpf8',
                'name' => 'nebrosaerf'
            ),
            'debug'     => false, // Debug flag
            '503'       => false, // If true, site is 503
            'debug_ips' => array(), // IPs for debug
            'mailer' => array(
                'class' => 'smtp',
                'host'  => 'smtphost',
                'user'  => 'smtpuser',
                'from'  => 'smtpfrom',
                'pass'  => 'smtppassword',
                'port'  => 25,
                'test'  => 'testemail'
            ),
            'users' => array(
                'activation' => true
            )
        ));
    }