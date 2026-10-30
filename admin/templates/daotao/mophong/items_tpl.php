<?php if(!defined('SOURCES')) die("Error"); $status = dt_req('trang_thai'); $mp_ret_q = array('com'=>'daotao','act'=>'mophong'); foreach(array('id_khoa','keyword','trang_thai','p') as $__k) if(isset($_GET[$__k]) && $_GET[$__k] !== '') $mp_ret_q[$__k] = $_GET[$__k]; $mp_ret = rawurlencode('index.php?'.http_build_query($mp_ret_q)); ?>
<section class="content-header text-sm">
	<div class="container-fluid"><ol class="breadcrumb float-sm-left"><li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li><li class="breadcrumb-item active">Mô phỏng</li></ol></div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Kết quả mô phỏng</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><?php if(dt_can('lythuyet','edit')): ?><button type="submit" form="dt-module-mophong" onclick="return confirm('Đánh dấu Đạt mô phỏng cho học viên đã chọn?')" class="btn btn-sm bg-gradient-success"><i class="fas fa-check mr-1"></i>Đạt mục đã chọn</button> <?php endif; ?><?php if(dt_can('lythuyet','delete')): ?><button type="submit" form="dt-module-mophong" formaction="index.php?com=daotao&act=moduleDeleteSelected" onclick="return confirm('Xóa kết quả mô phỏng của các học viên đã chọn?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button> <?php endif; ?><a href="index.php?com=daotao&act=uploadMophong<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</a> <a href="<?=dt_export_link('exportMophong', array('id_khoa','keyword','trang_thai'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a></div>
		</div>
		<div class="card-body">
			<?php if(dt_can('lythuyet','edit') || dt_can('lythuyet','delete')): ?><form id="dt-module-mophong" method="post" action="index.php?com=daotao&act=modulePassSelected"><input type="hidden" name="entity" value="mophong"><input type="hidden" name="id_khoa" value="<?=(int)$id_khoa_sel?>"><input type="hidden" name="ret" value="<?=$mp_ret?>"></form><?php endif; ?>
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="mophong">
				<select name="id_khoa" class="form-control form-control-sm mr-2 mb-1" onchange="this.form.submit()"><option value="0">— Chọn khóa —</option><?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars(course_option_label($k))?></option><?php endforeach; ?></select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2 mb-1" placeholder="Tên / CCCD / Mã HV" value="<?=htmlspecialchars(dt_req('keyword'))?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2 mb-1"><option value="">— Trạng thái —</option><option value="dat" <?=$status==='dat'?'selected':''?>>Đạt</option><option value="chua" <?=$status==='chua'?'selected':''?>>Chưa đạt / chưa nhập</option></select>
				<button class="btn btn-sm bg-gradient-primary mb-1">Lọc</button>
			</form>
			<div class="dt-stats mb-2"><div class="dt-stat is-green"><div class="dt-stat-label">Mô phỏng đạt</div><div class="dt-stat-num"><?=(int)$module_stats['dat']?></div></div><div class="dt-stat is-red"><div class="dt-stat-label">Mô phỏng chưa đạt</div><div class="dt-stat-num"><?=(int)$module_stats['chua']?></div></div></div>
			<?php if(!$id_khoa_sel && empty($items)): ?><div class="alert alert-info mb-0">Chọn khóa để xem kết quả mô phỏng, hoặc nhập CCCD/tên để tra cứu toàn bộ khóa.</div><?php else: ?>
			<div class="table-responsive"><table class="table dt-list table-hover mb-0">
				<thead><tr><th class="text-center" style="width:34px"><?php if(dt_can('lythuyet','edit') || dt_can('lythuyet','delete')): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-module-mophong" title="Chọn tất cả"><?php endif; ?></th><th>Học viên</th><th class="text-center">Tiến độ hoàn thành</th><th class="text-center">Kết quả</th></tr></thead>
				<tbody><?php if(!empty($items)): foreach($items as $it): ?>
				<tr><td class="text-center"><?php if(dt_can('lythuyet','edit') || dt_can('lythuyet','delete')): ?><input type="checkbox" class="dt-bulk-item" form="dt-module-mophong" name="ids[]" value="<?=(int)$it['hv_id']?>"><?php endif; ?></td><td><div class="dt-title"><?=htmlspecialchars($it['hoten'])?></div><div class="dt-sub"><?=htmlspecialchars($it['ma_hv'])?> <span class="dt-sep">•</span><?=htmlspecialchars($it['cccd'])?></div></td>
				<td class="text-center"><?=isset($it['tien_do'])?htmlspecialchars($it['tien_do']).'%':'—'?></td>
				<td class="text-center"><?php if(empty($it['id'])): ?><span class="badge badge-secondary">Chưa nhập</span><?php elseif((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-danger">Chưa đạt</span><?php endif; ?></td></tr>
				<?php endforeach; else: ?><tr><td colspan="4" class="text-center text-muted p-3">Không có học viên khớp bộ lọc.</td></tr><?php endif; ?></tbody>
			</table></div><?php endif; ?>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>