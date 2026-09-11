<?php
	$linkMan = "index.php?com=xangdau&act=hoadon";
	$linkUpload = "index.php?com=xangdau&act=uploadHoadon";
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Hóa đơn xăng dầu</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top bg-light border-bottom py-3">
		<form method="get" action="index.php" class="d-flex flex-wrap align-items-end" style="gap:.75rem;">
			<input type="hidden" name="com" value="xangdau">
			<input type="hidden" name="act" value="hoadon">
			<a class="btn btn-sm bg-gradient-success text-white mr-2" href="<?=$linkUpload?>"><i class="fas fa-upload mr-1"></i>Import hóa đơn</a>
			<a class="btn btn-sm bg-gradient-primary text-white mr-2" href="index.php?com=xangdau&act=loc"><i class="fas fa-filter mr-1"></i>Lọc thanh toán</a>
			<?php if(xd_can_duyet() || xd_can_kiem_tra()) { $exportBase = 'index.php?com=xangdau&act=xuatHoadonExcel'; $exportUrlAll = $exportBase.'&scope=all'; $exportUrlPaid = $exportBase.'&scope=paid'; $exportUrlUnpaid = $exportBase.'&scope=unpaid'; if($xd_filter_keyword !== '') { $exportUrlAll .= '&keyword='.urlencode($xd_filter_keyword); $exportUrlPaid .= '&keyword='.urlencode($xd_filter_keyword); $exportUrlUnpaid .= '&keyword='.urlencode($xd_filter_keyword); } if($xd_filter_from !== '') { $exportUrlAll .= '&from_date='.urlencode($xd_filter_from); $exportUrlPaid .= '&from_date='.urlencode($xd_filter_from); $exportUrlUnpaid .= '&from_date='.urlencode($xd_filter_from); } if($xd_filter_to !== '') { $exportUrlAll .= '&to_date='.urlencode($xd_filter_to); $exportUrlPaid .= '&to_date='.urlencode($xd_filter_to); $exportUrlUnpaid .= '&to_date='.urlencode($xd_filter_to); } if($xd_filter_ky !== '') { $exportUrlAll .= '&ky='.urlencode($xd_filter_ky); $exportUrlPaid .= '&ky='.urlencode($xd_filter_ky); $exportUrlUnpaid .= '&ky='.urlencode($xd_filter_ky); } if($xd_filter_kt_from !== '') { $exportUrlAll .= '&kt_from='.urlencode($xd_filter_kt_from); $exportUrlPaid .= '&kt_from='.urlencode($xd_filter_kt_from); $exportUrlUnpaid .= '&kt_from='.urlencode($xd_filter_kt_from); } if($xd_filter_kt_to !== '') { $exportUrlAll .= '&kt_to='.urlencode($xd_filter_kt_to); $exportUrlPaid .= '&kt_to='.urlencode($xd_filter_kt_to); $exportUrlUnpaid .= '&kt_to='.urlencode($xd_filter_kt_to); } ?>
			<div class="btn-group mr-3">
				<button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</button>
				<div class="dropdown-menu">
					<a class="dropdown-item" href="<?=$exportUrlAll?>">Tổng</a>
					<a class="dropdown-item" href="<?=$exportUrlPaid?>">Đã thanh toán</a>
					<a class="dropdown-item" href="<?=$exportUrlUnpaid?>">Chưa thanh toán</a>
				</div>
			</div>
			<?php } ?>
			<?php if(xd_can_xoa()) { ?><a class="btn btn-sm btn-danger mr-3" href="index.php?com=xangdau&act=deleteAllHoadon" onclick="return confirm('Xóa TOÀN BỘ hóa đơn, bao gồm cả hóa đơn đã quyết toán? Dữ liệu đã xóa không thể khôi phục.');"><i class="fas fa-trash-alt mr-1"></i>Xóa toàn bộ</a><?php } ?>
			<div class="w-100"></div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Tìm kiếm</label>
				<input class="form-control form-control-sm text-sm" style="min-width:200px;" type="search" name="keyword" placeholder="Mã HĐ / Tên GV" value="<?=htmlspecialchars($xd_filter_keyword)?>">
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Ngày HĐ từ</label>
				<input class="form-control form-control-sm text-sm" type="date" name="from_date" value="<?=htmlspecialchars($xd_filter_from)?>">
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Ngày HĐ đến</label>
				<input class="form-control form-control-sm text-sm" type="date" name="to_date" value="<?=htmlspecialchars($xd_filter_to)?>">
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start">
				<label class="mb-1 small text-muted">Kỳ</label>
				<input class="form-control form-control-sm text-sm" type="text" name="ky" placeholder="Kỳ (VD: T5)" value="<?=htmlspecialchars($xd_filter_ky)?>" style="max-width:120px;">
			</div>
			<div class="form-group mb-0 d-flex flex-column align-items-start"><label class="mb-1 small text-muted">KT kế toán từ</label><input class="form-control form-control-sm text-sm" type="date" name="kt_from" value="<?=htmlspecialchars($xd_filter_kt_from)?>"></div>
			<div class="form-group mb-0 d-flex flex-column align-items-start"><label class="mb-1 small text-muted">KT kế toán đến</label><input class="form-control form-control-sm text-sm" type="date" name="kt_to" value="<?=htmlspecialchars($xd_filter_kt_to)?>"></div>
			<button type="submit" class="btn btn-sm bg-gradient-success text-white mr-1"><i class="fas fa-search mr-1"></i>Lọc</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="<?=$linkMan?>">Bỏ lọc</a>
		</form>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header"><h3 class="card-title">Danh sách hóa đơn xăng dầu</h3></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Mã HĐ</th>
						<th>Ngày HĐ</th>
						<th>Thông tin bán hàng</th>
						<th>Chi tiết</th>
						<th class="text-right">Số tiền HĐ</th>
						<th>Biển số</th>
						<th>Giáo viên</th>
						<th>Kỳ</th>
						<th>Hợp lệ</th>
						<th>Note 1</th>
						<th>Ngày thanh toán</th>
						<th>KT kế toán</th>
						<th>Trạng thái</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)) { $i = 0; foreach($items as $it) { $i++; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($it['ma_hoa_don'])?></td>
						<td><?=($it['ngay_hoa_don'] ? date('d/m/Y', strtotime($it['ngay_hoa_don'])) : '-')?></td>
						<td><?=htmlspecialchars(isset($it['thong_tin_ban_hang']) ? $it['thong_tin_ban_hang'] : '')?></td>
						<td><?=htmlspecialchars(isset($it['chi_tiet']) ? $it['chi_tiet'] : '')?></td>
						<td class="text-right"><?=number_format((float)$it['tong_tien'], 0, ',', '.')?></td>
						<td><?=htmlspecialchars(isset($it['bien_so']) ? $it['bien_so'] : '')?></td>
						<td><?=htmlspecialchars($it['gv_hoten'])?></td>
						<td><?=htmlspecialchars($it['ky'])?></td>
						<td>
							<?php if((int)($it['hop_le'] ?? 1) === 1) { ?>
								<span class="badge badge-success">Hợp lệ</span>
							<?php } else { ?>
								<span class="badge badge-danger">Không hợp lệ</span>
							<?php } ?>
						</td>
						<td><?=htmlspecialchars($it['note_1'] ?? '')?></td>
						<td><?=((int)$it['da_quyettoan'] === 1 && !empty($it['ngay_thanh_toan'])) ? date('d/m/Y', strtotime($it['ngay_thanh_toan'])) : '-'?></td>
						<td>
							<?php if((int)($it['ke_toan_kiem_tra'] ?? 0) === 1) { ?>
								<span class="badge badge-success">Đã kiểm tra</span>
								<?php if(!empty($it['ngay_kiem_tra'])) { ?><div class="small text-muted"><?=date('d/m/Y', strtotime($it['ngay_kiem_tra']))?></div><?php } ?>
							<?php } else { ?>
								<span class="badge badge-warning text-dark">Chưa kiểm tra</span>
							<?php } ?>
						</td>
						<td>
							<?php if((int)$it['da_quyettoan'] === 1) { ?>
								<span class="badge badge-secondary">Đã quyết toán</span>
							<?php } else { ?>
								<span class="badge badge-success">Chưa quyết toán</span>
							<?php } ?>
						</td>
						<td class="text-right">
							<?php if(xd_can_kiem_tra()) { $toggleUrl = 'index.php?com=xangdau&act=toggleKiemTraHoadon&id='.(int)$it['id'].'&p='.$curPage; ?>
								<a class="btn btn-xs <?=((int)($it['ke_toan_kiem_tra'] ?? 0) === 1) ? 'btn-secondary' : 'btn-warning'?>" href="<?=$toggleUrl?>" onclick="return confirm('<?=((int)($it['ke_toan_kiem_tra'] ?? 0) === 1) ? 'Bỏ xác nhận kiểm tra cho hóa đơn này?' : 'Xác nhận kiểm tra hóa đơn này?';?>');">
									<i class="fas fa-<?=((int)($it['ke_toan_kiem_tra'] ?? 0) === 1) ? 'undo' : 'check'?>"></i>
								</a>
							<?php } ?>
								<?php if(xd_can_kiem_tra() && (int)$it['da_quyettoan'] === 0) { $toggleValidUrl = 'index.php?com=xangdau&act=toggleHopLeHoadon&id='.(int)$it['id'].'&p='.$curPage; ?>
									<a class="btn btn-xs <?=((int)($it['hop_le'] ?? 1) === 1) ? 'btn-success' : 'btn-outline-danger'?>" href="<?=$toggleValidUrl?>" onclick="return confirm('<?=((int)($it['hop_le'] ?? 1) === 1) ? 'Đánh dấu hóa đơn này là không hợp lệ?' : 'Đánh dấu hóa đơn này là hợp lệ?';?>');">
										<i class="fas fa-<?=((int)($it['hop_le'] ?? 1) === 1) ? 'thumbs-up' : 'exclamation-triangle'?>"></i>
									</a>
								<?php } ?>
								<?php if(xd_can_xoa() && (int)$it['da_quyettoan'] === 0) { ?>
								<a class="btn btn-xs btn-danger" href="index.php?com=xangdau&act=deleteHoadon&id=<?=(int)$it['id']?>&p=<?=$curPage?>" onclick="return confirm('Xóa hóa đơn này?');"><i class="far fa-trash-alt"></i></a>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="15" class="text-center text-muted">Chưa có hóa đơn nào</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer text-sm"><?=isset($paging) ? $paging : ''?></div>
	</div>
</section>
