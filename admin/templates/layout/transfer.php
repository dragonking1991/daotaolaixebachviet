<?php
/* Chỉ cho phép URL nội bộ tương đối và vài thẻ định dạng thông báo; mọi dữ liệu còn lại được escape. */
$page_transfer = (string)$page_transfer;
if($page_transfer !== '' && (strpos($page_transfer, 'index.php') !== 0 || preg_match('/[\x00-\x1F\x7F]/', $page_transfer))) $page_transfer = 'index.php';
$transfer_url = htmlspecialchars("admin/".$page_transfer, ENT_QUOTES, 'UTF-8');
$transfer_full_url = htmlspecialchars($basehref."admin/".$page_transfer, ENT_QUOTES, 'UTF-8');
$transfer_text = htmlspecialchars(html_entity_decode((string)@$showtext, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
$transfer_text = preg_replace('#&lt;(/?)(br|strong|em|b)\s*/?&gt;#i', '<$1$2>', $transfer_text);
?>
<HTML>
<HEAD>
    <TITLE>:: Thông Báo ::</TITLE>
    <base href="<?=$basehref?>"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="REFRESH" content="1; url=<?=$transfer_full_url?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="robots" content="noodp,noindex,nofollow" />
    <link rel="stylesheet" href="assets/bootstrap/bootstrap.css">

    <style type="text/css">
        body{background:#eee}
        #alert{background:#fff;padding:20px;margin:30px auto;border-radius:3px;-webkit-box-shadow:0px 0px 3px 0px rgba(50,50,50,0.3);-moz-box-shadow:0px 0px 3px 0px rgba(50,50,50,0.3);box-shadow:0px 0px 3px 0px rgba(50,50,50,0.3);margin-top:100px;text-align:center;width:100%;max-width:400px;}
        #alert .fas{font-size:60px;}
        #alert .rlink{margin:10px 0px;}
        #alert .title{text-transform:uppercase;font-weight:bold;margin:10px;}
        .fasuccess{color:#5cb85c;}
        .fadanger{color:#D9534F;}
        #process-bar{width:0%;-webkit-transition:all 0.3s!important;transition:all 0.3s!important;}
        @font-face{font-family:'Font Awesome 5 Brands';font-style:normal;font-weight:normal;font-display:auto;src:url("assets/fonts/awesome/fa-brands-400.woff2") format("woff2"),url("assets/fonts/awesome/fa-brands-400.woff") format("woff");}
        .fab{font-family:'Font Awesome 5 Brands';}
        @font-face{font-family:'Font Awesome 5 Free';font-style:normal;font-weight:400;font-display:auto;src:url("assets/fonts/awesome/fa-regular-400.woff2") format("woff2"),url("assets/fonts/awesome/fa-regular-400.woff") format("woff");}
        .far{font-family:'Font Awesome 5 Free';font-weight:400;}
        @font-face{font-family:'Font Awesome 5 Free';font-style:normal;font-weight:900;font-display:auto;src:url("assets/fonts/awesome/fa-solid-900.woff2") format("woff2"),url("assets/fonts/awesome/fa-solid-900.woff") format("woff");}
        .fa,.fas{font-family:'Font Awesome 5 Free';font-weight:900;}
        .fa,.fas,.far,.fal,.fab{-moz-osx-font-smoothing:grayscale;-webkit-font-smoothing:antialiased;display:inline-block;font-style:normal;font-variant:normal;text-rendering:auto;line-height:1;}
        .fa-envelope-open:before{content:"\f2b6";}
        .fa-phone:before{content:"\f095";}
        .fa-map-marker-alt:before{content:"\f3c5";}
        .fa-shopping-cart:before{content:"\f07a";}
        .fa-bars:before{content:"\f0c9";}
        .fa-calendar-alt:before{content:"\f073";}
        .fa-search:before{content:"\f002";}
        .fa-exclamation-triangle:before{content:"\f071";}
        .fa-check-circle:before{content:"\f058";}
        .fa-user:before{content:"\f007";}
        .fa-sign-out-alt:before{content:"\f2f5";}
        .fa-minus:before{content:"\f068";}
        .fa-plus:before{content:"\f067";}
    </style>
</HEAD>
<BODY>
    <div id="alert">
        <i class="fas <?=($stt)?'fa-check-circle fasuccess':'fa-exclamation-triangle fadanger'?>"></i>
        <div class="title">Thông báo</div>
        <div class="message alert <?=($stt) ? 'alert-success' :         'alert-danger'?>"><?=$transfer_text?></div>
                <div class="rlink">(<a href="<?=$transfer_url?>" >Click vào đây nếu không muốn đợi lâu</a>)</div>
        <div class="progress"><div id="process-bar" class="progress-bar progress-bar-striped progress-bar-<?=($stt) ? 'success' : 'danger'?> active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>
    </div>
    <script type="text/javascript">
        var elem = document.getElementById("process-bar"); 
        var pos = 0;
        setInterval(function(){
            pos+=8; 
            elem.style.width = pos + '%'; 
        },40);
    </script>
</BODY>
</HTML>