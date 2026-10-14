<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Cấu hình cổng giáo viên</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong>Cấu hình cổng giáo viên</strong></h3></div>
		<form method="post" action="index.php?com=daotao&act=congGvConfigSave">
			<div class="card-body">
				<div class="alert alert-info mb-3"><i class="fas fa-info-circle mr-1"></i>Chọn những nội dung hiển thị cho giáo viên khi tra cứu quá trình học của học viên phụ trách, và cho phép giáo viên tự nhập kết quả thực hành trong hình.</div>

				<h6 class="text-muted text-uppercase" style="letter-spacing:.5px;">Cột hiển thị</h6>
				<div class="row">
					<?php
						$cols = array('show_lythuyet'=>'Lý thuyết (7 môn)','show_cabin'=>'Cabin','show_hinh'=>'Thực hành trong hình','show_dat'=>'DAT (trên đường)','show_ketluan'=>'Kết luận (đủ điều kiện)');
						foreach($cols as $k=>$lbl):
					?>
					<div class="col-md-4 mb-2"><div class="custom-control custom-checkbox">
						<input type="checkbox" class="custom-control-input" id="<?=$k?>" name="<?=$k?>" value="1" <?=!empty($cfg[$k])?'checked':''?>>
						<label class="custom-control-label" for="<?=$k?>"><?=$lbl?></label>
					</div></div>
					<?php endforeach; ?>
				</div>

				<hr>
				<h6 class="text-muted text-uppercase" style="letter-spacing:.5px;">Tùy chọn</h6>
				<div class="custom-control custom-checkbox mb-2">
					<input type="checkbox" class="custom-control-input" id="show_detail" name="show_detail" value="1" <?=!empty($cfg['show_detail'])?'checked':''?>>
					<label class="custom-control-label" for="show_detail">Hiển thị chi tiết nội dung còn thiếu (như mục Tổng hợp) — giúp giáo viên biết cần dạy thêm gì</label>
				</div>
				<div class="custom-control custom-checkbox mb-2">
					<input type="checkbox" class="custom-control-input" id="allow_hinh_input" name="allow_hinh_input" value="1" <?=!empty($cfg['allow_hinh_input'])?'checked':''?>>
					<label class="custom-control-label" for="allow_hinh_input">Cho phép giáo viên tự nhập Giờ/Km thực hành trong hình (như mục Thực hành hình)</label>
				</div>
			</div>
			<div class="card-footer"><button class="btn btn-sm bg-gradient-primary"><i class="fas fa-save mr-1"></i>Lưu cấu hình</button></div>
		</form>
	</div>
</section>
