
	<style type="text/css">     
div.phone_mobi{background: #fc0;width:100%;position:fixed;left:0;bottom:0;line-height:18px;color:#fff;z-index: 9999;padding: 6px 0;box-shadow: 0px 0px 3px #000;}
div.phone_mobi ul{list-style:none; margin:0; padding:0; display: flex;}
div.phone_mobi ul li{flex: auto 1 0;text-align:center;position: relative;}
div.phone_mobi ul li a{color: #000;text-decoration:none;font-size:15px;display: block;}
div.phone_mobi ul li a i{font-size:19px;margin-right:5px;margin-top:3px;}
div.phone_mobi ul li a span{display: block;text-align: center;}
div.phone_mobi ul li ul{
    background: #1182fc;
    position: absolute;
    bottom: 48px;
    width: 150px;
    display: none;
    right: 0;
}
div.phone_mobi ul li ul.phone-box{left:0}
div.phone_mobi ul li ul li{
    text-align: left;
    padding: 5px 10px;
    display: block;
    flex: initial;
    border-bottom: 1px solid;
}
div.phone_mobi ul li ul li a{
    color: #fff;
}
div.phone_mobi ul li ul li a span{display:inline-block}
.blink_me {-webkit-animation-name: blinker;-webkit-animation-duration: 1s;-webkit-animation-timing-function: linear;-webkit-animation-iteration-count: infinite;-moz-animation-name: blinker;-moz-animation-duration: 1s;-moz-animation-timing-function: linear;-moz-animation-iteration-count: infinite;animation-name: blinker;
animation-duration: 1s;animation-timing-function: linear;animation-iteration-count: infinite;}
@-moz-keyframes blinker {  0% { opacity: 1.0; }50% { opacity: 0.0; }100% { opacity: 1.0; }}
@-webkit-keyframes blinker {  0% { opacity: 1.0; }50% { opacity: 0.0; }100% { opacity: 1.0; }}
@keyframes blinker {0% { opacity: 1.0; }50% { opacity: 0.0; }100% { opacity: 1.0; }}   
    </style>
    <div class="phone_mobi">
        <ul>
            <li><a class="blink_me show-phone"><i class="fas fa-phone-volume"></i><span></span></a>
                <ul class="phone-box">
                    <li><a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline']);?>">Call: <span><?=$optsetting['hotline']?></span></a></li>
                    <li><a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['zalo']);?>">Call: <span><?=$optsetting['zalo']?></span></a></li>
                    <li><a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['dienthoai']);?>">Call: <span><?=$optsetting['dienthoai']?></span></a></li>
                </ul>
            </li>
            <li><a href="sms:<?=preg_replace('/[^0-9]/','',$optsetting['hotline']);?>"><i class="fas fa-comments"></i><span></span></a></li>
            <li><a href="<?=$optsetting['chiduong']?>"><i class="fas fa-map-marker-alt"></i><span></span></a></li>
			<li><a class="show-zalo"><i class="fas fa-comment"></i><span></span></a>
                <ul class="zalo-box">
                    <li><a href="https://zalo.me/<?=preg_replace('/[^0-9]/','',$optsetting['hotline']);?>">Zalo: <span><?=$optsetting['hotline']?></span></a></li>
                    <li><a href="https://zalo.me/<?=preg_replace('/[^0-9]/','',$optsetting['zalo']);?>">Zalo: <span><?=$optsetting['zalo']?></span></a></li>
                    <li><a href="https://zalo.me/<?=preg_replace('/[^0-9]/','',$optsetting['dienthoai']);?>">Zalo: <span><?=$optsetting['dienthoai']?></span></a></li>
                </ul>
            </li>
        </ul>
    </div>
    <script type="text/javascript">
        $(document).ready(function(e) { 
            var bien2 =1;
            $(document).on('click', '.show-phone', function(){  
                if(bien2==1){ $('.phone-box').css("display","block"); bien2 = 0;            
                }else{ bien2 = 1; $('.phone-box').css("display","none"); }
            });

            var bien3 =1;
            $(document).on('click', '.show-zalo', function(){   
                if(bien3==1){ $('.zalo-box').css("display","block"); bien3 = 0;         
                }else{ bien3 = 1; $('.zalo-box').css("display","none"); }
            });
        });
    </script>