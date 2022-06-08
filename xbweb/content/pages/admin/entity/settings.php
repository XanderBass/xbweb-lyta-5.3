<?php
    namespace xbweb;

    use xbweb\lib\Template;

    if (empty($form))   $form   = array();
    if (empty($values)) $values = array();
    if (empty($errors)) $errors = array();
?>
<section class="form">
    <?=Template::form(Request::get('node').'/'.Request::get('action'), $form, $values, $errors)?>
</section>