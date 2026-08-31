<?php
	include "ajax_config.php";

	function xd_norm_cccd($value)
	{
		return preg_replace('/\D+/', '', (string)$value);
	}

	function xd_cccd_variants_public($cccd)
	{
		$cccd = xd_norm_cccd($cccd);
		$variants = array($cccd);
		if(strlen($cccd) == 11) $variants[] = '0'.$cccd;
		elseif(strlen($cccd) == 12 && substr($cccd, 0, 1) === '0') $variants[] = substr($cccd, 1);
		return array_values(array_unique(array_filter($variants, function($v){ return $v !== ''; })));
	}

	// Khóa định danh giáo viên theo TÊN (đồng bộ với admin/sources/xangdau.php::xd_gv_key)
	function xd_gv_key_public($name)
	{
		$name = function_exists('mb_strtolower') ? mb_strtolower((string)$name, 'UTF-8') : strtolower((string)$name);
		$name = trim($name);
		if($name === '') return '';
		$search  = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
		$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
		$name = str_replace($search, $replace, $name);
		$name = preg_replace('/[^a-z0-9\s]+/', ' ', $name);
		$name = preg_replace('/\s+/', ' ', trim($name));
		$name = preg_replace('/^(thay|co)\s+/', '', $name);
		return trim($name);
	}

	$cccd = isset($_POST['cccd']) ? xd_norm_cccd($_POST['cccd']) : '';
	$fromDate = (isset($_POST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['from_date'])) ? $_POST['from_date'] : '';
	$toDate = (isset($_POST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['to_date'])) ? $_POST['to_date'] : '';

	if($cccd === '')
	{
		echo '<p style="color:#c00; text-align:center;">Vui lòng nhập số CCCD.</p>';
		exit;
	}

	$variants = xd_cccd_variants_public($cccd);
	$cccdPlaceholders = implode(',', array_fill(0, count($variants), '?'));

	// GV đăng nhập bằng CCCD: tra tên giáo viên từ hồ sơ nhân viên (table_product type='nhan-vien')
	$gvHoten = '';
	$gvKey = '';
	$emp = $d->rawQueryOne(
		"select ten$lang as ten from #_product where type = 'nhan-vien' and cccd in ($cccdPlaceholders) and hienthi = 1 limit 0,1",
		$variants
	);
	if(!empty($emp) && !empty($emp['ten']))
	{
		$gvHoten = $emp['ten'];
		$gvKey = xd_gv_key_public($gvHoten);
	}

	// Nếu không tìm thấy trong hồ sơ nhân viên, thử khớp trực tiếp gv_key có trong dữ liệu XD
	// (phòng trường hợp CCCD được lưu kèm hoặc khớp theo dữ liệu đã import)
	if($gvKey === '')
	{
		echo '<div style="text-align:center; padding:20px; background:#fff3cd; border:1px solid #ffe082; border-radius:8px; color:#663c00;">Không tìm thấy giáo viên với số CCCD này trong hồ sơ nhân viên. Vui lòng liên hệ văn phòng để được hỗ trợ.</div>';
		exit;
	}

	// Kiểm tra GV có dữ liệu XD nào không
	$existHoadon = $d->rawQueryOne("select id from #_xd_hoadon where gv_key = ? limit 0,1", array($gvKey));
	$existHocvien = $d->rawQueryOne("select id from #_xd_hocvien where gv_key = ? limit 0,1", array($gvKey));

	if(empty($existHoadon) && empty($existHocvien))
	{
		echo '<div style="text-align:center; padding:20px; background:#fff3cd; border:1px solid #ffe082; border-radius:8px; color:#663c00;">Giáo viên <strong>'.htmlspecialchars($gvHoten).'</strong> chưa có dữ liệu xăng dầu. Vui lòng liên hệ văn phòng để được hỗ trợ.</div>';
		exit;
	}

	// Bảng hóa đơn XD trong khoảng thời gian
	$hoadonWhere = "";
	$hoadonParams = array($gvKey);
	if($fromDate !== '') { $hoadonWhere .= " and ngay_hoa_don >= ?"; $hoadonParams[] = $fromDate; }
	if($toDate !== '') { $hoadonWhere .= " and ngay_hoa_don <= ?"; $hoadonParams[] = $toDate; }
	$hoadons = $d->rawQuery(
		"select ma_hoa_don, ngay_hoa_don, tong_tien, ky from #_xd_hoadon where gv_key = ? $hoadonWhere order by ngay_hoa_don desc, id desc",
		$hoadonParams
	);

	// Bảng học viên đã thanh toán trong khoảng thời gian
	$hocvienWhere = " and ngay_thanh_toan is not null";
	$hocvienParams = array($gvKey);
	if($fromDate !== '') { $hocvienWhere .= " and ngay_thanh_toan >= ?"; $hocvienParams[] = $fromDate; }
	if($toDate !== '') { $hocvienWhere .= " and ngay_thanh_toan <= ?"; $hocvienParams[] = $toDate; }
	$hocviens = $d->rawQuery(
		"select ho_ten, cccd, nhom, dinh_muc, so_tien_thanh_toan, ngay_thanh_toan from #_xd_hocvien where gv_key = ? $hocvienWhere order by ngay_thanh_toan desc, id asc",
		$hocvienParams
	);

	$tongHoadon = 0.0;
	if(!empty($hoadons)) foreach($hoadons as $h) $tongHoadon += (float)$h['tong_tien'];
	$tongThanhToan = 0.0;
	if(!empty($hocviens)) foreach($hocviens as $h) $tongThanhToan += (float)$h['so_tien_thanh_toan'];
