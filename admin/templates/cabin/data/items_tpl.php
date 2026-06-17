<?php
	$id_kh = $kh_info['id'];
	$linkMan = "index.php?com=cabin&act=data&id=".$id_kh."&p=".$curPage;
	$linkEdit = "index.php?com=product&act=edit&type=cabin&p=1";
	$linkSave = "index.php?com=cabin&act=saveData&p=".$curPage;
	$linkDelete = "index.php?com=cabin&act=deleteData&id_kh=".$id_kh."&p=".$curPage;
	$linkDeleteAll = "index.php?com=cabin&act=deleteAllData&id_kh=".$id_kh;
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=cabin&act=man" title="Quản lý khóa học cabin">Quản lý khóa học cabin</a></li>
				<li class="breadcrumb-item active">Học viên: <?=htmlspecialchars($kh_info['ten'])?></li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top">
		<a class="btn btn-sm bg-gradient-secondary text-white" href="index.php?com=cabin&act=man" title="Quay lại"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		<a class="btn btn-sm bg-gradient-primary text-white" href="#" data-toggle="modal" data-target="#modal-student" title="Thêm học viên"><i class="fas fa-plus mr-2"></i>Thêm học viên</a>
		<a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa đã chọn"><i class="far fa-trash-alt mr-2"></i>Xóa đã chọn</a>
		<a class="btn btn-sm bg-gradient-dark text-white" href="#" id="btn-delete-all-data" title="Xóa toàn bộ dữ liệu"><i class="fas fa-trash mr-2"></i>Xóa toàn bộ</a>
		<div class="form-inline form-search d-inline-block align-middle ml-3">
			<div class="input-group input-group-sm">
				<input class="form-control form-control-navbar text-sm" type="search" id="keyword" placeholder="Tìm theo tên/CCCD" aria-label="Tìm kiếm" value="<?=(isset($_GET['keyword'])) ? $_GET['keyword'] : ''?>" onkeypress="doEnter(event,'keyword','<?=$linkMan?>')">
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
			<h3 class="card-title">Danh sách học viên cabin - <?=htmlspecialchars($kh_info['ten'])?></h3>
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
						<th class="align-middle">Họ tên</th>
						<th class="align-middle" width="14%">Ngày sinh</th>
						<th class="align-middle" width="16%">CCCD</th>
						<th class="align-middle" width="10%">Người nộp hồ sơ</th>
						<th class="align-middle text-center" width="8%">Hiển thị</th>
						<th class="align-middle text-center" width="10%">Thao tác</th>
					</tr>
				</thead>
				<?php if(empty($items_data)) { ?>
					<tbody><tr><td colspan="100" class="text-center">Không có dữ liệu</td></tr></tbody>
				<?php } else { ?>
					<tbody>
						<?php for($i = 0; $i < count($items_data); $i++) { ?>
							<tr>
								<td class="align-middle">
									<div class="custom-control custom-checkbox my-checkbox">
										<input type="checkbox" class="custom-control-input select-checkbox" id="select-checkbox-<?=$items_data[$i]['id']?>" value="<?=$items_data[$i]['id']?>">
										<label for="select-checkbox-<?=$items_data[$i]['id']?>" class="custom-control-label"></label>
									</div>
								</td>
								<td class="align-middle">
									<a class="text-dark" href="<?=$linkEdit?>&id=<?=$items_data[$i]['id']?>" title="<?=htmlspecialchars($items_data[$i]['tenvi'])?>"><?=htmlspecialchars($items_data[$i]['tenvi'])?></a>
								</td>
								<td class="align-middle"><?=htmlspecialchars($items_data[$i]['ngaysinh'])?></td>
								<td class="align-middle"><?=htmlspecialchars($items_data[$i]['cccd'])?></td>
								<td class="align-middle"><?=htmlspecialchars($items_data[$i]['hang'])?></td>
								<td class="align-middle text-center">
									<div class="custom-control custom-checkbox my-checkbox">
										<input type="checkbox" class="custom-control-input show-checkbox" id="show-checkbox-<?=$items_data[$i]['id']?>" data-table="product" data-id="<?=$items_data[$i]['id']?>" data-loai="hienthi" <?=($items_data[$i]['hienthi']) ? 'checked' : ''?>>
										<label for="show-checkbox-<?=$items_data[$i]['id']?>" class="custom-control-label"></label>
									</div>
								</td>
								<td class="align-middle text-center text-md text-nowrap">
									<a class="text-primary mr-2 btn-edit-student" href="#"
										data-id="<?=$items_data[$i]['id']?>"
										data-ten="<?=htmlspecialchars($items_data[$i]['tenvi'], ENT_QUOTES)?>"
										data-ngaysinh="<?=htmlspecialchars($items_data[$i]['ngaysinh'], ENT_QUOTES)?>"
										data-cccd="<?=htmlspecialchars($items_data[$i]['cccd'], ENT_QUOTES)?>"
										data-hang="<?=htmlspecialchars($items_data[$i]['hang'], ENT_QUOTES)?>"
										data-hienthi="<?=(int)$items_data[$i]['hienthi']?>"
										title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
									<a class="text-danger" id="delete-item" data-url="<?=$linkDelete?>&id=<?=$items_data[$i]['id']?>" title="Xóa"><i class="fas fa-trash-alt"></i></a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				<?php } ?>
			</table>
		</div>
	</div>

	<?php if($paging_data) { ?>
		<div class="card-footer text-sm pb-0"><?=$paging_data?></div>
	<?php } ?>

	<div class="card-footer text-sm">
		<a class="btn btn-sm bg-gradient-secondary text-white" href="index.php?com=cabin&act=man" title="Quay lại"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		<a class="btn btn-sm bg-gradient-primary text-white" href="#" data-toggle="modal" data-target="#modal-student" title="Thêm học viên"><i class="fas fa-plus mr-2"></i>Thêm học viên</a>
		<a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa đã chọn"><i class="far fa-trash-alt mr-2"></i>Xóa đã chọn</a>
		<a class="btn btn-sm bg-gradient-dark text-white" href="#" id="btn-delete-all-data" title="Xóa toàn bộ dữ liệu"><i class="fas fa-trash mr-2"></i>Xóa toàn bộ</a>
	</div>
</section>

<div class="modal fade" id="modal-student" tabindex="-1" role="dialog" aria-labelledby="modal-student-label" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="post" action="<?=$linkSave?>" class="validation-form" novalidate>
				<div class="modal-header">
					<h5 class="modal-title" id="modal-student-label">Thêm học viên cabin</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="id" id="student-id" value="0">
					<input type="hidden" name="id_kh" value="<?=$id_kh?>">

					<div class="form-group">
						<label for="student-ten">Họ tên <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="data[tenvi]" id="student-ten" required>
					</div>
					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="student-ngaysinh">Ngày sinh</label>
							<input type="text" class="form-control" name="data[ngaysinh]" id="student-ngaysinh" placeholder="dd/mm/yyyy">
						</div>
						<div class="form-group col-md-6">
							<label for="student-cccd">CCCD <span class="text-danger">*</span></label>
							<input type="text" class="form-control" name="data[cccd]" id="student-cccd" maxlength="12" required>
						</div>
					</div>
					<div class="form-group">
						<label for="student-hang">Người nộp hồ sơ</label>
						<input type="text" class="form-control" name="data[hang]" id="student-hang">
					</div>
					<div class="form-group mb-0">
						<div class="custom-control custom-checkbox my-checkbox">
							<input type="checkbox" class="custom-control-input" name="data[hienthi]" id="student-hienthi" checked>
							<label for="student-hienthi" class="custom-control-label">Hiển thị</label>
						</div>
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
	$('[data-target="#modal-student"]').on('click', function(){
		$('#modal-student-label').text('Thêm học viên cabin');
		$('#student-id').val(0);
		$('#student-ten').val('');
		$('#student-ngaysinh').val('');
		$('#student-cccd').val('');
		$('#student-hang').val('');
		$('#student-hienthi').prop('checked', true);
	});

	$('body').on('click', '.btn-edit-student', function(e){
		e.preventDefault();
		$('#modal-student-label').text('Chỉnh sửa học viên cabin');
		$('#student-id').val($(this).data('id'));
		$('#student-ten').val($(this).data('ten'));
		$('#student-ngaysinh').val($(this).data('ngaysinh'));
		$('#student-cccd').val($(this).data('cccd'));
		$('#student-hang').val($(this).data('hang'));
		$('#student-hienthi').prop('checked', parseInt($(this).data('hienthi'), 10) === 1);
		$('#modal-student').modal('show');
	});

	$('#btn-delete-all-data').click(function(e){
		e.preventDefault();
		if(confirm('Bạn có chắc chắn muốn xóa toàn bộ học viên cabin của khóa này?')){
			window.location.href = '<?=$linkDeleteAll?>';
		}
	});
});
</script>
