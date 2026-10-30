<?php if(!defined('SOURCES')) die("Error"); ?>
<?php $is_check = (isset($lt_mode) && $lt_mode === 'check'); ?>
<?php $lt_ret_q = array('com'=>'daotao','act'=>'lythuyet'); foreach(array('id_khoa','keyword','trang_thai','du_lieu','gv','p') as $__k) if(isset($_REQUEST[$__k]) && $_REQUEST[$__k] !== '') $lt_ret_q[$__k] = $_REQUEST[$__k]; $__inc = isset($_GET['ret']) ? rawurldecode((string)$_GET['ret']) : ''; $lt_ret = (strpos($__inc, 'index.php?com=daotao') === 0) ? rawurlencode($__inc) : rawurlencode('index.php?'.http_build_query($lt_ret_q)); ?>
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
			<h3 class="card-title"><strong>Kết quả các môn lý thuyết theo hạng</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><?php if(dt_can('lythuyet','edit')): ?><button type="submit" form="dt-module-lythuyet" onclick="return confirm('Đánh dấu Đạt các môn lý thuyết cần thiết theo hạng cho học viên đã chọn?')" class="btn btn-sm bg-gradient-success"><i class="fas fa-check mr-1"></i>Đạt mục đã chọn</button> <a href="index.php?com=daotao&act=crudEdit&entity=lythuyet" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm kết quả</a> <?php endif; ?><?php if(dt_can('lythuyet','delete')): ?><button type="submit" form="dt-module-lythuyet" formaction="index.php?com=daotao&act=moduleDeleteSelected" onclick="return confirm('Xóa kết quả lý thuyết của các học viên đã chọn?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa mục đã chọn</button> <?php endif; ?><?php if(dt_can('lythuyet','import')): ?><a href="index.php?com=daotao&act=uploadLythuyet<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import môn</a> <?php endif; ?><?php if(dt_can('lythuyet','export')): ?>			<a href="<?=dt_export_link('exportLythuyet', array('id_khoa','keyword','trang_thai','du_lieu','gv'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <?php endif; ?></div>
		</div>
		<div class="card-body">
			<?php if(dt_can('lythuyet','edit') || dt_can('lythuyet','delete')): ?><form id="dt-module-lythuyet" method="post" action="index.php?com=daotao&act=modulePassSelected"><input type="hidden" name="entity" value="lythuyet"><input type="hidden" name="id_khoa" value="<?=(int)$id_khoa_sel?>"><input type="hidden" name="ret" value="<?=$lt_ret?>"></form><?php endif; ?>
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="lythuyet">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars(course_option_label($k))?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<input type="text" name="gv" class="form-control form-control-sm mr-2" placeholder="Giáo viên" value="<?=isset($_REQUEST['gv'])?htmlspecialchars($_REQUEST['gv']):''?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2">
					<option value="">— Trạng thái —</option>
					<option value="dat" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='dat')?'selected':''?>>Đạt đủ môn lý thuyết theo hạng</option>
					<option value="chua" <?=(isset($_REQUEST['trang_thai'])&&$_REQUEST['trang_thai']=='chua')?'selected':''?>>Chưa đủ</option>
				</select>
				<select name="du_lieu" class="form-control form-control-sm mr-2">
					<option value="co" <?=($du_lieu_filter==='co')?'selected':''?>>Có kết quả lý thuyết</option>
					<option value="tat_ca" <?=($du_lieu_filter==='tat_ca')?'selected':''?>>Tất cả học viên</option>
					<option value="chua" <?=($du_lieu_filter==='chua')?'selected':''?>>Chưa có kết quả lý thuyết</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<div class="dt-stats mb-2">
				<div class="dt-stat is-green"><div class="dt-stat-label">Đạt đủ các môn lý thuyết theo hạng</div><div class="dt-stat-num"><?=(int)$module_stats['dat']?></div></div>
				<div class="dt-stat is-red"><div class="dt-stat-label">Chưa đạt đủ các môn lý thuyết theo hạng</div><div class="dt-stat-num"><?=(int)$module_stats['chua']?></div></div>
				<?php foreach($module_stats['mon'] as $__mk => $__mstat): ?>
				<div class="dt-stat is-blue"><div class="dt-stat-label"><?=htmlspecialchars($__mstat['label'])?></div><div class="dt-stat-num"><?=(int)$__mstat['dat']?> <small class="text-muted">Đạt</small> / <?=(int)$__mstat['chua']?> <small class="text-muted">Chưa đạt</small></div></div>
				<?php endforeach; ?>
			</div>
			<?php if(!$id_khoa_sel && empty($items)): ?>
			<div class="alert alert-info mb-0">Chọn khóa để xem kết quả các môn lý thuyết theo hạng, hoặc nhập CCCD/tên để tra cứu toàn bộ khóa.</div>
			<?php else: ?>
			<?php if($is_check): ?>
			<div class="alert alert-warning py-1 mb-2"><i class="fas fa-info-circle mr-1"></i>Khóa nâng hạng: tích chọn các môn <strong>Đạt</strong> rồi bấm <strong>Lưu</strong>. Có thể dùng nút “Đạt toàn bộ học viên”.</div>
			<form method="post" action="index.php?com=daotao&act=saveLythuyetCheck&id_khoa=<?=$id_khoa_sel?>">
			<div class="mb-2">
				<button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu kết quả</button>
				<button type="submit" name="dat_all" value="1" class="btn btn-sm bg-gradient-warning" onclick="return confirm('Đánh dấu ĐẠT toàn bộ 4 môn cho TẤT CẢ học viên?')"><i class="fas fa-check-double mr-1"></i>Đạt toàn bộ học viên</button>
			</div>
			<?php endif; ?>
			<div class="table-responsive">
			<table class="table dt-list dt-lythuyet-list table-hover mb-0">
				<thead><tr><th class="text-center" style="width:34px"><?php if(dt_can('lythuyet','edit') || dt_can('lythuyet','delete')): ?><input type="checkbox" class="dt-bulk-all" data-form="dt-module-lythuyet" title="Chọn tất cả"><?php endif; ?></th><th>Học viên</th><th>Kết quả các môn bắt buộc (gồm mô phỏng)</th><th class="text-center">Tổng kết LT</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $hv=$it['hv']; $soDat=0; $tongMon=count($it['ds_mon']); ?>
					<tr>
						<td class="text-center"><?php if(dt_can('lythuyet','edit') || dt_can('lythuyet','delete')): ?><input type="checkbox" class="dt-bulk-item" form="dt-module-lythuyet" name="ids[]" value="<?=(int)$hv['id']?>"><?php endif; ?></td>
						<td>
							<div class="dt-title"><?=htmlspecialchars($hv['hoten'] ?: '—')?></div>
							<div class="dt-sub"><i class="fas fa-id-card"></i><?=htmlspecialchars($hv['cccd'] ?: '—')?></div>
							<div class="dt-sub"><i class="fas fa-chalkboard-teacher"></i><?=htmlspecialchars(!empty($hv['gv_hoten']) ? $hv['gv_hoten'] : '—')?></div>
						</td>
						<td>
							<div class="dt-ly-modules">
								<?php foreach($it['ds_mon'] as $mk=>$lbl): $m=isset($it['mon'][$mk])?$it['mon'][$mk]:null; if($m && (int)$m['dat']===1) $soDat++; ?>
								<?php if($is_check): ?>
								<label class="dt-ly-module dt-ly-check<?=(!$m || (int)$m['dat']!==1)?' is-chuadat':''?>" style="display:inline-flex;align-items:center;gap:4px;margin-right:10px;">
									<input type="checkbox" name="check[<?=htmlspecialchars($hv['cccd'])?>][<?=$mk?>]" value="1" <?=($m && (int)$m['dat']===1)?'checked':''?>>
									<span class="dt-ly-label"><?=htmlspecialchars($lbl)?></span>
								</label>
								<?php else: ?>
								<div class="dt-ly-module<?=(!$m || (int)$m['dat']!==1)?' is-chuadat':''?>">
									<span class="dt-ly-label"><?=htmlspecialchars($lbl)?></span>
									<?php if(!$m): ?>
										<a class="dt-ly-add text-danger" href="index.php?com=daotao&amp;act=crudEdit&amp;entity=lythuyet&amp;id_khoa=<?=(int)$hv['id_khoa']?>&amp;cccd=<?=urlencode($hv['cccd'])?>&amp;mon=<?=urlencode($mk)?>&amp;ret=<?=$lt_ret?>" title="Chưa có dữ liệu — bấm để thêm"><i class="fas fa-plus"></i> Chưa có</a>
									<?php elseif(!empty($m['standalone'])): ?>
										<span class="dt-ly-val"><a href="index.php?com=daotao&act=mophong&id_khoa=<?=(int)$hv['id_khoa']?>&keyword=<?=urlencode($hv['cccd'])?>" title="Tiến độ mô phỏng <?=htmlspecialchars($m['tien_do'])?>% — xem mô phỏng"><span class="badge badge-<?=$m['dat']?'success':'danger'?>"><?=$m['dat']?'Đạt':'Chưa đạt'?></span></a></span>
									<?php else: ?>
										<span class="dt-ly-val">
											<a href="index.php?com=daotao&act=crudEdit&entity=lythuyet&id=<?=$m['id']?>&ret=<?=$lt_ret?>" title="TĐ <?=htmlspecialchars($m['tien_do'])?><?=$mk==='mophong'?'%':''?> / Điểm <?=htmlspecialchars($m['diem_kt'])?> — bấm để sửa"><span class="badge badge-<?=$m['dat']?'success':'danger'?>"><?=$m['dat']?'Đạt':'Chưa đạt'?></span></a>
											<a href="index.php?com=daotao&act=crudDelete&entity=lythuyet&id=<?=$m['id']?>&ret=<?=$lt_ret?>" onclick="return confirm('Xóa kết quả môn này?')" class="text-danger dt-ly-del" title="Xóa"><i class="fas fa-times"></i></a>
										</span>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</td>
						<td class="text-center">
							<div class="dt-summary-score"><?=$soDat?>/<?=$tongMon?></div>
							<?php if($soDat>=$tongMon): ?><span class="badge badge-success">Đạt lý thuyết</span><?php else: ?><span class="badge badge-danger">Chưa đủ</span><?php endif; ?>
						</td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="4" class="text-center text-muted p-3"><?php if($du_lieu_filter==='co'): ?>Chưa có học viên có kết quả lý thuyết. Chọn “Tất cả học viên” để xem roster.<?php elseif($du_lieu_filter==='chua'): ?>Không có học viên nào chưa có kết quả lý thuyết.<?php else: ?>Chưa có học viên trong khóa này.<?php endif; ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php if($is_check): ?>
			<div class="mt-2"><button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-save mr-1"></i>Lưu kết quả</button></div>
			</form>
			<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
<style>.dt-ly-module.is-chuadat{background:#fff0f0;border-radius:4px;} .dt-ly-module.is-chuadat .dt-ly-label{color:#c0392b;}</style>
