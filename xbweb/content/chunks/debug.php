<div class="block navbar">
    <?php if (!empty($trace))  echo '<a href="#trace">Trace</a>'; ?>
    <?php if (!empty($timing)) echo '<a href="#timing">Timing</a>'; ?>
    <a href="#vars">Variables</a>
    <a href="#debug">Debug</a>
    <a href="#request">Request</a>
    <a href="#sql">SQL</a>
</div>
<?php if (!empty($trace)): ?>
    <div class="block add" id="trace">
        <pre><?php var_dump($trace)?></pre>
    </div>
<?php endif; ?>
<?php if (!empty($timing)): ?>
    <div class="block add" id="timing">
        <pre><?=var_export($timing, true)?></pre>
    </div>
<?php endif; ?>
<div class="block add" id="vars">
    <pre><?php var_dump(get_defined_vars())?></pre>
</div>
<div class="block add" id="debug">
    <pre><?=var_export(\xbweb\Debug::get(), true)?></pre>
</div>
<div class="block add" id="request">
    <pre><?=var_export(\xbweb\Request::get(), true)?></pre>
</div>
<div class="block add" id="sql">
    <pre><?=var_export(\xbweb\DB::getLog(), true)?></pre>
</div>