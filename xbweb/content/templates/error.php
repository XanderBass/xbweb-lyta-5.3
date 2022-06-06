<?php
    namespace xbweb;

    /**
     * @var string $content
     */

    $debug = Config::get('debug', false);
    if (empty($title)) $title = 'Error';
?><!DOCTYPE html>
<html><head>
    <meta charset="utf-8">
    <title>XBWeb CMF | <?=$title?></title>
    <link rel="icon" href="<?=\xbweb::icon()?>" type="image/png">
    <!--suppress CssUnusedSymbol -->
    <style type="text/css">
        body {
            margin: 0; padding: 0; font: 16px monospace;
            color: #9f4f40;
        }

        a { color: #9fcfff; }

        div.wrapper {
            width: 500px;
            margin: 2em auto; padding: 1em 2em 2em;
        }

        div.wrapper.center {
            text-align: center;
        }

        h1 { margin: 0; padding: 0; font-size: 2em; line-height: 2em; }
        h1.big { font-size: 7.5em; line-height: 1.2em; }

        .grayed { color: rgba(255, 255, 255, .5); }

        div.code {
            width: 3em;
            float: left;
            font-size: 2em; font-weight: bold; line-height: 2em;
            color: rgba(255, 255, 255, .7)
        }

        div.message { padding: 1em 0; }

        div.block[id] { display: none; }
        div.block[id]:target { display: block; }
        div.block.navbar { line-height: 4em; }
        div.block:after { display: block; clear: both; content: ''; }

        pre { margin: 0; }

        h1.logo:after {
            display: inline-block; width: 1.6em; height: 1.6em; float: right; margin-top: .2em;
            background: rgba(255, 255, 255, .7) url('<?=\xbweb::icon()?>') no-repeat 50% 50%;
            background-size: 1.2em 1.2em;
            content: '';
            border-radius: .4em;
        }
    </style>
</head><body>
    <div class="wrapper<?=' '.($debug ? 'debug' : 'center'); ?>">
        <?=$content?>
    </div>
</body></html>