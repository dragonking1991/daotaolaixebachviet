<?php
if(!defined('SOURCES')) die("Error");
/* Tab "Lịch ôn" trên Cổng giáo viên: đăng ký giờ ôn sát hạch và xem lịch đã công bố. */
$loE = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$loSt = lo_status_list();
$loShort = function($date) { $w = (int)date('w', strtotime($date)); return ($w === 0 ? 'CN' : 'T'.($w + 1)).' '.date('d/m', strtotime($date)); };
$loXn = lo_xac_nhan_list();
$loLimit = lo_gio_canh_bao();
?>
<section class="dt-lo">
	<div class="dt-lo-intro">
		<div><h2><i class="fas fa-calendar-alt" aria-hidden="true"></i> Đăng ký giờ ôn sát hạch</h2>
		<p>Chọn hạng, nhập số giờ cần ôn và ghi chú yêu cầu (ngày, khung giờ mong muốn). Bộ phận đào tạo sẽ xếp lịch và công bố tại đây.</p></div>
	</div>
	<?php if(empty($dtLoDots)): ?>
	<div class="dt-lo-empty"><i class="far fa-calendar" aria-hidden="true"></i><strong>Hiện chưa có đợt ôn nào</strong><span>Khi trung tâm mở đăng ký giờ ôn cho ngày sát hạch, đợt ôn sẽ hiện ở đây.</span></div>
	<?php endif; ?>
	<?php foreach($dtLoDots as $L): $dot = $L['dot']; $st = $dot['trang_thai']; $open = $st === 'mo_dk';
		$mineHang = array_column($L['mine'], 'hang');
		$mineTotal = array_sum(array_map('intval', array_column($L['mine'], 'so_gio')));
		$mineXn = $L['mine'] ? (string)($L['mine'][0]['xac_nhan'] ?? '') : '';
		$canAdd = array(); foreach($L['cap'] as $h => $c) if($c['tong_gio'] > 0 && !in_array($h, $mineHang, true)) $canAdd[$h] = $c; ?>
	<article class="dt-lo-card is-<?=$st?>">
		<header class="dt-lo-head">
			<span class="dt-lo-date"><b><?=date('d', strtotime($dot['ngay_sh']))?></b><small>Th<?=date('m', strtotime($dot['ngay_sh']))?></small></span>
			<div class="dt-lo-title">
				<strong><?=$loE(lo_dot_title($dot))?></strong>
				<span><?=lo_weekday($dot['ngay_sh'])?><?=$dot['ghi_chu'] !== '' ? ' · '.$loE($dot['ghi_chu']) : ''?></span>
			</div>
			<span class="dt-lo-pill is-<?=$st?>"><i class="fas <?=$loSt[$st]['icon']?>" aria-hidden="true"></i> <?=$loSt[$st]['label']?></span>
		</header>

		<div class="dt-lo-cap">
			<?php foreach($L['cap'] as $h => $c): if(!$c['tong_gio']) continue; $free = max(0, $c['tong_gio'] - $c['da_dk']); ?>
			<div class="dt-lo-cap-item">
				<div><strong><?=$loE($h)?></strong><span class="<?=$free ? '' : 'is-full'?>"><?=$free ? 'Còn trống '.$free.' giờ' : 'Đã đủ giờ'?></span></div>
				<div class="dt-lo-bar"><i style="width:<?=$c['tong_gio'] ? min(100, round($c['da_dk'] * 100 / $c['tong_gio'])) : 0?>%"></i></div>
				<small>
					<?php foreach($L['days'] as $day => $rows) foreach($rows as $n) if($n['hang'] === $h): ?>
					<span><?=$loShort($day)?> <?=sprintf('%02d–%02dh', $n['gio_bd'], $n['gio_kt'])?> · <?=count($n['xe'])?> xe</span>
					<?php endif; ?>
				</small>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="dt-lo-section">
			<h3>Đăng ký của bạn<?php if($mineTotal): ?> <span class="dt-lo-total <?=$mineTotal > $loLimit ? 'is-over' : ''?>">Tổng <?=$mineTotal?> giờ</span><?php endif; ?></h3>
			<?php if($mineTotal > $loLimit && isset($loXn[$mineXn])): ?>
			<p class="dt-lo-xn"><i class="fas <?=$loXn[$mineXn]['icon']?>" aria-hidden="true"></i> Trên <?=$loLimit?> giờ – bạn đã chọn: <b><?=$loE($loXn[$mineXn]['label'])?></b></p>
			<?php endif; ?>
			<?php if(!$L['mine']): ?>
			<p class="dt-lo-muted"><?=$open ? 'Bạn chưa đăng ký giờ ôn trong đợt này.' : 'Bạn không đăng ký giờ ôn trong đợt này.'?></p>
			<?php endif; ?>
			<?php foreach($L['mine'] as $r): $cap = $L['cap'][$r['hang']] ?? array('tong_gio' => 0, 'da_dk' => 0); $max = max((int)$r['so_gio'], $cap['tong_gio'] - $cap['da_dk'] + (int)$r['so_gio']); ?>
			<div class="dt-lo-reg">
				<div class="dt-lo-reg-main">
					<span class="dt-lo-hang"><?=$loE($r['hang'])?></span>
					<strong><?=(int)$r['so_gio']?> giờ</strong>
					<?php if($st === 'cong_bo' || $r['da_xep']): ?><span class="dt-lo-xep <?=$r['con_lai'] > 0 ? 'is-miss' : ''?>">Đã xếp <?=$r['da_xep']?>/<?=(int)$r['so_gio']?> giờ</span><?php endif; ?>
					<?php if($r['ghi_chu'] !== ''): ?><span class="dt-lo-note"><i class="far fa-comment-dots" aria-hidden="true"></i> <?=$loE($r['ghi_chu'])?></span><?php endif; ?>
				</div>
				<?php if($open): ?>
				<details class="dt-lo-edit">
					<summary><i class="fas fa-pen" aria-hidden="true"></i> Sửa</summary>
					<form method="post" action="index.php?com=cong-giao-vien&amp;tab=lichon" class="dt-lo-form" data-lo-confirm data-other="<?=$mineTotal - (int)$r['so_gio']?>" data-xn="<?=$loE($mineXn)?>" data-title="<?=$loE(lo_dot_title($dot))?>">
						<input type="hidden" name="dt_act" value="loDangky"><input type="hidden" name="dt_csrf" value="<?=$loE(dt_gv_csrf_token())?>"><input type="hidden" name="xac_nhan" value="">
						<input type="hidden" name="id_dot" value="<?=(int)$dot['id']?>"><input type="hidden" name="hang" value="<?=$loE($r['hang'])?>">
						<label><span>Số giờ (tối đa <?=$max?>)</span><input type="number" name="so_gio" min="<?=max(1, $r['da_xep'])?>" max="<?=$max?>" value="<?=(int)$r['so_gio']?>" required></label>
						<label class="is-wide"><span>Ghi chú</span><input type="text" name="ghi_chu" maxlength="1000" value="<?=$loE($r['ghi_chu'])?>" placeholder="VD: chỉ tập buổi chiều"></label>
						<button type="submit"><i class="fas fa-save" aria-hidden="true"></i> Lưu</button>
					</form>
					<?php if(!$r['da_xep']): ?>
					<form method="post" action="index.php?com=cong-giao-vien&amp;tab=lichon" onsubmit="return confirm('Hủy đăng ký <?=$loE($r['hang'])?>?')" class="dt-lo-cancel">
						<input type="hidden" name="dt_act" value="loHuy"><input type="hidden" name="dt_csrf" value="<?=$loE(dt_gv_csrf_token())?>">
						<input type="hidden" name="id_dot" value="<?=(int)$dot['id']?>"><input type="hidden" name="hang" value="<?=$loE($r['hang'])?>">
						<button type="submit"><i class="fas fa-times" aria-hidden="true"></i> Hủy đăng ký này</button>
					</form>
					<?php endif; ?>
				</details>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>

			<?php if($open && $canAdd): ?>
			<form method="post" action="index.php?com=cong-giao-vien&amp;tab=lichon" class="dt-lo-form dt-lo-add" data-lo-confirm data-other="<?=$mineTotal?>" data-xn="<?=$loE($mineXn)?>" data-title="<?=$loE(lo_dot_title($dot))?>">
				<input type="hidden" name="dt_act" value="loDangky"><input type="hidden" name="dt_csrf" value="<?=$loE(dt_gv_csrf_token())?>"><input type="hidden" name="xac_nhan" value="">
				<input type="hidden" name="id_dot" value="<?=(int)$dot['id']?>">
				<label><span>Hạng xe</span>
					<select name="hang" required>
						<?php foreach($canAdd as $h => $c): $free = max(0, $c['tong_gio'] - $c['da_dk']); ?><option value="<?=$loE($h)?>" data-max="<?=$free?>" <?=$free ? '' : 'disabled'?>><?=$loE($h)?> — còn <?=$free?> giờ</option><?php endforeach; ?>
					</select>
				</label>
				<label><span>Số giờ đăng ký</span><input type="number" name="so_gio" min="1" required placeholder="VD: 10"></label>
				<label class="is-wide"><span>Ghi chú (ngày / khung giờ mong muốn)</span><input type="text" name="ghi_chu" maxlength="1000" placeholder="VD: ưu tiên thứ 3, chỉ tập buổi chiều"></label>
				<button type="submit"><i class="fas fa-plus" aria-hidden="true"></i> Đăng ký</button>
			</form>
			<?php elseif($st === 'khoa_dk'): ?>
			<p class="dt-lo-info"><i class="fas fa-lock" aria-hidden="true"></i> Đã khóa đăng ký, bộ phận đào tạo đang xếp lịch. Lịch sẽ hiện tại đây khi được công bố.</p>
			<?php endif; ?>
		</div>

		<?php if($st === 'cong_bo'): ?>
		<div class="dt-lo-section">
			<h3>Lịch ôn của bạn</h3>
			<?php if(!$L['blocks']): ?><p class="dt-lo-muted">Bạn không có giờ ôn trong lịch đã công bố.</p><?php endif; ?>
			<div class="dt-lo-days">
			<?php foreach($L['blocks'] as $day => $blocks): ?>
				<div class="dt-lo-day">
					<div class="dt-lo-day-head"><b><?=lo_weekday($day)?></b> <?=lo_date_vn($day)?></div>
					<?php foreach($blocks as $b): ?>
					<div class="dt-lo-slot"><span class="dt-lo-time"><?=lo_range_label($b['gio_bd'], $b['gio_kt'])?></span><span class="dt-lo-hang"><?=$loE($b['hang'])?></span><span>Xe <b><?=$loE($b['so_xe'])?></b></span><small><?=$b['gio_kt'] - $b['gio_bd']?> giờ</small></div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</article>
	<?php endforeach; ?>
