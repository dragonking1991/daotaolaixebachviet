<?php
	$q = '';
	if($xd_loc_ky !== '') $q .= '&ky='.urlencode($xd_loc_ky);
	if($xd_loc_from !== '') $q .= '&from_date='.urlencode($xd_loc_from);
	if($xd_loc_to !== '') $q .= '&to_date='.urlencode($xd_loc_to);
	$nhomBadge = array('BT' => 'badge-primary', 'CK' => 'badge-info', 'DAT' => 'badge-warning');
	$tongChon = 0;
	$tongTien = 0.0;
	if(!empty($xd_loc_selected)) { foreach($xd_loc_selected as $s) { $tongChon++; $tongTien += (float)$s['so_tien_thanh_toan']; } }
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Lọc thanh toán xăng dầu</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top">
		<form method="get" action="index.php" class="form-inline mb-2">
			<input type="hidden" name="com" value="xangdau">
			<input type="hidden" name="act" value="loc">
			<div class="form-group mb-0 mr-2">
				<label class="mr-1">Kỳ</label>
				<select class="form-control form-control-sm text-sm" name="ky">
					<option value="">Tất cả kỳ chưa quyết toán</option>
					<?php if(!empty($xd_loc_ky_options)) { foreach($xd_loc_ky_options as $ko) { ?>
						<option value="<?=htmlspecialchars($ko['ky'])?>" <?=($xd_loc_ky==$ko['ky'])?'selected':''?>><?=htmlspecialchars($ko['ky'])?></option>
					<?php } } ?>
				</select>
			</div>
			<div class="form-group mb-0 mr-2">
				<label class="mr-1">Từ ngày HĐ</label>
				<input class="form-control form-control-sm text-sm" type="date" name="from_date" value="<?=htmlspecialchars($xd_loc_from)?>">
			</div>
			<div class="form-group mb-0 mr-2">
				<label class="mr-1">Đến ngày HĐ</label>
				<input class="form-control form-control-sm text-sm" type="date" name="to_date" value="<?=htmlspecialchars($xd_loc_to)?>">
			</div>
			<button type="submit" class="btn btn-sm bg-gradient-primary text-white"><i class="fas fa-filter mr-1"></i>Chạy lọc</button>
		</form>
		<div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
			<?php if(!empty($xd_loc_summary)) { ?>
			<div class="btn-group btn-group-sm" role="group" aria-label="Xuất báo cáo">
				<?php $exportAll = 'index.php?com=xangdau&act=xuatTatCaBangKe&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
				<a class="btn btn-success" href="<?=$exportAll?>" title="Xuất toàn bộ danh sách hóa đơn và học viên"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a>
				<?php $exportHv = 'index.php?com=xangdau&act=xuatToanBoDanhSachHocVien&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
				<a class="btn btn-info" href="<?=$exportHv?>" title="Xuất danh sách học viên"><i class="fas fa-list mr-1"></i>Xuất HV</a>
				<?php if(xd_can_duyet()) { $exportTongHop = 'index.php?com=xangdau&act=xuatTongHopGiaoVien&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
				<a class="btn btn-secondary" href="<?=$exportTongHop?>" title="Xuất file tổng hợp thanh toán theo giáo viên"><i class="fas fa-file-invoice-dollar mr-1"></i>Xuất tổng hợp GV</a>
				<?php } ?>
			</div>
			<?php } ?>
			<?php if(xd_can_kiem_tra() || xd_can_duyet()) { ?>
			<div class="btn-group btn-group-sm" role="group" aria-label="Danh sách theo trạng thái">
				<?php if(xd_can_kiem_tra()) { $checkList = 'index.php?com=xangdau&act=locKiemTra'; ?><a class="btn btn-outline-warning" href="<?=$checkList?>" title="Danh sách chưa kiểm tra"><i class="fas fa-search mr-1"></i>Chưa kiểm tra</a><?php } ?>
				<?php if(xd_can_duyet()) { $approveList = 'index.php?com=xangdau&act=locDuyet'; ?><a class="btn btn-outline-primary" href="<?=$approveList?>" title="Danh sách chờ duyệt"><i class="fas fa-check-circle mr-1"></i>Chờ duyệt</a><?php } ?>
			</div>
			<?php } ?>
			<?php if(xd_can_duyet() && !empty($xd_loc_summary)) { $approveAll = 'index.php?com=xangdau&act=duyetTatCaGiaoVien&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
			<a class="btn btn-sm btn-success" href="<?=$approveAll?>" onclick="return confirm('Duyệt toàn bộ giáo viên đã được kế toán kiểm tra?');"><i class="fas fa-stamp mr-1"></i>Duyệt tất cả</a>
			<?php } ?>
		</div>
	</div>

	<div class="card card-info card-outline text-sm mb-3">
		<div class="card-header"><h3 class="card-title">Tổng hợp theo giáo viên</h3></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Giáo viên</th>
						<th class="text-center">Số HĐ</th>
						<th class="text-right">Tổng HĐ (S_HĐ)</th>
						<th class="text-center">N tối đa</th>
						<th class="text-center">HV được chọn</th>
						<th class="text-right">Định mức tối đa</th>
						<th class="text-right">Chênh lệch</th>
						<th class="text-right">Tổng chi</th>
						<th>Kiểm tra</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($xd_loc_summary)) { $i = 0; foreach($xd_loc_summary as $g) { $i++; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($g['gv_hoten'] !== '' ? $g['gv_hoten'] : $g['gv_key'])?></td>
						<td class="text-center"><?=(int)$g['so_hd']?></td>
						<td class="text-right"><?=number_format((float)$g['s_hd'], 0, ',', '.')?></td>
						<td class="text-center"><?=(int)$g['n_max']?></td>
						<td class="text-center"><strong><?=(int)$g['so_hv_chon']?></strong></td>
						<td class="text-right"><?=number_format((float)$g['dinh_muc_toi_da'], 0, ',', '.')?></td>
						<td class="text-right"><span class="badge <?=abs((float)$g['chenh_lech']) <= 500000 ? 'badge-success' : 'badge-danger'?>"><?=number_format((float)$g['chenh_lech'], 0, ',', '.')?></span></td>
						<td class="text-right"><?=number_format((float)$g['tong_chi'], 0, ',', '.')?></td>
						<td>
							<?php if((int)$g['ke_toan_kiem_tra'] === 1) { ?><span class="badge badge-success">Đã kiểm tra</span><?php } else { ?><span class="badge badge-warning">Chưa kiểm tra</span><?php } ?>
						</td>
						<td class="text-right">
							<?php $teacherView = 'index.php?com=xangdau&act=xemGiaoVien&gv_key='.urlencode($g['gv_key']); $teacherCheck = 'index.php?com=xangdau&act=kiemTraGiaoVien&gv_key='.urlencode($g['gv_key']); $teacherApprove = 'index.php?com=xangdau&act=duyetGiaoVien&gv_key='.urlencode($g['gv_key']).'&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
							<a class="btn btn-xs btn-info" href="<?=$teacherView?>" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
							<?php if(xd_can_kiem_tra() && (int)$g['ke_toan_kiem_tra'] === 0) { ?><a class="btn btn-xs btn-warning" href="<?=$teacherCheck?>" title="Kế toán xác nhận đã kiểm tra" onclick="return confirm('Xác nhận đã kiểm tra giáo viên này?');"><i class="fas fa-check"></i></a><?php } ?>
							<?php if(xd_can_duyet()) { ?><a class="btn btn-xs btn-primary" href="<?=$teacherApprove?>" title="Quản lý duyệt giáo viên" onclick="return confirm('Duyệt thanh toán cho giáo viên này?');"><i class="fas fa-stamp"></i></a><?php } ?>
							<?php $teacherExport = 'index.php?com=xangdau&act=xuatBangKeGiaoVien&gv_key='.urlencode($g['gv_key']).'&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="11" class="text-center text-muted">Chưa có dữ liệu. Chọn kỳ/khoảng ngày rồi bấm "Chạy lọc".</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header">
			<h3 class="card-title">Danh sách học viên được duyệt thanh toán</h3>
			<span class="ml-3">Tổng: <strong><?=$tongChon?></strong> học viên - <strong><?=number_format($tongTien, 0, ',', '.')?></strong> đ</span>
		</div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Họ tên</th>
						<th>CCCD</th>
						<th>Nhóm</th>
						<th>Giáo viên</th>
						<th class="text-right">Định mức XD</th>
						<th class="text-right">Số tiền thanh toán</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($xd_loc_selected)) { $i = 0; foreach($xd_loc_selected as $hv) { $i++; $badge = isset($nhomBadge[$hv['nhom']]) ? $nhomBadge[$hv['nhom']] : 'badge-secondary'; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($hv['ho_ten'])?></td>
						<td><?=htmlspecialchars($hv['cccd'])?></td>
						<td><span class="badge <?=$badge?>"><?=htmlspecialchars($hv['nhom'])?></span></td>
						<td><?=htmlspecialchars($hv['gv_hoten'] !== '' ? $hv['gv_hoten'] : $hv['gv_key'])?></td>
						<td class="text-right"><?=number_format((float)$hv['dinh_muc'], 0, ',', '.')?></td>
						<td class="text-right"><?=number_format((float)$hv['so_tien_thanh_toan'], 0, ',', '.')?></td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="7" class="text-center text-muted">Không có học viên nào đủ điều kiện.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
