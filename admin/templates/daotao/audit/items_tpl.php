<?php if(!defined('SOURCES')) die("Error"); $rows = isset($dt_audit_rows)?$dt_audit_rows:array(); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=dashboard">Đào tạo</a></li>
			<li class="breadcrumb-item active">Nhật ký thao tác</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-secondary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Nhật ký thao tác</strong> <span class="dt-count"><?=number_format(count($rows))?></span></h3>
		</div>
		<div class="card-body pb-0">
			<form method="get" class="form-inline mb-0">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="audit">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2 mb-1" placeholder="Chi tiết / người dùng" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="flt_action" class="form-control form-control-sm mr-2 mb-1">
					<option value="">— Hành động —</option>
					<?php foreach((isset($dt_audit_actions)?$dt_audit_actions:array()) as $a): ?><option value="<?=htmlspecialchars($a)?>" <?=(isset($_REQUEST['flt_action'])&&$_REQUEST['flt_action']==$a)?'selected':''?>><?=htmlspecialchars($a)?></option><?php endforeach; ?>
				</select>
				<select name="flt_entity" class="form-control form-control-sm mr-2 mb-1">
					<option value="">— Đối tượng —</option>
					<?php foreach((isset($dt_audit_entities)?$dt_audit_entities:array()) as $e): ?><option value="<?=htmlspecialchars($e)?>" <?=(isset($_REQUEST['flt_entity'])&&$_REQUEST['flt_entity']==$e)?'selected':''?>><?=htmlspecialchars($e)?></option><?php endforeach; ?>
				</select>
				<button class="btn btn-sm bg-gradient-primary mb-1 mr-2">Lọc</button>
				<a href="index.php?com=daotao&act=audit" class="btn btn-sm btn-outline-secondary mb-1">Xóa lọc</a>
			</form>
		</div>
		<div class="card-body p-0">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Thời gian</th><th>Hành động</th><th>Chi tiết</th><th>Người dùng</th><th>IP</th></tr></thead>
				<tbody>
				<?php if(!empty($rows)): foreach($rows as $r): ?>
					<tr>
						<td class="dt-sub"><i class="far fa-clock"></i><?=$r['ngaytao'] ? date('d/m/Y H:i', (int)$r['ngaytao']) : '—'?></td>
						<td><span class="dt-tag"><?=htmlspecialchars($r['action'])?></span>
							<div class="dt-sub"><?=htmlspecialchars($r['entity'])?><?=((int)$r['affected'])?' · '.(int)$r['affected'].' dòng':''?></div></td>
						<td class="dt-sub" style="max-width:420px;overflow-wrap:anywhere;"><?=htmlspecialchars($r['detail'] ?: '—')?></td>
						<td class="dt-sub"><?=htmlspecialchars($r['user'] ?: '—')?></td>
						<td class="dt-sub"><?=htmlspecialchars($r['ip'] ?: '—')?></td>
					</tr>
				<?php endforeach; else: ?>
					<tr><td colspan="5"><div class="dt-empty-box"><i class="fas fa-clipboard-list"></i> Chưa có thao tác nào được ghi nhận.</div></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
