<?php
if(!defined('SOURCES')) die("Error");
$loE = $lo_edit_ngay;
$loDefHang = $loE ? $loE['hang'] : lo_hang_list()[0];
$loDefH = $loE ? array((int)$loE['gio_bd'], (int)$loE['gio_kt']) : lo_hang_default_hours($loDefHang);
$loByDay = array();
foreach($lo_ngay as $n) $loByDay[$n['ngay']][] = $n;
$loXepByNgay = array();
foreach($lo_grid as $idNgay => $xes) { $loXepByNgay[$idNgay] = 0; foreach($xes as $hrs) $loXepByNgay[$idNgay] += count($hrs); }
$loDefaults = array();
foreach(lo_hang_list() as $h) $loDefaults[$h] = lo_hang_default_hours($h);
?>
<div class="row">
	<?php if($loCanEdit): ?>
	<div class="col-lg-4">
		<div class="card <?=$loE ? 'card-warning' : 'card-primary'?> lo-sticky" id="lo-ngay-form">
			<div class="card-header"><h3 class="card-title"><i class="fas <?=$loE ? 'fa-edit' : 'fa-plus-circle'?> mr-1"></i><?=$loE ? 'Sửa ngày tập '.lo_date_vn($loE['ngay']) : 'Thêm ngày tập'?></h3></div>
			<form method="post" action="index.php?com=daotao&amp;act=lichonNgaySave" data-lo-ngay-form>
				<input type="hidden" name="_csrf" value="<?=$loCsrf?>">
				<input type="hidden" name="id_dot" value="<?=$loId?>">
				<input type="hidden" name="id" value="<?=$loE ? (int)$loE['id'] : 0?>">
				<div class="card-body">
					<?php if(!$loE): ?>
					<div class="form-row">
						<div class="form-group col-6">
							<label for="lo-n-tu">Ngày tập <span class="text-danger">*</span></label>
							<input type="date" id="lo-n-tu" name="ngay_tu" class="form-control" required max="<?=$loEsc($lo_dot['ngay_sh'])?>">
						</div>
						<div class="form-group col-6">
							<label for="lo-n-den">Đến ngày <span class="text-muted font-weight-normal">(tùy chọn)</span></label>
							<input type="date" id="lo-n-den" name="ngay_den" class="form-control" max="<?=$loEsc($lo_dot['ngay_sh'])?>">
						</div>
					</div>
					<?php endif; ?>
					<div class="form-group">
						<label>Hạng xe <span class="text-danger">*</span></label>
						<div class="lo-hang-pick">
							<?php foreach(lo_hang_list() as $h): ?>
							<label class="lo-chip-radio"><input type="radio" name="hang" value="<?=$loEsc($h)?>" <?=$h === $loDefHang ? 'checked' : ''?> required><span><?=$loEsc($h)?></span></label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="form-row">
						<div class="form-group col-6">
							<label for="lo-n-bd">Từ giờ</label>
							<select id="lo-n-bd" name="gio_bd" class="form-control"><?php foreach($loHours as $h): ?><option value="<?=$h?>" <?=$h === $loDefH[0] ? 'selected' : ''?>><?=lo_hour_label($h)?></option><?php endforeach; ?></select>
						</div>
						<div class="form-group col-6">
							<label for="lo-n-kt">Đến giờ</label>
							<select id="lo-n-kt" name="gio_kt" class="form-control"><?php foreach(range(6, 24) as $h): ?><option value="<?=$h?>" <?=$h === $loDefH[1] ? 'selected' : ''?>><?=lo_hour_label($h)?></option><?php endforeach; ?></select>
						</div>
					</div>
					<div class="form-group">
						<label for="lo-n-xe">Số xe của sân <span class="text-danger">*</span></label>
						<input type="text" id="lo-n-xe" name="xe_list" class="form-control" required value="<?=$loE ? $loEsc($loE['xe_list']) : ''?>" placeholder="Ví dụ: 21, 23, 24, 25 hoặc 89-99" autocomplete="off">
						<small class="form-text text-muted">Phân cách bằng dấu phẩy; dùng dấu gạch nối cho dãy số liên tiếp.</small>
					</div>
					<div class="lo-preview" data-lo-preview><i class="fas fa-calculator mr-1"></i><span>Nhập số xe để tính tổng giờ.</span></div>
					<?php if($loE): ?><div class="small text-muted mt-2"><i class="fas fa-info-circle mr-1"></i>Giờ đã xếp nằm ngoài cấu hình mới (xe bị bỏ, ngoài khung giờ) sẽ được trả lại cho giáo viên.</div><?php endif; ?>
				</div>
				<div class="card-footer d-flex">
					<button type="submit" class="btn <?=$loE ? 'btn-warning' : 'btn-primary'?> mr-2"><i class="fas fa-save mr-1"></i><?=$loE ? 'Lưu thay đổi' : 'Thêm ngày tập'?></button>
					<?php if($loE): ?><a href="<?=$loEsc($loTabUrl('cauhinh'))?>" class="btn btn-outline-secondary">Hủy</a><?php endif; ?>
				</div>
			</form>
		</div>
	</div>
	<?php endif; ?>
	<div class="<?=$loCanEdit ? 'col-lg-8' : 'col-12'?>">
		<?php if(!$lo_ngay): ?>
		<div class="card"><div class="card-body text-center py-5">
			<div class="text-muted mb-3"><i class="fas fa-car-side fa-3x"></i></div>
			<h4 class="h5">Chưa có ngày tập nào</h4>
			<p class="text-muted mb-0">Sau khi sân chốt số lượng xe và ngày tập, nhập từng ngày (hoặc một khoảng ngày) với hạng, khung giờ và số xe.<br>Ví dụ: 10 xe số sàn × 12 giờ = 120 giờ.</p>
		</div></div>
		<?php else: ?>
		<div class="card">
			<div class="card-header"><h3 class="card-title"><i class="far fa-calendar-alt mr-1"></i>Ngày tập đã cấu hình</h3></div>
			<div class="table-responsive">
				<table class="table table-sm mb-0 lo-ngay-table">
					<thead><tr><th>Ngày</th><th>Hạng</th><th>Khung giờ</th><th>Số xe</th><th class="text-right">Giờ sân</th><th class="text-right">Đã xếp</th><?php if($loCanEdit): ?><th></th><?php endif; ?></tr></thead>
					<tbody>
					<?php foreach($loByDay as $day => $rows): foreach($rows as $i => $n): $xep = $loXepByNgay[$n['id']] ?? 0; ?>
					<tr class="<?=$i === 0 ? 'lo-day-first' : ''?> <?=$loE && (int)$loE['id'] === (int)$n['id'] ? 'table-warning' : ''?>">
						<?php if($i === 0): ?><td rowspan="<?=count($rows)?>" class="lo-day-cell"><strong><?=lo_weekday($day)?></strong><small class="d-block text-muted"><?=lo_date_vn($day)?></small></td><?php endif; ?>
						<td><span class="badge badge-light border"><?=$loEsc($n['hang'])?></span></td>
						<td class="text-nowrap"><?=lo_range_label($n['gio_bd'], $n['gio_kt'])?> <small class="text-muted">(<?=$n['so_gio_xe']?>h)</small></td>
						<td><div class="lo-xe-chips"><?php foreach($n['xe'] as $x): ?><span><?=$loEsc($x)?></span><?php endforeach; ?></div><small class="text-muted"><?=count($n['xe'])?> xe</small></td>
						<td class="text-right"><strong><?=$n['tong_gio']?></strong></td>
						<td class="text-right"><span class="<?=$xep >= $n['tong_gio'] ? 'text-success font-weight-bold' : ''?>"><?=$xep?></span></td>
						<?php if($loCanEdit): ?>
						<td class="text-right text-nowrap">
							<a href="<?=$loEsc($loTabUrl('cauhinh', array('edit_ngay' => $n['id'])))?>#lo-ngay-form" class="btn btn-xs btn-outline-primary" title="Sửa"><i class="fas fa-edit"></i></a>
							<form method="post" action="index.php?com=daotao&amp;act=lichonNgayDelete" class="d-inline" onsubmit="return confirm('Xóa ngày <?=lo_date_vn($n['ngay'])?> – <?=$loEsc($n['hang'])?>?<?=$xep ? '\n'.$xep.' giờ đã xếp sẽ được trả lại cho giáo viên.' : ''?>')">
								<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="id" value="<?=(int)$n['id']?>">
								<button type="submit" class="btn btn-xs btn-outline-danger" title="Xóa"><i class="fas fa-trash"></i></button>
							</form>
						</td>
						<?php endif; ?>
					</tr>
					<?php endforeach; endforeach; ?>
					</tbody>
					<tfoot><tr><th colspan="4" class="text-right">Tổng giờ sân</th><th class="text-right"><?=number_format($loTotal['san'])?></th><th class="text-right"><?=number_format($loTotal['xep'])?></th><?php if($loCanEdit): ?><th></th><?php endif; ?></tr></tfoot>
				</table>
			</div>
		</div>
		<?php if($loSt === 'nhap' && $loCanEdit): ?>
		<div class="callout callout-success py-2 small"><i class="fas fa-lightbulb text-success mr-1"></i>Cấu hình xong? Bấm <strong>“Mở đăng ký cho GV”</strong> ở đầu trang để giáo viên bắt đầu đăng ký giờ trên Cổng GV.</div>
		<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
