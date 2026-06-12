<?php
if(!defined('SOURCES')) die("Error");

switch($act)
{
	case "man":
		get_items_cabin();
		$template = "cabin/man/items";
		break;
	case "add":
		get_items_cabin();
		$template = "cabin/man/items";
		break;
	case "edit":
		get_items_cabin();
		$template = "cabin/man/items";
		break;
	case "save":
		save_item_cabin();
		break;
	case "delete":
		delete_item_cabin();
		break;
	case "upload":
		$template = "cabin/upload/items";
		break;
	case "uploadExcel":
		uploadExcel_cabin();
		break;
	case "data":
		get_data_cabin();
		$template = "cabin/data/items";
		break;
	case "deleteData":
		deleteData_cabin();
		break;
	case "deleteAllData":
		deleteAllData_cabin();
		break;
	case "ajaxData":
		ajaxData_cabin();
		break;
	case "dangky":
		get_dangky_cabin();
		$template = "cabin/dangky/items";
		break;
	case "exportExcel":
		exportExcel_cabin();
		break;
	default:
		$template = "404";
}

function get_items_cabin()
{
	global $d, $func, $curPage, $items, $paging;

	$where = "";
	if(isset($_REQUEST['keyword']))
	{
		$keyword = htmlspecialchars($_REQUEST['keyword']);
		$where .= " and k.ten LIKE '%$keyword%'";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$limit = " limit ".$startpoint.",".$per_page;
	$sql = "select k.*, "
		. "(select count(*) from #_product p where p.id_cabin_khoahoc = k.id and p.type = 'cabin') as so_hoc_vien, "
		. "(select count(*) from #_cabin_dangky dk where dk.id_khoahoc = k.id) as so_dang_ky "
		. "from #_cabin_khoahoc k where k.hienthi >= 0 $where order by k.ngay_batdau desc, k.id desc $limit";
	$items = $d->rawQuery($sql);
	$sqlNum = "select count(*) as 'num' from #_cabin_khoahoc k where k.hienthi >= 0 $where";
	$count = $d->rawQueryOne($sqlNum);
	$total = $count['num'];
	$url = "index.php?com=cabin&act=man";
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function get_items_cabin_all()
{
	global $d, $items_kh;

	$items_kh = $d->rawQuery("select * from #_cabin_khoahoc where hienthi = 1 order by ngay_batdau desc, id desc");
}

function get_item_cabin()
{
	global $d, $func, $curPage, $item;

	$id = (isset($_GET['id'])) ? (int)htmlspecialchars($_GET['id']) : 0;

	if(!$id)
		$func->transfer("Không nhận được dữ liệu", "index.php?com=cabin&act=man&p=".$curPage, false);

	$item = $d->rawQueryOne("select * from #_cabin_khoahoc where id = ? limit 0,1", array($id));

	if(!$item['id'])
		$func->transfer("Dữ liệu không có thực", "index.php?com=cabin&act=man&p=".$curPage, false);
}

function save_item_cabin()
{
	global $d, $func, $curPage, $login_admin;

	if(empty($_POST))
		$func->transfer("Không nhận được dữ liệu", "index.php?com=cabin&act=man&p=".$curPage, false);

	$data = array();
	$data['ten'] = isset($_POST['data']['ten']) ? htmlspecialchars($_POST['data']['ten']) : '';
	$data['ngay_batdau'] = isset($_POST['data']['ngay_batdau']) ? htmlspecialchars($_POST['data']['ngay_batdau']) : '';
	$data['ngay_ketthuc'] = isset($_POST['data']['ngay_ketthuc']) ? htmlspecialchars($_POST['data']['ngay_ketthuc']) : '';
	$suc_chua = isset($_POST['data']['suc_chua_ca']) ? (int)$_POST['data']['suc_chua_ca'] : 3;
	$data['suc_chua_ca'] = ($suc_chua > 0) ? $suc_chua : 3;

	if(empty($data['ten']) || empty($data['ngay_batdau']) || empty($data['ngay_ketthuc']))
		$func->transfer("Vui lòng nhập đầy đủ thông tin", "index.php?com=cabin&act=man&p=".$curPage, false);

	if(strtotime($data['ngay_ketthuc']) < strtotime($data['ngay_batdau']))
		$func->transfer("Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu", "index.php?com=cabin&act=man&p=".$curPage, false);

	$id = isset($_POST['id']) ? (int)htmlspecialchars($_POST['id']) : 0;

	if($id)
	{
		$d->where('id', $id);
		if($d->update('cabin_khoahoc', $data))
			$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=cabin&act=man&p=".$curPage);
		else
			$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=cabin&act=man&p=".$curPage, false);
	}
	else
	{
		$data['ngaytao'] = time();
		$data['user_tao'] = isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
		$data['hienthi'] = 1;

		if($d->insert('cabin_khoahoc', $data))
			$func->transfer("Lưu dữ liệu thành công", "index.php?com=cabin&act=man&p=".$curPage);
		else
			$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=cabin&act=man&p=".$curPage, false);
	}
}

function delete_item_cabin()
{
	global $d, $func, $curPage;

	$id = (isset($_GET['id'])) ? (int)htmlspecialchars($_GET['id']) : 0;

	if($id)
	{
		$d->rawQuery("delete from #_cabin_khoahoc where id = ?", array($id));
		$d->rawQuery("delete from #_cabin_dangky where id_khoahoc = ?", array($id));
		$func->transfer("Xóa dữ liệu thành công", "index.php?com=cabin&act=man&p=".$curPage);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		for($i = 0; $i < count($listid); $i++)
		{
			$tid = (int)htmlspecialchars($listid[$i]);
			$d->rawQuery("delete from #_cabin_khoahoc where id = ?", array($tid));
			$d->rawQuery("delete from #_cabin_dangky where id_khoahoc = ?", array($tid));
		}
		$func->transfer("Xóa dữ liệu thành công", "index.php?com=cabin&act=man&p=".$curPage);
	}
	else
		$func->transfer("Không nhận được dữ liệu", "index.php?com=cabin&act=man&p=".$curPage, false);
}

function uploadExcel_cabin()
{
	global $d, $func, $login_admin;

	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		$func->transfer("Vui lòng chọn file Excel", "index.php?com=cabin&act=upload", false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	if(!in_array($ext, array('xls', 'xlsx')))
		$func->transfer("Chỉ hỗ trợ file .xls hoặc .xlsx", "index.php?com=cabin&act=upload", false);

	require_once LIBRARIES.'PHPExcel.php';

	$inputFileName = $file['tmp_name'];

	if($ext == 'xlsx')
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	else
		$objReader = PHPExcel_IOFactory::createReader('Excel5');

	$objPHPExcel = $objReader->load($inputFileName);
	$sheet = $objPHPExcel->getSheet(0);
	$highestRow = $sheet->getHighestRow();

	$type = 'cabin';
	$imported = 0;
	$skippedNoCourse = 0;
	$createdCourses = array();
	$courseMap = array();
	for($row = 2; $row <= $highestRow; $row++)
	{
		$stt      = trim($sheet->getCell('A'.$row)->getValue());
		$hoTen    = trim($sheet->getCell('B'.$row)->getValue());
		$ngaySinh = trim($sheet->getCell('C'.$row)->getFormattedValue());
		$cccd     = trim($sheet->getCell('D'.$row)->getValue());
		$tenKhoa  = trim($sheet->getCell('E'.$row)->getValue());
		$hang     = trim($sheet->getCell('F'.$row)->getValue());

		$hoTenLower = mb_strtolower($hoTen, 'UTF-8');
		$cccdLower = mb_strtolower($cccd, 'UTF-8');
		$tenKhoaLower = mb_strtolower($tenKhoa, 'UTF-8');
		$isHeaderRow = false;
		if(
			strpos($hoTenLower, 'họ và tên') !== false ||
			strpos($hoTenLower, 'ho va ten') !== false ||
			strpos($cccdLower, 'cccd') !== false ||
			strpos($cccdLower, 'cc/hc') !== false ||
			strpos($tenKhoaLower, 'khóa') !== false ||
			strpos($tenKhoaLower, 'khoa') !== false
		) {
			$isHeaderRow = true;
		}

		$cccdDigits = preg_replace('/\D+/', '', $cccd);

		if($isHeaderRow) continue;
		if(empty($hoTen) || empty($cccd)) continue;
		if(strlen($cccdDigits) < 9) continue;

		$cccd = $cccdDigits;
		if(empty($tenKhoa))
		{
			$skippedNoCourse++;
			continue;
		}

		if(isset($courseMap[$tenKhoa]))
		{
			$id_khoahoc = $courseMap[$tenKhoa];
		}
		else
		{
			$kh = $d->rawQueryOne("select id, ten from #_cabin_khoahoc where ten = ? limit 0,1", array($tenKhoa));
			if(!empty($kh['id']))
			{
				$id_khoahoc = (int)$kh['id'];
			}
			else
			{
				$dataKhoa = array(
					'ten' => $tenKhoa,
					'ngay_batdau' => date('Y-m-d'),
					'ngay_ketthuc' => date('Y-m-d'),
					'suc_chua_ca' => 3,
					'ngaytao' => time(),
					'user_tao' => isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '',
					'hienthi' => 1
				);

				if(!$d->insert('cabin_khoahoc', $dataKhoa))
					continue;

				$id_khoahoc = (int)$d->getLastInsertId();
				$createdCourses[] = $tenKhoa;
			}

			$courseMap[$tenKhoa] = $id_khoahoc;
		}

		$existing = $d->rawQueryOne(
			"select id from #_product where cccd = ? and type = ? and id_cabin_khoahoc = ? limit 0,1",
			array($cccd, $type, $id_khoahoc)
		);

		$data = array(
			'tenvi'            => $hoTen,
			'tenkhongdauvi'    => $func->changeTitle($hoTen),
			'ngaysinh'         => $ngaySinh,
			'cccd'             => $cccd,
			'hang'             => $hang,
			'type'             => $type,
			'hienthi'          => 1,
			'id_cabin_khoahoc' => $id_khoahoc
		);
		if($existing && $existing['id'])
		{
			$d->where('id', $existing['id']);
			$d->update('product', $data);
		}
		else
		{
			$data['stt'] = (int)$stt;
			$data['ngaytao'] = time();
			$d->insert('product', $data);
		}
		$imported++;
	}

	if($imported == 0)
		$func->transfer("Không có bản ghi nào được import. Vui lòng kiểm tra file (cột B: Họ tên, cột D: CCCD, cột E: Khóa).", "index.php?com=cabin&act=upload", false);

	$message = "Import thành công $imported học viên cabin.";

	if($skippedNoCourse > 0)
		$message .= "<br>Bỏ qua $skippedNoCourse dòng do thiếu tên khóa ở cột E.";

	if(!empty($createdCourses))
	{
		$createdCourses = array_unique($createdCourses);
		$message .= "<br><strong>Cảnh báo:</strong> Đã tự tạo " . count($createdCourses) . " khóa mới: " . implode(', ', $createdCourses) . ".";
		$message .= "<br>Vui lòng vào Danh sách khóa để cập nhật NGÀY BẮT ĐẦU/NGÀY KẾT THÚC chính xác cho các khóa vừa tạo.";
	}

	$func->transfer($message, "index.php?com=cabin&act=upload");
}

function get_data_cabin()
{
	global $d, $func, $curPage, $items_data, $paging_data, $kh_info;

	$id_kh = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id_kh)
		$func->transfer("Không nhận được dữ liệu", "index.php?com=cabin&act=man", false);

	$kh_info = $d->rawQueryOne("select * from #_cabin_khoahoc where id = ? limit 0,1", array($id_kh));
	if(!$kh_info || !$kh_info['id'])
		$func->transfer("Khóa học cabin không tồn tại", "index.php?com=cabin&act=man", false);

	$where = "";
	if(isset($_REQUEST['keyword']))
	{
		$keyword = htmlspecialchars($_REQUEST['keyword']);
		$where .= " and (tenvi LIKE '%$keyword%' or cccd LIKE '%$keyword%')";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$limit = " limit ".$startpoint.",".$per_page;
	$sql = "select * from #_product where id_cabin_khoahoc = ? and type = 'cabin' $where order by stt asc, id desc $limit";
	$items_data = $d->rawQuery($sql, array($id_kh));
	$sqlNum = "select count(*) as 'num' from #_product where id_cabin_khoahoc = ? and type = 'cabin' $where";
	$count = $d->rawQueryOne($sqlNum, array($id_kh));
	$total = $count['num'];
	$url = "index.php?com=cabin&act=data&id=".$id_kh;
	$paging_data = $func->pagination($total, $per_page, $curPage, $url);
}

function deleteData_cabin()
{
	global $d, $func, $curPage;

	$id_kh = isset($_GET['id_kh']) ? (int)$_GET['id_kh'] : 0;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=cabin&act=data&id=".$id_kh."&p=".$curPage;

	if($id)
	{
		$d->rawQuery("delete from #_product where id = ?", array($id));
		$d->rawQuery("delete from #_cabin_dangky where id_hocvien = ?", array($id));
		$func->transfer("Xóa thành công", $redirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		for($i = 0; $i < count($listid); $i++)
		{
			$tid = (int)htmlspecialchars($listid[$i]);
			$d->rawQuery("delete from #_product where id = ?", array($tid));
			$d->rawQuery("delete from #_cabin_dangky where id_hocvien = ?", array($tid));
		}
		$func->transfer("Xóa thành công", $redirect);
	}
	else
		$func->transfer("Không nhận được dữ liệu", $redirect, false);
}

function deleteAllData_cabin()
{
	global $d, $func;

	$id_kh = isset($_GET['id_kh']) ? (int)$_GET['id_kh'] : 0;

	if(!$id_kh)
		$func->transfer("Không nhận được dữ liệu", "index.php?com=cabin&act=man", false);

	$count = $d->rawQueryOne("select count(*) as num from #_product where id_cabin_khoahoc = ? and type = 'cabin'", array($id_kh));
	$total = $count['num'];

	$d->rawQuery("delete from #_product where id_cabin_khoahoc = ? and type = 'cabin'", array($id_kh));
	$d->rawQuery("delete from #_cabin_dangky where id_khoahoc = ?", array($id_kh));
	$func->transfer("Đã xóa toàn bộ $total học viên", "index.php?com=cabin&act=man");
}

function ajaxData_cabin()
{
	global $d;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id) { echo '<div class="text-center p-3 text-danger">Không nhận được dữ liệu</div>'; exit(); }

	$items = $d->rawQuery("select * from #_product where id_cabin_khoahoc = ? and type = 'cabin' order by stt asc, id asc", array($id));

	if(empty($items)) { echo '<div class="text-center p-3">Không có dữ liệu</div>'; exit(); }

	$html = '<table class="table table-hover table-sm text-sm mb-0">';
	$html .= '<thead><tr>';
	$html .= '<th class="text-center" width="5%">STT</th>';
	$html .= '<th>Họ tên</th>';
	$html .= '<th>Ngày sinh</th>';
	$html .= '<th>Số CCCD</th>';
	$html .= '<th>Người nộp hồ sơ</th>';
	$html .= '</tr></thead><tbody>';

	foreach($items as $i => $row)
	{
		$html .= '<tr>';
		$html .= '<td class="text-center">'.($i + 1).'</td>';
		$html .= '<td>'.htmlspecialchars($row['tenvi']).'</td>';
		$html .= '<td>'.htmlspecialchars($row['ngaysinh']).'</td>';
		$html .= '<td>'.htmlspecialchars($row['cccd']).'</td>';
		$html .= '<td>'.htmlspecialchars($row['hang']).'</td>';
		$html .= '</tr>';
	}

	$html .= '</tbody></table>';
	echo $html;
	exit();
}

function get_dangky_cabin()
{
	global $d, $func, $curPage, $items_dk, $paging_dk, $kh_info, $ngay_hoc_filter, $ca_filter;

	require_once LIBRARIES.'cabin_config.php';

	$id_kh = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id_kh)
		$func->transfer("Không nhận được dữ liệu", "index.php?com=cabin&act=man", false);

	$kh_info = $d->rawQueryOne("select * from #_cabin_khoahoc where id = ? limit 0,1", array($id_kh));
	if(!$kh_info || !$kh_info['id'])
		$func->transfer("Khóa học cabin không tồn tại", "index.php?com=cabin&act=man", false);

	$where = "";
	$params = array($id_kh);
	$keyword = '';
	$ngay_hoc_filter = '';
	$ca_filter = 0;
	if(isset($_REQUEST['keyword']) && $_REQUEST['keyword'] != '')
	{
		$keyword = htmlspecialchars($_REQUEST['keyword']);
		$where .= " and (p.tenvi LIKE ? or p.cccd LIKE ?)";
		$params[] = '%'.$keyword.'%';
		$params[] = '%'.$keyword.'%';
	}

	if(isset($_REQUEST['ngay_hoc']) && $_REQUEST['ngay_hoc'] != '')
	{
		$ngay_hoc = htmlspecialchars($_REQUEST['ngay_hoc']);
		if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_hoc))
		{
			$ngay_hoc_filter = $ngay_hoc;
			$where .= " and dk.ngay_hoc = ?";
			$params[] = $ngay_hoc_filter;
		}
	}

	if(isset($_REQUEST['ca']) && $_REQUEST['ca'] !== '')
	{
		$ca = (int)$_REQUEST['ca'];
		if($ca >= 1 && $ca <= 4)
		{
			$ca_filter = $ca;
			$where .= " and dk.ca = ?";
			$params[] = $ca_filter;
		}
	}

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$limit = " limit ".$startpoint.",".$per_page;
	$sql = "select dk.*, p.tenvi, p.cccd as hv_cccd, p.hang, p.ngaysinh "
		. "from #_cabin_dangky dk "
		. "left join #_product p on p.id = dk.id_hocvien "
		. "where dk.id_khoahoc = ? $where "
		. "order by dk.ngay_hoc asc, dk.ca asc, p.tenvi asc $limit";
	$items_dk = $d->rawQuery($sql, $params);

	$sqlNum = "select count(*) as 'num' from #_cabin_dangky dk "
		. "left join #_product p on p.id = dk.id_hocvien "
		. "where dk.id_khoahoc = ? $where";
	$count = $d->rawQueryOne($sqlNum, $params);
	$total = $count['num'];
	$url = "index.php?com=cabin&act=dangky&id=".$id_kh;
	if($keyword != '') $url .= '&keyword='.urlencode($keyword);
	if($ngay_hoc_filter != '') $url .= '&ngay_hoc='.urlencode($ngay_hoc_filter);
	if($ca_filter > 0) $url .= '&ca='.$ca_filter;
	$paging_dk = $func->pagination($total, $per_page, $curPage, $url);
}

