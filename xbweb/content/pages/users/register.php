<?php
    namespace xbweb;

    $req_l = Config::get('users/login_required', true);
    $req_e = Config::get('users/email_required', true);
    $req_p = Config::get('users/phone_required', false);
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
    <form action="<?=(Request::get('context') == Request::CTX_ADMIN) ? '/admin' : ''?>/users/register" method="post">
        <?php if ($req_l) : ?>
        <label class="required">
            <span>Login</span>
            <input type="text" name="login" placeholder="Login" required="required">
        </label>
        <?php endif; ?>
        <?php if ($req_e) : ?>
        <label class="required">
            <span>E-mail</span>
            <input type="email" name="email" placeholder="E-mail" required="required">
        </label>
        <?php endif; ?>
        <?php if ($req_p) : ?>
        <label class="required">
            <span>Phone</span>
            <input type="tel" name="phone" placeholder="Phone" required="required">
        </label>
        <?php endif; ?>
        <label class="required">
            <span>Password</span>
            <input type="password" name="password" placeholder="Password" required="required">
        </label>
        <label class="required">
            <span>Repeat</span>
            <input type="password" name="re-password" placeholder="Repeat password" required="required">
        </label>
        <div class="buttons">
            <button type="submit" name="action" value="register">Register</button>
        </div>
    </form>
    <div class="links">
        <a href="/users/remainpass">Forgot password?</a>
        <a class="button" href="/users/login">Login</a>
    </div>
</div>