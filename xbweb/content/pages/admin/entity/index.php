<?php /** @noinspection PhpUnhandledExceptionInspection */
    namespace xbweb;

    use xbweb\lib\Template;

    if (empty($result)) $result = array();
    if (empty($page))   $page   = 1;
    if (empty($pages))  $pages  = 1;
    if (empty($order))  $order  = array();
?>
<section>
    <?=Template::table(Request::get('node'), $result, $page, $pages, $order)?>
</section>