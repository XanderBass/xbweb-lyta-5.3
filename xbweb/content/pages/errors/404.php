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

    $page = empty($data['id']) ? Request::get('page') : $data['id'];
    if (Config::get('debug', false)) {
        ?>
        <h1 class="logo">Not found</h1>
        <div class="block">
            <div class="code"><?=http_response_code()?></div>
            <div class="message">
                <?=$message?><br/>
                <strong><?=is_array($page) ? '<pre>'.var_export($page).'</pre>' : $page?></strong>
            </div>
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
        <h1 class="grayed big"><?=http_response_code()?></h1>
        <strong class="big"><?=$message?></strong>
        <br><br>
        <strong><?=$page?></strong>
        <?php
    }