<?php 
	$gioithieu = $d->rawQueryOne("select ten$lang as ten,mota$lang as mota, photo from #_static where type = ? limit 0,1",array('gioi-thieu'));
?>
<div class="wap_gioithieu">
	<div class="gioithieu main_fix">
		<div class="img_gt <?=$hieuung[1]?>"><a href="gioi-thieu"><img src="<?=UPLOAD_NEWS_L.$gioithieu['photo']?>" alt="<?=$gioithieu['ten']?>"></a></div>
		<div class="desc_gt <?=$hieuung[0]?>">
			<p class="title_gt"><?=$gioithieu['ten']?><span>Bách việt</span></p>
			<?=htmlspecialchars_decode($gioithieu['mota'])?>
		</div>
	</div>
	<p class="banner main_fix"><img src="<?=$func->get_photo('banner')?>" alt="banner"/></p>
</div>