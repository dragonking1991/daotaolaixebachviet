<?php
	include "ajax_config.php";
	require_once LIBRARIES.'daotao_lib.php';

	$cccd = isset($_POST['cccd']) ? dt_normalize_cccd($_POST['cccd']) : '';
	if($cccd === '' || strlen($cccd) < 9)
	{
		echo '<p style="text-align:center;color:#c00;">Vui lòng nhập đúng số CCCD.</p>';
		exit;
	}

	$variants = dt_cccd_variants($cccd);
	$in = implode(',', array_fill(0, count($variants), '?'));
	$hvs = $d->rawQuery(
		"select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where h.cccd in ($in) order by h.id desc",
		$variants
	);

	if(empty($hvs))
	{
		echo '<p style="text-align:center;color:#c00;">Không tìm thấy học viên với CCCD này.</p>';
		exit;
	}

	function dt_badge($ok, $labelOk = 'Đạt', $labelNo = 'Chưa đạt')
	{
		$bg = $ok ? '#1e9e4a' : '#c0392b';
		$txt = $ok ? $labelOk : $labelNo;
		return '<span style="display:inline-block;padding:2px 10px;border-radius:12px;background:'.$bg.';color:#fff;font-size:12px;font-weight:700;">'.$txt.'</span>';
	}

	foreach($hvs as $hv)
	{
		$s = dt_student_summary($hv);
		$agg = $s['dat']['agg'];
		$khoa = trim($hv['ma_khoa'].' '.$hv['ten_khoa']);
		echo '<div style="max-width:640px;margin:0 auto 20px;border:1px solid #ddd;border-radius:12px;overflow:hidden;font-family:Arial,sans-serif;">';
		echo '<div style="background:linear-gradient(135deg,#2954f2,#1e3fb8);color:#fff;padding:14px 18px;">';
		echo '<div style="font-size:17px;font-weight:700;">'.htmlspecialchars($hv['hoten']).'</div>';
		echo '<div style="font-size:13px;opacity:.9;">CCCD: '.htmlspecialchars($hv['cccd']).' &nbsp;•&nbsp; Hạng: '.htmlspecialchars($hv['hang']).($khoa!==''?' &nbsp;•&nbsp; Khóa: '.htmlspecialchars($khoa):'').'</div>';
		echo '</div>';
		echo '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
		echo '<tr><td style="padding:10px 18px;border-bottom:1px solid #eee;">Lý thuyết ('.$s['ly_thuyet']['so_dat'].'/'.$s['ly_thuyet']['tong'].' môn)</td><td style="padding:10px 18px;border-bottom:1px solid #eee;text-align:right;">'.dt_badge($s['ly_thuyet']['dat']).'</td></tr>';
		echo '<tr><td style="padding:10px 18px;border-bottom:1px solid #eee;">Cabin</td><td style="padding:10px 18px;border-bottom:1px solid #eee;text-align:right;">'.dt_badge($s['cabin']['dat'], 'Đạt', ($s['cabin']['co']?'Không đạt':'Chưa có')).'</td></tr>';
		echo '<tr><td style="padding:10px 18px;border-bottom:1px solid #eee;">Thực hành trong hình<br><span style="font-size:12px;color:#888;">'.$s['hinh']['gio'].' giờ / '.$s['hinh']['km'].' km</span></td><td style="padding:10px 18px;border-bottom:1px solid #eee;text-align:right;">'.dt_badge($s['hinh']['dat']).'</td></tr>';
		echo '<tr><td style="padding:10px 18px;border-bottom:1px solid #eee;">DAT (trên đường)<br><span style="font-size:12px;color:#888;">A '.$agg['a'].'h • đêm '.$agg['b'].'h • tự động '.$agg['c'].'h • số sàn '.$agg['d'].'h • '.$agg['e'].' km</span></td><td style="padding:10px 18px;border-bottom:1px solid #eee;text-align:right;">'.dt_badge($s['dat']['dat']).'</td></tr>';
		echo '<tr><td style="padding:12px 18px;font-weight:700;">Kết luận</td><td style="padding:12px 18px;text-align:right;">'.dt_badge($s['du_dieu_kien'], 'Đủ điều kiện kiểm tra', 'Chưa đủ điều kiện').'</td></tr>';
		echo '</table>';
		echo '</div>';
	}
?>
