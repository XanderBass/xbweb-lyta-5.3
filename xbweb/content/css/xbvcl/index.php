<?php
    if (empty($fontroot)) $fontroot = '';
?><!DOCTYPE html>
<html><head>
    <meta charset="utf-8">
    <title>XBVCL iconic font</title>
    <link rel="icon" href="<?=xbweb::icon()?>" type="image/png">
    <link rel="stylesheet" type="text/css" href="<?=$fontroot?>font.css">
    <style>
        ul {
            display: block; margin: 1em auto; padding: 0; list-style: none; width: 960px;
            border: 1px solid silver;
        }
        ul li { display: inline-block; width: 60px; margin: .5em 0; text-align: center; }
        ul li span.letter { display: block; font: 32px "XBVCL",monospace; line-height: 2em; color: #184; }
        ul li span.code { font: 18px monospace; color: gray; }
    </style>
</head><body>
<ul><?php for ($c = 0xf000; $c <= 0xf16f; $c++) { ?><li>
        <span class="letter">&#<?php echo $c; ?></span>
        <span class="code"><?php echo dechex($c); ?></span>
        </li><?php } ?><?php foreach (array('-','+','i','?','!','^','>','V','<','*','р','$','L','E','Y','W','B','@','c','"','M','H') as $c) { ?><li>
        <span class="letter"><?php echo $c; ?></span>
        <span class="code"><?php echo $c; ?></span>
        </li><?php } ?></ul>
</body></html>