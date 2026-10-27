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
	<?php $__ky_sel = isset($_REQUEST['id_ky']) ? (int)$_REQUEST['id_ky'] : 0; if((dt_can('htkh','edit') || dt_can('htkh','view')) && !empty($ds_ky)): ?>
	<div class="card card-warning card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong>Cấp giấy HTKH theo ngày kiểm tra</strong></h3></div>
		<div class="card-body">
			<p class="text-muted mb-2">Chọn ngày kiểm tra và khóa → nhập thông tin ký → tạo giấy cho học viên <strong>thi đạt</strong> → xuất Excel, Word hoặc in. Mỗi học viên một trang; các file và bản in dùng thông tin ký đã lưu khi tạo giấy.</p>
			<form method="get" action="index.php" class="form-inline mb-3">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="htkh">
				<label class="mr-2" for="htkh-id-ky">Ngày kiểm tra</label>
				<select id="htkh-id-ky" name="id_ky" class="form-control form-control-sm mr-2" style="max-width:100%" required onchange="this.form.elements.ky_khoa.value='';this.form.submit()">
					<option value="">— Chọn ngày kiểm tra —</option>
					<?php foreach($ds_ky as $k): ?><option value="<?=$k['id']?>" <?=$__ky_sel==$k['id']?'selected':''?>><?=htmlspecialchars((!empty($k['ngay_kt'])?date('d/m/Y',strtotime($k['ngay_kt'])).' ':'').$k['ten'])?> — <?=(int)$k['so_kq']?> có KQ</option><?php endforeach; ?>
				</select>
				<label class="mr-2" for="htkh-ky-khoa">Khóa trong kỳ</label>
				<select id="htkh-ky-khoa" name="ky_khoa" class="form-control form-control-sm mr-2" style="max-width:100%" onchange="this.form.submit()">
					<option value="">Tất cả khóa trong kỳ</option>
					<?php foreach($htkh_ky_khoa as $key => $label): ?><option value="<?=htmlspecialchars($key)?>" <?=dt_req('ky_khoa')===$key?'selected':''?>><?=htmlspecialchars($label)?></option><?php endforeach; ?>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Lọc danh sách</button>
			</form>
			<?php if($__ky_sel): ?>
			<?php if(dt_req('ky_khoa')!==''): ?>
			<form method="post" action="index.php?com=daotao&act=htkhScheduleSave" class="border-top border-bottom py-3 mb-3">
				<input type="hidden" name="id_ky" value="<?=$__ky_sel?>"><input type="hidden" name="ky_khoa" value="<?=htmlspecialchars(dt_req('ky_khoa'),ENT_QUOTES,'UTF-8')?>"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_htkh_schedule_csrf'],ENT_QUOTES,'UTF-8')?>">
				<h4 class="h6 font-weight-bold">Thời gian học: <?=htmlspecialchars($htkh_ky_khoa[dt_req('ky_khoa')],ENT_QUOTES,'UTF-8')?></h4>
				<div class="form-row align-items-end">
					<div class="form-group col-sm-4 col-lg-2 mb-2"><label for="htkh-so-ngay">Số ngày học</label><input id="htkh-so-ngay" name="so_ngay" type="number" min="1" max="10000" step="1" required class="form-control form-control-sm" value="<?=htmlspecialchars((string)$htkh_schedule['so_ngay'],ENT_QUOTES,'UTF-8')?>" <?=!dt_can('htkh','edit')?'disabled':''?>></div>
					<div class="form-group col-sm-4 col-lg-3 mb-2"><label for="htkh-tu-ngay">Từ ngày</label><input id="htkh-tu-ngay" name="tu_ngay" type="date" required class="form-control form-control-sm" value="<?=htmlspecialchars($htkh_schedule['tu_ngay'],ENT_QUOTES,'UTF-8')?>" <?=!dt_can('htkh','edit')?'disabled':''?>></div>
					<div class="form-group col-sm-4 col-lg-3 mb-2"><label for="htkh-den-ngay">Đến ngày</label><input id="htkh-den-ngay" name="den_ngay" type="date" required class="form-control form-control-sm" value="<?=htmlspecialchars($htkh_schedule['den_ngay'],ENT_QUOTES,'UTF-8')?>" <?=!dt_can('htkh','edit')?'disabled':''?>></div>
					<?php if(dt_can('htkh','edit')): ?><div class="col-lg-4 mb-2"><button class="btn btn-sm bg-gradient-primary"><i class="fas fa-save mr-1"></i>Lưu thời gian học</button></div><?php endif; ?>
				</div>
			</form>
			<?php endif; ?>
			<div class="mb-2"><strong><?=count($htkh_ky_passed)?> học viên thi đạt</strong> theo bộ lọc đã chọn.</div>
			<?php if(dt_can('htkh','edit') && !empty($htkh_ky_passed)): ?>
			<form method="post" action="index.php?com=daotao&act=htkhCapGiayKy" class="form-inline mb-3" onsubmit="return confirm('Tạo/cập nhật giấy và thông tin ký cho <?=count($htkh_ky_passed)?> học viên đạt trong bộ lọc đã chọn?');">
				<input type="hidden" name="id_ky" value="<?=$__ky_sel?>">
				<input type="hidden" name="ky_khoa" value="<?=htmlspecialchars(dt_req('ky_khoa'))?>">
				<label class="mr-2" for="htkh-nguoi-ky">Người ký</label>
				<input id="htkh-nguoi-ky" type="text" name="nguoi_ky" class="form-control form-control-sm mr-2" placeholder="Họ tên người ký" maxlength="255" required style="width:180px">
				<label class="mr-2" for="htkh-ngay-ky">Ngày ký</label>
				<input id="htkh-ngay-ky" type="text" name="ngay_ky" class="form-control form-control-sm mr-2" placeholder="dd/mm/yyyy" value="<?=date('d/m/Y')?>" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" required style="width:140px">
				<label class="mr-2" for="htkh-so-quyet-dinh">Số Quyết định</label>
				<input id="htkh-so-quyet-dinh" type="text" name="so_quyet_dinh" class="form-control form-control-sm mr-2" placeholder="Số quyết định" maxlength="100" style="width:150px">
				<select name="chuc_danh" class="form-control form-control-sm mr-2" style="width:230px" title="Chức danh người ký"><option value="kt_pgd">KT. GIÁM ĐỐC / PHÓ GIÁM ĐỐC</option><option value="gd">GIÁM ĐỐC</option></select>
				<select name="hang_ce" class="form-control form-control-sm mr-2" style="width:170px" title="Kiểu nâng hạng (chỉ áp dụng hạng CE)"><option value="">Hạng CE: C lên CE</option><option value="d">Hạng CE: D lên CE</option><option value="d2">Hạng CE: D2 lên CE</option></select>
				<button class="btn btn-sm bg-gradient-warning"><i class="fas fa-certificate mr-1"></i>Tạo giấy xác nhận</button>
			</form>
			<?php endif; ?>
			<?php if(!empty($htkh_ky_passed)): ?>
			<div class="mb-3">
				<?php if(dt_can('htkh','export')): ?>
				<a href="<?=dt_export_link('htkhExcelKy', array('id_ky','ky_khoa'))?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-file-excel mr-1"></i>Xuất giấy Excel</a>
				<a href="<?=dt_export_link('htkhWordKy', array('id_ky','ky_khoa'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-word mr-1"></i>Xuất giấy Word</a>
				<?php endif; ?>
				<a href="<?=dt_export_link('htkhPrintKy', array('id_ky','ky_khoa'))?>" target="_blank" rel="noopener" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-print mr-1"></i>In giấy / Lưu PDF</a>
			</div>
			<div class="table-responsive">
				<table class="table table-sm table-bordered">
					<thead><tr><th>STT</th><th>Họ và tên</th><th>Mã HV</th><th>Khóa</th><th>Hạng</th><th>Kết quả</th></tr></thead>
					<tbody><?php foreach($htkh_ky_passed as $index => $hv): ?>
						<tr><td><?=$index+1?></td><td><?=htmlspecialchars($hv['hoten'])?></td><td><?=htmlspecialchars($hv['ma_hv'])?></td><td><?=htmlspecialchars($htkh_ky_khoa[dt_htkh_khoa_key($hv)])?></td><td><?=htmlspecialchars($hv['hang'])?></td><td><?=htmlspecialchars($hv['ket_luan'])?></td></tr>
					<?php endforeach; ?></tbody>
				</table>
			</div>
			<?php else: ?><div class="alert alert-info mb-0">Không có học viên thi đạt trong bộ lọc này.</div><?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
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
			<form method="post" action="index.php?com=daotao&act=htkhCapGiay&id_khoa=<?=$id_khoa_sel?>" class="border rounded mb-3" onsubmit="return confirm(this.elements.tat_ca.checked ? 'Cấp giấy cho tất cả học viên trong khóa, kể cả học viên chưa đủ điều kiện?' : 'Cấp giấy HTKH cho học viên đủ điều kiện trong khóa?');">
				<div class="p-3">
					<h4 class="h6 font-weight-bold mb-1">Cấp giấy hàng loạt</h4>
					<p class="text-muted mb-3">Thiết lập thông tin ký cho giấy xác nhận của khóa đang chọn.</p>
					<div class="form-row">
						<div class="form-group col-md-6 col-xl-3 mb-3 mb-xl-0">
							<label for="htkh-khoa-nguoi-ky">Người ký</label>
							<input id="htkh-khoa-nguoi-ky" type="text" name="nguoi_ky" class="form-control form-control-sm" placeholder="Nhập họ tên người ký">
						</div>
						<div class="form-group col-md-6 col-xl-2 mb-3 mb-xl-0">
							<label for="htkh-khoa-ngay-ky">Ngày ký</label>
							<input id="htkh-khoa-ngay-ky" type="text" name="ngay_ky" class="form-control form-control-sm" placeholder="dd/mm/yyyy" aria-describedby="htkh-khoa-ngay-ky-hint">
							<small id="htkh-khoa-ngay-ky-hint" class="form-text text-muted">Để trống: dùng ngày hôm nay.</small>
						</div>
						<div class="form-group col-md-6 col-xl-3 mb-3 mb-xl-0">
							<label for="htkh-khoa-so-quyet-dinh">Số Quyết định</label>
							<input id="htkh-khoa-so-quyet-dinh" type="text" name="so_quyet_dinh" class="form-control form-control-sm" maxlength="100" placeholder="Nhập số quyết định">
						</div>
						<div class="form-group col-md-6 col-xl-4 mb-3 mb-md-0">
							<label for="htkh-khoa-chuc-danh">Chức danh người ký</label>
							<select id="htkh-khoa-chuc-danh" name="chuc_danh" class="form-control form-control-sm">
								<option value="kt_pgd">KT. GIÁM ĐỐC / PHÓ GIÁM ĐỐC</option>
								<option value="gd">GIÁM ĐỐC</option>
							</select>
						</div>
						<div class="form-group col-md-6 col-xl-3 mb-0">
							<label for="htkh-khoa-hang-ce">Kiểu nâng hạng CE</label>
							<select id="htkh-khoa-hang-ce" name="hang_ce" class="form-control form-control-sm" aria-describedby="htkh-khoa-hang-ce-hint">
								<option value="">C lên CE</option>
								<option value="d">D lên CE</option>
								<option value="d2">D2 lên CE</option>
							</select>
							<small id="htkh-khoa-hang-ce-hint" class="form-text text-muted">Chỉ áp dụng cho học viên hạng CE.</small>
						</div>
					</div>
				</div>
				<div class="border-top bg-light p-3 rounded-bottom d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
					<div class="mb-3 mb-lg-0 mr-lg-3">
						<div class="custom-control custom-checkbox">
							<input id="htkh-khoa-tat-ca" type="checkbox" name="tat_ca" value="1" class="custom-control-input" aria-describedby="htkh-khoa-pham-vi">
							<label class="custom-control-label" for="htkh-khoa-tat-ca">Cấp cho tất cả học viên trong khóa</label>
						</div>
						<small id="htkh-khoa-pham-vi" class="form-text text-muted">Mặc định chỉ cấp cho học viên đủ điều kiện. Chọn để bỏ qua điều kiện.</small>
					</div>
					<button type="submit" class="btn btn-sm bg-gradient-warning flex-shrink-0"><i class="fas fa-certificate mr-1" aria-hidden="true"></i>Cấp giấy hàng loạt</button>
				</div>
			</form>
			<?php endif; ?>
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th>Họ và tên</th><th>CCCD</th><th>Hạng</th><th style="width:700px">Số giấy HTKH / Ngày cấp / Số Quyết định</th><th>Ký</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $h=$it['htkh']; $autoSo = dt_htkh_so_giay($it['ma_hv'], $it['hang']); ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?> <span class="badge badge-light border" title="Hạng in trên giấy"><?=htmlspecialchars(dt_htkh_hang_quydoi($it['hang']))?></span></td>
						<td>
							<form method="post" action="index.php?com=daotao&act=saveHtkh&id_khoa=<?=(int)$it['id_khoa']?>" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0;">
								<input type="hidden" name="cccd" value="<?=htmlspecialchars($it['cccd'])?>">
								<input type="text" name="so_giay" class="form-control form-control-sm" style="width:200px" placeholder="<?=htmlspecialchars($autoSo)?>" value="<?=$h?htmlspecialchars($h['so_giay']):''?>" title="Để trống sẽ tự sinh: <?=htmlspecialchars($autoSo)?>">
								<input type="text" name="ngay_cap" class="form-control form-control-sm" style="width:120px" placeholder="dd/mm/yyyy" value="<?=$h?htmlspecialchars($h['ngay_cap']):''?>">
								<input type="text" name="so_quyet_dinh" class="form-control form-control-sm" style="width:140px" placeholder="Số quyết định" maxlength="100" value="<?=$h?htmlspecialchars($h['so_quyet_dinh']):''?>">
								<input type="text" name="ghi_chu" class="form-control form-control-sm" style="width:110px" placeholder="Ghi chú" value="<?=$h?htmlspecialchars($h['ghi_chu']):''?>">
								<button class="btn btn-xs bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu</button>
							</form>
							<?php if($h && (trim((string)($h['so_quyet_dinh']??''))!=='' || trim((string)($h['nguoi_ky']??''))!=='' || trim((string)($h['ngay_ky']??''))!=='')): ?><div class="dt-sub"><i class="fas fa-signature"></i><?php if(!empty($h['so_quyet_dinh'])): ?> QĐ: <?=htmlspecialchars($h['so_quyet_dinh'])?><?php endif; ?><?php if(!empty($h['nguoi_ky'])): ?> <?=htmlspecialchars($h['nguoi_ky'])?><?php endif; ?><?php if(!empty($h['ngay_ky'])): ?> <span class="dt-sep">•</span> <?=htmlspecialchars($h['ngay_ky'])?><?php endif; ?></div><?php endif; ?>
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
