<?php
/* Thanh tiến trình import 4 bước. Biến: $ikStep (1–4), $ikHasPreview (bool), $ikFailed (bool, tùy chọn). */
$ikStepNow = isset($ikStep) ? (int)$ikStep : 1;
$ikStepLabels = array(
	1 => array('Chọn file', 'Tải file Excel'),
	2 => array(!empty($ikHasPreview) ? 'Xem trước' : 'Kiểm tra', !empty($ikHasPreview) ? 'Đối chiếu trước khi ghi' : 'Hệ thống tự kiểm tra'),
	3 => array('Ghi dữ liệu', 'Sao lưu rồi ghi'),
	4 => array('Kết quả', 'Xem lại & tải lỗi'),
);
?>
<ol class="ik-steps<?=!empty($ikFailed) ? ' is-failed' : ''?>" aria-label="Các bước import">
	<?php foreach($ikStepLabels as $ikNo => $ikLabel): $ikState = $ikNo < $ikStepNow ? 'is-done' : ($ikNo === $ikStepNow ? 'is-current' : ''); ?>
	<li class="ik-step <?=$ikState?>"<?=$ikNo === $ikStepNow ? ' aria-current="step"' : ''?>>
		<span class="ik-step-dot"><?php if($ikNo < $ikStepNow): ?><i class="fas fa-check" aria-hidden="true"></i><?php else: ?><?=$ikNo?><?php endif; ?></span>
		<span class="ik-step-text"><strong><?=$ikLabel[0]?></strong><small><?=$ikLabel[1]?></small></span>
	</li>
	<?php endforeach; ?>
</ol>
