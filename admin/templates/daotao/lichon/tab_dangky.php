<?php
if(!defined('SOURCES')) die("Error");
$loE = $lo_edit_dk;
$loFormOpen = $loE || isset($_GET['add']);
$loV = $loE ?: array('id' => 0, 'id_gv' => 0, 'hang' => $lo_hang_tabs ? $lo_hang_tabs[0] : lo_hang_list()[0], 'so_gio' => '', 'ten_lich' => '', 'ghi_chu' => '',
	'uu_tien_ngay' => '', 'uu_tien_gio_bd' => null, 'uu_tien_gio_kt' => null, 'admin_ghi_chu' => '', 'xac_nhan' => '', 'gv_hoten' => '', 'gv_cccd' => '', 'gv_sdt' => '');
$loMissingOnly = !empty($_GET['only_missing']);
$loHangsInDk = array_values(array_unique(array_column($lo_dk, 'hang')));
$loXn = lo_xac_nhan_list();
$loLimit = lo_gio_canh_bao();
$loGvTotal = array();
foreach($lo_dk as $r) $loGvTotal[$r['id_gv']] = ($loGvTotal[$r['id_gv']] ?? 0) + (int)$r['so_gio'];
$loEditExt = $loE && (int)$loE['id_gv'] < 0;
?>
<?php if($loSt === 'mo_dk'): ?>
<div class="callout callout-success py-2 small mb-3"><i class="fas fa-door-open text-success mr-1"></i>Đăng ký đang mở: giáo viên đăng nhập <strong>Cổng giáo viên → tab “Lịch ôn”</strong> để đăng ký số giờ theo hạng và ghi chú yêu cầu. Khi đủ, bấm <strong>“Khóa đăng ký &amp; xếp lịch”</strong>.</div>
<?php elseif($loSt === 'nhap'): ?>
<div class="callout callout-secondary py-2 small mb-3"><i class="fas fa-info-circle mr-1"></i>Đợt đang ở trạng thái chuẩn bị nên giáo viên chưa thấy. Bộ phận vẫn có thể nhập đăng ký hộ.</div>
<?php endif; ?>

