<div class="title-main"><span><?=$row_detail['ten']?></span></div>

<div class="sapxep">
    <?php foreach($hinhanhsp as $k => $v) { ?>
        <p class="item_sx"><a class="hover_sang2" data-fancybox="album_detail" href="<?=UPLOAD_PRODUCT_L.$v['photo']?>" title="<?=$v['ten']?>"><img src="<?=THUMBS?>/500x500x3/<?=UPLOAD_PRODUCT_L.$v['photo']?>" alt="<?=$v['ten']?>"></a></p>
    <?php } ?>
</div>