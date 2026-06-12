<?php
	$id_kh = $kh_info['id'];
	$linkMan = "index.php?com=cabin&act=data&id=".$id_kh."&p=".$curPage;
	$linkEdit = "index.php?com=product&act=edit&type=cabin&p=1";
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
						<th class="align-middle text-center" width="8%">STT</th>
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
									<input type="number" class="form-control form-control-mini m-auto update-stt" min="0" value="<?=$items_data[$i]['stt']?>" data-id="<?=$items_data[$i]['id']?>" data-table="product">
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
									<a class="text-primary mr-2" href="<?=$linkEdit?>&id=<?=$items_data[$i]['id']?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
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
		<a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa đã chọn"><i class="far fa-trash-alt mr-2"></i>Xóa đã chọn</a>
		<a class="btn btn-sm bg-gradient-dark text-white" href="#" id="btn-delete-all-data" title="Xóa toàn bộ dữ liệu"><i class="fas fa-trash mr-2"></i>Xóa toàn bộ</a>
	</div>
</section>

<script type="text/javascript">
$(document).ready(function(){
	$('#btn-delete-all-data').click(function(e){
		e.preventDefault();
		if(confirm('Bạn có chắc chắn muốn xóa toàn bộ học viên cabin của khóa này?')){
			window.location.href = '<?=$linkDeleteAll?>';
		}
	});
});
</script>
