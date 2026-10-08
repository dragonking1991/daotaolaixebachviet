<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Ngày kiểm tra</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Các kỳ kiểm tra</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=kythiTracuu" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-search mr-1"></i>Tra cứu kết quả</a></div>
		</div>
		<div class="card-body">
			<form method="post" action="index.php?com=daotao&act=kythiSave" class="form-inline mb-3 p-2" style="background:#f4f6f9;border-radius:4px;">
				<strong class="mr-2">Tạo kỳ kiểm tra:</strong>
				<input type="text" name="ten" class="form-control form-control-sm mr-2" placeholder="Tên kỳ (vd: 121B+200B+VR)" required>
				<input type="date" name="ngay_kt" class="form-control form-control-sm mr-2" required>
				<input type="text" name="ghi_chu" class="form-control form-control-sm mr-2" placeholder="Ghi chú">
				<button class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Tạo</button>
			</form>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Kỳ kiểm tra</th><th>Ngày</th><th class="text-center">Số HV</th><th class="text-center">Có kết quả</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><div class="dt-title"><a href="index.php?com=daotao&act=kythiDetail&id=<?=$it['id']?>"><?=htmlspecialchars($it['ten'] ?: '(Không tên)')?></a></div><?php if(!empty($it['ghi_chu'])): ?><div class="dt-sub"><?=htmlspecialchars($it['ghi_chu'])?></div><?php endif; ?></td>
						<td><?=htmlspecialchars($it['ngay_kt'] ?: '—')?></td>
						<td class="text-center"><span class="badge badge-info"><?=(int)$it['so_hv']?></span></td>
						<td class="text-center"><?php if((int)$it['so_kq']>0): ?><span class="badge badge-success"><?=(int)$it['so_kq']?></span><?php else: ?><span class="badge badge-secondary">0</span><?php endif; ?></td>
						<td class="dt-actions">
							<a href="index.php?com=daotao&act=kythiDetail&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info" title="Chi tiết"><i class="fas fa-list"></i></a>
							<a href="index.php?com=daotao&act=exportKythi&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-success" title="Xuất mẫu TTSH"><i class="fas fa-file-excel"></i></a>
							<a href="index.php?com=daotao&act=kythiUpload&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-warning" title="Import kết quả"><i class="fas fa-upload"></i></a>
							<a href="index.php?com=daotao&act=kythiDelete&id=<?=$it['id']?>" onclick="return confirm('Xóa kỳ kiểm tra này và toàn bộ học viên trong kỳ?')" class="btn btn-xs bg-gradient-danger" title="Xóa"><i class="fas fa-trash"></i></a>
						</td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="text-center text-muted p-3">Chưa có kỳ kiểm tra nào</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
