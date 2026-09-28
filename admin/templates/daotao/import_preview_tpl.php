<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=<?=htmlspecialchars($dtPreview['back'])?>"><?=htmlspecialchars($dtPreview['title'])?></a></li>
			<li class="breadcrumb-item active">Xem trước import</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Xem trước import <?=htmlspecialchars($dtPreview['title'])?></strong> <span class="text-muted">(chưa ghi vào hệ thống)</span></h3>
		</div>
		<div class="card-body">
			<div class="dt-stats mb-3">
				<div class="dt-stat is-blue"><div class="dt-stat-label">Tổng dòng đọc được</div><div class="dt-stat-num"><?=count($dtPreview['rows'])?></div></div>
				<div class="dt-stat is-green"><div class="dt-stat-label">Thêm mới</div><div class="dt-stat-num"><?=$dtPreview['insert']?></div></div>
				<div class="dt-stat is-amber"><div class="dt-stat-label">Cập nhật</div><div class="dt-stat-num"><?=$dtPreview['update']?></div></div>
				<div class="dt-stat is-red"><div class="dt-stat-label">Dòng lỗi</div><div class="dt-stat-num"><?=$dtPreview['err']?></div></div>
			</div>
			<?php if(!empty($dtPreview['errMsgs'])): ?>
			<div class="alert alert-warning py-2"><strong>Một số dòng lỗi:</strong> <?=htmlspecialchars(implode('; ', $dtPreview['errMsgs']))?></div>
			<?php endif; ?>
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Dòng</th><th>Thao tác</th><?php foreach($dtPreview['cols'] as $c): ?><th><?=htmlspecialchars($c)?></th><?php endforeach; ?></tr></thead>
				<tbody>
					<?php foreach($dtPreview['rows'] as $rw): ?>
					<tr>
						<td><?=$rw['r']?></td>
						<td><?php if($rw['action']==='insert'): ?><span class="badge badge-success">Thêm</span><?php elseif($rw['action']==='update'): ?><span class="badge badge-warning">Cập nhật</span><?php else: ?><span class="badge badge-danger">Lỗi</span><?php endif; ?></td>
						<?php foreach($dtPreview['cols'] as $k=>$lbl): ?><td class="dt-sub"><?=htmlspecialchars(isset($rw['data'][$k]) ? $rw['data'][$k] : '')?></td><?php endforeach; ?>
					</tr>
					<?php endforeach; ?>
					<?php if(empty($dtPreview['rows'])): ?><tr><td colspan="<?=count($dtPreview['cols'])+2?>" class="text-center text-muted p-3">Không đọc được dòng dữ liệu nào</td></tr><?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		<div class="card-footer">
			<form method="post" action="<?=htmlspecialchars($dtPreview['submit'])?>" enctype="multipart/form-data" style="display:inline;">
				<?php foreach($dtPreview['hidden'] as $k=>$v): ?><input type="hidden" name="<?=htmlspecialchars($k)?>" value="<?=htmlspecialchars($v)?>"><?php endforeach; ?>
				<div class="alert alert-info py-2 mb-2">Để ghi dữ liệu, vui lòng chọn lại file và bấm “Import thật” (trình duyệt không giữ được file đã chọn).</div>
				<a href="<?=htmlspecialchars($dtPreview['reupload'])?>" class="btn btn-sm bg-gradient-primary"><i class="fas fa-upload mr-1"></i>Chọn file & import thật</a>
				<a href="index.php?com=daotao&act=<?=htmlspecialchars($dtPreview['back'])?>" class="btn btn-sm btn-secondary">Hủy</a>
			</form>
		</div>
	</div>
</section>
