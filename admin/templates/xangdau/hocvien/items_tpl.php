<?php
	$linkMan = "index.php?com=xangdau&act=hocvien";
	$linkUpload = "index.php?com=xangdau&act=uploadHocvien";
	$nhomBadge = array('BT' => 'badge-primary', 'CK' => 'badge-info', 'DAT' => 'badge-warning');
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Học viên xăng dầu</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top">
		<form method="get" action="index.php" class="form-inline">
			<input type="hidden" name="com" value="xangdau">
			<input type="hidden" name="act" value="hocvien">
			<a class="btn btn-sm bg-gradient-success text-white mr-2" href="<?=$linkUpload?>"><i class="fas fa-upload mr-1"></i>Import học viên</a>
			<a class="btn btn-sm bg-gradient-primary text-white mr-2" href="index.php?com=xangdau&act=loc"><i class="fas fa-filter mr-1"></i>Lọc thanh toán</a>
			<a class="btn btn-sm btn-danger mr-3" href="index.php?com=xangdau&act=deleteAllHocvien" onclick="return confirm('Xóa toàn bộ học viên chưa thanh toán?');"><i class="fas fa-trash-alt mr-1"></i>Xóa toàn bộ</a>
			<div class="form-group mb-0 mr-2">
				<input class="form-control form-control-sm text-sm" style="min-width:200px;" type="search" name="keyword" placeholder="Tên / CCCD HV / Tên GV" value="<?=htmlspecialchars($xd_filter_keyword)?>">
			</div>
			<div class="form-group mb-0 mr-2">
				<select class="form-control form-control-sm text-sm" name="nhom">
					<option value="">Tất cả nhóm</option>
					<option value="BT" <?=($xd_filter_nhom=='BT')?'selected':''?>>BT</option>
					<option value="CK" <?=($xd_filter_nhom=='CK')?'selected':''?>>CK</option>
					<option value="DAT" <?=($xd_filter_nhom=='DAT')?'selected':''?>>DAT</option>
				</select>
			</div>
			<div class="form-group mb-0 mr-2">
				<select class="form-control form-control-sm text-sm" name="trangthai">
					<option value="">Tất cả trạng thái</option>
					<option value="da" <?=($xd_filter_trangthai=='da')?'selected':''?>>Đã thanh toán</option>
					<option value="chua" <?=($xd_filter_trangthai=='chua')?'selected':''?>>Chưa thanh toán</option>
				</select>
			</div>
			<button type="submit" class="btn btn-sm bg-gradient-success text-white mr-1"><i class="fas fa-search mr-1"></i>Lọc</button>
			<a class="btn btn-sm bg-gradient-secondary text-white" href="<?=$linkMan?>">Bỏ lọc</a>
		</form>
	</div>

	<div class="card card-primary card-outline text-sm mb-0">
		<div class="card-header"><h3 class="card-title">Danh sách học viên xăng dầu</h3></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Họ tên</th>
						<th>CCCD</th>
						<th>Khóa</th>
						<th>Ngày sinh</th>
						<th>Nhóm</th>
						<th>GV phụ trách</th>
						<th class="text-right">Số tiền TT</th>
						<th>Ngày TT</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)) { $i = 0; foreach($items as $it) { $i++; $badge = isset($nhomBadge[$it['nhom']]) ? $nhomBadge[$it['nhom']] : 'badge-secondary'; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($it['ho_ten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars(isset($it['khoa']) ? $it['khoa'] : '')?></td>
						<td><?=htmlspecialchars($it['ngaysinh'])?></td>
						<td><span class="badge <?=$badge?>"><?=htmlspecialchars($it['nhom'])?></span></td>
						<td><?=htmlspecialchars($it['gv_hoten'])?></td>
						<td class="text-right"><?=number_format((float)$it['so_tien_thanh_toan'], 0, ',', '.')?></td>
						<td>
							<?php if($it['ngay_thanh_toan'] !== null) { ?>
								<span class="badge badge-secondary"><?=date('d/m/Y', strtotime($it['ngay_thanh_toan']))?></span>
							<?php } else { ?>
								<span class="badge badge-light">Chưa TT</span>
							<?php } ?>
						</td>
						<td class="text-right">
							<?php if($it['ngay_thanh_toan'] === null) { ?>
								<a class="btn btn-xs btn-danger" href="index.php?com=xangdau&act=deleteHocvien&id=<?=(int)$it['id']?>&p=<?=$curPage?>" onclick="return confirm('Xóa học viên này?');"><i class="far fa-trash-alt"></i></a>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="10" class="text-center text-muted">Chưa có học viên nào</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer text-sm"><?=isset($paging) ? $paging : ''?></div>
	</div>
</section>
