<?php if(!defined('SOURCES')) die("Error");
	$monKw = array(
		'cau_tao' => array('cautao'),
		'ky_thuat' => array('kythuat'),
		'phan_1' => array('phan1', 'phapluat1'),
		'phan_2' => array('phan2', 'phapluat2'),
		'phan_3' => array('phan3', 'phapluat3'),
		'dao_duc' => array('daoduc'),
	);
	$monMeta = array();
	foreach($ds_mon as $mk => $lbl) $monMeta[] = array('v' => $mk, 'l' => $lbl, 'kw' => isset($monKw[$mk]) ? $monKw[$mk] : array());
	$monJson = htmlspecialchars(json_encode($monMeta, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
?>
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
			<div class="card-header"><h3 class="card-title"><strong>Import kết quả lý thuyết (tự nhận môn theo tên file)</strong></h3></div>
			<div class="card-body">
				<div class="alert alert-warning">Đọc theo tên cột: <strong>Mã đăng nhập (CCCD), Tiến độ hoàn thành, Điểm kiểm tra</strong>. Đạt khi tiến độ &gt; 70 và điểm kiểm tra &gt; 5.<br>Môn học được <strong>tự nhận theo tên file</strong> (ví dụ "Cấu tạo", "Kỹ thuật lái", "Phần 1 - Pháp luật", "Đạo đức"…) — có thể chọn lại tay cho từng file.</div>
				<div class="row">
					<div class="col-md-6 form-group">
						<label>Khóa <span class="text-muted">(tùy chọn)</span></label>
						<select name="id_khoa" class="form-control form-control-sm">
							<option value="">— Tất cả khóa (tự nhận theo học viên) —</option>
							<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'].' — '.(int)$k['so_hoc_vien'].' học viên')?></option><?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label>File Excel (.xlsx, .xls) — có thể chọn/thả nhiều file (mỗi file một môn)</label>
					<div class="dt-dropzone" data-back="index.php?com=daotao&act=lythuyet" data-mon="<?=$monJson?>">
						<label class="dt-dz-area">
							<input type="file" class="dt-dz-input" name="file-excel" accept=".xlsx,.xls" multiple>
							<span class="dt-dz-hint"><i class="fas fa-cloud-upload-alt"></i>Kéo &amp; thả hoặc <b>bấm để chọn</b> file .xlsx/.xls (nhiều file cùng lúc)</span>
						</label>
						<ul class="dt-dz-list"></ul>
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
