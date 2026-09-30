<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=hocvien">Học viên</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadHocvienExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import danh sách học viên (mẫu 2)</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">File phải có <strong>cả Mã đăng ký (mã học viên) và Số CMND/CCCD</strong>. Dòng thiếu một trong hai sẽ bị báo lỗi và không nạp.</div>
				<div class="form-group">
					<label>Khóa <span class="text-muted">(tự nhận từ cột “Khóa” trong file; để trống nếu file đã có)</span></label>
					<select name="id_khoa" class="form-control form-control-sm">
						<option value="">— Tự nhận từ file / hoặc chọn khóa —</option>
						<?php foreach($ds_khoa as $k): ?>
						<option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls) — có thể chọn/thả nhiều file</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=hocvien">
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
				<button type="submit" name="preview" value="1" class="btn btn-sm bg-gradient-info"><i class="fas fa-eye mr-1"></i>Xem trước</button>
				<a href="index.php?com=daotao&act=hocvien" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
