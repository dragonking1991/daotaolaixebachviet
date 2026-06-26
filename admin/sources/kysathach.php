<?php
if(!defined('SOURCES')) die("Error");

switch($act)
{
	case "man":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		get_items_kysathach();
		$template = "kysathach/man/items";
		break;
	case "add":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		$template = "kysathach/man/item_add";
		break;
	case "edit":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		get_item_kysathach();
		$template = "kysathach/man/item_add";
		break;
	case "save":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		save_item_kysathach();
		break;
	case "delete":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		delete_item_kysathach();
		break;
	case "upload":
		if(kysathach_permission_denied(array('kysathach_upload'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		get_items_kysathach_all();
		$template = "kysathach/upload/items";
		break;
	case "uploadExcel":
		if(kysathach_permission_denied(array('kysathach_upload'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		uploadExcel_kysathach();
		break;
	case "data":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		get_data_kysathach();
		$template = "kysathach/data/items";
		break;
	case "deleteData":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		deleteData_kysathach();
		break;
	case "deleteAllData":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		deleteAllData_kysathach();
		break;
	case "ajaxData":
		if(kysathach_permission_denied(array('kysathach_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		ajaxData_kysathach();
		break;
	default:
		$template = "404";
}

function kysathach_permission_denied($permissions = array())
{
	global $func, $login_admin;

	if(!$func->check_permission()) return false;
	if(!isset($_SESSION[$login_admin]['active']) || $_SESSION[$login_admin]['active'] != true) return true;
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return true;

	foreach($permissions as $permission)
	{
		if(in_array($permission, $_SESSION['list_quyen'])) return false;
	}

	return true;
}

function get_items_kysathach()
{
	global $d, $func, $curPage, $items, $paging;

	$where = "";
	if(isset($_REQUEST['keyword']))
	{
		$keyword = htmlspecialchars($_REQUEST['keyword']);
		$where .= " and (ten_viettat LIKE '%$keyword%' or loai_sathach LIKE '%$keyword%')";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$limit = " limit ".$startpoint.",".$per_page;
	$sql = "select k.*, (select count(*) from #_product p where p.id_kysathach = k.id) as so_ban_ghi from #_kysathach k where k.hienthi >= 0 $where order by k.ngay_sathach desc, k.id desc $limit";
	$items = $d->rawQuery($sql);
	$sqlNum = "select count(*) as 'num' from #_kysathach where hienthi >= 0 $where";
	$count = $d->rawQueryOne($sqlNum);
	$total = $count['num'];
	$url = "index.php?com=kysathach&act=man";
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function get_items_kysathach_all()
{
	global $d, $items_ky;

	$items_ky = $d->rawQuery("select * from #_kysathach where hienthi = 1 order by ngay_sathach desc, id desc");
}

function get_item_kysathach()
{
	global $d, $func, $curPage, $item;

	$id = (isset($_GET['id'])) ? (int)htmlspecialchars($_GET['id']) : 0;

	if(!$id)
		$func->transfer("Không nhận được dữ liệu", "index.php?com=kysathach&act=man&p=".$curPage, false);

	$item = $d->rawQueryOne("select * from #_kysathach where id = ? limit 0,1", array($id));

	if(!$item['id'])
		$func->transfer("Dữ liệu không có thực", "index.php?com=kysathach&act=man&p=".$curPage, false);
}

function save_item_kysathach()
{
	global $d, $func, $curPage, $login_admin;

	if(empty($_POST))
		$func->transfer("Không nhận được dữ liệu", "index.php?com=kysathach&act=man&p=".$curPage, false);

	$data = array();
	$data['ngay_sathach'] = isset($_POST['data']['ngay_sathach']) ? htmlspecialchars($_POST['data']['ngay_sathach']) : '';
	$data['ten_viettat'] = isset($_POST['data']['ten_viettat']) ? htmlspecialchars($_POST['data']['ten_viettat']) : '';
	$data['loai_sathach'] = isset($_POST['data']['loai_sathach']) ? htmlspecialchars($_POST['data']['loai_sathach']) : '';

	if(empty($data['ngay_sathach']) || empty($data['ten_viettat']) || empty($data['loai_sathach']))
		$func->transfer("Vui lòng nhập đầy đủ thông tin", "index.php?com=kysathach&act=man&p=".$curPage, false);

	$id = isset($_POST['id']) ? (int)htmlspecialchars($_POST['id']) : 0;

	if($id)
	{
		$d->where('id', $id);
		if($d->update('kysathach', $data))
			$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=kysathach&act=man&p=".$curPage);
		else
			$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=kysathach&act=man&p=".$curPage, false);
	}
	else
	{
		$data['ngaytao'] = time();
		$data['user_tao'] = isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
		$data['hienthi'] = 1;

		if($d->insert('kysathach', $data))
			$func->transfer("Lưu dữ liệu thành công", "index.php?com=kysathach&act=man&p=".$curPage);
		else
			$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=kysathach&act=man&p=".$curPage, false);
	}
}

function delete_item_kysathach()
{
	global $d, $func, $curPage;

	$id = (isset($_GET['id'])) ? (int)htmlspecialchars($_GET['id']) : 0;

	if($id)
	{
		$d->rawQuery("delete from #_kysathach where id = ?", array($id));
		$func->transfer("Xóa dữ liệu thành công", "index.php?com=kysathach&act=man&p=".$curPage);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		for($i = 0; $i < count($listid); $i++)
		{
			$tid = (int)htmlspecialchars($listid[$i]);
			$d->rawQuery("delete from #_kysathach where id = ?", array($tid));
		}
		$func->transfer("Xóa dữ liệu thành công", "index.php?com=kysathach&act=man&p=".$curPage);
	}
	else
		$func->transfer("Không nhận được dữ liệu", "index.php?com=kysathach&act=man&p=".$curPage, false);
}

function uploadExcel_kysathach()
{
	global $d, $func, $curPage, $config, $login_admin;

	$id_kysathach = isset($_POST['id_kysathach']) ? (int)$_POST['id_kysathach'] : 0;

	if(!$id_kysathach)
		$func->transfer("Vui lòng chọn kỳ sát hạch", "index.php?com=kysathach&act=upload", false);

	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		$func->transfer("Vui lòng chọn file Excel", "index.php?com=kysathach&act=upload", false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	if(!in_array($ext, array('xls', 'xlsx')))
		$func->transfer("Chỉ hỗ trợ file .xls hoặc .xlsx", "index.php?com=kysathach&act=upload", false);

	require_once LIBRARIES.'PHPExcel.php';

	$inputFileName = $file['tmp_name'];

	if($ext == 'xlsx')
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	else
		$objReader = PHPExcel_IOFactory::createReader('Excel5');

	$objPHPExcel = $objReader->load($inputFileName);
	$sheet = $objPHPExcel->getSheet(0);
	$highestRow = $sheet->getHighestRow();

	// Get kỳ sát hạch info for QR generation
	$ky = $d->rawQueryOne("select * from #_kysathach where id = ? limit 0,1", array($id_kysathach));
	$type = 'qr';

	// Build row → QR image map from Excel column G drawings
	$qrImageMap = array();
	foreach($sheet->getDrawingCollection() as $drawing)
	{
		$coords = $drawing->getCoordinates();
		if(!preg_match('/^([A-Z]+)(\d+)$/', $coords, $matches)) continue;
		if($matches[1] !== 'G') continue;
		$drawingRow = (int)$matches[2];

		$imgData = null;
		if($drawing instanceof PHPExcel_Worksheet_MemoryDrawing)
		{
			ob_start();
			call_user_func($drawing->getRenderingFunction(), $drawing->getImageResource());
			$imgData = ob_get_clean();
		}
		elseif($drawing instanceof PHPExcel_Worksheet_Drawing)
		{
			$path = $drawing->getPath();
			if(!empty($path))
			{
				$imgData = @file_get_contents($path);
				if($imgData === false) $imgData = null;
			}
		}

		if(!empty($imgData)) $qrImageMap[$drawingRow] = $imgData;
	}

	$imported = 0;
	$skippedQrRows = array();
	for($row = 2; $row <= $highestRow; $row++)
	{
		$stt      = trim($sheet->getCell('A'.$row)->getValue());
		$hoTen    = trim($sheet->getCell('B'.$row)->getValue());
		$ngaySinh = trim($sheet->getCell('C'.$row)->getValue());
		$cccd     = trim($sheet->getCell('D'.$row)->getValue());
		$hang     = trim($sheet->getCell('E'.$row)->getValue());
		$soTien   = trim($sheet->getCell('F'.$row)->getValue());
		$qrImageData = null;
		for($r = $row; $r <= $row + 6; $r++)
		{
			if(isset($qrImageMap[$r])) { $qrImageData = $qrImageMap[$r]; break; }
		}

		if(empty($hoTen) || empty($cccd)) continue;

		$soTien = str_replace(array(",", "."), "", $soTien);

		// Check if record exists
		$existing = $d->rawQueryOne("select id from #_product where cccd = ? and type = ? limit 0,1", array($cccd, $type));

		$qr_filename = '';
		if($qrImageData !== null)
		{
			$qr_filename = 'qr-' . $cccd . '.png';
			$qr_path = ROOT . '/../upload/product/' . $qr_filename;
			file_put_contents($qr_path, $qrImageData);
		}
		else
		{
			$skippedQrRows[] = $row;
		}

		$data = array(
			'tenvi'          => $hoTen,
			'tenkhongdauvi'  => $func->changeTitle($hoTen),
			'ngaysinh'       => $ngaySinh,
			'cccd'           => $cccd,
			'hang'           => $hang,
			'gia'            => (float)$soTien,
			'photo'          => $qr_filename,
			'type'           => $type,
			'hienthi'        => 1,
			'id_kysathach'   => $id_kysathach
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

	$message = "Import thành công $imported bản ghi cho kỳ sát hạch: " . $ky['ten_viettat'] . " - " . $ky['loai_sathach'];
	if(count($skippedQrRows) > 0) {
		$message .= "<br>Cảnh báo: Bỏ qua lưu QR tại các dòng không có ảnh QR: ".implode(', ', $skippedQrRows);
	}
	$func->transfer($message, "index.php?com=kysathach&act=upload");
}

function get_data_kysathach()
{
	global $d, $func, $curPage, $items_data, $paging_data, $ky_info;

	$id_ky = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id_ky)
		$func->transfer("Không nhận được dữ liệu", "index.php?com=kysathach&act=man", false);

	$ky_info = $d->rawQueryOne("select * from #_kysathach where id = ? limit 0,1", array($id_ky));
	if(!$ky_info || !$ky_info['id'])
		$func->transfer("Kỳ sát hạch không tồn tại", "index.php?com=kysathach&act=man", false);

	$where = "";
	if(isset($_REQUEST['keyword']))
	{
		$keyword = htmlspecialchars($_REQUEST['keyword']);
		$where .= " and (tenvi LIKE '%$keyword%' or cccd LIKE '%$keyword%')";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$limit = " limit ".$startpoint.",".$per_page;
	$sql = "select * from #_product where id_kysathach = ? $where order by stt asc, id desc $limit";
	$items_data = $d->rawQuery($sql, array($id_ky));
	$sqlNum = "select count(*) as 'num' from #_product where id_kysathach = ? $where";
	$count = $d->rawQueryOne($sqlNum, array($id_ky));
	$total = $count['num'];
	$url = "index.php?com=kysathach&act=data&id=".$id_ky;
	$paging_data = $func->pagination($total, $per_page, $curPage, $url);
}

function deleteData_kysathach()
{
	global $d, $func, $curPage;

	$id_ky = isset($_GET['id_ky']) ? (int)$_GET['id_ky'] : 0;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=kysathach&act=data&id=".$id_ky."&p=".$curPage;

	if($id)
	{
		$d->rawQuery("delete from #_product where id = ?", array($id));
		$func->transfer("Xóa thành công", $redirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		for($i = 0; $i < count($listid); $i++)
		{
			$tid = (int)htmlspecialchars($listid[$i]);
			$d->rawQuery("delete from #_product where id = ?", array($tid));
		}
		$func->transfer("Xóa thành công", $redirect);
	}
	else
		$func->transfer("Không nhận được dữ liệu", $redirect, false);
}

function deleteAllData_kysathach()
{
	global $d, $func;

	$id_ky = isset($_GET['id_ky']) ? (int)$_GET['id_ky'] : 0;

	if(!$id_ky)
		$func->transfer("Không nhận được dữ liệu", "index.php?com=kysathach&act=man", false);

	$count = $d->rawQueryOne("select count(*) as num from #_product where id_kysathach = ?", array($id_ky));
	$total = $count['num'];

	// Delete QR image files
	$items = $d->rawQuery("select photo from #_product where id_kysathach = ? and photo != ''", array($id_ky));
	foreach($items as $item)
	{
		$filepath = ROOT.'/../upload/product/'.$item['photo'];
		if(file_exists($filepath)) @unlink($filepath);
	}

	$d->rawQuery("delete from #_product where id_kysathach = ?", array($id_ky));
	$func->transfer("Đã xóa toàn bộ $total bản ghi", "index.php?com=kysathach&act=man");
}

function ajaxData_kysathach()
{
	global $d;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id) { echo '<div class="text-center p-3 text-danger">Không nhận được dữ liệu</div>'; exit(); }

	$items = $d->rawQuery("select * from #_product where id_kysathach = ? order by stt asc, id asc", array($id));

	if(empty($items)) { echo '<div class="text-center p-3">Không có dữ liệu</div>'; exit(); }

	$html = '<table class="table table-hover table-sm text-sm mb-0">';
	$html .= '<thead><tr>';
	$html .= '<th class="text-center" width="5%">STT</th>';
	$html .= '<th>Họ tên</th>';
	$html .= '<th>Ngày sinh</th>';
	$html .= '<th>Số CCCD</th>';
	$html .= '<th>Hạng GPLX</th>';
	$html .= '<th class="text-right">Số tiền</th>';
	$html .= '<th class="text-center">QR</th>';
	$html .= '</tr></thead><tbody>';

	foreach($items as $i => $row)
	{
		$photo = '';
		if(!empty($row['photo']))
			$photo = '<a href="../upload/product/'.htmlspecialchars($row['photo']).'" target="_blank"><img src="../upload/product/'.htmlspecialchars($row['photo']).'" style="max-height:40px"></a>';

		$html .= '<tr>';
		$html .= '<td class="text-center">'.($i + 1).'</td>';
		$html .= '<td>'.htmlspecialchars($row['tenvi']).'</td>';
		$html .= '<td>'.htmlspecialchars($row['ngaysinh']).'</td>';
		$html .= '<td>'.htmlspecialchars($row['cccd']).'</td>';
		$html .= '<td>'.htmlspecialchars($row['hang']).'</td>';
		$html .= '<td class="text-right">'.number_format((float)$row['gia'], 0, ',', '.').'</td>';
		$html .= '<td class="text-center">'.$photo.'</td>';
		$html .= '</tr>';
	}

	$html .= '</tbody></table>';
	echo $html;
	exit();
}
