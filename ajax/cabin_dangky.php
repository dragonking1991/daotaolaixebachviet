<?php
include "ajax_config.php";
require_once LIBRARIES.'cabin_config.php';

header('Content-Type: application/json; charset=utf-8');

function cabin_json($success, $message, $html)
{
    echo json_encode(array(
        'success' => $success ? 1 : 0,
        'message' => $message,
        'html' => $html
    ));
    exit();
}

function cabin_normalize_cccd_pair($cccd)
{
    $cccd = trim((string)$cccd);
    $cccd2 = $cccd;
    if(strlen($cccd) == 11) {
        $cccd2 = '0'.$cccd;
    } elseif(strlen($cccd) == 12 && substr($cccd, 0, 1) === '0') {
        $cccd2 = substr($cccd, 1);
    }
    return array($cccd, $cccd2);
}

function cabin_week_start($input = '')
{
    $ts = $input ? strtotime($input) : time();
    if($ts === false) $ts = time();

    $dow = (int)date('N', $ts);
    if($dow === 7) {
        $ts = strtotime('+1 day', $ts);
        $dow = 1;
    }

    $mondayTs = strtotime('-'.($dow - 1).' day', strtotime(date('Y-m-d', $ts)));
    return date('Y-m-d', $mondayTs);
}

function cabin_course_week_bounds($course)
{
    $start = isset($course['ngay_batdau']) ? $course['ngay_batdau'] : date('Y-m-d');
    $end = isset($course['ngay_ketthuc']) ? $course['ngay_ketthuc'] : $start;

    $minWeekStart = cabin_week_start($start);
    $maxWeekStart = cabin_week_start($end);

    if($maxWeekStart < $minWeekStart) {
        $maxWeekStart = $minWeekStart;
    }

    return array($minWeekStart, $maxWeekStart);
}

