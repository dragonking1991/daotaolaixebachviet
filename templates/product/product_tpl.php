<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
<?=htmlspecialchars_decode($noidung_cap);?>
<div class="wap_item">
	<?php echo $func->get_product($product);?>
</div>
<?php if(count($product)==0) echo '<div class="alert alert-warning" role="alert"><strong>'.khongtimthayketqua.'</strong></div>'; ?>
<div class="pagination-home"><?=(isset($paging) && $paging != '') ? $paging : ''?></div>
