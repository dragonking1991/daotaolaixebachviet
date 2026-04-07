<?php include TEMPLATE.LAYOUT."gioithieu.php"; ?>

<div class="wap_daotao main_fix">
	<div class="title-main"><span>Các hạng đào tạo</span></div>
	<div class="daotao wap_news ">
		<?=$func->get_posts($daotao,'item_news',THUMBS.'/400x300x1/');  ?>
	</div>
</div>

<?php /*
 <div class="wap_nhantin_f">
	 <div class="nhantin_f main_fix">
	 	<div class="title-main"><span>Đăng ký nhận thông báo khóa học mới</span></div>
	    <p><?=slogandangkynhantin?></p>
	    <form class="form-newsletter validation-newsletter" novalidate method="post" action="" enctype="multipart/form-data" id="FormNewsletter">
	        <input type="text" class="form-control" id="ten" name="ten" placeholder="Họ tên" required />
	        <input type="text" class="form-control" id="dienthoai" name="dienthoai" placeholder="Số điện thoại" required />
	        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required />
	        <input type="text" class="form-control" id="diachi" name="diachi" placeholder="Địa chỉ" required />
            <input type="submit" class="btn btn-primary" value="<?=dangky?>" disabled />
            <input type="hidden" class="btn btn-primary" name="submit-newsletter" value="<?=gui?>" />
            <input type="hidden" name="recaptcha_response_newsletter" id="recaptchaResponseNewsletter">
	    </form>
	</div>
</div>*/?>


<div class="wap_tintuc">
    <div class="wap_tintuc2 main_fix">
        <?php include TEMPLATE.LAYOUT."tintuc.php"; ?>
        <?php include TEMPLATE.LAYOUT."video.php"; ?>
    </div>
</div>


<div class="wap_hinhanh">
	<div class="title-main"><span>Hình ảnh thực tế</span></div>
	<div class="hinhanh slick4322 control_slick main_fix">
		 <?php $func->get_slider2('hinh-anh','thumbs/500x350x1/','class="no_lazy" data-lazy'); ?>
	</div>
</div>


<div class="wap_lienhe main_fix">
	<div class="lienhe">
		<?php echo $addons->setAddons('footer-map', 'footer-map', 10);?>
	</div>

	<div class="camnhan">
		<p class="title-intro"><span>Cảm nhận của học viên</span></p>
		<div class="camnhan2">
			<?=$func->get_posts_no($camnhan,'item_cn',THUMBS.'/110x110x1/');  ?>
		</div>
	</div>
</div>