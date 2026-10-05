<?php if(!defined('SOURCES')) die("Error");
	$dt_ret = ''; $__q = array();
	foreach(array('keyword','hang','loai_xe','nhan_hieu','show_thanh_ly','p') as $__k) if(isset($_GET[$__k]) && $_GET[$__k] !== '') $__q[$__k] = $_GET[$__k];
	if($__q) $dt_ret = '&'.http_build_query($__q);
	$show_tl = isset($_REQUEST['show_thanh_ly']) ? $_REQUEST['show_thanh_ly'] : '';
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Xe tập lái</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách xe tập lái</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=xe" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <a href="index.php?com=daotao&act=uploadXe" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import xe</a> <a href="<?=dt_export_link('exportXe', array('keyword','hang','loai_xe','nhan_hieu'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=xe" onclick="return confirm('Xóa toàn bộ xe tập lái?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<?php if(!empty($xe_stats)): ?>
			<div class="mb-2 text-sm">
				<?php foreach($xe_stats as $hang => $st): ?>
				<span class="badge badge-light border mr-1" title="Hạng <?=htmlspecialchars($hang)?>: <?=$st['con_han']?> còn hạn / <?=$st['sap_het']?> sắp hết / <?=$st['het_han']?> hết hạn">
					<strong><?=htmlspecialchars($hang)?></strong>:
					<span class="text-success"><?=$st['con_han']?> còn hạn</span>
					<?php if($st['sap_het']): ?><span class="text-warning">• <?=$st['sap_het']?> sắp hết</span><?php endif; ?>
					<?php if($st['het_han']): ?><span class="text-danger">• <?=$st['het_han']?> hết hạn</span><?php endif; ?>
				</span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="xe">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Biển số / Giáo viên" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="hang" class="form-control form-control-sm mr-2">
					<option value="">— Hạng —</option>
					<?php foreach(array('B11','B1','C1','C','CE') as $h): ?><option value="<?=$h?>" <?=(isset($_REQUEST['hang'])&&$_REQUEST['hang']==$h)?'selected':''?>><?=$h?></option><?php endforeach; ?>
				</select>
				<input type="text" name="loai_xe" class="form-control form-control-sm mr-2" placeholder="Loại xe" value="<?=isset($_REQUEST['loai_xe'])?htmlspecialchars($_REQUEST['loai_xe']):''?>">
				<input type="text" name="nhan_hieu" class="form-control form-control-sm mr-2" placeholder="Nhãn hiệu" value="<?=isset($_REQUEST['nhan_hieu'])?htmlspecialchars($_REQUEST['nhan_hieu']):''?>">
				<select name="show_thanh_ly" class="form-control form-control-sm mr-2">
					<option value="">Đang hoạt động</option>
					<option value="1" <?=$show_tl==='1'?'selected':''?>>Gồm cả đã thanh lý</option>
					<option value="chi" <?=$show_tl==='chi'?'selected':''?>>Chỉ xe đã thanh lý</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Xe tập lái</th><th>Thông số</th><th>Giáo viên</th><th>GPLX tập lái</th><th>Cam kết GV</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $exp = function_exists('dt_expiry_info') ? dt_expiry_info(isset($it['ngay_hethan'])?$it['ngay_hethan']:'') : null; $is_tl = !empty($it['thanh_ly']); $ck = isset($it['gv_cam_ket']) ? $it['gv_cam_ket'] : null; ?>
					<tr<?=$is_tl?' class="text-muted"':''?>>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['bien_so'])?> <?php if($is_tl): ?><span class="badge badge-secondary">Đã thanh lý</span><?php endif; ?></div>
							<div class="dt-sub"><i class="fas fa-car"></i><?=htmlspecialchars($it['nhan_hieu'] ?: '—')?></div>
						</td>
						<td>
							<div><?php if(!empty($it['hang_xe'])): ?><span class="dt-tag"><?=htmlspecialchars($it['hang_xe'])?></span><?php endif; ?> <?php if(!empty($it['loai_xe'])): ?><span class="dt-tag is-muted">Loại <?=htmlspecialchars($it['loai_xe'])?></span><?php endif; ?></div>
							<div class="dt-sub"><i class="fas fa-hashtag"></i>Số khung: <strong><?=htmlspecialchars($it['so_khung'] ?: '—')?></strong></div>
						</td>
						<td class="dt-sub"><i class="fas fa-chalkboard-teacher"></i><?=htmlspecialchars($it['gv_hoten'] ?: '—')?></td>
						<td class="dt-sub">
							<?php if(!empty($it['ngay_hethan'])): ?>HH: <strong><?=htmlspecialchars($it['ngay_hethan'])?></strong><br>
								<?php if($exp && $exp['status']==='het_han'): ?><span class="badge badge-danger">Hết hạn <?=abs($exp['days'])?> ngày</span>
								<?php elseif($exp && $exp['status']==='sap_het'): ?><span class="badge badge-warning">Còn <?=$exp['days']?> ngày</span>
								<?php else: ?><span class="badge badge-success">Còn hạn</span><?php endif; ?>
							<?php else: ?><span class="text-muted">—</span><?php endif; ?>
						</td>
						<td class="dt-sub">
							<?php if($ck): ?>
								<?php if($ck['con_hieu_luc']): ?><span class="badge badge-info">Còn <?=htmlspecialchars($ck['con_text'])?></span>
								<?php else: ?><span class="badge badge-secondary">Hết cam kết</span><?php endif; ?>
								<div><small class="text-muted"><?=(int)$ck['nam']?> năm<?php if(!empty($ck['den_ngay'])): ?> • đến <?=htmlspecialchars($ck['den_ngay'])?><?php endif; ?></small></div>
							<?php else: ?><span class="text-muted">Chưa có cam kết</span><?php endif; ?>
						</td>
						<td class="dt-actions">
							<a href="index.php?com=daotao&act=crudEdit&entity=xe&id=<?=$it['id']?><?=$dt_ret?>" class="btn btn-xs bg-gradient-info" title="Sửa"><i class="fas fa-edit"></i></a>
							<?php if($is_tl): ?>
								<a href="index.php?com=daotao&act=xeThanhLy&huy=1&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Hủy thanh lý xe này?')" class="btn btn-xs bg-gradient-secondary" title="Hủy thanh lý"><i class="fas fa-undo"></i></a>
							<?php else: ?>
								<a href="index.php?com=daotao&act=xeThanhLy&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Thanh lý xe <?=htmlspecialchars($it['bien_so'])?>? Mặc định sẽ ẩn khỏi danh sách đang hoạt động.')" class="btn btn-xs bg-gradient-warning" title="Thanh lý xe"><i class="fas fa-clipboard-check"></i></a>
							<?php endif; ?>
							<a href="index.php?com=daotao&act=crudDelete&entity=xe&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Xóa xe này?')" class="btn btn-xs bg-gradient-danger" title="Xóa"><i class="fas fa-trash"></i></a>
						</td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="6" class="text-center text-muted p-3">Chưa có xe</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
