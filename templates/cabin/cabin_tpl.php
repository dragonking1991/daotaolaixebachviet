<div class="w_1000">
    <div class="title-main"><span>Đăng ký ngày học cabin</span></div>

    <div class="content-main" style="margin-bottom:15px;">
        


        <p style="margin-bottom:8px;">Học viên nhập CCCD để tra cứu và đăng ký lịch học cabin theo khóa.</p>
        <p style="margin:0; color:#d35400;">
            <strong>Lưu ý:</strong> 
            <br />
            Học viên phải học đủ 2 giờ/ 1 ca.
            <br />
            Quý học viên vui lòng ăn mặc lịch sự, có mặt trước ca học ít nhất 15 phút để tránh ảnh hưởng đến ca sau.
            <br />
            Lịch sau khi đăng ký sẽ không được hủy, quý học viên vui lòng sắp xếp theo lịch đã đăng ký để bảo đảm tiến độ khóa học.
            <br />
            Sau thời gian diễn ra khóa học cabin, hệ thống đăng ký sẽ bị khóa, nếu học viên học cabin vui lòng liên hệ văn phòng để được xếp lịch cabin.
        </p>
    </div>

    <div class="frm_tracuu" style="padding:14px;">
        <div class="frm_kysathach" style="display:block; margin-bottom:10px;">
            <select id="cabin_khoahoc" style="width:100%; padding:10px; border:1px solid rgba(0,0,0,0.1); border-radius:4px; font-size:14px; height:45px; background:#ffffff;">
                <option value="">--- Chọn khóa học cabin ---</option>
                <?php if(!empty($cabin_courses)) { ?>
                    <?php foreach($cabin_courses as $kh) { ?>
                        <option value="<?=$kh['id']?>"><?=htmlspecialchars($kh['ten'])?> (<?=date('d/m/Y', strtotime($kh['ngay_batdau']))?> - <?=date('d/m/Y', strtotime($kh['ngay_ketthuc']))?>)</option>
                    <?php } ?>
                <?php } ?>
            </select>
        </div>

        <div class="frm_tracuu2">
            <input type="text" placeholder="Nhập số CCCD" id="input_cccd_cabin" maxlength="12">
            <p class="c_tracuu" id="btn_lookup_cabin">Tra cứu</p>
        </div>
    </div>

    <div id="cabin_lookup_result" style="margin-top:15px;"></div>
</div>

