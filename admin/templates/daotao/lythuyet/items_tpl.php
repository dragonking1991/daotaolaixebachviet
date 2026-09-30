<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Lý thuyết</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Tổng hợp lý thuyết (6 môn)</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=lythuyet" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm kết quả</a> <a href="index.php?com=daotao&act=uploadLythuyet<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import môn</a> <a href="<?=dt_export_link('exportLythuyet', array('id_khoa','keyword','trang_thai','du_lieu'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=lythuyet" onclick="return confirm('Xóa toàn bộ kết quả lý thuyết?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="lythuyet">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'].' — '.(int)$k['so_hoc_vien'].' học viên')?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2">
					<option value="">— Trạng thái —</option>
					<option value="dat" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='dat')?'selected':''?>>Đạt đủ 6 môn</option>
					<option value="chua" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='chua')?'selected':''?>>Chưa đủ</option>
				</select>
				<select name="du_lieu" class="form-control form-control-sm mr-2">
					<option value="co" <?=($du_lieu_filter==='co')?'selected':''?>>Có kết quả (mặc định)</option>
					<option value="tat_ca" <?=($du_lieu_filter==='tat_ca')?'selected':''?>>Tất cả học viên</option>
					<option value="chua" <?=($du_lieu_filter==='chua')?'selected':''?>>Chưa có kết quả</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<?php if(!$id_khoa_sel): ?>
			<div class="alert alert-info mb-0">Chọn khóa để xem tổng hợp lý thuyết.</div>
			<?php else: ?>
			<div class="table-responsive">
			<table class="table dt-list dt-lythuyet-list table-hover mb-0">
				<thead><tr><th>Học viên</th><th>Kết quả 6 môn lý thuyết</th><th class="text-center">Tổng kết</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $hv=$it['hv']; $soDat=0; $tongMon=count($ds_mon); ?>
					<tr>
						<td>
							<div class="dt-title"><?=htmlspecialchars($hv['hoten'] ?: '—')?></div>
							<div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($hv['cccd'] ?: '—')?></div>
						</td>
						<td>
							<div class="dt-ly-modules">
								<?php foreach($ds_mon as $mk=>$lbl): $m=isset($it['mon'][$mk])?$it['mon'][$mk]:null; if($m && (int)$m['dat']===1) $soDat++; ?>
								<div class="dt-ly-module">
									<span class="dt-ly-label"><?=htmlspecialchars($lbl)?></span>
									<?php if(!$m): ?>
										<a class="dt-ly-add" href="index.php?com=daotao&act=crudEdit&entity=lythuyet" title="Thêm kết quả môn này"><i class="fas fa-plus"></i> Thêm</a>
									<?php else: ?>
										<span class="dt-ly-val">
											<a href="index.php?com=daotao&act=crudEdit&entity=lythuyet&id=<?=$m['id']?>" title="TĐ <?=htmlspecialchars($m['tien_do'])?> / Điểm <?=htmlspecialchars($m['diem_kt'])?> — bấm để sửa"><span class="badge badge-<?=$m['dat']?'success':'danger'?>"><?=$m['dat']?'Đạt':'Chưa'?></span></a>
											<a href="index.php?com=daotao&act=crudDelete&entity=lythuyet&id=<?=$m['id']?>" onclick="return confirm('Xóa kết quả môn này?')" class="text-danger dt-ly-del" title="Xóa"><i class="fas fa-times"></i></a>
										</span>
									<?php endif; ?>
								</div>
								<?php endforeach; ?>
							</div>
						</td>
						<td class="text-center">
							<div class="dt-summary-score"><?=$soDat?>/<?=$tongMon?></div>
							<?php if($soDat>=$tongMon): ?><span class="badge badge-success">Đạt lý thuyết</span><?php else: ?><span class="badge badge-secondary">Chưa đủ</span><?php endif; ?>
						</td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="3" class="text-center text-muted p-3"><?php if($du_lieu_filter==='co'): ?>Chưa có học viên có kết quả lý thuyết. Chọn “Tất cả học viên” để xem danh sách khóa.<?php elseif($du_lieu_filter==='chua'): ?>Không có học viên nào chưa có kết quả lý thuyết.<?php else: ?>Chưa có học viên trong khóa này.<?php endif; ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
