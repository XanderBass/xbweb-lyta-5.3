<?php
    if (empty($name))        $name        = '';
    if (empty($value))       $value       = '';
    if (empty($title))       $title       = 'String';
    if (empty($placeholder)) $placeholder = $title;
    if (empty($description)) $description = '';
    if (empty($flags)) $flags = array();
    $rc = in_array('required', $flags) ? ' required' : '';
    $ra = in_array('required', $flags) ? ' required="required"' : '';
?>
<label class="fc-string<?=$rc?>">
    <span><?=$title?></span>
    <input type="email" name="<?=$name?>" value="<?=$value?>" placeholder="<?=$placeholder?>"<?=$ra?>>
</label>