<script>
(function(){
	var form = document.querySelector('[data-lo-ngay-form]');
	if(!form) return;
	var defaults = <?=json_encode($loDefaults, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)?>;
	var bd = form.querySelector('[name=gio_bd]'), kt = form.querySelector('[name=gio_kt]'), xe = form.querySelector('[name=xe_list]');
	var tu = form.querySelector('[name=ngay_tu]'), den = form.querySelector('[name=ngay_den]');
	var out = form.querySelector('[data-lo-preview] span');
	function parseXe(v){
		var list = [], bad = '';
		v.split(/[\s,;]+/).forEach(function(p){
			if(!p) return;
			var m = p.match(/^(\d+)-(\d+)$/);
			if(m){ var a = +m[1], b = +m[2]; if(b < a || b - a > 100){ bad = p; return; } for(var i = a; i <= b; i++) if(list.indexOf(String(i)) < 0) list.push(String(i)); }
			else if(/^[0-9A-Za-z.\-]{1,20}$/.test(p)){ if(list.indexOf(p) < 0) list.push(p); }
			else bad = p;
		});
		return {list: list, bad: bad};
	}
	function days(){
		if(!tu || !tu.value) return 1;
		if(!den || !den.value) return 1;
		var n = Math.round((new Date(den.value) - new Date(tu.value)) / 86400000) + 1;
		return n > 0 ? n : 0;
	}
	function update(){
		var r = parseXe(xe.value), h = (+kt.value) - (+bd.value), nd = days();
		var box = out.parentNode; box.classList.remove('is-error', 'is-ok');
		if(r.bad){ out.textContent = 'Số xe "' + r.bad + '" không hợp lệ.'; box.classList.add('is-error'); return; }
		if(h <= 0){ out.textContent = 'Giờ kết thúc phải sau giờ bắt đầu.'; box.classList.add('is-error'); return; }
		if(!nd){ out.textContent = 'Đến ngày phải sau hoặc bằng ngày tập.'; box.classList.add('is-error'); return; }
		if(!r.list.length){ out.textContent = 'Nhập số xe để tính tổng giờ.'; return; }
		out.innerHTML = '<b>' + r.list.length + ' xe × ' + h + ' giờ = ' + (r.list.length * h) + ' giờ/ngày</b>' + (nd > 1 ? ' · ' + nd + ' ngày = ' + (r.list.length * h * nd) + ' giờ' : '') + '<br><small>Xe: ' + r.list.join(', ') + '</small>';
		box.classList.add('is-ok');
	}
	form.querySelectorAll('[name=hang]').forEach(function(r){
		r.addEventListener('change', function(){ if(defaults[r.value] && +form.querySelector('[name=id]').value === 0){ bd.value = defaults[r.value][0]; kt.value = defaults[r.value][1]; } update(); });
	});
	[bd, kt, xe, tu, den].forEach(function(el){ if(el) el.addEventListener('input', update); if(el) el.addEventListener('change', update); });
	if(tu && den) tu.addEventListener('change', function(){ den.min = tu.value; });
	update();
})();
</script>
