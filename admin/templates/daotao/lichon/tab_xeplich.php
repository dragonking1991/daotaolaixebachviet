<?php
if(!defined('SOURCES')) die("Error");
$loHangCfg = array_values(array_filter($lo_ngay, function($n) use ($lo_hang) { return $n['hang'] === $lo_hang; }));
$loHangDk = array_values(array_filter($lo_dk, function($r) use ($lo_hang) { return $r['hang'] === $lo_hang; }));
$loFocus = (int)($_GET['focus'] ?? 0);
$loMissingOnly = !empty($_GET['only_missing']);
$loJsDk = array();
foreach($lo_dk as $r) $loJsDk[$r['id']] = array('ten' => $r['ten_lich'], 'hoten' => $r['gv_hoten'], 'hang' => $r['hang'], 'gv' => (int)$r['id_gv'], 'so_gio' => (int)$r['so_gio'], 'da_xep' => $r['da_xep'], 'color' => lo_color($r['id_gv']));
$loHangMiss = array();
foreach($lo_dk as $r) $loHangMiss[$r['hang']] = ($loHangMiss[$r['hang']] ?? 0) + max(0, $r['con_lai']);
?>
<?php if(!$lo_ngay): ?>
<div class="card"><div class="card-body text-center py-5">
	<div class="text-muted mb-3"><i class="fas fa-th fa-3x"></i></div>
	<h4 class="h5">Chưa có ngày tập</h4>
	<p class="text-muted">Cần cấu hình ngày tập, hạng và số xe trước khi xếp lịch.</p>
	<a href="<?=$loEsc($loTabUrl('cauhinh'))?>" class="btn btn-primary"><i class="fas fa-arrow-left mr-1"></i>Đến bước 1</a>
</div></div>
<?php else: ?>

