<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=cabinkq">Cabin (kết quả)</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadCabinExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả cabin (mẫu cabin - 13C1)</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">Ghép theo <strong>Mã học viên</strong> trong khóa. Đạt khi ghi chú "Đáp ứng quy định".</div>
				<div class="form-group">
					<label>Khóa <span class="text-danger">*</span></label>
					<select name="id_khoa" class="form-control form-control-sm" required>
						<option value="">— Chọn khóa —</option>
						<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
					</select>
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
				<a href="index.php?com=daotao&act=cabinkq" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
<script>document.getElementById('file-excel').addEventListener('change',function(){var l=this.nextElementSibling;if(l)l.textContent=this.files[0]?this.files[0].name:'Chọn file...';});</script>
