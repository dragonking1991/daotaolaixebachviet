<?php
if(!defined('SOURCES')) die("Error");
$escape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
?>
<section class="content pt-3">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title">Xem trước import học viên XD</h3></div>
		<div class="card-body">
			<p>Thêm mới: <strong><?=(int)$xd_roster_preview['new']?></strong>; cập nhật: <strong><?=(int)$xd_roster_preview['updated']?></strong>. Chưa lưu dữ liệu học viên.</p>
			<div class="alert alert-info">Các khoản tiền dưới đây là dữ liệu nguồn (VNĐ), không tạo giao dịch hoặc thay đổi số tiền/ngày thanh toán xăng dầu. Hồ sơ đã có giữ nguyên khóa, lịch sử thanh toán và liên kết DAT. Nhóm cập nhật theo BT/CK/DAT trong Excel, kể cả hồ sơ đã thanh toán; thiếu nhóm trong file thì giữ nhóm cũ.</div>
			<?php if(!empty($xd_roster_preview['group_changes'])): ?><div class="alert alert-warning">Sẽ đổi nhóm <?=(int)$xd_roster_preview['group_changes']?> hồ sơ theo Excel. Nhóm mới áp dụng cho tính toán XD về sau; số tiền đã thanh toán không được tính lại.</div><?php endif; ?>
			<?php if($xd_roster_preview['missing_teachers']): ?><div class="alert alert-warning"><?=(int)$xd_roster_preview['missing_teachers']?> dòng không có giáo viên trong file; giữ nguyên giáo viên trên hồ sơ hiện có.</div><?php endif; ?>
			<div class="table-responsive"><table class="table table-sm table-bordered">
				<thead><tr><th>Dòng</th><th>Họ tên</th><th>CCCD/CMND</th><th>Khóa XD</th><th>Nhóm</th><th>Ngày nộp hồ sơ</th><th>Thao tác</th><?php foreach(xd_student_financial_fields() as $label): ?><th class="text-right"><?=$escape($label)?></th><?php endforeach; ?></tr></thead>
				<tbody><?php foreach($xd_roster_preview['rows'] as $row): ?><tr>
					<td><?=(int)$row['row']?></td><td><?=$escape($row['ho_ten'])?></td><td><?=$escape($row['cccd'])?></td><td><?=$escape($row['khoa'])?></td><td><?php if($row['existing'] && $row['existing']['nhom'] !== $row['nhom']): ?><?=$escape($row['existing']['nhom'])?> → <?php endif; ?><?=$escape($row['nhom'])?></td>
					<td><?=!empty($row['ngay_nop_ho_so']) ? date('d/m/Y', strtotime($row['ngay_nop_ho_so'])) : (!empty($row['existing']['ngay_nop_ho_so']) ? date('d/m/Y', strtotime($row['existing']['ngay_nop_ho_so'])).' (giữ nguyên)' : '')?></td>
					<td><?=$row['existing'] ? 'Cập nhật' : 'Thêm mới'?></td>
					<?php foreach(xd_student_financial_fields() as $key => $label): ?><td class="text-right"><?=isset($row['financial'][$key]) ? number_format($row['financial'][$key], 0, ',', '.') : 'Giữ nguyên'?></td><?php endforeach; ?>
				</tr><?php endforeach; ?></tbody>
				<tfoot><tr><th colspan="7">Tổng tiền trong file (VNĐ)</th><?php foreach($xd_roster_preview['totals'] as $total): ?><th class="text-right"><?=number_format($total, 0, ',', '.')?></th><?php endforeach; ?></tr></tfoot>
			</table></div>
		</div>
		<div class="card-footer">
			<form method="post" action="index.php?com=xangdau&act=uploadHocvienExcel" class="d-inline">
				<input type="hidden" name="confirm_roster" value="<?=$escape($xd_roster_preview['token'])?>">
				<button type="submit" class="btn btn-sm btn-success">Xác nhận import</button>
			</form>
			<a href="index.php?com=xangdau&act=uploadHocvien" class="btn btn-sm btn-secondary">Chọn lại file</a>
		</div>
	</div>
</section>
