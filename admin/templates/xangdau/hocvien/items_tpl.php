<?php
	$linkMan = "index.php?com=xangdau&act=hocvien";
	$linkUpload = "index.php?com=xangdau&act=uploadHocvien";
	$nhomBadge = array('BT' => 'badge-primary', 'CK' => 'badge-info', 'DAT' => 'badge-warning');
	$xd_ret_q = array('com' => 'xangdau', 'act' => 'hocvien');
	if($xd_filter_keyword !== '') $xd_ret_q['keyword'] = $xd_filter_keyword;
	if($xd_filter_nhom !== '') $xd_ret_q['nhom'] = $xd_filter_nhom;
	if($xd_filter_khoa !== '') $xd_ret_q['khoa'] = $xd_filter_khoa;
	if($xd_filter_gv !== '') $xd_ret_q['gv'] = $xd_filter_gv;
	if($xd_filter_trangthai !== '') $xd_ret_q['trangthai'] = $xd_filter_trangthai;
	if($xd_filter_tt_from !== '') $xd_ret_q['tt_from'] = $xd_filter_tt_from;
	if($xd_filter_tt_to !== '') $xd_ret_q['tt_to'] = $xd_filter_tt_to;
	$xd_ret_q['p'] = (int)$curPage;
	$xd_ret = rawurlencode('index.php?'.http_build_query($xd_ret_q));
	$xd_can_edit_info = (function_exists('xd_can_import') && xd_can_import()) || (function_exists('xd_can_kiem_tra') && xd_can_kiem_tra());
	if(empty($_SESSION['xd_dat_csrf'])) $_SESSION['xd_dat_csrf'] = bin2hex(random_bytes(24));
?>
<?php
	$xdFilterQuery = array('keyword' => $xd_filter_keyword, 'nhom' => $xd_filter_nhom, 'khoa' => $xd_filter_khoa, 'gv' => $xd_filter_gv, 'trangthai' => $xd_filter_trangthai, 'tt_from' => $xd_filter_tt_from, 'tt_to' => $xd_filter_tt_to);
	$xdFilterQuery = array_filter($xdFilterQuery, function($value) { return $value !== ''; });
	$xdHasFilter = !empty($xdFilterQuery);
	$xdCourseNames = array();
	$xdRankNames = array('bss' => 'B số sàn', 'btd' => 'B tự động', 'c1' => 'C1', 'c' => 'C', 'ce' => 'CE');
	foreach(($xd_hocvien_khoas ?? array()) as $course)
	{
		$name = trim((string)($course['ten_khoa'] ?? ''));
		if($name === '') $name = $xdRankNames[strtolower(trim((string)$course['khoa']))] ?? '';
		$xdCourseNames[(string)$course['khoa']] = $name;
	}
	$xdTeacherNames = array();
	foreach(($xd_hocvien_teachers ?? array()) as $teacher) $xdTeacherNames[(string)$teacher['gv_key']] = $teacher['gv_hoten'] !== '' ? $teacher['gv_hoten'] : $teacher['gv_key'];
	$xdChipLabels = array(
		'keyword' => 'Tìm',
		'nhom' => 'Nhóm',
		'khoa' => 'Khóa',
		'gv' => 'GV',
		'trangthai' => 'Trạng thái',
		'tt_from' => 'TT từ',
		'tt_to' => 'TT đến',
	);
	$xdChipValue = function($key, $value) use ($xdCourseNames, $xdTeacherNames) {
		if($key === 'khoa') return ($xdCourseNames[$value] ?? '') !== '' ? $xdCourseNames[$value].' ('.$value.')' : $value;
		if($key === 'gv') return $xdTeacherNames[$value] ?? $value;
		if($key === 'trangthai') return $value === 'da' ? 'Đã thanh toán' : 'Chưa thanh toán';
		if($key === 'tt_from' || $key === 'tt_to') return xd_display_date($value);
		return $value;
	};
	$xdTotal = (int)($xd_hocvien_total ?? 0);
	$xdOffset = (int)($xd_hocvien_offset ?? 0);
	$xdShown = is_array($items) ? count($items) : 0;
	$xdCanBulk = xd_can_xoa() || xd_can_import();
