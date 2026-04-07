<?php 
    $tintuc = $d->rawQuery("select ten$lang, tenkhongdauvi, tenkhongdauen, mota$lang, ngaytao, id, photo, options from #_news where type = ? and noibat > 0 and hienthi > 0 order by stt,id desc",array('tin-tuc'));
?>

<div class="wap_tintuc">
    <div class="main_fix">
        <?php include TEMPLATE.LAYOUT."tintuc.php"; ?>
        <?php include TEMPLATE.LAYOUT."video.php"; ?>
    </div>
</div>


<div class="main_fix">
    <div class="wrap-intro wap-tin-video">
        <div class="left-intro">
            <p class="title-intro"><span>Tin tức mới</span></p>
            <div class="newshome-intro">                
                <a class="newshome-best text-decoration-none" href="<?=$newsnb[0][$sluglang]?>" title="<?=$newsnb[0]['ten'.$lang]?>">
                    <p class="pic-newshome-best scale-img"><img onerror="this.src='<?=THUMBS?>/360x200x2/assets/images/noimage.png';" src="<?=WATERMARK?>/news/360x200x1/<?=UPLOAD_NEWS_L.$newsnb[0]['photo']?>" alt="<?=$newsnb[0]['ten'.$lang]?>"></p>
                    <h3 class="name-newshome text-split"><?=$newsnb[0]['ten'.$lang]?></h3>
                    <p class="time-newshome">Ngày đăng: <?=date("d/m/Y",$newsnb[0]['ngaytao'])?></p>
                    <p class="desc-newshome text-split"><?=$newsnb[0]['mota'.$lang]?></p>
                    <span class="view-newshome transition"><?=xemthem?></span>
                </a>
                <div class="newshome-scroll">
                    <ul>
                        <?php foreach($newsnb as $v) {?>
                            <li>
                                <a class="newshome-normal text-decoration-none" href="<?=$v[$sluglang]?>" title="<?=$v['ten'.$lang]?>">
                                    <p class="pic-newshome-normal scale-img"><img onerror="this.src='<?=THUMBS?>/150x120x2/assets/images/noimage.png';" src="<?=THUMBS?>/150x120x1/<?=UPLOAD_NEWS_L.$v['photo']?>" alt="<?=$v['ten'.$lang]?>"></p>
                                    <div class="info-newshome-normal">
                                        <h3 class="name-newshome text-split"><?=$v['ten'.$lang]?></h3>
                                        <p class="time-newshome"><?=ngaydang?>: <?=date("d/m/Y",$v['ngaytao'])?></p>
                                        <p class="desc-newshome text-split"><?=$v['mota'.$lang]?></p>
                                    </div>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="right-intro">
            <p class="title-intro"><span>Video clip</span></p>
            <div class="videohome-intro">
                <?=$addons->setAddons('video-fotorama', 'video-fotorama', 10);?>
                <?php //$addons->setAddons('video-select', 'video-select', 10); ?>
            </div>
        </div>
    </div>
</div>
