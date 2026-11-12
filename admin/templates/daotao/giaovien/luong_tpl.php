<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=giaovien">Giáo viên</a></li>
			<li class="breadcrumb-item active">Phiếu lương</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Phiếu lương — <?=htmlspecialchars($gv['hoten'])?></strong> <span class="text-muted ml-1">CCCD: <?=htmlspecialchars($gv['cccd'])?></span></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=giaovien" class="btn btn-sm btn-default"><i class="fas fa-arrow-left mr-1"></i>Danh sách giáo viên</a></div>
		</div>
		<div class="card-body">
			<?php if(empty($gv_luong)): ?>
				<div class="text-muted p-3 text-center">Chưa có dữ liệu lương cho CCCD này. Import file lương (sheet GVTH/CTV) tại Quản lý nhân viên → Import.</div>
			<?php else: $rec = $gv_luong['record']; ?>
			<div class="row">
				<div class="col-lg-7">
					<form method="get" class="form-inline mb-3">
						<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="gvLuong"><input type="hidden" name="id" value="<?=(int)$gv['id']?>">
						<label class="mr-2 mb-0">Tháng lương</label>
						<select name="thang" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
							<?php foreach($gv_luong['months'] as $m): ?><option value="<?=htmlspecialchars($m)?>" <?=$m===$rec['thang']?'selected':''?>><?=htmlspecialchars(payrollMonthlyLabel($m))?></option><?php endforeach; ?>
						</select>
						<span class="badge badge-light border"><?=htmlspecialchars(payrollMonthlyDepartmentLabel($rec['bo_phan']))?> (sheet <?=htmlspecialchars($rec['sheet'])?>)</span>
					</form>
					<table class="table table-sm table-bordered mb-2">
						<tbody>
							<?php foreach($gv_luong['fields'] as $f): $isTotal = isset($f['key']) && $f['key'] === 'thuc_linh'; ?>
							<tr<?=$isTotal ? ' class="table-success font-weight-bold"' : ''?>>
								<td style="width:60%"><?=htmlspecialchars((string)$f['label'])?></td>
								<td class="text-right"><?=htmlspecialchars(payrollMonthlyFormatValue(isset($f['value']) ? $f['value'] : null, isset($f['type']) ? $f['type'] : 'money'))?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<div class="text-muted">File: <?=htmlspecialchars($rec['file_name'])?><?php if(!empty($rec['ngaytao'])): ?> • import lúc <?=date('d/m/Y H:i', (int)$rec['ngaytao'])?><?php endif; ?></div>
				</div>
				<div class="col-lg-5">
					<h6 class="font-weight-bold">Các tháng đã có</h6>
					<table class="table table-sm table-hover mb-0">
						<thead><tr><th>Tháng</th><th>Sheet</th><th class="text-right">Thực lĩnh</th></tr></thead>
						<tbody>
							<?php foreach($gv_luong_history as $h): ?>
							<tr<?=$h['thang']===$rec['thang'] ? ' class="table-active"' : ''?>>
								<td><a href="index.php?com=daotao&amp;act=gvLuong&amp;id=<?=(int)$gv['id']?>&amp;thang=<?=urlencode($h['thang'])?>"><?=htmlspecialchars(payrollMonthlyLabel($h['thang']))?></a></td>
								<td><?=htmlspecialchars($h['sheet'])?></td>
								<td class="text-right"><?=htmlspecialchars(payrollMonthlyFormatValue($h['thuc_linh'], 'money'))?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
