<?php
    /**
     * @var string $content
     */
?><div class="redirect-message">
    <p><?=(empty($result) ? 'Some messafe' : $result)?></p>
    <?php if (!empty($url)) echo '<p>Click <a href="'.$url.'">here</a> or page will redirect automatically</p>'; ?>
</div>