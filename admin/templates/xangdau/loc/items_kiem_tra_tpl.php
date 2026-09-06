<?php
	$q = '';
	if(!empty($xd_loc_ky_options)) $q .= '&ky='.urlencode($xd_loc_ky_options[0]['ky'] ?? '');
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Danh sách giáo viên chưa kiểm tra</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card card-warning card-outline text-sm">
		<div class="card-header"><h3 class="card-title">Danh sách giáo viên cần kiểm tra</h3></div>
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
					<?php if(!empty($xd_loc_kiem_tra_data)) { $i = 0; foreach($xd_loc_kiem_tra_data as $gv) { $i++; ?>
					<tr>
						<td><?=$i?></td>
						<td><?=htmlspecialchars($gv['gv_hoten'] !== '' ? $gv['gv_hoten'] : $gv['gv_key'])?></td>
						<td>
							<?php if(xd_can_kiem_tra()) { $check_url = 'index.php?com=xangdau&act=kiemTraGiaoVien&gv_key='.urlencode($gv['gv_key']); ?>
							<a class="btn btn-sm btn-warning" href="<?=$check_url?>" onclick="return confirm('Xác nhận đã kiểm tra giáo viên này?');"><i class="fas fa-check mr-1"></i>Kiểm tra</a>
							<?php } else { ?>
							<span class="text-muted">Chờ kế toán kiểm tra</span>
							<?php } ?>
						</td>
					</tr>
					<?php } } else { ?>
					<tr><td colspan="3" class="text-center text-muted">Không có giáo viên nào cần kiểm tra.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
