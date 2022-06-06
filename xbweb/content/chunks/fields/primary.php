<?php
    if (empty($name))  $name  = 'id';
    if (empty($value)) $value = '';
?>
<input type="hidden" name="<?=$name?>" value="<?=$value?>">