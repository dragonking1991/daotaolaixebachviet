<?php
	$linkSave = "index.php?com=payroll_config&act=save";
	$labels = array(
		'td' => 'Đơn giá TĐ (đ/học viên)',
		'ss' => 'Đơn giá SS (đ/học viên)',
		'c1' => 'Đơn giá C1 (đ/học viên)',
		'ce' => 'Đơn giá CE (đ/học viên)',
	);
	$keys = array(
		'td' => 'payroll_rate_td',
		'ss' => 'payroll_rate_ss',
		'c1' => 'payroll_rate_c1',
		'ce' => 'payroll_rate_ce',
	);
?>
<!-- Content Header -->
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Cấu hình đơn giá học viên</li>
			</ol>
		</div>
	</div>
</section>

<!-- Main content -->
<section class="content">
	<form class="validation-form" novalidate method="post" action="<?=$linkSave?>">
		<div class="card-footer text-sm sticky-top">
			<button type="submit" class="btn btn-sm bg-gradient-primary submit-check"><i class="far fa-save mr-2"></i>Lưu</button>
		</div>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Đơn giá học viên (đồng/học viên)</h3>
				<small class="text-muted ml-2">Dùng để tính lương giáo viên trên phiếu tra cứu</small>
			</div>
			<div class="card-body">
				<div class="row">
					<?php foreach($keys as $short => $key): ?>
					<div class="form-group col-md-3 col-sm-6">
						<label for="rate_<?=$short?>"><?=htmlspecialchars($labels[$short])?></label>
						<input type="text" class="form-control" id="rate_<?=$short?>"
							name="data[<?=$key?>]"
							value="<?=number_format((int)($item[$short] ?? 0), 0, ',', '.')?>"
							placeholder="Nhập đơn giá">
					</div>
					<?php endforeach; ?>
				</div>
				<p class="text-muted text-sm mt-2">
					Công thức tính lương giáo viên:<br>
					<code>Lương CE = số CE × đơn giá CE</code><br>
					<code>L theo DS phân xe = (TĐ × đơn giá TĐ) + (SS × đơn giá SS) + (C1 × đơn giá C1)</code>
				</p>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-primary submit-check"><i class="far fa-save mr-2"></i>Lưu</button>
		</div>
	</form>
</section>