<?php if($loCanEdit): ?>
<div class="card <?=$loE ? 'card-warning' : 'card-primary'?> <?=$loFormOpen ? '' : 'd-none'?>" id="lo-dk-form">
	<div class="card-header d-flex align-items-center">
		<h3 class="card-title"><i class="fas <?=$loE ? 'fa-user-edit' : 'fa-user-plus'?> mr-1"></i><?=$loE ? 'Sửa đăng ký – '.$loEsc($loE['gv_hoten']) : 'Nhập đăng ký hộ giáo viên'?></h3>
		<a href="<?=$loEsc($loTabUrl('dangky'))?>" class="ml-auto btn btn-tool" aria-label="Đóng"><i class="fas fa-times"></i></a>
	</div>
	<form method="post" action="index.php?com=daotao&amp;act=lichonDkSave">
		<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="id" value="<?=(int)$loV['id']?>">
		<div class="card-body">
			<div class="row">
				<div class="col-lg-6">
					<h4 class="h6 text-primary border-bottom pb-2">Đăng ký</h4>
					<?php if(!$loE): ?>
					<div class="form-group mb-2 lo-gv-mode">
						<div class="custom-control custom-radio custom-control-inline"><input type="radio" class="custom-control-input" id="lo-gv-mode-0" name="gv_ngoai" value="0" checked><label class="custom-control-label" for="lo-gv-mode-0">GV trong hệ thống</label></div>
						<div class="custom-control custom-radio custom-control-inline"><input type="radio" class="custom-control-input" id="lo-gv-mode-1" name="gv_ngoai" value="1"><label class="custom-control-label" for="lo-gv-mode-1">GV ngoài hệ thống (nhập thông tin)</label></div>
					</div>
					<div class="form-group" data-lo-gv-mode="0">
						<label for="lo-gv-search">Giáo viên <span class="text-danger">*</span></label>
						<input type="search" id="lo-gv-search" class="form-control form-control-sm mb-1" placeholder="Gõ tên hoặc CCCD để lọc…" autocomplete="off">
						<select name="id_gv" id="lo-gv-select" class="form-control" size="6" required>
							<?php foreach($lo_gv_options as $g): ?><option value="<?=(int)$g['id']?>" data-s="<?=$loEsc(mb_strtolower($g['hoten'].' '.$g['cccd']))?>"><?=$loEsc($g['hoten'])?> · <?=$loEsc($g['cccd'])?><?=$g['hang_daotao_phep'] !== '' ? ' · '.$loEsc($g['hang_daotao_phep']) : ''?></option><?php endforeach; ?>
						</select>
						<small class="form-text text-muted">Không tìm thấy GV? Chọn “GV ngoài hệ thống” để nhập thông tin.</small>
					</div>
					<?php endif; ?>
					<?php if(!$loE || $loEditExt): ?>
					<div class="lo-ext-box <?=$loE ? '' : 'd-none'?>" data-lo-gv-mode="1">
						<div class="small text-muted mb-2"><i class="fas fa-info-circle mr-1"></i>Giáo viên chưa có trong danh sách giáo viên của hệ thống (VD: GV cộng tác, GV đơn vị khác). Nếu CCCD trùng GV trong hệ thống, đăng ký sẽ gắn vào GV đó.</div>
						<div class="form-group">
							<label for="lo-ext-ten">Họ tên giáo viên <span class="text-danger">*</span></label>
							<input type="text" id="lo-ext-ten" name="gv_hoten_ngoai" class="form-control" maxlength="255" value="<?=$loEsc($loV['gv_hoten'])?>" placeholder="VD: Nguyễn Văn An" <?=$loE ? 'required' : 'disabled'?>>
						</div>
						<div class="form-row">
							<div class="form-group col-sm-6">
								<label for="lo-ext-cccd">CCCD</label>
								<input type="text" id="lo-ext-cccd" name="gv_cccd_ngoai" class="form-control" maxlength="12" inputmode="numeric" pattern="\d{9,12}" value="<?=$loEsc($loV['gv_cccd'])?>" placeholder="9–12 chữ số" <?=$loE ? '' : 'disabled'?>>
							</div>
							<div class="form-group col-sm-6">
								<label for="lo-ext-sdt">Số điện thoại</label>
								<input type="tel" id="lo-ext-sdt" name="gv_sdt_ngoai" class="form-control" maxlength="15" value="<?=$loEsc($loV['gv_sdt'] ?? '')?>" placeholder="VD: 0912345678" <?=$loE ? '' : 'disabled'?>>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<div class="form-group">
						<label>Hạng xe <span class="text-danger">*</span></label>
						<div class="lo-hang-pick">
							<?php foreach(lo_hang_list() as $h): $hasCfg = isset($lo_cap[$h]) && $lo_cap[$h]['tong_gio'] > 0; ?>
							<label class="lo-chip-radio <?=$hasCfg ? '' : 'is-muted'?>" title="<?=$hasCfg ? 'Còn trống '.($lo_cap[$h]['tong_gio'] - $lo_cap[$h]['da_dk']).' giờ' : 'Chưa có ngày tập cho hạng này'?>"><input type="radio" name="hang" value="<?=$loEsc($h)?>" <?=$h === $loV['hang'] ? 'checked' : ''?> required><span><?=$loEsc($h)?></span></label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="form-row">
						<div class="form-group col-sm-5">
							<label for="lo-dk-gio">Số giờ đăng ký <span class="text-danger">*</span></label>
							<div class="input-group"><input type="number" id="lo-dk-gio" name="so_gio" class="form-control" min="1" max="500" required value="<?=$loEsc($loV['so_gio'])?>"><div class="input-group-append"><span class="input-group-text">giờ</span></div></div>
						</div>
						<div class="form-group col-sm-7">
							<label for="lo-dk-ten">Tên hiển thị trên lịch</label>
							<input type="text" id="lo-dk-ten" name="ten_lich" class="form-control" maxlength="50" value="<?=$loEsc($loV['ten_lich'])?>" placeholder="Tự động 2 chữ cuối (VD: Duy Minh)">
						</div>
					</div>
					<div class="form-group">
						<label for="lo-dk-xn">Khi tổng giờ của GV trong đợt &gt; <?=$loLimit?>h</label>
						<select id="lo-dk-xn" name="xac_nhan" class="form-control">
							<option value="">— Chưa chọn —</option>
							<?php foreach($loXn as $k => $x): ?><option value="<?=$k?>" <?=($loV['xac_nhan'] ?? '') === $k ? 'selected' : ''?>><?=$loEsc($x['label'])?></option><?php endforeach; ?>
						</select>
						<small class="form-text text-muted">Áp dụng cho mọi hạng của GV trong đợt; tự bỏ khi tổng ≤ <?=$loLimit?>h. “Tập 2 ngày” thì hệ thống không xếp song song cho GV này.</small>
					</div>
					<div class="form-group mb-lg-0">
						<label for="lo-dk-gc">Ghi chú của giáo viên</label>
						<textarea id="lo-dk-gc" name="ghi_chu" class="form-control" rows="2" maxlength="1000" placeholder="VD: chỉ tập buổi chiều, ưu tiên ngày 24/11"><?=$loEsc($loV['ghi_chu'])?></textarea>
					</div>
				</div>
				<div class="col-lg-6">
					<h4 class="h6 text-primary border-bottom pb-2">Yêu cầu xếp lịch <small class="text-muted">(theo ghi chú của GV – không bắt buộc)</small></h4>
					<div class="form-group">
						<label for="lo-dk-ngay">Ưu tiên ngày tập</label>
						<select id="lo-dk-ngay" name="uu_tien_ngay" class="form-control">
							<option value="">— Không yêu cầu —</option>
							<?php foreach($loDays as $day): ?><option value="<?=$day?>" <?=$loV['uu_tien_ngay'] === $day ? 'selected' : ''?>><?=lo_weekday($day)?> <?=lo_date_vn($day)?></option><?php endforeach; ?>
						</select>
						<small class="form-text text-muted">Hệ thống xếp ngày này trước; nếu không đủ chỗ sẽ xếp sang ngày khác.</small>
					</div>
					<div class="form-row">
						<div class="form-group col-6">
							<label for="lo-dk-bd">Chỉ xếp từ giờ</label>
							<select id="lo-dk-bd" name="uu_tien_gio_bd" class="form-control"><option value="">— Bất kỳ —</option><?php foreach($loHours as $h): ?><option value="<?=$h?>" <?=$loV['uu_tien_gio_bd'] !== null && (int)$loV['uu_tien_gio_bd'] === $h ? 'selected' : ''?>><?=lo_hour_label($h)?></option><?php endforeach; ?></select>
						</div>
						<div class="form-group col-6">
							<label for="lo-dk-kt">Đến giờ</label>
							<select id="lo-dk-kt" name="uu_tien_gio_kt" class="form-control"><option value="">— Bất kỳ —</option><?php foreach(range(6, 24) as $h): ?><option value="<?=$h?>" <?=$loV['uu_tien_gio_kt'] !== null && (int)$loV['uu_tien_gio_kt'] === $h ? 'selected' : ''?>><?=lo_hour_label($h)?></option><?php endforeach; ?></select>
						</div>
					</div>
					<div class="form-group mb-0">
						<label for="lo-dk-agc">Ghi chú của bộ phận</label>
						<input type="text" id="lo-dk-agc" name="admin_ghi_chu" class="form-control" maxlength="500" value="<?=$loEsc($loV['admin_ghi_chu'])?>" placeholder="Ghi chú nội bộ (GV không thấy)">
					</div>
				</div>
			</div>
		</div>
		<div class="card-footer">
			<button type="submit" class="btn <?=$loE ? 'btn-warning' : 'btn-primary'?>"><i class="fas fa-save mr-1"></i><?=$loE ? 'Lưu thay đổi' : 'Thêm đăng ký'?></button>
			<a href="<?=$loEsc($loTabUrl('dangky'))?>" class="btn btn-outline-secondary ml-1">Hủy</a>
			<?php if($loE && $loE['da_xep']): ?><span class="small text-muted ml-2">Đã xếp <?=$loE['da_xep']?> giờ cho đăng ký này.</span><?php endif; ?>
		</div>
	</form>
