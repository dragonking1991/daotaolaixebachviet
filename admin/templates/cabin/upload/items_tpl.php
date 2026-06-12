<?php
	$linkUploadExcel = "index.php?com=cabin&act=uploadExcel";
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=cabin&act=man" title="Quản lý khóa học cabin">Quản lý khóa học cabin</a></li>
				<li class="breadcrumb-item active">Import học viên</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<form method="post" action="<?=$linkUploadExcel?>" enctype="multipart/form-data" id="upload-form">
		<div class="card card-primary card-outline text-sm mb-0">
			<div class="card-header">
				<h3 class="card-title"><strong>Import danh sách học viên cabin</strong></h3>
			</div>
			<div class="card-body">
				<div class="alert alert-warning mb-3">
					<strong>Lưu ý:</strong> Hệ thống sẽ đọc tên <strong>Khóa</strong> trực tiếp từ cột E trong Excel.
					Nếu khóa chưa tồn tại, hệ thống sẽ tự tạo khóa mới với thời gian tạm và cảnh báo sau khi import.
					Bạn cần vào <strong>Danh sách khóa</strong> để cập nhật ngày bắt đầu/kết thúc chính xác.
				</div>

				<div class="form-group">
					<div id="drop-zone" style="border: 2px dashed #007bff; border-radius: 8px; padding: 60px 20px; text-align: center; cursor: pointer; background: #f8f9fa; transition: background 0.2s;">
						<p style="font-size: 16px; color: #777; margin: 0;" id="drop-text">Kéo thả file Excel vào đây hoặc bấm để chọn file</p>
					</div>
				</div>

				<div class="form-group">
					<div class="custom-file my-custom-file">
						<input type="file" class="custom-file-input" name="file-excel" id="file-excel" accept=".xls,.xlsx" required>
						<label class="custom-file-label" for="file-excel" id="file-label">Chọn file...</label>
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-success" name="importExcel"><i class="fas fa-upload mr-2"></i>Import</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="index.php?com=cabin&act=man"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		</div>
	</form>

	<p style="font-size: 14px; font-weight: bold; color: #dc3545; padding: 10px;">Mẫu file Excel: STT | Họ tên | Ngày sinh | CCCD | Khóa | Người nộp hồ sơ (cột A-F)</p>
</section>

<script type="text/javascript">
$(document).ready(function(){
	var dropZone = $('#drop-zone');
	var fileInput = $('#file-excel');
	var fileLabel = $('#file-label');
	var dropText = $('#drop-text');

	dropZone.click(function(){
		fileInput.click();
	});

	fileInput.change(function(){
		if(this.files.length > 0){
			var fileName = this.files[0].name;
			fileLabel.text(fileName);
			dropText.text(fileName);
			dropZone.css('background', '#d4edda');
		}
	});

	dropZone.on('dragover', function(e){
		e.preventDefault();
		e.stopPropagation();
		$(this).css('background', '#cce5ff');
	});

	dropZone.on('dragleave', function(e){
		e.preventDefault();
		e.stopPropagation();
		$(this).css('background', '#f8f9fa');
	});

	dropZone.on('drop', function(e){
		e.preventDefault();
		e.stopPropagation();
		var files = e.originalEvent.dataTransfer.files;
		if(files.length > 0){
			fileInput[0].files = files;
			var fileName = files[0].name;
			fileLabel.text(fileName);
			dropText.text(fileName);
			$(this).css('background', '#d4edda');
		}
	});

	$(document).on('dragover drop', function(e){
		e.preventDefault();
	});
});
</script>
