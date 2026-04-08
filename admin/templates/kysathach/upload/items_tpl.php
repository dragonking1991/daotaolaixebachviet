<?php
	$linkUploadExcel = "index.php?com=kysathach&act=uploadExcel";
?>
<!-- Content Header -->
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=kysathach&act=man" title="Quản lý Kỳ Sát Hạch">Quản lý Kỳ Sát Hạch</a></li>
				<li class="breadcrumb-item active">Tải File</li>
			</ol>
		</div>
	</div>
</section>

<!-- Main content -->
<section class="content">
	<form method="post" action="<?=$linkUploadExcel?>" enctype="multipart/form-data" id="upload-form">
		<div class="card card-primary card-outline text-sm mb-0">
			<div class="card-header">
				<h3 class="card-title"><strong>Tải File</strong></h3>
			</div>
			<div class="card-body">
				<div class="form-group row">
					<label class="col-sm-3 col-form-label text-right font-weight-bold">Kỳ sát hạch:</label>
					<div class="col-sm-6">
						<select class="form-control" name="id_kysathach" id="id_kysathach" required>
							<option value="">--- Chọn ---</option>
							<?php if(isset($items_ky) && count($items_ky)) { ?>
								<?php foreach($items_ky as $ky) { ?>
									<option value="<?=$ky['id']?>"><?=date('d-m-Y', strtotime($ky['ngay_sathach']))?> - <?=$ky['ten_viettat']?> - <?=$ky['loai_sathach']?></option>
								<?php } ?>
							<?php } ?>
						</select>
					</div>
				</div>

				<div class="form-group">
					<div id="drop-zone" style="border: 2px dashed #007bff; border-radius: 8px; padding: 60px 20px; text-align: center; cursor: pointer; background: #f8f9fa; transition: background 0.2s;">
						<p style="font-size: 16px; color: #999; margin: 0;" id="drop-text">Drag & drop files here …</p>
					</div>
				</div>

				<div class="form-group">
					<div class="custom-file my-custom-file">
						<input type="file" class="custom-file-input" name="file-excel" id="file-excel" accept=".xls,.xlsx">
						<label class="custom-file-label" for="file-excel" id="file-label">Select file...</label>
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-success" name="importExcel"><i class="fas fa-upload mr-2"></i>Import</button>
		</div>
	</form>

	<p style="font-size: 14px; font-weight: bold; color: red; padding-top: 10px; padding-left: 10px;">Mẫu file Excel: STT | Họ tên | Ngày sinh | Số căn cước | Hạng GPLX | Số tiền</p>
</section>

<script type="text/javascript">
$(document).ready(function(){
	var dropZone = $('#drop-zone');
	var fileInput = $('#file-excel');
	var fileLabel = $('#file-label');
	var dropText = $('#drop-text');

	// Click drop zone to open file dialog
	dropZone.click(function(){
		fileInput.click();
	});

	// File input change
	fileInput.change(function(){
		if(this.files.length > 0){
			var fileName = this.files[0].name;
			fileLabel.text(fileName);
			dropText.text(fileName);
			dropZone.css('background', '#d4edda');
		}
	});

	// Drag and drop
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

	// Prevent default drag behaviors on document
	$(document).on('dragover drop', function(e){
		e.preventDefault();
	});
});
</script>
