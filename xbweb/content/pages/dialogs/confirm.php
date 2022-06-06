<?php
    namespace xbweb;
?>
<form method="post" action="/<?=Request::get('path')?>">
    <input type="hidden" name="id" value="<?=Request::get('id')?>">
    <h2><?=(empty($title) ? 'Confirm?' : $title)?></h2>
    <p class="modal-text">
        <?=(empty($content) ? 'Confirm?' : $content)?>
    </p>
    <div class="buttons">
        <button class="ok" type="submit" name="action" value="<?=Request::get('action')?>">OK</button>
        <button class="close"><?=Language::translate('cancel')?></button>
    </div>
</form>