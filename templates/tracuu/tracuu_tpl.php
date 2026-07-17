<div class="w_1000">
	<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
	<?php if(!empty($static['noidung'])) { ?>
		<div class="content-main"><?=htmlspecialchars_decode($static['noidung'])?></div>
	<?php } ?>

	<div class="frm_tracuu">
		<ul>
			<li class="active" data-type="gplx">Số GPLX</li>
			<li data-type="gxn">Số giấy xác nhận</li>
		</ul>
		<div class="frm_tracuu2">
			<input type="text" placeholder="Nhập số CCCD" id="input_cccd">
			<p class="c_tracuu">Tra cứu</p>
		</div>
		<!--
		<p style="margin-top:14px; text-align:center;">
			<a href="tra-cuu-qr-thanh-toan" style="display:inline-block; padding:11px 18px; border-radius:6px; background:#2954f2; color:#fff; font-weight:700; text-decoration:none;">Tra cứu QR thanh toán</a>
		</p>
-->

	</div>
</div>
<div class="ketqua"></div>