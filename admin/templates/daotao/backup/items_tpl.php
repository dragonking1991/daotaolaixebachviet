<?php
if(!defined('SOURCES')) die("Error");
$rows = isset($dt_backup_rows)?$dt_backup_rows:array();
function dt_backup_size_fmt($b){ $b=(int)$b; if($b<1024) return $b.' B'; if($b<1048576) return number_format($b/1024,1).' KB'; return number_format($b/1048576,2).' MB'; }
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=dashboard">Đào tạo</a></li>
			<li class="breadcrumb-item active">Sao lưu &amp; khôi phục</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-info card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>File sao lưu</strong> <span class="dt-count"><?=number_format(count($rows))?></span></h3>
		</div>
		<div class="card-body p-2">
			<p class="text-muted mb-2"><i class="fas fa-info-circle mr-1"></i>Hệ thống tự sao lưu trước mỗi lần xóa toàn bộ hoặc import. Giữ tối đa 15 file gần nhất. Khôi phục sẽ <b>ghi đè</b> dữ liệu hiện tại (đã tự sao lưu hiện trạng trước khi khôi phục).</p>
		</div>
		<div class="card-body p-0">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Tên file</th><th>Dung lượng</th><th>Thời gian</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
				<?php if(!empty($rows)): foreach($rows as $r): ?>
					<tr>
						<td><div class="dt-title"><i class="fas fa-database mr-1"></i><?=htmlspecialchars($r['name'])?></div></td>
						<td class="dt-sub"><?=dt_backup_size_fmt($r['size'])?></td>
						<td class="dt-sub"><i class="far fa-clock"></i><?=date('d/m/Y H:i', (int)$r['time'])?></td>
						<td class="dt-actions">
							<a href="index.php?com=daotao&act=backupDownload&file=<?=urlencode($r['name'])?>" class="btn btn-xs bg-gradient-secondary" title="Tải về"><i class="fas fa-download"></i></a>
							<form method="post" action="index.php?com=daotao&act=backupRestore" class="dt-restore-form" style="display:inline-block;margin:0">
								<input type="hidden" name="file" value="<?=htmlspecialchars($r['name'])?>">
								<button type="submit" class="btn btn-xs bg-gradient-warning" title="Khôi phục"><i class="fas fa-undo"></i> Khôi phục</button>
							</form>
						</td>
					</tr>
				<?php endforeach; else: ?>
					<tr><td colspan="4"><div class="dt-empty-box"><i class="fas fa-database"></i> Chưa có file sao lưu nào.</div></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
