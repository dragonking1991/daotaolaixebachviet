<?php
if(!defined('SOURCES')) die("Error");

require_once SOURCES.'xangdau/hocvien_financial_import.php';
require_once LIBRARIES.'course_options.php';

function dt_finance_source_data($course, $page = null)
{
	global $d;
	$where = $course === '' ? '1' : 'khoa=?';
	$params = $course === '' ? array() : array($course);
	$count = $d->rawQueryOne("select count(*) as total from #_xd_hocvien where $where", $params);
	if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không đọc được tổng số hồ sơ tiền nguồn.');
	$fields = implode(',', array_keys(xd_student_financial_fields()));
	$limit = $page === null ? '' : ' limit '.((max(1,(int)$page)-1)*30).',30';
	$rows = $d->rawQuery("select id,ho_ten,cccd,khoa,nhom,gv_hoten,$fields from #_xd_hocvien
		where $where order by substring_index(trim(ho_ten),' ',-1),ho_ten,id$limit", $params);
	if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không đọc được tiền nguồn import XD; kiểm tra cập nhật các cột tài chính XD.');
	return array('rows'=>$rows, 'total'=>(int)$count['total']);
}

function dt_finance_amount($value)
{
	$value = trim((string)$value);
	if(!preg_match('/^\d{1,13}$/', $value)) throw new InvalidArgumentException('Số tiền phải là số nguyên đồng không âm, tối đa 13 chữ số.');
	return (int)$value;
}

function dt_finance_net($salary, $fuel, $advance)
{
	$net = $salary + $fuel - $advance;
	if($net < 0) throw new InvalidArgumentException('Tổng lương và nhiên liệu nhỏ hơn số đã tạm ứng.');
	return $net;
}

function dt_finance_import_amount($value)
{
	$value = preg_replace('/[\s\x{00A0}]+/u', '', (string)$value);
	if($value === '') return 0;
	if(preg_match('/^\d{1,3}([.,]\d{3})+$/', $value)) $value = str_replace(array('.',','), '', $value);
	else $value = str_replace(',', '.', $value);
	if(!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0 || (float)$value > 9999999999999 || floor((float)$value) !== (float)$value)
		throw new InvalidArgumentException('Học phí phải là số nguyên đồng không âm.');
	return (int)$value;
}

function dt_finance_eligible($student, $group)
{
	$hang = dt_norm_hang($student['hang']);
	if($group === 'ce' && $hang !== 'CE') return false;
	if($group !== 'ce' && $hang === 'CE') return false;
	if((int)($student['id_khoa'] ?? 0) <= 0) return false;
	$system = trim((string)($student['he_daotao'] ?? ''));
	if($system === '') $system = (string)($student['he_khoa'] ?? '');
	if(!in_array(dt_norm_header($system), array('ck','dat'), true)) return false;
	$aggregate = dt_dat_aggregate($student['cccd'], (int)$student['id_khoa']);
	list($passed) = dt_dat_danhgia($hang, $aggregate);
	return $passed || dt_dat_override_get((int)$student['id_khoa'], $student['cccd']) === 1;
}

function dt_finance_list()
{
	global $d, $func, $finance_students, $finance_courses, $finance_history, $finance_course, $finance_group,
		$finance_source, $finance_source_courses, $finance_source_course, $finance_source_paging;
	$finance_course = (int)dt_req('id_khoa', 0);
	$finance_group = dt_req('group') === 'ce' ? 'ce' : 'bc';
	$finance_courses = dt_khoa_options();
	$finance_students = array(); $finance_history = array();
	$finance_source_course = trim((string)dt_req('source_khoa'));
	$finance_source_courses = $d->rawQuery("select x.khoa,count(distinct x.id) as total,max(k.ten_khoa) as ten_khoa
		from #_xd_hocvien x left join #_dt_khoa k on k.ma_khoa=x.khoa or k.ten_khoa=x.khoa
		group by x.khoa order by x.khoa");
	if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không đọc được danh sách khóa tiền nguồn.');
	$finance_source = dt_finance_source_data($finance_source_course, max(1,(int)dt_req('p',1)));
	$sourceUrl = 'index.php?'.http_build_query(array('com'=>'daotao','act'=>'taichinh','id_khoa'=>$finance_course,
		'group'=>$finance_group,'source_khoa'=>$finance_source_course));
	$finance_source_paging = $func->pagination($finance_source['total'],30,max(1,(int)dt_req('p',1)),$sourceUrl);
	if(empty($_SESSION['dt_finance_csrf'])) $_SESSION['dt_finance_csrf'] = bin2hex(random_bytes(24));
	if(!$finance_course) return;
	$rows = $d->rawQuery("select h.*, k.he_daotao as he_khoa, coalesce(f.tam_ung,0) as tam_ung, coalesce(f.thanh_toan,0) as thanh_toan, coalesce(f.da_tt,0) as da_tt
		from #_dt_hocvien h inner join #_dt_khoa k on k.id=h.id_khoa left join
		(select id_hocvien, sum(case when loai='tam_ung' then so_tien else 0 end) as tam_ung,
		sum(case when loai='thanh_toan' then so_tien else 0 end) as thanh_toan,
		sum(case when loai='thanh_toan' then 1 else 0 end) as da_tt from #_dt_taichinh group by id_hocvien) f on f.id_hocvien=h.id
		where h.id_khoa=? order by h.hoten asc", array($finance_course));
	foreach($rows as $row) if(dt_finance_eligible($row, $finance_group)) $finance_students[] = $row;
	$finance_history = $d->rawQuery('select f.*,h.hoten,h.cccd from #_dt_taichinh f inner join #_dt_hocvien h on h.id=f.id_hocvien where h.id_khoa=? order by f.id desc limit 100', array($finance_course));
}

function dt_finance_save()
{
	global $d, $func;
	$course = (int)($_POST['id_khoa'] ?? 0);
	$group = ($_POST['group'] ?? '') === 'ce' ? 'ce' : 'bc';
	$back = 'index.php?com=daotao&act=taichinh&id_khoa='.$course.'&group='.$group;
	if(empty($_SESSION['dt_finance_csrf']) || !hash_equals($_SESSION['dt_finance_csrf'], (string)($_POST['csrf'] ?? '')))
		$func->transfer('Phiên thao tác không hợp lệ.', $back, false);
	$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? array())))));
	sort($ids);
	$kind = (string)($_POST['loai'] ?? '');
	$date = (string)($_POST['ngay'] ?? '');
	$key = (string)($_POST['request_key'] ?? '');
	if(!$course || !$ids || !in_array($kind, array('tam_ung','thanh_toan'), true) || !preg_match('/^[a-f0-9]{32}$/', $key))
		$func->transfer('Chọn khóa, học viên và loại giao dịch.', $back, false);
	$parts = explode('-', $date);
	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)($parts[1] ?? 0), (int)($parts[2] ?? 0), (int)($parts[0] ?? 0)))
		$func->transfer('Ngày giao dịch không hợp lệ.', $back, false);
	$salary = 0; $fuel = 0; $manualAdvance = 0;
	try
	{
		if($kind === 'tam_ung') $manualAdvance = dt_finance_amount($_POST['so_tien_tam_ung'] ?? '');
		else { $salary = dt_finance_amount($_POST['tien_luong'] ?? ''); $fuel = dt_finance_amount($_POST['tien_nhien_lieu'] ?? ''); }
	}
	catch(InvalidArgumentException $error) { $func->transfer($error->getMessage(), $back, false); }
	if($kind === 'tam_ung' && $manualAdvance <= 0) $func->transfer('Số tiền tạm ứng phải lớn hơn 0.', $back, false);
	if($kind === 'thanh_toan' && $salary + $fuel <= 0) $func->transfer('Tổng lương và nhiên liệu phải lớn hơn 0.', $back, false);
	$d->startTransaction();
	try
	{
		foreach($ids as $id)
		{
			$student = $d->rawQueryOne('select h.*,k.he_daotao as he_khoa from #_dt_hocvien h inner join #_dt_khoa k on k.id=h.id_khoa where h.id=? and h.id_khoa=? for update', array($id, $course));
			if(!$student || !dt_finance_eligible($student, $group)) throw new RuntimeException('Học viên không thuộc khóa/nhóm đã chọn hoặc chưa đạt DAT thuộc hệ CK/DAT.');
			$entries = $d->rawQuery('select loai,so_tien from #_dt_taichinh where id_hocvien=? for update', array($id));
			if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không thể đọc lịch sử thanh toán.');
			$sum = array('tam_ung'=>0,'da_tt'=>0);
			foreach($entries as $entry)
			{
				if($entry['loai']==='tam_ung') $sum['tam_ung'] += (int)$entry['so_tien'];
				if($entry['loai']==='thanh_toan') $sum['da_tt']++;
			}
			if((int)$sum['da_tt'] > 0) throw new RuntimeException($student['hoten'].' đã thanh toán, không thể ghi nhận thêm.');
			$amount = $kind === 'tam_ung' ? $manualAdvance : dt_finance_net($salary, $fuel, (int)$sum['tam_ung']);
			$d->rawQuery('insert into #_dt_taichinh (id_hocvien,loai,ngay,tien_luong,tien_nhien_lieu,so_tien,request_key,nguoi_nhap,ngaytao) values (?,?,?,?,?,?,?,?,?)',
				array($id,$kind,$date,$salary,$fuel,$amount,$key,dt_username(),time()));
			if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không thể lưu hoặc giao dịch đã gửi trước đó.');
		}
		if($d->commit() === false) throw new RuntimeException('Không thể hoàn tất giao dịch.');
	}
	catch(Exception $error)
	{
		$d->rollback();
		$func->transfer($error->getMessage().' Chưa lưu giao dịch nào trong lần này.', $back, false);
	}
	dt_audit('payment', 'taichinh', count($ids), $kind.' id_khoa='.$course.' ids='.implode(',', $ids));
	$func->transfer('Đã lưu '.count($ids).' giao dịch học viên.', $back);
}

