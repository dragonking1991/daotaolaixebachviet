<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="index.php?com=xangdau&act=hocvien">Xăng dầu</a></li>
				<li class="breadcrumb-item active">Quản lý CE</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card card-info card-outline text-sm">
		<div class="card-header"><h3 class="card-title">Quản lý học viên và hóa đơn CE theo khóa</h3></div>
		<div class="card-body">
			<div class="alert alert-info mb-3">
				Học viên CE đạt DAT được đồng bộ vào danh sách riêng này theo khóa; khi dữ liệu DAT thay đổi, hồ sơ CE đã đồng bộ không bị tự gỡ.
				Hóa đơn CE được lưu riêng theo khóa và không tham gia quyết toán chung của Xăng dầu.
			</div>
			<form method="get" action="index.php" class="form-inline flex-wrap mb-3" style="gap:.5rem;">
				<input type="hidden" name="com" value="xangdau">
				<input type="hidden" name="act" value="ce">
				<label for="ce_course" class="mb-0">Khóa CE</label>
				<select id="ce_course" name="id_khoa" class="form-control form-control-sm">
					<option value="0">Tất cả khóa CE</option>
					<?php foreach(($xd_ce_courses ?? array()) as $course): ?>
					<option value="<?=(int)$course['id']?>" <?=(int)$xd_ce_filter_khoa === (int)$course['id'] ? 'selected' : ''?>><?=htmlspecialchars($course['ma_khoa'].' — '.$course['ten_khoa'])?></option>
					<?php endforeach; ?>
				</select>
				<input class="form-control form-control-sm" type="search" name="keyword" value="<?=htmlspecialchars($xd_ce_filter_keyword ?? '')?>" placeholder="Tên, CCCD, mã khóa, giáo viên/hóa đơn">
				<button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search mr-1"></i>Lọc</button>
				<a class="btn btn-sm btn-secondary" href="index.php?com=xangdau&act=ce">Bỏ lọc</a>
				<?php if(xd_can_import()): ?><a class="btn btn-sm btn-info" href="index.php?com=xangdau&act=syncTrainingDat" onclick="return confirm('Đồng bộ học viên DAT đạt? Học viên CE sẽ vào danh sách CE riêng. Hồ sơ đã có sẽ không bị gỡ nếu dữ liệu DAT sau đó thay đổi.');"><i class="fas fa-rotate mr-1"></i>Đồng bộ DAT</a><?php endif; ?>
			</form>
			<?php if(xd_can_import()): ?>
			<div class="row">
				<div class="col-lg-7">
					<form method="post" action="index.php?com=xangdau&act=ceInvoiceSave" class="border rounded p-3 mb-3">
						<strong class="d-block mb-2">Nhập thủ công hóa đơn CE</strong>
						<div class="form-row">
							<div class="form-group col-md-6">
								<label for="ce-invoice-course">Khóa CE</label>
								<select class="form-control form-control-sm" id="ce-invoice-course" name="id_khoa" required>
									<option value="">Chọn khóa</option>
									<?php foreach(($xd_ce_courses ?? array()) as $course): ?><option value="<?=(int)$course['id']?>"><?=htmlspecialchars($course['ma_khoa'].' — '.$course['ten_khoa'])?></option><?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-6"><label>Số hóa đơn</label><input class="form-control form-control-sm" type="text" name="ma_hoa_don" maxlength="191" required></div>
							<div class="form-group col-md-4"><label>Ngày hóa đơn</label><input class="form-control form-control-sm" type="date" name="ngay_hoa_don" required></div>
							<div class="form-group col-md-4"><label>Số tiền (đồng)</label><input class="form-control form-control-sm" type="number" name="tong_tien" min="1" step="1" required></div>
							<div class="form-group col-md-4"><label>Kỳ</label><input class="form-control form-control-sm" type="text" name="ky" maxlength="100" required></div>
							<div class="form-group col-md-8"><label>Giáo viên</label><input class="form-control form-control-sm" type="text" name="gv_hoten" maxlength="255" required></div>
							<div class="form-group col-md-4 d-flex align-items-end"><button class="btn btn-sm btn-success btn-block" type="submit"><i class="fas fa-save mr-1"></i>Lưu hóa đơn CE</button></div>
						</div>
					</form>
				</div>
				<div class="col-lg-5">
					<form method="post" action="index.php?com=xangdau&act=ceInvoiceUpload" enctype="multipart/form-data" class="border rounded p-3 mb-3">
						<strong class="d-block mb-2">Import hóa đơn CE từ Excel</strong>
						<div class="small text-muted mb-2">Cột bắt buộc: Mã khóa CE, Số hóa đơn, Ngày hóa đơn, Số tiền, Giáo viên, Kỳ. Với số tiền trong Excel, ô số thuần nhỏ hơn 100.000 được hiểu theo đơn vị nghìn (ví dụ 608,75 = 608.750đ); chuỗi có dấu phân tách được đọc theo giá trị đầy đủ.</div>
						<div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
							<input class="form-control-file" type="file" name="file-excel" accept=".xls,.xlsx,.xlsb" required>
							<button class="btn btn-sm btn-success" type="submit"><i class="fas fa-upload mr-1"></i>Import</button>
							<a class="btn btn-sm btn-outline-secondary" href="index.php?com=xangdau&act=ceInvoiceTemplate"><i class="fas fa-file-download mr-1"></i>Tải mẫu</a>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<div class="card-header"><h3 class="card-title">Học viên CE <span class="badge badge-info"><?=count($xd_ce_students ?? array())?></span></h3></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover mb-0">
				<thead><tr><th>STT</th><th>Khóa</th><th>Mã HV</th><th>Họ tên</th><th>CCCD</th><th>Ngày sinh</th><th>Giáo viên</th><th>Ngày đạt DAT</th></tr></thead>
				<tbody>
					<?php if(!empty($xd_ce_students)): foreach($xd_ce_students as $i => $student): ?>
					<tr>
						<td><?=$i + 1?></td>
						<td><?=htmlspecialchars($student['ma_khoa'].' — '.$student['ten_khoa'])?></td>
						<td><?=htmlspecialchars($student['ma_hv'])?></td>
						<td><?=htmlspecialchars($student['ho_ten'])?></td>
						<td><?=htmlspecialchars($student['cccd'])?></td>
						<td><?=htmlspecialchars($student['ngaysinh'])?></td>
						<td><?=htmlspecialchars($student['gv_hoten'])?></td>
						<td><?=!empty($student['ngay_dat']) ? date('d/m/Y', strtotime($student['ngay_dat'])) : '—'?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="8" class="text-center text-muted">Chưa có học viên CE đạt DAT trong bộ lọc.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="card-header border-top"><h3 class="card-title">Hóa đơn CE theo khóa <span class="badge badge-secondary"><?=count($xd_ce_invoices ?? array())?></span></h3></div>
		<div class="card-body table-responsive p-0">
			<table class="table table-hover mb-0">
				<thead><tr><th>STT</th><th>Khóa CE</th><th>Số hóa đơn</th><th>Ngày hóa đơn</th><th class="text-right">Số tiền</th><th>Giáo viên</th><th>Kỳ</th><th></th></tr></thead>
				<tbody>
					<?php if(!empty($xd_ce_invoices)): foreach($xd_ce_invoices as $i => $invoice): ?>
					<tr>
						<td><?=$i + 1?></td>
						<td><?=htmlspecialchars($invoice['ma_khoa'])?></td>
						<td><?=htmlspecialchars($invoice['ma_hoa_don'])?></td>
						<td><?=!empty($invoice['ngay_hoa_don']) ? date('d/m/Y', strtotime($invoice['ngay_hoa_don'])) : '—'?></td>
						<td class="text-right"><?=number_format((float)$invoice['tong_tien'], 0, ',', '.')?></td>
						<td><?=htmlspecialchars($invoice['gv_hoten'])?></td>
						<td><?=htmlspecialchars($invoice['ky'])?></td>
						<td><?php if(xd_can_xoa()): ?><form method="post" action="index.php?com=xangdau&act=ceInvoiceDelete" class="d-inline" onsubmit="return confirm('Xóa hóa đơn CE này?');"><input type="hidden" name="id" value="<?=(int)$invoice['id']?>"><button class="btn btn-sm btn-danger" type="submit" aria-label="Xóa hóa đơn CE"><i class="fas fa-trash-alt"></i></button></form><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="8" class="text-center text-muted">Chưa có hóa đơn CE trong bộ lọc.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
