<?php
    namespace xbweb;

    /**
     * @var string $content
     */

    $rtime = Config::get('refresh-time', 3);
    $url   = empty($url) ? '/' : $url;
    if (empty($url))     $url     = '/';
    if (empty($title))   $title   = 'Message';
    if (empty($message)) $message = 'Empty message';
?><!DOCTYPE html>
<html><head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="<?=$rtime?>;URL=<?=$url?>">
    <title>XBWeb CMF | <?=$title?></title>
    <link rel="icon" href="<?=\xbweb::icon()?>" type="image/png">
    <style type="text/css">
        body {
            margin: 0; padding: 0; font: 16px monospace;
            background: #eff0ff; color: dimgray;
        }

        a { color: #9fcfff; }

        div.message {
            width: 400px; margin: 2em auto; padding: 1em 2em;
            background: white;
            box-sizing: border-box;
            box-shadow: 0 25px 25px -30px rgba(0, 0, 0, .5);
        }

        @media (max-width: 480px) {
            div.message { width: 300px; }
        }
    </style>
</head><body>
    <div class="message">
        <p><?=$message?></p>
        <p>Click <a href="<?=$url?>">here</a> or page redirects automatically</p>
    </div>
</body></html>