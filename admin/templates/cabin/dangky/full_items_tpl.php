<?php
	require_once LIBRARIES.'cabin_config.php';

	$id_kh = (int)$kh_info['id'];
	$linkBack = "index.php?com=cabin&act=man";
	$linkList = "index.php?com=cabin&act=dangky&id=".$id_kh;
	$linkAutofill = "index.php?com=cabin&act=autofill_dangky&id=".$id_kh;
	$allSlots = cabin_time_slots();
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=cabin&act=man" title="Quản lý khóa học cabin">Quản lý khóa học cabin</a></li>
				<li class="breadcrumb-item active">Full lịch: <?=htmlspecialchars($kh_info['ten'])?></li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card-footer text-sm sticky-top">
		<a class="btn btn-sm bg-gradient-secondary text-white" href="<?=$linkBack?>" title="Quay lại"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
		<a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkList?>" title="Danh sách đăng ký"><i class="fas fa-list mr-2"></i>Danh sách đăng ký</a>
		<a class="btn btn-sm bg-gradient-danger text-white" href="<?=$linkAutofill?>" id="btn-autofill-dangky" title="Tự động fill ca trống"><i class="fas fa-magic mr-2"></i>Auto fill học viên chưa đăng ký</a>
	</div>

	<div class="card card-primary card-outline text-sm mb-3">
		<div class="card-header">
			<h3 class="card-title">Thông tin khóa học</h3>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3"><strong>Khóa:</strong> <?=htmlspecialchars($kh_info['ten'])?></div>
				<div class="col-md-3"><strong>Từ ngày:</strong> <?=date('d/m/Y', strtotime($kh_info['ngay_batdau']))?></div>
				<div class="col-md-3"><strong>Đến ngày:</strong> <?=date('d/m/Y', strtotime($kh_info['ngay_ketthuc']))?></div>
				<div class="col-md-3"><strong>Sức chứa mỗi ca:</strong> <?=$capacity_per_slot?></div>
			</div>
			<?php $unregTotal = !empty($unregistered_students) ? count($unregistered_students) : 0; ?>
			<div class="mt-2"><span class="badge badge-warning">Chưa đăng ký: <?=$unregTotal?> học viên</span></div>
		</div>
	</div>

	<div class="card card-primary card-outline text-sm mb-3">
		<div class="card-header">
			<h3 class="card-title">Danh sách học viên chưa đăng ký</h3>
		</div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover table-sm mb-0">
				<thead>
					<tr>
						<th width="6%" class="text-center">STT</th>
						<th>Họ tên</th>
						<th width="16%">CCCD</th>
						<th width="16%">Ngày sinh</th>
						<th width="14%">Người nộp hồ sơ</th>
					</tr>
				</thead>
				<tbody>
					<?php if(empty($unregistered_students)) { ?>
						<tr><td colspan="100" class="text-center">Tất cả học viên đã có lịch đăng ký</td></tr>
					<?php } else { ?>
						<?php foreach($unregistered_students as $i => $hv) { ?>
							<tr>
								<td class="text-center"><?=$i + 1?></td>
								<td><?=htmlspecialchars($hv['tenvi'])?></td>
								<td><?=htmlspecialchars($hv['cccd'])?></td>
								<td><?=htmlspecialchars($hv['ngaysinh'])?></td>
								<td><?=htmlspecialchars($hv['hang'])?></td>
							</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php if(empty($schedule_dates)) { ?>
		<div class="alert alert-warning">Không có ngày học hợp lệ trong khoảng thời gian của khóa.</div>
	<?php } else { ?>
		<?php foreach($schedule_dates as $day) { ?>
			<div class="card card-primary card-outline text-sm mb-3">
				<div class="card-header">
					<h3 class="card-title"><?=$day['dow_label']?> - <?=$day['label']?></h3>
				</div>
				<div class="card-body table-responsive p-0">
					<table class="table table-bordered table-sm mb-0">
						<thead>
							<tr>
								<th width="20%" class="text-center">Ca</th>
								<th width="20%" class="text-center">Giờ</th>
								<th>Danh sách học viên</th>
								<th width="12%" class="text-center">Đã đăng ký</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($day['allowed_ca'] as $ca) { ?>
								<?php
									$slotDef = isset($allSlots[$ca]) ? $allSlots[$ca] : array('label' => 'Ca '.$ca, 'gio_b_d' => '', 'gio_kt' => '');
									$rows = (isset($schedule_map[$day['date']]) && isset($schedule_map[$day['date']][$ca])) ? $schedule_map[$day['date']][$ca] : array();
									$currentCount = count($rows);
								?>
								<tr>
									<td class="text-center font-weight-bold"><?=htmlspecialchars($slotDef['label'])?></td>
									<td class="text-center"><?=htmlspecialchars($slotDef['gio_b_d'])?> - <?=htmlspecialchars($slotDef['gio_kt'])?></td>
									<td>
										<?php if(empty($rows)) { ?>
											<span class="text-muted">Chưa có học viên đăng ký</span>
										<?php } else { ?>
											<?php foreach($rows as $r) { ?>
												<a href="#" class="badge badge-info mr-1 mb-1 btn-view-hv"
													data-iddk="<?=(int)$r['id']?>"
													data-ten="<?=htmlspecialchars($r['tenvi'], ENT_QUOTES)?>"
													data-cccd="<?=htmlspecialchars($r['hv_cccd'], ENT_QUOTES)?>"
													data-ngaysinh="<?=htmlspecialchars($r['ngaysinh'], ENT_QUOTES)?>"
													data-hang="<?=htmlspecialchars($r['hang'], ENT_QUOTES)?>"
													data-ngayhoc="<?=date('d/m/Y', strtotime($r['ngay_hoc']))?>"
													data-ca="<?=htmlspecialchars($slotDef['label'], ENT_QUOTES)?>"
													data-gio="<?=htmlspecialchars($r['gio_b_d'].' - '.$r['gio_kt'], ENT_QUOTES)?>"
													title="Xem chi tiết học viên"><?=htmlspecialchars($r['tenvi'])?></a>
											<?php } ?>
										<?php } ?>
									</td>
									<td class="text-center"><span class="badge <?=($currentCount >= $capacity_per_slot ? 'badge-success' : 'badge-secondary')?>"><?=$currentCount?> / <?=$capacity_per_slot?></span></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php } ?>
	<?php } ?>
</section>

<div class="modal fade" id="modal-hocvien-detail" tabindex="-1" role="dialog" aria-labelledby="modal-hocvien-detail-label" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modal-hocvien-detail-label">Chi tiết học viên</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="hocvien-detail-body"></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function(){
	$('#btn-autofill-dangky').on('click', function(e){
		if(!confirm('Xác nhận tự động xếp tất cả học viên chưa đăng ký vào các ca còn trống?'))
		{
			e.preventDefault();
			return false;
		}
	});

	$('body').on('click', '.btn-view-hv', function(e){
		e.preventDefault();
		var idDangky = parseInt($(this).data('iddk') || 0, 10);
		var editUrl = idDangky > 0 ? 'index.php?com=cabin&act=edit_dangky&id=' + idDangky + '&return_act=full_dangky' : '#';
		var deleteUrl = idDangky > 0 ? 'index.php?com=cabin&act=delete_dangky&id=' + idDangky + '&return_act=full_dangky' : '#';
		var html = '';
		html += '<p class="mb-2"><strong>Họ tên:</strong> ' + ($(this).data('ten') || '') + '</p>';
		html += '<p class="mb-2"><strong>CCCD:</strong> ' + ($(this).data('cccd') || '') + '</p>';
		html += '<p class="mb-2"><strong>Ngày sinh:</strong> ' + ($(this).data('ngaysinh') || '') + '</p>';
		html += '<p class="mb-2"><strong>Người nộp hồ sơ:</strong> ' + ($(this).data('hang') || '') + '</p>';
		html += '<hr>';
		html += '<p class="mb-2"><strong>Ngày học:</strong> ' + ($(this).data('ngayhoc') || '') + '</p>';
		html += '<p class="mb-0"><strong>Ca học:</strong> ' + ($(this).data('ca') || '') + ' (' + ($(this).data('gio') || '') + ')</p>';
		html += '<div class="mt-3">';
		html += '<a class="btn btn-sm btn-primary mr-2" href="' + editUrl + '"><i class="fas fa-edit mr-1"></i>Sửa đăng ký</a>';
		html += '<a class="btn btn-sm btn-danger btn-delete-dk" href="' + deleteUrl + '"><i class="fas fa-trash-alt mr-1"></i>Xóa đăng ký</a>';
		html += '</div>';
		$('#hocvien-detail-body').html(html);
		$('#modal-hocvien-detail').modal('show');
	});

	$('body').on('click', '.btn-delete-dk', function(e){
		if(!confirm('Bạn có chắc muốn xóa đăng ký này?'))
		{
			e.preventDefault();
			return false;
		}
	});
});
</script>
