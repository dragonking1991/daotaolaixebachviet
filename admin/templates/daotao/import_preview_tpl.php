<?php if(!defined('SOURCES')) die("Error");
$ikPrevCounts = array('all' => count($dtPreview['rows']), 'insert' => 0, 'update' => 0, 'error' => 0);
foreach($dtPreview['rows'] as $ikPrevRow) if(isset($ikPrevCounts[$ikPrevRow['action']])) $ikPrevCounts[$ikPrevRow['action']]++;
$ikPrevToken = (string)($dtPreview['staged_token'] ?? '');
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row"><ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=<?=htmlspecialchars($dtPreview['back'])?>"><?=htmlspecialchars($dtPreview['title'])?></a></li>
			<li class="breadcrumb-item active">Xem trước import</li>
		</ol></div>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Xem trước import <?=htmlspecialchars($dtPreview['title'])?></strong> <span class="text-muted">(chưa ghi vào hệ thống)</span></h3>
		</div>
		<div class="card-body">
			<?php $ikStep = 2; $ikHasPreview = true; include TEMPLATE.LAYOUT.'import_steps.php'; ?>
			<div class="dt-stats mb-3">
				<div class="dt-stat is-blue"><div class="dt-stat-label">Tổng dòng đọc được</div><div class="dt-stat-num"><?=$ikPrevCounts['all']?></div></div>
				<div class="dt-stat is-green"><div class="dt-stat-label">Thêm mới</div><div class="dt-stat-num"><?=$dtPreview['insert']?></div></div>
				<div class="dt-stat is-amber"><div class="dt-stat-label">Cập nhật</div><div class="dt-stat-num"><?=$dtPreview['update']?></div></div>
				<div class="dt-stat is-red"><div class="dt-stat-label">Dòng lỗi</div><div class="dt-stat-num"><?=$dtPreview['err']?></div></div>
			</div>
			<?php if(!empty($dtPreview['note'])): ?><div class="alert alert-light border py-2"><i class="fas fa-circle-info text-info mr-1"></i><?=htmlspecialchars($dtPreview['note'])?></div><?php endif; ?>
			<?php if(!empty($dtPreview['errMsgs'])): ?>
			<div class="alert alert-warning py-2"><strong>Dòng lỗi (sẽ bị bỏ qua khi import):</strong><ul class="mb-0 pl-3"><?php foreach($dtPreview['errMsgs'] as $ikMsg): ?><li><?=htmlspecialchars($ikMsg)?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
			<?php if(!empty($dtPreview['mapping'])): ?>
			<details class="mb-3" open>
				<summary class="mb-2"><strong>Hệ thống đọc các cột như sau</strong> <span class="text-muted">— kiểm tra trước khi xác nhận</span></summary>
				<div class="table-responsive"><table class="table table-sm table-bordered ik-mapping mb-0">
					<thead><tr><th>Trường hệ thống</th><th>Cột Excel</th><th>Tiêu đề trong file</th></tr></thead>
					<tbody><?php foreach($dtPreview['mapping'] as $ikMap): ?><tr<?=$ikMap['col'] === '' ? ' class="text-muted"' : ''?>>
						<td><?=htmlspecialchars($ikMap['label'])?></td>
						<td><?php if($ikMap['col'] !== ''): ?><span class="ik-col-letter"><?=htmlspecialchars($ikMap['col'])?></span><?php else: ?>—<?php endif; ?></td>
						<td><?=$ikMap['col'] !== '' ? htmlspecialchars($ikMap['header']) : '<em>Không có trong file</em>'?></td>
					</tr><?php endforeach; ?></tbody>
				</table></div>
			</details>
			<?php endif; ?>
			<div class="ik-tabs" role="tablist" aria-label="Lọc dòng xem trước" data-ik-tabs="dt-preview-table">
				<button type="button" class="ik-tab is-active" data-filter="all" role="tab" aria-selected="true">Tất cả<span class="badge badge-light"><?=$ikPrevCounts['all']?></span></button>
				<button type="button" class="ik-tab" data-filter="insert" role="tab" aria-selected="false">Thêm mới<span class="badge badge-success"><?=$ikPrevCounts['insert']?></span></button>
				<button type="button" class="ik-tab" data-filter="update" role="tab" aria-selected="false">Cập nhật<span class="badge badge-warning"><?=$ikPrevCounts['update']?></span></button>
				<button type="button" class="ik-tab" data-filter="error" role="tab" aria-selected="false">Lỗi<span class="badge badge-danger"><?=$ikPrevCounts['error']?></span></button>
			</div>
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0" id="dt-preview-table">
				<thead><tr><th>Dòng</th><th>Thao tác</th><?php foreach($dtPreview['cols'] as $c): ?><th><?=htmlspecialchars($c)?></th><?php endforeach; ?></tr></thead>
				<tbody>
					<?php foreach($dtPreview['rows'] as $rw): ?>
					<tr data-kind="<?=htmlspecialchars($rw['action'])?>">
						<td><?=$rw['r']?></td>
						<td><?php if($rw['action']==='insert'): ?><span class="badge badge-success">Thêm</span><?php elseif($rw['action']==='update'): ?><span class="badge badge-warning">Cập nhật</span><?php else: ?><span class="badge badge-danger">Lỗi</span><?php endif; ?></td>
						<?php foreach($dtPreview['cols'] as $k=>$lbl): ?><td class="dt-sub"><?=htmlspecialchars(isset($rw['data'][$k]) ? $rw['data'][$k] : '')?></td><?php endforeach; ?>
					</tr>
					<?php endforeach; ?>
					<tr class="ik-empty-filter" style="display:none;"><td colspan="<?=count($dtPreview['cols'])+2?>" class="text-center text-muted p-3">Không có dòng nào thuộc nhóm này</td></tr>
					<?php if(empty($dtPreview['rows'])): ?><tr><td colspan="<?=count($dtPreview['cols'])+2?>" class="text-center text-muted p-3">Không đọc được dòng dữ liệu nào</td></tr><?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		<div class="card-footer">
			<?php if($ikPrevToken !== '' && ($dtPreview['insert'] + $dtPreview['update']) > 0): ?>
			<form method="post" action="<?=htmlspecialchars($dtPreview['submit'])?>" class="ik-confirm-bar">
				<input type="hidden" name="staged_token" value="<?=htmlspecialchars($ikPrevToken)?>">
				<?php foreach($dtPreview['hidden'] as $k=>$v): ?><input type="hidden" name="<?=htmlspecialchars($k)?>" value="<?=htmlspecialchars($v)?>"><?php endforeach; ?>
				<button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i>Xác nhận import <?=($dtPreview['insert'] + $dtPreview['update'])?> dòng</button>
				<span class="ik-file"><i class="far fa-file-excel text-success mr-1"></i><?=htmlspecialchars($dtPreview['filename'] ?? '')?> — không cần chọn lại file<?=$dtPreview['err'] ? '; '.$dtPreview['err'].' dòng lỗi sẽ được bỏ qua' : ''?>.</span>
				<a href="<?=htmlspecialchars($dtPreview['reupload'])?>" class="btn btn-sm btn-outline-secondary ml-auto"><i class="fas fa-upload mr-1"></i>Chọn file khác</a>
				<a href="index.php?com=daotao&act=<?=htmlspecialchars($dtPreview['back'])?>" class="btn btn-sm btn-secondary">Hủy</a>
			</form>
			<?php else: ?>
			<div class="alert alert-info py-2 mb-2"><?=$ikPrevToken === '' ? 'Không giữ được file đã chọn; vui lòng chọn lại file và bấm “Import”.' : 'Không có dòng hợp lệ để import. Sửa file rồi tải lên lại.'?></div>
			<a href="<?=htmlspecialchars($dtPreview['reupload'])?>" class="btn btn-sm bg-gradient-primary"><i class="fas fa-upload mr-1"></i>Chọn file khác</a>
			<a href="index.php?com=daotao&act=<?=htmlspecialchars($dtPreview['back'])?>" class="btn btn-sm btn-secondary">Hủy</a>
			<?php endif; ?>
		</div>
	</div>
</section>
