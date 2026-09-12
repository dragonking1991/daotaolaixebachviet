<?php
	$linkSave = "index.php";
	$nhomInfo = array(
		'bt'  => array('label' => 'BT', 'badge' => 'badge-primary', 'border' => 'border-left-primary'),
		'ck'  => array('label' => 'CK', 'badge' => 'badge-info', 'border' => 'border-left-info'),
		'dat' => array('label' => 'DAT', 'badge' => 'badge-warning', 'border' => 'border-left-warning'),
	);
	$dinhMucFields = array(
		'bt'  => array('key' => 'xd_dinh_muc', 'val' => (int)($item['dinh_muc'] ?? 0)),
		'ck'  => array('key' => 'xd_dinh_muc_ck', 'val' => (int)($item['dinh_muc_ck'] ?? 0)),
		'dat' => array('key' => 'xd_dinh_muc_dat', 'val' => (int)($item['dinh_muc_dat'] ?? 0)),
	);
	$mucThanhToanFields = array(
		'bt'  => array('key' => 'xd_muc_bt', 'val' => (int)($item['muc_bt'] ?? 0)),
		'ck'  => array('key' => 'xd_muc_ck', 'val' => (int)($item['muc_ck'] ?? 0)),
		'dat' => array('key' => 'xd_muc_dat', 'val' => (int)($item['muc_dat'] ?? 0)),
	);
	$hangKhoaFields = array(
		'ck'  => array('bss' => 'xd_dinh_muc_ck_bss', 'btd' => 'xd_dinh_muc_ck_btd', 'c1' => 'xd_dinh_muc_ck_c1', 'c' => 'xd_dinh_muc_ck_c', 'ce' => 'xd_dinh_muc_ck_ce'),
		'dat' => array('bss' => 'xd_dinh_muc_dat_bss', 'btd' => 'xd_dinh_muc_dat_btd', 'c1' => 'xd_dinh_muc_dat_c1', 'c' => 'xd_dinh_muc_dat_c', 'ce' => 'xd_dinh_muc_dat_ce'),
	);
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Cấu hình định mức xăng dầu</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<form class="validation-form" novalidate method="post" action="<?=$linkSave?>">
		<input type="hidden" name="com" value="xangdau">
		<input type="hidden" name="act" value="saveConfig">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title"><i class="fas fa-gas-pump mr-2 text-primary"></i>Tham số thanh toán chi phí xăng dầu</h3>
				<div class="card-tools">
					<small class="text-muted"><i class="far fa-lightbulb mr-1"></i>Dùng cho thuật toán lọc thanh toán và bảng kê</small>
				</div>
			</div>
			<div class="card-body">
				<h6 class="text-uppercase text-muted font-weight-bold mb-3"><i class="fas fa-divide mr-1"></i>Định mức XD / 1 học viên <small class="font-weight-normal">(dùng để chia số học viên được chọn theo từng nhóm)</small></h6>
				<div class="row mb-4">
					<?php foreach($dinhMucFields as $nhom => $f) { $info = $nhomInfo[$nhom]; ?>
					<div class="col-md-4 col-sm-12 mb-2">
						<div class="card card-outline <?=$info['border']?> mb-0 h-100">
							<div class="card-body py-2 px-3">
								<label class="mb-1 d-flex align-items-center" for="cfg_<?=$f['key']?>">
									<span class="badge <?=$info['badge']?> mr-2"><?=$info['label']?></span>
									Định mức XD nhóm <?=$info['label']?>
								</label>
								<div class="input-group input-group-sm">
									<input type="text" inputmode="numeric" class="form-control text-right money-input" id="cfg_<?=$f['key']?>"
										name="data[<?=$f['key']?>]"
										value="<?=number_format((int)$f['val'], 0, ',', '.')?>"
										placeholder="Nhập số tiền">
									<div class="input-group-append"><span class="input-group-text">đ</span></div>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>
				</div>

				<h6 class="text-uppercase text-muted font-weight-bold mb-3"><i class="fas fa-hand-holding-usd mr-1"></i>Mức thanh toán / 1 học viên <small class="font-weight-normal">(số tiền thực trả cho mỗi học viên theo nhóm)</small></h6>
				<div class="row mb-4">
					<?php foreach($mucThanhToanFields as $nhom => $f) { $info = $nhomInfo[$nhom]; ?>
					<div class="col-md-4 col-sm-12 mb-2">
						<div class="card card-outline <?=$info['border']?> mb-0 h-100">
							<div class="card-body py-2 px-3">
								<label class="mb-1 d-flex align-items-center" for="cfg_<?=$f['key']?>">
									<span class="badge <?=$info['badge']?> mr-2"><?=$info['label']?></span>
									Mức thanh toán nhóm <?=$info['label']?>
								</label>
								<div class="input-group input-group-sm">
									<input type="text" inputmode="numeric" class="form-control text-right money-input" id="cfg_<?=$f['key']?>"
										name="data[<?=$f['key']?>]"
										value="<?=number_format((int)$f['val'], 0, ',', '.')?>"
										placeholder="Nhập số tiền">
									<div class="input-group-append"><span class="input-group-text">đ</span></div>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>
				</div>

				<h6 class="text-uppercase text-muted font-weight-bold mb-3"><i class="fas fa-user-cog mr-1"></i>Điều chỉnh định mức thanh toán theo học viên <small class="font-weight-normal">(ảnh hưởng trực tiếp Định mức tối đa và Tổng chi khi lọc thanh toán)</small></h6>
				<div class="card card-outline border-left-secondary mb-4">
					<div class="card-body py-3">
						<div class="form-row align-items-end">
							<div class="col-md-4 mb-2"><label class="mb-1" for="cccd_lookup">CCCD học viên</label><input type="text" class="form-control form-control-sm" id="cccd_lookup" name="cccd" value="<?=htmlspecialchars($xd_config_hocvien_cccd ?? '')?>" placeholder="Nhập CCCD để tìm"></div>
							<div class="col-md-2 mb-2"><button class="btn btn-sm btn-secondary" type="button" onclick="window.location.href='index.php?com=xangdau&amp;act=config&amp;cccd='+encodeURIComponent(document.getElementById('cccd_lookup').value);"><i class="fas fa-search mr-1"></i>Tìm học viên</button></div>
							<div class="col-md-3 mb-2"><a class="btn btn-sm btn-outline-primary" href="index.php?com=xangdau&amp;act=config&amp;danh_sach_dieu_chinh=1"><i class="fas fa-list mr-1"></i>DS đã điều chỉnh</a></div>
							<?php if(!empty($xd_config_hocvien)) { ?>
							<div class="col-md-4 mb-2"><label class="mb-1">Học viên</label><div class="form-control form-control-sm bg-light"><?=htmlspecialchars($xd_config_hocvien['ho_ten'])?> - <?=htmlspecialchars($xd_config_hocvien['nhom'])?> - <?=htmlspecialchars($xd_config_hocvien['khoa'])?></div></div>
							<div class="col-md-2 mb-2"><label class="mb-1">Số tiền TT hiện tại</label><div class="form-control form-control-sm bg-light text-right font-weight-bold"><?=number_format((float)$xd_config_hocvien['so_tien_thanh_toan'], 0, ',', '.')?> đ</div></div>
							<div class="col-md-2 mb-2"><label class="mb-1" for="so_tien_thanh_toan_ca_nhan">Định mức TT mới</label><div class="input-group input-group-sm"><input type="text" inputmode="numeric" class="form-control text-right money-input" id="so_tien_thanh_toan_ca_nhan" name="so_tien_thanh_toan_ca_nhan" value="<?=number_format((int)$xd_config_hocvien['so_tien_thanh_toan'], 0, ',', '.')?>"><div class="input-group-append"><span class="input-group-text">đ</span></div></div><input type="hidden" name="cccd_hocvien" value="<?=htmlspecialchars($xd_config_hocvien['cccd'])?>"></div>
							<div class="col-md-2 mb-2"><label class="mb-1 d-block">&nbsp;</label><button type="submit" class="btn btn-sm btn-primary"><i class="far fa-save mr-1"></i>Lưu định mức</button></div>
							<?php } elseif(($xd_config_hocvien_cccd ?? '') !== '') { ?><div class="col-md-6 mb-2 text-danger small">Không tìm thấy học viên có CCCD này.</div><?php } ?>
						</div>
						<?php if(isset($_GET['danh_sach_dieu_chinh']) && $_GET['danh_sach_dieu_chinh'] === '1') { ?>
						<div class="table-responsive border-top pt-3 mt-2"><table class="table table-sm table-bordered mb-0"><thead class="bg-light"><tr><th>STT</th><th>Học viên</th><th>CCCD</th><th>Khóa</th><th>Nhóm</th><th>GV phụ trách</th><th class="text-right">Số tiền TT</th><th></th></tr></thead><tbody><?php if(!empty($xd_config_hocvien_dieu_chinh)) { $stt = 0; foreach($xd_config_hocvien_dieu_chinh as $hocvien) { $stt++; ?><tr><td><?=$stt?></td><td><?=htmlspecialchars($hocvien['ho_ten'])?></td><td><?=htmlspecialchars($hocvien['cccd'])?></td><td><?=htmlspecialchars($hocvien['khoa'])?></td><td><?=htmlspecialchars($hocvien['nhom'])?></td><td><?=htmlspecialchars($hocvien['gv_hoten'])?></td><td class="text-right"><?=number_format((float)$hocvien['so_tien_thanh_toan'], 0, ',', '.')?></td><td class="text-center"><a class="btn btn-sm btn-outline-danger" href="index.php?com=xangdau&amp;act=xoaDieuChinhHocVien&amp;cccd=<?=urlencode($hocvien['cccd'])?>" title="Xóa điều chỉnh" onclick="return confirm('Xóa điều chỉnh số tiền của học viên này và trả về mức thanh toán theo nhóm?');"><i class="far fa-trash-alt"></i></a></td></tr><?php } } else { ?><tr><td colspan="8" class="text-center text-muted">Chưa có học viên nào được điều chỉnh số tiền thanh toán.</td></tr><?php } ?></tbody></table></div>
						<?php } ?>
					</div>
				</div>

				<h6 class="text-uppercase text-muted font-weight-bold mb-3"><i class="fas fa-layer-group mr-1"></i>Định mức XD theo hạng khóa (CK/DAT) <small class="font-weight-normal">(ưu tiên theo khóa nếu cấu hình > 0, ngược lại dùng định mức nhóm)</small></h6>
				<div class="row mb-4">
					<?php foreach($hangKhoaFields as $nhom => $fields) { $info = $nhomInfo[$nhom]; ?>
					<div class="col-lg-6 col-md-12 mb-3">
						<div class="card card-outline <?=$info['border']?> mb-0 h-100">
							<div class="card-body py-2 px-3">
								<div class="d-flex align-items-center mb-3">
									<span class="badge <?=$info['badge']?> mr-2"><?=$info['label']?></span>
									<strong>Định mức nhóm <?=$info['label']?></strong>
								</div>
								<div class="row">
									<?php foreach($fields as $hang => $key) { $val = (int)($item[$key] ?? 0); ?>
									<div class="col-md-6 col-sm-12 mb-2">
										<label class="mb-1" for="cfg_<?=$key?>">Khóa <?=$hang?></label>
										<div class="input-group input-group-sm">
											<input type="text" inputmode="numeric" class="form-control text-right money-input" id="cfg_<?=$key?>"
												name="data[<?=$key?>]"
												value="<?=number_format($val, 0, ',', '.')?>"
												placeholder="0">
											<div class="input-group-append"><span class="input-group-text">đ</span></div>
										</div>
									</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>
				</div>

				<div class="callout callout-info mb-0">
					<h6><i class="fas fa-info-circle mr-1"></i>Công thức tính</h6>
					<p class="mb-1">Số học viên tối đa mỗi nhóm: <code class="bg-white text-dark px-2 py-1 rounded border">N = floor(Tổng hóa đơn / Định mức XD của nhóm đó)</code></p>
					<p class="mb-0">Tính lần lượt theo thứ tự nhập cho tới khi hết ngân sách hóa đơn. Học viên nhóm <strong>BT/CK/DAT</strong> nhận "Mức thanh toán" tương ứng; số lượng học viên mỗi nhóm được chọn phụ thuộc "Định mức XD" riêng của nhóm đó.</p>
				</div>
			</div>
			<div class="card-footer text-sm text-right">
				<button type="submit" class="btn btn-sm bg-gradient-primary px-4"><i class="far fa-save mr-2"></i>Lưu thay đổi</button>
			</div>
		</div>
	</form>
</section>

<style>
	.border-left-primary { border-left: 3px solid #007bff !important; }
	.border-left-info { border-left: 3px solid #17a2b8 !important; }
	.border-left-warning { border-left: 3px solid #ffc107 !important; }
</style>
<script>
	(function() {
		document.querySelectorAll('.money-input').forEach(function(input) {
			input.addEventListener('input', function() {
				var digits = input.value.replace(/\D/g, '');
				input.value = digits === '' ? '' : Number(digits).toLocaleString('vi-VN');
			});
		});
	})();
</script>
