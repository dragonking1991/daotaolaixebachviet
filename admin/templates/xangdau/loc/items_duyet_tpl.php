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
		<div class="card-header"><h3 class="card-title">Danh sách giáo viên đã kiểm tra, chờ duyệt thanh toán</h3></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>STT</th>
						<th>Giáo viên</th>
						<th>Thao tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($xd_loc_duyet_data)) { $i = 0; foreach($xd_loc_duyet_data as $gv) { $i++; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($gv['gv_hoten'] !== '' ? $gv['gv_hoten'] : $gv['gv_key'])?></td>
						<td>
							<?php if(xd_can_duyet()) { $approve_url = 'index.php?com=xangdau&act=duyetGiaoVien&gv_key='.urlencode($gv['gv_key']); ?>
							<a class="btn btn-sm btn-primary" href="<?=$approve_url?>" onclick="return confirm('Duyệt thanh toán cho giáo viên này?');"><i class="fas fa-stamp mr-1"></i>Duyệt</a>
							<?php } else { ?>
							<span class="text-muted">Chờ quản lý duyệt</span>
							<?php } ?>
							<?php if(xd_can_kiem_tra()) { $uncheck_url = 'index.php?com=xangdau&act=huyKiemTraGiaoVien&gv_key='.urlencode($gv['gv_key']); ?>
							<a class="btn btn-sm btn-secondary" href="<?=$uncheck_url?>" title="Chuyển về chưa kiểm tra" onclick="return confirm('Chuyển giáo viên này về trạng thái chưa kiểm tra?');"><i class="fas fa-undo mr-1"></i>Hủy kiểm tra</a>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="3" class="text-center text-muted">Không có giáo viên nào chờ duyệt.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
