<?php if(!defined('SOURCES'))die('Error'); $escape=function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}; ?>
<section class="content-header text-sm"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php?com=xangdau&act=hocvien">Học viên XD</a></li><li class="breadcrumb-item active">Quản lý khóa XD</li></ol></section>
<section class="content">
    <div class="card card-primary card-outline text-sm">
        <div class="card-header"><h3 class="card-title">Quản lý khóa XD <span class="badge badge-light"><?=count($xd_courses)?></span></h3></div>
        <div class="card-body">
            <form method="get" class="form-inline mb-3"><input type="hidden" name="com" value="xangdau"><input type="hidden" name="act" value="khoa"><input type="search" name="keyword" class="form-control form-control-sm mr-2" placeholder="Mã / Tên khóa XD" value="<?=$escape($_GET['keyword']??'')?>"><button class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Tìm</button></form>
            <div class="table-responsive"><table class="table table-sm table-bordered table-hover"><thead><tr><th>Khóa / nguồn</th><th>Tổng HV</th><th>Đã thanh toán</th><th>Có thể xóa (chưa TT)</th><th>GV</th><th>Tiền đã ghi</th><th></th></tr></thead><tbody>
            <?php foreach($xd_courses as $course): ?>
                <tr><td><?=$escape(xd_hocvien_course_label($course))?></td><td><?=(int)$course['so']?></td><td><?=(int)$course['paid']?></td><td><?=(int)$course['editable']?></td><td><?=(int)$course['teachers']?></td><td class="text-right"><?=number_format((float)$course['amount'],0,',','.')?></td><td><a class="btn btn-xs btn-outline-info" href="index.php?com=xangdau&act=khoa&khoa=<?=urlencode($course['khoa'])?>#xd-course-detail" title="Xem chi tiết khóa"><i class="fas fa-eye"></i></a></td></tr>
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
</section>