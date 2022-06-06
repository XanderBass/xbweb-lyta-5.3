<?php
    namespace xbweb;

    /**
     * @var string $message
     * @var string $file
     * @var int    $line
     * @var mixed  $data
     * @var array  $trace
     * @var mixed  $id
     */

    if (Config::get('debug', false)) {
        ?>
        <h1 class="logo">Internal error</h1>
        <div class="block">
            <div class="code"><?=http_response_code()?></div>
            <div class="message"><?=$message?></div>
        </div>
        <div class="block">
            <span class="file"><?=strtr($file, '\\', '/')?></span>
            <strong class="line">(<?=$line?>)</strong>
        </div>
        <?php
        echo View::chunk('/debug', array(
            'timing' => empty($timing) ? null : $timing,
            'trace'  => empty($trace)  ? null : $trace
        ), true);
    } else {
        ?>
        <strong style="font-size: 1.5em">XBWeb CMF</strong><br><br><br>
        <img src="<?=\xbweb::icon()?>">
        <h1 class="grayed big">503</h1>
        <strong class="big">Site temporary unavailable</strong>
        <?php
    }