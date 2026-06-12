<?php
/**
 * Cấu hình lịch học cabin dùng chung cho admin, frontend và AJAX.
 *
 * Quy tắc:
 *  - 1 ngày có tối đa 4 ca, mỗi ca 2 tiếng.
 *  - Thứ 2 → Thứ 6: đủ 4 ca (8-10h, 10-12h, 12-14h, 14-16h).
 *  - Thứ 7 (buổi sáng): chỉ có Ca 1 và Ca 2.
 *  - Chủ nhật: không có ca nào.
 *  - Mỗi ca có tối đa N lịch đăng ký (suc_chua_ca, mặc định 3).
 */

if (!function_exists('cabin_time_slots')) {
    /**
     * Trả về định nghĩa toàn bộ 4 ca.
     * @return array[ca => ['ca'=>int,'label'=>string,'gio_b_d'=>'HH:MM','gio_kt'=>'HH:MM']]
     */
    function cabin_time_slots()
    {
        return array(
            1 => array('ca' => 1, 'label' => 'Ca 1', 'gio_b_d' => '08:00', 'gio_kt' => '10:00'),
            2 => array('ca' => 2, 'label' => 'Ca 2', 'gio_b_d' => '10:00', 'gio_kt' => '12:00'),
            3 => array('ca' => 3, 'label' => 'Ca 3', 'gio_b_d' => '12:00', 'gio_kt' => '14:00'),
            4 => array('ca' => 4, 'label' => 'Ca 4', 'gio_b_d' => '14:00', 'gio_kt' => '16:00'),
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
            return array(1, 2, 3, 4); // Thứ 2 - Thứ 6
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
