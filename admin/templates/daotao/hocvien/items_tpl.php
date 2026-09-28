<?php if(!defined('SOURCES')) die("Error"); ?>
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
			<h3 class="card-title"><strong>Danh sách học viên</strong></h3>
			<div class="card-tools">
				<a href="index.php?com=daotao&act=crudEdit&entity=hocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a>
				<a href="index.php?com=daotao&act=uploadHocvien<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import học viên</a>
				<a href="index.php?com=daotao&act=crudDeleteAll&entity=hocvien" onclick="return confirm('Xóa toàn bộ học viên và kết quả đào tạo liên quan?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a>
			</div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="hocvien">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Tất cả khóa —</option>
					<?php foreach($ds_khoa as $k): ?>
					<option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Học viên</th><th>Thông tin đào tạo</th><th>Người giới thiệu</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
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
						<td class="dt-sub"><?=htmlspecialchars($it['nguoi_gioithieu'] ?: '—')?></td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=crudEdit&entity=hocvien&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=hocvien&id=<?=$it['id']?>" onclick="return confirm('Xóa học viên và kết quả liên quan?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="4" class="text-center text-muted p-3">Chưa có học viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
