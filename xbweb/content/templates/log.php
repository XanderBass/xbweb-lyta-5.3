<?php
    if (empty($title)) $title = 'Log';
    if (empty($lines)) $lines = array('Ok');
?><!DOCTYPE html>
<html><head>
    <meta charset="utf-8">
    <title>XBWeb CMF | <?=$title?></title>
    <link rel="icon" href="<?=xbweb::icon()?>" type="image/png">
    <style type="text/css">
        body {
            margin: 0; padding: 0; font: 16px monospace;
            background: #eff0ff; color: dimgray;
        }

        div.log {
            width: 960px; margin: 1em auto; padding: 1em;
            box-sizing: border-box;
        }
    </style>
</head><body>
    <div class="log">
        <?php foreach ($lines as $line) echo $line.'<br>'; ?>
    </div>
</body></html>