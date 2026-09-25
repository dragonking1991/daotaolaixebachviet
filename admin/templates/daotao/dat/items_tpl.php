<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">DAT (thực hành trên đường)</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Tổng hợp DAT</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=uploadDat<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import DAT</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="dat">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
			</form>
			<?php if(!$id_khoa_sel): ?>
			<div class="alert alert-info mb-0">Chọn khóa để xem tổng hợp DAT.</div>
			<?php else: ?>
			<div class="table-responsive">
			<table class="table table-bordered table-sm table-hover mb-0">
				<thead><tr><th>Họ và tên</th><th>CCCD</th><th>Hạng</th><th class="text-center">Phiên</th><th class="text-center">A (giờ)</th><th class="text-center">B đêm</th><th class="text-center">C tự động</th><th class="text-center">D số sàn</th><th class="text-center">E (km)</th><th class="text-center">Kết quả</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $hv=$it['hv']; $a=$it['agg']; ?>
					<tr>
						<td><?=htmlspecialchars($hv['hoten'])?></td>
						<td><?=htmlspecialchars($hv['cccd'])?></td>
						<td><?=htmlspecialchars($hv['hang'])?></td>
						<td class="text-center"><?=$it['so_phien']?></td>
						<td class="text-center"><?=$a['a']?></td>
						<td class="text-center"><?=$a['b']?></td>
						<td class="text-center"><?=$a['c']?></td>
						<td class="text-center"><?=$a['d']?></td>
						<td class="text-center"><?=$a['e']?></td>
						<td class="text-center"><?php if((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-danger" title="<?=htmlspecialchars(implode(', ', $it['thieu']))?>">Chưa</span><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="10" class="text-center text-muted p-3">Chưa có dữ liệu</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
