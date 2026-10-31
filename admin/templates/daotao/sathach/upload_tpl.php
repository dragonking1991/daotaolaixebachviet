<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=sathach">Kết quả sát hạch</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form id="dt-sh-import-form" method="post" action="index.php?com=daotao&act=uploadSathachExcel" enctype="multipart/form-data">
		<input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả sát hạch tổng hợp</strong></h3></div>
			<div class="card-body">
				<?php $ikStep = 1; $ikHasPreview = false; include TEMPLATE.LAYOUT.'import_steps.php'; $ikKey = 'daotao.sathach'; include TEMPLATE.LAYOUT.'import_guide.php'; ?>
				<div class="alert alert-info">Kết quả được lưu riêng trong mục này, tự chia theo ngày và loại xe; không tạo hay cập nhật học viên/khóa ở các mục Đào tạo khác.</div>
				<div class="form-group">
					<label>File tổng hợp Excel (.xlsx, .xls, .xlsm)</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=sathach">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls,.xlsm" multiple>
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls/.xlsm</span>
						</label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</button>
				<a href="index.php?com=daotao&act=sathach" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
