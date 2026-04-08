<div class="w_1000">
	<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
	<div class="content-main"><?=htmlspecialchars_decode($static['noidung'])?></div>

	<div class="frm_tracuu">
		<ul>
			<li class="active" data-type="gplx">Số GPLX</li>
			<li data-type="gxn">Số giấy xác nhận</li>
			<li data-type="qr">QR Thanh toán</li>
		</ul>
		<div class="frm_kysathach" style="display:none; margin-bottom:10px;">
			<select id="id_kysathach" style="width:100%; padding:10px; border:1px solid rgba(0,0,0,0.1); border-radius:4px; font-size:14px; height:45px; background:#ffffff;">
				<option value="">--- Chọn kỳ sát hạch ---</option>
				<?php if(isset($items_kysathach) && count($items_kysathach)) { ?>
					<?php foreach($items_kysathach as $ky) { ?>
						<option value="<?=$ky['id']?>"><?=date('d/m/Y', strtotime($ky['ngay_sathach']))?> - <?=$ky['ten_viettat']?> - <?=$ky['loai_sathach']?></option>
					<?php } ?>
				<?php } ?>
			</select>
		</div>
		<div class="frm_tracuu2">
			<input type="text" placeholder="Nhập số CCCD" id="input_cccd">
			<p class="c_tracuu">Tra cứu</p>
		</div>

	</div>
</div>
<div class="ketqua"></div>