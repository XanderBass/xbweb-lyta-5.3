<?php
    if (empty($name))        $name        = '';
    if (empty($value))       $value       = '';
    if (empty($title))       $title       = 'String';
    if (empty($placeholder)) $placeholder = $title;
    if (empty($description)) $description = '';
    if (empty($precision))   $precision   = 0;
    if (empty($flags)) $flags = array();
    $rc = in_array('required', $flags) ? ' required' : '';
    $ra = in_array('required', $flags) ? ' required="required"' : '';
    $sv = 1 / pow(10, $precision);
?>
<label class="fc-string<?=$rc?>">
    <span><?=$title?></span>
    <?php if (!empty($description)) echo '<span class="description">'.$description.'</span>'; ?>
    <input type="number" step="<?=$sv?>" name="<?=$name?>" value="<?=$value?>" placeholder="<?=$placeholder?>"<?=$ra?>>
</label>