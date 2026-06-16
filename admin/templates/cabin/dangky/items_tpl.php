<?php
	require_once LIBRARIES.'cabin_config.php';

	$id_kh = $kh_info['id'];
	$linkFilter = "index.php?com=cabin&act=dangky&id=".$id_kh;
	$linkExport = "index.php?com=cabin&act=exportExcel&id=".$id_kh;
	$linkAdd = "index.php?com=cabin&act=add_dangky&id=".$id_kh;
	$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
	$ngayHoc = isset($ngay_hoc_filter) ? $ngay_hoc_filter : '';
	$ca = isset($ca_filter) ? (int)$ca_filter : 0;
	$slots = cabin_time_slots();
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=cabin&act=man" title="Quản lý khóa học cabin">Quản lý khóa học cabin</a></li>
				<li class="breadcrumb-item active">Đăng ký: <?=htmlspecialchars($kh_info['ten'])?></li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top">
		<a class="btn btn-sm bg-gradient-secondary text-white" href="index.php?com=cabin&act=man" title="Quay lại"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		<a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm đăng ký"><i class="fas fa-plus mr-2"></i>Thêm đăng ký</a>
		<a class="btn btn-sm bg-gradient-success text-white" href="<?=$linkExport?>" title="Xuất Excel"><i class="fas fa-file-excel mr-2"></i>Xuất Excel</a>
	</div>

	<div class="card card-primary card-outline text-sm mb-3">
		<div class="card-header">
			<h3 class="card-title">Lọc danh sách đăng ký</h3>
		</div>
		<div class="card-body">
			<form method="get" action="index.php" class="row">
				<input type="hidden" name="com" value="cabin">
				<input type="hidden" name="act" value="dangky">
				<input type="hidden" name="id" value="<?=$id_kh?>">
				<div class="col-md-4 form-group mb-2">
					<input type="text" class="form-control" name="keyword" placeholder="Tên học viên hoặc CCCD" value="<?=htmlspecialchars($keyword)?>">
				</div>
				<div class="col-md-3 form-group mb-2">
					<input type="date" class="form-control" name="ngay_hoc" value="<?=htmlspecialchars($ngayHoc)?>">
				</div>
				<div class="col-md-3 form-group mb-2">
					<select class="form-control" name="ca">
						<option value="">Tất cả ca</option>
						<?php foreach($slots as $k => $v) { ?>
							<option value="<?=$k?>" <?=($ca === (int)$k ? 'selected' : '')?>><?=$v['label']?> (<?=$v['gio_b_d']?>-<?=$v['gio_kt']?>)</option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-2 form-group mb-2 text-right">
					<button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Lọc</button>
				</div>
			</form>
		</div>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header">
			<h3 class="card-title">Danh sách đăng ký lịch học - <?=htmlspecialchars($kh_info['ten'])?></h3>
		</div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th class="align-middle text-center" width="6%">STT</th>
						<th class="align-middle">Họ tên</th>
						<th class="align-middle" width="14%">CCCD</th>
						<th class="align-middle" width="10%">Người nộp hồ sơ</th>
						<th class="align-middle text-center" width="12%">Ngày học</th>
						<th class="align-middle text-center" width="10%">Ca</th>
						<th class="align-middle text-center" width="16%">Giờ học</th>
						<th class="align-middle" width="12%">Ngày đăng ký</th>
						<th class="align-middle text-center" width="10%">Thao tác</th>
					</tr>
				</thead>
				<?php if(empty($items_dk)) { ?>
					<tbody><tr><td colspan="100" class="text-center">Không có dữ liệu đăng ký</td></tr></tbody>
				<?php } else { ?>
					<tbody>
						<?php foreach($items_dk as $i => $row) { ?>
							<?php
								$caNum = (int)$row['ca'];
								$caText = isset($slots[$caNum]) ? $slots[$caNum]['label'] : ('Ca '.$caNum);
								$gio = trim($row['gio_b_d']).' - '.trim($row['gio_kt']);
							?>
							<tr>
								<td class="align-middle text-center"><?=$i + 1?></td>
								<td class="align-middle"><?=htmlspecialchars($row['tenvi'])?></td>
								<td class="align-middle"><?=htmlspecialchars($row['hv_cccd'])?></td>
								<td class="align-middle"><?=htmlspecialchars($row['hang'])?></td>
								<td class="align-middle text-center"><?=date('d/m/Y', strtotime($row['ngay_hoc']))?></td>
								<td class="align-middle text-center"><?=$caText?></td>
								<td class="align-middle text-center"><?=$gio?></td>
								<td class="align-middle"><?=($row['ngaytao'] > 0) ? date('H:i d/m/Y', $row['ngaytao']) : ''?></td>
								<td class="align-middle text-center text-md text-nowrap">
									<a class="text-primary mr-2" href="index.php?com=cabin&act=edit_dangky&id=<?=(int)$row['id']?>&p=<?=$curPage?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
									<a class="text-danger" id="delete-item" data-url="index.php?com=cabin&act=delete_dangky&id=<?=(int)$row['id']?>&p=<?=$curPage?>" title="Xóa"><i class="fas fa-trash-alt"></i></a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				<?php } ?>
			</table>
		</div>
	</div>

		<div class="card-footer text-sm">
			<a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm đăng ký"><i class="fas fa-plus mr-2"></i>Thêm đăng ký</a>
		</div>

	<?php if($paging_dk) { ?>
		<div class="card-footer text-sm pb-0"><?=$paging_dk?></div>
	<?php } ?>
</section>
