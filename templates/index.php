<!DOCTYPE html>
<html lang="<?=$config['website']['lang-doc']?>">
<head>
    <?php include TEMPLATE.LAYOUT."head.php"; ?>
    <?php include TEMPLATE.LAYOUT."css.php"; ?>
</head>
<body>
    <div class="wapper">
    <?php
        include TEMPLATE.LAYOUT."seo.php";
        include TEMPLATE.LAYOUT."header.php";
        if($source=='index') include TEMPLATE.LAYOUT."slider_slick.php";
        else include TEMPLATE.LAYOUT."breadcrumb.php";
    ?>
    <div class="<?php if($source!='index')echo 'main_content main_fix';?> clear">
        <?php include TEMPLATE.$template."_tpl.php"; ?>
    </div>
    <?php
        include TEMPLATE.LAYOUT."footer.php";
        include TEMPLATE.LAYOUT."copy.php";
        include TEMPLATE.LAYOUT."addon.php";
        include TEMPLATE.LAYOUT."modal.php";        
        include TEMPLATE.LAYOUT."js.php";
    ?>
    </div>
</body>
</html>