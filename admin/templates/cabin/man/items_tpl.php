<?php
	$linkMan = "index.php?com=cabin&act=man&p=".$curPage;
	$linkDelete = "index.php?com=cabin&act=delete&p=".$curPage;
	$linkSave = "index.php?com=cabin&act=save&p=".$curPage;
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Quản lý khóa học cabin</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top">
		<a class="btn btn-sm bg-gradient-primary text-white" href="#" data-toggle="modal" data-target="#modal-add" title="Tạo mới"><i class="fas fa-plus mr-2"></i>Tạo mới</a>
		<a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa"><i class="far fa-trash-alt mr-2"></i>Xóa</a>
		<div class="form-inline form-search d-inline-block align-middle ml-3">
			<div class="input-group input-group-sm">
				<input class="form-control form-control-navbar text-sm" type="search" id="keyword" placeholder="Tìm theo tên khóa" aria-label="Tìm kiếm" value="<?=(isset($_GET['keyword'])) ? $_GET['keyword'] : ''?>" onkeypress="doEnter(event,'keyword','<?=$linkMan?>')">
				<div class="input-group-append bg-primary rounded-right">
					<button class="btn btn-navbar text-white" type="button" onclick="onSearch('keyword','<?=$linkMan?>')">
						<i class="fas fa-search"></i>
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header">
			<h3 class="card-title">Danh sách khóa học cabin</h3>
		</div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th class="align-middle" width="5%">
							<div class="custom-control custom-checkbox my-checkbox">
								<input type="checkbox" class="custom-control-input" id="selectall-checkbox">
								<label for="selectall-checkbox" class="custom-control-label"></label>
							</div>
						</th>
						<th class="align-middle text-center" width="6%">STT</th>
						<th class="align-middle">Tên khóa</th>
						<th class="align-middle text-center" width="12%">Bắt đầu</th>
						<th class="align-middle text-center" width="12%">Kết thúc</th>
						<th class="align-middle text-center" width="9%">Sức chứa/ca</th>
						<th class="align-middle text-center" width="10%">Học viên</th>
						<th class="align-middle text-center" width="10%">Đăng ký</th>
						<th class="align-middle" width="15%">Thời gian tạo</th>
						<th class="align-middle" width="10%">User tạo</th>
						<th class="align-middle text-center" width="12%">Thao tác</th>
					</tr>
				</thead>
				<?php if(empty($items)) { ?>
					<tbody><tr><td colspan="100" class="text-center">Không có dữ liệu</td></tr></tbody>
				<?php } else { ?>
					<tbody>
						<?php for($i = 0; $i < count($items); $i++) { ?>
							<tr>
								<td class="align-middle">
									<div class="custom-control custom-checkbox my-checkbox">
										<input type="checkbox" class="custom-control-input select-checkbox" id="select-checkbox-<?=$items[$i]['id']?>" value="<?=$items[$i]['id']?>">
										<label for="select-checkbox-<?=$items[$i]['id']?>" class="custom-control-label"></label>
									</div>
								</td>
								<td class="align-middle text-center"><?=$i + 1?></td>
								<td class="align-middle font-weight-bold"><?=htmlspecialchars($items[$i]['ten'])?></td>
								<td class="align-middle text-center"><?=date('d/m/Y', strtotime($items[$i]['ngay_batdau']))?></td>
								<td class="align-middle text-center"><?=date('d/m/Y', strtotime($items[$i]['ngay_ketthuc']))?></td>
								<td class="align-middle text-center"><?=(int)$items[$i]['suc_chua_ca']?></td>
								<td class="align-middle text-center">
									<?php if((int)$items[$i]['so_hoc_vien'] > 0) { ?>
										<a href="index.php?com=cabin&act=data&id=<?=$items[$i]['id']?>" class="badge badge-info" title="Xem học viên"><?=(int)$items[$i]['so_hoc_vien']?> học viên</a>
									<?php } else { ?>
										<span class="badge badge-secondary">0</span>
									<?php } ?>
								</td>
								<td class="align-middle text-center">
									<?php if((int)$items[$i]['so_dang_ky'] > 0) { ?>
										<a href="index.php?com=cabin&act=dangky&id=<?=$items[$i]['id']?>" class="badge badge-success" title="Xem đăng ký"><?=(int)$items[$i]['so_dang_ky']?> lịch</a>
									<?php } else { ?>
										<span class="badge badge-secondary">0</span>
									<?php } ?>
								</td>
								<td class="align-middle"><?=($items[$i]['ngaytao'] > 0) ? date('H:i d-m-Y', $items[$i]['ngaytao']) : ''?></td>
								<td class="align-middle"><?=htmlspecialchars($items[$i]['user_tao'])?></td>
								<td class="align-middle text-center text-md text-nowrap">
									<a class="text-primary mr-2 btn-edit-khoa" href="#"
										data-id="<?=$items[$i]['id']?>"
										data-ten="<?=htmlspecialchars($items[$i]['ten'], ENT_QUOTES)?>"
										data-ngay-batdau="<?=$items[$i]['ngay_batdau']?>"
										data-ngay-ketthuc="<?=$items[$i]['ngay_ketthuc']?>"
										data-suc-chua="<?=(int)$items[$i]['suc_chua_ca']?>"
										title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
									<a class="text-success mr-2" href="index.php?com=cabin&act=upload&id=<?=$items[$i]['id']?>" title="Import học viên"><i class="fas fa-file-upload"></i></a>
									<a class="text-danger" id="delete-item" data-url="<?=$linkDelete?>&id=<?=$items[$i]['id']?>" title="Xóa"><i class="fas fa-trash-alt"></i></a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				<?php } ?>
			</table>
		</div>
	</div>

	<?php if($paging) { ?>
		<div class="card-footer text-sm pb-0"><?=$paging?></div>
	<?php } ?>

	<div class="card-footer text-sm">
		<a class="btn btn-sm bg-gradient-primary text-white" href="#" data-toggle="modal" data-target="#modal-add" title="Tạo mới"><i class="fas fa-plus mr-2"></i>Tạo mới</a>
		<a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa"><i class="far fa-trash-alt mr-2"></i>Xóa</a>
	</div>
</section>

<div class="modal fade" id="modal-add" tabindex="-1" role="dialog" aria-labelledby="modal-add-label" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="post" action="<?=$linkSave?>" class="validation-form" novalidate>
				<div class="modal-header">
					<h5 class="modal-title" id="modal-add-label">Tạo mới khóa học cabin</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="id" id="modal-id" value="0">
					<div class="form-group">
						<label for="ten">Tên khóa <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="data[ten]" id="ten" placeholder="Ví dụ: Khóa cabin tháng 06/2026" required>
					</div>
					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="ngay_batdau">Ngày bắt đầu <span class="text-danger">*</span></label>
							<input type="date" class="form-control" name="data[ngay_batdau]" id="ngay_batdau" required>
						</div>
						<div class="form-group col-md-6">
							<label for="ngay_ketthuc">Ngày kết thúc <span class="text-danger">*</span></label>
							<input type="date" class="form-control" name="data[ngay_ketthuc]" id="ngay_ketthuc" required>
						</div>
					</div>
					<div class="form-group">
						<label for="suc_chua_ca">Sức chứa mỗi ca</label>
						<input type="number" class="form-control" name="data[suc_chua_ca]" id="suc_chua_ca" min="1" value="3">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
					<button type="submit" class="btn btn-primary submit-check">Lưu</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function(){
	$('[data-target="#modal-add"]').click(function(){
		$('#modal-add-label').text('Tạo mới khóa học cabin');
		$('#modal-id').val(0);
		$('#ten').val('');
		$('#ngay_batdau').val('');
		$('#ngay_ketthuc').val('');
		$('#suc_chua_ca').val(3);
	});

	$('.btn-edit-khoa').click(function(e){
		e.preventDefault();
		$('#modal-add-label').text('Chỉnh sửa khóa học cabin');
		$('#modal-id').val($(this).data('id'));
		$('#ten').val($(this).data('ten'));
		$('#ngay_batdau').val($(this).data('ngay-batdau'));
		$('#ngay_ketthuc').val($(this).data('ngay-ketthuc'));
		$('#suc_chua_ca').val($(this).data('suc-chua'));
		$('#modal-add').modal('show');
	});
});
</script>