</section>
<div class="dt-lo-modal" data-lo-modal hidden>
	<div class="dt-lo-dialog" role="dialog" aria-modal="true" aria-labelledby="dt-lo-modal-title">
		<h3 id="dt-lo-modal-title"><i class="fas fa-clipboard-check" aria-hidden="true"></i> Xác nhận đăng ký giờ ôn</h3>
		<p class="dt-lo-modal-sub" data-lo-m-title></p>
		<div class="dt-lo-modal-sum">
			<div><span>Hạng xe</span><b data-lo-m-hang></b></div>
			<div><span>Số giờ đăng ký</span><b data-lo-m-gio></b></div>
			<div><span>Tổng giờ trong đợt</span><b data-lo-m-total></b></div>
		</div>
		<div class="dt-lo-modal-warn" data-lo-m-over hidden>
			<p><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Bạn đăng ký <b>trên <?=$loLimit?> giờ</b>. Vui lòng gửi cho người phụ trách đăng ký, hoặc xác nhận tập song song 2 xe / tập trong 2 ngày:</p>
			<?php foreach($loXn as $k => $x): ?>
			<label class="dt-lo-choice"><input type="radio" name="dt_lo_xn_choice" value="<?=$k?>"><i class="fas <?=$x['icon']?>" aria-hidden="true"></i><span><b><?=$loE($x['label'])?></b><small><?=$k === 'song_song' ? 'Đồng ý đứng 2 xe cùng giờ để tập đủ giờ trong ngày.' : ($k === 'hai_ngay' ? 'Chia số giờ ra 2 ngày tập, không tập song song.' : 'Bộ phận đào tạo sẽ liên hệ và xếp lịch giúp bạn.')?></small></span></label>
			<?php endforeach; ?>
			<p class="dt-lo-modal-err" data-lo-m-err hidden>Vui lòng chọn một cách xử lý.</p>
		</div>
		<div class="dt-lo-modal-actions">
			<button type="button" class="is-ghost" data-lo-m-cancel>Quay lại sửa</button>
			<button type="button" data-lo-m-ok><i class="fas fa-check" aria-hidden="true"></i> Xác nhận đăng ký</button>
		</div>
	</div>
