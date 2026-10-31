<?php
/* Thẻ kết quả cố định sau import/thao tác (thay trang thông báo tự chuyển sau 1 giây). Biến: $ikNotice (mảng session). */
$ikOk = !empty($ikNotice['success']);
$ikIsImport = !empty($ikNotice['steps']);
$ikLinks = isset($ikNotice['links']) && is_array($ikNotice['links']) ? $ikNotice['links'] : array();
$ikLines = preg_split('/\n+/u', trim((string)$ikNotice['message']));
$ikHead = array_shift($ikLines);
$ikMore = count($ikLines);
?>
<div id="dt-import-notice" class="dt-import-notice ik-result<?=$ikIsImport ? ' is-import' : ''?> alert <?=$ikOk ? 'alert-success' : 'alert-danger'?>" role="status" aria-live="polite">
	<i class="fas <?=$ikOk ? 'fa-check-circle' : 'fa-exclamation-circle'?>" aria-hidden="true"></i>
	<div class="dt-import-notice-message">
		<?php if($ikIsImport): ?>
		<div class="ik-result-title"><?=ik_esc($ikNotice['title'] ?? ($ikOk ? 'Import hoàn tất' : 'Import chưa thành công'))?></div>
		<?php $ikStep = 4; $ikFailed = !$ikOk; $ikHasPreview = false; include __DIR__.'/import_steps.php'; ?>
		<?php endif; ?>
		<div class="ik-result-head"><?=ik_esc($ikHead)?></div>
		<?php if($ikMore): ?>
		<ul class="ik-result-lines<?=$ikMore > 6 ? ' is-collapsed' : ''?>"><?php foreach($ikLines as $ikLine): ?><li><?=ik_esc($ikLine)?></li><?php endforeach; ?></ul>
		<?php if($ikMore > 6): ?><button type="button" class="btn btn-link btn-sm p-0 ik-result-toggle" data-more="Xem tất cả <?=$ikMore?> dòng" data-less="Thu gọn">Xem tất cả <?=$ikMore?> dòng</button><?php endif; ?>
		<?php endif; ?>
		<?php if($ikLinks): ?>
		<div class="ik-result-actions">
			<?php foreach($ikLinks as $ikLink): ?>
			<a class="btn btn-sm <?=!empty($ikLink['primary']) ? 'btn-primary' : 'btn-light border'?>" href="<?=ik_esc($ikLink['url'])?>"><i class="fas <?=ik_esc($ikLink['icon'] ?? 'fa-arrow-right')?> mr-1" aria-hidden="true"></i><?=ik_esc($ikLink['label'])?></a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
	<button type="button" class="dt-import-notice-close" aria-label="Đóng thông báo" title="Đóng">&times;</button>
</div>
