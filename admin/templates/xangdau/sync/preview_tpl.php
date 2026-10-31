<?php
if(!defined('SOURCES')) die("Error");
$xdSync = is_array($xd_sync_preview) ? $xd_sync_preview : array();
$xdSyncOk = !empty($xdSync['ok']);
$xdSyncChanges = (int)($xdSync['xd_added'] ?? 0) + (int)($xdSync['xd_course_updated'] ?? 0) + (int)($xdSync['xd_group_updated'] ?? 0);
$xdSyncErrors = $xdSync['errors'] ?? (isset($xdSync['error']) ? array($xdSync['error']) : array());
$xdSyncCards = array(
	array('is-add', (int)($xdSync['xd_added'] ?? 0), 'Học viên đạt DAT sẽ được thêm vào XD'),
	array('is-change', (int)($xdSync['xd_course_updated'] ?? 0), 'Hồ sơ XD sẽ cập nhật mã khóa'),
	array('is-change', (int)($xdSync['xd_group_updated_bt'] ?? 0), 'Hồ sơ đồng bộ cũ đổi DAT → BT'),
	array('is-change', (int)($xdSync['xd_group_updated_ck'] ?? 0), 'Hồ sơ đồng bộ cũ đổi DAT → CK'),
	array('', (int)($xdSync['existing'] ?? 0), 'Hồ sơ đã khớp, giữ nguyên'),
	array('', (int)($xdSync['not_passed'] ?? 0), 'Học viên Đào tạo chưa đạt DAT (bỏ qua)'),
);
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row"><ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=xangdau&act=hocvien">Học viên xăng dầu</a></li>
			<li class="breadcrumb-item active">Đồng bộ HV đạt DAT</li>
		</ol></div>
	</div>
</section>
<section class="content">
	<div class="card card-info card-outline text-sm">
		<div class="card-header d-flex flex-wrap align-items-center" style="gap:.5rem;">
			<h3 class="card-title mb-0"><i class="fas fa-rotate mr-1" aria-hidden="true"></i>Đồng bộ học viên đạt DAT từ Đào tạo — xem trước</h3>
			<?php if(!empty($xd_sync_last)): ?><span class="ml-auto ik-last-sync">Đồng bộ gần nhất: <?=date('H:i d/m/Y', (int)$xd_sync_last['time'])?> · <?=ik_esc($xd_sync_last['user'])?> · <?=$xd_sync_last['ok'] ? 'thành công' : 'có lỗi'?>, thêm <?=(int)$xd_sync_last['added']?> HV</span><?php endif; ?>
		</div>
		<div class="card-body">
			<p class="text-muted mb-3">Hệ thống đã chạy thử toàn bộ quy tắc đồng bộ rồi hoàn tác — <strong>chưa có dữ liệu nào thay đổi</strong>. Kiểm tra số liệu dưới đây rồi bấm <em>Đồng bộ ngay</em>.</p>
			<div class="ik-sync-stats">
				<?php foreach($xdSyncCards as $xdCard): ?>
				<div class="ik-sync-stat <?=$xdCard[0]?>"><div class="ik-sync-num"><?=number_format($xdCard[1])?></div><div class="ik-sync-label"><?=$xdCard[2]?></div></div>
				<?php endforeach; ?>
			</div>
			<?php if($xdSyncErrors): ?>
			<div class="alert alert-danger"><strong>Chạy thử gặp lỗi — đồng bộ thật sẽ bị hoàn tác toàn bộ nếu lỗi lặp lại:</strong><ul class="mb-0 pl-3"><?php foreach(array_slice($xdSyncErrors, 0, 15) as $xdErr): ?><li><?=ik_esc($xdErr)?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
			<details class="mb-0">
				<summary>Quy tắc đồng bộ</summary>
				<ul class="mt-2 mb-0 pl-3 small">
					<li>Lấy học viên đạt DAT (đủ giờ/km hoặc đạt thủ công) ở mọi khóa Đào tạo, kể cả CE.</li>
					<li>B1/B11/C1 vào nhóm BT/CK theo hệ đào tạo; hạng khác và CE vào nhóm DAT.</li>
					<li>Hồ sơ XD đã có (khớp CCCD) giữ nguyên lịch sử thanh toán; chỉ cập nhật mã khóa và sửa nhóm DAT → BT/CK cho hồ sơ đồng bộ cũ đúng liên kết.</li>
					<li>Có lỗi ở bất kỳ dòng nào thì toàn bộ lượt đồng bộ được hoàn tác.</li>
				</ul>
			</details>
		</div>
		<div class="card-footer d-flex flex-wrap align-items-center" style="gap:.5rem;">
			<?php if($xdSyncOk && $xdSyncChanges > 0): ?>
			<a class="btn btn-sm btn-info" href="index.php?com=xangdau&act=syncTrainingDat"><i class="fas fa-rotate mr-1" aria-hidden="true"></i>Đồng bộ ngay (<?=number_format($xdSyncChanges)?> thay đổi)</a>
			<?php elseif($xdSyncOk): ?>
			<span class="text-success"><i class="fas fa-circle-check mr-1" aria-hidden="true"></i>Dữ liệu XD đã khớp với Đào tạo, không cần đồng bộ.</span>
			<?php endif; ?>
			<a class="btn btn-sm btn-outline-secondary" href="index.php?com=xangdau&act=syncTrainingDatPreview"><i class="fas fa-redo mr-1" aria-hidden="true"></i>Chạy thử lại</a>
			<a class="btn btn-sm btn-secondary ml-auto" href="index.php?com=xangdau&act=hocvien">Quay lại danh sách</a>
		</div>
	</div>
</section>