<?php if($loCanEdit): ?>
<div class="card lo-action-bar">
	<div class="card-body py-2 d-flex flex-wrap align-items-center">
		<form method="post" action="index.php?com=daotao&amp;act=lichonAuto" class="mr-2 my-1">
			<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="mode" value="fill">
			<button type="submit" class="btn btn-primary" <?=$lo_dk ? '' : 'disabled'?>><i class="fas fa-magic mr-1"></i>Tự động xếp phần còn thiếu</button>
		</form>
		<form method="post" action="index.php?com=daotao&amp;act=lichonAuto" class="mr-2 my-1" onsubmit="return confirm('Xếp lại từ đầu: xóa các ô hệ thống đã tự xếp rồi xếp lại toàn bộ.\nCác ô bộ phận xếp tay được giữ nguyên. Tiếp tục?')">
			<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="mode" value="reset">
			<button type="submit" class="btn btn-outline-primary" <?=$lo_dk ? '' : 'disabled'?>><i class="fas fa-redo mr-1"></i>Xếp lại từ đầu</button>
		</form>
		<div class="btn-group mr-2 my-1">
			<button type="button" class="btn btn-outline-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-eraser mr-1"></i>Xóa lịch</button>
			<div class="dropdown-menu lo-clear-menu">
				<form method="post" action="index.php?com=daotao&amp;act=lichonClear" data-lo-clear-gv><input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="mode" value="gv"><input type="hidden" name="hang" value="<?=$loEsc($lo_hang)?>"><button class="dropdown-item" type="submit"><i class="fas fa-user-check mr-2"></i>Lịch của GV đã tích bên phải <span class="badge badge-pill badge-danger ml-1" data-lo-check-count>0</span></button></form>
				<form method="post" action="index.php?com=daotao&amp;act=lichonClear" onsubmit="return confirm('Xóa các ô do hệ thống tự xếp? Ô xếp tay được giữ lại.')"><input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="mode" value="auto"><input type="hidden" name="hang" value="<?=$loEsc($lo_hang)?>"><button class="dropdown-item" type="submit"><i class="fas fa-robot mr-2"></i>Chỉ ô tự động (giữ ô xếp tay)</button></form>
				<div class="dropdown-divider"></div>
				<form method="post" action="index.php?com=daotao&amp;act=lichonClear" onsubmit="return confirm('Xóa TOÀN BỘ lịch đã xếp của đợt này (tất cả hạng, kể cả ô xếp tay)?')"><input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id_dot" value="<?=$loId?>"><input type="hidden" name="mode" value="all"><input type="hidden" name="hang" value="<?=$loEsc($lo_hang)?>"><button class="dropdown-item text-danger" type="submit"><i class="fas fa-trash mr-2"></i>Toàn bộ lịch của đợt</button></form>
			</div>
		</div>
		<button type="button" class="btn btn-link btn-sm my-1" data-toggle="collapse" data-target="#lo-rules" aria-expanded="false"><i class="fas fa-question-circle mr-1"></i>Quy tắc xếp tự động</button>
		<?php if($loCanExport): ?><a href="index.php?com=daotao&amp;act=exportLichon&amp;id=<?=$loId?>&amp;hang=<?=rawurlencode($lo_hang)?>" class="btn btn-outline-success ml-auto my-1"><i class="fas fa-file-excel mr-1"></i>Xuất lịch <?=$loEsc($lo_hang)?></a><?php endif; ?>
	</div>
	<div class="collapse" id="lo-rules">
		<div class="card-body pt-0 small">
			<ol class="mb-0 pl-3">
				<li>GV có <b>yêu cầu ngày / khung giờ</b> (đặt ở bước 2) được xếp trước, sau đó theo <b>số giờ đăng ký nhiều → ít</b>.</li>
				<li>Giờ của một GV trong ngày được xếp <b>liên tục</b>, nối tiếp giữa các hạng (VD: số sàn 06–08h, tự động 08–09h, C1 09–10h).</li>
				<li>Xe đang dùng bị kín thì chuyển sang <b>xe trống khác</b> cùng hạng để giữ liên tục.</li>
				<li>Không đủ chỗ liên tục thì xếp <b>song song 2 xe cùng giờ</b>, tối đa <?=(int)$lo_dot['max_song_song']?> giờ/ngày, để tránh đẩy sang ngày sau.</li>
				<li>Vượt quá giờ sân của ngày thì phần còn lại chuyển sang <b>ngày tập tiếp theo</b>; cuối cùng lấp vào chỗ trống còn lại.</li>
				<li>“Tự động xếp phần còn thiếu” giữ nguyên lịch hiện có. Bỏ một ô thì số giờ đó <b>trả lại</b> cho GV.</li>
				<li>GV chọn <b>“Tập trong 2 ngày”</b> (khi đăng ký &gt; <?=lo_gio_canh_bao()?>h) sẽ không bị xếp song song; phần dư chuyển sang ngày khác.</li>
			</ol>
		</div>
	</div>
</div>
<?php endif; ?>

<ul class="nav nav-pills lo-hang-tabs mb-2">
	<?php foreach($lo_hang_tabs as $h): ?>
	<li class="nav-item"><a class="nav-link <?=$h === $lo_hang ? 'active' : ''?>" href="<?=$loEsc($loTabUrl('xeplich', array('hang' => $h)))?>"><?=$loEsc($h)?><?php if(!empty($loHangMiss[$h])): ?> <span class="badge badge-danger"><?=$loHangMiss[$h]?>h</span><?php endif; ?></a></li>
	<?php endforeach; ?>
</ul>

