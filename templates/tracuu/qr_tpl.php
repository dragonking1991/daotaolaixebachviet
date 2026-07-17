<div class="w_1000">
	<div class="title-main"><span><?=(@$title_cat!='')?$title_cat:@$title_crumb?></span></div>
	<?php if(!empty($static['noidung'])) { ?>
		<div class="content-main"><?=htmlspecialchars_decode($static['noidung'])?></div>
	<?php } ?>

	<div class="frm_tracuu">
		<input type="hidden" id="tracuu_type" value="qr">
		<div class="frm_kysathach" style="margin-bottom:10px;">
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