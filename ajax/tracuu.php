<?php
	include "ajax_config.php";
	
	$cccd = (isset($_POST['cccd']) && $_POST['cccd'] !='') ? htmlspecialchars($_POST['cccd']) : '';
	$type = (isset($_POST['type']) && $_POST['type'] !='') ? htmlspecialchars($_POST['type']) : '';
	$cccd = trim($cccd);

	if (strlen($cccd) == 11) {
	    $cccd2 = '0' . $cccd;
	}
	else if (strlen($cccd) == 12 && substr($cccd, 0, 1) === '0') {
	    $cccd2 = substr($cccd, 1);
	}

	$tracuu = $d->rawQueryOne("select id, type, ten$lang as ten, ngaysinh, gplx,hang,khoa,gxn, cccd, photo from #_product where type = ? and (cccd = '".$cccd."' or cccd='".$cccd2."' ) and hienthi=1 limit 0,1",array($type));
?>

<?php if(!empty($tracuu)) { ?>
	<?php if($type=='qr') { ?>
		<div class="qr_ttoan" style="text-align: center; ">
			<p><?=$tracuu['ten']?></p>
			<p>CCCD: <?=$tracuu['cccd']?></p>
			<?php if($tracuu['photo']!='') { ?><img src="<?=UPLOAD_PRODUCT_L.$tracuu['photo']?>" alt="<?=$tracuu['ten']?>" style="max-height: 300px;"><?php } ?>
		</div>
	<?php } else { ?>
		<ul>
			<li><b>Họ tên: </b><span class="ktt"><?=$tracuu['ten']?></span></li>
			<li><b>Ngày sinh: </b><?=$tracuu['ngaysinh']?></li>
			<li><b>CCCD: </b><?=$tracuu['cccd']?></li>
			<?php if($type=='gplx') { ?>
				<li><b>Số GPLX: </b><?=$tracuu['gplx']?></li>
			<?php } else { ?>
				<li><b>Hạng đào tạo: </b><?=$tracuu['hang']?></li>
				<li><b>Khóa học: </b><?=$tracuu['khoa']?></li>
				<li><b>Số giấy xác nhận: </b><?=$tracuu['gxn']?></li>
			<?php } ?>
		</ul>
	<?php } ?>
<?php } else { ?>
	<p class="ktt">Không tìm thấy từ dữ liệu hệ thống!</p>
<?php } ?>