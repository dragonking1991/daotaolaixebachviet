<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">DAT (thực hành trên đường)</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Tổng hợp DAT</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=dat" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm phiên</a> <a href="index.php?com=daotao&act=uploadDat<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import DAT</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=dat" onclick="return confirm('Xóa toàn bộ phiên DAT?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="dat">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
			</form>
			<?php if(!$id_khoa_sel): ?>
			<div class="alert alert-info mb-0">Chọn khóa để xem tổng hợp DAT.</div>
			<?php else: ?>
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Học viên</th><th>Tiến độ định mức</th><th>Phân bổ giờ &amp; phiên</th><th class="text-center">Điều kiện DAT</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it):
						$hv=$it['hv']; $a=$it['agg']; $nguong=dt_nguong_dat(); $hangDat=dt_norm_hang($hv['hang']);
						$gioMucTieu=isset($nguong[$hangDat])?(float)$nguong[$hangDat]['a']:0;
						$kmMucTieu=isset($nguong[$hangDat])?(float)$nguong[$hangDat]['km']:0;
						$pctGio=$gioMucTieu>0?min(100,(int)round($a['a']/$gioMucTieu*100)):0;
						$pctKm=$kmMucTieu>0?min(100,(int)round($a['e']/$kmMucTieu*100)):0;
					?>
					<tr>
						<td>
							<div class="dt-title"><?=htmlspecialchars($hv['hoten'])?></div>
							<div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($hv['cccd'])?><span class="dt-sep">•</span><span class="dt-tag is-muted"><?=htmlspecialchars($hv['hang'])?></span></div>
						</td>
						<td class="dt-dat-progress">
							<div class="dt-dat-progress-line"><span>Giờ thực hành</span><strong><?=$a['a']?><?php if($gioMucTieu): ?> / <?=$gioMucTieu?> giờ<?php else: ?> giờ<?php endif; ?></strong></div>
							<div class="dt-prog"><div class="dt-prog-bar"><span style="width:<?=$pctGio?>%"></span></div><span class="dt-prog-num"><?=$pctGio?>%</span></div>
							<div class="dt-dat-progress-line"><span>Quãng đường</span><strong><?=$a['e']?><?php if($kmMucTieu): ?> / <?=$kmMucTieu?> km<?php else: ?> km<?php endif; ?></strong></div>
							<div class="dt-prog"><div class="dt-prog-bar"><span style="width:<?=$pctKm?>%"></span></div><span class="dt-prog-num"><?=$pctKm?>%</span></div>
						</td>
						<td>
							<div class="dt-sub"><span class="dt-tag is-muted"><?=$it['so_phien']?> phiên</span><span class="dt-sep">•</span>Đêm <strong><?=$a['b']?> giờ</strong></div>
							<div class="dt-sub"><i class="fas fa-car"></i>Tự động <strong><?=$a['c']?> giờ</strong><span class="dt-sep">•</span>Số sàn <strong><?=$a['d']?> giờ</strong></div>
						</td>
						<td class="text-center"><?php if((int)$it['dat']===1): ?><span class="badge badge-success">Đủ điều kiện</span><?php else: ?><span class="badge badge-danger" title="<?=htmlspecialchars(implode(', ', $it['thieu']))?>">Chưa đủ</span><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="4" class="dt-empty"><div class="dt-empty-box"><i class="fas fa-road"></i><div>Chưa có dữ liệu DAT trong khóa này</div></div></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php endif; ?>
		</div>
			<?php if($id_khoa_sel && !empty($phien)): ?>
			<div class="mt-4"><h5 class="mb-2">Chi tiết phiên DAT</h5><div class="table-responsive"><table class="table dt-list table-hover mb-0"><thead><tr><th>Phiên học</th><th>Thời gian</th><th>Quãng đường &amp; xe</th><th>Giáo viên</th><th class="dt-actions">Hành động</th></tr></thead><tbody><?php foreach($phien as $p): ?><tr><td><div class="dt-title"><?=htmlspecialchars($p['ma_phien'])?></div><div class="dt-sub"><?=htmlspecialchars($p['ma_hv'])?></div></td><td><div class="dt-title"><?=!empty($p['ngay_hoc'])?date('d/m/Y',strtotime($p['ngay_hoc'])):'—'?></div><div class="dt-sub"><?=!empty($p['tg_batdau'])?date('H:i',strtotime($p['tg_batdau'])):'--:--'?> – <?=!empty($p['tg_ketthuc'])?date('H:i',strtotime($p['tg_ketthuc'])):'--:--'?></div></td><td><div class="dt-title"><?=htmlspecialchars($p['km'])?> km <span class="dt-sep">•</span><?=htmlspecialchars($p['gio_thuchanh'])?> giờ</div><div class="dt-sub"><span class="dt-tag is-muted"><?=htmlspecialchars($p['bien_so']?:'Chưa gán xe')?></span> <?=!empty($p['la_xe_tudong'])?'<span class="dt-tag">Tự động</span>':'<span class="dt-tag is-muted">Số sàn</span>'?></div></td><td class="dt-sub"><i class="fas fa-chalkboard-teacher"></i><?=htmlspecialchars($p['gv_hoten']?:'—')?></td><td class="dt-actions"><a href="index.php?com=daotao&act=crudEdit&entity=dat&id=<?=$p['id']?>" class="btn btn-xs bg-gradient-info" title="Sửa"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=dat&id=<?=$p['id']?>" class="btn btn-xs bg-gradient-danger dt-del" data-name="<?=htmlspecialchars($p['ma_phien'])?>" title="Xóa"><i class="fas fa-trash"></i></a></td></tr><?php endforeach; ?></tbody></table></div></div>
			<?php endif; ?>
	</div>
</section>