function cabin_render_panel($d, $student, $course, $weekStart)
{
    $maxStudentSlots = 1;
    list($minWeekStart, $maxWeekStart) = cabin_course_week_bounds($course);
    $weekStart = cabin_week_start($weekStart);
    if($weekStart < $minWeekStart) $weekStart = $minWeekStart;
    if($weekStart > $maxWeekStart) $weekStart = $maxWeekStart;

    $weekEnd = date('Y-m-d', strtotime($weekStart.' +5 day'));
    $today = date('Y-m-d');
    $closed = ($today > $course['ngay_ketthuc']);
    $capacity = max(1, (int)$course['suc_chua_ca']);

    // Chỉ có 1 cabin dùng chung: số lượng phải tính gộp toàn bộ khóa theo ngày + ca.
    $slotRows = $d->rawQuery(
        "select ngay_hoc, ca, count(*) as total from #_cabin_dangky where ngay_hoc between ? and ? group by ngay_hoc, ca",
        array($weekStart, $weekEnd)
    );
    $slotCountMap = array();
    foreach($slotRows as $r)
    {
        $k = $r['ngay_hoc'].'_'.$r['ca'];
        $slotCountMap[$k] = (int)$r['total'];
    }

    $studentRows = $d->rawQuery(
        "select id, ngay_hoc, ca from #_cabin_dangky where id_khoahoc = ? and id_hocvien = ? order by ngay_hoc asc, ca asc",
        array($course['id'], $student['id'])
    );
    $studentRegMap = array();
    $studentRegCount = 0;
    foreach($studentRows as $r)
    {
        $studentRegCount++;
        $studentRegMap[$r['ngay_hoc'].'_'.$r['ca']] = 1;
    }

    $weekDays = array();
    for($i = 0; $i < 6; $i++)
    {
        $date = date('Y-m-d', strtotime($weekStart.' +'.$i.' day'));
        $dow = (int)date('N', strtotime($date));
        $weekDays[] = array(
            'date' => $date,
            'dow' => $dow,
            'label' => cabin_dow_label($dow),
            'view' => date('d/m', strtotime($date))
        );
    }

    $weekPrev = date('Y-m-d', strtotime($weekStart.' -7 day'));
    $weekNext = date('Y-m-d', strtotime($weekStart.' +7 day'));
    $canPrev = ($weekStart > $minWeekStart);
    $canNext = ($weekStart < $maxWeekStart);
    $slots = cabin_time_slots();

    $html = '';
    $html .= '<div style="border:1px solid #e5e5e5; border-radius:8px; padding:12px; background:#fff;">';
    $html .= '<div style="margin-bottom:10px;">';
    $html .= '<div style="font-weight:700; color:#0a6ebd; margin-bottom:6px;">Học viên: '.htmlspecialchars($student['tenvi']).'</div>';
    $html .= '<div style="font-size:13px; color:#333;">CCCD: <strong>'.htmlspecialchars($student['cccd']).'</strong> | Người nộp hồ sơ: <strong>'.htmlspecialchars($student['hang']).'</strong></div>';
    $html .= '<div style="font-size:13px; color:#333;">Khóa: <strong>'.htmlspecialchars($course['ten']).'</strong> ('.date('d/m/Y', strtotime($course['ngay_batdau'])).' - '.date('d/m/Y', strtotime($course['ngay_ketthuc'])).')</div>';
    $html .= '<div style="font-size:13px; color:#333;">Đã đăng ký: <strong>'.$studentRegCount.'/'.$maxStudentSlots.' ca</strong></div>';
    $html .= '</div>';

    if($closed) {
        $html .= '<div style="margin-bottom:10px; padding:10px; border:1px solid #f5c6cb; background:#fff3f5; color:#9f1d35; border-radius:6px;">Khóa học đã kết thúc. Hệ thống chỉ cho xem lịch đã đăng ký, không cho đăng ký mới. Vui lòng liên hệ văn phòng để được hỗ trợ.</div>';
    }

    $html .= '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">';
    if($canPrev) {
        $html .= '<button type="button" class="btn-cabin-week" data-week-start="'.$weekPrev.'" style="padding:6px 10px; border:1px solid #ddd; border-radius:4px; background:#f8f9fa;">Tuần trước</button>';
    } else {
        $html .= '<button type="button" disabled style="padding:6px 10px; border:1px solid #e5e5e5; border-radius:4px; background:#f3f3f3; color:#aaa; cursor:not-allowed;">Tuần trước</button>';
    }
    $html .= '<div style="font-weight:700;">Tuần '.date('d/m/Y', strtotime($weekStart)).' - '.date('d/m/Y', strtotime($weekEnd)).'</div>';
    if($canNext) {
        $html .= '<button type="button" class="btn-cabin-week" data-week-start="'.$weekNext.'" style="padding:6px 10px; border:1px solid #ddd; border-radius:4px; background:#f8f9fa;">Tuần sau</button>';
    } else {
        $html .= '<button type="button" disabled style="padding:6px 10px; border:1px solid #e5e5e5; border-radius:4px; background:#f3f3f3; color:#aaa; cursor:not-allowed;">Tuần sau</button>';
    }
    $html .= '</div>';

    $html .= '<div style="overflow:auto;">';
    $html .= '<table style="width:100%; border-collapse:collapse; min-width:820px;">';
    $html .= '<thead><tr>';
    $html .= '<th style="border:1px solid #ddd; padding:8px; background:#f6f8fb;">Ca</th>';
    foreach($weekDays as $day)
    {
        $html .= '<th style="border:1px solid #ddd; padding:8px; background:#f6f8fb; text-align:center;">'.$day['label'].'<br><span style="font-weight:400;">'.$day['view'].'</span></th>';
    }
    $html .= '</tr></thead><tbody>';

    for($ca = 1; $ca <= 4; $ca++)
    {
        $slot = $slots[$ca];
        $html .= '<tr>';
        $html .= '<td style="border:1px solid #ddd; padding:8px; font-weight:700;">'.$slot['label'].'<br><span style="font-size:12px; font-weight:400;">'.$slot['gio_b_d'].'-'.$slot['gio_kt'].'</span></td>';

        foreach($weekDays as $day)
        {
            $date = $day['date'];
            $dow = $day['dow'];
            $key = $date.'_'.$ca;
            $count = isset($slotCountMap[$key]) ? (int)$slotCountMap[$key] : 0;
            $already = isset($studentRegMap[$key]);

            $isValidByDay = in_array($ca, cabin_ca_for_dow($dow));
            $inRange = ($date >= $course['ngay_batdau'] && $date <= $course['ngay_ketthuc']);
            $isPast = ($date < $today);
            $isFull = ($count >= $capacity);
            $studentLimitReached = ($studentRegCount >= $maxStudentSlots);
            $canRegister = (!$closed && $isValidByDay && $inRange && !$isPast && !$already && !$isFull && !$studentLimitReached);

            $html .= '<td style="border:1px solid #ddd; padding:8px; text-align:center;">';

            if(!$isValidByDay) {
                $html .= '<span style="color:#999;">-</span>';
            } elseif($already) {
                $html .= '<span style="display:inline-block; padding:4px 8px; border-radius:4px; background:#d4edda; color:#155724; font-size:12px;">Đã đăng ký</span>';
                if(!$closed && !$isPast) {
                    $html .= '<div style="margin-top:6px;"><button type="button" class="btn-cabin-cancel" data-hocvien="'.$student['id'].'" data-ngay="'.$date.'" data-ca="'.$ca.'" data-week-start="'.$weekStart.'" style="padding:4px 10px; border:1px solid #dc3545; border-radius:4px; background:#fff; color:#dc3545; font-size:12px;">Hủy ca này</button></div>';
                }
                $html .= '<div style="font-size:11px; color:#666; margin-top:4px;">'.$count.'/'.$capacity.'</div>';
            } elseif($closed) {
                $html .= '<span style="display:inline-block; padding:4px 8px; border-radius:4px; background:#f8d7da; color:#721c24; font-size:12px;">Đã khóa</span>';
            } elseif(!$inRange) {
                $html .= '<span style="display:inline-block; padding:4px 8px; border-radius:4px; background:#ececec; color:#555; font-size:12px;">Ngoài khóa</span>';
            } elseif($isPast) {
                $html .= '<span style="display:inline-block; padding:4px 8px; border-radius:4px; background:#ececec; color:#555; font-size:12px;">Đã qua</span>';
            } elseif($isFull) {
                $html .= '<span style="display:inline-block; padding:4px 8px; border-radius:4px; background:#f8d7da; color:#721c24; font-size:12px;">Đã đủ</span>';
                $html .= '<div style="font-size:11px; color:#666; margin-top:4px;">'.$count.'/'.$capacity.'</div>';
            } elseif($studentLimitReached) {
                $html .= '<span style="display:inline-block; padding:4px 8px; border-radius:4px; background:#fff3cd; color:#856404; font-size:12px;">Tối đa 1 ca</span>';
                $html .= '<div style="font-size:11px; color:#666; margin-top:4px;">'.$count.'/'.$capacity.'</div>';
            } elseif($canRegister) {
                $btnLabel = ($studentRegCount > 0) ? 'Đăng ký thêm' : 'Đăng ký';
                $html .= '<button type="button" class="btn-cabin-register" data-hocvien="'.$student['id'].'" data-ngay="'.$date.'" data-ca="'.$ca.'" data-week-start="'.$weekStart.'" style="padding:5px 10px; border:1px solid #0a6ebd; border-radius:4px; background:#0a6ebd; color:#fff; font-size:12px;">'.$btnLabel.'</button>';
                $html .= '<div style="font-size:11px; color:#666; margin-top:4px;">'.$count.'/'.$capacity.'</div>';
            }

            $html .= '</td>';
        }

        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$id_khoahoc = isset($_POST['id_khoahoc']) ? (int)$_POST['id_khoahoc'] : 0;
$cccdInput = isset($_POST['cccd']) ? trim($_POST['cccd']) : '';
$weekStart = isset($_POST['week_start']) ? trim($_POST['week_start']) : '';

if($id_khoahoc <= 0) {
    cabin_json(false, 'Vui lòng chọn khóa học cabin.', '<div style="padding:10px; border:1px solid #f5c6cb; background:#ffecec; color:#721c24;">Vui lòng chọn khóa học cabin.</div>');
}

$course = $d->rawQueryOne("select * from #_cabin_khoahoc where id = ? and hienthi = 1 limit 1", array($id_khoahoc));
if(empty($course) || empty($course['id'])) {
    cabin_json(false, 'Khóa học không tồn tại hoặc đã ẩn.', '<div style="padding:10px; border:1px solid #f5c6cb; background:#ffecec; color:#721c24;">Khóa học không tồn tại hoặc đã ẩn.</div>');
}

list($cccd, $cccd2) = cabin_normalize_cccd_pair($cccdInput);

if($action === 'lookup')
{
    if($cccd == '' || (strlen($cccd) < 11 || strlen($cccd) > 12)) {
        cabin_json(false, 'CCCD không hợp lệ.', '<div style="padding:10px; border:1px solid #f5c6cb; background:#ffecec; color:#721c24;">CCCD không hợp lệ (11 hoặc 12 ký tự).</div>');
    }

    $student = $d->rawQueryOne(
        "select * from #_product where type = 'cabin' and id_cabin_khoahoc = ? and hienthi = 1 and (cccd = ? or cccd = ?) limit 1",
        array($id_khoahoc, $cccd, $cccd2)
    );

    if(empty($student) || empty($student['id'])) {
        cabin_json(false, 'Không tìm thấy học viên.', '<div style="padding:10px; border:1px solid #ffeeba; background:#fff8e1; color:#856404;">Không tìm thấy học viên cabin theo CCCD trong khóa đã chọn.</div>');
    }

    $html = cabin_render_panel($d, $student, $course, $weekStart);
    cabin_json(true, '', $html);
}

if($action === 'register')
{
    $maxStudentSlots = 1;
    $id_hocvien = isset($_POST['id_hocvien']) ? (int)$_POST['id_hocvien'] : 0;
    $ngay_hoc = isset($_POST['ngay_hoc']) ? trim($_POST['ngay_hoc']) : '';
    $ca = isset($_POST['ca']) ? (int)$_POST['ca'] : 0;

    if($id_hocvien <= 0 || $ngay_hoc == '' || $ca <= 0) {
        cabin_json(false, 'Thiếu dữ liệu đăng ký.', '<div style="padding:10px; border:1px solid #f5c6cb; background:#ffecec; color:#721c24;">Thiếu dữ liệu đăng ký.</div>');
    }

    $student = $d->rawQueryOne(
        "select * from #_product where id = ? and type = 'cabin' and id_cabin_khoahoc = ? and hienthi = 1 and (cccd = ? or cccd = ?) limit 1",
        array($id_hocvien, $id_khoahoc, $cccd, $cccd2)
    );

    if(empty($student) || empty($student['id'])) {
        cabin_json(false, 'Không xác thực được học viên.', '<div style="padding:10px; border:1px solid #ffeeba; background:#fff8e1; color:#856404;">Không xác thực được học viên trong khóa đã chọn.</div>');
    }

    $today = date('Y-m-d');
    if($today > $course['ngay_ketthuc']) {
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Khóa học đã kết thúc, không thể tự đăng ký.', $html);
    }

    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_hoc) || !cabin_is_valid_slot($ngay_hoc, $ca)) {
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Khung giờ không hợp lệ.', $html);
    }

    if($ngay_hoc < $course['ngay_batdau'] || $ngay_hoc > $course['ngay_ketthuc'] || $ngay_hoc < $today) {
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Ngày học nằm ngoài phạm vi đăng ký hợp lệ.', $html);
    }

    $slotTimes = cabin_slot_times($ca);
    if(empty($slotTimes)) {
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Không tìm thấy cấu hình ca học.', $html);
    }

    $capacity = max(1, (int)$course['suc_chua_ca']);

    $d->startTransaction();
    try {
        $studentRegs = $d->rawQuery(
            "select id, ngay_hoc, ca from #_cabin_dangky where id_khoahoc = ? and id_hocvien = ? order by ngay_hoc asc, ca asc for update",
            array($id_khoahoc, $student['id'])
        );

        $exists = $d->rawQueryOne(
            "select id from #_cabin_dangky where id_khoahoc = ? and id_hocvien = ? and ngay_hoc = ? and ca = ? limit 1 for update",
            array($id_khoahoc, $student['id'], $ngay_hoc, $ca)
        );
        if(!empty($exists['id'])) {
            $d->rollback();
            $html = cabin_render_panel($d, $student, $course, $weekStart);
            cabin_json(false, 'Ca này bạn đã đăng ký trước đó.', $html);
        }

        if(count($studentRegs) >= $maxStudentSlots) {
            $d->rollback();
            $html = cabin_render_panel($d, $student, $course, $weekStart);
            cabin_json(false, 'Mỗi học viên chỉ được đăng ký tối đa 1 ca trong 1 khóa.', $html);
        }

        // Khóa theo ngày + ca toàn hệ thống (không tách khóa học).
        $slotCount = $d->rawQueryOne(
            "select count(*) as total from #_cabin_dangky where ngay_hoc = ? and ca = ? for update",
            array($ngay_hoc, $ca)
        );
        $used = isset($slotCount['total']) ? (int)$slotCount['total'] : 0;

        if($used >= $capacity) {
            $d->rollback();
            $html = cabin_render_panel($d, $student, $course, $weekStart);
            cabin_json(false, 'Ca học đã đủ số lượng đăng ký.', $html);
        }

        $data = array(
            'id_khoahoc' => $id_khoahoc,
            'id_hocvien' => $student['id'],
            'cccd' => $student['cccd'],
            'ngay_hoc' => $ngay_hoc,
            'ca' => $ca,
            'gio_b_d' => $slotTimes['gio_b_d'],
            'gio_kt' => $slotTimes['gio_kt'],
            'trang_thai' => 1,
            'ngaytao' => time()
        );

        $ok = $d->insert('cabin_dangky', $data);
        if(!$ok) {
            $d->rollback();
            $html = cabin_render_panel($d, $student, $course, $weekStart);
            cabin_json(false, 'Không thể lưu đăng ký, vui lòng thử lại.', $html);
        }

        $d->commit();

        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(true, 'Đăng ký ca học thành công.', $html);
    } catch (Exception $e) {
        $d->rollback();
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Có lỗi hệ thống khi đăng ký. Vui lòng thử lại.', $html);
    }
}

