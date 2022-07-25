<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Template functions library
     * @category     CMF libraries
     * @link         https://xbweb.ru/doc/dist/classes/lib/template
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\lib;

    use xbweb\ACL;
    use xbweb\Controller;
    use xbweb\Events;
    use xbweb\Language;
    use xbweb\Model;
    use xbweb\PipeLine;
    use xbweb\Request;

    /**
     * Class Template
     */
    class Template
    {
        const TPL_MENU_BLOCK = <<<HTML
<nav class="[+classes+]">
  <h2><span>[+title+]</span></h2>
  <ul>
    [+items+]
  </ul>
</nav>
HTML;
        const TPL_MENU_CATEGORY = <<<HTML
<li class="[+classes+]">
  <span><span>[+title+]</span>[+counter+]</span>
  <ul>
    [+items+]
  </ul>
</li>
HTML;
        const TPL_MENU_ITEM = <<<HTML
<li class="[+classes+]"><a href="[+url+]"><span>[+title+]</span>[+counter+]</a></li>
HTML;

        /**
         * Structured menu
         * @param array  $data   Menu data
         * @param array  $tpls   Template parts
         * @param string $place  Placement
         * @return string
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        public static function menu($data = null, $tpls = null, $place = null)
        {
            static $level = -1;

            if (empty($data))     $data  = array();
            if (empty($place))    $place = 'main';
            if (!is_array($tpls)) $tpls  = array();
            if (empty($tpls['block']))    $tpls['block']    = static::TPL_MENU_BLOCK;
            if (empty($tpls['category'])) $tpls['category'] = static::TPL_MENU_CATEGORY;
            if (empty($tpls['item']))     $tpls['item']     = static::TPL_MENU_ITEM;

            $ret = array();
            $level++;
            foreach ($data as $key => $item) {
                $type = empty($item['type']) ? 'item' : $item['type'];
                if (empty($tpls[$type])) continue;
                if (in_array($type, array('block', 'category'))) {
                    if (empty($item['items']) || !is_array($item['items'])) continue;
                    $item['items'] = self::menu($item['items'], $tpls);
                    $a = trim($item['items']);
                    if (empty($a)) continue;
                } else {
                    $a = empty($item['action']) ? '' : $item['action'];
                    if (!empty($a)) if (!ACL::granted($item['action'], null, false)) continue;
                }
                $item['key'] = $key;
                $tpl = $tpls[$type];
                if (empty($item['title'])) {
                    $item['title'] = Language::translate($key);
                }
                if (empty($item['action'])) {
                    $item['action'] = '';
                } else {
                    if ($item['action'] == '/') {
                        $item['title'] = Language::translate('dashboard');
                    } else {
                        $item['title'] = Language::action($item['action']);
                    }
                }
                $action = empty($item['id']) ? $item['action'] : $item['action'].'/'.$item['id'];
                if (empty($item['url']))    $item['url']    = Request::URL($action);
                if (empty($item['action'])) $item['action'] = $item['url'];
                $classes = array('menu-'.$type, 'key-'.$key);
                if ($type == 'block') $classes[] = 'place-'.$place;
                foreach ($item as $k => $v) {
                    if (is_array($v)) continue;
                    switch ($k) {
                        case 'modal' :
                            if (!empty($v)) $classes[] = 'modal';
                            break;
                        case 'newtab':
                            if (!empty($v)) $tpl = str_replace('<a ', '<a target="_blank" ', $tpl);
                            break;
                        case 'counter':
                            if ($v === false) continue;
                            $a = intval($v) > 0 ? ' active' : '';
                            if (isset($item['counter-url'])) {
                                $cnt = '<a class="counter'.$a.'" href="'.$item['counter-url'].'">'.$v.'</a>';
                            } else {
                                $cnt = '<span class="counter'.$a.'">'.$v.'</span>';
                            }
                            $tpl = str_replace('[+counter+]', $cnt, $tpl);
                            break;
                        default:
                            $tpl = str_replace("[+{$k}+]", $v, $tpl);
                    }
                }
                $ret[] = str_replace(array(
                    '[+classes+]', '[+level+]', '[+counter+]'
                ), array(
                    implode(' ', $classes), $level, ''
                ), $tpl);
            }
            $level--;
            return implode("\r\n", $ret);
        }

        /**
         * Data table
         * @param string $path   Controller path
         * @param array  $data   Data rows
         * @param int    $page   Page
         * @param int    $pages  Pages count
         * @param array  $order  Order fields
         * @return string
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        public static function table($path, $data = null, $page = 1, $pages = 1, $order = null)
        {
            return self::table_('index', $path, $data, $page, $pages, $order);
        }

        /**
         * Data table (trash)
         * @param string $path   Controller path
         * @param array  $data   Data rows
         * @param int    $page   Page
         * @param int    $pages  Pages count
         * @param array  $order  Order fields
         * @return string
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        public static function trash($path, $data = null, $page = 1, $pages = 1, $order = null)
        {
            return self::table_('trash', $path, $data, $page, $pages, $order);
        }

        /**
         * Data table
         * @param string $type   Table type
         * @param string $path   Controller path
         * @param array  $data   Data rows
         * @param int    $page   Page
         * @param int    $pages  Pages count
         * @param array  $order  Order fields
         * @return string
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        protected static function table_($type, $path, $data = null, $page = 1, $pages = 1, $order = null)
        {
            /** @var Controller $controller */
            $controller = Controller::create($path);
            $index      = Request::URL($controller->a($type));
            $cpath      = $controller->path;
            $model      = Model::create($controller->modelPath);
            list($module,) = \xbweb::MN('model', $controller->modelPath);
            $fields     = $model->tableFields();
            $cells      = count($fields) + 2;
            $caption    = Language::action($cpath.'/'.$type);
            $t_create   = Language::action('create');
            $t_trash    = Language::action('trash');
            $t_index    = Language::action('index');
            $t_clean    = Language::action('clean');

            $ret = array('<div class="data-table-heap">');
            $ret[] = '<h2>'.$caption.'</h2><span class="actions">';
            if ($type == 'trash') {
                if (ACL::granted($cpath.'/index')) {
                    $ret[] = '<a href="'.Request::URL($cpath.'/index').'" class="button add action">'.$t_index.'</a>';
                }
                if (ACL::granted($cpath.'/clean')) {
                    $ret[] = '<a href="'.Request::URL($cpath.'/clean').'" class="button delete action">'.$t_clean.'</a>';
                }
            } else {
                if (ACL::granted($cpath.'/create')) {
                    $ret[] = '<a href="'.Request::URL($cpath.'/create').'" class="button add action">'.$t_create.'</a>';
                }
                if (ACL::granted($cpath.'/trash')) {
                    $ret[] = '<a href="'.Request::URL($cpath.'/trash').'" class="button delete action">'.$t_trash.'</a>';
                }
            }
            $ret[] = '</span></div>';

            $actions = array('edit');
            if ($type == 'trash') {
                $actions[] = 'restore';
                $actions[] = 'remove';
            } else {
                if ($model->hasField('rank')) {
                    $actions[] = 'move_up';
                    $actions[] = 'move_down';
                }
                $actions[] = 'delete';
            }
            $allowed = array();
            foreach ($actions as $action) {
                if (!ACL::granted($cpath.'/'.$action)) continue;
                $allowed[$action] = Request::URL($cpath.'/'.$action);
            }

            $ret[] = '<table class="data-table"><thead><tr>';
            $f_id  = <<<html
<th class="checker id sortable">
    <input type="hidden" name="table_all_checked" value="0">
    <span>ID</span>
</th>
html;
            $ret[] = $f_id;
            foreach ($fields as $key => $field) {
                $cl = array();
                if (in_array('sortable', $field['flags'])) {
                    $cl[] = 'sortable';
                    if (!empty($order[$key])) {
                        $cl[] = 'sort_'.$order[$key];
                    }
                }
                $fk = 'field-'.((empty($module) || ($module == 'system')) ? '' : $module.'-').$key;
                $ret[] = '<th class="'.implode(' ', $cl).'">'.Language::translate($fk).'</th>';
            }
            $ret[] = '<th class="actions">';
            if ($model->hasField('rank') && ACL::granted($cpath.'/reset_rank')) {
                $c = 'icon action modal';
                $ret[] = '<a href="'.Request::URL($cpath.'/reset_rank').'" class="'.$c.'"></a>';
            }
            $ret[] = '</th>';
            $ret[] = '</tr></thead>';

            $ret[] = '<tbody>';
            if (empty($data)) {
                $ret[] = '<tr><td class="no-rows" colspan="'.$cells.'">'.Language::translate('no_rows').'</td></tr>';
            } else {
                foreach ($data as $row_id => $row) {
                    $f_id  = <<<html
<td class="checker">
    <input type="hidden" name="table['{$row_id}'][checked]" value="0"><span>{$row_id}</span>
</td>
html;
                    $ret[] = '<tr>';
                    $ret[] = $f_id;
                    foreach ($fields as $key => $field) {
                        $value = isset($row[$key]) ? $row[$key] : '';
                        if (!empty($field['table_value']) && !empty($value)) {
                            $_ = explode(':', $field['table_value']);
                            $m = array_shift($_);
                            switch ($m) {
                                case 'items':
                                    if (empty($_[0]) || empty($field['data']['items'][$value])) break;
                                    $f = $_[0];
                                    if (!isset($field['data']['items'][$value][$f])) break;
                                    $value = $field['data']['items'][$value][$f];
                                    break;
                                case 'html':
                                    $html = implode(':', $_);
                                    if (!isset($field['data']['items'][$value])) break;
                                    $value = \xbweb::placeholders($html, $field['data']['items'][$value]);
                                    break;
                            }
                        }

                        $ret[] = '<td>'.$value.'</td>';
                    }
                    $ret[] = '<td class="actions">';
                    foreach ($allowed as $action => $action_full) {
                        if (!Events::invoke($model->pipeName('rowAction'), $action, $row)) continue;
                        $c = 'icon action'.($action == 'edit' ? '' : ' modal');
                        $ret[] = '<a href="'.$action_full.'/'.$row_id.'" class="'.$c.'"></a>';
                    }
                    $ret[] = '</td>';
                    $ret[] = '</tr>';
                }
            }
            $ret[] = '</tbody>';

            if ($pages > 1) {
                $ret[] = '<tfoot><tr><td class="pager" colspan="'.($cells).'">';
                if ($page > 1) {
                    $ret[] = '<a class="first action" href="'.$index.'/1"></a>';
                    $ret[] = '<a class="prev action" href="'.$index.'/'.($page - 1).'"></a>';
                } else {
                    $ret[] = '<span class="first"></span>';
                    $ret[] = '<span class="prev"></span>';
                }
                if ($pages > 7) {
                    if ($page > 1) {
                        $ret[] = '<span>...</span>';
                    } else {
                        $ret[] = '<span>1</span>';
                    }
                    for ($c = 1; $c < $page; $c++) continue;
                    $ret[] = '<span>'.$page.'</span>';
                    for ($c = $page; $c < $pages; $c++) continue;
                    if ($page < $pages) {
                        $ret[] = '<span>...</span>';
                    } else {
                        $ret[] = '<span>'.$pages.'</span>';
                    }
                } else {
                    for ($c = 1; $c <= $pages ; $c++) {
                        if ($c == $page) {
                            $ret[] = '<span>'.$c.'</span>';
                        } else {
                            $ret[] = '<a class="action" href="'.$index.'/'.$c.'">'.$c.'</a>';
                        }
                    }
                }
                if ($page < $pages) {
                    $ret[] = '<a class="next action" href="'.$index.'/'.($page + 1).'"></a>';
                    $ret[] = '<a class="last action" href="'.$index.'/'.$pages.'"></a>';
                } else {
                    $ret[] = '<span class="next"></span>';
                    $ret[] = '<span class="last"></span>';
                }
                $ret[] = '</td></tr></tfoot>';
            }

            $ret[] = '</table>';
            return implode("\r\n", $ret);
        }

        /**
         * Form
         * @param string $action  Action
         * @param array  $form    Form fields
         * @param array  $values  Values
         * @param array  $errors  Errors
         * @return string
         */
        public static function form($action, $form, $values = null, $errors = null)
        {
            $caption = Language::action($action);
            $url     = Request::URL($action);
            $action  = explode('/', $action);
            $module  = array_shift($action);
            $cats    = array();
            $tabs    = array();
            $f       = true;
            foreach ($form as $fid => $field) {
                $fc    = explode('/', $field['input']);
                $mn    = array_shift($fc);
                $fp    = $mn.'/fields/'.implode('/', $fc);
                $key   = 'field-'.(empty($module) ? '' : $module.'-').$fid;
                $cat   = empty($field['category']) ? 'main' : $field['category'];
                $field = Language::field($key, $field);
                $field['value'] = isset($values[$fid]) ? $values[$fid] : null;
                $_fl          = array();
                $fn           = Content::chunk($fp, true, $_fl);
                $tabs[$cat][] = Content::render($fn, $field, $_fl);
            }

            foreach ($tabs as $cat => $fields) {
                if (empty($fields)) continue;
                $a   = $f ? 'active' : '';
                $f   = false;
                $tab = implode("\r\n", $fields);;
                $c   = count($tabs) > 1 ? 'tab '.$a : 'single-tab';
                $cats[] = '<a href="#form-category-'.$cat.'" class="'.$a.'">'.Language::translate('category-'.$cat).'</a>';
                $tabs[] = <<<html
<section id="form-category-{$cat}" class="{$c}">
    {$tab}
</section>
html;
            }
            if (count($cats) > 1) {
                $cats = implode("\r\n", $cats);
                $cats = <<<html
<nav class="tabs">
    {$cats}
</nav>
html;
            } else {
                $cats = '';
            }
            $tabs = implode("\r\n", $tabs);
            $buttons = array(
                'edit'  => Language::translate('save'),
                'save'  => Language::translate('apply'),
                'reset' => Language::translate('reset'),
            );
            $errs = array();
            if (!empty($errors)) foreach ($errors as $k => $v) {
                if (is_int($k)) {
                    $errs[] = '<li class="error">'.$v.'</li>';
                } else {
                    $f = Language::translate('field-'.(empty($module) ? '' : $module.'-').$k);
                    $e = Language::translate('error-'.$v);
                    $errs[] = '<li class="error">'.$f.': '.$e.'</li>';
                }
            }
            if (!empty($errs)) {
                $errs = implode("\r\n", $errs);
                $errs = <<<html
<ul class="messages">
{$errs}
</ul> 
html;
            } else {
                $errs = '';
            }
            $_b = array('<button type="submit" name="method" value="edit" class="ok">'.$buttons['edit'].'</button>');
            if (array_pop($action) != 'settings') $_b[] = '<button type="submit" name="method" value="save" class="ok">'.$buttons['save'].'</button>';
            $_b = implode("\r\n", $_b);
            return <<<html
<form action="{$url}" method="post" enctype="multipart/form-data">
    <h2>{$caption}</h2>
    {$errs}
    {$cats}
    {$tabs}
    <div class="buttons">
        {$_b}
        <button type="reset">{$buttons['reset']}</button>
    </div>
</form> 
html;
        }

        /**
         * JS tags
         * @param array $jslist  List of preloaded JS
         * @return string
         */
        public static function js($jslist = array())
        {
            $js = PipeLine::invoke('js', $jslist);
            $ret = array();
            foreach ($js as $jslink) $ret[] = '<script type="text/javascript" src="'.$jslink.'"></script>';
            return implode("\r\n", $ret);
        }

        /**
         * CSS tags
         * @param array $csslist  List of preloaded CSS
         * @return string
         */
        public static function css($csslist = array())
        {
            $css = PipeLine::invoke('css', $csslist);
            $ret = array();
            foreach ($css as $csslink) $ret[] = '<link rel="stylesheet" href="'.$csslink.'">';
            return implode("\r\n", $ret);
        }

        /**
         * jQuery load script
         * @param bool $onlyjs  Return only JS code
         * @return string
         */
        public static function jqueryLoad($onlyjs = false)
        {
            $js = PipeLine::invoke('jqueryLoad', '');
            if (empty($js)) return '';
            $js = <<<JS
$(function(){
    {$js}
});
JS;
            return $onlyjs ? $js : <<<HTML
<script type="text/javascript">
{$js}
</script> 
HTML;
        }
    }