<?php if(!defined('SOURCES')) die("Error"); $isEdit = !empty($item['id']); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=khoa">Khóa đào tạo</a></li>
			<li class="breadcrumb-item active"><?=$isEdit ? 'Sửa khóa' : 'Thêm khóa'?></li>
		</ol>
	</div>
</section>
<section class="content">
	<form method="post" action="index.php?com=daotao&act=khoaSave">
		<input type="hidden" name="id" value="<?=$isEdit ? (int)$item['id'] : 0?>">
		<div class="card card-primary card-outline text-sm" style="max-width:720px">
			<div class="card-header"><h3 class="card-title"><strong><?=$isEdit ? 'Sửa khóa đào tạo' : 'Thêm khóa đào tạo'?></strong></h3></div>
			<div class="card-body">
				<div class="form-group">
					<label>Mã khóa học <span class="text-danger">*</span></label>
					<input type="text" class="form-control form-control-sm" name="data[ma_khoa]" required value="<?=$isEdit ? htmlspecialchars($item['ma_khoa']) : ''?>">
				</div>
				<div class="form-group">
					<label>Tên khóa</label>
					<input type="text" class="form-control form-control-sm" name="data[ten_khoa]" value="<?=$isEdit ? htmlspecialchars($item['ten_khoa']) : ''?>">
				</div>
				<div class="form-group">
					<label>Hạng</label>
					<select class="form-control form-control-sm" name="data[hang]">
						<?php foreach(array('B11','B1','C1','C','CE') as $h): ?>
						<option value="<?=$h?>" <?=($isEdit && $item['hang']==$h)?'selected':''?>><?=$h?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="row">
					<div class="col-md-6 form-group">
						<label>Ngày khai giảng</label>
						<input type="date" class="form-control form-control-sm" name="data[ngay_khaigiang]" value="<?=$isEdit ? htmlspecialchars($item['ngay_khaigiang']) : ''?>">
					</div>
					<div class="col-md-6 form-group">
						<label>Ngày mãn khóa</label>
						<input type="date" class="form-control form-control-sm" name="data[ngay_manhoa]" value="<?=$isEdit ? htmlspecialchars($item['ngay_manhoa']) : ''?>">
					</div>
				</div>
				<div class="form-group">
					<label>Hệ đào tạo</label>
					<input type="text" class="form-control form-control-sm" name="data[he_daotao]" value="<?=$isEdit ? htmlspecialchars($item['he_daotao']) : ''?>">
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu</button>
				<a href="index.php?com=daotao&act=khoa" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
