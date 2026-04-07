<?php 
	$thongke = $d->rawQuery("select ten$lang as ten, tenkhongdauvi, tenkhongdauen, mota$lang as mota from #_news where type = ? and hienthi > 0 order by stt,id desc",array('thong-ke'));
?>
<div class="wap_thongke">
	<div class="thongke main_fix">
		<?=$func->get_posts_no($thongke,'item_tk');  ?>
	</div>
</div>
<script>
	$(document).ready(function($) {
		var chay = 0;
		$(window).scroll(function(){
			if($(window).scrollTop() > ($('.thongke').offset().top-400) && chay==0){
			    <?php foreach ($thongke as $k => $v) { ?>
			        $('.thongke .tk<?=$k?>').animateNumber({ number: <?=$v['ten']?>},2000); 
			    <?php } ?>
			    chay = 1;  
			}
		});
	});
</script>