<?php
	$linkUploadExcel = "index.php?com=xangdau&act=uploadHocvienExcel";
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=xangdau&act=hocvien">Học viên xăng dầu</a></li>
				<li class="breadcrumb-item active">Import học viên</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<form method="post" action="<?=$linkUploadExcel?>" enctype="multipart/form-data" id="upload-form">
		<div class="card card-primary card-outline text-sm mb-0">
			<div class="card-header"><h3 class="card-title"><strong>Import danh sách học viên theo nhóm</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning mb-3">
					<strong>Cột theo mẫu:</strong> STT | HỌ VÀ TÊN | KHÓA | Ngày tháng năm sinh | Số CCCD | NGƯỌI NỘP | <strong>PHÂN XE / GIÁO VIÊN</strong> | <strong>GHI CHÚ / Nhóm</strong> (BT/CK/DAT).<br>
					Giáo viên được liên kết theo <strong>tên</strong> ở cột PHÂN XE/GIÁO VIÊN. Nhóm nhận diện theo tiêu đề hoặc theo giá trị BT/CK/DAT.<br>
					<strong>Hỗ trợ định dạng:</strong> chỉ nhận file .xlsx. Nếu đang có file .xls hoặc .xlsb, vui lòng mở bằng Excel và lưu lại dưới dạng .xlsx.
				</div>
				<div class="alert alert-danger mb-3">
					<strong>Kiểm tra trùng CCCD:</strong> Nếu có bất kỳ CCCD nào trùng (trong file hoặc đã tồn tại trên hệ thống),
					toàn bộ file sẽ <strong>không được lưu</strong> và hệ thống báo rõ dòng bị trùng.
				</div>

				<div class="form-group mb-0">
					<div class="custom-file my-custom-file">
						<input type="file" class="custom-file-input" name="file-excel" id="file-excel" accept=".xlsx" required>
						<label class="custom-file-label" for="file-excel" id="file-label">Chọn file...</label>
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-2"></i>Import</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="index.php?com=xangdau&act=hocvien"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		</div>
		<?php if(!empty($_SESSION['xd_hocvien_import_error'])) { $xdImportError = $_SESSION['xd_hocvien_import_error']; unset($_SESSION['xd_hocvien_import_error']); ?>
		<div class="alert alert-danger mx-3 mb-3" role="alert">
			<strong>Import không thành công:</strong><br><?=$xdImportError?>
		</div>
		<?php } ?>
	</form>
</section>

<script type="text/javascript">
$(document).ready(function(){
	$('#file-excel').change(function(){
		if(this.files.length > 0) $('#file-label').text(this.files[0].name);
	});
});
</script>
