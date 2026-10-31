<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid"><ol class="breadcrumb float-sm-left">
		<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
		<li class="breadcrumb-item active">Đủ điều kiện học DAT</li>
	</ol></div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Đủ điều kiện học DAT</strong> <span class="dt-count"><?=count($dat_entry)?></span></h3>
			<div class="card-tools"><?php if(dt_can('lythuyet','export')): ?>
				<a href="<?=dt_export_link('exportDieukienDat', array('id_khoa','keyword','gv','trang_thai'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a>
			<?php endif; ?></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="dieukienDat">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $course): ?><option value="<?=(int)$course['id']?>" <?=$id_khoa_sel==$course['id']?'selected':''?>><?=htmlspecialchars(course_option_label($course))?></option><?php endforeach; ?>
				</select>
				<input name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV" value="<?=htmlspecialchars(dt_req('keyword'))?>">
				<input name="gv" class="form-control form-control-sm mr-2" placeholder="Giáo viên" value="<?=htmlspecialchars(dt_req('gv'))?>">
				<select name="trang_thai" class="form-control form-control-sm mr-2">
					<option value="">Tất cả trạng thái</option>
					<option value="dat" <?=dt_req('trang_thai')==='dat'?'selected':''?>>Đủ điều kiện học DAT</option>
					<option value="chua" <?=dt_req('trang_thai')==='chua'?'selected':''?>>Chưa đủ điều kiện</option>
				</select>
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<div class="alert alert-info py-2">Đủ cả <strong>4 môn</strong>: Kỹ thuật lái xe, Pháp luật phần 1, 2, 3. Mỗi môn cần <strong>tiến độ ≥ 70%</strong> và <strong>điểm kiểm tra ≥ 5</strong>. Tiến độ giữ cách tính cũ: tổng 3 cột thời gian / mẫu số × 100 (lần lượt 20, 25, 40, 25). Thiếu kết quả là chưa đủ. Đây không phải kết quả đạt DAT và không tự đồng bộ sang XD.</div>
			<div class="dt-stats mb-2">
				<div class="dt-stat is-green"><div class="dt-stat-label">Đủ điều kiện học DAT</div><div class="dt-stat-num"><?=(int)$dat_entry_stats['eligible']?></div></div>
				<div class="dt-stat is-red"><div class="dt-stat-label">Chưa đủ điều kiện</div><div class="dt-stat-num"><?=(int)$dat_entry_stats['pending']?></div></div>
			</div>
			<?php if(!$id_khoa_sel && dt_req('keyword')===''): ?>
				<div class="alert alert-info mb-0">Chọn khóa hoặc nhập tên / CCCD / mã học viên để tra cứu.</div>
			<?php else: ?>
			<div class="table-responsive"><table class="table dt-list table-hover mb-0">
				<thead><tr><th>Học viên</th><?php foreach(dt_dat_entry_subjects() as $label): ?><th><?=htmlspecialchars($label)?></th><?php endforeach; ?><th>Điều kiện học DAT</th></tr></thead>
				<tbody>
				<?php foreach($dat_entry as $item): $student=$item['student']; $evaluation=$item['evaluation']; ?>
					<tr>
						<td><div class="dt-title"><?=htmlspecialchars($student['hoten'])?></div>
							<div class="dt-sub"><?=htmlspecialchars($student['cccd'])?> · <?=htmlspecialchars($student['ma_hv'])?></div>
							<div class="dt-sub"><?=htmlspecialchars($student['ma_khoa'].' — '.$student['ten_khoa'])?></div>
							<div class="dt-sub"><?=htmlspecialchars($student['gv_hoten'])?></div>
						</td>
						<?php foreach($evaluation['subjects'] as $subject): ?>
						<td>
							<div><?=is_numeric($subject['tien_do']) ? htmlspecialchars((string)$subject['tien_do']).'%' : '—'?> · Điểm <?=is_numeric($subject['diem_kt']) ? htmlspecialchars((string)$subject['diem_kt']) : '—'?></div>
							<span class="badge badge-<?=$subject['eligible']?'success':'danger'?>"><?=$subject['eligible']?'Đạt':htmlspecialchars($subject['reason'])?></span>
						</td>
						<?php endforeach; ?>
						<td><span class="badge badge-<?=$evaluation['eligible']?'success':'warning'?>"><?=$evaluation['eligible']?'Đủ điều kiện học DAT':'Chưa đủ ('.$evaluation['passed'].'/4)'?></span></td>
					</tr>
				<?php endforeach; ?>
				<?php if(!$dat_entry): ?><tr><td colspan="6" class="text-center text-muted p-3">Không có học viên phù hợp.</td></tr><?php endif; ?>
				</tbody>
			</table></div>
			<?php endif; ?>
		</div>
	</div>
</section>
