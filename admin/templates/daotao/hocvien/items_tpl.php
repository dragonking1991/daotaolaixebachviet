<?php if(!defined('SOURCES')) die("Error");
	$dt_ret = ''; $__q = array();
	foreach(array('id_khoa','keyword','p') as $__k) if(isset($_GET[$__k]) && $_GET[$__k] !== '') $__q[$__k] = $_GET[$__k];
	if($__q) $dt_ret = '&'.http_build_query($__q);
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Học viên</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách học viên</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools">
				<?php if(dt_can('hocvien','edit')): ?><a href="index.php?com=daotao&act=crudEdit&entity=hocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a><?php endif; ?>
				<?php if(dt_can('hocvien','import')): ?><a href="index.php?com=daotao&act=uploadHocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import học viên</a> <a href="index.php?com=daotao&act=importHistory" class="btn btn-sm btn-link px-1" title="Lịch sử import, tải lỗi, hoàn tác"><i class="fas fa-history mr-1"></i>Lịch sử import</a><?php endif; ?>
				<?php if(dt_can('hocvien','export')): ?><a href="<?=dt_export_link('exportHocvien', array('id_khoa','keyword','hang','gv','he'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a><?php endif; ?>
				<?php if(dt_can('hocvien','delete')): ?><button type="submit" form="dt-bulk-hocvien" class="btn btn-sm bg-gradient-danger dt-bulk-delete"><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button><?php endif; ?>
			</div>
		</div>
		<div class="card-body">
			<?php if(dt_can('hocvien','delete')): ?><form id="dt-bulk-hocvien" method="post" action="index.php?com=daotao&act=crudDeleteSelected&entity=hocvien<?=$dt_ret?>"></form><?php endif; ?>
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="hocvien">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Tất cả khóa —</option>
					<option value="-1" <?=$id_khoa_sel===-1?'selected':''?>>Chưa gán khóa</option>
					<?php foreach($ds_khoa as $k): ?>
					<option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars(course_option_label($k))?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="hang" class="form-control form-control-sm mr-2">
					<option value="">— Hạng —</option>
					<?php foreach(array('B11','B1','C1','C','CE') as $h): ?><option value="<?=$h?>" <?=(isset($_REQUEST['hang'])&&$_REQUEST['hang']==$h)?'selected':''?>><?=$h?></option><?php endforeach; ?>
				</select>
				<input type="text" name="gv" class="form-control form-control-sm mr-2" placeholder="Giáo viên" value="<?=isset($_REQUEST['gv'])?htmlspecialchars($_REQUEST['gv']):''?>">
				<input type="text" name="he" class="form-control form-control-sm mr-2" placeholder="Hệ đào tạo" value="<?=isset($_REQUEST['he'])?htmlspecialchars($_REQUEST['he']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<?php if($id_khoa_sel===-1 && $total_items>0 && dt_is_admin()): ?><form method="post" action="index.php?com=daotao&act=deleteUnassignedHocvien" class="mb-3" onsubmit="return confirm('Xóa toàn bộ <?=$total_items?> hồ sơ chưa gán khóa? Kết quả sát hạch và dữ liệu nguồn sẽ được giữ lại. Hệ thống sẽ sao lưu trước khi xóa.');"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_hocvien_unassigned_csrf'])?>"><button class="btn btn-sm btn-danger"><i class="fas fa-trash mr-1"></i>Xóa tất cả học viên chưa gán khóa</button></form><?php endif; ?>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th class="text-center" style="width:34px"><?php if(dt_can('hocvien','delete')): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-bulk-hocvien" title="Chọn tất cả"><?php endif; ?></th><th>Học viên</th><th>Thông tin đào tạo</th><th>Tiến độ</th><th class="text-center">Điều kiện dự thi</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $s=isset($it['sum'])?$it['sum']:null;
						$done=0; if($s){ $done=($s['ly_thuyet']['dat']?1:0)+(dt_cabin_pass($s['cabin'])?1:0)+($s['hinh']['dat']?1:0)+($s['dat']['dat']?1:0); }
						$pct=$s?round($done/4*100):0; ?>
					<tr>
						<td class="text-center"><?php if(dt_can('hocvien','delete')): ?><input type="checkbox" class="dt-bulk-item" form="dt-bulk-hocvien" name="ids[]" value="<?=$it['id']?>"><?php endif; ?></td>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['hoten'])?></div>
							<div class="dt-sub">
								<i class="fas fa-id-badge"></i><?=htmlspecialchars($it['ma_hv'] ?: '—')?>
								<span class="dt-sep">•</span><i class="fas fa-id-card"></i><?=htmlspecialchars($it['cccd'] ?: '—')?>
								<?php if(!empty($it['ngaysinh'])): ?><span class="dt-sep">•</span><i class="fas fa-birthday-cake"></i><?=htmlspecialchars($it['ngaysinh'])?><?php endif; ?>
							</div>
						</td>
						<td>
							<div class="d-flex flex-wrap align-items-center">
								<?php if(!empty($it['hang'])): ?><span class="dt-tag mr-1" title="Hạng đào tạo"><?=htmlspecialchars($it['hang'])?></span><?php endif; ?>
								<?php if(!empty($it['ma_khoa']) || trim((string)($it['ten_khoa'] ?? '')) !== ''): ?>
								<span class="dt-tag is-muted" style="white-space:normal;overflow-wrap:anywhere" title="Mã khóa — Tên khóa"><i class="fas fa-book-open mr-1" aria-hidden="true"></i><?=htmlspecialchars($it['ma_khoa'] ?? '')?><?php if(!empty($it['ma_khoa']) && trim((string)($it['ten_khoa'] ?? '')) !== ''): ?> — <?php endif; ?><?=htmlspecialchars($it['ten_khoa'] ?? '')?></span>
								<?php elseif((int)$it['id_khoa']===0): ?><span class="badge badge-warning">Chưa gán khóa</span><?php endif; ?>
							</div>
							<div class="dt-sub d-flex flex-wrap align-items-center">
								<span class="mr-3" title="Hệ đào tạo"><i class="fas fa-graduation-cap" aria-hidden="true"></i><span class="sr-only">Hệ đào tạo: </span><?=htmlspecialchars(trim((string)($it['he_daotao'] ?? '')) ?: '—')?></span>
								<span title="Giáo viên"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i><span class="sr-only">Giáo viên: </span><?=htmlspecialchars($it['gv_hoten'] ?: '—')?></span>
							</div>
						</td>
						<td>
								<?php if((int)$it['id_khoa']>0): ?><div class="dt-prog"><div class="dt-prog-bar"><span style="width:<?=$pct?>%"></span></div><span class="dt-prog-num"><?=$done?>/4</span></div><?php else: ?><span class="text-muted">Chưa có dữ liệu đào tạo</span><?php endif; ?>
							<?php if($s): $__kq = ($it['id_khoa']?'&id_khoa='.(int)$it['id_khoa']:'').'&keyword='.urlencode($it['cccd'] ?: $it['ma_hv']); ?><div class="dt-sub mt-1">
								<a href="index.php?com=daotao&act=lythuyet<?=$__kq?>" class="badge badge-<?=$s['ly_thuyet']['dat']?'success':'secondary'?>" title="Xem lý thuyết">LT</a>
								<a href="index.php?com=daotao&act=cabinkq<?=$__kq?>" class="badge badge-<?=$s['cabin']['dat']?'success':(empty($s['cabin']['yeu_cau'])?'info':'secondary')?>" title="<?=empty($s['cabin']['yeu_cau'])?'Nâng hạng — không yêu cầu cabin':'Xem cabin'?>">Cabin</a>
								<a href="index.php?com=daotao&act=hinh<?=$__kq?>" class="badge badge-<?=$s['hinh']['dat']?'success':'secondary'?>" title="Xem thực hành hình">Hình</a>
								<a href="index.php?com=daotao&act=dat<?=$__kq?>" class="badge badge-<?=$s['dat']['dat']?'success':'secondary'?>" title="Xem DAT">DAT</a>
							</div><?php endif; ?>
						</td>
						<td class="text-center"><?php if((int)$it['id_khoa']===0): ?><span class="badge badge-secondary">Chờ gán khóa</span><?php elseif($s && $s['du_dieu_kien']): ?><span class="badge badge-success">Đủ ĐK</span><?php else: ?><span class="badge badge-danger">Chưa đủ</span><?php endif; ?></td>
						<td class="dt-actions"><?php if(dt_can('hocvien','edit')): ?><a href="index.php?com=daotao&act=crudEdit&entity=hocvien&id=<?=$it['id']?><?=$dt_ret?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a><?php endif; ?> <?php if(dt_can('hocvien','delete')): ?><a href="index.php?com=daotao&act=crudDelete&entity=hocvien&id=<?=$it['id']?><?=$dt_ret?>" class="btn btn-xs bg-gradient-danger dt-del" data-name="<?=htmlspecialchars($it['hoten'])?>"><i class="fas fa-trash"></i></a><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="6" class="dt-empty"><div class="dt-empty-box"><i class="fas fa-user-graduate"></i><div>Chưa có học viên trong danh sách</div><a href="index.php?com=daotao&act=uploadHocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success mt-2"><i class="fas fa-upload mr-1"></i>Import học viên</a></div></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
