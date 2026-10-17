<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=sathach">Kết quả sát hạch</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadSathachExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả sát hạch</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">File theo mẫu: <strong>CCCD</strong> (bắt buộc) + Mã HV, Họ tên, Hạng, Mã khóa, Tên khóa, Giáo viên, và các nội dung sát hạch <strong>Điểm LT / LT / Điểm MP / MP / Điểm Hình / Hình / Điểm Đường / Đường / Chữ chi / Điểm Chữ chi / Kết luận</strong>. Ghép theo CCCD.</div>
				<div class="row">
					<div class="col-md-6 form-group">
						<label>Khóa <span class="text-muted">(tùy chọn)</span></label>
						<select name="id_khoa" class="form-control form-control-sm">
							<option value="">— Tất cả khóa (tự nhận theo học viên) —</option>
							<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-6 form-group">
						<label>Ngày sát hạch <span class="text-muted">(áp dụng chung nếu file không có cột ngày)</span></label>
						<input type="text" name="ngay_sh_chung" class="form-control form-control-sm" placeholder="dd/mm/yyyy">
					</div>
				</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls)</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=sathach">
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
				<a href="index.php?com=daotao&act=sathach" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
