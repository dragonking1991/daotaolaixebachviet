<?php if(!defined('SOURCES')) die("Error"); $shSummaryMode=($_REQUEST['import_mode'] ?? '')==='summary'; ?>
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
	<form id="dt-sh-import-form" method="post" action="index.php?com=daotao&act=uploadSathachExcel" enctype="multipart/form-data">
		<input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>">
		<div class="card card-primary card-outline text-sm">
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả sát hạch</strong></h3></div>
			<div class="card-body">
				<div class="form-group"><label>Chế độ import</label><select name="import_mode" class="form-control form-control-sm"><option value="period" <?=$shSummaryMode?'':'selected'?>>Theo kỳ đã tạo (mẫu điểm)</option><option value="summary" <?=$shSummaryMode?'selected':''?>>Tổng hợp theo ngày thi (LT / MP / TH / Đ)</option></select></div>
				<div class="form-group" data-sh-fixed <?=$shSummaryMode?'hidden':''?>><label>Kỳ sát hạch</label><select <?=$shSummaryMode?'disabled':'required'?> name="id_ky" class="form-control form-control-sm"><option value="">Chọn kỳ sát hạch</option><?php foreach($sh_periods as $period): ?><option value="<?=(int)$period['id']?>" <?=(int)$period['id']===$sh_period_sel?'selected':''?>><?=htmlspecialchars($period['ngay'].' / '.$period['ten'].' / '.($period['loai']==='moto'?'Mô tô':'Ô tô'))?></option><?php endforeach; ?></select></div>
				<div class="row">
					<div class="col-md-6 form-group">
						<label>Khóa <span class="text-muted">(tùy chọn)</span></label>
						<select name="id_khoa" class="form-control form-control-sm">
							<option value="">— Tất cả khóa (tự nhận theo học viên) —</option>
							<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-6 form-group" data-sh-fixed <?=$shSummaryMode?'hidden':''?>>
						<label>Kỳ sát hạch mới</label>
						<a class="btn btn-sm btn-outline-primary" href="index.php?com=daotao&act=sathach"><i class="fas fa-calendar-plus mr-1"></i>Tạo kỳ sát hạch</a>
					</div>
				</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls, .xlsm)</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=sathach">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls,.xlsm" multiple>
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls/.xlsm</span>
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
<script>
(function(){
	var form=document.getElementById('dt-sh-import-form');
	var mode=form.querySelector('[name="import_mode"]');
	var period=form.querySelector('[name="id_ky"]');
	function updateMode(){
		var summary=mode.value==='summary';
		period.disabled=summary;
		period.required=!summary;
		Array.prototype.forEach.call(form.querySelectorAll('[data-sh-fixed]'),function(section){section.hidden=summary;});
	}
	mode.addEventListener('change',updateMode);
	updateMode();
})();
</script>
