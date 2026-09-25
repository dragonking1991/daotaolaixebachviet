<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=giaovien">Giáo viên</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadGiaovienExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import danh sách giáo viên (sheet GiaoVien)</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">Đọc theo tên cột: <strong>HoTenDem, TenGV, SoCMT (CCCD), HangGPLX, Hạng đào tạo được phép</strong>... Giáo viên mới được cấp mật khẩu cổng mặc định = CCCD.</div>
				<div class="form-group">
					<label>File Excel (.xlsx)</label>
					<div class="custom-file">
						<input type="file" class="custom-file-input" name="file-excel" id="file-excel" accept=".xlsx" required>
						<label class="custom-file-label" for="file-excel">Chọn file...</label>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</button>
				<a href="index.php?com=daotao&act=giaovien" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
<script>document.getElementById('file-excel').addEventListener('change',function(){var l=this.nextElementSibling;if(l)l.textContent=this.files[0]?this.files[0].name:'Chọn file...';});</script>
