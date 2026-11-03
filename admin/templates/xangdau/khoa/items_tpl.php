<?php if(!defined('SOURCES'))die('Error'); $escape=function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$xdCanMerge=xd_can_import(); $xdMergeLog=$xdCanMerge?(array)$xd_merge_log:array(); $xdApplied=array();
foreach($xdMergeLog as $log)if($log['trang_thai']!=='undone')$xdApplied[$log['khoa_dich']][]=$log;
$xdUndoForm=function($log)use($escape){return '<form method="post" action="index.php?com=xangdau&act=undoMergeKhoa" class="d-inline" onsubmit="return confirm(\'Hoàn tác lần gộp này? Học viên sẽ được trả về khóa nguồn.\');"><input type="hidden" name="csrf" value="'.$escape($_SESSION['xd_course_csrf']).'"><input type="hidden" name="log_id" value="'.(int)$log['id'].'"><button class="btn btn-xs btn-outline-secondary"><i class="fas fa-undo mr-1"></i>Hoàn tác</button></form>';};
$xdCourseName=function($code)use($xd_course_names){return $xd_course_names[$code]??$code;}; ?>
<section class="content-header text-sm"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php?com=xangdau&act=hocvien">Học viên XD</a></li><li class="breadcrumb-item active">Quản lý khóa XD</li></ol></section>
<section class="content">
    <div class="card card-primary card-outline text-sm">
        <div class="card-header"><h3 class="card-title">Quản lý khóa XD <span class="badge badge-light"><?=count($xd_courses)?></span></h3>
            <?php if($xdCanMerge): ?><div class="card-tools"><button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#xd-merge-history" aria-expanded="false"><i class="fas fa-history mr-1"></i>Lịch sử gộp khóa<?php if($xdApplied): ?> <span class="badge badge-success"><?=array_sum(array_map('count',$xdApplied))?> đang áp dụng</span><?php endif; ?></button></div><?php endif; ?>
        </div>
        <?php if($xdCanMerge): ?>
        <div id="xd-merge-history" class="collapse border-bottom bg-light">
            <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Thời gian</th><th>Khóa nguồn</th><th>Gộp vào</th><th class="text-center">Số HV</th><th>Người thực hiện</th><th>Trạng thái</th><th></th></tr></thead><tbody>
            <?php foreach($xdMergeLog as $log): ?>
                <tr><td><?=date('d/m/Y H:i',(int)$log['ngaytao'])?></td><td><?=$escape($log['khoa_nguon'])?></td><td title="Mã: <?=$escape($log['khoa_dich'])?>"><?=$escape($xdCourseName($log['khoa_dich']))?></td><td class="text-center"><?=(int)$log['so_hv']?></td><td><?=$escape($log['user_tao'])?></td>
                <td><?php if($log['trang_thai']!=='undone'): ?><span class="badge badge-success">Đang áp dụng</span><?php else: ?><span class="badge badge-secondary">Đã hoàn tác</span> <span class="small text-muted"><?=date('d/m/Y H:i',(int)$log['ngay_hoantac'])?> · <?=(int)$log['so_hoantac']?> HV</span><?php endif; ?></td>
                <td class="text-right"><?=$log['trang_thai']!=='undone'?$xdUndoForm($log):''?></td></tr>
            <?php endforeach; ?>
            <?php if(!$xdMergeLog): ?><tr><td colspan="7" class="text-center text-muted">Chưa có lần gộp khóa nào.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
        <?php endif; ?>
        <div class="card-body">
            <form method="get" class="form-inline mb-3"><input type="hidden" name="com" value="xangdau"><input type="hidden" name="act" value="khoa"><input type="search" name="keyword" class="form-control form-control-sm mr-2" placeholder="Mã / Tên khóa XD" value="<?=$escape($_GET['keyword']??'')?>"><button class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Tìm</button></form>
            <div class="table-responsive"><table class="table table-sm table-bordered table-hover"><thead><tr><th>Khóa / nguồn</th><th>Tổng HV</th><th>Đã thanh toán</th><th>Có thể xóa (chưa TT)</th><th>GV</th><th>Tiền đã ghi</th><th></th></tr></thead><tbody>
            <?php foreach($xd_courses as $course): ?>
                <tr><td><?=$escape(xd_hocvien_course_label($course))?><?php foreach($xdApplied[$course['khoa']]??array() as $log): ?><div class="small text-muted mt-1"><i class="fas fa-compress-arrows-alt text-warning mr-1"></i>Đã gộp từ <strong><?=$escape($log['khoa_nguon'])?></strong> (<?=(int)$log['so_hv']?> HV, <?=date('d/m/Y H:i',(int)$log['ngaytao'])?>) <?=$xdUndoForm($log)?></div><?php endforeach; ?></td><td><?=(int)$course['so']?></td><td><?=(int)$course['paid']?></td><td><?=(int)$course['editable']?></td><td><?=(int)$course['teachers']?></td><td class="text-right"><?=number_format((float)$course['amount'],0,',','.')?></td><td><a class="btn btn-xs btn-outline-info" href="index.php?com=xangdau&act=khoa&khoa=<?=urlencode($course['khoa'])?>#xd-course-detail" title="Xem chi tiết khóa"><i class="fas fa-eye"></i></a><?php if(xd_can_import()): ?> <button type="button" class="btn btn-xs btn-outline-warning xd-merge-open" data-khoa="<?=$escape($course['khoa'])?>" data-so="<?=(int)$course['so']?>" data-label="<?=$escape(xd_hocvien_course_label($course))?>" title="Gộp khóa này vào khóa khác"><i class="fas fa-compress-arrows-alt"></i> Gộp</button><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if(!$xd_courses): ?><tr><td colspan="7" class="text-center text-muted">Không có khóa phù hợp.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
    </div>
    <?php if($xd_course_name!==''): ?>
    <div class="card card-primary card-outline text-sm" id="xd-course-detail">
        <div class="card-header"><h3 class="card-title">Khóa <?=$escape($xd_course_name)?> <span class="badge badge-light"><?=$xd_course_total?></span></h3><div class="card-tools"><a href="index.php?com=xangdau&act=hocvien&khoa=<?=urlencode($xd_course_name)?>" class="btn btn-sm btn-outline-info"><i class="fas fa-users mr-1"></i>Học viên XD</a></div></div>
        <div class="card-body">
            <p class="text-muted">Học viên đồng bộ DAT giữ nguyên khóa Đào tạo. Đổi tên chỉ áp dụng hồ sơ nhập trực tiếp XD chưa thanh toán.</p>
            <?php if(xd_can_import()): ?><form method="post" action="index.php?com=xangdau&act=saveKhoa" class="form-inline mb-3" onsubmit="return confirm('Đổi tên khóa cho các hồ sơ chưa thanh toán? Hồ sơ đã thanh toán/duyệt/thuộc bảng kê được giữ nguyên tên khóa cũ.');"><input type="hidden" name="csrf" value="<?=$escape($_SESSION['xd_course_csrf'])?>"><input type="hidden" name="khoa" value="<?=$escape($xd_course_name)?>"><label class="mr-2" for="xd-course-new-name">Tên khóa mới</label><input id="xd-course-new-name" name="ten_khoa" class="form-control form-control-sm mr-2" maxlength="100" required value="<?=$escape($xd_course_name)?>"><button class="btn btn-sm btn-primary"><i class="fas fa-save mr-1"></i>Lưu tên khóa</button></form><?php endif; ?>
            <?php if(xd_can_xoa()): ?><form method="post" action="index.php?com=xangdau&act=deleteKhoa" class="mb-3" onsubmit="return confirm('Xóa học viên chưa thanh toán trong khóa này? Hồ sơ đã thanh toán/duyệt/thuộc bảng kê và dữ liệu Đào tạo sẽ không bị thay đổi. Hệ thống sao lưu trước khi xóa.');"><input type="hidden" name="csrf" value="<?=$escape($_SESSION['xd_course_csrf'])?>"><input type="hidden" name="khoa" value="<?=$escape($xd_course_name)?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i>Xóa học viên chưa thanh toán của khóa</button></form><?php endif; ?>
            <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Học viên</th><th>CCCD</th><th>Nhóm</th><th>Giáo viên</th><th>Nguồn</th><th>Tiền TT</th><th>Ngày TT</th><th>Trạng thái</th></tr></thead><tbody>
            <?php foreach($xd_course_students as $student): ?><tr><td><?=$escape($student['ho_ten'])?></td><td><?=$escape($student['cccd'])?></td><td><?=$escape($student['nhom'])?></td><td><?=$escape($student['gv_hoten'])?></td><td><?=$escape($student['user_tao'])?></td><td class="text-right"><?=number_format((float)$student['so_tien_thanh_toan'],0,',','.')?></td><td><?=$escape($student['ngay_thanh_toan']??'—')?></td><td><?=xd_course_mutable($student)?'Chưa thanh toán':'Giữ lịch sử / bảng kê'?></td></tr><?php endforeach; ?>
            <?php if(!$xd_course_students): ?><tr><td colspan="8" class="text-center text-muted">Khóa không còn học viên.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div><div class="card-footer"><?=$xd_course_paging?></div>
    </div>
    <?php endif; ?>
    <?php if(xd_can_import()): ?>
    <div class="modal fade" id="xd-merge-modal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" role="document"><form method="post" action="index.php?com=xangdau&act=mergeKhoa" class="modal-content text-sm">
        <input type="hidden" name="csrf" value="<?=$escape($_SESSION['xd_course_csrf'])?>"><input type="hidden" name="khoa" id="xd-merge-source">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-compress-arrows-alt mr-1"></i>Gộp khóa</h5><button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body">
            <div class="mb-3"><div class="text-muted small">Khóa nguồn (sẽ được chuyển đi)</div><div class="font-weight-bold" id="xd-merge-source-label"></div></div>
            <div class="form-group mb-2"><label for="xd-merge-target">Gộp vào khóa đúng</label>
                <select name="target" id="xd-merge-target" class="form-control form-control-sm" required>
                    <option value="">— Chọn khóa đích —</option>
                    <?php foreach($xd_course_targets as $value=>$target): ?><option value="<?=$escape($value)?>" data-xd="<?=(int)$target['xd']?>"><?=$escape($target['label'])?></option><?php endforeach; ?>
                </select></div>
            <div class="alert alert-warning py-2 mb-2 small" id="xd-merge-preview" style="display:none"></div>
            <div class="alert alert-info py-2 mb-0 small">Toàn bộ học viên của khóa nguồn được chuyển sang khóa đích (kể cả đã thanh toán, chỉ đổi tên khóa, không đổi tiền). Học viên đồng bộ DAT sẽ theo khóa Đào tạo khi đồng bộ lại. Có thể hoàn tác ngay tại dòng khóa đích hoặc nút "Lịch sử gộp khóa" ở đầu trang.</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Hủy</button><button class="btn btn-sm btn-warning"><i class="fas fa-compress-arrows-alt mr-1"></i>Gộp khóa</button></div>
    </form></div></div>
    <script>
    document.addEventListener('change',function(e){
        if(e.target.id!=='xd-merge-target')return;
        var p=document.getElementById('xd-merge-preview'),o=e.target.options[e.target.selectedIndex];
        if(!o||o.value===''){p.style.display='none';return;}
        var n=parseInt(document.getElementById('xd-merge-source').getAttribute('data-so'),10)||0,x=parseInt(o.getAttribute('data-xd'),10)||0;
        p.textContent='Sau khi gộp, khóa đích có '+(x+n)+' học viên XD ('+x+' hiện có + '+n+' chuyển sang).';p.style.display='block';
    });
    document.addEventListener('click',function(e){
        var b=e.target.closest&&e.target.closest('.xd-merge-open');if(!b)return;
        document.getElementById('xd-merge-source').value=b.getAttribute('data-khoa');
        document.getElementById('xd-merge-source-label').textContent=b.getAttribute('data-label');
        var sel=document.getElementById('xd-merge-target');sel.value='';
        document.getElementById('xd-merge-preview').style.display='none';
        document.getElementById('xd-merge-source').setAttribute('data-so',b.getAttribute('data-so'));
        Array.prototype.forEach.call(sel.options,function(o){o.disabled=(o.value!==''&&o.value===b.getAttribute('data-khoa'));});
        if(window.jQuery)jQuery('#xd-merge-modal').modal('show');
    });
    </script>
    <?php endif; ?>
</section>