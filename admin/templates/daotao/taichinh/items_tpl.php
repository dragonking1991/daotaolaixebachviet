<?php if(!defined('SOURCES')) die('Error'); ?>
<section class="content-header text-sm"><div class="container-fluid"><ol class="breadcrumb float-sm-left"><li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li><li class="breadcrumb-item active">Học phí, tạm ứng, thanh toán</li></ol></div></section>
<section class="content">
<div class="card card-primary card-outline text-sm">
    <div class="card-header"><h3 class="card-title"><strong>Học phí, tạm ứng, thanh toán</strong> <span class="dt-count"><?=count($finance_students)?></span></h3>
    <?php if(dt_can('taichinh','export')): ?><div class="card-tools"><a class="btn btn-sm bg-gradient-info" href="<?=dt_export_link('exportTaichinh',array('id_khoa','group'))?>"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a></div><?php endif; ?></div>
    <div class="card-body">
        <h6><i class="fas fa-file-invoice-dollar mr-1" aria-hidden="true"></i>Tiền nguồn từ Excel <span class="dt-count"><?=(int)$finance_source['total']?></span></h6>
        <div class="alert alert-info py-2">6 khoản tiền nguồn được đọc từ dữ liệu import XD (VNĐ), gồm tất cả nhóm BT/CK/DAT, không yêu cầu đạt DAT. Chỉ xem/đối chiếu; không cộng vào giao dịch tạm ứng, thanh toán hoặc học phí đã ghi nhận bên Đào tạo. Import và nơi lưu dữ liệu giữ nguyên.</div>
        <form method="get" class="form-inline mb-2">
            <input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="taichinh">
            <input type="hidden" name="id_khoa" value="<?=(int)$finance_course?>"><input type="hidden" name="group" value="<?=htmlspecialchars($finance_group)?>">
            <select name="source_khoa" class="form-control form-control-sm mr-2" aria-label="Khóa XD của tiền nguồn"><option value="">Tất cả khóa XD (tiền nguồn)</option><?php foreach($finance_source_courses as $course): if($course['khoa']==='') continue; ?><option value="<?=htmlspecialchars($course['khoa'])?>" <?=$course['khoa']===$finance_source_course?'selected':''?>><?=htmlspecialchars(course_option_label($course))?></option><?php endforeach; ?></select>
            <button class="btn btn-sm bg-gradient-primary mr-2">Lọc tiền nguồn</button>
            <?php if(dt_can('taichinh','export')): ?><a class="btn btn-sm bg-gradient-info" href="<?=htmlspecialchars('index.php?'.http_build_query(array('com'=>'daotao','act'=>'exportTaichinh','source_export'=>'1','source_khoa'=>$finance_source_course)))?>"><i class="fas fa-file-excel mr-1"></i>Xuất tiền nguồn</a><?php endif; ?>
        </form>
        <div class="table-responsive"><table class="table dt-list table-hover"><thead><tr><th>Học viên</th><th>Khóa XD / Nhóm</th><th>Giáo viên</th><?php foreach(xd_student_financial_fields() as $label): ?><th class="text-right"><?=htmlspecialchars($label)?><small class="d-block text-muted">Nguồn · VNĐ</small></th><?php endforeach; ?></tr></thead><tbody>
        <?php foreach($finance_source['rows'] as $student): ?><tr>
            <td><div class="dt-title"><?=htmlspecialchars($student['ho_ten'])?></div><div class="dt-sub"><?=htmlspecialchars($student['cccd'])?></div></td>
            <td><?=htmlspecialchars($student['khoa'].' / '.$student['nhom'])?></td><td><?=htmlspecialchars($student['gv_hoten'])?></td>
            <?php foreach(xd_student_financial_fields() as $key => $label): ?><td class="text-right"><?=number_format((float)$student[$key],0,',','.')?></td><?php endforeach; ?>
        </tr><?php endforeach; ?>
        <?php if(!$finance_source['rows']): ?><tr><td colspan="9" class="text-center text-muted">Chưa có dữ liệu tiền nguồn trong khóa đã chọn.</td></tr><?php endif; ?>
        </tbody></table></div>
        <div class="mb-3"><?=$finance_source_paging?></div>
        <hr><h6>Giao dịch Đào tạo (đạt DAT, hệ CK/DAT)</h6>
        <form method="get" class="form-inline mb-3">
            <input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="taichinh">
            <input type="hidden" name="source_khoa" value="<?=htmlspecialchars($finance_source_course)?>">
            <select name="group" class="form-control form-control-sm mr-2" style="max-width:100%;"><option value="bc" <?=$finance_group==='bc'?'selected':''?>>Khác CE (đạt DAT, CK/DAT)</option><option value="ce" <?=$finance_group==='ce'?'selected':''?>>CE (đạt DAT, CK/DAT)</option></select>
            <select name="id_khoa" class="form-control form-control-sm mr-2"><option value="0">Chọn khóa</option><?php foreach($finance_courses as $course): ?><option value="<?=(int)$course['id']?>" <?=(int)$course['id']===$finance_course?'selected':''?>><?=htmlspecialchars(course_option_label($course))?></option><?php endforeach; ?></select>
            <button class="btn btn-sm bg-gradient-primary"><i class="fas fa-filter mr-1"></i>Lọc</button>
        </form>
        <?php if(dt_can('taichinh','edit')): ?>
        <form id="dt-finance-form" method="post" action="index.php?com=daotao&act=saveTaichinh" class="mb-3">
            <input type="hidden" name="id_khoa" value="<?=$finance_course?>"><input type="hidden" name="group" value="<?=$finance_group?>"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_finance_csrf'])?>"><input type="hidden" name="request_key" value="<?=bin2hex(random_bytes(16))?>">
            <div class="form-row align-items-end">
                <div class="col-sm-2 form-group mb-0"><label>Giao dịch</label><select name="loai" class="form-control form-control-sm"><option value="tam_ung">Tạm ứng</option><option value="thanh_toan">Thanh toán</option></select></div>
                <div class="col-sm-2 form-group mb-0"><label>Ngày</label><input required type="date" name="ngay" value="<?=date('Y-m-d')?>" class="form-control form-control-sm"></div>
                <div class="col-sm-4 form-group mb-0" data-finance-advance><label>Số tiền tạm ứng / học viên (đ)</label><input required type="number" min="1" max="9999999999999" step="1" name="so_tien_tam_ung" class="form-control form-control-sm" placeholder="Nhập số tiền thực tạm ứng"></div>
                <div class="col-sm-2 form-group mb-0" data-finance-settlement hidden><label>Tiền lương / học viên (đ)</label><input disabled type="number" min="0" max="9999999999999" step="1" name="tien_luong" value="0" class="form-control form-control-sm"></div>
                <div class="col-sm-2 form-group mb-0" data-finance-settlement hidden><label>Nhiên liệu / học viên (đ)</label><input disabled type="number" min="0" max="9999999999999" step="1" name="tien_nhien_lieu" value="0" class="form-control form-control-sm"></div>
                <div class="col-sm-2 form-group mb-0" data-finance-settlement hidden><label>Tổng lương + nhiên liệu (đ)</label><output id="dt-finance-total" class="form-control form-control-sm">0</output></div>
                <div class="col-sm-2"><button class="btn btn-sm bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu mục đã chọn</button></div>
            </div>
        </form>
        <?php endif; ?>
        <div class="table-responsive"><table class="table dt-list table-hover"><thead><tr><th><?php if(dt_can('taichinh','edit')): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-finance-form" title="Chọn tất cả"><?php endif; ?></th><th>Học viên</th><th>Hạng / Giáo viên</th><th class="text-right">Học phí đã đóng</th><th class="text-right">Học phí còn thiếu</th><th class="text-right">Đã tạm ứng</th><th class="text-right">Thanh toán</th><th>Trạng thái</th></tr></thead><tbody>
        <?php foreach($finance_students as $student): ?><tr>
            <td><?php if(dt_can('taichinh','edit') && !$student['da_tt']): ?><input type="checkbox" form="dt-finance-form" class="dt-bulk-item" name="ids[]" value="<?=(int)$student['id']?>"><?php endif; ?></td>
            <td><div class="dt-title"><?=htmlspecialchars($student['hoten'])?></div><div class="dt-sub"><?=htmlspecialchars($student['cccd'])?></div></td>
            <td><?=htmlspecialchars($student['hang'].' / '.$student['gv_hoten'])?></td>
            <?php foreach(array('hoc_phi_da_dong','hoc_phi_con_thieu','tam_ung','thanh_toan') as $field): ?><td class="text-right"><?=number_format((float)$student[$field],0,',','.')?></td><?php endforeach; ?>
            <td><span class="badge badge-<?=$student['da_tt']?'success':'secondary'?>"><?=$student['da_tt']?'Đã thanh toán':'Chưa thanh toán'?></span></td>
        </tr><?php endforeach; ?>
        <?php if(!$finance_students): ?><tr><td colspan="8" class="text-center text-muted">Chưa có học viên phù hợp</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php if($finance_history): ?><h6 class="mt-3">Lịch sử giao dịch trong khóa</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Ngày</th><th>Học viên</th><th>Giao dịch</th><th>Lương</th><th>Nhiên liệu</th><th>Số tiền thực chi</th><th>Người nhập</th></tr></thead><tbody><?php foreach($finance_history as $entry): ?><tr><td><?=htmlspecialchars($entry['ngay'])?></td><td><?=htmlspecialchars($entry['hoten'].' / '.$entry['cccd'])?></td><td><?=$entry['loai']==='tam_ung'?'Tạm ứng':'Thanh toán'?></td><?php foreach(array('tien_luong','tien_nhien_lieu','so_tien') as $field): ?><td><?=number_format((float)$entry[$field],0,',','.')?></td><?php endforeach; ?><td><?=htmlspecialchars($entry['nguoi_nhap'])?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>
</div>
</section>
<script>
(function(){
    var form=document.getElementById('dt-finance-form');
    if(!form) return;
    var salary=form.querySelector('[name="tien_luong"]');
    var fuel=form.querySelector('[name="tien_nhien_lieu"]');
    var kind=form.querySelector('[name="loai"]');
    var advance=form.querySelector('[name="so_tien_tam_ung"]');
    function updateTotal(){document.getElementById('dt-finance-total').textContent=(Number(salary.value||0)+Number(fuel.value||0)).toLocaleString('vi-VN');}
    function updateMode(){
        var isAdvance=kind.value==='tam_ung';
        form.querySelector('[data-finance-advance]').hidden=!isAdvance;
        advance.disabled=!isAdvance;
        advance.required=isAdvance;
        Array.prototype.forEach.call(form.querySelectorAll('[data-finance-settlement]'),function(section){section.hidden=isAdvance;});
        salary.disabled=isAdvance;
        fuel.disabled=isAdvance;
        salary.required=!isAdvance;
        fuel.required=!isAdvance;
        updateTotal();
    }
    salary.addEventListener('input',updateTotal);
    fuel.addEventListener('input',updateTotal);
    kind.addEventListener('change',updateMode);
    updateMode();
})();
</script>