<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=xe">Xe tập lái</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadXeExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import danh sách xe (sheet XE)</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">Đọc cột theo tên tiêu đề: <strong>Biển số xe, Hạng xe tập lái, Giáo viên</strong>... Nạp lại sẽ cập nhật theo biển số.</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls) — có thể chọn/thả nhiều file</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=xe">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls" multiple>
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls (nhiều file cùng lúc)</span>
						</label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</button>
				<a href="index.php?com=daotao&act=xe" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
