<?php
	$xdPaidReturnParams = array();
	foreach(array('keyword','paid_from','paid_to') as $key)
		if(isset($_REQUEST[$key]) && is_scalar($_REQUEST[$key]) && (string)$_REQUEST[$key] !== '') $xdPaidReturnParams[$key] = (string)$_REQUEST[$key];
	$xdPaidReturnUrl = rawurlencode('index.php?com=xangdau&act=locDaThanhToan'.($xdPaidReturnParams ? '&'.http_build_query($xdPaidReturnParams) : ''));
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Danh sách đã thanh toán</li>
			</ol>
		</div>
	</div>
</section>
<script src="assets/js/xangdau-bulk.js?v=<?=@filemtime(__DIR__.'/../../../assets/js/xangdau-bulk.js')?>"></script>

<section class="content">
	<div class="card card-secondary card-outline text-sm">
		<div class="card-header"><h3 class="card-title">Giáo viên đã quyết toán / đã thanh toán</h3></div>
		<div class="card-body border-bottom">
			<form method="get" action="index.php" class="form-inline flex-wrap" style="gap:.5rem;">
				<input type="hidden" name="com" value="xangdau">
				<input type="hidden" name="act" value="locDaThanhToan">
				<label class="mb-0">Tên GV</label>
				<input class="form-control form-control-sm" type="text" name="keyword" placeholder="Nhập tên giáo viên" value="<?=htmlspecialchars($xd_loc_paid_keyword ?? '')?>" style="min-width:220px;">
				<label class="mr-2 mb-0">Ngày thanh toán từ</label>
				<input class="form-control form-control-sm mr-2" type="date" name="paid_from" value="<?=htmlspecialchars($xd_loc_paid_from ?? '')?>">
				<label class="mr-2 mb-0">đến</label>
				<input class="form-control form-control-sm mr-2" type="date" name="paid_to" value="<?=htmlspecialchars($xd_loc_paid_to ?? '')?>">
				<button class="btn btn-sm btn-primary mr-2" type="submit"><i class="fas fa-search mr-1"></i>Lọc</button>
				<a class="btn btn-sm btn-secondary" href="index.php?com=xangdau&act=locDaThanhToan">Bỏ lọc</a>
			</form>
			<div class="small text-muted mt-2">Danh sách tách theo từng ngày thanh toán. Có thể chọn cùng một ngày ở hai ô lọc để xem riêng ngày đó; thao tác hủy duyệt chỉ áp dụng cho giáo viên và ngày trên dòng được chọn.</div>
		</div>
		<div class="card-body border-top border-bottom bg-light text-sm">
			<?php if(xd_can_duyet() || xd_can_kiem_tra()) {
				$exportUrl = 'index.php?com=xangdau&act=xuatTongHopDaDuyet&keyword='.urlencode($xd_loc_paid_keyword ?? '').'&paid_from='.urlencode($xd_loc_paid_from ?? '').'&paid_to='.urlencode($xd_loc_paid_to ?? '');
			?>
			<a class="btn btn-sm btn-success" href="<?=$exportUrl?>" title="Xuất tổng hợp giáo viên đã quyết toán"><i class="fas fa-file-excel mr-1"></i>Xuất tổng hợp</a>
			<?php } ?>
		</div>
		<?php if(xd_can_duyet() && !empty($xd_loc_dathanhtoan_data)) { ?>
		<div class="card-body py-2 border-bottom">
			<form id="xd-paid-bulk-form" method="post" action="index.php?com=xangdau&act=huyDuyetGiaoVienChon" class="form-inline" style="gap:.5rem;" onsubmit="var m=document.getElementById('xd-paid-bulk-msg');if(!document.querySelector('[form=xd-paid-bulk-form]:checked')){m.textContent='Vui lòng tích chọn ít nhất một dòng cần bỏ duyệt.';m.style.display='block';return false;}m.style.display='none';return confirm('Hủy duyệt đúng các giáo viên/ngày thanh toán đã chọn? Những ngày khác được giữ nguyên.');">
				<input type="hidden" name="ret" value="<?=$xdPaidReturnUrl?>">
				<button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-undo mr-1"></i>Bỏ duyệt các mục đã chọn</button>
				<span class="small text-muted">Mỗi dòng tương ứng một giáo viên và một ngày thanh toán.</span>
				<div id="xd-paid-bulk-msg" class="alert alert-danger py-1 px-2 mb-0 w-100" style="display:none" role="alert"></div>
			</form>
		</div>
		<?php } ?>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<?php if(xd_can_duyet()): ?><th class="text-center" style="width:34px"><input type="checkbox" class="xd-bulk-all" data-form="xd-paid-bulk-form" title="Chọn tất cả đợt thanh toán"></th><?php endif; ?>
						<th>STT</th>
						<th>Giáo viên</th>
						<th class="text-center">Số HĐ đã quyết toán</th>
						<th class="text-center">Số HV đã thanh toán</th>
						<th class="text-right">Tổng tiền</th>
						<th>Ngày thanh toán</th>
						<th>Thao tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($xd_loc_dathanhtoan_data)) { $i = 0; foreach($xd_loc_dathanhtoan_data as $gv) { $i++; ?>
					<tr>
						<?php if(xd_can_duyet()): ?><td class="text-center">
							<?php if((int)$gv['so_bangke'] > 0): ?>
							<input type="checkbox" class="xd-bulk-item" form="xd-paid-bulk-form" name="rows[<?=$i?>][selected]" value="1" aria-label="Chọn <?=htmlspecialchars($gv['gv_hoten'], ENT_QUOTES, 'UTF-8')?> ngày <?=htmlspecialchars($gv['ngay_thanh_toan'], ENT_QUOTES, 'UTF-8')?>">
							<input type="hidden" form="xd-paid-bulk-form" name="rows[<?=$i?>][gv_key]" value="<?=htmlspecialchars($gv['gv_key'], ENT_QUOTES, 'UTF-8')?>">
							<input type="hidden" form="xd-paid-bulk-form" name="rows[<?=$i?>][payment_date]" value="<?=htmlspecialchars($gv['ngay_thanh_toan'], ENT_QUOTES, 'UTF-8')?>">
							<?php endif; ?>
						</td><?php endif; ?>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($gv['gv_hoten'] !== '' ? $gv['gv_hoten'] : $gv['gv_key'])?></td>
						<td class="text-center"><?=(int)$gv['so_hd']?></td>
						<td class="text-center"><?=(int)$gv['so_hv']?></td>
						<td class="text-right"><?=number_format((float)$gv['tong_tien'], 0, ',', '.')?></td>
						<td><?=(!empty($gv['ngay_thanh_toan']) ? date('d/m/Y', strtotime($gv['ngay_thanh_toan'])) : '-')?></td>
						<td>
							<a class="btn btn-sm btn-info" href="index.php?com=xangdau&act=xemGiaoVien&paid=1&gv_key=<?=urlencode($gv['gv_key'])?>"><i class="fas fa-eye mr-1"></i>Xem bảng kê</a>
							<?php if(xd_can_duyet() || xd_can_kiem_tra()) { ?><a class="btn btn-sm btn-success ml-1" href="index.php?com=xangdau&act=xuatBangKeDaDuyetGiaoVien&gv_key=<?=urlencode($gv['gv_key'])?>"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a><?php } ?>
							<?php if(xd_can_duyet() && (int)$gv['so_bangke'] > 0) {
							?>
							<form method="post" action="index.php?com=xangdau&act=huyDuyetGiaoVien" class="d-inline ml-1" onsubmit="return confirm('Chỉ hủy duyệt cho giáo viên này trong ngày <?=htmlspecialchars(date('d/m/Y', strtotime($gv['ngay_thanh_toan'])))?>? Các ngày thanh toán khác sẽ được giữ nguyên.');">
								<input type="hidden" name="gv_key" value="<?=htmlspecialchars($gv['gv_key'], ENT_QUOTES, 'UTF-8')?>">
								<input type="hidden" name="payment_date" value="<?=htmlspecialchars($gv['ngay_thanh_toan'], ENT_QUOTES, 'UTF-8')?>">
								<input type="hidden" name="keyword" value="<?=htmlspecialchars($xd_loc_paid_keyword ?? '', ENT_QUOTES, 'UTF-8')?>">
								<input type="hidden" name="paid_from" value="<?=htmlspecialchars($xd_loc_paid_from ?? '', ENT_QUOTES, 'UTF-8')?>">
								<input type="hidden" name="paid_to" value="<?=htmlspecialchars($xd_loc_paid_to ?? '', ENT_QUOTES, 'UTF-8')?>">
								<button class="btn btn-sm btn-danger" type="submit" title="Chỉ hủy duyệt giao dịch của giáo viên này trong ngày thanh toán đang hiển thị"><i class="fas fa-undo mr-1"></i>Hủy ngày này</button>
							</form>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="<?=xd_can_duyet()?8:7?>" class="text-center text-muted">Chưa có giáo viên nào đã thanh toán.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
