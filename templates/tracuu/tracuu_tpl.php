<div class="w_1000">
	<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
	<div class="content-main"><?=htmlspecialchars_decode($static['noidung'])?></div>

	<div class="frm_tracuu">
		<ul>
			<li class="active" data-type="gplx">Số GPLX</li>
			<li data-type="gxn">Số giấy xác nhận</li>
			<li data-type="qr">QR Thanh toán</li>
		</ul>
		<div class="frm_tracuu2">
			<input type="text" placeholder="Nhập số CCCD" class="cccd">
			<p class="c_tracuu">Tra cứu</p>
		</div>

	</div>
</div>
<div class="ketqua"></div>