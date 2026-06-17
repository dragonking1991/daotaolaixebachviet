<?php
	$isEdit = isset($item_dk) && isset($item_dk['id']) && (int)$item_dk['id'] > 0;
	$id_kh = isset($kh_info['id']) ? (int)$kh_info['id'] : 0;
	$returnAct = isset($_REQUEST['return_act']) ? trim($_REQUEST['return_act']) : '';
	if(!in_array($returnAct, array('dangky', 'full_dangky'))) $returnAct = 'dangky';
	$linkMan = "index.php?com=cabin&act=".$returnAct."&id=".$id_kh."&p=".$curPage;
	$linkSave = "index.php?com=cabin&act=save_dangky&p=".$curPage;
	$selectedHocvien = $isEdit ? (int)$item_dk['id_hocvien'] : 0;
	$selectedNgayHoc = $isEdit ? date('d/m/Y', strtotime($item_dk['ngay_hoc'])) : '';
	$selectedCa = $isEdit ? (int)$item_dk['ca'] : 0;
	$minNgayHoc = date('d/m/Y', strtotime($kh_info['ngay_batdau']));
	$maxNgayHoc = date('d/m/Y', strtotime($kh_info['ngay_ketthuc']));
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=cabin&act=man" title="Quản lý khóa học cabin">Quản lý khóa học cabin</a></li>
				<li class="breadcrumb-item"><a href="<?=$linkMan?>" title="Đăng ký lịch học"><?=htmlspecialchars($kh_info['ten'])?></a></li>
				<li class="breadcrumb-item active"><?php if($isEdit) echo 'Chỉnh sửa đăng ký'; else echo 'Thêm đăng ký'; ?></li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<form method="post" action="<?=$linkSave?>" enctype="multipart/form-data">
		<div class="card-footer text-sm sticky-top">
			<button type="submit" class="btn btn-sm bg-gradient-primary"><i class="far fa-save mr-2"></i>Lưu</button>
			<button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
			<a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
		</div>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title"><?php if($isEdit) echo 'Chỉnh sửa đăng ký lịch học'; else echo 'Thêm đăng ký lịch học'; ?></h3>
			</div>
			<div class="card-body">
				<div class="form-group">
					<label>Khóa học cabin:</label>
					<input type="text" class="form-control" value="<?=htmlspecialchars($kh_info['ten'])?>" readonly>
					<input type="hidden" name="data[id_khoahoc]" value="<?=$id_kh?>">
				</div>
				<div class="form-group">
					<label for="id_hocvien">Học viên <span class="text-danger">*</span></label>
					<select class="form-control select2" name="data[id_hocvien]" id="id_hocvien" required>
						<option value="">Chọn học viên</option>
						<?php if(!empty($hocvien_list)) { foreach($hocvien_list as $hv) { ?>
							<option value="<?=(int)$hv['id']?>" <?php if($selectedHocvien == (int)$hv['id']) echo 'selected'; ?>><?=htmlspecialchars($hv['tenvi'])?> - <?=htmlspecialchars($hv['cccd'])?></option>
						<?php }} ?>
					</select>
				</div>
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="ngay_hoc">Ngày học <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="data[ngay_hoc]" id="ngay_hoc" value="<?=htmlspecialchars($selectedNgayHoc)?>" placeholder="dd/mm/yyyy" readonly required>
						<small class="form-text text-muted">Khoảng: <?=$minNgayHoc?> - <?=$maxNgayHoc?> (<?=date('Y/m/d', strtotime($kh_info['ngay_batdau']))?>...<?=date('Y/m/d', strtotime($kh_info['ngay_ketthuc']))?>)</small>
					</div>
					<div class="form-group col-md-6">
						<label for="ca">Ca học <span class="text-danger">*</span></label>
						<select class="form-control" name="data[ca]" id="ca" required>
							<option value="">Chọn ca</option>
							<?php if(!empty($slots)) { foreach($slots as $k => $v) { ?>
								<option value="<?=(int)$k?>" <?php if($selectedCa === (int)$k) echo 'selected'; ?>><?=htmlspecialchars($v['label'])?> (<?=htmlspecialchars($v['gio_b_d'])?>-<?=htmlspecialchars($v['gio_kt'])?>)</option>
							<?php }} ?>
						</select>
						<small class="form-text text-muted">Ca hiển thị theo ngày đã chọn (Thứ 2-6: Ca 1,2,3; Thứ 7: Ca 1,2).</small>
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-primary"><i class="far fa-save mr-2"></i>Lưu</button>
			<button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
			<a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
			<input type="hidden" name="id" value="<?php if($isEdit) echo (int)$item_dk['id']; else echo 0; ?>">
			<input type="hidden" name="return_act" value="<?=$returnAct?>">
		</div>
	</form>
</section>

<script type="text/javascript">
	function parseVNDateToDow(dateStr)
	{
		if(!dateStr) return 0;
		var parts = dateStr.split('/');
		if(parts.length !== 3) return 0;
		var day = parseInt(parts[0], 10);
		var month = parseInt(parts[1], 10);
		var year = parseInt(parts[2], 10);
		if(!day || !month || !year) return 0;
		var d = new Date(year, month - 1, day);
		if(isNaN(d.getTime())) return 0;
		var jsDow = d.getDay();
		return jsDow === 0 ? 7 : jsDow;
	}

	function allowedCaByDow(dow)
	{
		if(dow >= 1 && dow <= 5) return [1, 2, 4];
		if(dow === 6) return [1, 2];
		return [];
	}

	function refreshCaOptions()
	{
		var ngayHoc = jQuery('#ngay_hoc').val();
		var selected = parseInt(jQuery('#ca').val() || 0, 10);
		var dow = parseVNDateToDow(ngayHoc);
		var allowed = allowedCaByDow(dow);

		jQuery('#ca option').each(function(){
			var val = parseInt(jQuery(this).val() || 0, 10);
			if(!val) return;
			jQuery(this).prop('disabled', allowed.indexOf(val) === -1);
		});

		if(selected && allowed.indexOf(selected) === -1)
		{
			jQuery('#ca').val('');
		}
	}

	jQuery(document).ready(function(){
		jQuery('#ngay_hoc').datetimepicker({
			timepicker: false,
			format: 'd/m/Y',
			formatDate: 'd/m/Y',
			minDate: '<?=date('d/m/Y', strtotime($kh_info['ngay_batdau']))?>',
			maxDate: '<?=date('d/m/Y', strtotime($kh_info['ngay_ketthuc']))?>'
		});

		jQuery('#ngay_hoc').on('change', function(){
			refreshCaOptions();
		});

		refreshCaOptions();
	});
</script>
