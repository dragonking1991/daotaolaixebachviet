<?php
	$linkUploadExcel = "index.php?com=xangdau&act=uploadHocvienExcel";
	$ikExtraHtml = '<p class="mb-1"><strong>Mẫu cũ (không có cột tiền):</strong> STT | HỌ VÀ TÊN | KHÓA | Ngày sinh | Số CCCD | NGƯỜI NỘP | PHÂN XE / GIÁO VIÊN | GHI CHÚ / Nhóm (BT/CK/DAT). Ghi thẳng, không có bước xem trước; hồ sơ đã thanh toán được bỏ qua; hồ sơ đồng bộ DAT giữ nguyên khóa, giáo viên và liên kết Đào tạo.</p>'
		.'<p class="mb-1"><strong>Mẫu BC1 có tài chính:</strong> không import vào Đào tạo, không tạo giao dịch hay thay đổi trạng thái thanh toán XD. Hồ sơ đã có giữ nguyên khóa, lịch sử thanh toán và liên kết DAT; nhóm mới chỉ áp dụng cho tính toán XD về sau. Cột tiền không có trong file được giữ nguyên; ô tiền trống trong cột có sẵn được nhập là 0.</p>'
		.'<p class="mb-0"><strong>Ngày nộp hồ sơ:</strong> nhận dd-mm-yyyy (10-07-2026), dd/mm/yyyy, yyyy-mm-dd hoặc ngày Excel; ngày đứng trước tháng. Ô lỗi công thức (#REF!) phải sửa và lưu lại file trước khi import.</p>';
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
			<div class="card-header"><h3 class="card-title"><strong>Import học viên xăng dầu</strong></h3></div>
			<div class="card-body">
				<?php $ikStep = 1; $ikHasPreview = true; include TEMPLATE.LAYOUT.'import_steps.php'; $ikKey = 'xangdau.hocvien'; include TEMPLATE.LAYOUT.'import_guide.php'; ?>
				<div class="ik-note mb-3">
					<i class="fas fa-shield-halved text-primary mr-1" aria-hidden="true"></i>
					<strong>An toàn:</strong> CCCD lặp trong file sẽ chặn toàn bộ file; mẫu BC1 luôn có bước <strong>xem trước → xác nhận</strong>; hệ thống sao lưu trước khi ghi và có thể hoàn tác trong <a href="index.php?com=xangdau&act=importHistory">Lịch sử import</a>.
				</div>
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="khoa_mac_dinh">Khóa XD cho học viên mới <span class="text-muted font-weight-normal">(nếu file không có cột Khóa)</span></label>
						<input id="khoa_mac_dinh" name="khoa_mac_dinh" maxlength="100" class="form-control form-control-sm" placeholder="Ví dụ: 201-B(SS)" aria-describedby="khoa-help">
						<small id="khoa-help" class="text-muted">Để trống: tra khóa Đào tạo khớp cả họ tên và CCCD; nhiều khóa khác nhau sẽ báo lỗi để chọn rõ. Hồ sơ XD đã có giữ khóa hiện tại.</small>
					</div>
					<div class="form-group col-md-6">
						<label for="nhom_mac_dinh">Nhóm cho học viên mới <span class="text-muted font-weight-normal">(nếu file để trống Hệ đào tạo)</span></label>
						<select id="nhom_mac_dinh" name="nhom_mac_dinh" class="form-control form-control-sm"><option value="">Lấy từ file; báo lỗi nếu thiếu</option><option value="BT">BT</option><option value="CK">CK</option><option value="DAT">DAT</option></select>
					</div>
				</div>
				<div class="form-group mb-0">
					<label>File Excel (một file mỗi lần)</label>
					<div class="dt-dropzone" data-accept="xlsx,xls">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls">
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls</span>
						</label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-2"></i>Tải lên &amp; kiểm tra</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="index.php?com=xangdau&act=hocvien"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		</div>
	</form>
</section>
