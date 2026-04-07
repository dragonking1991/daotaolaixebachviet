<?php 
	$visao = $d->rawQuery("select ten$lang as ten, tenkhongdauvi, tenkhongdauen, mota$lang as mota, ngaytao, id, photo from #_news where type = ? and hienthi > 0 order by stt,id desc",array('vi-sao'));
?>
<div class="wap_visao">
	<div class="title-main <?=$hieuung[2]?>"><span><?=visaochonchungtoi?></span></div>
	<p class="hinh_vs <?=$hieuung[4]?> hidden_m2" href=""><img src="<?=$func->get_photo('hinh_vs')?>"/></p>
	<div class="visao main_fix">
		<?=$func->get_posts_no($visao,'item_vs',THUMBS.'/100x100x2/');  ?>
	</div>
</div>