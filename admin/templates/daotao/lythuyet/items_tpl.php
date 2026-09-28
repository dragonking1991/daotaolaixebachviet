<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Lý thuyết</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Tổng hợp lý thuyết (6 môn)</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=lythuyet" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm kết quả</a> <a href="index.php?com=daotao&act=uploadLythuyet<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import môn</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=lythuyet" onclick="return confirm('Xóa toàn bộ kết quả lý thuyết?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="lythuyet">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
			</form>
			<?php if(!$id_khoa_sel): ?>
			<div class="alert alert-info mb-0">Chọn khóa để xem tổng hợp lý thuyết.</div>
			<?php else: ?>
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Họ và tên</th><th>CCCD</th><?php foreach($ds_mon as $lbl): ?><th class="text-center" style="font-size:11px"><?=htmlspecialchars($lbl)?></th><?php endforeach; ?><th class="text-center">Lý thuyết</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $hv=$it['hv']; $soDat=0; ?>
					<tr>
						<td><?=htmlspecialchars($hv['hoten'])?></td>
						<td><?=htmlspecialchars($hv['cccd'])?></td>
						<?php foreach($ds_mon as $mk=>$lbl): $m=isset($it['mon'][$mk])?$it['mon'][$mk]:null; if($m && (int)$m['dat']===1) $soDat++; ?>
						<td class="text-center">
							<?php if(!$m): ?><span class="text-muted">—</span>
							<?php else: ?><a href="index.php?com=daotao&act=crudEdit&entity=lythuyet&id=<?=$m['id']?>" title="Sửa kết quả"><span class="badge badge-<?=$m['dat']?'success':'danger'?>" title="TĐ <?=$m['tien_do']?> / Điểm <?=$m['diem_kt']?>"><?=$m['dat']?'Đạt':'Chưa'?></span></a> <a href="index.php?com=daotao&act=crudDelete&entity=lythuyet&id=<?=$m['id']?>" onclick="return confirm('Xóa kết quả môn này?')" class="text-danger"><i class="fas fa-times"></i></a><?php endif; ?>
						</td>
						<?php endforeach; ?>
						<td class="text-center"><?php if($soDat>=count($ds_mon)): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-secondary"><?=$soDat?>/<?=count($ds_mon)?></span><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="<?=count($ds_mon)+3?>" class="text-center text-muted p-3">Chưa có học viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
