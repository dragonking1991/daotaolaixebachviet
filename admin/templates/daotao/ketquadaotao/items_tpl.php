<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Thông tin kết quả đào tạo</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Thông tin kết quả đào tạo</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><?php if(dt_can('ketqua','export')): ?><a href="<?=dt_export_link('exportKetqua', array('id_khoa','cccd','hoten','ma_khoa'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a><?php endif; ?></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-3">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="ketquadaotao">
				<select name="id_khoa" class="form-control form-control-sm mr-2">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$filters['id_khoa']==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
				<input type="text" name="cccd" class="form-control form-control-sm mr-2" placeholder="CCCD" value="<?=htmlspecialchars($filters['cccd'])?>">
				<input type="text" name="hoten" class="form-control form-control-sm mr-2" placeholder="Họ tên" value="<?=htmlspecialchars($filters['hoten'])?>">
				<input type="text" name="ma_khoa" class="form-control form-control-sm mr-2" placeholder="Mã khóa" value="<?=htmlspecialchars($filters['ma_khoa'])?>">
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<div class="table-responsive">
			<table class="table dt-list table-hover table-bordered mb-0" style="white-space:nowrap;">
				<thead>
					<tr>
						<th rowspan="2">Học viên</th><th rowspan="2">Hạng</th><th rowspan="2">Khóa</th><th rowspan="2">Giáo viên</th>
						<th colspan="2" class="text-center">Thực hành hình</th><th colspan="2" class="text-center">DAT</th>
						<th colspan="5" class="text-center">Kết quả kiểm tra</th>
					</tr>
					<tr>
						<th>Giờ</th><th>Km</th><th>Giờ</th><th>Km</th>
						<th>LT</th><th>Hình</th><th>Đường</th><th>Chữ chi</th><th>Kết luận</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $hv=$it['hv']; $s=$it['sum']; $ex=$it['exam']; $agg=$s['dat']['agg']; ?>
					<tr>
						<td><div class="dt-title"><?=htmlspecialchars($hv['hoten'])?></div><div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($hv['cccd'])?></div></td>
						<td><?=htmlspecialchars($hv['hang'])?></td>
						<td><?=htmlspecialchars(isset($hv['ma_khoa'])?$hv['ma_khoa']:'')?></td>
						<td><?=htmlspecialchars(isset($hv['gv_hoten'])?$hv['gv_hoten']:'—')?></td>
						<td><?=htmlspecialchars($s['hinh']['gio'])?></td>
						<td><?=htmlspecialchars($s['hinh']['km'])?></td>
						<td><?=htmlspecialchars($agg['a'])?></td>
						<td><?=htmlspecialchars($agg['e'])?></td>
						<td><?=$ex?htmlspecialchars($ex['diem_lt'].' / '.$ex['lt']):'—'?></td>
						<td><?=$ex?htmlspecialchars($ex['diem_hinh'].' / '.$ex['hinh']):'—'?></td>
						<td><?=$ex?htmlspecialchars($ex['diem_duong'].' / '.$ex['duong']):'—'?></td>
						<td><?=$ex?htmlspecialchars($ex['chu_chi']):'—'?></td>
						<td><?php if($ex && $ex['ket_luan']!==''): ?><span class="badge badge-<?=($ex['ket_luan']==='Đạt')?'success':'danger'?>"><?=htmlspecialchars($ex['ket_luan'])?></span><?php else: ?>—<?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="13" class="text-center text-muted p-3">Chọn khóa hoặc nhập điều kiện để xem kết quả đào tạo kết hợp kết quả kiểm tra.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</section>
