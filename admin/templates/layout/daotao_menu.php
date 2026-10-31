<?php
$dt_kiemtra = isset($kiemtra) && $kiemtra == true;
$dt_lq = isset($_SESSION['list_quyen']) && is_array($_SESSION['list_quyen']) ? $_SESSION['list_quyen'] : array();
$dt_can_sec = function($section) use ($dt_lq, $dt_kiemtra) {
    if(!$dt_kiemtra) return true;
    if($section === '_man') return in_array('daotao_man', $dt_lq, true);
    $allow = array('daotao_man', 'daotao_tracuu', 'daotao_import', 'daotao_upload', 'daotao_'.$section,
        'daotao_'.$section.'_view', 'daotao_'.$section.'_edit', 'daotao_'.$section.'_delete',
        'daotao_'.$section.'_import', 'daotao_'.$section.'_export');
    if($section === 'cabinkq') $allow[] = 'product_man_cabin';
    return (bool)array_intersect($allow, $dt_lq);
};
$dt_menu = array(
    'dashboard' => 'Tổng quan',
    'khoa' => 'Khóa đào tạo',
    'hocvien' => 'Học viên',
    'xe' => 'Xe tập lái',
    'giaovien' => 'Giáo viên',
    'lythuyet' => 'Lý thuyết',
    'dieukienDat' => 'Đủ điều kiện học DAT',
    'cabinkq' => 'Cabin (kết quả)',
    'hinh' => 'Thực hành hình',
    'dat' => 'DAT (trên đường)',
    'tonghop' => 'Tổng hợp',
    'kythi' => 'Ngày kiểm tra',
    'ketquadaotao' => 'Kết quả đào tạo',
    'htkh' => 'Số giấy HTKH',
    'sathach' => 'Kết quả sát hạch',
    'taichinh' => 'Học phí, tạm ứng, thanh toán',
    'audit' => 'Nhật ký thao tác',
    'backup' => 'Sao lưu & khôi phục',
    'congGvConfig' => 'Cấu hình cổng GV',
);
$dt_menu_sec = array(
    'dashboard' => '_man', 'khoa' => 'khoa', 'hocvien' => 'hocvien', 'xe' => 'xe',
    'giaovien' => 'giaovien', 'lythuyet' => 'lythuyet', 'dieukienDat' => 'lythuyet', 'cabinkq' => 'cabinkq', 'hinh' => 'hinh',
    'dat' => 'dat', 'tonghop' => 'tonghop', 'kythi' => 'kythi', 'ketquadaotao' => 'ketqua',
    'htkh' => 'htkh', 'sathach' => 'sathach', 'audit' => '_man', 'backup' => '_man',
    'congGvConfig' => '_man', 'taichinh' => 'taichinh',
);
$dt_groups = array(
    'records' => array('label'=>'Hồ sơ & nguồn lực', 'icon'=>'users', 'items'=>array('khoa','hocvien','giaovien','xe')),
    'study' => array('label'=>'Quá trình học', 'icon'=>'book', 'items'=>array('lythuyet','dieukienDat','cabinkq','hinh','dat')),
    'completion' => array('label'=>'Kiểm tra & hoàn tất', 'icon'=>'clipboard-check', 'items'=>array('tonghop','kythi','ketquadaotao','htkh','sathach')),
    'finance' => array('label'=>'Tài chính', 'icon'=>'hand-holding-usd', 'items'=>array('taichinh')),
    'management' => array('label'=>'Quản trị', 'icon'=>'cogs', 'items'=>array('audit','backup','congGvConfig')),
);
$dt_current = '';
if($com === 'daotao')
{
    $dt_current = $act === '' ? 'dashboard' : $act;
    $dt_template = explode('/', (string)($template ?? ''));
    $dt_template_alias = array('dat_eligibility'=>'dieukienDat', 'conggv_config'=>'congGvConfig', 'crud_form'=>'');
    if(($dt_template[0] ?? '') === 'daotao')
    {
        $dt_page = $dt_template[1] ?? '';
        $dt_current = $dt_template_alias[$dt_page] ?? $dt_page;
    }
    if(strpos($act, 'crud') === 0 || strpos($act, 'module') === 0)
    {
        $dt_entity = preg_replace('/[^a-z]/', '', (string)($_REQUEST['entity'] ?? ''));
        $dt_current = $dt_entity === 'mophong' ? 'lythuyet' : $dt_entity;
    }
}
foreach($dt_groups as $dt_group_key => &$dt_group)
{
    $dt_group['items'] = array_values(array_filter($dt_group['items'], function($item) use ($dt_can_sec, $dt_menu_sec) {
        return $dt_can_sec($dt_menu_sec[$item]);
    }));
    $dt_group['open'] = in_array($dt_current, $dt_group['items'], true);
    if(!$dt_group['items']) unset($dt_groups[$dt_group_key]);
}
unset($dt_group);
$dt_show_dashboard = $dt_can_sec('_man');
?>
<?php if($dt_groups || $dt_show_dashboard): ?>
<li class="nav-item has-treeview dt-sidebar-menu <?=$com==='daotao'?'menu-open':''?>">
    <a class="nav-link <?=$com==='daotao'?'active':''?>" href="#" title="Quản lý quá trình đào tạo">
        <i class="nav-icon text-sm fas fa-user-graduate"></i>
        <p>QL Đào Tạo<i class="right fas fa-angle-left"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <?php if($dt_show_dashboard): ?>
        <li class="nav-item">
            <a class="nav-link <?=$dt_current==='dashboard'?'active':''?>" href="index.php?com=daotao&amp;act=dashboard" <?=$dt_current==='dashboard'?'aria-current="page"':''?> title="Tổng quan">
                <i class="nav-icon text-sm fas fa-tachometer-alt"></i><p>Tổng quan</p>
            </a>
        </li>
        <?php endif; ?>
        <?php foreach($dt_groups as $dt_group_key => $dt_group): ?>
        <li class="nav-item has-treeview dt-training-group <?=$dt_group['open']?'menu-open':''?>" data-training-group="<?=$dt_group_key?>">
            <a class="nav-link dt-training-group-link <?=$dt_group['open']?'active':''?>" href="#" title="<?=htmlspecialchars($dt_group['label'])?>">
                <i class="nav-icon text-sm fas fa-<?=$dt_group['icon']?>"></i>
                <p><?=htmlspecialchars($dt_group['label'])?><i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview dt-training-items">
                <?php foreach($dt_group['items'] as $dtAct): $dtLabel=$dt_menu[$dtAct]; ?>
                <li class="nav-item">
                    <a class="nav-link <?=$dt_current===$dtAct?'active':''?>" href="index.php?com=daotao&amp;act=<?=$dtAct?>" <?=$dt_current===$dtAct?'aria-current="page"':''?> title="<?=htmlspecialchars($dtLabel)?>">
                        <i class="nav-icon text-sm far fa-circle" aria-hidden="true"></i><p><?=htmlspecialchars($dtLabel)?></p>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endforeach; ?>
    </ul>
</li>
<?php endif; ?>
