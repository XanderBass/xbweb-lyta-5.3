<?php
    namespace xbweb;
?>
<form method="post" action="/<?=Request::get('path')?>">
    <h2><?=(empty($title) ? 'Success' : $title)?></h2>
    <p class="modal-text">
        <?=(empty($content) ? 'Success' : $content)?>
    </p>
    <div class="buttons">
        <button class="close">Cancel</button>
    </div>
</form>