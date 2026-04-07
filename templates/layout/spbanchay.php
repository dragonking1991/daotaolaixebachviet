<?php 
	$spbanchay = $d->rawQuery("select photo, ten$lang as ten, tenkhongdauvi, tenkhongdauen, giamoi, gia, giakm, id, type from #_product where hienthi=1 and banchay=1 and type = ? limit 0,15",array('san-pham'));
	if(count($spbanchay)>0) {
?>
<div class="spbanchay">
	<div class="title-main"><span><?=sanpham?></span></div>
	<div class="slick4322 control_slick main_fix">
		<?php echo $func->get_product_slick($spbanchay);?>
	</div>
</div>
<?php } ?>