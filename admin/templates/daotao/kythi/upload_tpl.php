<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=kythi">Ngày kiểm tra</a></li>
			<li class="breadcrumb-item active">Import kết quả</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=kythiImportExcel&id=<?=$ky['id']?>" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả kỳ: <?=htmlspecialchars($ky['ten'] ?: '')?></strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">Nạp file kết quả từ TTSH (mẫu đã xuất). Ghép theo <strong>CCCD</strong>: cập nhật học viên trong kỳ, đồng thời chèn mới học viên khóa cũ (vắng/rớt) để lưu trữ.</div>
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
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import kết quả</button>
				<a href="index.php?com=daotao&act=kythiDetail&id=<?=$ky['id']?>" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
