<?php
	include "ajax_config.php";
	
	$cccd = (isset($_POST['cccd']) && $_POST['cccd'] !='') ? htmlspecialchars($_POST['cccd']) : '';
	$type = (isset($_POST['type']) && $_POST['type'] !='') ? htmlspecialchars($_POST['type']) : '';
	$id_kysathach = (isset($_POST['id_kysathach']) && $_POST['id_kysathach'] !='') ? (int)$_POST['id_kysathach'] : 0;
	$cccd = trim($cccd);

	if (strlen($cccd) == 11) {
	    $cccd2 = '0' . $cccd;
	}
	else if (strlen($cccd) == 12 && substr($cccd, 0, 1) === '0') {
	    $cccd2 = substr($cccd, 1);
	}

	$kysathach_filter = '';
	if($type == 'qr' && $id_kysathach > 0) {
		$kysathach_filter = ' and id_kysathach = '.(int)$id_kysathach;
	}

	$tracuu = $d->rawQueryOne("select p.id, p.type, p.ten$lang as ten, p.ngaysinh, p.gplx, p.hang, p.khoa, p.gxn, p.cccd, p.photo, p.id_kysathach from #_product p where p.type = ? and (p.cccd = '".$cccd."' or p.cccd='".$cccd2."' ) and p.hienthi=1 $kysathach_filter limit 0,1",array($type));

	// Get kỳ sát hạch info and company name for QR display
	$ky_info = null;
	$company_name = '';
	if(!empty($tracuu) && $type == 'qr') {
		if($tracuu['id_kysathach'] > 0) {
			$ky_info = $d->rawQueryOne("select * from #_kysathach where id = ? limit 0,1", array($tracuu['id_kysathach']));
		}
		$setting = $d->rawQueryOne("select tenvi from #_setting limit 0,1");
		$company_name = isset($setting['tenvi']) ? $setting['tenvi'] : '';
	}
?>

<?php if(!empty($tracuu)) { ?>
	<?php if($type=='qr') { ?>
		<div style="max-width:420px; margin:0 auto; font-family:Arial,sans-serif;">
			<div style="background:linear-gradient(135deg,#f0f4ff 0%,#e8eeff 100%); border-radius:16px; padding:24px; margin-bottom:16px;">
				<h2 style="margin:0 0 6px 0; font-size:22px; color:#1a1a2e;">Kết quả tra cứu thí sinh</h2>
				<p style="margin:0; color:#1a6fbf; font-size:13px; font-weight:600;">@ <?=strtoupper($company_name)?></p>

				<div style="background:#fff; border-radius:12px; padding:20px; margin-top:16px; display:flex; flex-wrap:wrap; gap:4px;">
					<div style="flex:1; min-width:50%;">
						<p style="margin:0 0 4px 0; font-size:12px; color:#888;">Họ và tên</p>
						<p style="margin:0; font-size:16px; font-weight:700; color:#1a1a2e;"><?=strtoupper($tracuu['ten'])?></p>
					</div>
					<div style="flex:1; min-width:40%;">
						<p style="margin:0 0 4px 0; font-size:12px; color:#888;">Ngày sinh</p>
						<p style="margin:0; font-size:16px; font-weight:700; color:#1a1a2e;"><?=$tracuu['ngaysinh']?></p>
					</div>
					<div style="flex:1; min-width:50%; margin-top:16px;">
						<p style="margin:0 0 4px 0; font-size:12px; color:#888;">Kỳ sát hạch</p>
						<p style="margin:0; font-size:16px; font-weight:700; color:#1a1a2e;"><?=($ky_info ? date('d-m-Y', strtotime($ky_info['ngay_sathach'])) : '---')?></p>
					</div>
					<div style="flex:1; min-width:40%; margin-top:16px;">
						<p style="margin:0 0 4px 0; font-size:12px; color:#888;">Hạng GPLX</p>
						<p style="margin:0; font-size:16px; font-weight:700; color:#1a1a2e;"><?=$tracuu['hang']?></p>
					</div>
				</div>
			</div>

			<div style="text-align:center; padding:10px 0;">
				<p style="margin:0 0 4px 0; font-size:16px; font-weight:700; color:#1a1a2e;">Thanh toán qua mã QR</p>
				<p style="margin:0 0 12px 0; font-size:12px; color:#888;">Kiểm tra kỹ thông tin trước khi chuyển khoản</p>
				<?php if($tracuu['photo']!='') { ?>
					<div style="background:linear-gradient(135deg,#f5f7fa 0%,#e8ecf1 100%); border-radius:12px; padding:16px; display:inline-block;">
						<p style="margin:0 0 2px 0; font-size:13px; color:#666; font-weight:600;"><?=strtoupper($tracuu['ten'])?></p>
						<p style="margin:0 0 10px 0; font-size:12px; color:#888;">CCCD: <?=$tracuu['cccd']?></p>
						<img src="<?=UPLOAD_PRODUCT_L.$tracuu['photo']?>" alt="<?=$tracuu['ten']?>" style="max-width:250px; width:100%;">
					</div>
				<?php } ?>
			</div>

			<div style="background:#fff8e1; border:1px solid #ffe082; border-radius:10px; padding:14px 16px; margin-top:16px; font-size:13px; color:#333; line-height:1.6;">
				<span style="color:#e65100;">⚠</span> Học viên sử dụng <b><i>ứng dụng ngân hàng (Mobile Banking)</i></b> để thanh toán. <b>Không sử dụng</b> ví điện tử (Momo, Viettel Money, ZaloPay...) để tránh lỗi xử lý giao dịch.
			</div>
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