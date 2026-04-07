<?php 
	$camket = $d->rawQuery("select ten$lang as ten, tenkhongdauvi, tenkhongdauen, mota$lang as mota, ngaytao, id, photo from #_news where type = ? and hienthi > 0 order by stt,id desc",array('cam-ket'));
?>
<div class="wap_camket">
	<div class="camket main_fix slick4321 control_slick">
		<?=$func->get_posts_no($camket,'item_ck',THUMBS.'/60x60x2/');  ?>
	</div>
</div>