<?php
/**
 * Cấu hình lịch học cabin dùng chung cho admin, frontend và AJAX.
 *
 * Quy tắc:
 *  - 1 ngày có tối đa 3 ca, mỗi ca 2 tiếng.
 *  - Thứ 2 → Thứ 6: Ca 1 (8-10h), Ca 2 (10-12h), Ca 4 (14-16h).
 *  - Thứ 7 (buổi sáng): chỉ có Ca 1 và Ca 2.
 *  - Chủ nhật: không có ca nào.
 *  - Mỗi ca có tối đa N lịch đăng ký (suc_chua_ca, mặc định 3).
 */

if (!function_exists('cabin_time_slots')) {
    /**
     * Trả về định nghĩa các ca (Ca 3 đã bị bỏ).
     * @return array[ca => ['ca'=>int,'label'=>string,'gio_b_d'=>'HH:MM','gio_kt'=>'HH:MM']]
     */
    function cabin_time_slots()
    {
        return array(
            1 => array('ca' => 1, 'label' => 'Ca 1', 'gio_b_d' => '08:00', 'gio_kt' => '10:00'),
            2 => array('ca' => 2, 'label' => 'Ca 2', 'gio_b_d' => '10:00', 'gio_kt' => '12:00'),
            4 => array('ca' => 4, 'label' => 'Ca 3', 'gio_b_d' => '14:00', 'gio_kt' => '16:00'),
        );
    }
}

if (!function_exists('cabin_ca_for_dow')) {
    /**
     * Danh sách số ca hợp lệ cho 1 thứ trong tuần.
     * @param int $dow Thứ theo chuẩn ISO (date('N')): 1=Thứ 2 ... 6=Thứ 7, 7=Chủ nhật.
     * @return int[] Mảng các số ca hợp lệ.
     */
    function cabin_ca_for_dow($dow)
    {
        $dow = (int) $dow;
        if ($dow >= 1 && $dow <= 5) {
            return array(1, 2, 4); // Thứ 2 - Thứ 6
        }
        if ($dow === 6) {
            return array(1, 2); // Thứ 7 sáng
        }
        return array(); // Chủ nhật
    }
}

if (!function_exists('cabin_is_valid_slot')) {
    /**
     * Kiểm tra (ngày, ca) có phải slot hợp lệ theo quy tắc lịch không.
     * @param string $ngay_hoc 'Y-m-d'
     * @param int    $ca
     * @return bool
     */
    function cabin_is_valid_slot($ngay_hoc, $ca)
    {
        $ts = strtotime($ngay_hoc);
        if ($ts === false) {
            return false;
        }
        $dow = (int) date('N', $ts);
        return in_array((int) $ca, cabin_ca_for_dow($dow), true);
    }
}

if (!function_exists('cabin_slot_times')) {
    /**
     * Lấy giờ bắt đầu/kết thúc của 1 ca.
     * @param int $ca
     * @return array|null ['gio_b_d'=>..,'gio_kt'=>..] hoặc null nếu ca không hợp lệ.
     */
    function cabin_slot_times($ca)
    {
        $slots = cabin_time_slots();
        $ca = (int) $ca;
        if (isset($slots[$ca])) {
            return array('gio_b_d' => $slots[$ca]['gio_b_d'], 'gio_kt' => $slots[$ca]['gio_kt']);
        }
        return null;
    }
}

if (!function_exists('cabin_dow_label')) {
    /**
     * Nhãn tiếng Việt cho thứ trong tuần.
     * @param int $dow date('N')
     * @return string
     */
    function cabin_dow_label($dow)
    {
        $map = array(
            1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5',
            5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật',
        );
        $dow = (int) $dow;
        return isset($map[$dow]) ? $map[$dow] : '';
    }
}

if (!function_exists('cabin_course_deadline_ts')) {
    /**
     * Timestamp hạn đăng ký của khóa (cột han_dangky). 0 nếu không đặt hạn riêng.
     * @param array $course
     * @return int
     */
    function cabin_course_deadline_ts($course)
    {
        if (empty($course['han_dangky'])) {
            return 0;
        }
        $value = trim((string) $course['han_dangky']);
        if ($value === '' || strpos($value, '0000-00-00') === 0) {
            return 0;
        }
        $ts = strtotime($value);
        return ($ts === false) ? 0 : $ts;
    }
}

if (!function_exists('cabin_registration_closed')) {
    /**
     * Khóa học đã đóng đăng ký chưa (quá ngày kết thúc HOẶC quá hạn đăng ký).
     * @param array $course
     * @return bool
     */
    function cabin_registration_closed($course)
    {
        $today = date('Y-m-d');
        $end = isset($course['ngay_ketthuc']) ? $course['ngay_ketthuc'] : $today;
        if ($today > $end) {
            return true;
        }
        $deadline = cabin_course_deadline_ts($course);
        if ($deadline > 0 && time() > $deadline) {
            return true;
        }
        return false;
    }
}
