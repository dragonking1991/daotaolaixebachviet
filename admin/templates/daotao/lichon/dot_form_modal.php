<?php
if(!defined('SOURCES')) die("Error");
/* Biểu mẫu tạo / sửa đợt ôn. $loDotForm = null (tạo mới) hoặc dòng đợt ôn (sửa). */
$loF = $loDotForm ?: array('id' => 0, 'ngay_sh' => '', 'san' => '', 'id_sh_ky' => 0, 'max_song_song' => 3, 'ghi_chu' => '');
$loFEsc = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
?>
<div class="modal fade" id="lo-dot-modal" tabindex="-1" role="dialog" aria-labelledby="lo-dot-modal-title" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form class="modal-content" method="post" action="index.php?com=daotao&amp;act=lichonSave">
			<input type="hidden" name="_csrf" value="<?=$loFEsc(admin_csrf_token())?>">
			<input type="hidden" name="id" value="<?=(int)$loF['id']?>">
			<div class="modal-header">
				<h5 class="modal-title" id="lo-dot-modal-title"><i class="fas fa-calendar-plus text-primary mr-2"></i><?=$loF['id'] ? 'Sửa thông tin đợt ôn' : 'Tạo đợt ôn mới'?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="form-row">
					<div class="form-group col-sm-6">
						<label for="lo-ngay-sh">Ngày sát hạch <span class="text-danger">*</span></label>
						<input type="date" id="lo-ngay-sh" name="ngay_sh" class="form-control" value="<?=$loFEsc($loF['ngay_sh'])?>" required>
					</div>
					<div class="form-group col-sm-6">
						<label for="lo-san">Sân sát hạch</label>
						<input type="text" id="lo-san" name="san" class="form-control" maxlength="100" list="lo-san-list" value="<?=$loFEsc($loF['san'])?>" placeholder="Ví dụ: Sân 2">
						<datalist id="lo-san-list"><option value="Sân 1"><option value="Sân 2"><option value="Sân 3"></datalist>
					</div>
				</div>
				<?php if(!empty($lo_sh_ky)): ?>
				<div class="form-group">
					<label for="lo-sh-ky">Gắn với kỳ sát hạch <span class="text-muted font-weight-normal">(không bắt buộc)</span></label>
					<select id="lo-sh-ky" name="id_sh_ky" class="form-control">
						<option value="0">— Không gắn —</option>
						<?php foreach($lo_sh_ky as $k): ?><option value="<?=(int)$k['id']?>" <?=(int)$loF['id_sh_ky'] === (int)$k['id'] ? 'selected' : ''?>><?=$loFEsc($k['ten'])?> (<?=lo_date_vn($k['ngay'])?>)</option><?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>
				<div class="form-group">
					<label for="lo-max-ss">Số giờ tối đa một GV được xếp song song 2 xe trong 1 ngày</label>
					<div class="input-group" style="max-width:220px"><input type="number" id="lo-max-ss" name="max_song_song" class="form-control" min="0" max="6" value="<?=(int)$loF['max_song_song']?>"><div class="input-group-append"><span class="input-group-text">giờ/ngày</span></div></div>
					<small class="form-text text-muted">Chỉ dùng khi không xếp liên tục được, để tránh đẩy giờ sang ngày sau. Đặt 0 để tắt.</small>
				</div>
				<div class="form-group mb-0">
					<label for="lo-ghichu">Ghi chú</label>
					<textarea id="lo-ghichu" name="ghi_chu" class="form-control" rows="2" maxlength="1000" placeholder="Ví dụ: sân chốt 10 xe số sàn, 5 xe tự động"><?=$loFEsc($loF['ghi_chu'])?></textarea>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
				<button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i><?=$loF['id'] ? 'Lưu thay đổi' : 'Tạo đợt ôn'?></button>
			</div>
		</form>
	</div>
</div>
