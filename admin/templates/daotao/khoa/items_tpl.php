<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Khóa đào tạo</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách khóa đào tạo</strong></h3>
			<div class="card-tools">
				<a href="index.php?com=daotao&act=khoaAdd" class="btn btn-sm bg-gradient-success"><i class="fas fa-plus mr-1"></i>Thêm khóa</a>
			</div>
		</div>
		<div class="card-body p-0">
			<table class="table table-bordered table-hover table-sm mb-0">
				<thead>
					<tr>
						<th>Mã khóa</th><th>Tên khóa</th><th>Hạng</th><th>Khai giảng</th><th>Mãn khóa</th><th class="text-center">Số HV</th><th class="text-center" style="width:120px">Thao tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><strong><?=htmlspecialchars($it['ma_khoa'])?></strong></td>
						<td><?=htmlspecialchars($it['ten_khoa'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=$it['ngay_khaigiang'] ? date('d/m/Y', strtotime($it['ngay_khaigiang'])) : ''?></td>
						<td><?=$it['ngay_manhoa'] ? date('d/m/Y', strtotime($it['ngay_manhoa'])) : ''?></td>
						<td class="text-center"><a href="index.php?com=daotao&act=hocvien&id_khoa=<?=$it['id']?>"><?=(int)$it['so_hoc_vien']?></a></td>
						<td class="text-center">
							<a href="index.php?com=daotao&act=khoaEdit&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a>
							<a href="index.php?com=daotao&act=khoaDelete&id=<?=$it['id']?>" onclick="return confirm('Xóa khóa và toàn bộ dữ liệu liên quan?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a>
						</td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="7" class="text-center text-muted p-3">Chưa có khóa nào</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
