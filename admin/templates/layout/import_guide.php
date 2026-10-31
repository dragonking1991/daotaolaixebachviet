<?php
/* Hướng dẫn cột + nút tải file mẫu + lịch sử. Biến: $ikKey (vd 'daotao.hocvien'), $ikExtraHtml (tùy chọn, đã escape). */
$ikGuideSpec = isset($ikKey) ? ik_spec($ikKey) : null;
if($ikGuideSpec):
	list($ikScope, $ikType) = explode('.', $ikKey, 2);
	$ikGuideId = 'ik-guide-'.preg_replace('/[^a-z0-9]+/', '-', $ikKey);
?>
<div class="ik-guide">
	<div class="ik-guide-bar">
		<div class="ik-guide-summary">
			<i class="fas fa-list-check" aria-hidden="true"></i>
			<span><strong>Cột bắt buộc:</strong> <?=ik_esc(implode(' · ', array_keys($ikGuideSpec['required'])))?></span>
		</div>
		<div class="ik-guide-actions">
			<?php if(!empty($ikGuideSpec['sample'])): ?>
			<a class="btn btn-sm btn-outline-success" href="index.php?com=<?=ik_esc($ikScope)?>&act=importTemplate&type=<?=ik_esc($ikType)?>"><i class="fas fa-file-download mr-1" aria-hidden="true"></i>Tải file mẫu</a>
			<?php endif; ?>
			<button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#<?=$ikGuideId?>" aria-expanded="false" aria-controls="<?=$ikGuideId?>"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>Xem quy tắc cột</button>
			<a class="btn btn-sm btn-link" href="index.php?com=<?=ik_esc($ikScope)?>&act=importHistory"><i class="fas fa-history mr-1" aria-hidden="true"></i>Lịch sử import</a>
		</div>
	</div>
	<div class="collapse" id="<?=$ikGuideId?>">
		<div class="ik-guide-body">
			<?php if(!empty($ikGuideSpec['system_export'])): ?><p class="mb-2"><i class="fas fa-circle-info text-info mr-1"></i>Dùng file xuất trực tiếp từ <strong><?=ik_esc($ikGuideSpec['system_export'])?></strong>, không cần sửa tiêu đề.</p><?php endif; ?>
			<div class="ik-guide-cols">
				<div>
					<div class="ik-guide-heading is-required">Bắt buộc</div>
					<ul><?php foreach($ikGuideSpec['required'] as $ikHeader => $ikDesc): ?><li><strong><?=ik_esc($ikHeader)?></strong><?=$ikDesc !== '' ? ' — '.ik_esc($ikDesc) : ''?></li><?php endforeach; ?></ul>
				</div>
				<?php if(!empty($ikGuideSpec['optional'])): ?>
				<div>
					<div class="ik-guide-heading">Tùy chọn</div>
					<ul><?php foreach($ikGuideSpec['optional'] as $ikHeader => $ikDesc): ?><li><?=ik_esc($ikHeader)?><?=$ikDesc !== '' ? ' <span class="text-muted">— '.ik_esc($ikDesc).'</span>' : ''?></li><?php endforeach; ?></ul>
				</div>
				<?php endif; ?>
			</div>
			<?php if(!empty($ikGuideSpec['notes'])): ?><ul class="ik-guide-notes"><?php foreach($ikGuideSpec['notes'] as $ikNote): ?><li><?=ik_esc($ikNote)?></li><?php endforeach; ?></ul><?php endif; ?>
			<?php if(!empty($ikExtraHtml)): ?><div class="ik-guide-extra"><?=$ikExtraHtml?></div><?php endif; ?>
			<p class="small text-muted mb-0">Ngày theo dd/mm/yyyy; ngày không tồn tại (vd 31/02) sẽ bị báo lỗi theo dòng. Định dạng ô CCCD là Text để giữ số 0 đầu. Hệ thống sao lưu trước khi ghi và có thể hoàn tác lượt import gần nhất trong Lịch sử import.</p>
		</div>
	</div>
</div>
<?php endif; ?>
