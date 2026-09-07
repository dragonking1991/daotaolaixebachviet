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
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Giáo viên</th>
						<th class="text-center">Số HĐ đã quyết toán</th>
						<th class="text-right">Tổng tiền</th>
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
						<td>
							<a class="btn btn-sm btn-info" href="index.php?com=xangdau&act=hoadon&keyword=<?=urlencode($gv['gv_hoten'])?>"><i class="fas fa-eye mr-1"></i>Xem hóa đơn</a>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="5" class="text-center text-muted">Chưa có giáo viên nào đã thanh toán.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
