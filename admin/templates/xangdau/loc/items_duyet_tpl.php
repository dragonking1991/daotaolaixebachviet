<?php
	$q = '';
	if(!empty($xd_loc_ky_options)) $q .= '&ky='.urlencode($xd_loc_ky_options[0]['ky'] ?? '');
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Danh sách giáo viên chờ duyệt</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header py-3 d-flex align-items-center justify-content-between"><h3 class="card-title mb-0"><i class="fas fa-clipboard-check text-primary mr-2"></i>Danh sách giáo viên đã kiểm tra, chờ duyệt thanh toán</h3><?php if(xd_can_duyet() || xd_can_kiem_tra()) { ?><a class="btn btn-sm btn-success" href="index.php?com=xangdau&act=xuatDaKiemTraGiaoVien&cho_duyet=1" title="Xuất Excel danh sách giáo viên chờ duyệt"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a><?php } ?></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover table-striped mb-0">
				<thead>
					<tr>
						<th class="text-center" style="width:70px;">STT</th>
						<th style="min-width:220px;">Giáo viên</th>
						<th style="width:210px;">Ngày kế toán kiểm tra</th>
						<th style="min-width:430px;">Thao tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($xd_loc_duyet_data)) { $i = 0; foreach($xd_loc_duyet_data as $gv) { $i++; ?>
					<tr>
						<td class="text-center text-muted"><?=$i?></td>
						<td class="font-weight-bold"><?=htmlspecialchars($gv['gv_hoten'] !== '' ? $gv['gv_hoten'] : $gv['gv_key'])?></td>
						<td><?php if(!empty($gv['ngay_kiem_tra'])) { ?><span class="badge badge-light border px-2 py-1"><i class="far fa-calendar-alt mr-1 text-primary"></i><?=date('d/m/Y', strtotime($gv['ngay_kiem_tra']))?></span><?php } else { ?><span class="text-muted">Chưa có dữ liệu</span><?php } ?></td>
						<td><div class="d-flex flex-wrap align-items-center" style="gap:.35rem;">
							<?php $detail_url = 'index.php?com=xangdau&act=xemGiaoVien&gv_key='.urlencode($gv['gv_key']); ?>
							<a class="btn btn-sm btn-info" href="<?=$detail_url?>" title="Xem danh sách học viên / hóa đơn"><i class="fas fa-eye mr-1"></i>Xem danh sách</a>
							<?php if(xd_can_duyet()) { $approve_url = 'index.php?com=xangdau&act=duyetGiaoVien&gv_key='.urlencode($gv['gv_key']); ?>
							<a class="btn btn-sm btn-primary" href="<?=$approve_url?>" onclick="return confirm('Duyệt thanh toán cho giáo viên này?');"><i class="fas fa-stamp mr-1"></i>Duyệt</a>
							<?php } else { ?>
							<span class="badge badge-warning text-dark px-2 py-2"><i class="fas fa-hourglass-half mr-1"></i>Chờ quản lý duyệt</span>
							<?php } ?>
							<?php if(xd_can_kiem_tra()) { $uncheck_url = 'index.php?com=xangdau&act=huyKiemTraGiaoVien&gv_key='.urlencode($gv['gv_key']); ?>
							<a class="btn btn-sm btn-secondary" href="<?=$uncheck_url?>" title="Chuyển về chưa kiểm tra" onclick="return confirm('Chuyển giáo viên này về trạng thái chưa kiểm tra?');"><i class="fas fa-undo mr-1"></i>Hủy kiểm tra</a>
							<?php } ?>
						</div></td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="4" class="text-center text-muted">Không có giáo viên nào chờ duyệt.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
