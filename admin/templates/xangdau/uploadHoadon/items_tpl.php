<?php
	$linkUploadExcel = "index.php?com=xangdau&act=uploadHoadonExcel";
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=xangdau&act=hoadon">Hóa đơn xăng dầu</a></li>
				<li class="breadcrumb-item active">Import hóa đơn</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<form method="post" action="<?=$linkUploadExcel?>" enctype="multipart/form-data" id="upload-form">
		<div class="card card-primary card-outline text-sm mb-0">
			<div class="card-header"><h3 class="card-title"><strong>Import danh sách hóa đơn XD hợp lệ</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning mb-3">
					<strong>Cột theo mẫu:</strong> STT | Số hóa đơn | Ngày | Thông tin bán hàng | Chi tiết | Số tiền HĐ | Biển số xe | HĐ từ trang thuế | <strong>GV</strong> (tên giáo viên) | Note.<br>
					Giáo viên được liên kết theo <strong>tên</strong> ở cột GV. Số tiền dạng số (ví dụ 608.75) được hiểu là đơn vị nghìn (608.750đ).<br>
					<strong>Chống trùng:</strong> theo (Mã HĐ + Ngày HĐ); không cho import đè kỳ đã tồn tại hoặc hóa đơn đã quyết toán.<br>
					<strong>Hỗ trợ định dạng:</strong> .xlsx, .xls, .xlsb.
				</div>

				<div class="form-group">
					<label for="ky">Kỳ tổng hợp (tùy chọn, ghi đè cột Kỳ trong file)</label>
					<input type="text" class="form-control" name="ky" id="ky" placeholder="VD: T5, T6..." style="max-width:220px;">
				</div>

				<div class="form-group mb-0">
					<div class="custom-file my-custom-file">
						<input type="file" class="custom-file-input" name="file-excel" id="file-excel" accept=".xls,.xlsx,.xlsb" required>
						<label class="custom-file-label" for="file-excel" id="file-label">Chọn file...</label>
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-2"></i>Import</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="index.php?com=xangdau&act=hoadon"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		</div>
	</form>
</section>

<script type="text/javascript">
$(document).ready(function(){
	$('#file-excel').change(function(){
		if(this.files.length > 0) $('#file-label').text(this.files[0].name);
	});
});
</script>
