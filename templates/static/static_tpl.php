<div class="w_1000">
	<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
	<div class="content-main"><?=htmlspecialchars_decode($static['noidung'])?></div>
	<?php include TEMPLATE.LAYOUT."share.php"; ?>
</div>