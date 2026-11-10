<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=kythi">Ngày kiểm tra</a></li>
			<li class="breadcrumb-item active"><?=htmlspecialchars($ky['ten'] ?: '(Không tên)')?></li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong><?=htmlspecialchars($ky['ten'] ?: '(Không tên)')?></strong> — <?=htmlspecialchars($ky['ngay_kt'] ?: '—')?> <span class="dt-count"><?=number_format((int)$total_items)?> HV</span></h3>
			<div class="card-tools">
				<?php $kyId = (int)$ky['id']; ?>
				<?php if(dt_can('kythi','export')): ?>
				<div class="btn-group">
					<button type="button" class="btn btn-sm bg-gradient-success dropdown-toggle" data-toggle="dropdown"><i class="fas fa-file-excel mr-1"></i>Xuất Excel (mẫu TTSH)</button>
					<div class="dropdown-menu dropdown-menu-right">
						<h6 class="dropdown-header">Danh sách dự kiểm tra (gửi TTSH)</h6>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythi&id=<?=$kyId?>&dk=all">Tất cả học viên</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythi&id=<?=$kyId?>&dk=du">Chỉ đủ điều kiện</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythi&id=<?=$kyId?>&dk=chua">Chỉ chưa đủ điều kiện</a>
						<div class="dropdown-divider"></div>
						<h6 class="dropdown-header">Kết quả kiểm tra (<?=number_format($kythi_counts['ket_qua'])?> HV có KQ)</h6>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythiKetqua&id=<?=$kyId?>&kq=all">Tất cả kết quả</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythiKetqua&id=<?=$kyId?>&kq=dat">Chỉ đạt</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythiKetqua&id=<?=$kyId?>&kq=vang">Chỉ vắng</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythiKetqua&id=<?=$kyId?>&kq=rot">Chỉ rớt</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="#" onclick="var s=document.querySelectorAll('.dt-kythi-pick:checked');if(!s.length){alert('Vui lòng tick chọn học viên trong danh sách.');return false;}var q=[];s.forEach(function(c){q.push('ids[]='+encodeURIComponent(c.value));});location.href='index.php?com=daotao&act=exportKythiKetqua&id=<?=$kyId?>&kq=all&'+q.join('&');return false;"><i class="fas fa-check-square mr-1"></i>Mục đã chọn</a>
					</div>
				</div>
				<?php endif; ?>
				<?php if(dt_can('kythi','import')): ?>
				<div class="btn-group">
					<button type="button" class="btn btn-sm bg-gradient-warning dropdown-toggle" data-toggle="dropdown"><i class="fas fa-upload mr-1"></i>Import</button>
					<div class="dropdown-menu dropdown-menu-right">
						<a class="dropdown-item" href="index.php?com=daotao&act=kythiRoster&id=<?=$kyId?>"><i class="fas fa-users mr-1"></i>Danh sách học viên vào kỳ</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=kythiUpload&id=<?=$kyId?>"><i class="fas fa-clipboard-check mr-1"></i>Kết quả kiểm tra (mẫu TTSH)</a>
					</div>
				</div>
				<?php endif; ?>
				<?php if(dt_can('kythi','edit')): ?>
				<button type="submit" form="dt-kythi-sync" class="btn btn-sm bg-gradient-info" title="Lấy Mã HV, Hạng, Mã khóa, Tên khóa, Giáo viên từ dữ liệu Đào tạo"><i class="fas fa-sync-alt mr-1"></i>Lấy mã khóa từ hệ thống</button>
				<?php endif; ?>
				<?php if(dt_can('kythi','delete') || dt_can('htkh','delete')): ?>
				<div class="btn-group">
					<button type="button" class="btn btn-sm bg-gradient-danger dropdown-toggle" data-toggle="dropdown"><i class="fas fa-trash-alt mr-1"></i>Xóa</button>
					<div class="dropdown-menu dropdown-menu-right">
						<?php if(dt_can('kythi','delete')): ?>
						<button type="submit" form="dt-bulk-kythi-roster" class="dropdown-item"><i class="fas fa-check-square mr-1"></i>Học viên đã chọn</button>
						<button type="submit" form="dt-kythi-clear-kq" class="dropdown-item" <?=$kythi_counts['ket_qua'] ? '' : 'disabled'?>><i class="fas fa-eraser mr-1"></i>Kết quả kiểm tra trong kỳ (<?=number_format($kythi_counts['ket_qua'])?>)</button>
						<?php endif; ?>
						<?php if(dt_can('htkh','delete')): ?>
						<button type="submit" form="dt-kythi-clear-htkh" class="dropdown-item" <?=$kythi_counts['htkh'] ? '' : 'disabled'?>><i class="fas fa-certificate mr-1"></i>Số giấy xác nhận đã cấp (<?=number_format($kythi_counts['htkh'])?>)</button>
						<?php endif; ?>
						<?php if(dt_can('kythi','delete')): ?>
						<div class="dropdown-divider"></div>
						<button type="submit" form="dt-kythi-roster-clear" class="dropdown-item text-danger"><i class="fas fa-trash-alt mr-1"></i>Toàn bộ danh sách (gồm kết quả)</button>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="card-body">
			<?php if(dt_can('kythi','edit')): ?>
			<form id="dt-kythi-sync" method="post" action="index.php?com=daotao&act=kythiSyncKhoa" onsubmit="return confirm('Cập nhật Mã HV, Hạng, Mã khóa, Tên khóa, Giáo viên từ dữ liệu Đào tạo cho học viên trong kỳ? (Học viên chưa có trong hệ thống giữ nguyên)')"><input type="hidden" name="id" value="<?=$kyId?>"></form>
			<?php if($kythi_counts['thieu_khoa'] > 0): ?>
			<div class="alert alert-warning py-2 d-flex align-items-center flex-wrap">
				<span class="mr-2"><i class="fas fa-exclamation-triangle mr-1"></i><strong><?=number_format($kythi_counts['thieu_khoa'])?></strong> học viên chưa có mã khóa.</span>
				<button type="submit" form="dt-kythi-sync" class="btn btn-xs bg-gradient-info"><i class="fas fa-sync-alt mr-1"></i>Lấy mã khóa từ hệ thống</button>
			</div>
			<?php endif; ?>
			<?php endif; ?>
			<?php if(dt_can('kythi','delete')): ?>
			<form id="dt-bulk-kythi-roster" method="post" action="index.php?com=daotao&act=kythiRemoveSelected"><input type="hidden" name="id" value="<?=$kyId?>"></form>
			<form id="dt-kythi-roster-clear" method="post" action="index.php?com=daotao&act=kythiRemoveSelected" onsubmit="return confirm('Xóa TOÀN BỘ danh sách học viên và kết quả trong kỳ này?')"><input type="hidden" name="id" value="<?=$kyId?>"><input type="hidden" name="delete_all" value="1"></form>
			<form id="dt-kythi-clear-kq" method="post" action="index.php?com=daotao&act=kythiClearResults" onsubmit="return confirm('Xóa kết quả kiểm tra (điểm, KQ, kết luận) của <?=(int)$kythi_counts['ket_qua']?> học viên trong kỳ? Danh sách học viên được giữ nguyên.')"><input type="hidden" name="id" value="<?=$kyId?>"></form>
			<?php endif; ?>
			<?php if(dt_can('htkh','delete')): ?>
			<form id="dt-kythi-clear-htkh" method="post" action="index.php?com=daotao&act=kythiClearHtkh" onsubmit="return confirm('Xóa <?=(int)$kythi_counts['htkh']?> số giấy xác nhận (HTKH) đã cấp cho học viên trong kỳ? Có sao lưu trước khi xóa.')"><input type="hidden" name="id" value="<?=$kyId?>"></form>
			<?php endif; ?>
			<div class="row">
				<div class="col-md-6">
					<form method="get" class="form-inline mb-2 p-2" style="background:#f4f6f9;border-radius:4px;">
						<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="kythiDetail"><input type="hidden" name="id" value="<?=$ky['id']?>">
						<strong class="mr-2">Thêm cả khóa (đủ ĐK):</strong>
						<select name="id_khoa" class="form-control form-control-sm mr-2">
							<option value="0">— Chọn khóa —</option>
							<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=(isset($_REQUEST['id_khoa'])&&$_REQUEST['id_khoa']==$k['id'])?'selected':''?>><?=htmlspecialchars(course_option_label($k))?></option><?php endforeach; ?>
						</select>
						<button class="btn btn-sm bg-gradient-secondary">Lọc</button>
						<?php if(!empty($_REQUEST['id_khoa'])): ?><a href="index.php?com=daotao&act=kythiAddEligible&id=<?=$ky['id']?>&id_khoa=<?=(int)$_REQUEST['id_khoa']?>" onclick="return confirm('Thêm TẤT CẢ học viên đủ điều kiện của khóa này vào kỳ?')" class="btn btn-sm bg-gradient-primary ml-2"><i class="fas fa-user-plus mr-1"></i>Thêm đủ ĐK</a>
						<a href="index.php?com=daotao&act=kythiAddEligible&id=<?=$ky['id']?>&id_khoa=<?=(int)$_REQUEST['id_khoa']?>&all=1" onclick="return confirm('Thêm TẤT CẢ học viên của khóa này (gồm cả chưa đủ điều kiện) vào kỳ?')" class="btn btn-sm bg-gradient-secondary ml-1" title="Gồm cả học viên chưa đủ điều kiện"><i class="fas fa-users mr-1"></i>Thêm cả khóa</a><?php endif; ?>
					</form>
				</div>
				<div class="col-md-6">
					<form method="get" class="form-inline mb-2 p-2" style="background:#f4f6f9;border-radius:4px;">
						<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="kythiDetail"><input type="hidden" name="id" value="<?=$ky['id']?>">
						<strong class="mr-2">Thêm lẻ (khóa cũ VR):</strong>
						<input type="text" name="tc" class="form-control form-control-sm mr-2" placeholder="CCCD / Họ tên / Mã HV" value="<?=htmlspecialchars($tracuu_kw)?>">
						<button class="btn btn-sm bg-gradient-secondary">Tra cứu</button>
					</form>
					<?php if($tracuu_kw !== ''): ?>
					<div class="table-responsive" style="max-height:220px;overflow:auto;">
						<table class="table table-sm table-hover mb-0">
							<tbody>
							<?php if(!empty($tracuu_kq)): foreach($tracuu_kq as $tq): $h=$tq['hv']; ?>
								<tr>
									<td><?=htmlspecialchars($h['hoten'])?><br><small class="text-muted"><?=htmlspecialchars($h['cccd'])?> • <?=htmlspecialchars(isset($h['ma_khoa'])?$h['ma_khoa']:'')?></small></td>
									<td class="text-center"><?php if($tq['du_dieu_kien']): ?><span class="badge badge-success">Đủ ĐK</span><?php else: ?><span class="badge badge-danger">Chưa đủ</span><?php endif; ?></td>
									<td class="text-right"><?php if($tq['da_them']): ?><span class="text-muted">Đã thêm</span><?php else: ?><a href="index.php?com=daotao&act=kythiAddOne&id=<?=$ky['id']?>&cccd=<?=urlencode($h['cccd'])?>&tc=<?=urlencode($tracuu_kw)?>" class="btn btn-xs bg-gradient-primary">Thêm</a><?php endif; ?></td>
								</tr>
							<?php endforeach; else: ?>
								<tr><td class="text-muted text-center p-2">Không tìm thấy học viên</td></tr>
							<?php endif; ?>
							</tbody>
						</table>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<form method="post" action="index.php?com=daotao&act=kythiAddManual&id=<?=$ky['id']?>" class="p-2 mb-0" style="background:#fff8e1;border:1px solid #ffe08a;border-radius:4px;">
				<strong class="mr-2"><i class="fas fa-keyboard mr-1"></i>Nhập thủ công (khóa cũ chưa có dữ liệu):</strong>
				<div class="form-row mt-2">
					<div class="col-md-3 mb-1"><input type="text" name="hoten" class="form-control form-control-sm" placeholder="Họ và tên *" required></div>
					<div class="col-md-2 mb-1"><input type="text" name="cccd" class="form-control form-control-sm" placeholder="CCCD *" required></div>
					<div class="col-md-2 mb-1"><input type="text" name="nam_sinh" class="form-control form-control-sm" placeholder="Năm sinh"></div>
					<div class="col-md-1 mb-1"><input type="text" name="hang" class="form-control form-control-sm" placeholder="Hạng"></div>
					<div class="col-md-2 mb-1"><input type="text" name="ma_hv" class="form-control form-control-sm" placeholder="Mã HV"></div>
					<div class="col-md-2 mb-1"><input type="text" name="ma_khoa" class="form-control form-control-sm" placeholder="Mã khóa"></div>
				</div>
				<div class="form-row">
					<div class="col-md-5 mb-1"><input type="text" name="ten_khoa" class="form-control form-control-sm" placeholder="Tên khóa"></div>
					<div class="col-md-5 mb-1"><input type="text" name="giaovien" class="form-control form-control-sm" placeholder="Giáo viên"></div>
					<div class="col-md-2 mb-1"><button class="btn btn-sm bg-gradient-primary btn-block"><i class="fas fa-plus mr-1"></i>Thêm vào kỳ</button></div>
				</div>
			</form>
			<hr>
			<form method="get" class="form-inline mb-2 p-2" style="background:#f4f6f9;border-radius:4px;">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="kythiDetail"><input type="hidden" name="id" value="<?=(int)$ky['id']?>">
				<strong class="mr-2">Lọc kết quả:</strong>
				<select name="hang" class="form-control form-control-sm mr-2" aria-label="Hạng">
					<option value="">Tất cả hạng</option>
					<?php foreach($result_hangs as $hang => $label): $value = $hang === '' ? 'none' : $hang; ?>
					<option value="<?=htmlspecialchars($value)?>" <?=$result_filters['hang'] === $value ? 'selected' : ''?>><?=htmlspecialchars($label)?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="ma_khoa" class="form-control form-control-sm mr-2" placeholder="Mã khóa" value="<?=htmlspecialchars($result_filters['ma_khoa'])?>">
				<input type="text" name="ten_khoa" class="form-control form-control-sm mr-2" placeholder="Khóa" value="<?=htmlspecialchars($result_filters['ten_khoa'])?>">
				<button class="btn btn-sm bg-gradient-secondary"><i class="fas fa-filter mr-1"></i>Lọc</button>
				<a href="index.php?com=daotao&act=kythiDetail&id=<?=(int)$ky['id']?>" class="btn btn-sm btn-default ml-1">Xóa lọc</a>
				<span class="text-muted ml-2"><?=number_format((int)$total_items)?> / <?=number_format((int)$result_total)?> học viên</span>
			</form>
			<?php if(!empty($result_stats)): ?>
			<div class="d-flex flex-wrap mb-2">
				<?php foreach($result_rank_stats as $rankStat): ?>
				<span class="mr-4 mb-1"><strong><?=htmlspecialchars($rankStat['hang'] ?: 'Chưa có hạng')?></strong>: <?=number_format(count($rankStat['khoa']))?> khóa · <?=number_format($rankStat['so_hv'])?> HV · <?=number_format($rankStat['so_dat'])?> đạt<?php if($rankStat['thieu_khoa']): ?> <small class="text-muted">(<?=number_format($rankStat['thieu_khoa'])?> HV chưa có khóa)</small><?php endif; ?></span>
				<?php endforeach; ?>
			</div>
			<div class="table-responsive mb-2">
				<table class="table table-sm table-bordered mb-0" style="max-width:760px;">
					<thead><tr><th>Hạng</th><th>Mã khóa</th><th>Khóa</th><th class="text-center">Số HV</th><th class="text-center">Đạt</th></tr></thead>
					<tbody><?php foreach($result_stats as $stat): ?>
					<tr><td><?=htmlspecialchars($stat['hang'] ?: '—')?></td><td><?=htmlspecialchars($stat['ma_khoa'] ?: '—')?></td><td><?=htmlspecialchars($stat['ten_khoa'] ?: '—')?></td><td class="text-center"><?=number_format((int)$stat['so_hv'])?></td><td class="text-center"><span class="badge badge-success"><?=number_format((int)$stat['so_dat'])?></span></td></tr>
					<?php endforeach; ?></tbody>
				</table>
			</div>
			<?php endif; ?>
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th class="text-center" style="width:34px"><?php if($dtCanPick = (dt_can('kythi','delete') || dt_can('kythi','export'))): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-bulk-kythi-roster" title="Chọn tất cả"><?php endif; ?></th><th>STT</th><th>Học viên</th><th>Mã HV</th><th>Hạng</th><th>Mã khóa</th><th>Khóa</th><th>Giáo viên</th><th class="text-center">Kết quả</th><th class="dt-actions"></th></tr></thead>
				<tbody>
					<?php if(!empty($items)): $i=1; foreach($items as $it): ?>
					<tr>
						<td class="text-center"><?php if($dtCanPick): ?><input type="checkbox" class="dt-bulk-item dt-kythi-pick" form="dt-bulk-kythi-roster" name="ids[]" value="<?=(int)$it['id']?>"><?php endif; ?></td>
						<td><?=$i++?></td>
						<td><div class="dt-title"><?=htmlspecialchars($it['hoten'])?></div><div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($it['cccd'])?></div></td>
						<td><?=htmlspecialchars($it['ma_hv'] ?: '—')?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars($it['ma_khoa'] ?: '—')?></td>
						<td><?=htmlspecialchars($it['ten_khoa'] ?: '—')?></td>
						<td><?=htmlspecialchars($it['giaovien'] ?: '—')?></td>
						<td class="text-center"><?php if((int)$it['co_ket_qua']===1): ?><span class="badge badge-<?=($it['ket_luan']==='Đạt')?'success':'danger'?>"><?=htmlspecialchars($it['ket_luan'] ?: 'Đã có KQ')?></span><?php else: ?><span class="badge badge-secondary">Chưa có</span><?php endif; ?></td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=kythiRemove&id=<?=$ky['id']?>&hvid=<?=$it['id']?>" onclick="return confirm('Xóa học viên khỏi kỳ?')" class="btn btn-xs bg-gradient-danger" title="Xóa khỏi kỳ"><i class="fas fa-times"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="10" class="text-center text-muted p-3">Không có học viên phù hợp bộ lọc.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</section>
