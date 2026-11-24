<?php
if(!defined('SOURCES')) die("Error");
$loEsc = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$loStatus = lo_status_list();
$loSt = $lo_dot['trang_thai'];
$loCanEdit = dt_can('lichon', 'edit');
$loCanDelete = dt_can('lichon', 'delete');
$loCanExport = dt_can('lichon', 'export');
$loId = (int)$lo_dot['id'];
$loCsrf = $loEsc(admin_csrf_token());
$loJs = function($v) { return htmlspecialchars(json_encode((string)$v, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); };
$loShortDay = function($date) {
	$w = (int)date('w', strtotime($date));
	return ($w === 0 ? 'CN' : 'T'.($w + 1)).' '.date('d/m', strtotime($date));
};
$loTotal = array('san' => 0, 'dk' => 0, 'xep' => 0, 'thieu' => 0);
foreach($lo_cap as $c) { $loTotal['san'] += $c['tong_gio']; $loTotal['dk'] += $c['da_dk']; }
foreach($lo_dk as $r) { $loTotal['xep'] += $r['da_xep']; $loTotal['thieu'] += max(0, $r['con_lai']); }
$loGvCount = count(array_unique(array_column($lo_dk, 'id_gv')));
$loDays = array_values(array_unique(array_column($lo_ngay, 'ngay')));
$loNext = array('nhap' => array('mo_dk', 'Mở đăng ký cho GV', 'success', 'fa-door-open'),
	'mo_dk' => array('khoa_dk', 'Khóa đăng ký & xếp lịch', 'warning', 'fa-lock'),
	'khoa_dk' => array('cong_bo', 'Công bố lịch cho GV', 'primary', 'fa-bullhorn'));
$loHours = range(5, 23);
$loTabUrl = function($tab, $extra = array()) use ($loId) { return lo_url($loId, $tab, $extra); };
$loDotForm = $lo_dot;
?>
<link rel="stylesheet" href="assets/css/lichon.css?v=<?=@filemtime(__DIR__.'/../../../assets/css/lichon.css')?>">
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&amp;act=lichon">Lịch ôn sát hạch</a></li>
			<li class="breadcrumb-item active"><?=$loEsc(lo_dot_title($lo_dot))?></li>
		</ol>
	</div>
</section>
<section class="content lo-page">
<div class="container-fluid">

	<!-- Thông tin đợt + quy trình trạng thái -->
	<div class="card card-primary card-outline lo-head">
		<div class="card-body">
			<div class="d-flex flex-column flex-xl-row justify-content-between">
				<div class="d-flex align-items-start mb-3 mb-xl-0">
					<span class="lo-date-box lo-date-box-lg mr-3"><b><?=date('d', strtotime($lo_dot['ngay_sh']))?></b><small>Th<?=date('m/Y', strtotime($lo_dot['ngay_sh']))?></small></span>
					<div>
						<div class="text-uppercase text-muted small">Đợt ôn sát hạch</div>
						<h2 class="h4 mb-1"><?=$loEsc(lo_dot_title($lo_dot))?> <span class="badge badge-<?=$loStatus[$loSt]['badge']?> lo-badge align-middle"><i class="fas <?=$loStatus[$loSt]['icon']?> mr-1"></i><?=$loStatus[$loSt]['label']?></span></h2>
						<div class="text-muted small">
							<span class="mr-3"><i class="far fa-calendar mr-1"></i><?=lo_weekday($lo_dot['ngay_sh'])?></span>
							<span class="mr-3"><i class="fas fa-random mr-1"></i>Song song tối đa <?=(int)$lo_dot['max_song_song']?> giờ/ngày</span>
							<?php if(!empty($lo_dot['id_sh_ky'])): foreach($lo_sh_ky as $k) if((int)$k['id'] === (int)$lo_dot['id_sh_ky']): ?><span class="mr-3"><i class="fas fa-link mr-1"></i><?=$loEsc($k['ten'])?></span><?php endif; endif; ?>
							<?php if($lo_dot['ngaycapnhat']): ?><span><i class="far fa-clock mr-1"></i>Cập nhật <?=date('H:i d/m/Y', (int)$lo_dot['ngaycapnhat'])?></span><?php endif; ?>
						</div>
						<?php if($lo_dot['ghi_chu'] !== ''): ?><div class="small mt-1"><i class="far fa-sticky-note text-muted mr-1"></i><?=$loEsc($lo_dot['ghi_chu'])?></div><?php endif; ?>
					</div>
				</div>
				<div class="d-flex flex-wrap align-items-start lo-head-actions">
					<?php if($loCanEdit && isset($loNext[$loSt])): $nx = $loNext[$loSt]; ?>
					<form method="post" action="index.php?com=daotao&amp;act=lichonStatus" class="mr-2 mb-2" <?=$nx[0] === 'cong_bo' && $loTotal['thieu'] ? 'onsubmit="return confirm(\'Còn '.$loTotal['thieu'].' giờ chưa xếp. Vẫn công bố lịch cho giáo viên?\')"' : ''?>>
						<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id" value="<?=$loId?>"><input type="hidden" name="trang_thai" value="<?=$nx[0]?>">
						<button type="submit" class="btn btn-<?=$nx[2]?>"><i class="fas <?=$nx[3]?> mr-1"></i><?=$nx[1]?></button>
					</form>
					<?php endif; ?>
					<?php if($loCanExport): ?>
					<div class="btn-group mr-2 mb-2">
						<button type="button" class="btn btn-outline-success dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</button>
						<div class="dropdown-menu dropdown-menu-right">
							<a class="dropdown-item" href="index.php?com=daotao&amp;act=exportLichon&amp;id=<?=$loId?>"><i class="fas fa-th mr-2 text-success"></i>Lịch ôn xe (tất cả hạng)</a>
							<a class="dropdown-item" href="index.php?com=daotao&amp;act=exportLichonDk&amp;id=<?=$loId?>"><i class="fas fa-list mr-2 text-success"></i>Danh sách đăng ký giờ</a>
						</div>
					</div>
					<?php endif; ?>
					<?php if($loCanEdit || $loCanDelete): ?>
					<div class="btn-group mb-2">
						<button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false" aria-label="Thao tác khác"><i class="fas fa-ellipsis-h"></i></button>
						<div class="dropdown-menu dropdown-menu-right">
							<?php if($loCanEdit): ?>
							<a class="dropdown-item" href="#" data-toggle="modal" data-target="#lo-dot-modal"><i class="fas fa-edit mr-2"></i>Sửa thông tin đợt</a>
							<div class="dropdown-divider"></div>
							<h6 class="dropdown-header">Chuyển trạng thái</h6>
							<?php foreach($loStatus as $k => $s): if($k === $loSt) continue; ?>
							<form method="post" action="index.php?com=daotao&amp;act=lichonStatus">
								<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id" value="<?=$loId?>"><input type="hidden" name="trang_thai" value="<?=$k?>">
								<button type="submit" class="dropdown-item"><i class="fas <?=$s['icon']?> mr-2 text-<?=$s['badge']?>"></i><?=$s['label']?></button>
							</form>
							<?php endforeach; ?>
							<?php endif; ?>
							<?php if($loCanDelete): ?>
							<div class="dropdown-divider"></div>
							<form method="post" action="index.php?com=daotao&amp;act=lichonDelete" onsubmit="return confirm(<?=$loJs('Xóa đợt ôn '.lo_dot_title($lo_dot).' cùng toàn bộ cấu hình, đăng ký và lịch đã xếp?')?>)">
								<input type="hidden" name="_csrf" value="<?=$loCsrf?>"><input type="hidden" name="id" value="<?=$loId?>">
								<button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash mr-2"></i>Xóa đợt ôn</button>
							</form>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<ol class="lo-steps">
				<?php $loIdx = array_search($loSt, array_keys($loStatus), true); $i = 0; foreach($loStatus as $k => $s): ?>
				<li class="<?=$i < $loIdx ? 'is-done' : ($i === $loIdx ? 'is-current' : '')?>">
					<span class="lo-step-dot"><?=$i < $loIdx ? '<i class="fas fa-check"></i>' : $i + 1?></span>
					<div><strong><?=$s['label']?></strong><small><?=$s['hint']?></small></div>
				</li>
				<?php $i++; endforeach; ?>
			</ol>
		</div>
	</div>

	<!-- Tổng quan giờ -->
	<div class="row lo-stats">
		<div class="col-6 col-lg-3"><div class="lo-stat"><span class="lo-stat-icon bg-info"><i class="fas fa-car"></i></span><div><b><?=number_format($loTotal['san'])?></b><small>Giờ sân (<?=count($loDays)?> ngày)</small></div></div></div>
		<div class="col-6 col-lg-3"><div class="lo-stat"><span class="lo-stat-icon bg-indigo"><i class="fas fa-user-edit"></i></span><div><b><?=number_format($loTotal['dk'])?></b><small>Giờ đăng ký (<?=$loGvCount?> GV)</small></div></div></div>
		<div class="col-6 col-lg-3"><div class="lo-stat"><span class="lo-stat-icon bg-success"><i class="fas fa-calendar-check"></i></span><div><b><?=number_format($loTotal['xep'])?></b><small>Giờ đã xếp</small></div></div></div>
		<div class="col-6 col-lg-3"><div class="lo-stat <?=$loTotal['thieu'] ? 'is-alert' : ''?>"><span class="lo-stat-icon <?=$loTotal['thieu'] ? 'bg-danger' : 'bg-secondary'?>"><i class="fas fa-hourglass-half"></i></span><div><b><?=number_format($loTotal['thieu'])?></b><small>Giờ chưa xếp</small></div></div></div>
	</div>
	<?php if($lo_cap): ?>
	<div class="lo-cap-row">
		<?php foreach($lo_cap as $hang => $c): $over = $c['da_dk'] > $c['tong_gio']; $pDk = $c['tong_gio'] ? min(100, round($c['da_dk'] * 100 / $c['tong_gio'])) : ($c['da_dk'] ? 100 : 0); $pX = $c['tong_gio'] ? min(100, round($c['da_xep'] * 100 / $c['tong_gio'])) : 0; ?>
		<div class="lo-cap <?=$over ? 'is-over' : ''?>">
			<div class="d-flex justify-content-between"><strong><?=$loEsc($hang)?></strong><span class="small <?=$over ? 'text-danger font-weight-bold' : 'text-muted'?>"><?=$over ? 'Vượt '.($c['da_dk'] - $c['tong_gio']).' giờ' : 'Còn trống '.($c['tong_gio'] - $c['da_dk']).' giờ'?></span></div>
			<div class="progress lo-cap-bar" title="Đã xếp <?=$c['da_xep']?> · Đăng ký <?=$c['da_dk']?> · Giờ sân <?=$c['tong_gio']?>">
				<div class="progress-bar bg-success" style="width:<?=$pX?>%"></div>
				<div class="progress-bar <?=$over ? 'bg-danger' : 'bg-info'?> progress-bar-striped" style="width:<?=max(0, $pDk - $pX)?>%"></div>
			</div>
			<div class="small text-muted"><span class="lo-dot-legend bg-success"></span>Xếp <?=$c['da_xep']?> · <span class="lo-dot-legend bg-info"></span>Đăng ký <?=$c['da_dk']?> / <?=$c['tong_gio']?> giờ sân</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<!-- Tabs -->
	<ul class="nav nav-tabs lo-tabs" role="tablist">
		<li class="nav-item"><a class="nav-link <?=$lo_tab==='cauhinh'?'active':''?>" href="<?=$loEsc($loTabUrl('cauhinh'))?>"><span class="lo-tab-num">1</span>Ngày tập &amp; xe <span class="badge badge-light border"><?=count($lo_ngay)?></span></a></li>
		<li class="nav-item"><a class="nav-link <?=$lo_tab==='dangky'?'active':''?>" href="<?=$loEsc($loTabUrl('dangky'))?>"><span class="lo-tab-num">2</span>Đăng ký giáo viên <span class="badge badge-light border"><?=count($lo_dk)?></span></a></li>
		<li class="nav-item"><a class="nav-link <?=$lo_tab==='xeplich'?'active':''?>" href="<?=$loEsc($loTabUrl('xeplich'))?>"><span class="lo-tab-num">3</span>Xếp lịch <?php if($loTotal['thieu']): ?><span class="badge badge-danger"><?=$loTotal['thieu']?>h thiếu</span><?php elseif($loTotal['dk']): ?><span class="badge badge-success"><i class="fas fa-check"></i></span><?php endif; ?></a></li>
	</ul>
	<div class="lo-tab-body">
	<?php if($lo_tab === 'cauhinh'): include __DIR__.'/tab_cauhinh.php';
	elseif($lo_tab === 'dangky'): include __DIR__.'/tab_dangky.php';
	else: include __DIR__.'/tab_xeplich.php'; endif; ?>
	</div>
</div>
</section>
<?php if($loCanEdit) include __DIR__.'/dot_form_modal.php'; ?>
