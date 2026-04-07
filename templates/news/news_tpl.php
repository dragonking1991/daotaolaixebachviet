<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
<?=htmlspecialchars_decode($noidung_cap);?>
<div class="wap_news">
    <?=$func->get_posts($news,'item_news',THUMBS.'/400x300x1/');  ?>
</div>

<?php if(count($news)==0) echo '<div class="alert alert-warning" role="alert"><strong>'.khongtimthayketqua.'</strong></div>'; ?>
<div class="clear"></div>
<div class="pagination-home"><?=(isset($paging) && $paging != '') ? $paging : ''?></div>
