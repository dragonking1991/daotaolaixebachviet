<?php if(!defined('SOURCES')) die("Error"); $s = isset($dt_stats)?$dt_stats:array(); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Tổng quan đào tạo</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="row">
		<?php
			$cards = array(
				array('khoa', 'Khóa đào tạo', 'fa-graduation-cap', 'bg-gradient-primary', 'khoa'),
				array('hocvien', 'Học viên', 'fa-users', 'bg-gradient-success', 'hocvien'),
				array('giaovien', 'Giáo viên', 'fa-chalkboard-teacher', 'bg-gradient-info', 'giaovien'),
				array('xe', 'Xe tập lái', 'fa-car', 'bg-gradient-warning', 'xe'),
			);
			foreach($cards as $c):
		?>
		<div class="col-6 col-md-3">
			<a href="index.php?com=daotao&act=<?=$c[4]?>" class="small-box <?=$c[3]?>" style="display:block;color:#fff;text-decoration:none;">
				<div class="inner">
					<h3><?=number_format((int)(isset($s[$c[0]])?$s[$c[0]]:0))?></h3>
					<p><?=$c[1]?></p>
				</div>
				<div class="icon"><i class="fas <?=$c[2]?>"></i></div>
			</a>
		</div>
		<?php endforeach; ?>
	</div>

	<div class="row">
		<div class="col-md-6">
			<div class="card card-warning card-outline text-sm">
				<div class="card-header"><h3 class="card-title"><strong>Khóa sắp mãn hạn</strong> <span class="text-muted">(30 ngày tới)</span></h3></div>
				<div class="card-body p-0">
					<?php if(!empty($s['khoa_sap_manhoa'])): ?>
					<table class="table dt-list table-hover mb-0">
						<thead><tr><th>Khóa</th><th>Hạng</th><th>Mãn hạn</th></tr></thead>
						<tbody>
						<?php foreach($s['khoa_sap_manhoa'] as $k): $days = (int)floor((strtotime($k['ngay_manhoa']) - strtotime(date('Y-m-d'))) / 86400); ?>
							<tr>
								<td><a href="index.php?com=daotao&act=hocvien&id_khoa=<?=$k['id']?>" class="dt-title" style="text-decoration:none"><?=htmlspecialchars($k['ma_khoa'])?></a>
									<div class="dt-sub"><?=htmlspecialchars($k['ten_khoa'] ?: '—')?></div></td>
								<td><?php if(!empty($k['hang'])): ?><span class="dt-tag"><?=htmlspecialchars($k['hang'])?></span><?php endif; ?></td>
								<td><div class="dt-title"><?=date('d/m/Y', strtotime($k['ngay_manhoa']))?></div>
									<div class="dt-sub" style="color:<?=$days<=7?'#c0392b':'#b8860b'?>">còn <?=$days?> ngày</div></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php else: ?>
						<div class="dt-empty-box"><i class="fas fa-calendar-check"></i> Không có khóa nào sắp mãn hạn trong 30 ngày tới.</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card card-secondary card-outline text-sm">
				<div class="card-header"><h3 class="card-title"><strong>Thao tác gần đây</strong></h3>
					<div class="card-tools"><a href="index.php?com=daotao&act=audit" class="btn btn-xs bg-gradient-secondary"><i class="fas fa-list mr-1"></i>Xem tất cả</a></div>
				</div>
				<div class="card-body p-0">
					<?php if(!empty($s['audit_recent'])): ?>
					<table class="table dt-list table-hover mb-0">
						<thead><tr><th>Hành động</th><th>Người dùng</th><th>Thời gian</th></tr></thead>
						<tbody>
						<?php foreach($s['audit_recent'] as $a): ?>
							<tr>
								<td><span class="dt-tag"><?=htmlspecialchars($a['action'])?></span> <span class="dt-sub"><?=htmlspecialchars($a['entity'])?><?=((int)$a['affected'])?' ('.(int)$a['affected'].')':''?></span></td>
								<td class="dt-sub"><?=htmlspecialchars($a['user'] ?: '—')?></td>
								<td class="dt-sub"><?=$a['ngaytao'] ? date('d/m H:i', (int)$a['ngaytao']) : '—'?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php else: ?>
						<div class="dt-empty-box"><i class="fas fa-clock"></i> Chưa có thao tác nào được ghi nhận.</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