function exportExcel_cabin()
{
	global $d;

	require_once LIBRARIES.'cabin_config.php';

	$id_kh = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id_kh) die("Không nhận được dữ liệu");

	$kh = $d->rawQueryOne("select * from #_cabin_khoahoc where id = ? limit 0,1", array($id_kh));
	if(!$kh || !$kh['id']) die("Khóa học cabin không tồn tại");

	$rows = $d->rawQuery(
		"select dk.*, p.tenvi, p.cccd as hv_cccd, p.hang "
		. "from #_cabin_dangky dk "
		. "left join #_product p on p.id = dk.id_hocvien "
		. "where dk.id_khoahoc = ? "
		. "order by dk.ngay_hoc asc, dk.ca asc, p.tenvi asc",
		array($id_kh)
	);

	require_once LIBRARIES.'PHPExcel.php';

	$objPHPExcel = new PHPExcel();
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle('Đăng ký cabin');

	$headers = array('STT', 'Họ tên', 'CCCD', 'Người nộp hồ sơ', 'Ngày học', 'Ca', 'Giờ');
	$col = 'A';
	foreach($headers as $h)
	{
		$sheet->setCellValue($col.'1', $h);
		$sheet->getStyle($col.'1')->getFont()->setBold(true);
		$col++;
	}

	$slots = cabin_time_slots();
	$r = 2;
	$stt = 1;
	foreach($rows as $row)
	{
		$ca = (int)$row['ca'];
		$caLabel = isset($slots[$ca]) ? $slots[$ca]['label'] : ('Ca '.$ca);
		$gio = trim($row['gio_b_d']).' - '.trim($row['gio_kt']);
		$ngay = $row['ngay_hoc'] ? date('d/m/Y', strtotime($row['ngay_hoc'])) : '';

		$sheet->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$sheet->setCellValue('B'.$r, $row['tenvi']);
		$sheet->setCellValueExplicit('C'.$r, $row['hv_cccd'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValue('D'.$r, $row['hang']);
		$sheet->setCellValue('E'.$r, $ngay);
		$sheet->setCellValue('F'.$r, $caLabel);
		$sheet->setCellValue('G'.$r, $gio);
		$r++;
		$stt++;
	}

	foreach(range('A', 'G') as $c)
		$sheet->getColumnDimension($c)->setAutoSize(true);

	$filename = 'dangky-cabin-'.$id_kh.'-'.date('Ymd_His').'.xlsx';

	if(ob_get_length()) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'.$filename.'"');
	header('Cache-Control: max-age=0');

	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit();
}
