<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=htkh">Số giấy HTKH</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadHtkhExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import số giấy HTKH</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">File cần có cột <strong>CCCD</strong> (hoặc Mã HV) và <strong>Số giấy HTKH</strong>; tùy chọn Ngày cấp, Ghi chú. Ghép theo CCCD/Mã HV.</div>
				<div class="form-group">
					<label>Khóa <span class="text-muted">(tùy chọn)</span></label>
					<select name="id_khoa" class="form-control form-control-sm">
						<option value="">— Tất cả khóa (tự nhận theo học viên) —</option>
						<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'].' — '.(int)$k['so_hoc_vien'].' học viên')?></option><?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls)</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=htkh">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls" multiple>
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls</span>
						</label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</button>
				<a href="index.php?com=daotao&act=htkh" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
