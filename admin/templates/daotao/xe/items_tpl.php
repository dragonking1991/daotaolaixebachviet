<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Xe tập lái</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách xe tập lái</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=xe" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <a href="index.php?com=daotao&act=uploadXe" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import xe</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=xe" onclick="return confirm('Xóa toàn bộ xe tập lái?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="xe">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Biển số / Giáo viên" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="hang" class="form-control form-control-sm mr-2">
					<option value="">— Hạng —</option>
					<?php foreach(array('B11','B1','C1','C','CE') as $h): ?><option value="<?=$h?>" <?=(isset($_REQUEST['hang'])&&$_REQUEST['hang']==$h)?'selected':''?>><?=$h?></option><?php endforeach; ?>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Xe tập lái</th><th>Thông số</th><th>Giáo viên</th><th class="dt-actions">Hành động</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['bien_so'])?></div>
							<div class="dt-sub"><i class="fas fa-car"></i><?=htmlspecialchars($it['nhan_hieu'] ?: '—')?></div>
						</td>
						<td>
							<div><?php if(!empty($it['hang_xe'])): ?><span class="dt-tag"><?=htmlspecialchars($it['hang_xe'])?></span><?php endif; ?> <?php if(!empty($it['loai_xe'])): ?><span class="dt-tag is-muted">Loại <?=htmlspecialchars($it['loai_xe'])?></span><?php endif; ?></div>
							<div class="dt-sub"><i class="fas fa-hashtag"></i>Số khung: <strong><?=htmlspecialchars($it['so_khung'] ?: '—')?></strong></div>
						</td>
						<td class="dt-sub"><i class="fas fa-chalkboard-teacher"></i><?=htmlspecialchars($it['gv_hoten'] ?: '—')?></td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=crudEdit&entity=xe&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=xe&id=<?=$it['id']?>" onclick="return confirm('Xóa xe này?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="4" class="text-center text-muted p-3">Chưa có xe</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