</div>
<?php endif; ?>

<div class="card">
	<div class="card-header lo-toolbar">
		<div class="d-flex flex-wrap align-items-center w-100">
			<div class="input-group input-group-sm mr-2 mb-1" style="max-width:260px">
				<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
				<input type="search" class="form-control" placeholder="Tìm giáo viên…" data-lo-filter="q">
			</div>
			<select class="form-control form-control-sm mr-2 mb-1" style="max-width:160px" data-lo-filter="hang">
				<option value="">Tất cả hạng</option>
				<?php foreach($loHangsInDk as $h): ?><option value="<?=$loEsc($h)?>"><?=$loEsc($h)?></option><?php endforeach; ?>
			</select>
			<div class="custom-control custom-switch mr-3 mb-1"><input type="checkbox" class="custom-control-input" id="lo-f-miss" data-lo-filter="miss" <?=$loMissingOnly ? 'checked' : ''?>><label class="custom-control-label" for="lo-f-miss">Chỉ GV còn thiếu giờ</label></div>
			<div class="custom-control custom-switch mr-3 mb-1"><input type="checkbox" class="custom-control-input" id="lo-f-note" data-lo-filter="note"><label class="custom-control-label" for="lo-f-note">Có ghi chú / cảnh báo</label></div>
			<div class="ml-auto mb-1">
				<?php if($loCanExport): ?><a href="index.php?com=daotao&amp;act=exportLichonDk&amp;id=<?=$loId?>" class="btn btn-sm btn-outline-success mr-1"><i class="fas fa-file-excel mr-1"></i>Xuất danh sách</a><?php endif; ?>
				<?php if($loCanEdit): ?><a href="<?=$loEsc($loTabUrl('dangky', array('add' => 1)))?>#lo-dk-form" class="btn btn-sm btn-primary" data-lo-open-form><i class="fas fa-user-plus mr-1"></i>Nhập đăng ký hộ</a><?php endif; ?>
			</div>
		</div>
	</div>
	<?php if(!$lo_dk): ?>
	<div class="card-body text-center py-5">
		<div class="text-muted mb-3"><i class="fas fa-users fa-3x"></i></div>
		<h4 class="h5">Chưa có giáo viên đăng ký</h4>
		<p class="text-muted mb-0"><?=$loSt === 'nhap' ? 'Mở đăng ký để giáo viên tự đăng ký trên Cổng GV, hoặc nhập hộ.' : 'Giáo viên đăng ký trên Cổng GV sẽ hiện ở đây.'?></p>
	</div>
	<?php else: ?>
	<div class="table-responsive">
		<table class="table table-hover table-sm mb-0 lo-dk-table">
			<thead><tr>
				<th>Giáo viên</th><th>Hạng</th><th class="text-center">Đăng ký</th><th class="text-center">Đã xếp</th><th>Tình trạng</th>
				<th>Ghi chú &amp; yêu cầu</th><th>Lịch đã xếp</th><?php if($loCanEdit): ?><th></th><?php endif; ?>
			</tr></thead>
			<tbody>
			<?php foreach($lo_dk as $r):
				$miss = max(0, $r['con_lai']); $extra = max(0, -$r['con_lai']);
				$hasPref = $r['uu_tien_ngay'] || $r['uu_tien_gio_bd'] !== null;
				$isExt = (int)$r['id_gv'] < 0;
				$gvTotal = $loGvTotal[$r['id_gv']] ?? 0;
				$xn = $loXn[$r['xac_nhan'] ?? ''] ?? null;
				$blocks = array();
				foreach(($lo_blocks[$r['id_gv']] ?? array()) as $day => $bl) foreach($bl as $b) if($b['hang'] === $r['hang']) $blocks[] = array($day, $b); ?>
			<tr data-lo-row data-hang="<?=$loEsc($r['hang'])?>" data-miss="<?=$miss ? 1 : 0?>" data-note="<?=($r['ghi_chu'] !== '' || $r['admin_ghi_chu'] !== '' || $xn || $gvTotal > $loLimit) ? 1 : 0?>" data-q="<?=$loEsc(mb_strtolower($r['gv_hoten'].' '.$r['gv_cccd'].' '.$r['ten_lich']))?>">
				<td>
					<div class="d-flex align-items-center">
						<span class="lo-swatch" style="background:<?=lo_color($r['id_gv'])?>"><?=$loEsc(mb_substr($r['ten_lich'], 0, 1))?></span>
						<div><strong><?=$loEsc($r['gv_hoten'])?></strong><?=$isExt ? ' <span class="badge badge-warning" title="Giáo viên không có trong danh sách giáo viên của hệ thống">Ngoài HT</span>' : ''?><small class="d-block text-muted"><?=$loEsc($r['gv_cccd'])?><?=$isExt && ($r['gv_sdt'] ?? '') !== '' ? ' · '.$loEsc($r['gv_sdt']) : ''?> · Lịch: “<?=$loEsc($r['ten_lich'])?>”<?=$r['nguon'] === 'admin' ? ' · <i class="fas fa-user-shield" title="Bộ phận nhập hộ"></i>' : ''?></small></div>
					</div>
				</td>
				<td><span class="badge badge-light border"><?=$loEsc($r['hang'])?></span></td>
				<td class="text-center"><strong><?=(int)$r['so_gio']?></strong>h</td>
				<td class="text-center"><?=$r['da_xep']?>h</td>
				<td>
					<?php if($miss && !$r['da_xep']): ?><span class="badge badge-secondary">Chưa xếp</span>
					<?php elseif($miss): ?><span class="badge badge-danger">Thiếu <?=$miss?>h</span>
					<?php elseif($extra): ?><span class="badge badge-warning">Dư <?=$extra?>h</span>
					<?php else: ?><span class="badge badge-success"><i class="fas fa-check mr-1"></i>Đủ</span><?php endif; ?>
				</td>
				<td class="lo-note-cell">
					<?php if($xn): ?><div class="<?=$r['xac_nhan'] === 'phu_trach' ? 'lo-note-todo' : 'text-info'?>"><i class="fas <?=$xn['icon']?> mr-1"></i><?=$loEsc($xn['short'])?> <span class="text-muted">(tổng <?=$gvTotal?>h)</span></div>
					<?php elseif($gvTotal > $loLimit): ?><div class="lo-note-todo"><i class="fas fa-exclamation-triangle mr-1"></i>Tổng <?=$gvTotal?>h &gt; <?=$loLimit?>h, chưa chọn cách xử lý</div><?php endif; ?>
					<?php if($r['ghi_chu'] !== ''): ?><div class="<?=$hasPref ? '' : 'lo-note-todo'?>" title="<?=$hasPref ? '' : 'Có ghi chú nhưng chưa đặt yêu cầu xếp lịch'?>"><i class="far fa-comment-dots mr-1"></i><?=$loEsc($r['ghi_chu'])?></div><?php endif; ?>
					<?php if($hasPref): ?><div class="text-primary"><i class="fas fa-thumbtack mr-1"></i><?=$r['uu_tien_ngay'] ? $loShortDay($r['uu_tien_ngay']) : ''?><?=$r['uu_tien_ngay'] && $r['uu_tien_gio_bd'] !== null ? ', ' : ''?><?=$r['uu_tien_gio_bd'] !== null ? lo_range_label($r['uu_tien_gio_bd'], $r['uu_tien_gio_kt'] !== null ? $r['uu_tien_gio_kt'] : 24) : ''?></div><?php endif; ?>
					<?php if($r['admin_ghi_chu'] !== ''): ?><div class="text-muted"><i class="fas fa-user-shield mr-1"></i><?=$loEsc($r['admin_ghi_chu'])?></div><?php endif; ?>
					<?php if($r['ghi_chu'] === '' && !$hasPref && $r['admin_ghi_chu'] === '' && !$xn && $gvTotal <= $loLimit): ?><span class="text-muted">—</span><?php endif; ?>
				</td>
				<td class="lo-blocks">
					<?php if(!$blocks): ?><span class="text-muted">—</span><?php else: foreach($blocks as $bb): ?>
					<span class="lo-block"><b><?=$loShortDay($bb[0])?></b> <?=sprintf('%02d–%02d', $bb[1]['gio_bd'], $bb[1]['gio_kt'])?>h · xe <?=$loEsc($bb[1]['so_xe'])?></span>
					<?php endforeach; endif; ?>
				</td>
				<?php if($loCanEdit): ?>
				<td class="text-right text-nowrap">
					<a href="<?=$loEsc($loTabUrl('dangky', array('edit_dk' => $r['id'])))?>#lo-dk-form" class="btn btn-xs btn-outline-primary" title="Sửa / đặt yêu cầu"><i class="fas fa-edit"></i></a>
					<a href="<?=$loEsc($loTabUrl('xeplich', array('hang' => $r['hang'], 'focus' => $r['id'])))?>" class="btn btn-xs btn-outline-info" title="Xếp trên lưới"><i class="fas fa-th"></i></a>
					<form method="post" action="index.php?com=daotao&amp;act=lichonDkDelete" class="d-inline" onsubmit="return confirm(<?=$loJs('Xóa đăng ký '.$r['hang'].' của '.$r['gv_hoten'].'?'.($r['da_xep'] ? "\n".$r['da_xep'].' giờ đã xếp sẽ bị bỏ khỏi lịch.' : ''))?>)">
						<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="id" value="<?=(int)$r['id']?>">
						<button type="submit" class="btn btn-xs btn-outline-danger" title="Xóa"><i class="fas fa-trash"></i></button>
					</form>
				</td>
				<?php endif; ?>
			</tr>
			<?php endforeach; ?>
			<tr class="lo-empty-filter d-none"><td colspan="8" class="text-center text-muted py-4">Không có đăng ký phù hợp bộ lọc.</td></tr>
			</tbody>
			<tfoot><tr><th colspan="2" class="text-right">Tổng</th><th class="text-center"><?=number_format($loTotal['dk'])?>h</th><th class="text-center"><?=number_format($loTotal['xep'])?>h</th><th colspan="4"><?=$loTotal['thieu'] ? '<span class="text-danger">Còn thiếu '.$loTotal['thieu'].' giờ</span>' : ''?></th></tr></tfoot>
		</table>
	</div>
	<?php endif; ?>
