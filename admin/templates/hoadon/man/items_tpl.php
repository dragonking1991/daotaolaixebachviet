<?php
	$linkMan = "index.php?com=hoadon&act=man";
	$linkDelete = "index.php?com=hoadon&act=delete&p=".$curPage;
	$linkDeleteAll = "index.php?com=hoadon&act=deleteAll";

	$excelColumns = array();
	if(isset($hoadon_excel_columns) && is_array($hoadon_excel_columns) && !empty($hoadon_excel_columns))
	{
		$excelColumns = $hoadon_excel_columns;
	}
	elseif(!empty($items))
	{
		$seenColumns = array();
		for($ci = 0; $ci < count($items); $ci++)
		{
			if(empty($items[$ci]['thong_tin_hoa_don'])) continue;
			$decodedCols = json_decode($items[$ci]['thong_tin_hoa_don'], true);
			if(!is_array($decodedCols)) continue;
			foreach($decodedCols as $k => $v)
			{
				$colName = trim((string)$k);
				if($colName === '' || isset($seenColumns[$colName])) continue;
				$seenColumns[$colName] = 1;
				$excelColumns[] = $colName;
			}
		}
	}

	if(empty($excelColumns))
	{
		$excelColumns = array('Mã số hóa đơn', 'Họ tên người mua hàng', 'Chi tiết hóa đơn', 'Ngày hóa đơn', 'Tổng tiền');
	}
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Quản lý hóa đơn</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top">
		<div class="d-flex flex-wrap align-items-end hoadon-toolbar">
			<div class="mr-3 mb-2">
				<a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa đã chọn"><i class="far fa-trash-alt mr-2"></i>Xóa đã chọn</a>
				<a class="btn btn-sm btn-danger" href="<?=$linkDeleteAll?>" onclick="return confirm('Bạn có chắc muốn xóa toàn bộ hóa đơn?');" title="Xóa toàn bộ"><i class="fas fa-trash-alt mr-2"></i>Xóa toàn bộ</a>
			</div>

			<div class="d-flex flex-wrap align-items-end mb-2 hoadon-filter-wrap">
				<div class="form-group mb-0 mr-2">
					<label class="mb-1 d-block text-secondary font-weight-normal">Từ khóa</label>
					<input class="form-control form-control-sm text-sm" style="min-width: 220px;" type="search" id="keyword" placeholder="Tìm kiếm" value="<?=htmlspecialchars($hoadon_filter_keyword)?>" onkeypress="doEnter(event,'keyword','<?=$linkMan?>')">
				</div>
				<div class="form-group mb-0 mr-2">
					<label class="mb-1 d-block text-secondary font-weight-normal">Từ ngày</label>
					<input class="form-control form-control-sm text-sm" type="date" id="from_date" value="<?=htmlspecialchars($hoadon_filter_from_date)?>">
				</div>
				<div class="form-group mb-0 mr-2">
					<label class="mb-1 d-block text-secondary font-weight-normal">Đến ngày</label>
					<input class="form-control form-control-sm text-sm" type="date" id="to_date" value="<?=htmlspecialchars($hoadon_filter_to_date)?>">
				</div>
				<div class="form-group mb-0 mr-2">
					<label class="mb-1 d-block text-secondary font-weight-normal">Loại hóa đơn</label>
					<select class="form-control form-control-sm text-sm" id="loai_hoa_don">
						<option value="">Tất cả</option>
						<option value="mua_vao" <?=(isset($hoadon_filter_loai) && $hoadon_filter_loai == 'mua_vao') ? 'selected' : ''?>>Mua vào</option>
						<option value="ban_ra" <?=(isset($hoadon_filter_loai) && $hoadon_filter_loai == 'ban_ra') ? 'selected' : ''?>>Bán ra</option>
					</select>
				</div>
				<div class="form-group mb-0">
					<label class="mb-1 d-block text-secondary font-weight-normal">Thao tác</label>
					<div>
						<a class="btn btn-sm bg-gradient-success text-white mr-1" href="#" onclick="filterHoaDon(); return false;" title="Lọc"><i class="fas fa-search mr-1"></i>Lọc</a>
						<a class="btn btn-sm bg-gradient-secondary text-white" href="<?=$linkMan?>" title="Bỏ lọc">Bỏ lọc</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header">
			<h3 class="card-title">Danh sách hóa đơn</h3>
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
						<th class="align-middle">STT</th>
						<th class="align-middle col-loai-hoa-don">Loại</th>
						<?php for($h = 0; $h < count($excelColumns); $h++) { ?>
							<th class="align-middle"><?=htmlspecialchars($excelColumns[$h])?></th>
						<?php } ?>
						<th class="align-middle text-center" width="8%">Thao tác</th>
					</tr>
				</thead>
				<?php if(empty($items)) { ?>
					<tbody><tr><td colspan="100" class="text-center">Không có dữ liệu</td></tr></tbody>
				<?php } else { ?>
					<tbody>
						<?php for($i = 0; $i < count($items); $i++) { ?>
							<?php
								$invoiceInfo = array();
								if(!empty($items[$i]['thong_tin_hoa_don']))
								{
									$decodedInfo = json_decode($items[$i]['thong_tin_hoa_don'], true);
									if(is_array($decodedInfo)) $invoiceInfo = $decodedInfo;
								}

								if(empty($invoiceInfo))
								{
									$invoiceInfo = array(
										'Mã số hóa đơn' => $items[$i]['ma_so_hoa_don'],
										'Họ tên người mua hàng' => $items[$i]['ho_ten_nguoi_mua'],
										'Chi tiết hóa đơn' => $items[$i]['chi_tiet_hoa_don'],
										'Ngày hóa đơn' => $items[$i]['ngay_hoa_don'],
										'Tổng tiền' => $items[$i]['tong_tien']
									);
								}

								$invoiceInfoJson = htmlspecialchars(json_encode($invoiceInfo, JSON_UNESCAPED_UNICODE), ENT_NOQUOTES, 'UTF-8');
							?>
							<tr>
								<td class="align-middle">
									<div class="custom-control custom-checkbox my-checkbox">
										<input type="checkbox" class="custom-control-input select-checkbox" id="select-checkbox-<?=$items[$i]['id']?>" value="<?=$items[$i]['id']?>">
										<label for="select-checkbox-<?=$items[$i]['id']?>" class="custom-control-label"></label>
									</div>
								</td>
								<td class="align-middle"><?=(($curPage - 1) * $per_page) + $i + 1?></td>
								<td class="align-middle col-loai-hoa-don">
									<?php
										if(isset($items[$i]['loai_hoa_don']) && $items[$i]['loai_hoa_don'] == 'mua_vao') echo 'Mua vào';
										elseif(isset($items[$i]['loai_hoa_don']) && $items[$i]['loai_hoa_don'] == 'ban_ra') echo 'Bán ra';
										else echo '-';
									?>
								</td>
								<?php for($c = 0; $c < count($excelColumns); $c++) {
									$colName = $excelColumns[$c];
									$val = isset($invoiceInfo[$colName]) ? (string)$invoiceInfo[$colName] : '';
								?>
									<td class="align-middle" style="white-space: normal; min-width: 180px; max-width: 380px;">
										<?php
											$trimVal = trim($val);
											if($trimVal === '')
											{
												echo '-';
											}
											else
											{
												echo nl2br(htmlspecialchars(mb_substr($trimVal, 0, 180)));
												if(mb_strlen($trimVal) > 180) echo '...';
											}
										?>
									</td>
								<?php } ?>
								<td class="align-middle text-center text-md text-nowrap">
									<textarea class="d-none hoadon-info-data" id="hoadon-info-<?=$items[$i]['id']?>"><?=$invoiceInfoJson?></textarea>
									<a class="btn btn-xs bg-gradient-info text-white mr-2 btn-hoadon-detail" href="#" data-id="<?=$items[$i]['id']?>" title="Xem chi tiết">Chi tiết</a>
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
</section>

<div class="modal fade" id="modal-hoadon-detail" tabindex="-1" role="dialog" aria-labelledby="modal-hoadon-detail-label" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modal-hoadon-detail-label">Chi tiết hóa đơn</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-0">
				<div class="table-responsive">
					<table class="table table-bordered table-striped mb-0 text-sm">
						<tbody id="hoadon-detail-body"></tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
			</div>
		</div>
	</div>
</div>

<style>
.hoadon-toolbar .form-control {
	border-radius: 4px;
}

.col-loai-hoa-don {
	min-width: 130px;
	white-space: nowrap;
}

@media (max-width: 767.98px) {
	.hoadon-filter-wrap {
		width: 100%;
	}

	.hoadon-filter-wrap .form-group {
		width: 100%;
		margin-right: 0 !important;
		margin-bottom: 8px !important;
	}

	.hoadon-filter-wrap .form-group .form-control {
		min-width: 100% !important;
	}
}
</style>

<script type="text/javascript">
function filterHoaDon()
{
	var url = '<?=$linkMan?>';
	var keyword = $('#keyword').val().trim();
	var fromDate = $('#from_date').val();
	var toDate = $('#to_date').val();
	var loaiHoaDon = $('#loai_hoa_don').val();

	if(keyword !== '') url += '&keyword=' + encodeURIComponent(keyword);
	if(fromDate !== '') url += '&from_date=' + encodeURIComponent(fromDate);
	if(toDate !== '') url += '&to_date=' + encodeURIComponent(toDate);
	if(loaiHoaDon !== '') url += '&loai_hoa_don=' + encodeURIComponent(loaiHoaDon);

	window.location.href = url;
}

$(document).ready(function(){
	$('#keyword').on('keypress', function(e){
		if(e.which === 13)
		{
			e.preventDefault();
			filterHoaDon();
		}
	});

	$('#from_date, #to_date, #loai_hoa_don').on('change', function(){
		if($(this).val() !== '') filterHoaDon();
	});

	$('body').on('click', '.btn-hoadon-detail', function(e){
		e.preventDefault();

		var rowId = $(this).data('id');
		var rawJson = $('#hoadon-info-' + rowId).val();
		var info = {};

		try {
			info = JSON.parse(rawJson || '{}');
		} catch(err) {
			info = {};
		}

		var html = '';
		var hasData = false;
		Object.keys(info).forEach(function(key){
			var val = info[key];
			if(val === null || typeof val === 'undefined' || String(val).trim() === '') return;
			hasData = true;
			html += '<tr><th style="width: 32%;">' + $('<div>').text(key).html() + '</th><td>' + $('<div>').text(String(val)).html() + '</td></tr>';
		});

		if(!hasData) html = '<tr><td class="text-center" colspan="2">Không có dữ liệu chi tiết</td></tr>';

		$('#hoadon-detail-body').html(html);
		$('#modal-hoadon-detail').modal('show');
	});
});
</script>