if($action === 'cancel')
{
    $id_hocvien = isset($_POST['id_hocvien']) ? (int)$_POST['id_hocvien'] : 0;
    $ngay_hoc = isset($_POST['ngay_hoc']) ? trim($_POST['ngay_hoc']) : '';
    $ca = isset($_POST['ca']) ? (int)$_POST['ca'] : 0;

    if($id_hocvien <= 0 || $ngay_hoc == '' || $ca <= 0) {
        cabin_json(false, 'Thiếu dữ liệu hủy ca.', '<div style="padding:10px; border:1px solid #f5c6cb; background:#ffecec; color:#721c24;">Thiếu dữ liệu hủy ca.</div>');
    }

    $student = $d->rawQueryOne(
        "select * from #_product where id = ? and type = 'cabin' and id_cabin_khoahoc = ? and hienthi = 1 and (cccd = ? or cccd = ?) limit 1",
        array($id_hocvien, $id_khoahoc, $cccd, $cccd2)
    );

    if(empty($student) || empty($student['id'])) {
        cabin_json(false, 'Không xác thực được học viên.', '<div style="padding:10px; border:1px solid #ffeeba; background:#fff8e1; color:#856404;">Không xác thực được học viên trong khóa đã chọn.</div>');
    }

    $today = date('Y-m-d');
    if($today > $course['ngay_ketthuc']) {
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Khóa học đã kết thúc, không thể tự hủy lịch.', $html);
    }

    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_hoc) || !cabin_is_valid_slot($ngay_hoc, $ca)) {
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Khung giờ hủy không hợp lệ.', $html);
    }

    if($ngay_hoc < $today) {
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Không thể hủy ca đã qua.', $html);
    }

    $d->startTransaction();
    try {
        $exists = $d->rawQueryOne(
            "select id from #_cabin_dangky where id_khoahoc = ? and id_hocvien = ? and ngay_hoc = ? and ca = ? limit 1 for update",
            array($id_khoahoc, $student['id'], $ngay_hoc, $ca)
        );

        if(empty($exists['id'])) {
            $d->rollback();
            $html = cabin_render_panel($d, $student, $course, $weekStart);
            cabin_json(false, 'Không tìm thấy ca đã đăng ký để hủy.', $html);
        }

        $d->rawQuery("delete from #_cabin_dangky where id = ?", array((int)$exists['id']));
        $d->commit();

        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(true, 'Hủy ca học thành công. Bạn có thể chọn ca khác.', $html);
    } catch (Exception $e) {
        $d->rollback();
        $html = cabin_render_panel($d, $student, $course, $weekStart);
        cabin_json(false, 'Có lỗi hệ thống khi hủy ca. Vui lòng thử lại.', $html);
    }
}

cabin_json(false, 'Hành động không hợp lệ.', '<div style="padding:10px; border:1px solid #f5c6cb; background:#ffecec; color:#721c24;">Hành động không hợp lệ.</div>');
