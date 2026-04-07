<div class="title-main"><span><?=@$title_crumb?></span></div>

<?php if(isset($video) && count($video) > 0) { ?>
	<div class="wap_item">
		<?php echo $func->lay_video($video,THUMBS.'/480x360x1/'); ?>
	</div>
<?php } else { ?>
    <div class="alert alert-warning" role="alert">
        <strong><?=khongtimthayketqua?></strong>
    </div>
<?php } ?>
<div class="clear"></div>
<div class="pagination-home"><?=(isset($paging) && $paging != '') ? $paging : ''?></div>
