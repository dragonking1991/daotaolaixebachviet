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
					<strong>Hỗ trợ định dạng:</strong> .xlsx và .xls. Dữ liệu cũ không bị xóa khi import lại.
				</div>
				<div class="alert alert-info mb-3">
					<strong>Mẫu BC1 có tài chính:</strong> Họ và tên | Ngày sinh | Số CMND/HC | Hệ đào tạo | Giáo viên | Người nộp hồ sơ; bổ sung
					<strong>Số tiền học phí | Miễn giảm | Số tiền đã đóng | Số tiền còn thiếu | Số tiền tạm ứng | Thanh toán Lương</strong>.<br>
					Tiền tính theo <strong>VNĐ</strong>, không nhân 1.000. Công thức dùng kết quả lưu sẵn trong file Excel.
					Mẫu này luôn có bước <strong>xem trước → xác nhận</strong>; không import vào Đào tạo, không tạo giao dịch hoặc thay đổi trạng thái thanh toán XD.
					Hồ sơ đã có giữ nguyên khóa, lịch sử thanh toán và liên kết DAT. <strong>Nhóm BT/CK/DAT cập nhật theo Hệ đào tạo/Nhóm trong Excel, kể cả đã thanh toán.</strong> Cột/ô nhóm trống giữ nhóm cũ; lựa chọn nhóm mặc định bên dưới chỉ dùng cho học viên mới. Nhóm mới áp dụng cho tính toán XD về sau, không tính lại số tiền đã thanh toán. Cột tiền không có trong file được giữ nguyên; ô tiền trống trong cột có sẵn được nhập là 0.
					<br><strong>Ngày nộp hồ sơ:</strong> nhận dd-mm-yyyy (ví dụ 10-07-2026), dd/mm/yyyy, yyyy-mm-dd hoặc ngày Excel. Ngày đứng trước tháng. Cột/ô trống giữ ngày cũ; không lấy Ngày nhận Hồ sơ thay thế. Ô lỗi công thức (ví dụ #REF!) phải sửa và lưu lại file trước khi import.
				</div>
				<div class="alert alert-danger mb-3">
					<strong>Kiểm tra trùng CCCD:</strong> CCCD bị lặp trong chính file sẽ làm import không được lưu để tránh dữ liệu không rõ ràng.
					Mẫu cũ bỏ qua hồ sơ đã thanh toán. Mẫu tài chính được cập nhật 6 khoản tiền nguồn kể cả hồ sơ đã thanh toán, nhưng không sửa lịch sử tiền XD.
				</div>
				<div class="form-row">
					<div class="form-group col-md-6"><label for="khoa_mac_dinh">Khóa XD cho học viên mới (nếu file không có cột Khóa)</label><input id="khoa_mac_dinh" name="khoa_mac_dinh" maxlength="100" class="form-control form-control-sm" placeholder="Ví dụ: 201-B(SS)"><small class="text-muted">Để trống: tra khóa Đào tạo khớp cả họ tên và CCCD (ưu tiên mã khóa, nếu trống dùng tên khóa). Nhiều khóa khác nhau sẽ báo lỗi để chọn rõ khóa. Không tạo hoặc thay đổi dữ liệu Đào tạo; hồ sơ XD đã có giữ khóa hiện tại.</small></div>
					<div class="form-group col-md-6"><label for="nhom_mac_dinh">Nhóm cho học viên mới nếu file để trống Hệ đào tạo</label><select id="nhom_mac_dinh" name="nhom_mac_dinh" class="form-control form-control-sm"><option value="">Lấy từ file; báo lỗi nếu thiếu</option><option value="BT">BT</option><option value="CK">CK</option><option value="DAT">DAT</option></select></div>
				</div>
				<div class="form-group mb-0">
					<div class="custom-file my-custom-file">
						<input type="file" class="custom-file-input" name="file-excel" id="file-excel" accept=".xlsx,.xls" required>
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