</div>
<script>
(function(){
	var s = document.getElementById('lo-gv-search'), sel = document.getElementById('lo-gv-select');
	if(s && sel) s.addEventListener('input', function(){
		var q = s.value.trim().toLowerCase(), first = null;
		Array.prototype.forEach.call(sel.options, function(o){ var ok = !q || o.getAttribute('data-s').indexOf(q) >= 0; o.hidden = !ok; if(ok && !first) first = o; });
		if(first && (sel.selectedIndex < 0 || sel.options[sel.selectedIndex].hidden)) first.selected = true;
	});
	var open = document.querySelector('[data-lo-open-form]'), form = document.getElementById('lo-dk-form');
	document.querySelectorAll('input[name=gv_ngoai]').forEach(function(radio){
		radio.addEventListener('change', function(){
			var ext = radio.value === '1' && radio.checked;
			if(!radio.checked) return;
			document.querySelectorAll('[data-lo-gv-mode]').forEach(function(box){
				var on = box.getAttribute('data-lo-gv-mode') === (ext ? '1' : '0');
				box.classList.toggle('d-none', !on);
				box.querySelectorAll('input, select').forEach(function(el){ if(el.type !== 'search') el.disabled = !on; });
			});
			if(sel) sel.required = !ext;
			var ten = document.getElementById('lo-ext-ten'); if(ten) { ten.required = ext; if(ext) ten.focus(); }
		});
	});
	if(open && form) open.addEventListener('click', function(e){ e.preventDefault(); form.classList.remove('d-none'); form.scrollIntoView({behavior: 'smooth', block: 'start'}); if(s) s.focus(); });
	var f = {}; document.querySelectorAll('[data-lo-filter]').forEach(function(el){ f[el.getAttribute('data-lo-filter')] = el; el.addEventListener('input', apply); el.addEventListener('change', apply); });
	function apply(){
		var q = f.q ? f.q.value.trim().toLowerCase() : '', h = f.hang ? f.hang.value : '', shown = 0;
		document.querySelectorAll('[data-lo-row]').forEach(function(tr){
			var ok = (!q || tr.getAttribute('data-q').indexOf(q) >= 0) && (!h || tr.getAttribute('data-hang') === h)
				&& (!f.miss || !f.miss.checked || tr.getAttribute('data-miss') === '1') && (!f.note || !f.note.checked || tr.getAttribute('data-note') === '1');
			tr.classList.toggle('d-none', !ok); if(ok) shown++;
		});
		var empty = document.querySelector('.lo-empty-filter'); if(empty) empty.classList.toggle('d-none', shown > 0);
	}
	apply();
})();
</script>
