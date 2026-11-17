<?php
if(!defined('SOURCES')) die("Error");
$xeRepairEsc = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$xeRepairReturn = rawurlencode($xe_suachua_ret);
$xeRepairCanEdit = dt_can('xe', 'edit');
$xeRepairCanDelete = dt_can('xe', 'delete');
$xeRepairEditing = !empty($xe_suachua_edit);
$xeRepairFormOpen = $xeRepairEditing || isset($_GET['new']) || !empty($xe_suachua_errors);
$xeRepairDate = function($value) { return $value ? date('d/m/Y', strtotime($value)) : '—'; };
$xeRepairKm = function($value) { return rtrim(rtrim(number_format((float)$value, 2, ',', '.'), '0'), ','); };
$xeRepairStatus = array(
	'dang_sua' => array('Đang sửa', 'warning'),
	'hoan_tat' => array('Hoàn tất', 'success'),
);
$xeRepairReminder = $xe_suachua_latest ? dt_xe_suachua_reminder($xe_suachua_latest['ngay_baoduong']) : 'none';
$xeRepairReminderLabels = array('overdue' => array('Quá hạn', 'danger'), 'today' => array('Đến hạn hôm nay', 'warning'), 'soon' => array('Sắp đến hạn', 'warning'), 'later' => array('Đã lên lịch', 'success'));
$xeRepairValue = function($key, $default = '') use ($xe_suachua_values) {
	return isset($xe_suachua_values[$key]) && is_scalar($xe_suachua_values[$key]) ? (string)$xe_suachua_values[$key] : $default;
};
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="<?=$xeRepairEsc($xe_suachua_ret)?>">Xe tập lái</a></li>
			<li class="breadcrumb-item active">Nhật ký sửa xe</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="container-fluid">
		<div class="mb-3">
			<a href="<?=$xeRepairEsc($xe_suachua_ret)?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách xe</a>
		</div>
		<div class="card card-primary card-outline">
			<div class="card-body">
				<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
					<div>
						<div class="text-uppercase text-muted small mb-1">Hồ sơ bảo dưỡng xe</div>
						<h2 class="h4 mb-2"><i class="fas fa-car text-primary mr-2"></i><?=htmlspecialchars($xe_suachua_vehicle['bien_so'], ENT_QUOTES, 'UTF-8')?>
							<?php if(!empty($xe_suachua_vehicle['thanh_ly'])): ?><span class="badge badge-secondary align-middle">Đã thanh lý</span><?php endif; ?>
						</h2>
						<div class="text-muted">
							<span class="mr-3"><i class="fas fa-industry mr-1"></i><?=htmlspecialchars($xe_suachua_vehicle['nhan_hieu'] ?: 'Chưa có nhãn hiệu', ENT_QUOTES, 'UTF-8')?></span>
							<span><i class="fas fa-user mr-1"></i><?=htmlspecialchars($xe_suachua_vehicle['gv_hoten'] ?: 'Chưa phân công giáo viên', ENT_QUOTES, 'UTF-8')?></span>
						</div>
					</div>
					<?php if($xeRepairCanEdit): ?>
					<a href="<?=htmlspecialchars(dt_xe_suachua_url($xe_suachua_vehicle['id'], $xe_suachua_ret, array('new' => 1)), ENT_QUOTES, 'UTF-8')?>#repair-form" class="btn btn-primary mt-3 mt-md-0"><i class="fas fa-plus mr-1"></i>Thêm lần sửa chữa</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-6 col-lg-3">
				<div class="small-box bg-info"><div class="inner"><h3><?=(int)$xe_suachua_summary['tong']?></h3><p>Tổng lần sửa chữa</p></div><div class="icon"><i class="fas fa-tools"></i></div></div>
			</div>
			<div class="col-6 col-lg-3">
				<div class="small-box bg-warning"><div class="inner"><h3><?=(int)$xe_suachua_summary['dang_sua']?></h3><p>Đang sửa</p></div><div class="icon"><i class="fas fa-wrench"></i></div></div>
			</div>
			<div class="col-6 col-lg-3">
				<div class="small-box bg-success"><div class="inner"><h3 class="text-truncate"><?=number_format((float)$xe_suachua_summary['chi_phi'], 0, ',', '.')?> ₫</h3><p>Tổng chi phí đã nhập</p></div><div class="icon"><i class="fas fa-coins"></i></div></div>
			</div>
			<div class="col-6 col-lg-3">
				<div class="small-box bg-light"><div class="inner">
					<?php if($xeRepairReminder !== 'none' && isset($xeRepairReminderLabels[$xeRepairReminder])): ?>
						<h5><span class="badge badge-<?=$xeRepairReminderLabels[$xeRepairReminder][1]?>"><?=$xeRepairReminderLabels[$xeRepairReminder][0]?></span></h5>
						<p><?=$xeRepairDate($xe_suachua_latest['ngay_baoduong'])?></p>
					<?php elseif($xe_suachua_latest && $xe_suachua_latest['km_baoduong'] !== null): ?>
						<h5 class="text-primary"><?=$xeRepairKm($xe_suachua_latest['km_baoduong'])?> km</h5><p>Mốc bảo dưỡng (chưa có số km hiện tại)</p>
					<?php else: ?>
						<h5 class="text-muted">Chưa đặt lịch</h5><p>Bảo dưỡng tiếp theo</p>
					<?php endif; ?>
					<?php if(($xeRepairLatestKm = ($xe_suachua_latest['km_baoduong'] ?? null)) !== null): ?><p class="small mb-0">Mốc: <?=$xeRepairKm($xeRepairLatestKm)?> km</p><?php endif; ?>
				</div><div class="icon"><i class="fas fa-calendar-check"></i></div></div>
			</div>
		</div>
		<?php if((int)$xe_suachua_summary['chua_chi_phi'] > 0): ?>
			<div class="small text-muted mb-3"><i class="fas fa-info-circle mr-1"></i><?=number_format((int)$xe_suachua_summary['chua_chi_phi'])?> lần sửa chưa nhập chi phí; tổng trên chỉ cộng các chi phí đã ghi nhận.</div>
		<?php endif; ?>
		<?php if($xeRepairFormOpen && $xeRepairCanEdit): ?>
		<div class="card card-primary" id="repair-form">
			<div class="card-header">
				<h3 class="card-title"><i class="fas <?=$xeRepairEditing ? 'fa-edit' : 'fa-plus-circle'?> mr-1"></i><?=$xeRepairEditing ? 'Cập nhật lần sửa chữa' : 'Ghi nhận lần sửa chữa mới'?></h3>
			</div>
			<form method="post" action="index.php?com=daotao&act=xeSuachuaSave">
				<input type="hidden" name="_csrf" value="<?=htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8')?>">
				<input type="hidden" name="id_xe" value="<?=(int)$xe_suachua_vehicle['id']?>">
				<input type="hidden" name="id" value="<?=(int)$xe_suachua_edit?>">
				<input type="hidden" name="ret" value="<?=$xeRepairReturn?>">
				<div class="card-body">
					<div class="callout callout-info py-2"><span class="small">Hồ sơ: <strong><?=htmlspecialchars($xe_suachua_vehicle['bien_so'], ENT_QUOTES, 'UTF-8')?></strong>. Thông tin chi phí và số km có thể để trống nếu chưa có.</span></div>
					<h4 class="h6 text-primary border-bottom pb-2">Thông tin sửa chữa</h4>
					<div class="form-row">
						<div class="form-group col-md-3">
							<label for="repair-date">Ngày sửa <span class="text-danger">*</span></label>
							<input type="date" id="repair-date" name="ngay_sua" class="form-control <?=!empty($xe_suachua_errors['ngay_sua']) ? 'is-invalid' : ''?>" value="<?=$xeRepairEsc($xeRepairValue('ngay_sua', date('Y-m-d')))?>" required>
							<?php if(isset($xe_suachua_errors['ngay_sua'])): ?><div class="invalid-feedback"><?=$xeRepairEsc($xe_suachua_errors['ngay_sua'])?></div><?php endif; ?>
						</div>
						<div class="form-group col-md-3">
							<label for="repair-status">Trạng thái <span class="text-danger">*</span></label>
							<select id="repair-status" name="trang_thai" class="form-control <?=!empty($xe_suachua_errors['trang_thai']) ? 'is-invalid' : ''?>">
								<option value="dang_sua" <?=$xeRepairValue('trang_thai', 'dang_sua') === 'dang_sua' ? 'selected' : ''?>>Đang sửa</option>
								<option value="hoan_tat" <?=$xeRepairValue('trang_thai') === 'hoan_tat' ? 'selected' : ''?>>Hoàn tất</option>
							</select>
							<?php if(isset($xe_suachua_errors['trang_thai'])): ?><div class="invalid-feedback"><?=$xeRepairEsc($xe_suachua_errors['trang_thai'])?></div><?php endif; ?>
						</div>
						<div class="form-group col-md-6">
							<label for="repair-garage">Gara / đơn vị thực hiện</label>
							<input type="text" id="repair-garage" name="gara" maxlength="255" class="form-control <?=!empty($xe_suachua_errors['gara']) ? 'is-invalid' : ''?>" value="<?=$xeRepairEsc($xeRepairValue('gara'))?>" placeholder="Ví dụ: Gara Thành Công">
							<?php if(isset($xe_suachua_errors['gara'])): ?><div class="invalid-feedback"><?=$xeRepairEsc($xe_suachua_errors['gara'])?></div><?php endif; ?>
						</div>
					</div>
					<div class="form-group">
						<label for="repair-description">Nội dung sửa chữa <span class="text-danger">*</span></label>
						<textarea id="repair-description" name="noi_dung" maxlength="1000" rows="3" class="form-control <?=!empty($xe_suachua_errors['noi_dung']) ? 'is-invalid' : ''?>" required placeholder="Mô tả hạng mục, tình trạng xe hoặc công việc cần thực hiện"><?=$xeRepairEsc($xeRepairValue('noi_dung'))?></textarea>
						<?php if(isset($xe_suachua_errors['noi_dung'])): ?><div class="invalid-feedback"><?=$xeRepairEsc($xe_suachua_errors['noi_dung'])?></div><?php endif; ?>
					</div>

					<h4 class="h6 text-primary border-bottom pb-2 mt-4">Chi phí và tình trạng xe</h4>
					<div class="form-row">
						<div class="form-group col-md-4">
							<label for="repair-km">Số km hiện tại</label>
							<div class="input-group"><input type="number" id="repair-km" name="so_km" min="0" max="99999999.99" step="0.01" class="form-control <?=!empty($xe_suachua_errors['so_km']) ? 'is-invalid' : ''?>" value="<?=$xeRepairEsc($xeRepairValue('so_km'))?>" placeholder="Chưa ghi nhận"><div class="input-group-append"><span class="input-group-text">km</span></div></div>
							<?php if(isset($xe_suachua_errors['so_km'])): ?><div class="text-danger small"><?=$xeRepairEsc($xe_suachua_errors['so_km'])?></div><?php endif; ?>
						</div>
						<div class="form-group col-md-4">
							<label for="repair-cost">Chi phí</label>
							<div class="input-group"><input type="number" id="repair-cost" name="chi_phi" min="0" max="99999999999999" step="1" class="form-control <?=!empty($xe_suachua_errors['chi_phi']) ? 'is-invalid' : ''?>" value="<?=$xeRepairEsc($xeRepairValue('chi_phi'))?>" placeholder="Chưa nhập"><div class="input-group-append"><span class="input-group-text">₫</span></div></div>
							<?php if(isset($xe_suachua_errors['chi_phi'])): ?><div class="text-danger small"><?=$xeRepairEsc($xe_suachua_errors['chi_phi'])?></div><?php endif; ?>
						</div>
					</div>

					<h4 class="h6 text-primary border-bottom pb-2 mt-2">Lịch bảo dưỡng tiếp theo</h4>
					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="repair-next-date">Ngày dự kiến</label>
							<input type="date" id="repair-next-date" name="ngay_baoduong" class="form-control <?=!empty($xe_suachua_errors['ngay_baoduong']) ? 'is-invalid' : ''?>" value="<?=$xeRepairEsc($xeRepairValue('ngay_baoduong'))?>">
							<?php if(isset($xe_suachua_errors['ngay_baoduong'])): ?><div class="invalid-feedback"><?=$xeRepairEsc($xe_suachua_errors['ngay_baoduong'])?></div><?php endif; ?>
						</div>
						<div class="form-group col-md-6">
							<label for="repair-next-km">Bảo dưỡng tại</label>
							<div class="input-group"><input type="number" id="repair-next-km" name="km_baoduong" min="0" max="99999999.99" step="0.01" class="form-control <?=!empty($xe_suachua_errors['km_baoduong']) ? 'is-invalid' : ''?>" value="<?=$xeRepairEsc($xeRepairValue('km_baoduong'))?>" placeholder="Không đặt mốc km"><div class="input-group-append"><span class="input-group-text">km</span></div></div>
							<?php if(isset($xe_suachua_errors['km_baoduong'])): ?><div class="text-danger small"><?=$xeRepairEsc($xe_suachua_errors['km_baoduong'])?></div><?php endif; ?>
						</div>
						<div class="form-group col-md-6">
							<label for="repair-notes">Ghi chú</label>
							<textarea id="repair-notes" name="ghi_chu" maxlength="5000" rows="2" class="form-control <?=!empty($xe_suachua_errors['ghi_chu']) ? 'is-invalid' : ''?>" placeholder="Thông tin bổ sung (không bắt buộc)"><?=$xeRepairEsc($xeRepairValue('ghi_chu'))?></textarea>
							<?php if(isset($xe_suachua_errors['ghi_chu'])): ?><div class="invalid-feedback"><?=$xeRepairEsc($xe_suachua_errors['ghi_chu'])?></div><?php endif; ?>
						</div>
					</div>
					<div class="small text-muted">Nhật ký mới nhất theo ngày sửa quyết định lịch nhắc bảo dưỡng. Hệ thống không tự suy ra số km còn lại.</div>
				</div>
				<div class="card-footer d-flex flex-wrap">
					<button type="submit" class="btn btn-primary mr-2"><i class="fas fa-save mr-1"></i><?=$xeRepairEditing ? 'Lưu cập nhật' : 'Lưu lần sửa chữa'?></button>
					<a href="<?=htmlspecialchars(dt_xe_suachua_url($xe_suachua_vehicle['id'], $xe_suachua_ret), ENT_QUOTES, 'UTF-8')?>" class="btn btn-outline-secondary">Hủy</a>
				</div>
			</form>
		</div>
		<?php endif; ?>

		<div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
			<div><h3 class="h5 mb-0">Lịch sử sửa chữa <span class="badge badge-light border"><?=number_format((int)$xe_suachua_summary['tong'])?></span></h3><div class="small text-muted">Sắp xếp theo ngày sửa mới nhất</div></div>
		</div>
		<?php if(empty($xe_suachua_rows)): ?>
			<div class="card"><div class="card-body text-center py-5">
				<div class="text-muted mb-3"><i class="fas fa-clipboard-list fa-3x"></i></div>
				<h4 class="h5">Xe chưa có nhật ký sửa chữa</h4>
				<p class="text-muted mb-3">Ghi lại công việc, chi phí và lịch bảo dưỡng để theo dõi tình trạng xe tập lái.</p>
				<?php if($xeRepairCanEdit): ?><a href="<?=htmlspecialchars(dt_xe_suachua_url($xe_suachua_vehicle['id'], $xe_suachua_ret, array('new' => 1)), ENT_QUOTES, 'UTF-8')?>#repair-form" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Ghi lần sửa đầu tiên</a><?php endif; ?>
			</div></div>
		<?php else: ?>
			<div class="row">
				<?php foreach($xe_suachua_rows as $repair): $status = $xeRepairStatus[$repair['trang_thai']] ?? array('Không xác định', 'secondary'); ?>
				<div class="col-12">
					<div class="card card-outline <?=$repair['trang_thai'] === 'dang_sua' ? 'card-warning' : 'card-success'?>">
						<div class="card-header d-flex flex-wrap justify-content-between align-items-center">
							<div><strong><i class="far fa-calendar-alt mr-1 text-muted"></i><?=$xeRepairDate($repair['ngay_sua'])?></strong> <span class="badge badge-<?=$status[1]?> ml-1"><?=$status[0]?></span></div>
							<div class="mt-2 mt-sm-0">
								<?php if($xeRepairCanEdit): ?><a href="<?=htmlspecialchars(dt_xe_suachua_url($xe_suachua_vehicle['id'], $xe_suachua_ret, array('edit' => $repair['id'])), ENT_QUOTES, 'UTF-8')?>#repair-form" class="btn btn-xs btn-outline-primary"><i class="fas fa-edit mr-1"></i>Sửa</a><?php endif; ?>
								<?php if($xeRepairCanDelete): ?>
								<form method="post" action="index.php?com=daotao&act=xeSuachuaDelete" class="d-inline" onsubmit="return confirm('Xóa lần sửa chữa ngày <?=$xeRepairEsc($xeRepairDate($repair['ngay_sua']))?> của xe <?=$xeRepairEsc($xe_suachua_vehicle['bien_so'])?>?')">
									<input type="hidden" name="_csrf" value="<?=htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8')?>">
									<input type="hidden" name="id_xe" value="<?=(int)$xe_suachua_vehicle['id']?>"><input type="hidden" name="id" value="<?=(int)$repair['id']?>"><input type="hidden" name="ret" value="<?=$xeRepairReturn?>">
									<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash mr-1"></i>Xóa</button>
								</form>
								<?php endif; ?>
							</div>
						</div>
						<div class="card-body">
							<h4 class="h6"><?=nl2br(htmlspecialchars($repair['noi_dung'], ENT_QUOTES, 'UTF-8'))?></h4>
							<div class="row text-sm">
								<div class="col-md-3 mb-2"><span class="text-muted">Gara:</span> <strong><?=htmlspecialchars($repair['gara'] ?: 'Chưa ghi nhận', ENT_QUOTES, 'UTF-8')?></strong></div>
								<div class="col-md-3 mb-2"><span class="text-muted">Số km:</span> <strong><?=$repair['so_km'] !== null ? $xeRepairKm($repair['so_km']).' km' : 'Chưa ghi nhận'?></strong></div>
								<div class="col-md-3 mb-2"><span class="text-muted">Chi phí:</span> <strong><?=$repair['chi_phi'] !== null ? number_format((float)$repair['chi_phi'], 0, ',', '.').' ₫' : 'Chưa nhập'?></strong></div>
								<div class="col-md-3 mb-2"><span class="text-muted">Bảo dưỡng:</span> <strong><?=$repair['ngay_baoduong'] ? $xeRepairDate($repair['ngay_baoduong']) : 'Chưa đặt ngày'?><?=$repair['km_baoduong'] !== null ? ' · '.$xeRepairKm($repair['km_baoduong']).' km' : ''?></strong></div>
							</div>
							<?php if(!empty($repair['ghi_chu'])): ?><div class="border-top pt-2 mt-1 text-sm"><span class="text-muted">Ghi chú:</span> <?=nl2br(htmlspecialchars($repair['ghi_chu'], ENT_QUOTES, 'UTF-8'))?></div><?php endif; ?>
							<?php $repairImages = dt_xe_suachua_decode_images($repair['anh_minh_chung'] ?? ''); if($repairImages): ?>
							<div class="border-top pt-3 mt-2">
								<div class="small text-muted mb-2"><i class="fas fa-camera mr-1"></i>Ảnh minh chứng (<?=count($repairImages)?>)</div>
								<div class="d-flex flex-wrap" style="gap:10px;">
									<?php foreach($repairImages as $image): $imageUrl = 'index.php?com=daotao&act=xeSuachuaImage&id_xe='.(int)$xe_suachua_vehicle['id'].'&id='.(int)$repair['id'].'&image='.rawurlencode($image); ?>
									<a href="<?=$xeRepairEsc($imageUrl)?>" target="_blank" rel="noopener noreferrer" aria-label="Mở ảnh minh chứng">
										<img src="<?=$xeRepairEsc($imageUrl)?>" alt="Ảnh hiện trạng xe" loading="lazy" style="width:120px;height:90px;object-fit:cover;border:1px solid #dee2e6;border-radius:6px;">
									</a>
									<?php endforeach; ?>
								</div>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php if($xe_suachua_total_pages > 1): ?>
			<nav aria-label="Phân trang lịch sử sửa chữa"><ul class="pagination pagination-sm justify-content-center">
				<?php foreach($xe_suachua_paging as $page): ?><li class="page-item <?=!empty($page['active']) ? 'active' : ''?> <?=!empty($page['disabled']) ? 'disabled' : ''?>"><?php if(!empty($page['disabled'])): ?><span class="page-link"><?=$page['page']?></span><?php else: ?><a class="page-link" href="<?=htmlspecialchars($page['url'], ENT_QUOTES, 'UTF-8')?>" <?=!empty($page['active']) ? 'aria-current="page"' : ''?>><?=$page['page']?></a><?php endif; ?></li><?php endforeach; ?>
			</ul></nav>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</section>