?>
<style>
	.xd-hv-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
	.xd-hv-toolbar .xd-hv-spacer { flex:1 1 auto; }
	.xd-hv-filters { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:.5rem .75rem; align-items:end; margin-top:.75rem; }
	.xd-hv-filters .xd-hv-wide { grid-column:span 2; }
	.xd-hv-filters label { font-size:.75rem; color:#6c757d; margin-bottom:.15rem; }
	.xd-hv-filters .xd-hv-actions { display:flex; gap:.35rem; }
	@media (max-width: 575.98px) { .xd-hv-filters .xd-hv-wide { grid-column:span 1; } }
	.xd-hv-chips { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.6rem; align-items:center; }
	.xd-hv-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.15rem .55rem; border-radius:999px; background:#e8f0fe; color:#1a4fb4; font-size:.75rem; text-decoration:none; }
	.xd-hv-chip:hover { background:#d2e2fd; color:#123c8a; text-decoration:none; }
	.xd-hv-bulkbar { display:flex; flex-wrap:wrap; gap:.4rem; align-items:center; padding:.5rem .75rem; background:#f8f9fa; border-bottom:1px solid #e9ecef; }
	.xd-hv-bulkbar .xd-hv-selected { font-weight:600; margin-right:.25rem; }
	.xd-hv-bulkbar .xd-hv-divider { width:1px; align-self:stretch; background:#dee2e6; margin:0 .25rem; }
	.xd-hv-table td, .xd-hv-table th { vertical-align:middle; }
	.xd-hv-table thead th { position:sticky; top:0; z-index:2; background:#fff; box-shadow:inset 0 -1px 0 #dee2e6; white-space:nowrap; }
	.xd-hv-course small, .xd-hv-sub { display:block; color:#6c757d; font-size:.72rem; line-height:1.1; }
	.xd-hv-name { font-weight:600; text-transform:uppercase; }
	.xd-hv-danger .dropdown-item.text-danger:hover { background:#fdecea; }
	.xd-hv-help { cursor:help; color:#6c757d; }
	.xd-hv-scroll { max-height:calc(100vh - 260px); overflow:auto; }
</style>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Học viên xăng dầu</li>
			</ol>
		</div>
	</div>
</section>

<section class="content xd-hv-page">
	<div class="card card-outline card-primary text-sm mb-2">
		<div class="card-body py-2">
			<div class="xd-hv-toolbar">
				<a class="btn btn-sm bg-gradient-success text-white" href="<?=$linkUpload?>"><i class="fas fa-upload mr-1"></i>Import học viên</a>
				<?php if(xd_can_import()) { ?><a class="btn btn-sm bg-gradient-info text-white" href="index.php?com=xangdau&act=syncTrainingDat" onclick="return confirm('Thêm học viên đã đạt DAT từ Đào tạo vào Học viên XD cho mọi hạng, kể cả CE? Toàn bộ hồ sơ XD đã có được giữ nguyên.');" title="Lấy học viên đã đạt DAT từ Đào tạo"><i class="fas fa-rotate mr-1"></i>Đồng bộ HV đạt DAT</a><?php } ?>
				<span class="xd-hv-spacer"></span>
				<div class="btn-group">
					<a class="btn btn-sm btn-outline-secondary" href="index.php?com=xangdau&act=ce"><i class="fas fa-truck-moving mr-1"></i>Danh sách CE</a>
					<a class="btn btn-sm btn-outline-primary" href="index.php?com=xangdau&act=loc"><i class="fas fa-filter mr-1"></i>Lọc thanh toán</a>
				</div>
				<?php if(xd_can_duyet() || xd_can_kiem_tra()) { $exportBase = 'index.php?com=xangdau&act=xuatHocvienExcel&'.http_build_query(array_diff_key($xdFilterQuery, array('trangthai' => 1))); ?>
				<div class="btn-group">
					<button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Xuất theo bộ lọc hiện tại"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</button>
					<div class="dropdown-menu dropdown-menu-right">
						<h6 class="dropdown-header">Theo bộ lọc hiện tại</h6>
						<a class="dropdown-item" href="<?=htmlspecialchars($exportBase.'&scope=all'.($xd_filter_trangthai !== '' ? '&trangthai='.urlencode($xd_filter_trangthai) : ''))?>">Tổng</a>
						<a class="dropdown-item" href="<?=htmlspecialchars($exportBase.'&scope=paid'.($xd_filter_trangthai !== '' ? '&trangthai='.urlencode($xd_filter_trangthai) : ''))?>">Đã thanh toán</a>
						<a class="dropdown-item" href="<?=htmlspecialchars($exportBase.'&scope=unpaid'.($xd_filter_trangthai !== '' ? '&trangthai='.urlencode($xd_filter_trangthai) : ''))?>">Chưa thanh toán</a>
					</div>
				</div>
				<?php } ?>
				<?php if(xd_can_xoa()) { ?>
				<div class="btn-group xd-hv-danger">
					<button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Các thao tác xóa hàng loạt"><i class="fas fa-shield-alt mr-1"></i>Quản trị dữ liệu</button>
					<div class="dropdown-menu dropdown-menu-right">
						<h6 class="dropdown-header">Không phụ thuộc bộ lọc</h6>
						<a class="dropdown-item text-danger" href="#xd-delete-gv-panel" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="xd-delete-gv-panel"><i class="fas fa-user-times mr-2"></i>Xóa học viên theo giáo viên…</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item text-danger" id="xd-delete-all-btn" href="index.php?com=xangdau&act=deleteAllHocvien"><i class="fas fa-trash-alt mr-2"></i>Xóa toàn bộ học viên…</a>
					</div>
				</div>
				<?php } ?>
			</div>

			<?php if(xd_can_xoa()) { ?>
			<div class="collapse" id="xd-delete-gv-panel">
				<div class="alert alert-light border border-danger mt-2 mb-0 py-2">
					<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
						<div>
							<label class="mb-1 small text-danger font-weight-bold" for="delete_hocvien_gv">Xóa TẤT CẢ học viên chưa thanh toán của giáo viên</label>
							<select class="form-control form-control-sm" id="delete_hocvien_gv" style="min-width:300px;">
								<option value="">Chọn giáo viên</option>
								<?php foreach(($xd_hocvien_teachers ?? array()) as $teacher) { $canDelete = (int)($teacher['can_delete'] ?? 0); $protected = (int)($teacher['protected_count'] ?? 0); ?>
								<option value="<?=htmlspecialchars($teacher['gv_key'], ENT_QUOTES, 'UTF-8')?>" data-name="<?=htmlspecialchars($teacher['gv_hoten'], ENT_QUOTES, 'UTF-8')?>" data-can-delete="<?=$canDelete?>" data-protected="<?=$protected?>">
									<?=htmlspecialchars($teacher['gv_hoten'] !== '' ? $teacher['gv_hoten'] : $teacher['gv_key'])?> (<?=$canDelete?> xóa<?=$protected > 0 ? ', '.$protected.' giữ lại' : ''?>)
								</option>
								<?php } ?>
							</select>
						</div>
						<a class="btn btn-sm btn-danger disabled" id="delete_hocvien_gv_btn" href="#" aria-disabled="true"><i class="fas fa-user-times mr-1"></i>Xóa học viên GV</a>
					</div>
					<div class="small text-muted mt-1">Áp dụng cho mọi khóa/trang, không theo bộ lọc bên dưới. Học viên đã thanh toán hoặc thuộc bảng kê luôn được giữ lại.</div>
				</div>
			</div>
			<?php } ?>

			<form method="get" action="index.php" class="xd-hv-filters" role="search" aria-label="Lọc học viên">
				<input type="hidden" name="com" value="xangdau">
				<input type="hidden" name="act" value="hocvien">
				<div>
					<label for="xd-f-keyword">Tìm kiếm</label>
					<input id="xd-f-keyword" class="form-control form-control-sm" type="search" name="keyword" placeholder="Tên / CCCD HV / Tên GV" value="<?=htmlspecialchars($xd_filter_keyword)?>">
				</div>
				<div class="xd-hv-wide">
					<label for="xd-f-khoa">Khóa</label>
					<select id="xd-f-khoa" class="form-control form-control-sm" name="khoa" title="Lọc học viên theo khóa hoặc nhóm hạng">
						<option value="">Tất cả khóa</option>
						<?php $xdCourseGroupLabels=array('synced'=>'Đồng bộ từ Đào tạo','mixed'=>'Có dữ liệu Đào tạo và XD','stored'=>'Lưu riêng trong XD','rank'=>'Gộp theo hạng · không phải khóa'); foreach($xdCourseGroupLabels as $groupKey=>$groupLabel) { $groupCourses=$xd_hocvien_khoa_groups[$groupKey]??array(); if(!$groupCourses)continue; ?>
						<optgroup label="<?=htmlspecialchars($groupLabel.' ('.count($groupCourses).')', ENT_QUOTES, 'UTF-8')?>">
							<?php foreach($groupCourses as $k) { ?>
							<option value="<?=htmlspecialchars($k['khoa'], ENT_QUOTES, 'UTF-8')?>" <?=($xd_filter_khoa === (string)$k['khoa'])?'selected':''?>><?=htmlspecialchars(xd_hocvien_course_label($k), ENT_QUOTES, 'UTF-8')?></option>
							<?php } ?>
						</optgroup>
						<?php } ?>
					</select>
				</div>
				<div>
					<label for="xd-f-nhom">Nhóm</label>
					<select id="xd-f-nhom" class="form-control form-control-sm" name="nhom">
						<option value="">Tất cả nhóm</option>
						<?php foreach(array('BT','CK','DAT') as $n) { ?><option value="<?=$n?>" <?=($xd_filter_nhom===$n)?'selected':''?>><?=$n?></option><?php } ?>
					</select>
				</div>
				<div>
					<label for="xd-f-gv">Giáo viên</label>
					<select id="xd-f-gv" class="form-control form-control-sm" name="gv">
						<option value="">Tất cả giáo viên</option>
						<?php foreach(($xd_hocvien_teachers ?? array()) as $teacher) { ?>
						<option value="<?=htmlspecialchars($teacher['gv_key'], ENT_QUOTES, 'UTF-8')?>" <?=($xd_filter_gv === (string)$teacher['gv_key'])?'selected':''?>><?=htmlspecialchars($teacher['gv_hoten'] !== '' ? $teacher['gv_hoten'] : $teacher['gv_key'])?></option>
						<?php } ?>
					</select>
				</div>
				<div>
					<label for="xd-f-tt">Trạng thái</label>
					<select id="xd-f-tt" class="form-control form-control-sm" name="trangthai">
						<option value="">Tất cả trạng thái</option>
						<option value="da" <?=($xd_filter_trangthai=='da')?'selected':''?>>Đã thanh toán</option>
						<option value="chua" <?=($xd_filter_trangthai=='chua')?'selected':''?>>Chưa thanh toán</option>
					</select>
				</div>
				<div class="xd-hv-wide">
					<label for="xd-f-from">Ngày thanh toán (từ – đến)</label>
					<div class="input-group input-group-sm">
						<input id="xd-f-from" class="form-control" type="date" name="tt_from" value="<?=htmlspecialchars($xd_filter_tt_from)?>" aria-label="Ngày thanh toán từ">
						<div class="input-group-prepend input-group-append"><span class="input-group-text">–</span></div>
						<input class="form-control" type="date" name="tt_to" value="<?=htmlspecialchars($xd_filter_tt_to)?>" aria-label="Ngày thanh toán đến">
					</div>
				</div>
				<div class="xd-hv-actions">
					<button type="submit" class="btn btn-sm bg-gradient-success text-white flex-fill"><i class="fas fa-search mr-1"></i>Lọc</button>
					<?php if($xdHasFilter) { ?><a class="btn btn-sm btn-outline-secondary" href="<?=$linkMan?>">Bỏ lọc</a><?php } ?>
				</div>
			</form>

			<?php if($xdHasFilter) { ?>
			<div class="xd-hv-chips" aria-label="Bộ lọc đang áp dụng">
				<span class="small text-muted mr-1">Đang lọc:</span>
				<?php foreach($xdFilterQuery as $key => $value) { $without = $xdFilterQuery; unset($without[$key]); ?>
				<a class="xd-hv-chip" href="<?=htmlspecialchars('index.php?'.http_build_query(array_merge(array('com' => 'xangdau', 'act' => 'hocvien'), $without)))?>" title="Bỏ điều kiện này"><?=htmlspecialchars($xdChipLabels[$key].': '.$xdChipValue($key, $value))?> <i class="fas fa-times" aria-hidden="true"></i></a>
				<?php } ?>
			</div>
			<?php } ?>
		</div>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header d-flex flex-wrap align-items-center" style="gap:.5rem;">
			<h3 class="card-title mb-0">Danh sách học viên xăng dầu
				<i class="fas fa-info-circle xd-hv-help ml-1" data-toggle="tooltip" title="Học viên mới đồng bộ lấy khóa và giáo viên từ Đào tạo; B1/B11/C1 giữ nhóm BT/CK theo hệ đào tạo, CE vào nhóm DAT. Hồ sơ đồng bộ cũ đúng liên kết được sửa DAT → BT/CK; lịch sử thanh toán được giữ nguyên."></i>
			</h3>
			<span class="ml-auto text-muted"><?php if($xdTotal > 0) { ?>Hiển thị <strong><?=number_format($xdOffset + 1)?>–<?=number_format($xdOffset + $xdShown)?></strong> / <strong><?=number_format($xdTotal)?></strong> học viên<?php } else { ?>0 học viên<?php } ?></span>
		</div>
		<form id="xd-bulk-hocvien" method="post" action="index.php?com=xangdau&act=deleteHocvienSelected"><input type="hidden" name="ret" value="<?=$xd_ret?>"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['xd_dat_csrf'])?>"><input type="hidden" name="khoa" value="<?=htmlspecialchars($xd_filter_khoa)?>"></form>
		<?php if($xdCanBulk && !empty($items)) { ?>
		<div class="xd-hv-bulkbar" role="toolbar" aria-label="Thao tác hàng loạt">
			<span class="xd-hv-selected" id="xd-hv-selected" aria-live="polite">Chưa chọn học viên</span>
			<?php if(xd_can_import()) { ?><button type="submit" form="xd-bulk-hocvien" formaction="index.php?com=xangdau&act=markImportedDat" class="btn btn-sm btn-success xd-hv-needs-selection" disabled><i class="fas fa-check mr-1"></i>Đạt DAT mục đã chọn</button><?php } ?>
			<?php if(xd_can_xoa()) { ?><button type="submit" form="xd-bulk-hocvien" class="btn btn-sm btn-outline-danger xd-bulk-delete xd-hv-needs-selection" data-label="học viên" data-note="Học viên đã thanh toán hoặc thuộc bảng kê sẽ được giữ lại." disabled><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button><?php } ?>
			<?php if($xd_filter_khoa !== '') { $xdCourseText = ($xdCourseNames[$xd_filter_khoa] ?? '') !== '' ? $xdCourseNames[$xd_filter_khoa].' ('.$xd_filter_khoa.')' : $xd_filter_khoa; ?>
			<span class="xd-hv-divider" aria-hidden="true"></span>
			<span class="small text-muted">Cả khóa <strong><?=htmlspecialchars($xdCourseText)?></strong> (mọi trang, bỏ qua bộ lọc khác):</span>
			<?php if(xd_can_import()) { ?><button type="submit" name="all" value="1" form="xd-bulk-hocvien" formaction="index.php?com=xangdau&act=markImportedDat" class="btn btn-sm btn-outline-success" onclick="return confirm(<?=htmlspecialchars(json_encode('Xác nhận DAT cho toàn bộ học viên chưa thanh toán trong khóa '.$xdCourseText.'?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')?>)">Đạt DAT toàn khóa</button><?php } ?>
			<?php if(xd_can_xoa()) { ?><button type="submit" name="delete_course" value="1" form="xd-bulk-hocvien" class="btn btn-sm btn-outline-danger" onclick="return confirm(<?=htmlspecialchars(json_encode('Xóa toàn bộ học viên đủ điều kiện trong khóa '.$xdCourseText.' trên TẤT CẢ các trang? Thao tác áp dụng cho cả khóa, không phụ thuộc các bộ lọc khác hoặc ô đã chọn. Giữ hồ sơ có thanh toán, bảng kê, duyệt/kiểm tra; sao lưu trước khi xóa. Dữ liệu Đào tạo không bị xóa. Đồng bộ lại chỉ tạo học viên đạt DAT; các khoản tiền nguồn/ngày nộp hồ sơ cần import lại từ Excel.', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')?>)"><i class="fas fa-trash-alt mr-1" aria-hidden="true"></i>Xóa toàn khóa</button><?php } ?>
			<?php } ?>
		</div>
		<?php } ?>
		<div class="card-body table-responsive p-0 xd-hv-scroll">
			<table class="table table-hover table-sm mb-0 xd-hv-table">
				<thead>
					<tr>
						<th class="text-center" style="width:34px"><?php if($xdCanBulk) { ?><input type="checkbox" class="xd-bulk-all" data-form="xd-bulk-hocvien" title="Chọn tất cả trên trang này" aria-label="Chọn tất cả trên trang này"><?php } ?></th>
						<th>STT</th>
						<th>Họ tên</th>
						<th>CCCD</th>
						<th>Khóa</th>
						<th>Ngày sinh</th>
						<th>Ngày nộp hồ sơ</th>
						<th>Nhóm</th>
						<th>GV phụ trách</th>
						<th class="text-right">Số tiền TT XD</th>
						<th>Thanh toán</th>
						<th class="text-right">Thao tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)) { $i = $xdOffset; foreach($items as $it) { $i++; $badge = isset($nhomBadge[$it['nhom']]) ? $nhomBadge[$it['nhom']] : 'badge-secondary'; $courseCode = (string)($it['khoa'] ?? ''); $courseName = $xdCourseNames[$courseCode] ?? ''; ?>
					<tr>
						<td class="text-center"><?php if($xdCanBulk) { ?><input type="checkbox" class="xd-bulk-item" form="xd-bulk-hocvien" name="ids[]" value="<?=(int)$it['id']?>" aria-label="Chọn <?=htmlspecialchars($it['ho_ten'], ENT_QUOTES, 'UTF-8')?>"><?php } ?></td>
						<td class="text-muted"><?=$i?></td>
						<td class="xd-hv-name"><?=htmlspecialchars($it['ho_ten'])?></td>
						<td class="text-nowrap"><?=htmlspecialchars($it['cccd'])?></td>
						<td class="xd-hv-course"><?php if($courseName !== '' && $courseName !== $courseCode) { ?><strong><?=htmlspecialchars($courseName)?></strong><small><?=htmlspecialchars($courseCode)?></small><?php } else { ?><?=htmlspecialchars($courseCode)?><?php } ?></td>
						<td class="text-nowrap"><?=htmlspecialchars(xd_display_date($it['ngaysinh']))?></td>
						<td class="text-nowrap"><?=!empty($it['ngay_nop_ho_so']) ? htmlspecialchars(xd_display_date($it['ngay_nop_ho_so'])) : '<span class="text-muted">—</span>'?></td>
						<td><span class="badge <?=$badge?>"><?=htmlspecialchars($it['nhom'])?></span></td>
						<td><?=htmlspecialchars($it['gv_hoten'])?></td>
						<td class="text-right text-nowrap"><?=number_format((float)$it['so_tien_thanh_toan'], 0, ',', '.')?></td>
						<td class="text-nowrap">
							<?php if($it['ngay_thanh_toan'] !== null) { ?>
								<a class="badge badge-success" href="index.php?com=xangdau&act=updateHocvienStatus&id=<?=(int)$it['id']?>&status=chua&p=<?=(int)$curPage?>" title="Bấm để chuyển về chưa thanh toán" onclick="return confirm('Chuyển học viên <?=htmlspecialchars($it['ho_ten'], ENT_QUOTES, 'UTF-8')?> (CCCD: <?=htmlspecialchars($it['cccd'], ENT_QUOTES, 'UTF-8')?>) về chưa thanh toán?');">Đã TT</a>
								<span class="xd-hv-sub"><?=date('d/m/Y', strtotime($it['ngay_thanh_toan']))?></span>
							<?php } else { ?>
								<a class="badge badge-light border" href="index.php?com=xangdau&act=updateHocvienStatus&id=<?=(int)$it['id']?>&status=da&p=<?=(int)$curPage?>" title="Bấm để xác nhận đã thanh toán" onclick="return confirm('Xác nhận học viên <?=htmlspecialchars($it['ho_ten'], ENT_QUOTES, 'UTF-8')?> (CCCD: <?=htmlspecialchars($it['cccd'], ENT_QUOTES, 'UTF-8')?>) đã thanh toán?');">Chưa TT</a>
							<?php } ?>
						</td>
						<td class="text-right text-nowrap">
								<?php if($xd_can_edit_info && $it['ngay_thanh_toan'] === null) { ?>
								<details class="d-inline-block text-left" style="position:relative;">
									<summary class="btn btn-xs btn-outline-primary" style="list-style:none;cursor:pointer;"><i class="fas fa-user-edit"></i></summary>
									<form method="post" action="index.php?com=xangdau&act=updateHocvienInfo" class="p-2 border rounded shadow-sm bg-white" style="position:absolute;right:0;z-index:20;min-width:240px;">
										<input type="hidden" name="id" value="<?=(int)$it['id']?>">
										<input type="hidden" name="ret" value="<?=$xd_ret?>">
										<div class="form-group mb-1"><label class="small mb-0">Nhóm</label><select name="nhom" class="form-control form-control-sm"><?php foreach(array('BT','CK','DAT') as $n) { ?><option value="<?=$n?>" <?=$it['nhom']===$n?'selected':''?>><?=$n?></option><?php } ?></select></div>
										<div class="form-group mb-1"><label class="small mb-0">Giáo viên</label><input type="text" name="gv_hoten" class="form-control form-control-sm" value="<?=htmlspecialchars($it['gv_hoten'], ENT_QUOTES, 'UTF-8')?>"></div>
										<button class="btn btn-xs btn-success"><i class="fas fa-save mr-1"></i>Lưu</button>
									</form>
								</details>
								<?php } ?>
								<?php if(xd_can_xoa()) { ?>
								<a class="btn btn-xs btn-danger" href="index.php?com=xangdau&act=deleteHocvien&id=<?=(int)$it['id']?>&p=<?=$curPage?>" onclick="return confirm('Xóa học viên này?');"><i class="far fa-trash-alt"></i></a>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="12" class="text-center text-muted py-4">
						<div style="font-size:34px;line-height:1;color:#c8ccd4;margin-bottom:8px;"><i class="fas <?=$xdHasFilter ? 'fa-search' : 'fa-users'?>"></i></div>
						<?php if($xdHasFilter) { ?>
						<div class="mb-2">Không có học viên khớp bộ lọc đang chọn.</div>
						<a class="btn btn-sm btn-outline-secondary" href="<?=$linkMan?>"><i class="fas fa-times mr-1"></i>Bỏ lọc</a>
						<?php } else { ?>
						<div class="mb-2">Chưa có học viên xăng dầu nào.</div>
						<a class="btn btn-sm bg-gradient-success text-white" href="<?=$linkUpload?>"><i class="fas fa-upload mr-1"></i>Import học viên ngay</a>
						<?php } ?>
					</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer text-sm"><?=isset($paging) ? $paging : ''?></div>
	</div>
</section>
<script src="assets/js/xangdau-bulk.js?v=<?=@filemtime(__DIR__.'/../../../assets/js/xangdau-bulk.js')?>"></script>
<script>
	(function() {
		if (window.jQuery && jQuery.fn.tooltip) jQuery('.xd-hv-page [data-toggle="tooltip"]').tooltip();
		var counter = document.getElementById('xd-hv-selected');
		function refreshSelection() {
			var n = document.querySelectorAll('.xd-bulk-item[form="xd-bulk-hocvien"]:checked').length;
			if (counter) counter.textContent = n > 0 ? 'Đã chọn ' + n + ' học viên' : 'Chưa chọn học viên';
			Array.prototype.forEach.call(document.querySelectorAll('.xd-hv-needs-selection'), function(button) { button.disabled = n === 0; });
		}
		document.addEventListener('change', function(event) {
			if (event.target.matches('.xd-bulk-item, .xd-bulk-all')) setTimeout(refreshSelection, 0);
		});
		refreshSelection();
	})();
</script>
<?php if(xd_can_xoa()) { ?>
<script>
	(function() {
		var deleteAll = document.getElementById('xd-delete-all-btn');
		if (deleteAll) deleteAll.addEventListener('click', function(event) {
			var typed = prompt('Xóa TOÀN BỘ <?=number_format((int)($xd_hocvien_all_total ?? 0))?> học viên XD, kể cả học viên đã thanh toán (lịch sử thanh toán sẽ mất khỏi danh sách; hệ thống sao lưu JSON trước khi xóa).\n\nGõ XOA TOAN BO để xác nhận:');
			if (typed === null || typed.trim().toUpperCase() !== 'XOA TOAN BO') {
				event.preventDefault();
				if (typed !== null) alert('Chưa xóa: nội dung xác nhận không đúng.');
			}
		});
		var select = document.getElementById('delete_hocvien_gv');
		var button = document.getElementById('delete_hocvien_gv_btn');
		if (!select || !button) return;
		select.addEventListener('change', function() {
			var option = select.options[select.selectedIndex];
			var gvKey = select.value;
			var name = option ? option.getAttribute('data-name') : '';
			var canDelete = option ? option.getAttribute('data-can-delete') : '0';
			var protectedCount = option ? option.getAttribute('data-protected') : '0';
			var disabled = gvKey === '' || canDelete === '0';
			button.classList.toggle('disabled', disabled);
			button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
			button.href = gvKey === '' ? '#' : 'index.php?com=xangdau&act=deleteHocvienByGv&gv_key=' + encodeURIComponent(gvKey) + (window.ADMIN_CSRF ? '&_csrf=' + encodeURIComponent(window.ADMIN_CSRF.token) : '');
			button.onclick = function() {
				if (disabled) return false;
				var message = 'Xóa ' + canDelete + ' học viên chưa thanh toán của giáo viên ' + name + ' (mọi khóa, không theo bộ lọc)?';
				if (protectedCount !== '0') message += '\n\n' + protectedCount + ' học viên đã thanh toán hoặc thuộc bảng kê sẽ được giữ lại.';
				message += '\n\nHệ thống sao lưu trước khi xóa.';
				return confirm(message);
			};
		});
	})();
</script>
<?php } ?>
