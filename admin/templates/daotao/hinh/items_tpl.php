<?php if(!defined('SOURCES')) die("Error");
	$dt_ret = ''; $__q = array();
	foreach(array('id_khoa','keyword','trang_thai','gv','p') as $__k) if(isset($_GET[$__k]) && $_GET[$__k] !== '') $__q[$__k] = $_GET[$__k];
	if($__q) $dt_ret = '&'.http_build_query($__q);
	$__inc = isset($_GET['ret']) ? rawurldecode((string)$_GET['ret']) : '';
	$hinh_ret = (strpos($__inc, 'index.php?com=daotao') === 0) ? rawurlencode($__inc) : rawurlencode('index.php?com=daotao&act=hinh'.$dt_ret);
?>
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
		<div class="card-header"><h3 class="card-title"><strong>Thực hành trong hình (nhập tay)</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3><div class="card-tools"><?php if(dt_can('hinh','edit')): ?><button type="submit" form="dt-module-hinh" onclick="return confirm('Đánh dấu Đạt thực hành trong hình cho học viên đã chọn?')" class="btn btn-sm bg-gradient-success"><i class="fas fa-check mr-1"></i>Đạt mục đã chọn</button> <?php if($id_khoa_sel): ?><a href="index.php?com=daotao&act=hinhDatAll&id_khoa=<?=$id_khoa_sel?>" onclick="return confirm('Đánh dấu ĐẠT thực hành hình cho TẤT CẢ học viên trong khóa?')" class="btn btn-sm bg-gradient-warning"><i class="fas fa-check-double mr-1"></i>Đạt toàn bộ</a> <?php endif; ?><?php endif; ?><?php if(dt_can('hinh','export')): ?>		<a href="<?=dt_export_link('exportHinh', array('id_khoa','keyword','trang_thai','gv'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <?php endif; ?><?php if(dt_can('hinh','delete')): ?><button type="submit" form="dt-module-hinh" formaction="index.php?com=daotao&act=moduleDeleteSelected" onclick="return confirm('Xóa kết quả thực hành trong hình của các học viên đã chọn?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button><?php endif; ?></div></div>
		<div class="card-body">
			<?php if(dt_can('hinh','edit')): ?><button type="submit" form="dt-module-hinh" formaction="index.php?com=daotao&act=saveHinhSelected" name="save_hinh_selected" value="1" class="btn btn-sm bg-gradient-success mb-2"><i class="fas fa-save mr-1"></i>Lưu toàn bộ</button><?php endif; ?>
			<?php if(dt_can('hinh','edit') || dt_can('hinh','delete')): ?><form id="dt-module-hinh" method="post" action="index.php?com=daotao&act=modulePassSelected"><input type="hidden" name="entity" value="hinh"><input type="hidden" name="id_khoa" value="<?=(int)$id_khoa_sel?>"><input type="hidden" name="ret" value="<?=$hinh_ret?>"></form><?php endif; ?>
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="hinh">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars(course_option_label($k))?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<input type="text" name="gv" class="form-control form-control-sm mr-2" placeholder="Giáo viên" value="<?=isset($_REQUEST['gv'])?htmlspecialchars($_REQUEST['gv']):''?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2">
					<option value="">— Trạng thái —</option>
					<option value="dat" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='dat')?'selected':''?>>Đạt</option>
					<option value="chua" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='chua')?'selected':''?>>Chưa đạt</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<div class="dt-stats mb-2"><div class="dt-stat is-green"><div class="dt-stat-label">Thực hành hình đạt</div><div class="dt-stat-num"><?=(int)$module_stats['dat']?></div></div><div class="dt-stat is-red"><div class="dt-stat-label">Thực hành hình chưa đạt</div><div class="dt-stat-num"><?=(int)$module_stats['chua']?></div></div></div>
			<?php if(!$id_khoa_sel && empty($items)): ?>
			<div class="alert alert-info mb-0">Chọn khóa để nhập thực hành trong hình, hoặc nhập CCCD/tên để tra cứu toàn bộ khóa.</div>
			<?php else: ?>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th class="text-center" style="width:34px"><?php if(dt_can('hinh','edit') || dt_can('hinh','delete')): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-module-hinh" title="Chọn tất cả"><?php endif; ?></th><th>Họ và tên</th><th>CCCD</th><th>Hạng</th><th>Giáo viên</th><th class="text-center">Kết quả</th><th style="width:420px">Giờ / km / xác nhận</th><th>Thao tác</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $hinhLimit = dt_nguong_hinh()[dt_norm_hang($it['hang'])] ?? array(); ?>
					<tr>
						<td class="text-center"><?php if(dt_can('hinh','edit') || dt_can('hinh','delete')): ?><input type="checkbox" class="dt-bulk-item" form="dt-module-hinh" name="ids[]" value="<?=(int)$it['id']?>"><?php endif; ?></td>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td class="dt-sub"><i class="fas fa-chalkboard-teacher"></i><?=htmlspecialchars(!empty($it['gv_hoten']) ? $it['gv_hoten'] : '—')?></td>
						<td class="text-center"><?php if((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-secondary">Chưa</span><?php endif; ?></td>
						<td>
							<form data-hinh-id="<?=(int)$it['id']?>" method="post" action="index.php?com=daotao&act=saveHinh&id_khoa=<?=(int)(isset($it['id_khoa'])?$it['id_khoa']:$id_khoa_sel)?>" style="display:flex;gap:6px;align-items:center;margin:0;">
								<input type="hidden" name="cccd" value="<?=htmlspecialchars($it['cccd'])?>">
								<input type="number" step="0.01" min="0" <?=isset($hinhLimit['gio_max']) ? 'max="'.$hinhLimit['gio_max'].'"' : ''?> name="gio" class="form-control form-control-sm" style="width:90px" placeholder="giờ" value="<?=$it['gio']!==null?htmlspecialchars($it['gio']):''?>">
								<input type="number" step="0.01" min="0" <?=isset($hinhLimit['km_max']) ? 'max="'.$hinhLimit['km_max'].'"' : ''?> name="km" class="form-control form-control-sm" style="width:90px" placeholder="km" value="<?=$it['km']!==null?htmlspecialchars($it['km']):''?>">
								<label class="mb-0 text-nowrap"><input type="checkbox" name="dat_override" value="1" <?=$it['dat_override']!==null && (int)$it['dat_override']===1?'checked':''?>> Đạt</label>
								<button class="btn btn-xs bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu</button>
							</form>
						</td>
						<td class="text-center"><?php if(!empty($it['data_id'])): ?><a href="index.php?com=daotao&act=crudEdit&entity=hinh&id=<?=$it['data_id']?>&ret=<?=$hinh_ret?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=hinh&id=<?=$it['data_id']?>&ret=<?=$hinh_ret?>" onclick="return confirm('Xóa dữ liệu hình này?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="8" class="text-center text-muted p-3">Chưa có học viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>
</section>
