<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Số giấy HTKH</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Số giấy chứng nhận HTKH</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><?php if(dt_can('htkh','import')): ?><a href="index.php?com=daotao&act=uploadHtkh<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</a> <?php endif; ?><?php if($id_khoa_sel && dt_can('htkh','view')): ?><a href="index.php?com=daotao&act=htkhPrint&id_khoa=<?=$id_khoa_sel?>" target="_blank" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-print mr-1"></i>In giấy</a> <?php endif; ?><?php if(dt_can('htkh','export')): ?><a href="<?=dt_export_link('exportHtkh', array('id_khoa','keyword'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a><?php endif; ?></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="htkh">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'].' — '.(int)$k['so_hoc_vien'].' học viên')?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<?php if(!$id_khoa_sel && empty($items)): ?>
			<div class="alert alert-info mb-0">Chọn khóa để nhập/cập nhật số giấy HTKH, hoặc nhập CCCD/tên để tra cứu.</div>
			<?php else: ?>
			<?php if($id_khoa_sel && dt_can('htkh','edit')): ?>
			<form method="post" action="index.php?com=daotao&act=htkhCapGiay&id_khoa=<?=$id_khoa_sel?>" class="form-inline mb-2 p-2" style="background:#f1f5f9;border-radius:6px;" onsubmit="return confirm('Cấp giấy HTKH (sinh số giấy + ghi người/ngày ký) cho học viên trong khóa?');">
				<span class="mr-2"><strong>Cấp giấy hàng loạt:</strong></span>
				<input type="text" name="nguoi_ky" class="form-control form-control-sm mr-2" placeholder="Người ký" style="width:200px">
				<input type="text" name="ngay_ky" class="form-control form-control-sm mr-2" placeholder="Ngày ký (dd/mm/yyyy)" style="width:160px">
				<label class="mr-2 mb-0"><input type="checkbox" name="tat_ca" value="1"> Cấp cho tất cả (bỏ qua điều kiện)</label>
				<button class="btn btn-sm bg-gradient-warning"><i class="fas fa-certificate mr-1"></i>Cấp giấy cho HV đủ điều kiện</button>
			</form>
			<?php endif; ?>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Họ và tên</th><th>CCCD</th><th>Hạng</th><th style="width:520px">Số giấy HTKH / Ngày cấp</th><th>Ký</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $h=$it['htkh']; $autoSo = dt_htkh_so_giay($it['ma_hv'], $it['hang']); ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?> <span class="badge badge-light border" title="Hạng in trên giấy"><?=htmlspecialchars(dt_htkh_hang_quydoi($it['hang']))?></span></td>
						<td>
							<form method="post" action="index.php?com=daotao&act=saveHtkh&id_khoa=<?=(int)$it['id_khoa']?>" style="display:flex;gap:6px;align-items:center;margin:0;">
								<input type="hidden" name="cccd" value="<?=htmlspecialchars($it['cccd'])?>">
								<input type="text" name="so_giay" class="form-control form-control-sm" style="width:200px" placeholder="<?=htmlspecialchars($autoSo)?>" value="<?=$h?htmlspecialchars($h['so_giay']):''?>" title="Để trống sẽ tự sinh: <?=htmlspecialchars($autoSo)?>">
								<input type="text" name="ngay_cap" class="form-control form-control-sm" style="width:120px" placeholder="dd/mm/yyyy" value="<?=$h?htmlspecialchars($h['ngay_cap']):''?>">
								<input type="text" name="ghi_chu" class="form-control form-control-sm" style="width:110px" placeholder="Ghi chú" value="<?=$h?htmlspecialchars($h['ghi_chu']):''?>">
								<button class="btn btn-xs bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu</button>
							</form>
							<?php if($h && (trim((string)(isset($h['nguoi_ky'])?$h['nguoi_ky']:'')) !== '' || trim((string)(isset($h['ngay_ky'])?$h['ngay_ky']:'')) !== '')): ?><div class="dt-sub"><i class="fas fa-signature"></i> <?=htmlspecialchars(isset($h['nguoi_ky'])?$h['nguoi_ky']:'')?><?php if(!empty($h['ngay_ky'])): ?> <span class="dt-sep">•</span> <?=htmlspecialchars($h['ngay_ky'])?><?php endif; ?></div><?php endif; ?>
						</td>
						<td class="text-center"><?php if($h && trim((string)$h['so_giay'])!==''): ?><span class="badge badge-success">Đã có</span><?php else: ?><span class="badge badge-secondary">Chưa</span><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="text-center text-muted p-3">Chưa có học viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>
</section>
