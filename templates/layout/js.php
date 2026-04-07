<script type="text/javascript">
    var NN_FRAMEWORK = NN_FRAMEWORK || {};
    var CONFIG_BASE = '<?=$config_base?>';
    var WEBSITE_NAME = '<?=(isset($setting['ten'.$lang]) && $setting['ten'.$lang] != '') ? addslashes($setting['ten'.$lang]) : ''?>';
    var TIMENOW = '<?=date("d/m/Y",time())?>';
    var SHIP_CART = <?=(isset($config['order']['ship']) && $config['order']['ship'] == true) ? 'true' : 'false'?>;
    var GOTOP = 'assets/images/top.png';
    var LANG = {
        'no_keywords': '<?=chuanhaptukhoatimkiem?>',
        'delete_product_from_cart': '<?=banmuonxoasanphamnay?>',
        'no_products_in_cart': '<?=khongtontaisanphamtronggiohang?>',
        'wards': '<?=phuongxa?>',
        'back_to_home': '<?=vetrangchu?>',
    };
</script>
<?php
    $js->setCache("cached");
    //$js->setJs("./assets/js/jquery.min.js");
    $js->setJs("./assets/bootstrap/bootstrap.js");
    $js->setJs("./assets/slick/slick.js");
    //$js->setJs("./assets/js/jquery.lazyload.pack.js");
    $js->setJs("./assets/fotorama/fotorama.js");
    $js->setJs("./assets/fancybox3/jquery.fancybox.js");
    //$js->setJs("./assets/magiczoomplus/magiczoomplus.js");
    //$js->setJs("./assets/toc/toc.js");
    //$js->setJs("./assets/datetimepicker/php-date-formatter.min.js");
    //$js->setJs("./assets/datetimepicker/jquery.mousewheel.js");
    //$js->setJs("./assets/datetimepicker/jquery.datetimepicker.js");
    //$js->setJs("./assets/js/phantrang_ajax.js");
    //$js->setJs("./assets/js/jquery.animateNumber.min.js");
    //$js->setJs("./assets/js/jquery.fittext.js");
    //$js->setJs("./assets/js/jquery.lettering.js");
    //$js->setJs("./assets/js/jquery.textillate.js");
    //$js->setJs("./assets/js/wow.min.js");
    //$js->setJs("./assets/js/scrollspy.js");
    $js->setJs("./assets/js/functions.js");
    $js->setJs("./assets/js/apps.js");
    echo $js->getJs();
?>

<?php if(isset($config['googleAPI']['recaptcha']['active']) && $config['googleAPI']['recaptcha']['active'] == true) { ?>
    <!-- Js Google Recaptcha V3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?=$config['googleAPI']['recaptcha']['sitekey']?>"></script>

    <script type="text/javascript">
        grecaptcha.ready(function () {
            
            <?php if($source=='index') { ?>
            grecaptcha.ready(function() {
                document.getElementById('FormNewsletter').addEventListener("submit", function(event) {
                    event.preventDefault();
                    grecaptcha.execute('<?=$config['googleAPI']['recaptcha']['sitekey']?>', {action: 'Newsletter'}).then(function(token) {
                        document.getElementById("recaptchaResponseNewsletter").value = token; 
                        document.getElementById('FormNewsletter').submit();
                    });        
                }, false);
            });
            <?php } ?>
            
            <?php if($source=='contact') { ?>
                grecaptcha.ready(function() {
                    document.getElementById('FormContact').addEventListener("submit", function(event) {
                        event.preventDefault();
                        grecaptcha.execute('<?=$config['googleAPI']['recaptcha']['sitekey']?>', {action: 'contact'}).then(function(token) {
                            document.getElementById("recaptchaResponseContact").value = token; 
                            document.getElementById('FormContact').submit();
                        });        
                    }, false);
                });
            <?php } ?>
        });
    </script>
<?php } ?>

<?php if(isset($config['oneSignal']['active']) && $config['oneSignal']['active'] == true) { ?>
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
    <script type="text/javascript">
        var OneSignal = window.OneSignal || [];
        OneSignal.push(function() {
            OneSignal.init({
                appId: "<?=$config['oneSignal']['id']?>"
            });
        });
    </script>
<?php } ?>
<!-- Js Structdata -->
<?php //include TEMPLATE.LAYOUT."strucdata.php"; ?>
<!-- Js Addons -->
<?=$addons->setAddons('script-main', 'script-main', 0.5);?>
<?=$addons->getAddons();?>
<!-- Js Body -->
<?=htmlspecialchars_decode($setting['bodyjs'])?>
