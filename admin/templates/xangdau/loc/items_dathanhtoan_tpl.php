<?php
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

<section class="content">
	<div class="card card-secondary card-outline text-sm">
		<div class="card-header"><h3 class="card-title">Giáo viên đã quyết toán / đã thanh toán</h3></div>
		<div class="card-body border-bottom">
			<form method="get" action="index.php" class="form-inline">
				<input type="hidden" name="com" value="xangdau">
				<input type="hidden" name="act" value="locDaThanhToan">
				<label class="mr-2 mb-0">Ngày thanh toán từ</label>
				<input class="form-control form-control-sm mr-2" type="date" name="paid_from" value="<?=htmlspecialchars($xd_loc_paid_from ?? '')?>">
				<label class="mr-2 mb-0">đến</label>
				<input class="form-control form-control-sm mr-2" type="date" name="paid_to" value="<?=htmlspecialchars($xd_loc_paid_to ?? '')?>">
				<button class="btn btn-sm btn-primary mr-2" type="submit"><i class="fas fa-search mr-1"></i>Lọc</button>
				<a class="btn btn-sm btn-secondary" href="index.php?com=xangdau&act=locDaThanhToan">Bỏ lọc</a>
			</form>
		</div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Giáo viên</th>
						<th class="text-center">Số HĐ đã quyết toán</th>
						<th class="text-right">Tổng tiền</th>
						<th>Ngày thanh toán</th>
						<th>Thao tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($xd_loc_dathanhtoan_data)) { $i = 0; foreach($xd_loc_dathanhtoan_data as $gv) { $i++; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($gv['gv_hoten'] !== '' ? $gv['gv_hoten'] : $gv['gv_key'])?></td>
						<td class="text-center"><?=(int)$gv['so_hd']?></td>
						<td class="text-right"><?=number_format((float)$gv['tong_tien'], 0, ',', '.')?></td>
						<td><?=(!empty($gv['ngay_thanh_toan']) ? date('d/m/Y', strtotime($gv['ngay_thanh_toan'])) : '-')?><?=(!empty($gv['ngay_thanh_toan_den']) && $gv['ngay_thanh_toan_den'] !== $gv['ngay_thanh_toan']) ? ' - '.date('d/m/Y', strtotime($gv['ngay_thanh_toan_den'])) : ''?></td>
						<td>
							<a class="btn btn-sm btn-info" href="index.php?com=xangdau&act=hoadon&keyword=<?=urlencode($gv['gv_hoten'])?>"><i class="fas fa-eye mr-1"></i>Xem hóa đơn</a>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="6" class="text-center text-muted">Chưa có giáo viên nào đã thanh toán.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
