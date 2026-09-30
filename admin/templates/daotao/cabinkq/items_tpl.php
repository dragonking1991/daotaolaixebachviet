<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Cabin (kết quả)</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Kết quả cabin</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=cabinkq" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <a href="index.php?com=daotao&act=uploadCabin<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import cabin</a> <a href="<?=dt_export_link('exportCabin', array('id_khoa','keyword','trang_thai'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=cabinkq" onclick="return confirm('Xóa toàn bộ kết quả cabin?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="cabinkq">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Tất cả khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2">
					<option value="">— Trạng thái —</option>
					<option value="dat" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='dat')?'selected':''?>>Đạt</option>
					<option value="chua" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='chua')?'selected':''?>>Chưa đạt</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Học viên</th><th>Kết quả cabin</th><th class="text-center">Trạng thái</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['hoten'] ?: $it['ma_hv'])?></div>
							<div class="dt-sub"><i class="fas fa-id-badge"></i><?=htmlspecialchars($it['ma_hv'] ?: '—')?></div>
						</td>
						<td>
							<div class="dt-sub">Tổng thời gian: <strong><?=htmlspecialchars($it['tong_thoigian'] ?: '—')?></strong> <span class="dt-sep">•</span> Số nội dung: <strong><?=(int)$it['so_noidung']?></strong></div>
							<?php if(!empty($it['ghi_chu'])): ?><div class="dt-sub"><i class="fas fa-comment-dots"></i><?=htmlspecialchars($it['ghi_chu'])?></div><?php endif; ?>
						</td>
						<td class="text-center"><?php if((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-danger">Không đạt</span><?php endif; ?></td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=crudEdit&entity=cabinkq&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=cabinkq&id=<?=$it['id']?>" onclick="return confirm('Xóa kết quả cabin này?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="4" class="text-center text-muted p-3">Chưa có kết quả cabin</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