<!-- Modal xác nhận đăng ký ca -->
<div id="cabin_confirm_modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; max-width:420px; width:92%; padding:28px 24px 20px; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
        <h3 style="margin:0 0 12px; font-size:17px; color:#2c3e50; border-bottom:1px solid #eee; padding-bottom:10px;">Xác nhận đăng ký ca học</h3>
        <div id="cabin_confirm_info" style="margin:14px 0 20px; font-size:14px; line-height:1.7; color:#333;"></div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button id="cabin_confirm_cancel_btn" style="padding:8px 20px; border:1px solid #ccc; background:#f5f5f5; border-radius:5px; cursor:pointer; font-size:14px;">Hủy</button>
            <button id="cabin_confirm_ok_btn" style="padding:8px 20px; border:none; background:#e74c3c; color:#fff; border-radius:5px; cursor:pointer; font-size:14px; font-weight:600;">Xác nhận đăng ký</button>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function(){
    function cabinLookup(weekStart) {
        var cccd = $.trim($('#input_cccd_cabin').val());
        var idKhoahoc = $('#cabin_khoahoc').val();

        if(cccd === '') {
            alert('Vui lòng nhập số CCCD');
            return false;
        }
        if(cccd.length < 11 || cccd.length > 12) {
            alert('CCCD phải từ 11 đến 12 ký tự');
            return false;
        }
        if(!idKhoahoc) {
            alert('Vui lòng chọn khóa học cabin');
            return false;
        }

        $.ajax({
            url: 'ajax/cabin_dangky.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'lookup',
                cccd: cccd,
                id_khoahoc: idKhoahoc,
                week_start: weekStart || ''
            },
            success: function(res){
                if(res && res.html !== undefined) {
                    $('#cabin_lookup_result').html(res.html);
                } else {
                    $('#cabin_lookup_result').html('<div style="padding:10px; background:#ffecec; border:1px solid #f5c6cb; color:#721c24;">Lỗi dữ liệu trả về.</div>');
                }
            },
            error: function(){
                $('#cabin_lookup_result').html('<div style="padding:10px; background:#ffecec; border:1px solid #f5c6cb; color:#721c24;">Không thể kết nối hệ thống.</div>');
            }
        });

        return false;
    }

    $('body').on('click', '#btn_lookup_cabin', function(){
        cabinLookup('');
        return false;
    });

    $('body').on('keypress', '#input_cccd_cabin', function(e){
        if(e.which === 13) {
            e.preventDefault();
            cabinLookup('');
            return false;
        }
    });

    $('body').on('click', '.btn-cabin-week', function(){
        var weekStart = $(this).data('week-start');
        cabinLookup(weekStart);
        return false;
    });

    $('body').on('click', '.btn-cabin-register', function(){
        var cccd = $.trim($('#input_cccd_cabin').val());
        var idKhoahoc = $('#cabin_khoahoc').val();
        var idHocvien = $(this).data('hocvien');
        var ngayHoc = $(this).data('ngay');
        var ca = $(this).data('ca');
        var weekStart = $(this).data('week-start');

        // Hiện modal xác nhận
        var caLabels = {1: 'Ca 1 (08:00 - 10:00)', 2: 'Ca 2 (10:00 - 12:00)', 3: 'Ca 3 (14:00 - 16:00)', 4: 'Ca 3 (14:00 - 16:00)'};
        var caText = caLabels[ca] || 'Ca ' + ca;
        $('#cabin_confirm_info').html(
            '<p style="margin:0 0 6px;"><strong>Ngày học:</strong> ' + ngayHoc + '</p>' +
            '<p style="margin:0 0 6px;"><strong>Ca học:</strong> ' + caText + '</p>' +
            '<p style="margin:14px 0 0; color:#c0392b; font-size:13px;"><i>Lịch sau khi đăng ký sẽ không được hủy.</i></p>'
        );
        $('#cabin_confirm_modal').css('display', 'flex');

        // Lưu tham số để dùng khi xác nhận
        $('#cabin_confirm_ok_btn').off('click').on('click', function(){
            $('#cabin_confirm_modal').hide();
            $.ajax({
                url: 'ajax/cabin_dangky.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'register',
                    cccd: cccd,
                    id_khoahoc: idKhoahoc,
                    id_hocvien: idHocvien,
                    ngay_hoc: ngayHoc,
                    ca: ca,
                    week_start: weekStart
                },
                success: function(res){
                    if(res && res.html !== undefined) {
                        $('#cabin_lookup_result').html(res.html);
                    }
                    if(res && res.message && !res.success) {
                        alert(res.message);
                    }
                },
                error: function(){
                    alert('Đăng ký thất bại, vui lòng thử lại.');
                }
            });
        });

        return false;
    });

    $('#cabin_confirm_cancel_btn').on('click', function(){
        $('#cabin_confirm_modal').hide();
    });

    // Đóng modal khi click nền
    $('#cabin_confirm_modal').on('click', function(e){
        if($(e.target).is('#cabin_confirm_modal')) {
            $(this).hide();
        }
    });

    $('body').on('click', '.btn-cabin-cancel', function(){
        var cccd = $.trim($('#input_cccd_cabin').val());
        var idKhoahoc = $('#cabin_khoahoc').val();
        var idHocvien = $(this).data('hocvien');
        var ngayHoc = $(this).data('ngay');
        var ca = $(this).data('ca');
        var weekStart = $(this).data('week-start');

        $.ajax({
            url: 'ajax/cabin_dangky.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cancel',
                cccd: cccd,
                id_khoahoc: idKhoahoc,
                id_hocvien: idHocvien,
                ngay_hoc: ngayHoc,
                ca: ca,
                week_start: weekStart
            },
            success: function(res){
                if(res && res.html !== undefined) {
                    $('#cabin_lookup_result').html(res.html);
                }
                if(res && res.message && !res.success) {
                    alert(res.message);
                }
            },
            error: function(){
                alert('Hủy ca thất bại, vui lòng thử lại.');
            }
        });

        return false;
    });
});
</script>