?>

<div style="background:#e8f0ff; border-radius:8px; padding:12px 16px; margin-bottom:16px;">
	<strong>Giáo viên:</strong> <?=htmlspecialchars($gvHoten !== '' ? $gvHoten : ('CCCD '.$cccd))?>
	<?php if($fromDate !== '' || $toDate !== '') { ?>
		<span style="margin-left:12px; color:#555;">Khoảng thời gian:
			<?=($fromDate !== '' ? date('d/m/Y', strtotime($fromDate)) : '...')?> - <?=($toDate !== '' ? date('d/m/Y', strtotime($toDate)) : '...')?>
		</span>
	<?php } ?>
</div>

<h3 style="font-size:16px; margin:0 0 8px;">1. Danh sách hóa đơn xăng dầu</h3>
<div style="overflow-x:auto; margin-bottom:24px;">
	<table style="width:100%; border-collapse:collapse; font-size:14px;">
		<thead>
			<tr style="background:#2954f2; color:#fff;">
				<th style="padding:8px; border:1px solid #ddd;">STT</th>
				<th style="padding:8px; border:1px solid #ddd;">Ngày hóa đơn</th>
				<th style="padding:8px; border:1px solid #ddd;">Số hóa đơn</th>
				<th style="padding:8px; border:1px solid #ddd;">Kỳ</th>
				<th style="padding:8px; border:1px solid #ddd; text-align:right;">Tiền hóa đơn</th>
			</tr>
		</thead>
		<tbody>
			<?php if(!empty($hoadons)) { $i = 0; foreach($hoadons as $h) { $i++; ?>
			<tr>
				<td style="padding:8px; border:1px solid #ddd; text-align:center;"><?=$i?></td>
				<td style="padding:8px; border:1px solid #ddd;"><?=($h['ngay_hoa_don'] ? date('d/m/Y', strtotime($h['ngay_hoa_don'])) : '-')?></td>
				<td style="padding:8px; border:1px solid #ddd;"><?=htmlspecialchars($h['ma_hoa_don'])?></td>
				<td style="padding:8px; border:1px solid #ddd;"><?=htmlspecialchars($h['ky'])?></td>
				<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?=number_format((float)$h['tong_tien'], 0, ',', '.')?></td>
			</tr>
			<?php } ?>
			<tr style="background:#f5f5f5; font-weight:700;">
				<td colspan="4" style="padding:8px; border:1px solid #ddd; text-align:right;">Tổng cộng</td>
				<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?=number_format($tongHoadon, 0, ',', '.')?></td>
			</tr>
			<?php } else { ?>
			<tr><td colspan="5" style="padding:12px; border:1px solid #ddd; text-align:center; color:#888;">Không có hóa đơn trong khoảng thời gian đã chọn.</td></tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<h3 style="font-size:16px; margin:0 0 8px;">2. Danh sách học viên đã thanh toán</h3>
<div style="overflow-x:auto;">
	<table style="width:100%; border-collapse:collapse; font-size:14px;">
		<thead>
			<tr style="background:#108824; color:#fff;">
				<th style="padding:8px; border:1px solid #ddd;">STT</th>
				<th style="padding:8px; border:1px solid #ddd;">Họ tên</th>
				<th style="padding:8px; border:1px solid #ddd;">CCCD</th>
				<th style="padding:8px; border:1px solid #ddd;">Nhóm</th>
				<th style="padding:8px; border:1px solid #ddd; text-align:right;">Định mức XD</th>
				<th style="padding:8px; border:1px solid #ddd; text-align:right;">Số tiền thanh toán</th>
				<th style="padding:8px; border:1px solid #ddd;">Ngày thanh toán</th>
			</tr>
		</thead>
		<tbody>
			<?php if(!empty($hocviens)) { $i = 0; foreach($hocviens as $h) { $i++; ?>
			<tr>
				<td style="padding:8px; border:1px solid #ddd; text-align:center;"><?=$i?></td>
				<td style="padding:8px; border:1px solid #ddd;"><?=htmlspecialchars($h['ho_ten'])?></td>
				<td style="padding:8px; border:1px solid #ddd;"><?=htmlspecialchars($h['cccd'])?></td>
				<td style="padding:8px; border:1px solid #ddd; text-align:center;"><?=htmlspecialchars($h['nhom'])?></td>
				<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?=number_format((float)$h['dinh_muc'], 0, ',', '.')?></td>
				<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?=number_format((float)$h['so_tien_thanh_toan'], 0, ',', '.')?></td>
				<td style="padding:8px; border:1px solid #ddd; text-align:center;"><?=($h['ngay_thanh_toan'] ? date('d/m/Y', strtotime($h['ngay_thanh_toan'])) : '-')?></td>
			</tr>
			<?php } ?>
			<tr style="background:#f5f5f5; font-weight:700;">
				<td colspan="5" style="padding:8px; border:1px solid #ddd; text-align:right;">Tổng cộng</td>
				<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?=number_format($tongThanhToan, 0, ',', '.')?></td>
				<td style="padding:8px; border:1px solid #ddd;"></td>
			</tr>
			<?php } else { ?>
			<tr><td colspan="7" style="padding:12px; border:1px solid #ddd; text-align:center; color:#888;">Chưa có học viên nào được thanh toán trong khoảng thời gian đã chọn.</td></tr>
			<?php } ?>
		</tbody>
	</table>
</div>
