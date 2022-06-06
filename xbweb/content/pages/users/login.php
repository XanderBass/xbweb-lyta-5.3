<?php
    namespace xbweb;
?><div id="xbweb-login" class="xbweb-ui dialog">
    <h1>
        <a id="xbweb-logo" href="/">XBWeb CMF</a>
    </h1>
    <div class="messages">
    <?php
        if (!empty($errors))   foreach ($errors as $key => $item)   echo '<div class="error">'.$key.': '.$item."</div>\r\n";
        if (!empty($warnings)) foreach ($warnings as $key => $item) echo '<div class="warning">'.$key.': '.$item."</div>\r\n";
        if (!empty($notices))  foreach ($notices as $key => $item)  echo '<div class="notice">'.$key.': '.$item."</div>\r\n";
    ?>
    </div>
    <form action="<?=(Request::get('context') == Request::CTX_ADMIN) ? '/admin' : ''?>/users/login" method="post">
        <label>
            <span>Login</span>
            <input type="text" name="login" placeholder="Login">
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" placeholder="Password">
        </label>
        <div class="buttons">
            <label class="checkbox">
                <input type="checkbox" name="safe"> <span>Safe (close others)</span>
            </label>
            <button type="submit" name="action" value="login">Log in</button>
        </div>
    </form>
    <div class="links">
        <a href="/users/remainpass">Forgot password?</a>
        <a class="button" href="/users/register">Register</a>
    </div>
</div>