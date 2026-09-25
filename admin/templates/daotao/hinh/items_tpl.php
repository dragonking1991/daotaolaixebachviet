<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Thực hành trong hình</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong>Thực hành trong hình (nhập tay)</strong></h3></div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="hinh">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
			</form>
			<?php if(!$id_khoa_sel): ?>
			<div class="alert alert-info mb-0">Chọn khóa để nhập thực hành trong hình.</div>
			<?php else: ?>
			<table class="table table-bordered table-sm table-hover mb-0">
				<thead><tr><th>Họ và tên</th><th>CCCD</th><th>Hạng</th><th class="text-center">Kết quả</th><th style="width:340px">Nhập thời gian (giờ) / quãng đường (km)</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td class="text-center"><?php if((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-secondary">Chưa</span><?php endif; ?></td>
						<td>
							<form method="post" action="index.php?com=daotao&act=saveHinh&id_khoa=<?=$id_khoa_sel?>" style="display:flex;gap:6px;align-items:center;margin:0;">
								<input type="hidden" name="cccd" value="<?=htmlspecialchars($it['cccd'])?>">
								<input type="number" step="0.01" name="gio" class="form-control form-control-sm" style="width:90px" placeholder="giờ" value="<?=$it['gio']!==null?htmlspecialchars($it['gio']):''?>">
								<input type="number" step="0.01" name="km" class="form-control form-control-sm" style="width:90px" placeholder="km" value="<?=$it['km']!==null?htmlspecialchars($it['km']):''?>">
								<button class="btn btn-xs bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu</button>
							</form>
						</td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="text-center text-muted p-3">Chưa có học viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>
</section>
