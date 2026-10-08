<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=sathach">Kết quả sát hạch</a></li>
			<li class="breadcrumb-item active">Tra cứu</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong>Tra cứu kết quả sát hạch</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3></div>
		<div class="card-body">
			<form method="get" class="form-inline mb-3">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="sathachTracuu">
				<input type="text" name="cccd" class="form-control form-control-sm mr-2" placeholder="CCCD" value="<?=htmlspecialchars($tc_filters['cccd'])?>">
				<input type="text" name="hoten" class="form-control form-control-sm mr-2" placeholder="Họ tên" value="<?=htmlspecialchars($tc_filters['hoten'])?>">
				<input type="text" name="so_gplx" class="form-control form-control-sm mr-2" placeholder="Số GPLX" value="<?=htmlspecialchars($tc_filters['so_gplx'])?>">
				<input type="text" name="ma_khoa" class="form-control form-control-sm mr-2" placeholder="Mã khóa" value="<?=htmlspecialchars($tc_filters['ma_khoa'])?>">
				<button class="btn btn-sm bg-gradient-primary">Tra cứu</button>
			</form>
			<div class="table-responsive">
			<table class="table dt-list table-hover table-bordered mb-0" style="white-space:nowrap;">
				<thead><tr><th>Họ tên</th><th>CCCD</th><th>Hạng</th><th>Mã khóa</th><th>Ngày SH</th><th>Số GPLX</th><th>Kết quả</th><th>Ghi chú</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars($it['ma_khoa'])?></td>
						<td><?=htmlspecialchars($it['ngay_sh'])?></td>
						<td><?=htmlspecialchars($it['so_gplx'])?></td>
						<td><?php if($it['ket_qua']!==''): ?><span class="badge badge-<?=($it['ket_qua']==='Đạt')?'success':'danger'?>"><?=htmlspecialchars($it['ket_qua'])?></span><?php else: ?>—<?php endif; ?></td>
						<td><?=htmlspecialchars($it['ghi_chu'])?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="8" class="text-center text-muted p-3">Nhập điều kiện để tra cứu kết quả sát hạch.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</section>
