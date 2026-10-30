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

<section class="content">
	<div class="card-footer text-sm sticky-top bg-light border-bottom py-3">
		<form method="get" action="index.php" class="d-flex flex-wrap align-items-end" style="gap:.75rem;">
			<input type="hidden" name="com" value="xangdau">
			<input type="hidden" name="act" value="hocvien">
			<a class="btn btn-sm bg-gradient-success text-white mr-2" href="<?=$linkUpload?>"><i class="fas fa-upload mr-1"></i>Import học viên</a>
			<?php if(xd_can_import()) { ?><a class="btn btn-sm bg-gradient-info text-white mr-2" href="index.php?com=xangdau&act=syncTrainingDat" onclick="return confirm('Thêm học viên đã đạt DAT từ Đào tạo vào Học viên XD cho mọi hạng, kể cả CE? Toàn bộ hồ sơ XD đã có được giữ nguyên.');"><i class="fas fa-rotate mr-1"></i>Đồng bộ HV đạt DAT</a><?php } ?>
			<a class="btn btn-sm bg-gradient-secondary text-white mr-2" href="index.php?com=xangdau&act=ce"><i class="fas fa-truck-moving mr-1"></i>Danh sách CE</a>
			<a class="btn btn-sm bg-gradient-primary text-white mr-2" href="index.php?com=xangdau&act=loc"><i class="fas fa-filter mr-1"></i>Lọc thanh toán</a>
			<?php if(xd_can_duyet() || xd_can_kiem_tra()) { $exportBase = 'index.php?com=xangdau&act=xuatHocvienExcel'; $exportUrlAll = $exportBase.'&scope=all'; $exportUrlPaid = $exportBase.'&scope=paid'; $exportUrlUnpaid = $exportBase.'&scope=unpaid'; if($xd_filter_keyword !== '') { $exportUrlAll .= '&keyword='.urlencode($xd_filter_keyword); $exportUrlPaid .= '&keyword='.urlencode($xd_filter_keyword); $exportUrlUnpaid .= '&keyword='.urlencode($xd_filter_keyword); } if($xd_filter_nhom !== '') { $exportUrlAll .= '&nhom='.urlencode($xd_filter_nhom); $exportUrlPaid .= '&nhom='.urlencode($xd_filter_nhom); $exportUrlUnpaid .= '&nhom='.urlencode($xd_filter_nhom); } if($xd_filter_tt_from !== '') { $exportUrlAll .= '&tt_from='.urlencode($xd_filter_tt_from); $exportUrlPaid .= '&tt_from='.urlencode($xd_filter_tt_from); $exportUrlUnpaid .= '&tt_from='.urlencode($xd_filter_tt_from); } if($xd_filter_tt_to !== '') { $exportUrlAll .= '&tt_to='.urlencode($xd_filter_tt_to); $exportUrlPaid .= '&tt_to='.urlencode($xd_filter_tt_to); $exportUrlUnpaid .= '&tt_to='.urlencode($xd_filter_tt_to); } if($xd_filter_trangthai !== '') { $exportUrlAll .= '&trangthai='.urlencode($xd_filter_trangthai); $exportUrlPaid .= '&trangthai='.urlencode($xd_filter_trangthai); $exportUrlUnpaid .= '&trangthai='.urlencode($xd_filter_trangthai); } ?>
			<?php $xdExtra = ($xd_filter_khoa !== '' ? '&khoa='.urlencode($xd_filter_khoa) : '').($xd_filter_gv !== '' ? '&gv='.urlencode($xd_filter_gv) : ''); $exportUrlAll .= $xdExtra; $exportUrlPaid .= $xdExtra; $exportUrlUnpaid .= $xdExtra; ?>
			<div class="btn-group mr-3">
				<button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</button>
				<div class="dropdown-menu">
					<a class="dropdown-item" href="<?=$exportUrlAll?>">Tổng</a>
					<a class="dropdown-item" href="<?=$exportUrlPaid?>">Đã thanh toán</a>
					<a class="dropdown-item" href="<?=$exportUrlUnpaid?>">Chưa thanh toán</a>
				</div>
			</div>
			<?php } ?>
			<?php if(xd_can_xoa()) { ?><a class="btn btn-sm btn-danger mr-3" href="index.php?com=xangdau&act=deleteAllHocvien" onclick="return confirm('Xóa TOÀN BỘ học viên, bao gồm cả học viên đã thanh toán? Dữ liệu đã xóa không thể khôi phục.');"><i class="fas fa-trash-alt mr-1"></i>Xóa toàn bộ</a><?php } ?>
			<?php if(xd_can_xoa()) { ?>
			<div class="form-group mb-0 d-flex align-items-end mr-3">
				<div class="mr-2">
					<label class="mb-1 small text-muted" for="delete_hocvien_gv">Xóa theo giáo viên</label>
					<select class="form-control form-control-sm" id="delete_hocvien_gv" style="min-width:260px;">
						<option value="">Chọn giáo viên</option>
						<?php foreach(($xd_hocvien_teachers ?? array()) as $teacher) { $canDelete = (int)($teacher['can_delete'] ?? 0); $protected = (int)($teacher['protected_count'] ?? 0); ?>
						<option value="<?=htmlspecialchars($teacher['gv_key'], ENT_QUOTES, 'UTF-8')?>" data-name="<?=htmlspecialchars($teacher['gv_hoten'], ENT_QUOTES, 'UTF-8')?>" data-can-delete="<?=$canDelete?>" data-protected="<?=$protected?>">
							<?=htmlspecialchars($teacher['gv_hoten'] !== '' ? $teacher['gv_hoten'] : $teacher['gv_key'])?> (<?=$canDelete?> xóa<?=$protected > 0 ? ', '.$protected.' giữ lại' : ''?>)
						</option>
						<?php } ?>
					</select>
				</div>
				<a class="btn btn-sm btn-outline-danger disabled" id="delete_hocvien_gv_btn" href="#"><i class="fas fa-user-times mr-1"></i>Xóa học viên GV</a>
			</div>
			<?php } ?>
			<div class="w-100"></div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Tìm kiếm</label>
				<input class="form-control form-control-sm text-sm" style="min-width:200px;" type="search" name="keyword" placeholder="Tên / CCCD HV / Tên GV" value="<?=htmlspecialchars($xd_filter_keyword)?>">
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Nhóm</label>
				<select class="form-control form-control-sm text-sm" name="nhom">
					<option value="">Tất cả nhóm</option>
					<option value="BT" <?=($xd_filter_nhom=='BT')?'selected':''?>>BT</option>
					<option value="CK" <?=($xd_filter_nhom=='CK')?'selected':''?>>CK</option>
					<option value="DAT" <?=($xd_filter_nhom=='DAT')?'selected':''?>>DAT</option>
				</select>
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Khóa</label>
				<select class="form-control form-control-sm text-sm" name="khoa" style="min-width:280px;max-width:420px;" title="Lọc học viên theo khóa hoặc nhóm hạng">
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
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Giáo viên</label>
				<select class="form-control form-control-sm text-sm" name="gv" style="min-width:200px;">
					<option value="">Tất cả giáo viên</option>
					<?php foreach(($xd_hocvien_teachers ?? array()) as $teacher) { ?>
					<option value="<?=htmlspecialchars($teacher['gv_key'], ENT_QUOTES, 'UTF-8')?>" <?=($xd_filter_gv === (string)$teacher['gv_key'])?'selected':''?>><?=htmlspecialchars($teacher['gv_hoten'] !== '' ? $teacher['gv_hoten'] : $teacher['gv_key'])?></option>
					<?php } ?>
				</select>
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Trạng thái</label>
				<select class="form-control form-control-sm text-sm" name="trangthai">
					<option value="">Tất cả trạng thái</option>
					<option value="da" <?=($xd_filter_trangthai=='da')?'selected':''?>>Đã thanh toán</option>
					<option value="chua" <?=($xd_filter_trangthai=='chua')?'selected':''?>>Chưa thanh toán</option>
				</select>
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start"><label class="mb-1 small text-muted">Ngày TT từ</label><input class="form-control form-control-sm text-sm" type="date" name="tt_from" value="<?=htmlspecialchars($xd_filter_tt_from)?>"></div>
			<div class="form-group mb-0 d-flex flex-column align-items-start"><label class="mb-1 small text-muted">Ngày TT đến</label><input class="form-control form-control-sm text-sm" type="date" name="tt_to" value="<?=htmlspecialchars($xd_filter_tt_to)?>"></div>
			<button type="submit" class="btn btn-sm bg-gradient-success text-white mr-1"><i class="fas fa-search mr-1"></i>Lọc</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="<?=$linkMan?>">Bỏ lọc</a>
		</form>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header">
			<h3 class="card-title">Danh sách học viên xăng dầu</h3>
			<div class="card-tools text-muted">Học viên mới đồng bộ lấy khóa và giáo viên từ Đào tạo; B1/B11/C1 giữ nhóm BT/CK theo hệ đào tạo, CE vào nhóm DAT. Hồ sơ đồng bộ cũ đúng liên kết được sửa DAT → BT/CK; lịch sử thanh toán được giữ nguyên.<?php if(xd_can_xoa()) { ?>
				<button type="submit" form="xd-bulk-hocvien" class="btn btn-sm btn-danger ml-2 xd-bulk-delete" data-label="học viên" data-note="Học viên đã thanh toán hoặc thuộc bảng kê sẽ được giữ lại."><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button>
				<?php if($xd_filter_khoa !== ''): ?>
				<button type="submit" name="delete_course" value="1" form="xd-bulk-hocvien" class="btn btn-sm btn-outline-danger ml-1" onclick="return confirm(<?=htmlspecialchars(json_encode('Xóa toàn bộ học viên đủ điều kiện trong khóa '.$xd_filter_khoa.' trên TẤT CẢ các trang? Thao tác áp dụng cho cả khóa, không phụ thuộc các bộ lọc khác hoặc ô đã chọn. Giữ hồ sơ có thanh toán, bảng kê, duyệt/kiểm tra; sao lưu trước khi xóa. Dữ liệu Đào tạo không bị xóa. Đồng bộ lại chỉ tạo học viên đạt DAT; các khoản tiền nguồn/ngày nộp hồ sơ cần import lại từ Excel.', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')?>)"><i class="fas fa-trash-alt mr-1" aria-hidden="true"></i>Xóa toàn khóa</button>
				<?php endif; ?>
				<?php } ?></div>
		</div>
		<div class="card-body table-responsive p-0">
				<form id="xd-bulk-hocvien" method="post" action="index.php?com=xangdau&act=deleteHocvienSelected"><input type="hidden" name="ret" value="<?=$xd_ret?>"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['xd_dat_csrf'])?>"><input type="hidden" name="khoa" value="<?=htmlspecialchars($xd_filter_khoa)?>"></form>
			<table class="table table-hover">
				<?php if(xd_can_import()): ?>
				<caption style="caption-side:top;"><button type="submit" form="xd-bulk-hocvien" formaction="index.php?com=xangdau&act=markImportedDat" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i>Đạt DAT mục đã chọn</button> <?php if($xd_filter_khoa !== ''): ?><button type="submit" name="all" value="1" form="xd-bulk-hocvien" formaction="index.php?com=xangdau&act=markImportedDat" class="btn btn-sm btn-outline-success" onclick="return confirm('Xác nhận DAT cho toàn bộ học viên chưa thanh toán trong khóa?')">Đạt DAT toàn khóa</button><?php endif; ?></caption>
				<?php endif; ?>
				<thead>
					<tr>
						<th class="text-center" style="width:34px"><?php if(xd_can_xoa() || xd_can_import()) { ?><input type="checkbox" class="xd-bulk-all" data-form="xd-bulk-hocvien" title="Chọn tất cả"><?php } ?></th>
						<th>STT</th>
						<th>Họ tên</th>
						<th>CCCD</th>
						<th>Khóa</th>
						<th>Ngày sinh</th>
						<th>Ngày nộp hồ sơ</th>
						<th>Nhóm</th>
						<th>GV phụ trách</th>
						<th class="text-right">Số tiền TT XD</th>
						<th>Trạng thái</th>
						<th>Ngày TT</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)) { $i = 0; foreach($items as $it) { $i++; $badge = isset($nhomBadge[$it['nhom']]) ? $nhomBadge[$it['nhom']] : 'badge-secondary'; ?>
					<tr>
						<td class="text-center"><?php if(xd_can_xoa() || xd_can_import()) { ?><input type="checkbox" class="xd-bulk-item" form="xd-bulk-hocvien" name="ids[]" value="<?=(int)$it['id']?>"><?php } ?></td>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($it['ho_ten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars(isset($it['khoa']) ? $it['khoa'] : '')?></td>
						<td><?=htmlspecialchars($it['ngaysinh'])?></td>
						<td><?=!empty($it['ngay_nop_ho_so']) ? date('d/m/Y', strtotime($it['ngay_nop_ho_so'])) : ''?></td>
						<td><span class="badge <?=$badge?>"><?=htmlspecialchars($it['nhom'])?></span></td>
						<td><?=htmlspecialchars($it['gv_hoten'])?></td>
						<td class="text-right"><?=number_format((float)$it['so_tien_thanh_toan'], 0, ',', '.')?></td>
						<td>
							<?php if($it['ngay_thanh_toan'] !== null) { ?>
								<a class="badge badge-success" href="index.php?com=xangdau&act=updateHocvienStatus&id=<?=(int)$it['id']?>&status=chua&p=<?=(int)$curPage?>" onclick="return confirm('Chuyển học viên <?=htmlspecialchars($it['ho_ten'], ENT_QUOTES, 'UTF-8')?> (CCCD: <?=htmlspecialchars($it['cccd'], ENT_QUOTES, 'UTF-8')?>) về chưa thanh toán?');">Đã thanh toán</a>
							<?php } else { ?>
								<a class="badge badge-light" href="index.php?com=xangdau&act=updateHocvienStatus&id=<?=(int)$it['id']?>&status=da&p=<?=(int)$curPage?>" onclick="return confirm('Xác nhận học viên <?=htmlspecialchars($it['ho_ten'], ENT_QUOTES, 'UTF-8')?> (CCCD: <?=htmlspecialchars($it['cccd'], ENT_QUOTES, 'UTF-8')?>) đã thanh toán?');">Chưa thanh toán</a>
							<?php } ?>
						</td>
						<td>
							<?php if($it['ngay_thanh_toan'] !== null) { ?>
								<span class="badge badge-secondary"><?=date('d/m/Y', strtotime($it['ngay_thanh_toan']))?></span>
							<?php } else { ?>
								<span class="badge badge-light">Chưa TT</span>
							<?php } ?>
						</td>
						<td class="text-right">
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
					<tr><td colspan="13" class="text-center text-muted py-4">
						<div style="font-size:34px;line-height:1;color:#c8ccd4;margin-bottom:8px;"><i class="fas fa-users"></i></div>
						<div class="mb-2">Chưa có học viên xăng dầu nào.</div>
						<a class="btn btn-sm bg-gradient-success text-white" href="<?=$linkUpload?>"><i class="fas fa-upload mr-1"></i>Import học viên ngay</a>
					</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer text-sm"><?=isset($paging) ? $paging : ''?></div>
	</div>
</section>
<script src="assets/js/xangdau-bulk.js"></script>
<?php if(xd_can_xoa()) { ?>
<script>
	(function() {
		var select = document.getElementById('delete_hocvien_gv');
		var button = document.getElementById('delete_hocvien_gv_btn');
		if (!select || !button) return;
		select.addEventListener('change', function() {
			var option = select.options[select.selectedIndex];
			var gvKey = select.value;
			var name = option ? option.getAttribute('data-name') : '';
			var canDelete = option ? option.getAttribute('data-can-delete') : '0';
			var protectedCount = option ? option.getAttribute('data-protected') : '0';
			button.classList.toggle('disabled', gvKey === '' || canDelete === '0');
			button.href = gvKey === '' ? '#' : 'index.php?com=xangdau&act=deleteHocvienByGv&gv_key=' + encodeURIComponent(gvKey);
			button.onclick = function() {
				if (gvKey === '' || canDelete === '0') return false;
				var message = 'Xóa ' + canDelete + ' học viên chưa thanh toán của giáo viên ' + name + '?';
				if (protectedCount !== '0') message += '\n\n' + protectedCount + ' học viên đã thanh toán hoặc thuộc bảng kê sẽ được giữ lại.';
				message += '\n\nDữ liệu đã xóa không thể khôi phục.';
				return confirm(message);
			};
		});
	})();
</script>
<?php } ?>
