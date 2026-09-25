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
			<div class="card-tools"><a href="index.php?com=daotao&act=uploadXe" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import xe</a></div>
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
			<table class="table table-bordered table-sm table-hover mb-0">
				<thead><tr><th>Biển số</th><th>Hạng xe</th><th>Loại</th><th>Nhãn hiệu</th><th>Số khung</th><th>Giáo viên</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><strong><?=htmlspecialchars($it['bien_so'])?></strong></td>
						<td><?=htmlspecialchars($it['hang_xe'])?></td>
						<td><?=htmlspecialchars($it['loai_xe'])?></td>
						<td><?=htmlspecialchars($it['nhan_hieu'])?></td>
						<td><?=htmlspecialchars($it['so_khung'])?></td>
						<td><?=htmlspecialchars($it['gv_hoten'])?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="6" class="text-center text-muted p-3">Chưa có xe</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
