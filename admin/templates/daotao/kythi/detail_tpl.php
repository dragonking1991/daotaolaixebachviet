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
				<?php if(dt_can('kythi','delete')): ?>
				<button type="submit" form="dt-bulk-kythi-roster" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button>
				<button type="submit" form="dt-kythi-roster-clear" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash-alt mr-1"></i>Xóa toàn bộ danh sách</button>
				<?php endif; ?>
				<div class="btn-group">
					<button type="button" class="btn btn-sm bg-gradient-success dropdown-toggle" data-toggle="dropdown"><i class="fas fa-file-excel mr-1"></i>Xuất Excel TTSH</button>
					<div class="dropdown-menu dropdown-menu-right">
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythi&id=<?=$ky['id']?>&dk=all">Tất cả học viên</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythi&id=<?=$ky['id']?>&dk=du">Chỉ đủ điều kiện</a>
						<a class="dropdown-item" href="index.php?com=daotao&act=exportKythi&id=<?=$ky['id']?>&dk=chua">Chỉ chưa đủ điều kiện</a>
					</div>
				</div>
				<a href="index.php?com=daotao&act=kythiRoster&id=<?=$ky['id']?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-import mr-1"></i>Import danh sách</a>
				<a href="index.php?com=daotao&act=kythiUpload&id=<?=$ky['id']?>" class="btn btn-sm bg-gradient-warning"><i class="fas fa-upload mr-1"></i>Import kết quả</a>
			</div>
		</div>
		<div class="card-body">
			<?php if(dt_can('kythi','delete')): ?>
			<form id="dt-bulk-kythi-roster" method="post" action="index.php?com=daotao&act=kythiRemoveSelected"><input type="hidden" name="id" value="<?=(int)$ky['id']?>"></form>
			<form id="dt-kythi-roster-clear" method="post" action="index.php?com=daotao&act=kythiRemoveSelected" onsubmit="return confirm('Xóa TOÀN BỘ danh sách học viên và kết quả trong kỳ này?')"><input type="hidden" name="id" value="<?=(int)$ky['id']?>"><input type="hidden" name="delete_all" value="1"></form>
			<?php endif; ?>
			<div class="row">
				<div class="col-md-6">
					<form method="get" class="form-inline mb-2 p-2" style="background:#f4f6f9;border-radius:4px;">
						<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="kythiDetail"><input type="hidden" name="id" value="<?=$ky['id']?>">
						<strong class="mr-2">Thêm cả khóa (đủ ĐK):</strong>
						<select name="id_khoa" class="form-control form-control-sm mr-2">
							<option value="0">— Chọn khóa —</option>
							<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=(isset($_REQUEST['id_khoa'])&&$_REQUEST['id_khoa']==$k['id'])?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
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
			<div class="table-responsive">
			<table class="table dt-list table-hover mb-0">
				<thead><tr><th class="text-center" style="width:34px"><?php if(dt_can('kythi','delete')): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-bulk-kythi-roster" title="Chọn tất cả"><?php endif; ?></th><th>STT</th><th>Học viên</th><th>Hạng</th><th>Khóa</th><th>Giáo viên</th><th class="text-center">Kết quả</th><th class="dt-actions"></th></tr></thead>
				<tbody>
					<?php if(!empty($items)): $i=1; foreach($items as $it): ?>
					<tr>
						<td class="text-center"><?php if(dt_can('kythi','delete')): ?><input type="checkbox" class="dt-bulk-item" form="dt-bulk-kythi-roster" name="ids[]" value="<?=(int)$it['id']?>"><?php endif; ?></td>
						<td><?=$i++?></td>
						<td><div class="dt-title"><?=htmlspecialchars($it['hoten'])?></div><div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($it['cccd'])?></div></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars(trim($it['ma_khoa'].' '.$it['ten_khoa']))?></td>
						<td><?=htmlspecialchars($it['giaovien'] ?: '—')?></td>
						<td class="text-center"><?php if((int)$it['co_ket_qua']===1): ?><span class="badge badge-<?=($it['ket_luan']==='Đạt')?'success':'danger'?>"><?=htmlspecialchars($it['ket_luan'] ?: 'Đã có KQ')?></span><?php else: ?><span class="badge badge-secondary">Chưa có</span><?php endif; ?></td>
						<td class="dt-actions"><a href="index.php?com=daotao&act=kythiRemove&id=<?=$ky['id']?>&hvid=<?=$it['id']?>" onclick="return confirm('Xóa học viên khỏi kỳ?')" class="btn btn-xs bg-gradient-danger" title="Xóa khỏi kỳ"><i class="fas fa-times"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="8" class="text-center text-muted p-3">Chưa có học viên trong kỳ. Dùng bộ lọc phía trên để thêm.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</section>
