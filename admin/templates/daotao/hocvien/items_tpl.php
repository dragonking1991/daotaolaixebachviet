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
				<a href="index.php?com=daotao&act=crudEdit&entity=hocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a>
				<a href="index.php?com=daotao&act=uploadHocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import học viên</a>
				<a href="<?=dt_export_link('exportHocvien', array('id_khoa','keyword','hang','gv','he'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a>
				<a href="index.php?com=daotao&act=crudDeleteAll&entity=hocvien" onclick="return confirm('Xóa toàn bộ học viên và kết quả đào tạo liên quan?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a>
			</div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="hocvien">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Tất cả khóa —</option>
					<?php foreach($ds_khoa as $k): ?>
					<option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'].' — '.(int)$k['so_hoc_vien'].' học viên')?></option>
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
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Học viên</th><th>Thông tin đào tạo</th><th>Tiến độ</th><th class="text-center">Điều kiện dự thi</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $s=isset($it['sum'])?$it['sum']:null;
						$done=0; if($s){ $done=($s['ly_thuyet']['dat']?1:0)+($s['cabin']['dat']?1:0)+($s['hinh']['dat']?1:0)+($s['dat']['dat']?1:0); }
						$pct=$s?round($done/4*100):0; ?>
					<tr>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['hoten'])?></div>
							<div class="dt-sub">
								<i class="fas fa-id-badge"></i><?=htmlspecialchars($it['ma_hv'] ?: '—')?>
								<span class="dt-sep">•</span><i class="fas fa-id-card"></i><?=htmlspecialchars($it['cccd'] ?: '—')?>
								<?php if(!empty($it['ngaysinh'])): ?><span class="dt-sep">•</span><i class="fas fa-birthday-cake"></i><?=htmlspecialchars($it['ngaysinh'])?><?php endif; ?>
							</div>
						</td>
						<td>
							<div><?php if(!empty($it['hang'])): ?><span class="dt-tag"><?=htmlspecialchars($it['hang'])?></span><?php endif; ?> <?php if(!empty($it['ma_khoa'])): ?><span class="dt-tag is-muted"><?=htmlspecialchars($it['ma_khoa'])?></span><?php endif; ?></div>
							<div class="dt-sub"><i class="fas fa-chalkboard-teacher"></i>GV: <?=htmlspecialchars($it['gv_hoten'] ?: '—')?></div>
						</td>
						<td>
							<div class="dt-prog"><div class="dt-prog-bar"><span style="width:<?=$pct?>%"></span></div><span class="dt-prog-num"><?=$done?>/4</span></div>
							<?php if($s): ?><div class="dt-sub mt-1">
								<span class="badge badge-<?=$s['ly_thuyet']['dat']?'success':'secondary'?>">LT</span>
								<span class="badge badge-<?=$s['cabin']['dat']?'success':'secondary'?>">Cabin</span>
								<span class="badge badge-<?=$s['hinh']['dat']?'success':'secondary'?>">Hình</span>
								<span class="badge badge-<?=$s['dat']['dat']?'success':'secondary'?>">DAT</span>
							</div><?php endif; ?>
						</td>
						<td class="text-center"><?php if($s && $s['du_dieu_kien']): ?><span class="badge badge-success">Đủ ĐK</span><?php else: ?><span class="badge badge-danger">Chưa đủ</span><?php endif; ?></td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=crudEdit&entity=hocvien&id=<?=$it['id']?><?=$dt_ret?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=hocvien&id=<?=$it['id']?><?=$dt_ret?>" class="btn btn-xs bg-gradient-danger dt-del" data-name="<?=htmlspecialchars($it['hoten'])?>"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="dt-empty"><div class="dt-empty-box"><i class="fas fa-user-graduate"></i><div>Chưa có học viên trong danh sách</div><a href="index.php?com=daotao&act=uploadHocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success mt-2"><i class="fas fa-upload mr-1"></i>Import học viên</a></div></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
