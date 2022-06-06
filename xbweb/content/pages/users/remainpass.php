<?php
    namespace xbweb;
?>
<div id="xbweb-login" class="xbweb-ui dialog">
    <h1>
        <a id="xbweb-logo" href="/">XBWeb CMF</a>
    </h1>
    <div class="messages">
    <?php
        if (!empty($errors))   foreach ($errors as $item)   echo '<div class="error">'.$item."</div>\r\n";
        if (!empty($warnings)) foreach ($warnings as $item) echo '<div class="warning">'.$item."</div>\r\n";
        if (!empty($notices))  foreach ($notices as $item)  echo '<div class="notice">'.$item."</div>\r\n";
    ?>
    </div>
    <form action="<?=(Request::get('context') == Request::CTX_ADMIN) ? '/admin' : ''?>/users/remainpass" method="post">
        <label>
            <span>E-mail</span>
            <input type="email" name="email" placeholder="E-mail">
        </label>
        <div class="buttons">
            <button type="submit" name="action" value="remainpass">Continue</button>
        </div>
    </form>
    <div class="links">
        <a href="/users/register">Register</a>
        <a class="button" href="/users/login">Log in</a>
    </div>
</div>