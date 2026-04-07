<?php 
    $tintuc = $d->rawQuery("select ten$lang as ten, tenkhongdauvi, tenkhongdauen, mota$lang as mota, ngaytao, id, photo, options from #_news where type = ? and noibat > 0 and hienthi > 0 order by stt,id desc limit 0,15",array('tin-tuc'));
?>
<div class="tintuc">
    <p class="title-intro"><span>Thông tin mới nhất</span></p>
    <div class="tintuc2">
        <div class="news_left">
            <p class="img_post"><a href="<?=$tintuc[0][$sluglang]?>"><img onerror="this.src='<?=THUMBS?>/380x250x2/assets/images/noimage.png';" src="<?=THUMBS?>/380x250x1/<?=UPLOAD_NEWS_L.$tintuc[0]['photo']?>" alt="<?=$tintuc[0]['ten']?>"></a></p>
            <h3><a class="name_post catchuoi2" href="<?=$tintuc[0][$sluglang]?>"><?=$tintuc[0]['ten']?></a></h3>
            <p class="desc_post catchuoi3"><?=$tintuc[0]['mota']?></p>
        </div>

        <div class="news_right">
            <?=$func->get_posts_slick($tintuc,'item_tin',THUMBS.'/200x150x1/');  ?>
        </div>
    </div>
</div>