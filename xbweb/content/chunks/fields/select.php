<?php
    if (empty($name))        $name        = '';
    if (empty($value))       $value       = '';
    if (empty($title))       $title       = 'Link';
    if (empty($placeholder)) $placeholder = $title;
    if (empty($description)) $description = '';
    if (empty($flags))       $flags = array();
    if (empty($data['items'])) $data['items'] = array();
    if (empty($data['title'])) $data['title'] = 'name';
    $rc = in_array('required', $flags) ? ' required' : '';
    $ra = in_array('required', $flags) ? ' required="required"' : '';
?>
<label class="fc-select<?=$rc?>">
    <span><?=$title?></span>
    <?php if (!empty($description)) echo '<span class="description">'.$description.'</span>'; ?>
    <select<?=$ra?> name="<?=$name?>">
        <option value=""><?=$title?></option>
        <?php foreach ($data['items'] as $id => $item) : ?>
            <option value="<?=$id?>"<?php if ($id == $value) echo ' selected="selected"'; ?>><?=$item[$data['title']]?></option>
        <?php endforeach; ?>
    </select>
</label>