</div>
<script>
document.querySelectorAll('.dt-lo-add').forEach(function(f){
	var s = f.querySelector('select[name=hang]'), n = f.querySelector('input[name=so_gio]');
	function sync(){ var o = s.options[s.selectedIndex]; if(o) n.max = o.getAttribute('data-max'); }
	s.addEventListener('change', sync); sync();
});
(function(){
	var LIMIT = <?=(int)$loLimit?>, modal = document.querySelector('[data-lo-modal]'), form = null;
	if(!modal) return;
	function q(sel){ return modal.querySelector(sel); }
	function close(){ modal.hidden = true; document.body.classList.remove('dt-lo-noscroll'); form = null; }
	document.querySelectorAll('[data-lo-confirm]').forEach(function(f){
		f.addEventListener('submit', function(e){
			if(f.getAttribute('data-ok') === '1') return;
			e.preventDefault();
			form = f;
			var hang = f.querySelector('[name=hang]').value;
			var gio = parseInt(f.querySelector('[name=so_gio]').value, 10) || 0, total = (parseInt(f.getAttribute('data-other'), 10) || 0) + gio;
			q('[data-lo-m-title]').textContent = f.getAttribute('data-title') || '';
			q('[data-lo-m-hang]').textContent = hang;
			q('[data-lo-m-gio]').textContent = gio + ' giờ';
			q('[data-lo-m-total]').textContent = total + ' giờ';
			var over = total > LIMIT, xn = f.getAttribute('data-xn') || '';
			q('[data-lo-m-over]').hidden = !over;
			q('[data-lo-m-err]').hidden = true;
			modal.querySelectorAll('[name=dt_lo_xn_choice]').forEach(function(r){ r.checked = over && r.value === xn; });
			modal.classList.toggle('is-over', over);
			modal.hidden = false;
			document.body.classList.add('dt-lo-noscroll');
			(over ? q('[name=dt_lo_xn_choice]') : q('[data-lo-m-ok]')).focus();
		});
	});
	q('[data-lo-m-cancel]').addEventListener('click', close);
	modal.addEventListener('click', function(e){ if(e.target === modal) close(); });
	document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && !modal.hidden) close(); });
	q('[data-lo-m-ok]').addEventListener('click', function(){
		if(!form) return;
		var xn = '';
		if(!q('[data-lo-m-over]').hidden)
		{
			var c = modal.querySelector('[name=dt_lo_xn_choice]:checked');
			if(!c) { q('[data-lo-m-err]').hidden = false; return; }
			xn = c.value;
		}
		form.querySelector('[name=xac_nhan]').value = xn;
		form.setAttribute('data-ok', '1');
		var f = form; close(); f.submit();
	});
})();
</script>
<style>
.dt-lo-tabbadge { display: inline-flex; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: #e5484d; color: #fff; font-size: 11px; align-items: center; justify-content: center; }
.dt-lo { max-width: 960px; }
.dt-lo-intro h2 { font-size: 18px; margin: 0 0 4px; color: #1e293b; }
.dt-lo-intro h2 i { color: #2954f2; margin-right: 6px; }
.dt-lo-intro p { color: #64748b; margin: 0 0 14px; font-size: 14px; }
.dt-lo-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 36px 16px; border: 1px dashed #cbd5e1; border-radius: 14px; color: #64748b; text-align: center; }
.dt-lo-empty i { font-size: 34px; color: #94a3b8; }
.dt-lo-empty strong { color: #334155; }
.dt-lo-card { border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; margin-bottom: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,.05); }
.dt-lo-card.is-mo_dk { border-color: #9fd8b4; }
.dt-lo-head { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #eef2f7; flex-wrap: wrap; }
.dt-lo-date { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; width: 50px; height: 50px; border-radius: 12px; background: #eef2ff; color: #2954f2; line-height: 1; }
.dt-lo-date b { font-size: 20px; }
.dt-lo-date small { font-size: 10px; color: #64748b; margin-top: 3px; }
.dt-lo-title { flex: 1; min-width: 180px; }
.dt-lo-title strong { display: block; font-size: 16px; color: #0f172a; }
.dt-lo-title span { color: #64748b; font-size: 13px; }
.dt-lo-pill { padding: 5px 11px; border-radius: 20px; font-size: 12px; font-weight: 700; background: #e2e8f0; color: #475569; }
.dt-lo-pill.is-mo_dk { background: #dcfce7; color: #15803d; }
.dt-lo-pill.is-khoa_dk { background: #fef3c7; color: #a16207; }
.dt-lo-pill.is-cong_bo { background: #dbeafe; color: #1d4ed8; }
.dt-lo-cap { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; padding: 14px 16px 0; }
.dt-lo-cap-item { border: 1px solid #eef2f7; border-radius: 10px; padding: 9px 11px; }
.dt-lo-cap-item > div:first-child { display: flex; justify-content: space-between; font-size: 13px; }
.dt-lo-cap-item > div:first-child span { color: #15803d; font-weight: 600; }
.dt-lo-cap-item > div:first-child span.is-full { color: #b91c1c; }
.dt-lo-bar { height: 5px; border-radius: 4px; background: #eef2f7; margin: 6px 0; overflow: hidden; }
.dt-lo-bar i { display: block; height: 100%; background: #60a5fa; }
.dt-lo-cap-item small { display: flex; flex-wrap: wrap; gap: 4px; }
.dt-lo-cap-item small span { background: #f1f5f9; border-radius: 4px; padding: 1px 6px; color: #475569; font-size: 11.5px; }
.dt-lo-section { padding: 14px 16px; }
.dt-lo-section + .dt-lo-section { border-top: 1px solid #eef2f7; }
.dt-lo-section h3 { font-size: 14px; font-weight: 700; color: #334155; margin: 0 0 10px; text-transform: uppercase; letter-spacing: .3px; }
.dt-lo-muted { color: #94a3b8; margin: 0 0 10px; font-size: 14px; }
.dt-lo-info { margin: 6px 0 0; padding: 10px 12px; border-radius: 10px; background: #fffbeb; color: #92400e; font-size: 14px; }
.dt-lo-reg { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; }
.dt-lo-reg-main { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; }
.dt-lo-reg-main strong { font-size: 16px; color: #0f172a; }
.dt-lo-hang { display: inline-block; padding: 2px 9px; border-radius: 6px; background: #eef2ff; color: #2954f2; font-weight: 700; font-size: 12.5px; }
.dt-lo-xep { font-size: 13px; color: #15803d; font-weight: 600; }
.dt-lo-xep.is-miss { color: #b45309; }
.dt-lo-note { font-size: 13px; color: #64748b; }
.dt-lo-edit { margin-top: 8px; }
.dt-lo-edit summary { color: #2954f2; font-size: 13px; font-weight: 600; cursor: pointer; list-style: none; display: inline-flex; gap: 5px; align-items: center; }
.dt-lo-edit summary::-webkit-details-marker { display: none; }
.dt-lo-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; margin-top: 10px; }
.dt-lo-form label { display: flex; flex-direction: column; gap: 4px; margin: 0; min-width: 140px; }
.dt-lo-form label.is-wide { flex: 1; min-width: 220px; }
.dt-lo-form label span { font-size: 12px; color: #64748b; font-weight: 600; }
.dt-lo-form input, .dt-lo-form select { height: 40px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; width: 100%; background: #fff; }
.dt-lo-form input:focus, .dt-lo-form select:focus { outline: none; border-color: #2954f2; box-shadow: 0 0 0 3px rgba(41,84,242,.15); }
.dt-lo-form button { height: 40px; padding: 0 18px; border: none; border-radius: 8px; background: #2954f2; color: #fff; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.dt-lo-form button:hover { background: #1f43cc; }
.dt-lo-add { padding: 12px; border: 1px dashed #9db4ff; border-radius: 10px; background: #f7f9ff; }
.dt-lo-cancel { margin-top: 8px; }
.dt-lo-cancel button { border: none; background: none; color: #b91c1c; font-size: 13px; cursor: pointer; padding: 0; }
.dt-lo-days { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; }
.dt-lo-day { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.dt-lo-day-head { padding: 8px 12px; background: #eff6ff; color: #1e3a8a; font-size: 14px; }
.dt-lo-slot { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-top: 1px solid #f1f5f9; font-size: 14px; flex-wrap: wrap; }
.dt-lo-time { font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
.dt-lo-slot small { margin-left: auto; color: #64748b; }
@media (max-width: 600px) { .dt-gv-tabs { overflow-x: auto; } .dt-gv-tabs a { padding: 10px 12px; white-space: nowrap; } .dt-lo-form label { min-width: 100%; } }
.dt-lo-total { margin-left: 8px; padding: 2px 9px; border-radius: 12px; background: #eef2ff; color: #2954f2; font-size: 12px; text-transform: none; letter-spacing: 0; }
.dt-lo-total.is-over { background: #fef3c7; color: #a16207; }
.dt-lo-xn { margin: 0 0 10px; padding: 8px 12px; border-radius: 10px; background: #f0f9ff; color: #075985; font-size: 13.5px; }
.dt-lo-xn i { margin-right: 4px; }
body.dt-lo-noscroll { overflow: hidden; }
.dt-lo-modal { position: fixed; inset: 0; z-index: 9999; background: rgba(15,23,42,.5); display: flex; align-items: center; justify-content: center; padding: 16px; }
.dt-lo-modal[hidden] { display: none; }
.dt-lo-dialog { width: 100%; max-width: 460px; max-height: calc(100vh - 32px); overflow-y: auto; background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 20px 50px rgba(15,23,42,.3); }
.dt-lo-dialog h3 { margin: 0; font-size: 17px; color: #0f172a; }
.dt-lo-dialog h3 i { color: #2954f2; margin-right: 6px; }
.dt-lo-modal.is-over .dt-lo-dialog h3 i { color: #d97706; }
.dt-lo-modal-sub { margin: 4px 0 12px; color: #64748b; font-size: 13px; }
.dt-lo-modal-sum { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
.dt-lo-modal-sum div { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: 8px 10px; }
.dt-lo-modal-sum span { display: block; font-size: 11.5px; color: #64748b; }
.dt-lo-modal-sum b { font-size: 15px; color: #0f172a; }
.dt-lo-modal.is-over .dt-lo-modal-sum div:last-child { background: #fffbeb; border-color: #fde68a; }
.dt-lo-modal.is-over .dt-lo-modal-sum div:last-child b { color: #b45309; }
.dt-lo-modal-warn > p { margin: 0 0 10px; padding: 10px 12px; border-radius: 10px; background: #fffbeb; color: #92400e; font-size: 13.5px; line-height: 1.45; }
.dt-lo-choice { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; margin: 0 0 8px; cursor: pointer; }
.dt-lo-choice:hover { border-color: #9db4ff; }
.dt-lo-choice:has(input:checked) { border-color: #2954f2; background: #f5f8ff; box-shadow: 0 0 0 2px rgba(41,84,242,.15); }
.dt-lo-choice input { margin-top: 3px; accent-color: #2954f2; }
.dt-lo-choice > i { color: #2954f2; margin-top: 3px; width: 16px; text-align: center; }
.dt-lo-choice span { display: flex; flex-direction: column; font-size: 14px; color: #0f172a; }
.dt-lo-choice small { color: #64748b; font-size: 12.5px; }
.dt-lo-modal-warn > p.dt-lo-modal-err { color: #b91c1c; background: none; padding: 0; font-size: 13px; margin: 0 0 6px; }
.dt-lo-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }
.dt-lo-modal-actions button { height: 40px; padding: 0 16px; border-radius: 8px; border: none; background: #2954f2; color: #fff; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.dt-lo-modal-actions button.is-ghost { background: #f1f5f9; color: #334155; }
@media (max-width: 480px) { .dt-lo-modal-sum { grid-template-columns: 1fr 1fr; } }
</style>
