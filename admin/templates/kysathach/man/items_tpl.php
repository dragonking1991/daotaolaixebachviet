<?php
	$linkMan = "index.php?com=kysathach&act=man&p=".$curPage;
	$linkAdd = "index.php?com=kysathach&act=add&p=".$curPage;
	$linkEdit = "index.php?com=kysathach&act=edit&p=".$curPage;
	$linkDelete = "index.php?com=kysathach&act=delete&p=".$curPage;
	$linkSave = "index.php?com=kysathach&act=save&p=".$curPage;
?>
<!-- Content Header -->
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Quản lý Kỳ Sát Hạch</li>
			</ol>
		</div>
	</div>
</section>

<!-- Main content -->
<section class="content">
	<div class="card-footer text-sm sticky-top">
		<a class="btn btn-sm bg-gradient-primary text-white" href="#" data-toggle="modal" data-target="#modal-add" title="Tạo mới"><i class="fas fa-plus mr-2"></i>Tạo mới</a>
		<a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa"><i class="far fa-trash-alt mr-2"></i>Xóa</a>
		<div class="form-inline form-search d-inline-block align-middle ml-3">
			<div class="input-group input-group-sm">
				<input class="form-control form-control-navbar text-sm" type="search" id="keyword" placeholder="Tìm kiếm" aria-label="Tìm kiếm" value="<?=(isset($_GET['keyword'])) ? $_GET['keyword'] : ''?>" onkeypress="doEnter(event,'keyword','<?=$linkMan?>')">
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
			<h3 class="card-title">Quản lý Kỳ Sát Hạch</h3>
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
						<th class="align-middle text-center" width="5%">STT</th>
						<th class="align-middle" style="width:15%">Ngày sát hạch</th>
						<th class="align-middle" style="width:20%">Tên viết tắt (CSĐT)</th>
						<th class="align-middle" style="width:12%">Loại sát hạch</th>
						<th class="align-middle text-center" style="width:12%">Dữ liệu import</th>
						<th class="align-middle" style="width:15%">Thời gian tạo</th>
						<th class="align-middle" style="width:10%">User tạo</th>
						<th class="align-middle text-center">Thao tác</th>
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
								<td class="align-middle"><?=$items[$i]['ngay_sathach']?></td>
								<td class="align-middle"><?=$items[$i]['ten_viettat']?></td>
								<td class="align-middle"><?=$items[$i]['loai_sathach']?></td>
								<td class="align-middle text-center">
									<?php if($items[$i]['so_ban_ghi'] > 0) { ?>
									<a href="index.php?com=kysathach&act=data&id=<?=$items[$i]['id']?>" class="badge badge-success" title="Xem danh sách"><?=$items[$i]['so_ban_ghi']?> bản ghi</a>
									<?php } else { ?>
										<span class="badge badge-secondary">Chưa có</span>
									<?php } ?>
								</td>
								<td class="align-middle"><?=($items[$i]['ngaytao'] > 0) ? date('H:i d-m-Y', $items[$i]['ngaytao']) : ''?></td>
								<td class="align-middle"><?=$items[$i]['user_tao']?></td>
								<td class="align-middle text-center text-md text-nowrap">
									<a class="text-primary mr-2 btn-edit-ky" href="#" data-id="<?=$items[$i]['id']?>" data-ngay="<?=$items[$i]['ngay_sathach']?>" data-ten="<?=$items[$i]['ten_viettat']?>" data-loai="<?=$items[$i]['loai_sathach']?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
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

<!-- Modal Tạo mới / Chỉnh sửa -->
<div class="modal fade" id="modal-add" tabindex="-1" role="dialog" aria-labelledby="modal-add-label" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="post" action="<?=$linkSave?>" class="validation-form" novalidate>
				<div class="modal-header">
					<h5 class="modal-title" id="modal-add-label">Tạo mới</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="id" id="modal-id" value="0">
					<div class="form-group">
						<label for="ngay_sathach">Ngày sát hạch <span class="text-danger">*</span></label>
						<input type="date" class="form-control" name="data[ngay_sathach]" id="ngay_sathach" required>
					</div>
					<div class="form-group">
						<label for="ten_viettat">Tên viết tắt (CSĐT) <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="data[ten_viettat]" id="ten_viettat" placeholder="Ví dụ: HÓC MÔN, BÁCH VIỆT..." required>
					</div>
					<div class="form-group">
						<label for="loai_sathach">Loại sát hạch <span class="text-danger">*</span></label>
						<select class="form-control" name="data[loai_sathach]" id="loai_sathach" required>
							<option value="">--- Chọn ---</option>
							<option value="Ô TÔ">Ô TÔ</option>
							<option value="XE MÁY">XE MÁY</option>
							<option value="Ô TÔ + XE MÁY">Ô TÔ + XE MÁY</option>
						</select>
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

<!-- Modal Xem dữ liệu import -->
<div class="modal fade" id="modal-data" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modal-data-label">Danh sách dữ liệu import</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-0" id="modal-data-body">
				<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function(){
	// View imported data
	$('.btn-view-data').click(function(e){
		e.preventDefault();
		var id = $(this).data('id');
		$('#modal-data-body').html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');
		$('#modal-data').modal('show');
		$.ajax({
			url: 'index.php?com=kysathach&act=ajaxData',
			type: 'GET',
			data: {id: id},
			success: function(res){
				$('#modal-data-body').html(res);
			},
			error: function(){
				$('#modal-data-body').html('<div class="text-center p-3 text-danger">Lỗi tải dữ liệu</div>');
			}
		});
	});

	// Reset form on modal open for new
	$('[data-target="#modal-add"]').click(function(){
		$('#modal-add-label').text('Tạo mới');
		$('#modal-id').val(0);
		$('#ngay_sathach').val('');
		$('#ten_viettat').val('');
		$('#loai_sathach').val('');
	});

	// Edit button
	$('.btn-edit-ky').click(function(e){
		e.preventDefault();
		$('#modal-add-label').text('Chỉnh sửa');
		$('#modal-id').val($(this).data('id'));
		$('#ngay_sathach').val($(this).data('ngay'));
		$('#ten_viettat').val($(this).data('ten'));
		$('#loai_sathach').val($(this).data('loai'));
		$('#modal-add').modal('show');
	});
});
</script>
