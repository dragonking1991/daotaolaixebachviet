<?php 
	$product_list = $d->rawQuery("select ten$lang as ten, tenkhongdauvi, tenkhongdauen,id, photo from #_product_list where type = ? and noibat > 0 and hienthi > 0 order by stt,id desc",array('san-pham'));
?>
<?php foreach ($product_list as $k1 => $v1) { ?>
<?php 
	$product_cat = $d->rawQuery("select photo, ten$lang as ten, tenkhongdauvi, tenkhongdauen, id, type from #_product_cat where id_list='".$v1['id']."' and type = ? and noibat > 0 and hienthi > 0 order by stt,id desc",array('san-pham'));
?>
<div class="wap_sanpham">
	<div class="sanpham main_fix">
		<div class="title-main">
			<span><?=$v1['ten']?></span>
			<ul>
				<?php foreach ($product_cat as $k2 => $v2) { ?>
					<li><a class="cap2" href="<?=$v2[$sluglang]?>" data-id="<?=$v2['id']?>"><?=$v2['ten']?></a></li>
				<?php } ?>
				<li><a href="<?=$v1[$sluglang]?>">Xem tất cả</a></li>
			</ul>
		</div>

	    <div class="load_page_<?=$v1['id']?>" data-list="<?=$v1['id']?>">
	        <script type="text/javascript">
	            $(document).ready(function() {
	                loadData(1,".load_page_<?=$v1['id']?>","<?=$v1['id']?>",""); 
	            });
	        </script>
	    </div>
	</div>
</div>
<?php } ?>

<script type="text/javascript">
    function loadData(page,id_tab,id_list,id_cat){
        $.ajax({
            type: "POST",
            url: "paging_ajax/ajax_paging.php",
            data: {page:page,id_list:id_list,id_cat:id_cat},
            success: function(msg)
            {
                $("#loadbody").fadeOut("fast");
                $(id_tab).html(msg);
                  $(id_tab+" .pagination li.active").click(function(){
                    var pager = $(this).attr("p");
                    var id_listr = $(this).parents().parents().parents().attr("data-list");
                    var id_catr = 0;
                    loadData(pager,".load_page_"+id_listr,id_listr,id_catr);
                });
            }
        });
    }
</script>