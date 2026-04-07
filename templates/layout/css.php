<?php
    $css->setCache("cached");
    $css->setCss("./assets/bootstrap/bootstrap.css");
    $css->setCss("./assets/css/all.css");
    $css->setCss("./assets/fancybox3/jquery.fancybox.css");
    $css->setCss("./assets/slick/slick.css");
    $css->setCss("./assets/slick/slick-theme.css");
    $css->setCss("./assets/fotorama/fotorama.css");
    //$css->setCss("./assets/magiczoomplus/magiczoomplus.css");
    $css->setCss("./assets/css/style.css");
    //$css->setCss("./assets/css/cart.css");
    $css->setCss("./assets/css/media.css");
    //$css->setCss("./assets/login/login.css");
    //$css->setCss("./assets/datetimepicker/jquery.datetimepicker.css");
    //$css->setCss("./assets/css/animate.css");
    echo $css->getCss();
?>
<?=htmlspecialchars_decode($setting['analytics'])?>
<?=htmlspecialchars_decode($setting['headjs'])?>
<script type="text/javascript" src="./assets/js/jquery.min.js"></script>
