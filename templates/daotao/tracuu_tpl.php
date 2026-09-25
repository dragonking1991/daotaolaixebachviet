<div class="w_1000">
	<div class="title-main"><span><?=(@$title_crumb != '') ? $title_crumb : 'Tra cứu quá trình học'?></span></div>

	<div class="frm_tracuu" style="max-width:560px; margin:0 auto;">
		<form id="dt-lookup-form" method="post" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
			<div style="flex:1; min-width:260px;">
				<label style="display:block; font-size:13px; color:#555; margin-bottom:4px;">Số CCCD</label>
				<input type="text" id="dt_cccd" name="cccd" placeholder="Nhập số CCCD của học viên" required
					style="width:100%; height:42px; padding:0 12px; border:1px solid #ccc; border-radius:6px;">
			</div>
			<div>
				<button type="submit" style="height:42px; padding:0 22px; border:none; border-radius:6px; background:#2954f2; color:#fff; font-weight:700; cursor:pointer;">Tra cứu</button>
			</div>
		</form>
		<p style="margin-top:10px; color:#666; font-size:13px;">Nhập số CCCD để xem tiến độ 4 nội dung: Lý thuyết, Cabin, Thực hành trong hình và DAT (thực hành trên đường).</p>
	</div>

	<div id="dt-lookup-result" style="margin-top:24px;"></div>
</div>

<script type="text/javascript">
(function(){
	var form = document.getElementById('dt-lookup-form');
	if(!form) return;
	form.addEventListener('submit', function(e){
		e.preventDefault();
		var cccd = document.getElementById('dt_cccd').value.trim();
		var box = document.getElementById('dt-lookup-result');
		if(!cccd){ box.innerHTML = ''; return; }
		box.innerHTML = '<p style="text-align:center;color:#888;">Đang tra cứu...</p>';
		var fd = new FormData();
		fd.append('cccd', cccd);
		fetch('ajax/tracuu_daotao.php', { method:'POST', body: fd })
			.then(function(r){ return r.text(); })
			.then(function(html){ box.innerHTML = html; })
			.catch(function(){ box.innerHTML = '<p style="text-align:center;color:#c00;">Có lỗi xảy ra, vui lòng thử lại.</p>'; });
	});
})();
</script>
