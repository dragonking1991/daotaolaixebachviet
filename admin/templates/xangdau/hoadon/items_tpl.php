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
	<div class="card-footer text-sm sticky-top">
		<form method="get" action="index.php" class="form-inline">
			<input type="hidden" name="com" value="xangdau">
			<input type="hidden" name="act" value="hoadon">
			<a class="btn btn-sm bg-gradient-success text-white mr-2" href="<?=$linkUpload?>"><i class="fas fa-upload mr-1"></i>Import hóa đơn</a>
			<a class="btn btn-sm bg-gradient-primary text-white mr-2" href="index.php?com=xangdau&act=loc"><i class="fas fa-filter mr-1"></i>Lọc thanh toán</a>
			<a class="btn btn-sm btn-danger mr-3" href="index.php?com=xangdau&act=deleteAllHoadon" onclick="return confirm('Xóa toàn bộ hóa đơn chưa quyết toán?');"><i class="fas fa-trash-alt mr-1"></i>Xóa toàn bộ</a>
			<div class="form-group mb-0 mr-2">
				<input class="form-control form-control-sm text-sm" style="min-width:200px;" type="search" name="keyword" placeholder="Mã HĐ / Tên GV" value="<?=htmlspecialchars($xd_filter_keyword)?>">
			</div>
			<div class="form-group mb-0 mr-2">
				<input class="form-control form-control-sm text-sm" type="date" name="from_date" value="<?=htmlspecialchars($xd_filter_from)?>">
			</div>
			<div class="form-group mb-0 mr-2">
				<input class="form-control form-control-sm text-sm" type="date" name="to_date" value="<?=htmlspecialchars($xd_filter_to)?>">
			</div>
			<div class="form-group mb-0 mr-2">
				<input class="form-control form-control-sm text-sm" type="text" name="ky" placeholder="Kỳ (VD: T5)" value="<?=htmlspecialchars($xd_filter_ky)?>" style="max-width:120px;">
			</div>
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
							<?php if((int)$it['da_quyettoan'] === 1) { ?>
								<span class="badge badge-secondary">Đã quyết toán</span>
							<?php } else { ?>
								<span class="badge badge-success">Chưa quyết toán</span>
							<?php } ?>
						</td>
						<td class="text-right">
							<?php if((int)$it['da_quyettoan'] === 0) { ?>
								<a class="btn btn-xs btn-danger" href="index.php?com=xangdau&act=deleteHoadon&id=<?=(int)$it['id']?>&p=<?=$curPage?>" onclick="return confirm('Xóa hóa đơn này?');"><i class="far fa-trash-alt"></i></a>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="11" class="text-center text-muted">Chưa có hóa đơn nào</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer text-sm"><?=isset($paging) ? $paging : ''?></div>
	</div>
</section>
