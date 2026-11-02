<?php if(!defined('SOURCES')) die("Error"); $selectedPeriod = (int)($sh_period_sel ?? 0); $uploadBack = 'index.php?com=daotao&act=sathach'.($selectedPeriod ? '&id_ky='.$selectedPeriod : ''); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="<?=htmlspecialchars($uploadBack, ENT_QUOTES, 'UTF-8')?>">Kết quả sát hạch</a></li>
			<li class="breadcrumb-item active">Import</li>
		</ol>
	</div>
</section>
<section class="content">
	<form id="dt-sh-import-form" method="post" action="index.php?com=daotao&act=uploadSathachExcel" enctype="multipart/form-data">
		<input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả sát hạch tổng hợp</strong></h3></div>
			<div class="card-body">
				<?php $ikStep = 1; $ikHasPreview = false; include TEMPLATE.LAYOUT.'import_steps.php'; $ikKey = 'daotao.sathach'; include TEMPLATE.LAYOUT.'import_guide.php'; ?>
				<div class="alert alert-info">Chọn một kỳ đã tạo để dùng ngày và loại xe của kỳ đó cho file mẫu Bách Việt. Không chọn kỳ chỉ dùng với file có sẵn cột ngày sát hạch; dữ liệu không tạo hay cập nhật học viên/khóa ở các mục Đào tạo khác.</div>
				<div class="form-group">
					<label for="sh-import-period">Ngày sát hạch / loại xe</label>
					<select id="sh-import-period" name="id_ky" class="form-control form-control-sm">
						<option value="0" <?=$selectedPeriod===0?'selected':''?>>Tự nhận ngày từ file (file phải có cột Ngày SH)</option>
						<?php foreach(($sh_periods ?? array()) as $period): ?><option value="<?=(int)$period['id']?>" <?=(int)$period['id']===$selectedPeriod?'selected':''?>><?=htmlspecialchars(dt_sathach_display_date($period['ngay']).' / '.($period['loai']==='moto'?'Mô tô':'Ô tô'))?></option><?php endforeach; ?>
					</select>
					<small class="form-text text-muted">Khi chọn kỳ, ngày trong kỳ sẽ được gán cho kết quả; hạng trong file phải đúng loại ô tô/mô tô đã chọn.</small>
				</div>
				<div class="form-group">
					<label>File tổng hợp Excel (.xlsx, .xls, .xlsm)<?=$selectedPeriod ? ' — một file cho kỳ đã chọn' : ''?></label>
					<div class="dt-dropzone" data-back="<?=htmlspecialchars($uploadBack, ENT_QUOTES, 'UTF-8')?>">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls,.xlsm" <?=$selectedPeriod ? '' : 'multiple'?>>
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls/.xlsm</span>
						</label>
						<ul class="dt-dz-list"></ul>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</button>
				<a href="<?=htmlspecialchars($uploadBack, ENT_QUOTES, 'UTF-8')?>" class="btn btn-sm bg-gradient-secondary text-white">Quay lại</a>
			</div>
		</div>
	</form>
</section>
