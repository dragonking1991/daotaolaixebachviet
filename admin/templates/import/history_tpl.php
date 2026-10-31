<?php
/* Lịch sử import dùng chung. Biến: $ikScope, $ikHistoryRows, $ikUndoableId, $ikCanUndo, $ikModuleTitles, $ikUndoNote (tùy chọn). */
$ikScopeTitle = $ikScope === 'xangdau' ? 'Xăng dầu' : 'Đào tạo';
$ikStatusBadges = array('done' => array('badge-success', 'Đã ghi'), 'failed' => array('badge-danger', 'Thất bại'), 'undone' => array('badge-secondary', 'Đã hoàn tác'));
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row"><ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=<?=ik_esc($ikScope)?>"><?=ik_esc($ikScopeTitle)?></a></li>
			<li class="breadcrumb-item active">Lịch sử import</li>
		</ol></div>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header d-flex flex-wrap align-items-center" style="gap:.5rem;">
			<h3 class="card-title mb-0"><i class="fas fa-history mr-1" aria-hidden="true"></i>Lịch sử import <?=ik_esc($ikScopeTitle)?></h3>
			<span class="ml-auto text-muted small">150 lượt gần nhất · chỉ lượt import gần nhất còn hiệu lực mới hoàn tác được</span>
		</div>
		<div class="card-body table-responsive p-0">
			<table class="table table-sm table-hover mb-0 ik-history">
				<thead><tr><th>Thời gian</th><th>Loại</th><th>File</th><th>Người import</th><th class="text-right">Thành công</th><th class="text-right">Lỗi</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr></thead>
				<tbody>
				<?php foreach($ikHistoryRows as $ikRow): $ikBadge = $ikStatusBadges[$ikRow['status']] ?? array('badge-light', $ikRow['status']); $ikErrors = json_decode((string)$ikRow['errors'], true); $ikErrorCount = is_array($ikErrors) ? count($ikErrors) : 0; ?>
				<tr>
					<td class="text-nowrap"><?=date('d/m/Y H:i', (int)$ikRow['created'])?></td>
					<td><?=ik_esc($ikModuleTitles[$ikRow['module']] ?? $ikRow['module'])?></td>
					<td style="max-width:260px;" class="text-truncate" title="<?=ik_esc($ikRow['filename'])?>"><i class="far fa-file-excel text-success mr-1" aria-hidden="true"></i><?=ik_esc($ikRow['filename'])?></td>
					<td><?=ik_esc($ikRow['username'])?></td>
					<td class="text-right ik-num"><?=number_format((int)$ikRow['so_them'] + (int)$ikRow['so_capnhat'])?><?php if((int)$ikRow['so_capnhat'] > 0): ?><div class="small text-muted">+<?=(int)$ikRow['so_them']?> mới · <?=(int)$ikRow['so_capnhat']?> cập nhật</div><?php endif; ?></td>
					<td class="text-right ik-num"><?=$ikErrorCount ?: (int)$ikRow['so_loi']?></td>
					<td>
						<span class="badge <?=$ikBadge[0]?>"><?=$ikBadge[1]?></span>
						<?php if($ikRow['status'] === 'undone' && (int)$ikRow['undone_at']): ?><div class="small text-muted"><?=date('d/m H:i', (int)$ikRow['undone_at'])?> · <?=ik_esc($ikRow['undone_by'])?></div><?php endif; ?>
					</td>
					<td class="text-right text-nowrap">
						<?php if($ikRow['status'] === 'done' && $ikRow['link'] !== ''): ?><a class="btn btn-xs btn-outline-primary" href="<?=ik_esc($ikRow['link'])?>" title="Xem dữ liệu của lượt import"><i class="fas fa-eye" aria-hidden="true"></i></a><?php endif; ?>
						<?php if($ikErrorCount): ?><a class="btn btn-xs btn-outline-secondary" href="index.php?com=<?=ik_esc($ikScope)?>&act=importErrors&id=<?=(int)$ikRow['id']?>" title="Tải danh sách lỗi (CSV)"><i class="fas fa-file-csv" aria-hidden="true"></i> <?=$ikErrorCount?></a><?php endif; ?>
						<?php if(!empty($ikCanUndo) && (int)$ikRow['id'] === (int)$ikUndoableId): ?>
						<form method="post" action="index.php?com=<?=ik_esc($ikScope)?>&act=importUndo" class="d-inline" onsubmit="return confirm(<?=ik_esc(json_encode('Hoàn tác lượt import "'.$ikRow['filename'].'"? Dữ liệu các bảng liên quan được khôi phục về ngay trước lúc import; mọi thay đổi khác trên các bảng này sau thời điểm đó cũng bị hoàn tác. Hệ thống sao lưu hiện trạng trước khi khôi phục.'.(!empty($ikUndoNote) ? ' '.$ikUndoNote : ''), JSON_UNESCAPED_UNICODE))?>);">
							<input type="hidden" name="id" value="<?=(int)$ikRow['id']?>">
							<button class="btn btn-xs btn-outline-danger" title="Hoàn tác lượt import này"><i class="fas fa-undo mr-1" aria-hidden="true"></i>Hoàn tác</button>
						</form>
						<?php endif; ?>
					</td>
				</tr>
				<?php if($ikRow['message'] !== '' && $ikRow['status'] === 'failed'): ?>
				<tr class="table-light"><td></td><td colspan="7" class="small text-danger"><?=ik_esc(mb_substr(preg_replace('/\s+/u', ' ', $ikRow['message']), 0, 300))?></td></tr>
				<?php endif; ?>
				<?php endforeach; ?>
				<?php if(empty($ikHistoryRows)): ?><tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox d-block mb-2" style="font-size:28px;color:#cbd5e1;" aria-hidden="true"></i>Chưa có lượt import nào được ghi nhận.</td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
