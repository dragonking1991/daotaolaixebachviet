<div class="w_1000">
    <div class="title-main"><span>Đăng ký ngày học cabin</span></div>

    <div class="content-main" style="margin-bottom:15px;">
        <p style="margin-bottom:8px;">Học viên nhập CCCD để tra cứu và đăng ký lịch học cabin theo tuần.</p>
        <p style="margin:0; color:#d35400;"><strong>Lưu ý:</strong> Học viên có mặt trước giờ học ít nhất 15 phút để điểm danh.</p>
        <p style="margin:6px 0 0 0; color:#c0392b;"><strong>Nếu hệ thống đã khóa đăng ký:</strong> vui lòng liên hệ văn phòng để được hỗ trợ sắp lịch.</p>
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

        return false;
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
