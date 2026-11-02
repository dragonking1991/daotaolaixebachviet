<?php
	$linkUploadExcel = "index.php?com=xangdau&act=uploadHoadonExcel";
	$xdUploadReturn = xd_safe_ret($_GET['ret'] ?? '', 'index.php?com=xangdau&act=hoadon');
	$ikExtraHtml = 'Giáo viên liên kết theo <strong>tên</strong> ở cột GV. Số tiền dạng số (vd 608.75) hiểu là nghìn đồng (608.750đ); chuỗi có dấu ngăn cách (vd "1.000.000") là số đầy đủ. Chống trùng theo <strong>Mã HĐ + Ngày HĐ + Giáo viên</strong>; hóa đơn đã quyết toán không bị sửa. Hỗ trợ .xlsx, .xls, .xlsb.';
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="<?=htmlspecialchars($xdUploadReturn, ENT_QUOTES, 'UTF-8')?>">Hóa đơn xăng dầu</a></li>
				<li class="breadcrumb-item active">Import hóa đơn</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<form method="post" action="<?=$linkUploadExcel?>" enctype="multipart/form-data" id="upload-form" onsubmit="if(this.elements['replace_period'] && this.elements['replace_period'].checked){return confirm('Thay thế toàn bộ hóa đơn chưa quyết toán của kỳ ' + (this.elements['ky'].value.trim() || '(chưa gán kỳ)') + '? Hóa đơn đã quyết toán được giữ nguyên.');}return true;">
		<input type="hidden" name="ret" value="<?=htmlspecialchars(rawurlencode($xdUploadReturn), ENT_QUOTES, 'UTF-8')?>">
		<input type="hidden" name="upload_session" value="<?=bin2hex(random_bytes(8))?>">
		<div class="card card-primary card-outline text-sm mb-0">
			<div class="card-header"><h3 class="card-title"><strong>Import danh sách hóa đơn XD</strong></h3></div>
			<div class="card-body">
				<?php $ikStep = 1; $ikHasPreview = false; include TEMPLATE.LAYOUT.'import_steps.php'; $ikKey = 'xangdau.hoadon'; include TEMPLATE.LAYOUT.'import_guide.php'; ?>
				<div class="form-row">
					<div class="form-group col-md-4">
						<label for="ky">Kỳ tổng hợp <span class="text-muted font-weight-normal">(tùy chọn)</span></label>
						<input type="text" class="form-control form-control-sm" name="ky" id="ky" placeholder="VD: T5, T6..." aria-describedby="ky-help">
						<small id="ky-help" class="text-muted">Ghi đè cột Kỳ trong file; dùng kỳ này làm phạm vi thay thế.</small>
					</div>
					<div class="form-group col-md-8 d-flex align-items-center">
						<div class="custom-control custom-checkbox mt-md-3">
							<input type="checkbox" class="custom-control-input" name="replace_period" id="replace_period" value="1" checked onchange="if(this.checked) document.getElementById('resync').checked=false;">
							<label class="custom-control-label" for="replace_period"><strong>Thay thế danh sách hóa đơn kỳ này</strong> — mặc định bật; chỉ thay hóa đơn chưa quyết toán và có sao lưu.</label>
						</div>
					</div>
					<div class="form-group col-12 mb-0">
						<div class="custom-control custom-checkbox mt-md-3">
							<input type="checkbox" class="custom-control-input" name="resync" id="resync" value="1" onchange="if(this.checked) document.getElementById('replace_period').checked=false;">
							<label class="custom-control-label" for="resync"><strong>Chỉ đồng bộ thông tin trùng</strong> — không xóa các hóa đơn khác trong kỳ.</label>
						</div>
					</div>
				</div>
				<div class="form-group mb-0">
					<label>File Excel — có thể chọn/thả nhiều file, hệ thống import lần lượt từng file</label>
					<div class="dt-dropzone" data-back="<?=htmlspecialchars($xdUploadReturn, ENT_QUOTES, 'UTF-8')?>" data-accept="xlsx,xls,xlsb">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls,.xlsb" multiple>
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls/.xlsb</span>
						</label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-2"></i>Import</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="<?=htmlspecialchars($xdUploadReturn, ENT_QUOTES, 'UTF-8')?>"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		</div>
	</form>
</section>
