<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Kết quả sát hạch</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Kết quả sát hạch</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=sathachTracuu" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-search mr-1"></i>Tra cứu</a> <a href="index.php?com=daotao&act=uploadSathach<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</a> <a href="<?=dt_export_link('exportSathach', array('id_khoa','keyword'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="sathach">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV / Số GPLX" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<?php if(!$id_khoa_sel && empty($items)): ?>
			<div class="alert alert-info mb-0">Chọn khóa hoặc nhập CCCD/tên để xem kết quả sát hạch.</div>
			<?php else: ?>
			<div class="table-responsive">
			<table class="table dt-list table-hover table-bordered mb-0" style="white-space:nowrap;">
				<thead><tr><th>Họ tên</th><th>CCCD</th><th>Hạng</th><th>Mã khóa</th><th>Ngày SH</th><th>Số GPLX</th><th>Kết quả</th><th>Ghi chú</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars($it['ma_khoa'])?></td>
						<td><?=htmlspecialchars($it['ngay_sh'])?></td>
						<td><?=htmlspecialchars($it['so_gplx'])?></td>
						<td><?php if($it['ket_qua']!==''): ?><span class="badge badge-<?=($it['ket_qua']==='Đạt')?'success':'danger'?>"><?=htmlspecialchars($it['ket_qua'])?></span><?php else: ?>—<?php endif; ?></td>
						<td><?=htmlspecialchars($it['ghi_chu'])?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="8" class="text-center text-muted p-3">Chưa có kết quả sát hạch</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