function dt_finance_export()
{
	global $finance_students;
	if(dt_req('source_export') === '1')
	{
		$data = dt_finance_source_data(trim((string)dt_req('source_khoa')));
		$rows = array();
		foreach($data['rows'] as $student)
		{
			$row = array($student['ho_ten'],$student['cccd'],$student['khoa'],$student['nhom'],$student['gv_hoten']);
			foreach(xd_student_financial_fields() as $key => $label) $row[] = (float)$student[$key];
			$rows[] = $row;
		}
		dt_xlsx_simple('Tien nguon Excel','tien-nguon-excel-'.date('Ymd_His').'.xlsx',
			array_merge(array('Họ tên','CCCD','Khóa XD','Nhóm XD','Giáo viên'),array_values(xd_student_financial_fields())),
			$rows,array(0,1,2,3,4));
		return;
	}
	dt_finance_list();
	$rows = array();
	foreach($finance_students as $student) $rows[] = array($student['hoten'],$student['cccd'],$student['hang'],$student['gv_hoten'],$student['hoc_phi_da_dong'],$student['hoc_phi_con_thieu'],$student['tam_ung'],$student['thanh_toan'],$student['da_tt'] ? 'Đã thanh toán' : 'Chưa thanh toán');
	dt_xlsx_simple('Tai chinh', 'hoc-phi-tam-ung-thanh-toan-'.date('Ymd_His').'.xlsx', array('Họ tên','CCCD','Hạng','Giáo viên','Học phí đã đóng','Học phí còn thiếu','Đã tạm ứng','Thanh toán','Trạng thái'), $rows, array(1));
}