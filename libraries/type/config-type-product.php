<?php
    $nametype = "gplx";
    $config['product'][$nametype]['title_main'] = "Giấy phép lái xe";
    $config['product'][$nametype]['import'] = true;

    $nametype = "gxn";
    $config['product'][$nametype]['title_main'] = "Giấy xác nhận";
    $config['product'][$nametype]['import'] = true;

    $nametype = "qr";
    $config['product'][$nametype]['title_main'] = "QR thanh toán";
    $config['product'][$nametype]['import'] = true;
    $config['product'][$nametype]['gia'] = true;
    $config['product'][$nametype]['images'] = true;
    $config['product'][$nametype]['show_images'] = true;
    $config['product'][$nametype]['width'] = 300;
    $config['product'][$nametype]['height'] = 300;
    $config['product'][$nametype]['thumb'] = '100x100x1';
    $config['product'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif|.JPG|.PNG|.JPEG|.Png|.GIF';
?>