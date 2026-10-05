<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid"><ol class="breadcrumb float-sm-left"><li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li><li class="breadcrumb-item"><a href="index.php?com=daotao&act=mophong">Mô phỏng</a></li><li class="breadcrumb-item active">Import</li></ol></div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=uploadMophongExcel" enctype="multipart/form-data">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả mô phỏng</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-info">Nhận diện cột CCCD/Mã đăng nhập và Tiến độ hoàn thành. Đạt khi tiến độ &gt; <?=dt_nguong_mophong()?>%. Kết quả được cập nhật theo khóa và CCCD.</div>
				<div class="form-group">
					<label>Khóa <span class="text-danger">*</span></label>
					<select name="id_khoa" class="form-control form-control-sm" required>
						<option value="">— Chọn khóa —</option>
						<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'].' — '.(int)$k['so_hoc_vien'].' học viên')?></option><?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls)</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&amp;act=mophong&amp;id_khoa=<?=$id_khoa_sel?>">
						<label class="dt-dz-area"><input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls" multiple><span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls</span></label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
			<div class="card-footer"><button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</button> <a href="index.php?com=daotao&act=mophong<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm btn-secondary">Quay lại</a></div>
		</div>
	</form>
</section>