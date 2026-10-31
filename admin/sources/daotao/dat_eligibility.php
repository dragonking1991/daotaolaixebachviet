<?php
if(!defined('SOURCES')) die("Error");

function dt_dat_entry_subjects()
{
	$subjects = dt_mon_lythuyet();
	return array_intersect_key($subjects, array_flip(array('ky_thuat','phan_1','phan_2','phan_3')));
}

function dt_dat_entry_evaluate($results)
{
	$subjects = array();
	$passed = 0;
	foreach(dt_dat_entry_subjects() as $key => $label)
	{
		$result = $results[$key] ?? null;
		$progress = $result['tien_do'] ?? null;
		$score = $result['diem_kt'] ?? null;
		$reasons = array();
		if(!$result) $reasons[] = 'Chưa có kết quả';
		else
		{
			if(!is_numeric($progress)) $reasons[] = 'Thiếu tiến độ';
			elseif((float)$progress < 70) $reasons[] = 'Tiến độ < 70%';
			if(!is_numeric($score)) $reasons[] = 'Thiếu điểm kiểm tra';
			elseif((float)$score < 5) $reasons[] = 'Điểm kiểm tra < 5';
		}
		$eligible = !$reasons;
		if($eligible) $passed++;
		$subjects[$key] = array('label'=>$label, 'tien_do'=>$progress, 'diem_kt'=>$score,
			'eligible'=>$eligible, 'reason'=>implode('; ', $reasons));
	}
	return array('eligible'=>$passed === 4, 'passed'=>$passed, 'subjects'=>$subjects);
}

function dt_dat_entry_list()
{
	global $d, $ds_khoa, $id_khoa_sel, $dat_entry, $dat_entry_stats;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = max(0, (int)dt_req('id_khoa', 0));
	$dat_entry = array();
	$dat_entry_stats = array('eligible'=>0, 'pending'=>0);
	$keyword = dt_req('keyword');
	if(!$id_khoa_sel && $keyword === '') return;
	$where = 'h.id > 0';
	$params = array();
	if($id_khoa_sel) { $where .= ' and h.id_khoa=?'; $params[] = $id_khoa_sel; }
	if($keyword !== '')
	{
		$where .= ' and (h.hoten like ? or h.cccd like ? or h.ma_hv like ?)';
		foreach(array('hoten','cccd','ma_hv') as $field) $params[] = '%'.$keyword.'%';
	}
	$teacher = dt_gv_key(dt_req('gv'));
	if($teacher !== '') { $where .= ' and h.gv_key=?'; $params[] = $teacher; }
	$students = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa
		from #_dt_hocvien h left join #_dt_khoa k on k.id=h.id_khoa
		where $where order by substring_index(h.hoten,' ',-1),h.hoten,h.id", $params);
	if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không đọc được học viên xét điều kiện học DAT.');
	$results = array();
	$courseIds = array_values(array_unique(array_column($students, 'id_khoa')));
	if($courseIds)
	{
		$marks = implode(',', array_fill(0, count($courseIds), '?'));
		$rows = $d->rawQuery("select id_khoa,cccd,mon,tien_do,diem_kt from #_dt_lythuyet
			where id_khoa in ($marks) and mon in ('ky_thuat','phan_1','phan_2','phan_3')", $courseIds);
		if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không đọc được bốn môn xét điều kiện học DAT.');
		foreach($rows as $row) $results[$row['id_khoa'].'|'.$row['cccd']][$row['mon']] = $row;
	}
	$status = dt_req('trang_thai');
	foreach($students as $student)
	{
		$evaluation = dt_dat_entry_evaluate($results[$student['id_khoa'].'|'.$student['cccd']] ?? array());
		$dat_entry_stats[$evaluation['eligible'] ? 'eligible' : 'pending']++;
		if($status === 'dat' && !$evaluation['eligible']) continue;
		if($status === 'chua' && $evaluation['eligible']) continue;
		$dat_entry[] = array('student'=>$student, 'evaluation'=>$evaluation);
	}
}

function dt_dat_entry_export()
{
	global $dat_entry;
	dt_dat_entry_list();
	$headers = array('STT','Mã HV','Họ và tên','CCCD','Hạng','Mã khóa','Tên khóa','Giáo viên');
	foreach(dt_dat_entry_subjects() as $label)
		foreach(array('Tiến độ (%)','Điểm kiểm tra','Kết quả') as $column) $headers[] = $label.' - '.$column;
	$headers[] = 'Điều kiện học DAT';
	$rows = array();
	foreach($dat_entry as $item)
	{
		$student = $item['student'];
		$evaluation = $item['evaluation'];
		$row = array(count($rows)+1,$student['ma_hv'],$student['hoten'],$student['cccd'],$student['hang'],
			$student['ma_khoa'],$student['ten_khoa'],$student['gv_hoten']);
		foreach($evaluation['subjects'] as $subject)
			$row = array_merge($row, array($subject['tien_do'],$subject['diem_kt'],
				$subject['eligible'] ? 'Đạt' : $subject['reason']));
		$row[] = $evaluation['eligible'] ? 'Đủ điều kiện học DAT' : 'Chưa đủ ('.$evaluation['passed'].'/4)';
		$rows[] = $row;
	}
	dt_xlsx_simple('Dieu kien hoc DAT', 'du-dieu-kien-hoc-dat', $headers, $rows, array(1,2,3,4,5,6,7));
}
