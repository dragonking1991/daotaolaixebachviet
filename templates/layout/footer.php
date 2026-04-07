<div class="wap_footer clear">
    <div class="footer main_fix">
        <div class="main_f2">
            <p class="ten"><?=$setting['ten'.$lang]?></p>
            <a class="logo_f" href=""><img src="<?=$func->get_photo('logo')?>" alt="logo"/></a>
            <div class="mxh_f"><?php $func->get_slider('mangxahoi'); ?></div>
        </div>

        <div class="main_f">
            <p class="title_f">Liên hệ</p>
            <?php echo htmlspecialchars_decode($func->get_text('footer'))?>
        </div>

        <div class="item_f">
            <p class="title_f"><?=chinhsach?></p>
            <?=$func->for1('news','chinh-sach')?>
        </div>

        <div class="fanpage_f">
            <p class="title_f">Fanpage facebook</p>
            <?=$addons->setAddons('fanpage-facebook', 'fanpage-facebook', 10);?>
        </div>

    </div>
</div>