<?php
if(PHP_SAPI !== 'cli') exit('CLI only');
function menu_check($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
}
function training_menu_render($permissions, $action = 'dashboard', $pageTemplate = '', $request = array(), $restricted = true, $module = 'daotao')
{
    $com = $module;
    $act = $action;
    $template = $pageTemplate;
    $kiemtra = $restricted;
    $_SESSION['list_quyen'] = $permissions;
    $_REQUEST = $request;
    ob_start();
    include dirname(__DIR__).'/admin/templates/layout/daotao_menu.php';
    return ob_get_clean();
}
function menu_dom($html)
{
    $document = new DOMDocument();
    $document->loadHTML('<?xml encoding="UTF-8"><html><body><ul>'.$html.'</ul></body></html>');
    return new DOMXPath($document);
}
$expected = array('dashboard','khoa','hocvien','giaovien','xe','lythuyet','dieukienDat','cabinkq','hinh','dat',
    'tonghop','kythi','ketquadaotao','htkh','sathach','taichinh','audit','backup','congGvConfig');
$html = training_menu_render(array('daotao_man'));
$xpath = menu_dom($html);
menu_check($xpath->query('//li[@data-training-group]')->length === 5, 'Expected exactly five themed groups');
$links = array();
foreach($xpath->query('//a[contains(@href,"com=daotao")]') as $link)
{
    parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
    $links[] = $query['act'];
}
menu_check($links === $expected, 'All original links must appear once in the intended order');
menu_check($xpath->query('//li[@data-training-group and contains(@class,"menu-open")]')->length === 0,
    'Dashboard must not open unrelated groups');
foreach(array_slice($expected, 1) as $action)
{
    $currentMenu = menu_dom(training_menu_render(array('daotao_man'), $action));
    menu_check($currentMenu->query('//li[@data-training-group and contains(@class,"menu-open")]')->length === 1 &&
        $currentMenu->query('//a[@aria-current="page"]')->length === 1,
        'Every primary menu page must open exactly its group: '.$action);
}
$pages = array(
    array('hocvien','daotao/hocvien/items',array(),'records','hocvien'),
    array('uploadHocvien','daotao/hocvien/upload',array(),'records','hocvien'),
    array('khoaEdit','daotao/khoa/item_add',array(),'records','khoa'),
    array('uploadLythuyet','daotao/lythuyet/upload',array(),'study','lythuyet'),
    array('dieukienDat','daotao/dat_eligibility/items',array(),'study','dieukienDat'),
    array('crudEdit','daotao/crud_form',array('entity'=>'mophong'),'study','lythuyet'),
    array('crudEdit','daotao/crud_form',array('entity'=>'dat'),'study','dat'),
    array('crudEdit','daotao/crud_form',array('entity'=>'hocvien'),'records','hocvien'),
    array('modulePassSelected','',array('entity'=>'cabinkq'),'study','cabinkq'),
    array('kythiDetail','daotao/kythi/detail',array(),'completion','kythi'),
    array('sathachPending','daotao/sathach/pending',array(),'completion','sathach'),
    array('uploadHtkh','daotao/htkh/upload',array(),'completion','htkh'),
    array('taichinh','daotao/taichinh/items',array(),'finance','taichinh'),
    array('congGvConfig','daotao/conggv_config/items',array(),'management','congGvConfig'),
);
foreach($pages as $page)
{
    $xpath = menu_dom(training_menu_render(array('daotao_man'), $page[0], $page[1], $page[2]));
    menu_check($xpath->query('//li[@data-training-group="'.$page[3].'" and contains(@class,"menu-open")]')->length === 1,
        'Current group not opened for '.$page[0]);
    menu_check($xpath->query('//li[@data-training-group and contains(@class,"menu-open")]')->length === 1,
        'Only the current group should be open for '.$page[0]);
    $current = $xpath->query('//a[@aria-current="page"]');
    menu_check($current->length === 1 && strpos($current->item(0)->getAttribute('href'), 'act='.$page[4]) !== false,
        'Wrong highlighted child for '.$page[0]);
}
foreach(array('view','edit','delete','import','export','') as $permission)
{
    $xpath = menu_dom(training_menu_render(array('daotao_lythuyet'.($permission!==''?'_'.$permission:'')), 'dieukienDat'));
    menu_check($xpath->query('//li[@data-training-group="study"]')->length === 1 &&
        $xpath->query('//li[@data-training-group]')->length === 1 &&
        $xpath->query('//a[contains(@href,"com=daotao")]')->length === 2,
        'Theory-only permissions must show only theory and DAT entry, without empty groups');
}
$xpath = menu_dom(training_menu_render(array('product_man_cabin'), 'cabinkq'));
menu_check($xpath->query('//li[@data-training-group]')->length === 1 &&
    $xpath->query('//a[contains(@href,"com=daotao")]')->length === 1 &&
    $xpath->query('//a[contains(@href,"act=cabinkq")]')->length === 1,
    'Cabin-only permission exposed unrelated training links');
$xpath = menu_dom(training_menu_render(array('daotao_tracuu')));
menu_check($xpath->query('//li[@data-training-group]')->length === 4 &&
    $xpath->query('//a[contains(@href,"act=dashboard") or contains(@href,"act=audit") or contains(@href,"act=backup") or contains(@href,"act=congGvConfig")]')->length === 0,
    'Lookup permission exposed administration links');
menu_check(trim(training_menu_render(array())) === '', 'No permissions must not render an empty training menu');
menu_check(trim(training_menu_render(array('daotao_unknown_view'))) === '', 'Unrelated permission must not expose empty groups');
$xpath = menu_dom(training_menu_render(array(), 'dashboard', '', array(), false));
menu_check($xpath->query('//a[contains(@href,"com=daotao")]')->length === count($expected),
    'Unrestricted admin must retain all training routes');
$xpath = menu_dom(training_menu_render(array('daotao_man'), 'hocvien', '', array(), true, 'xangdau'));
menu_check($xpath->query('//li[contains(@class,"menu-open")]')->length === 0 &&
    $xpath->query('//a[@aria-current="page"]')->length === 0, 'Another module must not open training groups');
$outer = file_get_contents(dirname(__DIR__).'/admin/templates/layout/menu.php');
menu_check(strpos($outer, "include __DIR__.'/daotao_menu.php'") !== false, 'Training menu partial not wired into sidebar');
echo "PASS training menu: all 19 links, five groups, primary/subpage/CRUD highlighting, theory/cabin/lookup/admin permissions, no empty groups and other-module isolation\n";