<div class="row lo-sched">
	<div class="col-xl-9 col-lg-8 order-2 order-lg-1">
		<?php if($loCanEdit): ?>
		<div class="lo-brush-hint" data-lo-hint><i class="fas fa-hand-pointer mr-1"></i><span>Chọn giáo viên ở bảng bên phải, rồi <b>bấm hoặc kéo</b> qua các ô để xếp. Bấm lại ô của chính GV đó để bỏ. Dùng <b>Tẩy</b> để xóa ô bất kỳ.</span></div>
		<?php endif; ?>
		<?php foreach($loHangCfg as $c): $hours = range((int)$c['gio_bd'], (int)$c['gio_kt'] - 1); ?>
		<div class="card lo-day">
			<div class="card-header py-2 d-flex align-items-center">
				<h3 class="card-title"><strong><?=lo_weekday($c['ngay'])?> ngày <?=lo_date_vn($c['ngay'])?></strong> <span class="text-muted small ml-2"><?=count($c['xe'])?> xe × <?=$c['so_gio_xe']?> giờ</span></h3>
				<?php $used = 0; foreach(($lo_grid[$c['id']] ?? array()) as $hrs) $used += count($hrs); ?>
				<span class="ml-auto small <?=$used >= $c['tong_gio'] ? 'text-success' : 'text-muted'?>">Đã xếp <b><?=$used?></b>/<?=$c['tong_gio']?> giờ</span>
			</div>
			<div class="table-responsive">
				<table class="table table-bordered lo-grid mb-0">
					<thead><tr><th class="lo-xe-head">Số xe</th><?php foreach($hours as $h): ?><th><?=sprintf('%02d', $h)?>h<small>–<?=sprintf('%02d', $h + 1)?>h</small></th><?php endforeach; ?></tr></thead>
					<tbody>
					<?php foreach($c['xe'] as $xe): ?>
					<tr>
						<th class="lo-xe-head"><?=$loEsc($xe)?></th>
						<?php foreach($hours as $h): $cell = $lo_grid[$c['id']][$xe][$h] ?? null; $dk = $cell ? ($lo_dk_map[$cell['dk']] ?? null) : null; $par = isset($lo_parallel[$c['id'].'|'.$xe.'|'.$h]); ?>
						<td class="lo-cell <?=$dk ? 'is-filled' : ''?> <?=$cell && $cell['nguon'] === 'tay' ? 'is-manual' : ''?> <?=$par ? 'is-par' : ''?>"
							data-ngay="<?=(int)$c['id']?>" data-xe="<?=$loEsc($xe)?>" data-gio="<?=$h?>" data-dk="<?=$dk ? (int)$dk['id'] : 0?>"
							<?=$dk ? 'style="background:'.lo_color($dk['id_gv']).'"' : ''?>
							title="<?=$dk ? $loEsc($dk['gv_hoten'].' · '.lo_range_label($h, $h + 1).' · xe '.$xe.($par ? ' · song song' : '').($cell['nguon'] === 'tay' ? ' · xếp tay' : '')) : $loEsc('Xe '.$xe.' · '.lo_range_label($h, $h + 1).' · trống')?>"><?=$dk ? $loEsc($dk['ten_lich']) : ''?></td>
						<?php endforeach; ?>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endforeach; ?>
		<div class="small text-muted mb-3">
			<span class="lo-legend is-manual"></span>Ô xếp tay
			<span class="lo-legend is-par ml-3"></span>GV đứng 2 xe cùng giờ (song song)
		</div>
	</div>

	<div class="col-xl-3 col-lg-4 order-1 order-lg-2">
		<div class="card lo-panel lo-sticky">
			<div class="card-header py-2">
				<h3 class="card-title"><i class="fas fa-users mr-1"></i>Giáo viên hạng <?=$loEsc($lo_hang)?></h3>
			</div>
			<div class="card-body p-2">
				<?php if($loCanEdit): ?>
				<div class="d-flex mb-2">
					<button type="button" class="btn btn-sm btn-outline-secondary flex-fill mr-1 lo-tool" data-lo-brush="0"><i class="fas fa-mouse-pointer mr-1"></i>Xem</button>
					<button type="button" class="btn btn-sm btn-outline-danger flex-fill lo-tool" data-lo-brush="-1"><i class="fas fa-eraser mr-1"></i>Tẩy</button>
				</div>
				<?php endif; ?>
				<input type="search" class="form-control form-control-sm mb-1" placeholder="Tìm giáo viên…" data-lo-pq>
				<div class="d-flex flex-wrap align-items-center justify-content-between small mb-2">
					<div class="custom-control custom-switch mr-2"><input type="checkbox" class="custom-control-input" id="lo-p-miss" data-lo-pmiss <?=$loMissingOnly ? 'checked' : ''?>><label class="custom-control-label" for="lo-p-miss">Chỉ GV còn thiếu giờ</label></div>
					<?php if($loCanEdit && $loHangDk): ?><div class="custom-control custom-checkbox text-nowrap" title="Tích để xóa lịch theo giáo viên (menu Xóa lịch)"><input type="checkbox" class="custom-control-input" id="lo-p-all" data-lo-check-all><label class="custom-control-label" for="lo-p-all">Tích tất cả</label></div><?php endif; ?>
				</div>
				<?php if(!$loHangDk): ?>
				<div class="text-center text-muted small py-3">Chưa có giáo viên đăng ký hạng này.</div>
				<?php endif; ?>
				<div class="lo-gv-list">
				<?php foreach($loHangDk as $r): $miss = max(0, $r['con_lai']); $pct = $r['so_gio'] ? min(100, round($r['da_xep'] * 100 / $r['so_gio'])) : 0; $xn = lo_xac_nhan_list()[$r['xac_nhan'] ?? ''] ?? null; ?>
					<div class="lo-gv-row" data-lo-gv-row="<?=(int)$r['id']?>">
					<?php if($loCanEdit): ?><label class="lo-gv-check" title="Tích để xóa lịch của <?=$loEsc($r['gv_hoten'])?>"><input type="checkbox" value="<?=(int)$r['id']?>" data-lo-check aria-label="Chọn <?=$loEsc($r['gv_hoten'])?>"></label><?php endif; ?>
					<button type="button" class="lo-gv <?=$loFocus === (int)$r['id'] ? 'is-focus' : ''?>" data-lo-gv="<?=(int)$r['id']?>" data-q="<?=$loEsc(mb_strtolower($r['gv_hoten'].' '.$r['ten_lich']))?>" <?=$loCanEdit ? '' : 'disabled'?>>
						<span class="lo-swatch" style="background:<?=lo_color($r['id_gv'])?>"><?=$loEsc(mb_substr($r['ten_lich'], 0, 1))?></span>
						<span class="lo-gv-main">
							<span class="d-flex justify-content-between"><b><?=$loEsc($r['ten_lich'])?></b><span class="lo-gv-count" data-lo-count><?=$r['da_xep']?>/<?=(int)$r['so_gio']?>h</span></span>
							<span class="progress progress-xxs"><span class="progress-bar <?=$miss ? 'bg-primary' : 'bg-success'?>" data-lo-bar style="width:<?=$pct?>%"></span></span>
							<small class="d-block text-truncate text-muted"><?=$loEsc($r['gv_hoten'])?><?=(int)$r['id_gv'] < 0 ? ' · ngoài HT' : ''?></small>
							<?php if($xn): ?><small class="d-block text-truncate text-info"><i class="fas <?=$xn['icon']?> mr-1"></i><?=$loEsc($xn['short'])?></small><?php endif; ?>
							<?php if($r['ghi_chu'] !== '' || $r['uu_tien_ngay'] || $r['uu_tien_gio_bd'] !== null): ?>
							<small class="d-block text-truncate lo-gv-note" title="<?=$loEsc($r['ghi_chu'])?>"><i class="far fa-comment-dots mr-1"></i><?=$loEsc($r['ghi_chu'] !== '' ? $r['ghi_chu'] : 'Có yêu cầu xếp lịch')?></small>
							<?php endif; ?>
						</span>
						<span class="badge <?=$miss ? 'badge-danger' : 'badge-success'?> lo-gv-badge" data-lo-badge><?=$miss ? '−'.$miss.'h' : '✓'?></span>
					</button>
					</div>
				<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="lo-toast" data-lo-toast role="status" aria-live="polite"></div>
<script>
window.LO_GRID = {
	dot: <?=$loId?>,
	csrf: <?=json_encode(admin_csrf_token(), JSON_HEX_TAG)?>,
	canEdit: <?=$loCanEdit ? 'true' : 'false'?>,
	dk: <?=json_encode($loJsDk, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>,
	focus: <?=$loFocus?>
};
</script>
<script src="assets/js/lichon.js?v=<?=@filemtime(__DIR__.'/../../../assets/js/lichon.js')?>"></script>
<?php endif; ?>
