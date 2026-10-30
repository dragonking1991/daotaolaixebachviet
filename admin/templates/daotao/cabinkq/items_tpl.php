<?php if(!defined('SOURCES')) die("Error");
	$dt_ret = ''; $__q = array();
	foreach(array('id_khoa','keyword','trang_thai','gv','p') as $__k) if(isset($_GET[$__k]) && $_GET[$__k] !== '') $__q[$__k] = $_GET[$__k];
	if($__q) $dt_ret = '&'.http_build_query($__q);
	$__inc = isset($_GET['ret']) ? rawurldecode((string)$_GET['ret']) : '';
	$cb_ret = (strpos($__inc, 'index.php?com=daotao') === 0) ? rawurlencode($__inc) : rawurlencode('index.php?com=daotao&act=cabinkq'.$dt_ret);
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Cabin (kết quả)</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Kết quả cabin</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><?php if(dt_can('cabinkq','edit')): ?><button type="submit" form="dt-module-cabinkq" onclick="return confirm('Đánh dấu Đạt cabin cho học viên đã chọn?')" class="btn btn-sm bg-gradient-success"><i class="fas fa-check mr-1"></i>Đạt mục đã chọn</button> <a href="index.php?com=daotao&act=crudEdit&entity=cabinkq" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <?php endif; ?><?php if(dt_can('cabinkq','import')): ?><a href="index.php?com=daotao&act=uploadCabin<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import cabin</a> <?php endif; ?><?php if(dt_can('cabinkq','export')): ?>			<a href="<?=dt_export_link('exportCabin', array('id_khoa','keyword','trang_thai','gv'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <?php endif; ?><?php if(dt_can('cabinkq','delete')): ?><button type="submit" form="dt-module-cabinkq" formaction="index.php?com=daotao&act=moduleDeleteSelected" onclick="return confirm('Xóa kết quả cabin của các học viên đã chọn?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button><?php endif; ?></div>
		</div>
		<div class="card-body">
			<?php if(dt_can('cabinkq','edit') || dt_can('cabinkq','delete')): ?><form id="dt-module-cabinkq" method="post" action="index.php?com=daotao&act=modulePassSelected"><input type="hidden" name="entity" value="cabinkq"><input type="hidden" name="id_khoa" value="<?=(int)$id_khoa_sel?>"><input type="hidden" name="ret" value="<?=$cb_ret?>"></form><?php endif; ?>
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="cabinkq">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Tất cả khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars(course_option_label($k))?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<input type="text" name="gv" class="form-control form-control-sm mr-2" placeholder="Giáo viên" value="<?=isset($_REQUEST['gv'])?htmlspecialchars($_REQUEST['gv']):''?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2">
					<option value="">— Trạng thái —</option>
					<option value="dat" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='dat')?'selected':''?>>Đạt</option>
					<option value="chua" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='chua')?'selected':''?>>Chưa đạt</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<div class="dt-stats mb-2"><div class="dt-stat is-green"><div class="dt-stat-label">Cabin đạt</div><div class="dt-stat-num"><?=(int)$module_stats['dat']?></div></div><div class="dt-stat is-red"><div class="dt-stat-label">Cabin chưa đạt / chưa nhập</div><div class="dt-stat-num"><?=(int)$module_stats['chua']?></div></div></div>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th class="text-center" style="width:34px"><?php if(dt_can('cabinkq','edit') || dt_can('cabinkq','delete')): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-module-cabinkq" title="Chọn tất cả"><?php endif; ?></th><th>Học viên</th><th>Kết quả cabin</th><th class="text-center">Trạng thái</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td class="text-center"><?php if(dt_can('cabinkq','edit') || dt_can('cabinkq','delete')): ?><input type="checkbox" class="dt-bulk-item" form="dt-module-cabinkq" name="ids[]" value="<?=(int)$it['hv_id']?>"><?php endif; ?></td>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['hoten'] ?: $it['roster_ma_hv'])?></div>
							<div class="dt-sub"><i class="fas fa-id-badge"></i><?=htmlspecialchars($it['roster_ma_hv'] ?: '—')?><span class="dt-sep">•</span><?=htmlspecialchars($it['hv_cccd'] ?: '—')?></div>
							<div class="dt-sub"><i class="fas fa-graduation-cap"></i><?=htmlspecialchars($it['ma_khoa'] ?: '—')?><?=!empty($it['ten_khoa']) ? ' — '.htmlspecialchars($it['ten_khoa']) : ''?><span class="dt-sep">•</span><span class="dt-tag is-muted"><?=htmlspecialchars($it['hang'] ?: '—')?></span></div>
							<div class="dt-sub"><i class="fas fa-chalkboard-teacher"></i><?=htmlspecialchars(!empty($it['gv_hoten']) ? $it['gv_hoten'] : '—')?></div>
						</td>
						<td>
							<?php if(!empty($it['id'])): ?><div class="dt-sub">Tổng thời gian: <strong><?=htmlspecialchars($it['tong_thoigian'] ?: '—')?></strong> <span class="dt-sep">•</span> Số nội dung: <strong><?=(int)$it['so_noidung']?></strong></div>
							<?php if(!empty($it['ghi_chu'])): ?><div class="dt-sub"><i class="fas fa-comment-dots"></i><?=htmlspecialchars($it['ghi_chu'])?></div><?php endif; ?>
							<?php else: ?><span class="text-muted">Chưa có dữ liệu cabin</span><?php endif; ?>
						</td>
						<td class="text-center"><?php if(empty($it['id'])): ?><span class="badge badge-secondary">Chưa nhập</span><?php elseif((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-danger">Không đạt</span><?php endif; ?></td>
						<td class="dt-actions"><?php if(!empty($it['id'])): ?><a href="index.php?com=daotao&act=crudEdit&entity=cabinkq&id=<?=$it['id']?>&ret=<?=$cb_ret?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=cabinkq&id=<?=$it['id']?>&ret=<?=$cb_ret?>" onclick="return confirm('Xóa kết quả cabin này?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a><?php else: ?><a href="index.php?com=daotao&act=crudEdit&entity=cabinkq&id_khoa=<?=(int)$id_khoa_sel?>&ret=<?=$cb_ret?>" class="btn btn-xs bg-gradient-primary" title="Thêm kết quả cabin"><i class="fas fa-plus"></i></a><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="text-center text-muted p-3">Chưa có kết quả cabin</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
