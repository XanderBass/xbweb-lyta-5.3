<?php
    /** @noinspection PhpUnhandledExceptionInspection */
    namespace xbweb;

    /** @var mixed $content */

    use xbweb\lib\Template;

    $username = User::current()->data['login'];
    if (empty($username)) $username = 'Anonimous';
?><!DOCTYPE html>
<html><head>
    <meta charset="utf-8">
    <title>XBWeb CMF<?=(empty($title) ? '' : ' | '.$title)?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/favicon.png">
    <link rel="stylesheet" href="/xbweb/css/admin.css">
    <link rel="stylesheet" href="/xbweb/css/themes/<?=Config::get('view/themes/admin', 'default')?>.css">
    <?=Template::js(array(
        '/xbweb/js/jquery.js',
        '/xbweb/js/jquery.form.js',
        '/xbweb/js/ui.js',
        '/xbweb/js/scrollbar.js'
    ))?>
    <?=Template::jqueryLoad()?>
    <script type="text/javascript" src="/xbweb/js/admin.js"></script>
</head><body class="xbweb-ui">
<aside id="menu-main">
    <nav class="toggler">
        <a class="do-toggle" href="#"></a>
        <h2><a href="/">XBWeb (Lyta)</a></h2>
    </nav>
    <section class="content">
        <?=View::menu('adminleft')?>
    </section>
</aside>
<main>
    <header>
        <div class="widgets"></div>
        <nav class="user-menu">
            <a class="username" href="#"><?=$username?></a>
            <a class="avatar" href="#"></a>
            <ul>
                <li class="category-info">
                    <a class="avatar" href="#"></a>
                    <span class="info">
                        <span class="username"><?=$username?></span>
                        <?=View::menu('userprofile', array(
                            'item' => '<a class="button" href="[+url+]">[+title+][+counter+]</a>'
                        ))?>
                    </span>
                </li>
                <?=View::menu('user', array(
                    'block'    => '<ul>[+items+]</ul>',
                    'category' => '<li class="category"><ul>[+items+]</ul></li>',
                    'item'     => '<li class="menu-item-[+key+]"><a href="[+url+]">[+title+][+counter+]</a></li>'
                ))?>
            </ul>
        </nav>
    </header>
    <div id="content">
        <?=$content?>
    </div>
</main>
</body></html>