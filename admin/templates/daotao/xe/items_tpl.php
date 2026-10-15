<?php if(!defined('SOURCES')) die("Error");
	$dt_ret = ''; $__q = array();
	foreach(array('keyword','hang','loai_xe','nhan_hieu','tt_tl','expiry','p') as $__k) if(isset($_GET[$__k]) && $_GET[$__k] !== '') $__q[$__k] = $_GET[$__k];
	if($__q) $dt_ret = '&'.http_build_query($__q);
	$show_tl = isset($_REQUEST['tt_tl']) ? $_REQUEST['tt_tl'] : '';
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
			<div class="card-tools"><?php if(dt_can('xe','edit')): ?><a href="index.php?com=daotao&act=crudEdit&entity=xe" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <?php endif; ?><?php if(dt_can('xe','import')): ?><a href="index.php?com=daotao&act=uploadXe" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import xe</a> <?php endif; ?><?php if(dt_can('xe','export')): ?><a href="<?=dt_export_link('exportXe', array('keyword','hang','loai_xe','nhan_hieu','tt_tl','expiry'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <?php endif; ?><?php if(dt_can('xe','delete') && dt_is_admin()): ?><a href="index.php?com=daotao&act=crudDeleteAll&entity=xe" onclick="return confirm('Xóa toàn bộ xe tập lái?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a><?php endif; ?></div>
		</div>
		<div class="card-body">
			<?php if(!empty($xe_stats)): ?>
			<div class="mb-2 text-sm">
				<a href="index.php?com=daotao&act=xe&expiry=sap_het" class="badge badge-light border mr-1" title="Xem xe hết hạn trong vòng chưa đủ 30 ngày"><strong>GPLX xe sắp hết hạn (&lt;30 ngày):</strong> <span class="text-warning"><?=$xe_stats['sap_het']?></span> / <?=$xe_stats['tong']?> xe đang hoạt động</a>
				<?php if($xe_stats['het_han']): ?><a href="index.php?com=daotao&act=xe&expiry=het_han" class="badge badge-light border text-danger mr-1" title="Xem xe đã hết hạn">Đã hết hạn: <?=$xe_stats['het_han']?></a><?php endif; ?>
				<?php if($xe_stats['chua_ngay']): ?><span class="badge badge-light border text-muted mr-1">Chưa có ngày hết hạn: <?=$xe_stats['chua_ngay']?></span><?php endif; ?>
			</div>
			<div class="mb-2 text-sm">
				<?php foreach($xe_stats['hang'] as $hang => $st): ?>
				<span class="badge badge-light border mr-1" title="Hạng <?=htmlspecialchars($hang)?>: <?=$st['con_han']?> còn hạn / <?=$st['sap_het']?> sắp hết / <?=$st['het_han']?> hết hạn / <?=$st['chua_ngay']?> thiếu ngày">
					<strong><?=htmlspecialchars($hang)?></strong>:
					<span class="text-success"><?=$st['con_han']?> còn hạn</span>
					<?php if($st['sap_het']): ?><a class="text-warning" href="index.php?com=daotao&act=xe&hang=<?=urlencode($hang)?>&expiry=sap_het" title="Xem xe hạng <?=htmlspecialchars($hang)?> sắp hết hạn">• <?=$st['sap_het']?> sắp hết</a><?php endif; ?>
					<?php if($st['het_han']): ?><a class="text-danger" href="index.php?com=daotao&act=xe&hang=<?=urlencode($hang)?>&expiry=het_han" title="Xem xe hạng <?=htmlspecialchars($hang)?> đã hết hạn">• <?=$st['het_han']?> hết hạn</a><?php endif; ?>
					<?php if($st['chua_ngay']): ?><span class="text-muted">• <?=$st['chua_ngay']?> thiếu ngày</span><?php endif; ?>
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
				<select name="tt_tl" class="form-control form-control-sm mr-2">
					<option value="">Tất cả trạng thái</option>
					<option value="hoat_dong" <?=$show_tl==='hoat_dong'?'selected':''?>>Đang hoạt động</option>
					<option value="da_thanh_ly" <?=$show_tl==='da_thanh_ly'?'selected':''?>>Đã thanh lý</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Xe tập lái</th><th>Thông số</th><th>Giáo viên</th><th>GPLX tập lái</th><th>Ghi chú</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $exp = function_exists('dt_expiry_info') ? dt_expiry_info(isset($it['ngay_hethan'])?$it['ngay_hethan']:'') : null; $is_tl = !empty($it['thanh_ly']); ?>
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
							<?php if(!empty($it['so_gpxtl'])): ?>Số GP: <strong><?=htmlspecialchars($it['so_gpxtl'])?></strong><br><?php endif; ?>
							<?php if(!empty($it['ngay_hethan'])): ?>HH: <strong><?=htmlspecialchars($it['ngay_hethan'])?></strong><br>
								<?php if($exp && $exp['status']==='het_han'): ?><span class="badge badge-danger">Hết hạn <?=abs($exp['days'])?> ngày</span>
								<?php elseif($exp && $exp['status']==='sap_het'): ?><span class="badge badge-warning">Còn <?=$exp['days']?> ngày</span>
								<?php else: ?><span class="badge badge-success">Còn hạn</span><?php endif; ?>
							<?php else: ?><span class="text-muted">—</span><?php endif; ?>
							<?php if(!empty($it['ngay_hethan_gcnkd'])): $expkd = function_exists('dt_expiry_info') ? dt_expiry_info($it['ngay_hethan_gcnkd']) : null; ?>
								<div class="mt-1">HH kiểm định: <strong><?=htmlspecialchars($it['ngay_hethan_gcnkd'])?></strong>
								<?php if($expkd && $expkd['status']==='het_han'): ?> <span class="badge badge-danger">Hết hạn</span>
								<?php elseif($expkd && $expkd['status']==='sap_het'): ?> <span class="badge badge-warning">Còn <?=$expkd['days']?> ngày</span><?php endif; ?></div>
							<?php endif; ?>
						</td>
						<td class="dt-sub">
							<?php if($is_tl): ?><span class="badge badge-secondary">Đã thanh lý<?php if(!empty($it['ngay_thanh_ly'])): ?> (<?=htmlspecialchars($it['ngay_thanh_ly'])?>)<?php endif; ?></span><?php endif; ?>
							<?php if(!empty($it['ghi_chu'])): ?><div class="dt-sub"><?=nl2br(htmlspecialchars($it['ghi_chu']))?></div><?php elseif(!$is_tl): ?><span class="text-muted">—</span><?php endif; ?>
						</td>
						<td class="dt-actions">
							<a href="index.php?com=daotao&act=crudEdit&entity=xe&id=<?=$it['id']?><?=$dt_ret?>" class="btn btn-xs bg-gradient-info" title="Sửa"><i class="fas fa-edit"></i></a>
							<?php if($is_tl): ?>
								<a href="index.php?com=daotao&act=xeThanhLy&huy=1&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Hủy thanh lý xe này?')" class="btn btn-xs bg-gradient-secondary" title="Hủy thanh lý"><i class="fas fa-undo"></i></a>
							<?php else: ?>
								<a href="index.php?com=daotao&act=xeThanhLy&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Thanh lý xe <?=htmlspecialchars($it['bien_so'])?>? Xe vẫn hiển thị nhưng không tính vào lưu lượng.')" class="btn btn-xs bg-gradient-warning" title="Thanh lý xe"><i class="fas fa-clipboard-check"></i></a>
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
