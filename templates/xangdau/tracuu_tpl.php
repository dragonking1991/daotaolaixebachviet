<div class="w_1000">
	<div class="title-main"><span><?=(@$title_crumb != '') ? $title_crumb : 'Tra cứu chi phí xăng dầu'?></span></div>

	<div class="frm_tracuu" style="max-width:640px; margin:0 auto;">
		<form id="xd-lookup-form" method="post" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
			<div style="flex:1; min-width:220px;">
				<label style="display:block; font-size:13px; color:#555; margin-bottom:4px;">Số CCCD hoặc Mã tra cứu</label>
				<input type="text" id="xd_cccd" name="cccd" placeholder="Nhập CCCD hoặc mã tra cứu (VD: NV-4-...)" required
					style="width:100%; height:40px; padding:0 12px; border:1px solid #ccc; border-radius:6px;">
			</div>
			<div style="min-width:150px;">
				<label style="display:block; font-size:13px; color:#555; margin-bottom:4px;">Từ ngày</label>
				<input type="date" id="xd_from" name="from_date"
					style="width:100%; height:40px; padding:0 10px; border:1px solid #ccc; border-radius:6px;">
			</div>
			<div style="min-width:150px;">
				<label style="display:block; font-size:13px; color:#555; margin-bottom:4px;">Đến ngày</label>
				<input type="date" id="xd_to" name="to_date"
					style="width:100%; height:40px; padding:0 10px; border:1px solid #ccc; border-radius:6px;">
			</div>
			<div>
				<button type="submit" style="height:40px; padding:0 20px; border:none; border-radius:6px; background:#2954f2; color:#fff; font-weight:700; cursor:pointer;">Tra cứu</button>
			</div>
		</form>
		<p style="margin-top:10px; color:#666; font-size:13px;">Giáo viên đăng nhập bằng số CCCD hoặc mã tra cứu để xem hóa đơn xăng dầu và danh sách học viên đã thanh toán của mình.</p>
	</div>

	<div id="xd-lookup-result" style="margin-top:24px;"></div>
</div>

<script type="text/javascript">
(function(){
	var form = document.getElementById('xd-lookup-form');
	if(!form) return;
	form.addEventListener('submit', function(e){
		e.preventDefault();
		var cccd = document.getElementById('xd_cccd').value.trim();
		var fromDate = document.getElementById('xd_from').value;
		var toDate = document.getElementById('xd_to').value;
		var box = document.getElementById('xd-lookup-result');

		if(cccd === ''){ alert('Vui lòng nhập số CCCD hoặc mã tra cứu'); return; }

		box.innerHTML = '<p style="text-align:center; color:#888;">Đang tra cứu...</p>';

		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'ajax/tracuu_xangdau.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onreadystatechange = function(){
			if(xhr.readyState === 4){
				if(xhr.status === 200) box.innerHTML = xhr.responseText;
				else box.innerHTML = '<p style="color:#c00; text-align:center;">Có lỗi xảy ra, vui lòng thử lại.</p>';
			}
		};
		xhr.send('cccd=' + encodeURIComponent(cccd) + '&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate));
	});
})();
</script>
