<div class="w_1000">
	<div class="title-main"><span><?=(@$title_cat!='') ? $title_cat : @$title_crumb?></span></div>
	<?php if(!empty($static['noidung'])) { ?>
		<div class="content-main"><?=htmlspecialchars_decode($static['noidung'])?></div>
	<?php } ?>

	<div class="frm_tracuu employee-lookup-box">
		<form id="employee-lookup-form" class="frm_tracuu2" method="post">
			<input type="text" placeholder="Nhập mã tra cứu nhân viên" id="employee_lookup_keyword" name="keyword">
			<button type="submit" class="employee-lookup-submit" style="padding:0 16px; margin-right:10px;">Tra cứu</button>
		</form>
		<p style="margin-top:10px; color:#666; font-size:14px;">Tra cứu chỉ bằng mã tham chiếu (Mã tra cứu).</p>
	</div>

	<div class="employee-lookup-result" style="margin-top:20px;"></div>
</div>