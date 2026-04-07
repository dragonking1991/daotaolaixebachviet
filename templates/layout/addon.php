<?php //if($source!='contact') echo $addons->setAddons('footer-map', 'footer-map', 10);?>
<div class="quick_contact ">
    <a class="button_gradient" href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline']);?>">
        <span class="button_gradient"><i class="fas fa-phone-alt"></i></span>
        <p class="contact-phone"><?=$optsetting['hotline']?></p>
    </a>
</div>
<?php echo $addons->setAddons('messages-facebook', 'messages-facebook', 10);?>
<a class="btn-phone btn-frame text-decoration-none " href="lien-he">
    <div class="animated infinite zoomIn kenit-alo-circle"></div>
    <div class="animated infinite pulse kenit-alo-circle-fill"></div>
    <i>Đăng ký</i>
</a>

<a class="btn-zalo btn-frame text-decoration-none " target="_blank" href="https://zalo.me/<?=preg_replace('/[^0-9]/','',$optsetting['zalo']);?>">
    <div class="animated infinite zoomIn kenit-alo-circle"></div>
    <div class="animated infinite pulse kenit-alo-circle-fill"></div>
    <i><img src="assets/images/zl.png" alt="Zalo" class="no_lazy"></i>
</a>
