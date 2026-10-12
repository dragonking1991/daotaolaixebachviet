<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=kythi">Ngày kiểm tra</a></li>
			<li class="breadcrumb-item active">Import danh sách vào kỳ</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=kythiImportRoster&id=<?=$ky['id']?>" enctype="multipart/form-data">
		<div class="card card-info card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import danh sách học viên vào kỳ: <?=htmlspecialchars($ky['ten'] ?: '')?></strong></h3></div>
			<div class="card-body">
				<div class="alert alert-info">Nạp file danh sách học viên để thêm vào kỳ kiểm tra (áp dụng cho khóa cũ chưa có dữ liệu trong hệ thống). Cột nhận diện: <strong>Mã HV, Họ và tên, Năm sinh, CCCD, Hạng, Mã khóa, Tên khóa, Giáo viên</strong>. Ghép theo <strong>CCCD</strong>; bỏ qua học viên đã có trong kỳ. Đây là <strong>import danh sách</strong>, không phải import kết quả.</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls)</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=kythiDetail&id=<?=$ky['id']?>">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls">
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls</span>
						</label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-import mr-1"></i>Import danh sách</button>
				<a href="index.php?com=daotao&act=kythiDetail&id=<?=$ky['id']?>" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
