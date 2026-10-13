<?php if(!defined('SOURCES')) die("Error"); $status = dt_req('trang_thai'); ?>
<section class="content-header text-sm">
	<div class="container-fluid"><ol class="breadcrumb float-sm-left"><li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li><li class="breadcrumb-item active">Mô phỏng</li></ol></div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Kết quả mô phỏng</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=uploadMophong<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</a> <a href="<?=dt_export_link('exportMophong', array('id_khoa','keyword','trang_thai'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <?php if(dt_is_admin()): ?><a href="index.php?com=daotao&act=crudDeleteAll&entity=mophong" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa kết quả</a><?php endif; ?></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="mophong">
				<select name="id_khoa" class="form-control form-control-sm mr-2 mb-1" onchange="this.form.submit()"><option value="0">— Chọn khóa —</option><?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'].' — '.(int)$k['so_hoc_vien'].' học viên')?></option><?php endforeach; ?></select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2 mb-1" placeholder="Tên / CCCD / Mã HV" value="<?=htmlspecialchars(dt_req('keyword'))?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2 mb-1"><option value="">— Trạng thái —</option><option value="dat" <?=$status==='dat'?'selected':''?>>Đạt</option><option value="chua" <?=$status==='chua'?'selected':''?>>Chưa đạt / chưa nhập</option></select>
				<button class="btn btn-sm bg-gradient-primary mb-1">Lọc</button>
			</form>
			<?php if(!$id_khoa_sel && empty($items)): ?><div class="alert alert-info mb-0">Chọn khóa để xem kết quả mô phỏng, hoặc nhập CCCD/tên để tra cứu toàn bộ khóa.</div><?php else: ?>
			<div class="table-responsive"><table class="table dt-list table-hover mb-0">
				<thead><tr><th>Học viên</th><th class="text-center">Tiến độ hoàn thành</th><th class="text-center">Kết quả</th></tr></thead>
				<tbody><?php if(!empty($items)): foreach($items as $it): ?>
				<tr><td><div class="dt-title"><?=htmlspecialchars($it['hoten'])?></div><div class="dt-sub"><?=htmlspecialchars($it['ma_hv'])?> <span class="dt-sep">•</span><?=htmlspecialchars($it['cccd'])?></div></td>
				<td class="text-center"><?=isset($it['tien_do'])?htmlspecialchars($it['tien_do']).'%':'—'?></td>
				<td class="text-center"><?php if(empty($it['id'])): ?><span class="badge badge-secondary">Chưa nhập</span><?php elseif((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-danger">Chưa đạt</span><?php endif; ?></td></tr>
				<?php endforeach; else: ?><tr><td colspan="3" class="text-center text-muted p-3">Không có học viên khớp bộ lọc.</td></tr><?php endif; ?></tbody>
			</table></div><?php endif; ?>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>