<?php
if(!defined('SOURCES')) die("Error");
$escape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$xdPrevGroup = 0;
foreach($xd_roster_preview['rows'] as $xdPrevRow) if($xdPrevRow['existing'] && $xdPrevRow['existing']['nhom'] !== $xdPrevRow['nhom']) $xdPrevGroup++;
$xdPrevTotal = count($xd_roster_preview['rows']);
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row"><ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=xangdau&act=hocvien">Học viên xăng dầu</a></li>
			<li class="breadcrumb-item active">Xem trước import</li>
		</ol></div>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong>Xem trước import học viên XD</strong> <span class="text-muted">(chưa ghi vào hệ thống)</span></h3></div>
		<div class="card-body">
			<?php $ikStep = 2; $ikHasPreview = true; include __DIR__.'/../../layout/import_steps.php'; ?>
			<div class="ik-sync-stats">
				<div class="ik-sync-stat"><div class="ik-sync-num"><?=$xdPrevTotal?></div><div class="ik-sync-label">Dòng đọc được</div></div>
				<div class="ik-sync-stat is-add"><div class="ik-sync-num"><?=(int)$xd_roster_preview['new']?></div><div class="ik-sync-label">Thêm mới</div></div>
				<div class="ik-sync-stat is-change"><div class="ik-sync-num"><?=(int)$xd_roster_preview['updated']?></div><div class="ik-sync-label">Cập nhật</div></div>
				<div class="ik-sync-stat is-change"><div class="ik-sync-num"><?=(int)($xd_roster_preview['group_changes'] ?? $xdPrevGroup)?></div><div class="ik-sync-label">Đổi nhóm BT/CK/DAT</div></div>
			</div>
			<div class="alert alert-light border py-2"><i class="fas fa-circle-info text-info mr-1"></i>Các khoản tiền là dữ liệu nguồn (VNĐ), không tạo giao dịch hoặc thay đổi số tiền/ngày thanh toán xăng dầu. Hồ sơ đã có giữ nguyên khóa, lịch sử thanh toán và liên kết DAT. Nhóm cập nhật theo Excel kể cả hồ sơ đã thanh toán; thiếu nhóm trong file thì giữ nhóm cũ.</div>
			<?php if(!empty($xd_roster_preview['group_changes'])): ?><div class="alert alert-warning py-2">Sẽ đổi nhóm <?=(int)$xd_roster_preview['group_changes']?> hồ sơ theo Excel. Nhóm mới áp dụng cho tính toán XD về sau; số tiền đã thanh toán không được tính lại.</div><?php endif; ?>
			<?php if($xd_roster_preview['missing_teachers']): ?><div class="alert alert-warning py-2"><?=(int)$xd_roster_preview['missing_teachers']?> dòng không có giáo viên trong file; giữ nguyên giáo viên trên hồ sơ hiện có.</div><?php endif; ?>
			<?php if(!empty($xd_roster_preview['mapping'])): ?>
			<details class="mb-3">
				<summary class="mb-2"><strong>Hệ thống đọc các cột như sau</strong> <span class="text-muted">— bấm để kiểm tra</span></summary>
				<div class="table-responsive"><table class="table table-sm table-bordered ik-mapping mb-0">
					<thead><tr><th>Trường hệ thống</th><th>Cột Excel</th><th>Tiêu đề trong file</th></tr></thead>
					<tbody><?php foreach($xd_roster_preview['mapping'] as $ikMap): ?><tr<?=$ikMap['col'] === '' ? ' class="text-muted"' : ''?>><td><?=$escape($ikMap['label'])?></td><td><?php if($ikMap['col'] !== ''): ?><span class="ik-col-letter"><?=$escape($ikMap['col'])?></span><?php else: ?>—<?php endif; ?></td><td><?=$ikMap['col'] !== '' ? $escape($ikMap['header']) : '<em>Không có trong file</em>'?></td></tr><?php endforeach; ?></tbody>
				</table></div>
			</details>
			<?php endif; ?>
			<div class="ik-tabs" role="tablist" aria-label="Lọc dòng xem trước" data-ik-tabs="xd-roster-preview">
				<button type="button" class="ik-tab is-active" data-filter="all" role="tab" aria-selected="true">Tất cả<span class="badge badge-light"><?=$xdPrevTotal?></span></button>
				<button type="button" class="ik-tab" data-filter="insert" role="tab" aria-selected="false">Thêm mới<span class="badge badge-success"><?=(int)$xd_roster_preview['new']?></span></button>
				<button type="button" class="ik-tab" data-filter="update" role="tab" aria-selected="false">Cập nhật<span class="badge badge-warning"><?=(int)$xd_roster_preview['updated']?></span></button>
				<button type="button" class="ik-tab" data-filter="group" role="tab" aria-selected="false">Đổi nhóm<span class="badge badge-info"><?=$xdPrevGroup?></span></button>
			</div>
			<div class="table-responsive"><table class="table table-sm table-bordered" id="xd-roster-preview">
				<thead><tr><th>Dòng</th><th>Họ tên</th><th>CCCD/CMND</th><th>Khóa XD</th><th>Nhóm</th><th>Ngày nộp hồ sơ</th><th>Thao tác</th><?php foreach(xd_student_financial_fields() as $label): ?><th class="text-right"><?=$escape($label)?></th><?php endforeach; ?></tr></thead>
				<tbody><?php foreach($xd_roster_preview['rows'] as $row): $xdKind = ($row['existing'] ? 'update' : 'insert').($row['existing'] && $row['existing']['nhom'] !== $row['nhom'] ? ' group' : ''); ?><tr data-kind="<?=$xdKind?>">
					<td><?=(int)$row['row']?></td><td><?=$escape($row['ho_ten'])?></td><td><?=$escape($row['cccd'])?></td><td><?=$escape($row['khoa'])?></td><td><?php if($row['existing'] && $row['existing']['nhom'] !== $row['nhom']): ?><span class="badge badge-info"><?=$escape($row['existing']['nhom'])?> → <?=$escape($row['nhom'])?></span><?php else: ?><?=$escape($row['nhom'])?><?php endif; ?></td>
					<td><?=!empty($row['ngay_nop_ho_so']) ? date('d/m/Y', strtotime($row['ngay_nop_ho_so'])) : (!empty($row['existing']['ngay_nop_ho_so']) ? date('d/m/Y', strtotime($row['existing']['ngay_nop_ho_so'])).' (giữ nguyên)' : '')?></td>
					<td><?=$row['existing'] ? '<span class="badge badge-warning">Cập nhật</span>' : '<span class="badge badge-success">Thêm mới</span>'?></td>
					<?php foreach(xd_student_financial_fields() as $key => $label): ?><td class="text-right"><?=isset($row['financial'][$key]) ? number_format($row['financial'][$key], 0, ',', '.') : '<span class="text-muted">Giữ nguyên</span>'?></td><?php endforeach; ?>
				</tr><?php endforeach; ?>
				<tr class="ik-empty-filter" style="display:none;"><td colspan="<?=7 + count(xd_student_financial_fields())?>" class="text-center text-muted p-3">Không có dòng nào thuộc nhóm này</td></tr>
				</tbody>
				<tfoot><tr><th colspan="7">Tổng tiền trong file (VNĐ)</th><?php foreach($xd_roster_preview['totals'] as $total): ?><th class="text-right"><?=number_format($total, 0, ',', '.')?></th><?php endforeach; ?></tr></tfoot>
			</table></div>
		</div>
		<div class="card-footer">
			<form method="post" action="index.php?com=xangdau&act=uploadHocvienExcel" class="ik-confirm-bar">
				<input type="hidden" name="confirm_roster" value="<?=$escape($xd_roster_preview['token'])?>">
				<button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i>Xác nhận import <?=$xdPrevTotal?> dòng</button>
				<span class="ik-file"><i class="far fa-file-excel text-success mr-1"></i><?=$escape($xd_roster_preview['filename'] ?? '')?> — sao lưu trước khi ghi, có thể hoàn tác trong Lịch sử import.</span>
				<a href="index.php?com=xangdau&act=uploadHocvien" class="btn btn-sm btn-outline-secondary ml-auto"><i class="fas fa-upload mr-1"></i>Chọn file khác</a>
			</form>
		</div>
	</div>
</section>
