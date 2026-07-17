<div class="w_1000">
	<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
	<?php if(!empty($static['noidung'])) { ?>
		<div class="content-main"><?=htmlspecialchars_decode($static['noidung'])?></div>
	<?php } ?>

	<div class="frm_tracuu">
		<input type="hidden" id="tracuu_type" value="qr">
		<div class="frm_tracuu2">
			<input type="text" placeholder="Nhập số CCCD" id="input_cccd">
			<p class="c_tracuu">Tra cứu</p>
		</div>
	</div>
</div>
<div class="ketqua"></div>