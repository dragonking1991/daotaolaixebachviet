<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=kythi">Ngày kiểm tra</a></li>
			<li class="breadcrumb-item active">Tra cứu kết quả</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong>Tra cứu kết quả kỳ kiểm tra</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3></div>
		<div class="card-body">
			<form method="get" class="form-inline mb-3">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="kythiTracuu">
				<input type="date" name="ngay" class="form-control form-control-sm mr-2" value="<?=htmlspecialchars($tc_filters['ngay'])?>" title="Ngày kiểm tra">
				<input type="text" name="cccd" class="form-control form-control-sm mr-2" placeholder="CCCD" value="<?=htmlspecialchars($tc_filters['cccd'])?>">
				<input type="text" name="hoten" class="form-control form-control-sm mr-2" placeholder="Họ tên" value="<?=htmlspecialchars($tc_filters['hoten'])?>">
				<input type="text" name="ma_khoa" class="form-control form-control-sm mr-2" placeholder="Mã khóa" value="<?=htmlspecialchars($tc_filters['ma_khoa'])?>">
				<button class="btn btn-sm bg-gradient-primary">Tra cứu</button>
			</form>
			<div class="table-responsive">
			<table class="table dt-list table-hover table-bordered mb-0" style="white-space:nowrap;">
				<thead><tr><th>Ngày KT</th><th>Họ tên</th><th>CCCD</th><th>Hạng</th><th>Mã khóa</th><th>Điểm LT</th><th>LT</th><th>Điểm MP</th><th>MP</th><th>Điểm Hình</th><th>Hình</th><th>Điểm Đường</th><th>Đường</th><th>Chữ chi</th><th>Kết luận</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['ngay_kt'] ?: '—')?></td>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars($it['ma_khoa'])?></td>
						<td><?=htmlspecialchars($it['diem_lt'])?></td>
						<td><?=htmlspecialchars($it['lt'])?></td>
						<td><?=htmlspecialchars($it['diem_mp'])?></td>
						<td><?=htmlspecialchars($it['mp'])?></td>
						<td><?=htmlspecialchars($it['diem_hinh'])?></td>
						<td><?=htmlspecialchars($it['hinh'])?></td>
						<td><?=htmlspecialchars($it['diem_duong'])?></td>
						<td><?=htmlspecialchars($it['duong'])?></td>
						<td><?=htmlspecialchars($it['chu_chi'])?></td>
						<td><?php if($it['ket_luan']!==''): ?><span class="badge badge-<?=($it['ket_luan']==='Đạt')?'success':'danger'?>"><?=htmlspecialchars($it['ket_luan'])?></span><?php else: ?>—<?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="15" class="text-center text-muted p-3">Nhập điều kiện để tra cứu kết quả kỳ kiểm tra.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</section>
