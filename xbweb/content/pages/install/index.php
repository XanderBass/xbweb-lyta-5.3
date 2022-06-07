<?php
    namespace xbweb;
?>
<div id="xbweb-install" class="xbweb-ui dialog xbweb-simple-form">
    <h1><a id="xbweb-logo" href="/">XBWeb CMF: <?=Language::translate('installation')?></a></h1>
    <div class="messages">
        <?php
            if (!empty($errors))   foreach ($errors as $item)   echo '<div class="error">'.$item."</div>\r\n";
            if (!empty($warnings)) foreach ($warnings as $item) echo '<div class="warning">'.$item."</div>\r\n";
            if (!empty($notices))  foreach ($notices as $item)  echo '<div class="notice">'.$item."</div>\r\n";
        ?>
    </div>
    <form action="/install" method="post">
        <label>
            <span><?=Language::translate('db-host')?></span>
            <input type="text" name="db[host]" placeholder="127.0.0.1">
        </label>
        <label class="required">
            <span><?=Language::translate('db-user')?></span>
            <input type="text" name="db[user]" placeholder="" required="required">
        </label>
        <label class="required">
            <span><?=Language::translate('db-pass')?></span>
            <input type="text" name="db[pass]" placeholder="" required="required">
        </label>
        <label class="required">
            <span><?=Language::translate('db-name')?></span>
            <input type="text" name="db[name]" placeholder="" required="required">
        </label>
        <label>
            <span><?=Language::translate('db-prefix')?></span>
            <input type="text" name="db[prefix]" placeholder="">
        </label>
        <label>
            <span><?=Language::translate('db-port')?></span>
            <input type="text" name="db[port]" placeholder="3306">
        </label>
        <label class="top-lined">
            <span><?=Language::translate('admin-login')?></span>
            <input type="text" name="admin[login]" placeholder="admin">
        </label>
        <label class="required">
            <span><?=Language::translate('admin-password')?></span>
            <input type="password" name="admin[password]" placeholder="" required="required">
        </label>
        <label class="required bottom-lined">
            <span><?=Language::translate('admin-email')?></span>
            <input type="email" name="admin[email]" placeholder="" required="required">
        </label>
        <div class="buttons">
            <button type="submit" name="action" value="index"><?=Language::translate('install')?></button>
        </div>
    </form>
</div>