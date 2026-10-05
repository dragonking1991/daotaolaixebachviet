<?php if(!defined('SOURCES')) die("Error");
	$dt_ret = ''; $__q = array();
	foreach(array('keyword','gioitinh','hang_gplx','hang','show_thanh_ly','p') as $__k) if(isset($_GET[$__k]) && $_GET[$__k] !== '') $__q[$__k] = $_GET[$__k];
	if($__q) $dt_ret = '&'.http_build_query($__q);
	$show_tl = isset($_REQUEST['show_thanh_ly']) ? $_REQUEST['show_thanh_ly'] : '';
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Giáo viên</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách giáo viên</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=giaovien" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <a href="index.php?com=daotao&act=uploadGiaovien" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import giáo viên</a> <a href="<?=dt_export_link('exportGiaovien', array('keyword','gioitinh','hang_gplx','hang'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=giaovien" onclick="return confirm('Xóa toàn bộ giáo viên?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="giaovien">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="gioitinh" class="form-control form-control-sm mr-2">
					<option value="">— Giới tính —</option>
					<option value="M" <?=(isset($_REQUEST['gioitinh'])&&$_REQUEST['gioitinh']=='M')?'selected':''?>>Nam</option>
					<option value="F" <?=(isset($_REQUEST['gioitinh'])&&$_REQUEST['gioitinh']=='F')?'selected':''?>>Nữ</option>
				</select>
				<input type="text" name="hang_gplx" class="form-control form-control-sm mr-2" placeholder="Hạng GPLX" value="<?=isset($_REQUEST['hang_gplx'])?htmlspecialchars($_REQUEST['hang_gplx']):''?>">
				<input type="text" name="hang" class="form-control form-control-sm mr-2" placeholder="Hạng ĐT được phép" value="<?=isset($_REQUEST['hang'])?htmlspecialchars($_REQUEST['hang']):''?>">
				<select name="show_thanh_ly" class="form-control form-control-sm mr-2">
					<option value="">Đang làm việc</option>
					<option value="1" <?=$show_tl==='1'?'selected':''?>>Gồm cả đã thanh lý</option>
					<option value="chi" <?=$show_tl==='chi'?'selected':''?>>Chỉ đã thanh lý</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Giáo viên</th><th>GPLX &amp; chuyên môn</th><th>Đào tạo &amp; tuyển dụng</th><th>Liên hệ</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $exp = function_exists('dt_expiry_info') ? dt_expiry_info(isset($it['ngay_hh_gplx'])?$it['ngay_hh_gplx']:'') : null; $is_tl = !empty($it['thanh_ly']); ?>
					<tr<?=$is_tl?' class="text-muted"':''?>>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['hoten'] ?: '—')?> <?php if($is_tl): ?><span class="badge badge-secondary">Đã thanh lý</span><?php endif; ?></div>
							<div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($it['cccd'] ?: '—')?></div>
							<div class="dt-sub">
								<?php if(!empty($it['ngaysinh'])): ?><i class="fas fa-birthday-cake"></i><?=htmlspecialchars($it['ngaysinh'])?><?php endif; ?>
								<?php if(!empty($it['gioitinh'])): ?><span class="dt-sep">•</span><?=htmlspecialchars($it['gioitinh']==='F'?'Nữ':($it['gioitinh']==='M'?'Nam':$it['gioitinh']))?><?php endif; ?>
							</div>
						</td>
						<td>
							<div class="dt-sub"><i class="fas fa-id-badge"></i>GPLX: <strong><?=htmlspecialchars($it['hang_gplx'] ?: '—')?></strong><?php if(!empty($it['so_gplx'])): ?> <span class="dt-sep">•</span> Số: <?=htmlspecialchars($it['so_gplx'])?><?php endif; ?></div>
							<?php if(!empty($it['ngay_cap_gplx']) || !empty($it['ngay_hh_gplx'])): ?><div class="dt-sub"><i class="far fa-calendar-alt"></i>Cấp: <?=htmlspecialchars($it['ngay_cap_gplx'] ?: '—')?> <span class="dt-sep">→</span> HH: <?=htmlspecialchars($it['ngay_hh_gplx'] ?: '—')?>
								<?php if($exp && $exp['status']==='het_han'): ?> <span class="badge badge-danger">Hết hạn <?=abs($exp['days'])?> ngày</span>
								<?php elseif($exp && $exp['status']==='sap_het'): ?> <span class="badge badge-warning">Còn <?=$exp['days']?> ngày</span><?php endif; ?></div><?php endif; ?>
							<?php if(!empty($it['trinh_do']) || !empty($it['chuyen_mon']) || !empty($it['su_pham'])): ?><div class="dt-sub"><i class="fas fa-graduation-cap"></i><?=htmlspecialchars(trim(implode(' • ', array_filter(array($it['trinh_do'], $it['chuyen_mon'], $it['su_pham'])))) ?: '—')?></div><?php endif; ?>
						</td>
						<td>
							<div><?php if(!empty($it['hang_daotao_phep'])): ?><span class="dt-tag">Được phép: <?=htmlspecialchars($it['hang_daotao_phep'])?></span><?php endif; ?> <?php if(!empty($it['loai_hinh_dt'])): ?><span class="dt-tag is-muted"><?=htmlspecialchars($it['loai_hinh_dt'])?></span><?php endif; ?></div>
							<?php if(!empty($it['hinh_thuc_td']) || !empty($it['tuyen_dung'])): ?><div class="dt-sub"><i class="fas fa-briefcase"></i><?=htmlspecialchars($it['hinh_thuc_td'] ?: '—')?><?php if(!empty($it['tuyen_dung'])): ?> <span class="dt-sep">•</span> <?=htmlspecialchars($it['tuyen_dung'])?><?php endif; ?></div><?php endif; ?>
							<?php $ck = function_exists('dt_cam_ket_info') ? dt_cam_ket_info(isset($it['cam_ket_nam'])?$it['cam_ket_nam']:0, isset($it['cam_ket_tu_ngay'])?$it['cam_ket_tu_ngay']:'') : null; if($ck): ?><div class="dt-sub"><i class="fas fa-handshake"></i>Cam kết <?=(int)$ck['nam']?> năm <?php if($ck['con_hieu_luc']): ?><span class="badge badge-info">còn <?=htmlspecialchars($ck['con_text'])?></span><?php else: ?><span class="badge badge-secondary">hết cam kết</span><?php endif; ?><?php if(!empty($ck['den_ngay'])): ?> <small class="text-muted">đến <?=htmlspecialchars($ck['den_ngay'])?></small><?php endif; ?></div><?php endif; ?>
							<?php if(!empty($it['noi_ct'])): ?><div class="dt-sub"><i class="fas fa-building"></i><?=htmlspecialchars($it['noi_ct'])?></div><?php endif; ?>
						</td>
						<td>
							<div class="dt-sub"><i class="fas fa-phone"></i><?=htmlspecialchars($it['sdt'] ?: '—')?></div>
							<?php if(!empty($it['dia_chi'])): ?><div class="dt-sub"><i class="fas fa-map-marker-alt"></i><?=htmlspecialchars($it['dia_chi'])?></div><?php endif; ?>
						</td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=crudEdit&entity=giaovien&id=<?=$it['id']?><?=$dt_ret?>" class="btn btn-xs bg-gradient-info" title="Sửa"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=gvResetPass&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Đặt lại mật khẩu cổng giáo viên về mặc định (= CCCD)?')" class="btn btn-xs bg-gradient-warning" title="Đặt lại mật khẩu"><i class="fas fa-key"></i></a> <?php if($is_tl): ?><a href="index.php?com=daotao&act=gvThanhLy&huy=1&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Hủy thanh lý giáo viên này?')" class="btn btn-xs bg-gradient-secondary" title="Hủy thanh lý"><i class="fas fa-undo"></i></a><?php else: ?><a href="index.php?com=daotao&act=gvThanhLy&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Đánh dấu giáo viên này đã thanh lý (nghỉ việc)? Mặc định sẽ ẩn khỏi danh sách.')" class="btn btn-xs bg-gradient-dark text-white" title="Thanh lý (nghỉ việc)"><i class="fas fa-user-slash"></i></a><?php endif; ?> <a href="index.php?com=daotao&act=crudDelete&entity=giaovien&id=<?=$it['id']?><?=$dt_ret?>" onclick="return confirm('Xóa giáo viên này?')" class="btn btn-xs bg-gradient-danger" title="Xóa"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="text-center text-muted p-3">Chưa có giáo viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
