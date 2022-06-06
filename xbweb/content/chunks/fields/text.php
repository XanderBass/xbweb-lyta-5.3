<?php
    if (empty($name))        $name        = '';
    if (empty($value))       $value       = '';
    if (empty($title))       $title       = 'Text';
    if (empty($placeholder)) $placeholder = $title;
    if (empty($description)) $description = '';
    if (empty($flags))       $flags       = array();
    $rc = in_array('required', $flags) ? ' required' : '';
    $ra = in_array('required', $flags) ? ' required="required"' : '';
?>
<label class="fc-text full<?=$rc?>">
    <span><?=$title?></span>
    <?php if (!empty($description)) echo '<span class="description">'.$description.'</span>'; ?>
    <textarea name="<?=$name?>" placeholder="<?=$placeholder?>"<?=$ra?>><?=$value?></textarea>
</label>