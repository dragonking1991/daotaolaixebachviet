<?php if(!defined('SOURCES')) die('Error'); ?>
<section class="content-header text-sm"><div class="container-fluid"><ol class="breadcrumb float-sm-left"><li class="breadcrumb-item"><a href="index.php?com=daotao&act=sathach">Kết quả sát hạch</a></li><li class="breadcrumb-item active">Dòng chờ bổ sung</li></ol></div></section>
<section class="content"><div class="card card-primary card-outline text-sm">
    <div class="card-header"><h3 class="card-title"><strong>Dòng import chờ bổ sung</strong> <?php if(!$sh_pending_detail): ?><span class="dt-count"><?=$sh_pending_count?></span><?php endif; ?></h3><div class="card-tools"><a class="btn btn-sm btn-outline-secondary" href="index.php?com=daotao&act=sathachPending">Tất cả dòng chờ</a></div></div>
    <div class="card-body">
    <?php if($sh_pending_detail): ?>
        <p><strong><?=htmlspecialchars($sh_pending_detail['filename'])?></strong> / Dòng <?=(int)$sh_pending_detail['so_dong']?></p>
        <?php if($sh_pending_detail['ly_do']!==''): ?><div class="alert alert-warning"><?=htmlspecialchars($sh_pending_detail['ly_do'])?></div><?php endif; ?>
        <form method="post" action="index.php?com=daotao&act=saveSathachPending">
            <input type="hidden" name="id" value="<?=(int)$sh_pending_detail['id']?>"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>">
            <div class="form-row">
            <?php foreach(array('hoten'=>'Họ và tên','cccd'=>'CMND / CCCD','nam_sinh'=>'Ngày sinh','hang'=>'Hạng','ngay_sh'=>'Ngày sát hạch','lt'=>'Kết quả LT','mp'=>'Kết quả MP','hinh'=>'Kết quả TH / Hình','duong'=>'Kết quả Đường','ket_qua'=>'Kết quả sát hạch','ma_khoa'=>'Mã khóa trong file','giaovien'=>'Giáo viên trong file','so_gplx'=>'Số GPLX','ghi_chu'=>'Ghi chú','noi_dung_sh'=>'Nội dung thi') as $field=>$label): ?>
                <div class="col-md-6 form-group"><label><?=htmlspecialchars($label)?></label><input name="data[<?=$field?>]" type="<?=$field==='ngay_sh'?'date':'text'?>" class="form-control form-control-sm" value="<?=htmlspecialchars((string)($sh_pending_data[$field]??''),ENT_QUOTES,'UTF-8')?>" <?=in_array($field,array('hoten','cccd','hang','ngay_sh','ket_qua'),true)?'required':''?> <?=($sh_pending_detail['trang_thai']!=='pending'||!dt_can('sathach','import'))?'disabled':''?>></div>
            <?php endforeach; ?>
            </div>
            <?php if($sh_pending_detail['trang_thai']==='pending'&&dt_can('sathach','import')): ?><button class="btn btn-sm bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu và nhập dòng</button><?php endif; ?>
        </form>
        <details class="mt-3"><summary>Dữ liệu gốc</summary><pre class="mt-2" style="white-space:pre-wrap;overflow-wrap:anywhere;"><?=htmlspecialchars(json_encode(json_decode($sh_pending_detail['raw_json'],true),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),ENT_QUOTES,'UTF-8')?></pre></details>
    <?php else: ?>
        <div class="table-responsive"><table class="table dt-list table-hover"><thead><tr><th>File / dòng</th><th>Học viên / CMND</th><th>Hạng / ngày thi</th><th>Cần bổ sung</th><th></th></tr></thead><tbody>
        <?php foreach($sh_pending_rows as $entry): $data=json_decode($entry['data_json'],true)?:array(); ?>
            <tr><td><?=htmlspecialchars($entry['filename'])?><div class="dt-sub">Dòng <?=(int)$entry['so_dong']?></div></td><td><?=htmlspecialchars((string)($data['hoten']??''))?><div class="dt-sub"><?=htmlspecialchars((string)($data['cccd']??''))?></div></td><td><?=htmlspecialchars((string)($data['hang']??''))?><div class="dt-sub"><?=htmlspecialchars(dt_sathach_display_date($data['ngay_sh']??''))?></div></td><td><?=htmlspecialchars($entry['ly_do'])?></td><td><a class="btn btn-xs bg-gradient-info" href="index.php?com=daotao&act=sathachPending&id=<?=(int)$entry['id']?>"><i class="fas fa-edit mr-1"></i>Bổ sung</a></td></tr>
        <?php endforeach; ?>
        <?php if(!$sh_pending_rows): ?><tr><td colspan="5" class="text-center text-muted">Không có dòng chờ bổ sung</td></tr><?php endif; ?>
        </tbody></table></div><?=$sh_pending_paging?>
    <?php endif; ?>
    </div>
</div></section>