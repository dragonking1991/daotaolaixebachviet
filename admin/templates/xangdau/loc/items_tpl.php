<?php
	$q = '';
	if($xd_loc_ky !== '') $q .= '&ky='.urlencode($xd_loc_ky);
	if($xd_loc_from !== '') $q .= '&from_date='.urlencode($xd_loc_from);
	if($xd_loc_to !== '') $q .= '&to_date='.urlencode($xd_loc_to);
	if($xd_loc_gv_keyword !== '') $q .= '&gv_keyword='.urlencode($xd_loc_gv_keyword);
	if($xd_loc_ma_hoa_don !== '') $q .= '&ma_hoa_don='.urlencode($xd_loc_ma_hoa_don);
	if($xd_loc_sort !== '') $q .= '&sort='.urlencode($xd_loc_sort);
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
			<div class="form-group mb-0 mr-2">
				<label class="mr-1">Giáo viên</label>
				<input class="form-control form-control-sm text-sm" type="search" name="gv_keyword" value="<?=htmlspecialchars($xd_loc_gv_keyword)?>" placeholder="Tên giáo viên">
			</div>
			<div class="form-group mb-0 mr-2">
				<label class="mr-1">Số HĐ</label>
				<input class="form-control form-control-sm text-sm" type="search" name="ma_hoa_don" value="<?=htmlspecialchars($xd_loc_ma_hoa_don)?>" placeholder="Nhập số hóa đơn">
			</div>
			<div class="form-group mb-0 mr-2">
				<label class="mr-1">Sắp xếp</label>
				<select class="form-control form-control-sm text-sm" name="sort">
					<option value="teacher_asc" <?=$xd_loc_sort==='teacher_asc'?'selected':''?>>Tên GV A–Z</option>
					<option value="invoice_desc" <?=$xd_loc_sort==='invoice_desc'?'selected':''?>>Số HĐ nhiều nhất</option>
					<option value="invoice_asc" <?=$xd_loc_sort==='invoice_asc'?'selected':''?>>Số HĐ ít nhất</option>
					<option value="date_desc" <?=$xd_loc_sort==='date_desc'?'selected':''?>>Ngày HĐ mới nhất</option>
					<option value="date_asc" <?=$xd_loc_sort==='date_asc'?'selected':''?>>Ngày HĐ cũ nhất</option>
				</select>
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
				<?php if(xd_can_duyet() || xd_can_kiem_tra()) { $exportTongHop = 'index.php?com=xangdau&act=xuatTongHopGiaoVien&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
				<a class="btn btn-secondary" href="<?=$exportTongHop?>" title="Xuất file tổng hợp thanh toán theo giáo viên"><i class="fas fa-file-invoice-dollar mr-1"></i>Xuất tổng hợp GV</a>
				<?php } ?>
			</div>
			<?php } ?>
			<?php if(xd_can_kiem_tra() || xd_can_duyet()) { ?>
			<div class="btn-group btn-group-sm" role="group" aria-label="Danh sách theo trạng thái">
				<?php if(xd_can_kiem_tra()) { $checkList = 'index.php?com=xangdau&act=locKiemTra'; ?><a class="btn btn-outline-warning" href="<?=$checkList?>" title="Danh sách chưa kiểm tra"><i class="fas fa-search mr-1"></i>Chưa kiểm tra</a><?php } ?>
				<?php if(xd_can_kiem_tra() || xd_can_duyet()) { $approveList = 'index.php?com=xangdau&act=locDuyet'; ?><a class="btn btn-outline-primary" href="<?=$approveList?>" title="Danh sách đã kiểm tra, chờ duyệt"><i class="fas fa-check-circle mr-1"></i>Chờ duyệt</a><?php } ?>
				<?php $paidList = 'index.php?com=xangdau&act=locDaThanhToan'; ?><a class="btn btn-outline-secondary" href="<?=$paidList?>" title="Danh sách đã thanh toán"><i class="fas fa-check-double mr-1"></i>Đã thanh toán</a>
			</div>
			<?php } ?>
			<?php if(xd_can_duyet() && !empty($xd_loc_summary)) { $approveAll = xd_loc_params_url('', 'duyetTatCaGiaoVien'); ?>
			<a class="btn btn-sm btn-success" href="<?=$approveAll?>" onclick="return confirm('Duyệt toàn bộ giáo viên đã được kế toán kiểm tra?');"><i class="fas fa-stamp mr-1"></i>Duyệt tất cả</a>
			<?php } ?>
		</div>
	</div>

	<div class="card card-info card-outline text-sm mb-3">
		<div class="card-header"><h3 class="card-title">Tổng hợp theo giáo viên</h3></div>
		<?php if((xd_can_kiem_tra() || xd_can_duyet()) && !empty($xd_loc_summary)): ?>
		<div class="card-body py-2 border-bottom">
			<form id="xd-loc-bulk-form" method="post" action="index.php?com=xangdau&act=locBulkAction" class="form-inline" style="gap:.5rem;" onsubmit="var f=this,m=document.getElementById('xd-loc-bulk-msg'),e='';if(!f.elements['operation'].value)e='Vui lòng chọn thao tác cần thực hiện.';else if(!document.querySelector('.xd-bulk-item[form=xd-loc-bulk-form]:checked'))e='Vui lòng tích chọn ít nhất một giáo viên trong danh sách.';if(e){m.textContent=e;m.style.display='block';m.scrollIntoView({block:'nearest'});return false;}m.style.display='none';return f.elements['operation'].value !== 'approve' || confirm('Duyệt thanh toán cho các giáo viên đã chọn?');">
				<input type="hidden" name="csrf" value="<?=htmlspecialchars(xd_loc_bulk_csrf_token(), ENT_QUOTES, 'UTF-8')?>">
				<input type="hidden" name="ret" value="<?=htmlspecialchars(rawurlencode(xd_loc_params_url('', 'loc')), ENT_QUOTES, 'UTF-8')?>">
				<?php foreach(array('ky'=>$xd_loc_ky,'from_date'=>$xd_loc_from,'to_date'=>$xd_loc_to,'gv_keyword'=>$xd_loc_gv_keyword,'ma_hoa_don'=>$xd_loc_ma_hoa_don,'sort'=>$xd_loc_sort) as $filter=>$value): ?><input type="hidden" name="<?=$filter?>" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8')?>"><?php endforeach; ?>
				<select class="form-control form-control-sm" name="operation">
					<option value="">Thao tác đã chọn…</option>
					<?php if(xd_can_kiem_tra()): ?><option value="check">Đánh dấu đã kiểm tra</option><option value="uncheck">Bỏ kiểm tra</option><?php endif; ?>
					<?php if(xd_can_duyet()): ?><option value="approve">Duyệt thanh toán</option><?php endif; ?>
				</select>
				<button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-play mr-1"></i>Thực hiện</button>
				<span class="small text-muted">Chọn một hoặc nhiều giáo viên trong danh sách.</span>
				<div id="xd-loc-bulk-msg" class="alert alert-danger py-1 px-2 mb-0 w-100" style="display:none" role="alert"></div>
			</form>
		</div>
		<?php endif; ?>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<?php if(xd_can_kiem_tra() || xd_can_duyet()): ?><th class="text-center" style="width:34px"><input type="checkbox" class="xd-bulk-all" data-form="xd-loc-bulk-form" title="Chọn tất cả giáo viên"></th><?php endif; ?>
						<th>STT</th>
						<th>Giáo viên</th>
						<th class="text-center">Số HĐ</th>
						<th>HĐ gần nhất</th>
						<th class="text-right">Tổng HĐ (S_HĐ)</th>
						<th class="text-center">Đã thanh toán</th>
						<th class="text-center">Chưa thanh toán</th>
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
						<?php if(xd_can_kiem_tra() || xd_can_duyet()): ?><td class="text-center"><input type="checkbox" class="xd-bulk-item" form="xd-loc-bulk-form" name="gv_keys[]" value="<?=htmlspecialchars($g['gv_key'], ENT_QUOTES, 'UTF-8')?>"></td><?php endif; ?>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($g['gv_hoten'] !== '' ? $g['gv_hoten'] : $g['gv_key'])?></td>
						<td class="text-center"><?=(int)$g['so_hd']?></td>
						<td><?=!empty($g['ngay_hd_gan_nhat']) ? date('d/m/Y', strtotime($g['ngay_hd_gan_nhat'])) : '—'?></td>
						<td class="text-right"><?=number_format((float)$g['s_hd'], 0, ',', '.')?></td>
						<td class="text-center"><strong><?=(int)$g['so_hv_da_thanh_toan']?></strong></td>
						<td class="text-center"><?=(int)$g['so_hv_chua_thanh_toan']?></td>
						<td class="text-right"><?=number_format((float)$g['dinh_muc_toi_da'], 0, ',', '.')?></td>
						<td class="text-right"><span class="badge <?=abs((float)$g['chenh_lech']) <= 500000 ? 'badge-success' : 'badge-danger'?>"><?=number_format((float)$g['chenh_lech'], 0, ',', '.')?></span></td>
						<td class="text-right"><?=number_format((float)$g['tong_chi'], 0, ',', '.')?></td>
						<td>
							<?php if((int)$g['ke_toan_kiem_tra'] === 1) { ?><span class="badge badge-success">Đã kiểm tra</span><?php } else { ?><span class="badge badge-warning">Chưa kiểm tra</span><?php } ?>
						</td>
						<td class="text-right">
							<?php $teacherView = xd_loc_params_url($g['gv_key'], 'xemGiaoVien'); $teacherCheck = xd_loc_params_url($g['gv_key'], 'kiemTraGiaoVien'); $teacherUncheck = xd_loc_params_url($g['gv_key'], 'huyKiemTraGiaoVien'); $teacherApprove = xd_loc_params_url($g['gv_key'], 'duyetGiaoVien'); ?>
							<a class="btn btn-xs btn-info" href="<?=$teacherView?>" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
							<?php if(xd_can_kiem_tra() && (int)$g['ke_toan_kiem_tra'] === 0) { ?><a class="btn btn-xs btn-warning" href="<?=$teacherCheck?>" title="Kế toán xác nhận đã kiểm tra" onclick="return confirm('Xác nhận đã kiểm tra giáo viên này?');"><i class="fas fa-check"></i></a><?php } ?>
							<?php if(xd_can_kiem_tra() && (int)$g['ke_toan_kiem_tra'] === 1) { ?><a class="btn btn-xs btn-secondary" href="<?=$teacherUncheck?>" title="Chuyển về chưa kiểm tra" onclick="return confirm('Chuyển giáo viên này về trạng thái chưa kiểm tra?');"><i class="fas fa-undo"></i></a><?php } ?>
							<?php if(xd_can_duyet()) { ?><a class="btn btn-xs btn-primary" href="<?=$teacherApprove?>" title="Quản lý duyệt giáo viên" onclick="return confirm('Duyệt thanh toán cho giáo viên này?');"><i class="fas fa-stamp"></i></a><?php } ?>
							<?php $teacherExport = 'index.php?com=xangdau&act=xuatBangKeGiaoVien&gv_key='.urlencode($g['gv_key']).'&ky='.urlencode($xd_loc_ky).'&from_date='.urlencode($xd_loc_from).'&to_date='.urlencode($xd_loc_to); ?>
							<?php if((int)$g['ke_toan_kiem_tra'] === 1) { ?><a class="btn btn-xs btn-success" href="<?=$teacherExport?>" title="Xuất Excel bảng kê giáo viên này"><i class="fas fa-file-excel"></i></a><?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="<?= (xd_can_kiem_tra() || xd_can_duyet()) ? 13 : 12 ?>" class="text-center text-muted">Chưa có dữ liệu. Chọn kỳ/khoảng ngày rồi bấm "Chạy lọc".</td></tr>
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
						<th>Tên khóa</th>
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
						<td><?=htmlspecialchars($hv['khoa_ten'] ?? $hv['khoa'] ?? '—')?></td>
						<td><span class="badge <?=$badge?>"><?=htmlspecialchars($hv['nhom'])?></span></td>
						<td><?=htmlspecialchars($hv['gv_hoten'] !== '' ? $hv['gv_hoten'] : $hv['gv_key'])?></td>
						<td class="text-right"><?=number_format((float)$hv['dinh_muc'], 0, ',', '.')?></td>
						<td class="text-right"><?=number_format((float)$hv['so_tien_thanh_toan'], 0, ',', '.')?></td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="8" class="text-center text-muted">Không có học viên nào đủ điều kiện.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
<script src="assets/js/xangdau-bulk.js?v=<?=@filemtime(__DIR__.'/../../../assets/js/xangdau-bulk.js')?>"></script>
