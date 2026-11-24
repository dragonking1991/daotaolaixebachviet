<?php
if(!defined('SOURCES')) die("Error");
$loEsc = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$loStatus = lo_status_list();
$loCanEdit = dt_can('lichon', 'edit');
$loCanExport = dt_can('lichon', 'export');
?>
<link rel="stylesheet" href="assets/css/lichon.css?v=<?=@filemtime(__DIR__.'/../../../assets/css/lichon.css')?>">
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item">QL Đào tạo</li>
			<li class="breadcrumb-item active">Lịch ôn sát hạch</li>
		</ol>
	</div>
</section>
<section class="content lo-page">
	<div class="container-fluid">
		<div class="lo-hero card card-primary card-outline">
			<div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
				<div>
					<div class="text-uppercase text-muted small mb-1">Đăng ký giờ ôn xe thiết bị</div>
					<h2 class="h4 mb-1"><i class="fas fa-calendar-alt text-primary mr-2"></i>Lịch ôn sát hạch</h2>
					<div class="text-muted">Mỗi đợt ôn ứng với một ngày sát hạch tại một sân. Giáo viên tự đăng ký số giờ trên Cổng GV, bộ phận xếp lịch và xuất file theo dõi.</div>
				</div>
				<?php if($loCanEdit): ?>
				<button type="button" class="btn btn-primary mt-3 mt-lg-0" data-toggle="modal" data-target="#lo-dot-modal"><i class="fas fa-plus mr-1"></i>Tạo đợt ôn</button>
				<?php endif; ?>
			</div>
			<div class="lo-flow">
				<div class="lo-flow-step"><span>1</span><div><strong>Tạo đợt &amp; cấu hình xe</strong><small>Ngày tập, hạng, số xe, khung giờ</small></div></div>
				<div class="lo-flow-step"><span>2</span><div><strong>Mở đăng ký</strong><small>GV đăng ký số giờ + ghi chú</small></div></div>
				<div class="lo-flow-step"><span>3</span><div><strong>Khóa &amp; xếp lịch</strong><small>Tự động, sau đó chỉnh tay</small></div></div>
				<div class="lo-flow-step"><span>4</span><div><strong>Công bố &amp; xuất Excel</strong><small>GV xem lịch, bộ phận theo dõi</small></div></div>
			</div>
		</div>

		<div class="d-flex flex-wrap align-items-center mb-2 lo-filter">
			<a href="index.php?com=daotao&amp;act=lichon" class="btn btn-sm <?=$lo_filter===''?'btn-dark':'btn-outline-secondary'?> mr-1 mb-1">Tất cả</a>
			<?php foreach($loStatus as $k => $s): ?>
			<a href="index.php?com=daotao&amp;act=lichon&amp;tt=<?=$k?>" class="btn btn-sm <?=$lo_filter===$k?'btn-'.$s['badge']:'btn-outline-'.$s['badge']?> mr-1 mb-1"><i class="fas <?=$s['icon']?> mr-1"></i><?=$s['label']?></a>
			<?php endforeach; ?>
		</div>

		<?php if(empty($lo_dots)): ?>
		<div class="card"><div class="card-body text-center py-5">
			<div class="text-muted mb-3"><i class="far fa-calendar-plus fa-3x"></i></div>
			<h4 class="h5"><?=$lo_filter === '' ? 'Chưa có đợt ôn nào' : 'Không có đợt ôn ở trạng thái này'?></h4>
			<p class="text-muted mb-3">Khi sân chốt số lượng xe và ngày tập, hãy tạo đợt ôn để giáo viên đăng ký giờ.</p>
			<?php if($loCanEdit && $lo_filter === ''): ?><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#lo-dot-modal"><i class="fas fa-plus mr-1"></i>Tạo đợt ôn đầu tiên</button><?php endif; ?>
		</div></div>
		<?php else: ?>
		<div class="card">
			<div class="table-responsive">
				<table class="table table-hover mb-0 lo-dot-table">
					<thead><tr>
						<th>Ngày sát hạch</th><th>Trạng thái</th><th class="text-center">Ngày tập</th><th class="text-center">Giáo viên</th>
						<th style="min-width:220px">Giờ đăng ký / giờ sân</th><th style="min-width:200px">Đã xếp / đăng ký</th><th class="text-right">Thao tác</th>
					</tr></thead>
					<tbody>
					<?php foreach($lo_dots as $o): $st = $loStatus[$o['trang_thai']] ?? $loStatus['nhap'];
						$pctDk = (int)$o['gio_san'] ? min(100, round($o['gio_dk'] * 100 / $o['gio_san'])) : 0;
						$pctXep = (int)$o['gio_dk'] ? min(100, round($o['gio_xep'] * 100 / $o['gio_dk'])) : 0;
						$url = 'index.php?com=daotao&act=lichonDetail&id='.(int)$o['id']; ?>
					<tr>
						<td>
							<a href="<?=$loEsc($url)?>" class="lo-date-link">
								<span class="lo-date-box"><b><?=date('d', strtotime($o['ngay_sh']))?></b><small>Th<?=date('m', strtotime($o['ngay_sh']))?></small></span>
								<span><strong><?=$loEsc(lo_dot_title($o))?></strong><small class="d-block text-muted"><?=lo_weekday($o['ngay_sh'])?><?=$o['ghi_chu'] !== '' ? ' · '.$loEsc(mb_strimwidth($o['ghi_chu'], 0, 60, '…')) : ''?></small></span>
							</a>
						</td>
						<td><span class="badge badge-<?=$st['badge']?> lo-badge"><i class="fas <?=$st['icon']?> mr-1"></i><?=$st['label']?></span></td>
						<td class="text-center"><?=(int)$o['so_ngay']?></td>
						<td class="text-center"><?=(int)$o['so_gv']?></td>
						<td>
							<div class="d-flex justify-content-between small"><span><strong><?=number_format((int)$o['gio_dk'])?></strong> / <?=number_format((int)$o['gio_san'])?> giờ</span><span class="<?=$o['gio_dk'] > $o['gio_san'] ? 'text-danger font-weight-bold' : 'text-muted'?>"><?=$o['gio_dk'] > $o['gio_san'] ? 'Vượt '.($o['gio_dk'] - $o['gio_san']).'h' : $pctDk.'%'?></span></div>
							<div class="progress progress-xs"><div class="progress-bar <?=$o['gio_dk'] > $o['gio_san'] ? 'bg-danger' : 'bg-info'?>" style="width:<?=$pctDk?>%"></div></div>
						</td>
						<td>
							<div class="d-flex justify-content-between small"><span><strong><?=number_format((int)$o['gio_xep'])?></strong> / <?=number_format((int)$o['gio_dk'])?> giờ</span><span class="text-muted"><?=$pctXep?>%</span></div>
							<div class="progress progress-xs"><div class="progress-bar <?=$pctXep >= 100 ? 'bg-success' : 'bg-primary'?>" style="width:<?=$pctXep?>%"></div></div>
						</td>
						<td class="text-right text-nowrap">
							<a href="<?=$loEsc($url)?>" class="btn btn-sm btn-primary"><i class="fas fa-arrow-right mr-1"></i>Mở</a>
							<?php if($loCanExport): ?><a href="index.php?com=daotao&amp;act=exportLichon&amp;id=<?=(int)$o['id']?>" class="btn btn-sm btn-outline-success" title="Xuất lịch ôn (Excel)"><i class="fas fa-file-excel"></i></a><?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endif; ?>
	</div>
</section>
<?php if($loCanEdit): $loDotForm = null; include __DIR__.'/dot_form_modal.php'; endif; ?>
