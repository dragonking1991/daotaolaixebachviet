<div class="grid-pro-detail">
    <?php $thumbsp = THUMBS.'/600x600x1/';//$thumbsp = WATERMARK.'/product/540x540x1/';?>
    <div class="left-pro-detail">

        <div class="album_pro">
            <a data-zoom-id="Zoom-detail" id="Zoom-detail" class="MagicZoom" href="<?=$thumbsp?><?=UPLOAD_PRODUCT_L.$row_detail['photo']?>" title="<?=$row_detail['ten']?>"><img class="cloudzoom no_lazy" onerror="this.src='<?=$thumbsp?>assets/images/noimage.png';" src="<?=$thumbsp?><?=UPLOAD_PRODUCT_L.$row_detail['photo']?>" alt="<?=$row_detail['ten']?>"></a>

            <?php if(count($hinhanhsp) > 0) { ?>
                <?php foreach($hinhanhsp as $v) { ?>
                <a data-zoom-id="Zoom-detail" id="Zoom-detail" class="MagicZoom" href="<?=$thumbsp?><?=UPLOAD_PRODUCT_L.$v['photo']?>" title="<?=$row_detail['ten']?>"><img class="cloudzoom no_lazy" onerror="this.src='<?=$thumbsp?>assets/images/noimage.png';" src="<?=$thumbsp?><?=UPLOAD_PRODUCT_L.$v['photo']?>" alt="<?=$row_detail['ten']?>"></a>
            <?php }} ?>
        </div>

        <div class="album_pro2">
            <p><img class="cloudzoom no_lazy" onerror="this.src='<?=$thumbsp?>assets/images/noimage.png';" src="<?=$thumbsp?><?=UPLOAD_PRODUCT_L.$row_detail['photo']?>" alt="<?=$row_detail['ten']?>"></p>
            
            <?php if(count($hinhanhsp) > 0) { ?>
                <?php foreach($hinhanhsp as $v) { ?>
                <p><img class="cloudzoom no_lazy" onerror="this.src='<?=$thumbsp?>assets/images/noimage.png';" src="<?=$thumbsp?><?=UPLOAD_PRODUCT_L.$v['photo']?>" alt="<?=$row_detail['ten']?>"></p>
            <?php }} ?>
        </div>
    </div>


    <div class="right-pro-detail">
        <p class="title-pro-detail"><?=$row_detail['ten']?></p>
        <?php include TEMPLATE.LAYOUT."share.php"; ?>

        <ul class="attr-pro-detail">
            <li> 
                <label class="attr-label-pro-detail"><?=masp?>:</label>
                <div class="attr-content-pro-detail"><?=(isset($row_detail['masp']) && $row_detail['masp'] != '') ? $row_detail['masp'] : 0?></div>
            </li>
            <?php if(isset($pro_brand['id']) && $pro_brand['id'] > 0) { ?>
                <li>
                    <label class="attr-label-pro-detail"><?=thuonghieu?>:</label>
                    <div class="attr-content-pro-detail"><a class="text-decoration-none" href="<?=$pro_brand[$sluglang]?>" title="<?=$pro_brand['ten']?>"><?=$pro_brand['ten']?></a></div>
                </li>
            <?php } ?>
            <li>
                <label class="attr-label-pro-detail"><?=gia?>:</label>
                <div class="attr-content-pro-detail">
                    <?php if($row_detail['giamoi']) { ?>
                        <span class="price-new-pro-detail"><?=$func->format_money($row_detail['giamoi'])?></span>
                        <span class="price-old-pro-detail"><?=$func->format_money($row_detail['gia'])?></span>
                    <?php } else { ?>
                        <span class="price-new-pro-detail"><?=($row_detail['gia']) ? $func->format_money($row_detail['gia']) : lienhe?></span>
                    <?php } ?>
                </div>
            </li>

            <?php if($config['order']['active']==true) { ?>
            <li class="clear cart-hidden">
                <label class="attr-label-pro-detail d-block"><?=mausac?>:</label>
                <div class="attr-content-pro-detail d-block">
                    <?php for($i=0;$i<count($mau);$i++) { ?>
                        <?php if($mau[$i]['loaihienthi']==1) { ?>
                            <a class="color-pro-detail text-decoration-none" data-idpro="<?=$row_detail['id']?>">
                                <input style="background-image: url(<?=UPLOAD_COLOR_L.$mau[$i]['photo']?>)" type="radio" value="<?=$mau[$i]['id']?>" name="color-pro-detail">
                            </a>
                        <?php } else { ?>
                            <a class="color-pro-detail text-decoration-none" data-idpro="<?=$row_detail['id']?>">
                                <input style="background-color: #<?=$mau[$i]['mau']?>" type="radio" value="<?=$mau[$i]['id']?>" name="color-pro-detail">
                            </a>
                        <?php } ?>
                    <?php } ?>
                </div>
            </li>
            <li class="clear cart-hidden">
                <label class="attr-label-pro-detail d-block"><?=kichthuoc?>:</label>
                <div class="attr-content-pro-detail d-block">
                    <?php for($i=0;$i<count($size);$i++) { ?>
                        <a class="size-pro-detail text-decoration-none">
                            <input type="radio" value="<?=$size[$i]['id']?>" name="size-pro-detail">
                            <?=$size[$i]['ten']?>
                        </a>
                    <?php } ?>
                </div> 
            </li>
            <li class="clear cart-hidden"> 
                <label class="attr-label-pro-detail d-block"><?=soluong?>:</label>
                <div class="attr-content-pro-detail d-block">
                    <div class="quantity-pro-detail">
                        <span class="quantity-minus-pro-detail">-</span>
                        <input type="number" class="qty-pro" min="1" value="1" readonly />
                        <span class="quantity-plus-pro-detail">+</span>
                    </div>
                </div>
            </li>
            <?php } ?>
            <li> 
                <label class="attr-label-pro-detail"><?=luotxem?>:</label>
                <div class="attr-content-pro-detail"><?=$row_detail['luotxem']?></div>
            </li>
        </ul>

        <div class="desc-pro-detail"><?=htmlspecialchars_decode($row_detail['mota'])?></div>

        <?php if($config['order']['active']==true) { ?>
        <div class="cart-pro-detail cart-hidden">
            <a class="transition addnow addcart text-decoration-none" data-id="<?=$row_detail['id']?>" data-action="addnow"><i class="fas fa-shopping-bag"></i><span>Thêm vào giỏ hàng</span></a>
            <a class="transition buynow addcart text-decoration-none" data-id="<?=$row_detail['id']?>" data-action="buynow"><i class="fas fa-shopping-bag"></i><span>Đặt hàng</span></a>
        </div>
        <?php } ?>
    </div>

    <div class="clear"></div>

    <div class="tags-pro-detail clear">
        <?php if(isset($pro_tags) && count($pro_tags) > 0) { foreach($pro_tags as $v) { ?>
            <a class="transition text-decoration-none clear" href="<?=$v[$sluglang]?>" title="<?=$v['ten']?>"><i class="fas fa-tags"></i><?=$v['ten']?></a>
        <?php } } ?>
    </div>

    <div class="clear"></div>

    <div class="tabs-pro-detail">
        <ul class="ul-tabs-pro-detail clear">
            <li class="active transition" data-tabs="info-pro-detail"><?=thongtinsanpham?></li>
            <li class="transition" data-tabs="commentfb-pro-detail"><?=binhluan?></li>
        </ul>
        <div class="content-tabs-pro-detail info-pro-detail active"><?=htmlspecialchars_decode($row_detail['noidung'])?></div>
        <div class="content-tabs-pro-detail commentfb-pro-detail"><div class="fb-comments" data-href="<?=$func->getCurrentPageURL()?>" data-numposts="3" data-colorscheme="light" data-width="100%"></div></div>
    </div>
</div>



<div class="title-main"><span><?=sanphamcungloai?></span></div>
<div class="wap_item">
    <?php echo $func->get_product($product);?>
</div>
<?php if(count($product)==0) echo '<div class="alert alert-warning" role="alert"><strong>'.khongtimthayketqua.'</strong></div>'; ?>
<div class="pagination-home"><?=(isset($paging) && $paging != '') ? $paging : ''?></div>