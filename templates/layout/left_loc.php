<?php 
    $mucgia = $d->rawQuery("select ten$lang as ten, id from #_news where type = ? and hienthi > 0 order by stt,id desc",array('muc-gia'));

    $brand = $d->rawQuery("select ten$lang as ten, id from #_product_brand where type = ? and hienthi > 0 order by stt,id desc",array($type));
?>
<div class="danhmuc">
    <div class="tieude">Sản phẩm</div>
    <?=$func->formenu('product',$type);?>
</div>

<div class="danhmuc boloc mucgia">
    <div class="tieude">Khoảng giá</div>
    <?php foreach ($mucgia as $k => $v) { ?>
    	<p data-id="<?=$v['id']?>"><i class="fas fa-check"></i><?=$v['ten']?></p>
    <?php } ?>
</div>

<div class="danhmuc boloc phanloai">
    <div class="tieude">Phân loại</div>
    <?php foreach ($brand as $k => $v) { ?>
    	<p data-id="<?=$v['id']?>"><i class="fas fa-check"></i><?=$v['ten']?></p>
    <?php } ?>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('.boloc p').click(function(event) {
        	if($(this).hasClass('active'))$(this).removeClass('active');
        	else $(this).addClass('active');
        	var id_list = $('.load_page_sanpham').data('id-list');
            var id_cat = $('.load_page_sanpham').data('id-cat');
            var type = $('.load_page_sanpham').data('type');

        	var mucgia = '';
        	var phanloai = '';
        	$('.mucgia .active').each(function(index, element) {
            	mucgia += $(this).data('id')+',';
        	}); 
        	$('.phanloai .active').each(function(index, element) {
            	phanloai += $(this).data('id')+',';
        	}); 
        	loadData(1,".load_page_sanpham","<?=$idl?>","<?=$idc?>",type,mucgia,phanloai); 
            event.preventDefault();
        }); 
    });
</script>

<script type="text/javascript">
	$(document).ready(function() {
        loadData(1,".load_page_sanpham","<?=$idl?>","<?=$idc?>","<?=$type?>","",""); 
    });
   
    function loadData(page,id_tab,id_list,id_cat,type,mucgia,phanloai){
        $.ajax({
            type: "POST",
            url: "paging_ajax/ajax_paging.php",
            data: {page:page,id_list:id_list,id_cat:id_cat,type:type,mucgia:mucgia,phanloai:phanloai},
            success: function(msg)
            {
                $("#loadbody").fadeOut("fast");
                $(id_tab).html(msg);
                  $(id_tab+" .pagination li.active").click(function(){
                    var pager = $(this).attr("p");
                    var id_list = $('.load_page_sanpham').data('id-list');
                    var id_cat = $('.load_page_sanpham').data('id-cat');
                    var type = $('.load_page_sanpham').data('type');
                    var phanloai = '';
                    var mucgia = '';
                    $('.mucgia .active').each(function(index, element) {
	                	mucgia += $(this).val()+',';
	            	}); 
	            	$('.phanloai .active').each(function(index, element) {
	                	phanloai += $(this).val()+',';
	            	}); 
                    loadData(pager,".load_page_sanpham",id_list,id_cat,type,mucgia,phanloai);
                });
            }
        });
    }
</script>


