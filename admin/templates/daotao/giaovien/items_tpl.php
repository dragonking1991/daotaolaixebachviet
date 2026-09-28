<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Giáo viên</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách giáo viên</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=giaovien" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <a href="index.php?com=daotao&act=uploadGiaovien" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import giáo viên</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=giaovien" onclick="return confirm('Xóa toàn bộ giáo viên?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="giaovien">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Giáo viên</th><th>GPLX &amp; chuyên môn</th><th>Đào tạo &amp; tuyển dụng</th><th>Liên hệ</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['hoten'] ?: '—')?></div>
							<div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($it['cccd'] ?: '—')?></div>
							<div class="dt-sub">
								<?php if(!empty($it['ngaysinh'])): ?><i class="fas fa-birthday-cake"></i><?=htmlspecialchars($it['ngaysinh'])?><?php endif; ?>
								<?php if(!empty($it['gioitinh'])): ?><span class="dt-sep">•</span><?=htmlspecialchars($it['gioitinh']==='F'?'Nữ':($it['gioitinh']==='M'?'Nam':$it['gioitinh']))?><?php endif; ?>
							</div>
						</td>
						<td>
							<div class="dt-sub"><i class="fas fa-id-badge"></i>GPLX: <strong><?=htmlspecialchars($it['hang_gplx'] ?: '—')?></strong><?php if(!empty($it['so_gplx'])): ?> <span class="dt-sep">•</span> Số: <?=htmlspecialchars($it['so_gplx'])?><?php endif; ?></div>
							<?php if(!empty($it['ngay_cap_gplx']) || !empty($it['ngay_hh_gplx'])): ?><div class="dt-sub"><i class="far fa-calendar-alt"></i>Cấp: <?=htmlspecialchars($it['ngay_cap_gplx'] ?: '—')?> <span class="dt-sep">→</span> HH: <?=htmlspecialchars($it['ngay_hh_gplx'] ?: '—')?></div><?php endif; ?>
							<?php if(!empty($it['trinh_do']) || !empty($it['chuyen_mon']) || !empty($it['su_pham'])): ?><div class="dt-sub"><i class="fas fa-graduation-cap"></i><?=htmlspecialchars(trim(implode(' • ', array_filter(array($it['trinh_do'], $it['chuyen_mon'], $it['su_pham'])))) ?: '—')?></div><?php endif; ?>
						</td>
						<td>
							<div><?php if(!empty($it['hang_daotao_phep'])): ?><span class="dt-tag">Được phép: <?=htmlspecialchars($it['hang_daotao_phep'])?></span><?php endif; ?> <?php if(!empty($it['loai_hinh_dt'])): ?><span class="dt-tag is-muted"><?=htmlspecialchars($it['loai_hinh_dt'])?></span><?php endif; ?></div>
							<?php if(!empty($it['hinh_thuc_td']) || !empty($it['tuyen_dung'])): ?><div class="dt-sub"><i class="fas fa-briefcase"></i><?=htmlspecialchars($it['hinh_thuc_td'] ?: '—')?><?php if(!empty($it['tuyen_dung'])): ?> <span class="dt-sep">•</span> <?=htmlspecialchars($it['tuyen_dung'])?><?php endif; ?></div><?php endif; ?>
							<?php if(!empty($it['noi_ct'])): ?><div class="dt-sub"><i class="fas fa-building"></i><?=htmlspecialchars($it['noi_ct'])?></div><?php endif; ?>
						</td>
						<td>
							<div class="dt-sub"><i class="fas fa-phone"></i><?=htmlspecialchars($it['sdt'] ?: '—')?></div>
							<?php if(!empty($it['dia_chi'])): ?><div class="dt-sub"><i class="fas fa-map-marker-alt"></i><?=htmlspecialchars($it['dia_chi'])?></div><?php endif; ?>
						</td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=crudEdit&entity=giaovien&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=gvResetPass&id=<?=$it['id']?>" onclick="return confirm('Đặt lại mật khẩu cổng giáo viên về mặc định (= CCCD)?')" class="btn btn-xs bg-gradient-warning" title="Đặt lại mật khẩu"><i class="fas fa-key"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=giaovien&id=<?=$it['id']?>" onclick="return confirm('Xóa giáo viên này?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="text-center text-muted p-3">Chưa có giáo viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
