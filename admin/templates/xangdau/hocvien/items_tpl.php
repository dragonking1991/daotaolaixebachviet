<?php
	$linkMan = "index.php?com=xangdau&act=hocvien";
	$linkUpload = "index.php?com=xangdau&act=uploadHocvien";
	$nhomBadge = array('BT' => 'badge-primary', 'CK' => 'badge-info', 'DAT' => 'badge-warning');
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
			<a class="btn btn-sm bg-gradient-primary text-white mr-2" href="index.php?com=xangdau&act=loc"><i class="fas fa-filter mr-1"></i>Lọc thanh toán</a>
			<?php if(xd_can_duyet() || xd_can_kiem_tra()) { $exportBase = 'index.php?com=xangdau&act=xuatHocvienExcel'; $exportUrlAll = $exportBase.'&scope=all'; $exportUrlPaid = $exportBase.'&scope=paid'; $exportUrlUnpaid = $exportBase.'&scope=unpaid'; if($xd_filter_keyword !== '') { $exportUrlAll .= '&keyword='.urlencode($xd_filter_keyword); $exportUrlPaid .= '&keyword='.urlencode($xd_filter_keyword); $exportUrlUnpaid .= '&keyword='.urlencode($xd_filter_keyword); } if($xd_filter_nhom !== '') { $exportUrlAll .= '&nhom='.urlencode($xd_filter_nhom); $exportUrlPaid .= '&nhom='.urlencode($xd_filter_nhom); $exportUrlUnpaid .= '&nhom='.urlencode($xd_filter_nhom); } if($xd_filter_tt_from !== '') { $exportUrlAll .= '&tt_from='.urlencode($xd_filter_tt_from); $exportUrlPaid .= '&tt_from='.urlencode($xd_filter_tt_from); $exportUrlUnpaid .= '&tt_from='.urlencode($xd_filter_tt_from); } if($xd_filter_tt_to !== '') { $exportUrlAll .= '&tt_to='.urlencode($xd_filter_tt_to); $exportUrlPaid .= '&tt_to='.urlencode($xd_filter_tt_to); $exportUrlUnpaid .= '&tt_to='.urlencode($xd_filter_tt_to); } if($xd_filter_trangthai !== '') { $exportUrlAll .= '&trangthai='.urlencode($xd_filter_trangthai); $exportUrlPaid .= '&trangthai='.urlencode($xd_filter_trangthai); $exportUrlUnpaid .= '&trangthai='.urlencode($xd_filter_trangthai); } ?>
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
		<div class="card-header"><h3 class="card-title">Danh sách học viên xăng dầu</h3></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Họ tên</th>
						<th>CCCD</th>
						<th>Khóa</th>
						<th>Ngày sinh</th>
						<th>Nhóm</th>
						<th>GV phụ trách</th>
						<th class="text-right">Số tiền TT</th>
						<th>Trạng thái</th>
						<th>Ngày TT</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)) { $i = 0; foreach($items as $it) { $i++; $badge = isset($nhomBadge[$it['nhom']]) ? $nhomBadge[$it['nhom']] : 'badge-secondary'; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($it['ho_ten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars(isset($it['khoa']) ? $it['khoa'] : '')?></td>
						<td><?=htmlspecialchars($it['ngaysinh'])?></td>
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
								<?php if(xd_can_xoa()) { ?>
								<a class="btn btn-xs btn-danger" href="index.php?com=xangdau&act=deleteHocvien&id=<?=(int)$it['id']?>&p=<?=$curPage?>" onclick="return confirm('Xóa học viên này?');"><i class="far fa-trash-alt"></i></a>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="11" class="text-center text-muted">Chưa có học viên nào</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer text-sm"><?=isset($paging) ? $paging : ''?></div>
	</div>
</section>
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
