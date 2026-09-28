<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li><li class="breadcrumb-item"><a href="index.php?com=daotao&act=<?=$dt_crud_config['back']?>"><?=$dt_crud_config['title']?></a></li><li class="breadcrumb-item active"><?=$item ? 'Sửa' : 'Thêm'?></li></ol></div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong><?=$item ? 'Sửa' : 'Thêm'?> <?=$dt_crud_config['title']?></strong></h3></div>
		<form method="post" action="index.php?com=daotao&act=crudSave">
			<input type="hidden" name="entity" value="<?=$dt_crud_entity?>"><input type="hidden" name="id" value="<?=isset($item['id'])?(int)$item['id']:0?>">
			<div class="card-body"><div class="row">
			<?php foreach($dt_crud_config['fields'] as $field => $label): $value=isset($item[$field])?$item[$field]:''; ?>
				<div class="col-md-6 form-group"><label><?=$label?></label>
				<?php if($field === 'id_khoa'): ?><select name="data[<?=$field?>]" class="form-control form-control-sm"><option value="0">— Chọn khóa —</option><?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$value==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?></select>
				<?php elseif($field === 'hang' || $field === 'hang_xe'): ?><select name="data[<?=$field?>]" class="form-control form-control-sm"><option value="">— Chọn hạng —</option><?php foreach(array('B11','B1','C1','C','CE','D','E','FC') as $h): ?><option value="<?=$h?>" <?=$value==$h?'selected':''?>><?=$h?></option><?php endforeach; ?></select>
				<?php elseif($field === 'ghi_chu' || $field === 'dia_chi'): ?><textarea name="data[<?=$field?>]" class="form-control form-control-sm" rows="2"><?=htmlspecialchars($value)?></textarea>
				<?php else: ?><input type="text" name="data[<?=$field?>]" class="form-control form-control-sm" value="<?=htmlspecialchars($value)?>">
				<?php endif; ?></div>
			<?php endforeach; ?>
			</div></div>
			<div class="card-footer"><button class="btn btn-sm bg-gradient-primary"><i class="fas fa-save mr-1"></i>Lưu</button> <a href="index.php?com=daotao&act=<?=$dt_crud_config['back']?>" class="btn btn-sm btn-secondary">Hủy</a></div>
		</form>
	</div>
</section>
