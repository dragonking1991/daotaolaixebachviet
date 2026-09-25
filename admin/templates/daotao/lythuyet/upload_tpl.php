<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=lythuyet">Lý thuyết</a></li>
			<li class="breadcrumb-item active">Import môn</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadLythuyetExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả một môn lý thuyết</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">Đọc theo tên cột: <strong>Mã đăng nhập (CCCD), Tiến độ hoàn thành, Điểm kiểm tra</strong>. Đạt khi tiến độ &gt; 70 và điểm kiểm tra &gt; 5.</div>
				<div class="row">
					<div class="col-md-6 form-group">
						<label>Khóa <span class="text-danger">*</span></label>
						<select name="id_khoa" class="form-control form-control-sm" required>
							<option value="">— Chọn khóa —</option>
							<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-6 form-group">
						<label>Môn học <span class="text-danger">*</span></label>
						<select name="mon" class="form-control form-control-sm" required>
							<option value="">— Chọn môn —</option>
							<?php foreach($ds_mon as $mk=>$lbl): ?><option value="<?=$mk?>"><?=htmlspecialchars($lbl)?></option><?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label>File Excel (.xlsx)</label>
					<div class="custom-file">
						<input type="file" class="custom-file-input" name="file-excel" id="file-excel" accept=".xlsx" required>
						<label class="custom-file-label" for="file-excel">Chọn file...</label>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</button>
				<a href="index.php?com=daotao&act=lythuyet" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
<script>document.getElementById('file-excel').addEventListener('change',function(){var l=this.nextElementSibling;if(l)l.textContent=this.files[0]?this.files[0].name:'Chọn file...';});</script>
