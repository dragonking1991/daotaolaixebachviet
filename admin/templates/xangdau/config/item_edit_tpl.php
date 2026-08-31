<?php
	$linkSave = "index.php?com=xangdau&act=saveConfig";
	$fields = array(
		'xd_dinh_muc' => array('label' => 'Định mức XD / 1 Học viên (đ)', 'val' => (int)($item['dinh_muc'] ?? 0)),
		'xd_muc_bt'   => array('label' => 'Mức thanh toán nhóm BT (đ)', 'val' => (int)($item['muc_bt'] ?? 0)),
		'xd_muc_ck'   => array('label' => 'Mức thanh toán nhóm CK (đ)', 'val' => (int)($item['muc_ck'] ?? 0)),
		'xd_muc_dat'  => array('label' => 'Mức thanh toán nhóm DAT (đ)', 'val' => (int)($item['muc_dat'] ?? 0)),
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
		<div class="card-footer text-sm sticky-top">
			<button type="submit" class="btn btn-sm bg-gradient-primary"><i class="far fa-save mr-2"></i>Lưu</button>
		</div>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Tham số thanh toán chi phí xăng dầu</h3>
				<small class="text-muted ml-2">Dùng cho thuật toán lọc thanh toán và bảng kê</small>
			</div>
			<div class="card-body">
				<div class="row">
					<?php foreach($fields as $key => $f): ?>
					<div class="form-group col-md-3 col-sm-6">
						<label for="cfg_<?=$key?>"><?=htmlspecialchars($f['label'])?></label>
						<input type="text" class="form-control" id="cfg_<?=$key?>"
							name="data[<?=$key?>]"
							value="<?=number_format((int)$f['val'], 0, ',', '.')?>"
							placeholder="Nhập số tiền">
					</div>
					<?php endforeach; ?>
				</div>
				<div class="alert alert-info text-sm mt-2 mb-0">
					<strong>Công thức:</strong> Số học viên tối đa <code>N = floor(Tổng hóa đơn / Định mức XD)</code>.<br>
					Học viên nhóm <strong>BT</strong> nhận mức BT; nhóm <strong>CK</strong>/<strong>DAT</strong> nhận mức tương ứng (mặc định 0).
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-primary"><i class="far fa-save mr-2"></i>Lưu</button>
		</div>
	</form>
</section>
