<div class="w_1000 dt-gv-page">
	<div class="title-main"><span><?=(@$title_crumb != '') ? $title_crumb : 'Cổng giáo viên'?></span></div>

	<?php if(!empty($dtGvMsg)): ?><div style="max-width:900px;margin:0 auto 12px;padding:10px 14px;background:#e6f7ec;border:1px solid #1e9e4a;border-radius:8px;color:#12673a;"><?=htmlspecialchars($dtGvMsg)?></div><?php endif; ?>
	<?php if(!empty($dtGvErr)): ?><div style="max-width:900px;margin:0 auto 12px;padding:10px 14px;background:#fdecea;border:1px solid #c0392b;border-radius:8px;color:#912018;"><?=htmlspecialchars($dtGvErr)?></div><?php endif; ?>

	<?php if(empty($dtGv)): ?>
	<div style="max-width:400px;margin:0 auto;border:1px solid #ddd;border-radius:12px;padding:24px;">
		<form method="post" action="index.php?com=cong-giao-vien">
			<input type="hidden" name="dt_act" value="login">
			<div style="margin-bottom:14px;">
				<label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Số CCCD</label>
				<input type="text" name="cccd" required style="width:100%;height:42px;padding:0 12px;border:1px solid #ccc;border-radius:6px;">
			</div>
			<div style="margin-bottom:16px;">
				<label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Mật khẩu <span style="color:#888;">(mặc định = CCCD)</span></label>
				<input type="password" name="password" required style="width:100%;height:42px;padding:0 12px;border:1px solid #ccc;border-radius:6px;">
			</div>
			<button type="submit" style="width:100%;height:44px;border:none;border-radius:6px;background:#2954f2;color:#fff;font-weight:700;cursor:pointer;">Đăng nhập</button>
		</form>
		<div style="text-align:center;margin-top:14px;">
			<a href="javascript:void(0)" onclick="var f=document.getElementById('dt-forgot');f.style.display=(f.style.display==='none'?'block':'none');" style="font-size:13px;color:#2954f2;text-decoration:none;">Quên mật khẩu?</a>
		</div>
		<div id="dt-forgot" style="display:<?=(isset($dtAct)&&$dtAct==='resetpass')?'block':'none'?>;margin-top:14px;border-top:1px solid #eee;padding-top:14px;">
			<div style="font-size:13px;color:#555;margin-bottom:10px;">Xác minh bằng ngày sinh và số điện thoại đã đăng ký với trung tâm. Nếu hồ sơ chưa có đủ hai thông tin này, vui lòng liên hệ trung tâm để được cấp lại mật khẩu.</div>
			<form method="post" action="index.php?com=cong-giao-vien">
				<input type="hidden" name="dt_act" value="resetpass">
				<div style="margin-bottom:10px;"><label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Số CCCD</label><input type="text" name="cccd" required style="width:100%;height:40px;padding:0 12px;border:1px solid #ccc;border-radius:6px;"></div>
				<div style="margin-bottom:10px;"><label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Ngày sinh <span style="color:#888;">(dd/mm/yyyy)</span></label><input type="text" name="ngaysinh" placeholder="dd/mm/yyyy" required style="width:100%;height:40px;padding:0 12px;border:1px solid #ccc;border-radius:6px;"></div>
				<div style="margin-bottom:10px;"><label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Số điện thoại</label><input type="tel" name="sdt" required style="width:100%;height:40px;padding:0 12px;border:1px solid #ccc;border-radius:6px;"></div>
				<div style="margin-bottom:12px;"><label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Mật khẩu mới <span style="color:#888;">(tối thiểu 8 ký tự)</span></label><input type="password" name="new_pass" minlength="8" autocomplete="new-password" required style="width:100%;height:40px;padding:0 12px;border:1px solid #ccc;border-radius:6px;"></div>
				<button type="submit" style="width:100%;height:42px;border:none;border-radius:6px;background:#1e9e4a;color:#fff;font-weight:700;cursor:pointer;">Đặt lại mật khẩu</button>
			</form>
		</div>
	</div>
	<?php else: ?>
	<div class="dt-gv-content">
		<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
			<div><strong style="font-size:16px;">Xin chào, <?=htmlspecialchars($dtGv['hoten'])?></strong> <span style="color:#888;">(CCCD: <?=htmlspecialchars($dtGv['cccd'])?>)</span></div>
			<div>
				<button type="button" onclick="document.getElementById('dt-changepass').style.display=(document.getElementById('dt-changepass').style.display==='none'?'block':'none')" style="height:36px;padding:0 14px;border:1px solid #2954f2;background:#fff;color:#2954f2;border-radius:6px;cursor:pointer;">Đổi mật khẩu</button>
				<form method="post" action="index.php?com=cong-giao-vien" style="display:inline;"><input type="hidden" name="dt_act" value="logout"><input type="hidden" name="dt_csrf" value="<?=htmlspecialchars(dt_gv_csrf_token())?>"><button type="submit" style="height:36px;padding:0 14px;border:none;background:#c0392b;color:#fff;border-radius:6px;cursor:pointer;">Đăng xuất</button></form>
			</div>
		</div>

		<?php $dtMustChange = !empty($dtGv['must_change']); ?>
		<?php if($dtMustChange): ?><div style="max-width:420px;margin:0 0 10px;padding:10px 14px;background:#fff7e6;border:1px solid #f0ad4e;border-radius:8px;color:#8a5a00;">Bạn đang dùng mật khẩu mặc định (CCCD). Vui lòng đổi mật khẩu để tiếp tục sử dụng cổng giáo viên.</div><?php endif; ?>
		<div id="dt-changepass" style="display:<?=$dtMustChange?'block':'none'?>;max-width:420px;margin:0 0 16px;border:1px solid #ddd;border-radius:10px;padding:16px;">
			<form method="post" action="index.php?com=cong-giao-vien">
				<input type="hidden" name="dt_act" value="changepass">
				<input type="hidden" name="dt_csrf" value="<?=htmlspecialchars(dt_gv_csrf_token())?>">
				<div style="margin-bottom:10px;"><label style="display:block;font-size:13px;color:#555;">Mật khẩu hiện tại</label><input type="password" name="old_pass" required style="width:100%;height:38px;padding:0 10px;border:1px solid #ccc;border-radius:6px;"></div>
				<div style="margin-bottom:12px;"><label style="display:block;font-size:13px;color:#555;">Mật khẩu mới <span style="color:#888;">(tối thiểu 8 ký tự, không dùng CCCD)</span></label><input type="password" name="new_pass" minlength="8" autocomplete="new-password" required style="width:100%;height:38px;padding:0 10px;border:1px solid #ccc;border-radius:6px;"></div>
				<button type="submit" style="height:38px;padding:0 16px;border:none;background:#2954f2;color:#fff;border-radius:6px;cursor:pointer;">Lưu mật khẩu</button>
			</form>
		</div>

		<?php if(!$dtMustChange): ?>
		<?php
			$showLt = !empty($dtCfg['show_lythuyet']); $showCabin = !empty($dtCfg['show_cabin']);
			$showHinh = !empty($dtCfg['show_hinh']); $showDat = !empty($dtCfg['show_dat']);
			$showExam = !empty($dtCfg['show_sathach']); $showKl = !empty($dtCfg['show_ketluan']); $showDetail = !empty($dtCfg['show_detail']);
			$allowHinh = !empty($dtCfg['allow_hinh_input']);
			$colCount = 2 + ($showLt?1:0) + ($showCabin?1:0) + ($showHinh?1:0) + ($showDat?1:0) + ($showExam?1:0) + ($showKl?1:0);
		?>
		<div class="dt-gv-tabs">
			<a href="cong-giao-vien" class="<?=$dtTab==='daotao'?'is-active':''?>"><i class="fas fa-user-graduate" aria-hidden="true"></i> Đào tạo</a>
			<a href="cong-giao-vien?tab=luong" class="<?=$dtTab==='luong'?'is-active':''?>"><i class="fas fa-receipt" aria-hidden="true"></i> Lương</a>
			<a href="cong-giao-vien?tab=xe" class="<?=$dtTab==='xe'?'is-active':''?>"><i class="fas fa-car" aria-hidden="true"></i> Xe phụ trách</a>
			<a href="cong-giao-vien?tab=lichon" class="<?=$dtTab==='lichon'?'is-active':''?>"><i class="fas fa-calendar-alt" aria-hidden="true"></i> Lịch ôn<?php if(!empty($dtLoOpen)): ?> <span class="dt-lo-tabbadge"><?=(int)$dtLoOpen?></span><?php endif; ?></a>
		</div>

		<?php if($dtTab === 'lichon'): include __DIR__.'/giaovien_lichon_tpl.php'; ?>
		<?php elseif($dtTab === 'xe'): ?>
		<section class="dt-gv-vehicles">
			<div class="dt-gv-vehicle-heading">
				<div><h2><i class="fas fa-car-side" aria-hidden="true"></i> Xe được phân công</h2><p>Chỉ hiển thị xe đang được phân công cho tài khoản của bạn.</p></div>
				<span class="dt-gv-vehicle-count"><?=count($dtXeRepairVehicles)?> xe</span>
			</div>
			<form method="get" action="cong-giao-vien" class="dt-gv-vehicle-search">
				<input type="hidden" name="tab" value="xe">
				<label for="dt-gv-xe-kw">Tìm xe</label>
				<input id="dt-gv-xe-kw" type="search" name="xe_kw" value="<?=htmlspecialchars($dtXeRepairKeyword, ENT_QUOTES, 'UTF-8')?>" placeholder="Nhập biển số hoặc nhãn hiệu">
				<button type="submit"><i class="fas fa-search" aria-hidden="true"></i> Tìm</button>
			</form>
			<?php if($dtXeRepairVehicles): ?>
			<div class="dt-gv-vehicle-list">
				<?php foreach($dtXeRepairVehicles as $vehicle): $isSelected = (int)$dtXeRepairSelectedId === (int)$vehicle['id']; ?>
				<a class="dt-gv-vehicle-item <?=$isSelected?'is-selected':''?>" href="cong-giao-vien?tab=xe&amp;id_xe=<?=(int)$vehicle['id']?>&amp;xe_kw=<?=rawurlencode($dtXeRepairKeyword)?>">
					<span class="dt-gv-vehicle-icon"><i class="fas fa-car" aria-hidden="true"></i></span>
					<span class="dt-gv-vehicle-info">
						<strong><?=htmlspecialchars($vehicle['bien_so'], ENT_QUOTES, 'UTF-8')?></strong>
						<span><?=htmlspecialchars(trim(($vehicle['nhan_hieu'] ?? '').' '.($vehicle['loai_xe'] ?? '')) ?: 'Chưa cập nhật nhãn hiệu', ENT_QUOTES, 'UTF-8')?></span>
					</span>
					<span class="dt-gv-vehicle-history">
						<?php if((int)$vehicle['so_lan_sua'] > 0): ?>
						<?=number_format((int)$vehicle['so_lan_sua'])?> nhật ký
						<?php if(($vehicle['trang_thai_cuoi'] ?? '') === 'dang_sua'): ?><em>Đang sửa</em><?php endif; ?>
						<?php else: ?>Chưa có nhật ký<?php endif; ?>
					</span>
					<i class="fas fa-chevron-right dt-gv-vehicle-arrow" aria-hidden="true"></i>
				</a>
				<?php endforeach; ?>
			</div>
			<?php else: ?>
			<div class="dt-gv-vehicle-empty"><i class="fas fa-car fa-2x" aria-hidden="true"></i><strong>Chưa có xe được phân công</strong><span>Liên hệ trung tâm nếu danh sách này chưa chính xác.</span></div>
			<?php endif; ?>

			<?php if($dtXeRepairSelectedVehicle): ?>
			<div class="dt-gv-repair-panel">
				<div class="dt-gv-repair-title">
					<div><span>NHẬT KÝ SỬA XE</span><h3><?=htmlspecialchars($dtXeRepairSelectedVehicle['bien_so'], ENT_QUOTES, 'UTF-8')?></h3><p><?=htmlspecialchars(trim(($dtXeRepairSelectedVehicle['nhan_hieu'] ?? '').' '.($dtXeRepairSelectedVehicle['loai_xe'] ?? '')) ?: 'Thông tin xe tập lái', ENT_QUOTES, 'UTF-8')?></p></div>
					<?php if(!$dtMustChange): ?><a href="cong-giao-vien?tab=xe&amp;id_xe=<?=(int)$dtXeRepairSelectedId?>&amp;new_log=1#dt-gv-repair-form" class="dt-gv-repair-add"><i class="fas fa-plus" aria-hidden="true"></i> Thêm nhật ký</a><?php endif; ?>
				</div>
				<div class="dt-gv-repair-stats">
					<div><span>Tổng nhật ký</span><strong><?=(int)$dtXeRepairSummary['tong']?></strong></div>
					<div><span>Đang sửa</span><strong><?=(int)$dtXeRepairSummary['dang_sua']?></strong></div>
					<div><span>Chi phí đã ghi nhận</span><strong><?=number_format((float)$dtXeRepairSummary['chi_phi'], 0, ',', '.')?> ₫</strong></div>
				</div>

				<?php if($dtXeRepairFormOpen && !$dtMustChange): ?>
				<form id="dt-gv-repair-form" method="post" action="index.php?com=cong-giao-vien" enctype="multipart/form-data" class="dt-gv-repair-form">
					<input type="hidden" name="dt_act" value="addXeSuachua">
					<input type="hidden" name="dt_csrf" value="<?=htmlspecialchars(dt_gv_csrf_token(), ENT_QUOTES, 'UTF-8')?>">
					<input type="hidden" name="tab" value="xe">
					<input type="hidden" name="id_xe" value="<?=(int)$dtXeRepairSelectedId?>">
					<input type="hidden" name="xe_kw" value="<?=htmlspecialchars($dtXeRepairKeyword, ENT_QUOTES, 'UTF-8')?>">
					<div class="dt-gv-repair-form-heading"><div><strong>Ghi nhận hiện trạng / sửa chữa</strong><span>Trạng thái mới bắt đầu là “Đang sửa”; có thể cập nhật sau.</span></div><a href="cong-giao-vien?tab=xe&amp;id_xe=<?=(int)$dtXeRepairSelectedId?>" aria-label="Đóng biểu mẫu">Đóng</a></div>
					<div class="dt-gv-repair-fields">
						<label><span>Ngày ghi nhận <b>*</b></span><input type="date" name="ngay_sua" value="<?=htmlspecialchars($dtXeRepairFormValues['ngay_sua'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8')?>" required><?php if(isset($dtXeRepairFormErrors['ngay_sua'])): ?><small><?=htmlspecialchars($dtXeRepairFormErrors['ngay_sua'], ENT_QUOTES, 'UTF-8')?></small><?php endif; ?></label>
						<label><span>Gara / đơn vị thực hiện</span><input type="text" name="gara" maxlength="255" value="<?=htmlspecialchars($dtXeRepairFormValues['gara'] ?? '', ENT_QUOTES, 'UTF-8')?>" placeholder="Nếu đã đưa xe đi sửa"></label>
						<label class="dt-gv-repair-description"><span>Mô tả hiện trạng hoặc nội dung sửa chữa <b>*</b></span><textarea name="noi_dung" maxlength="1000" rows="3" required placeholder="Ví dụ: Xe có vết xước ở cửa bên phải, cần kiểm tra và xử lý"><?=htmlspecialchars($dtXeRepairFormValues['noi_dung'] ?? '', ENT_QUOTES, 'UTF-8')?></textarea><?php if(isset($dtXeRepairFormErrors['noi_dung'])): ?><small><?=htmlspecialchars($dtXeRepairFormErrors['noi_dung'], ENT_QUOTES, 'UTF-8')?></small><?php endif; ?></label>
						<label><span>Số km hiện tại</span><input type="number" name="so_km" min="0" max="99999999.99" step="0.01" value="<?=htmlspecialchars($dtXeRepairFormValues['so_km'] ?? '', ENT_QUOTES, 'UTF-8')?>" placeholder="Chưa ghi nhận"><?php if(isset($dtXeRepairFormErrors['so_km'])): ?><small><?=htmlspecialchars($dtXeRepairFormErrors['so_km'], ENT_QUOTES, 'UTF-8')?></small><?php endif; ?></label>
						<label><span>Chi phí (₫)</span><input type="number" name="chi_phi" min="0" max="99999999999999" step="1" value="<?=htmlspecialchars($dtXeRepairFormValues['chi_phi'] ?? '', ENT_QUOTES, 'UTF-8')?>" placeholder="Chưa nhập"><?php if(isset($dtXeRepairFormErrors['chi_phi'])): ?><small><?=htmlspecialchars($dtXeRepairFormErrors['chi_phi'], ENT_QUOTES, 'UTF-8')?></small><?php endif; ?></label>
						<label class="dt-gv-repair-description"><span>Ảnh minh chứng hiện trạng</span><input type="file" name="anh_minh_chung[]" accept="image/jpeg,image/png,image/webp" multiple data-max-files="10" data-max-size="5242880"><small>Chọn tối đa 10 ảnh JPEG, PNG hoặc WebP; mỗi ảnh không quá 5 MB. Ảnh sẽ tự thu nhỏ và nén trước khi lưu. Bạn có thể chụp ảnh hoặc chọn ảnh có sẵn trên điện thoại/máy tính.</small><span class="dt-gv-upload-feedback" aria-live="polite"></span></label>
						<label class="dt-gv-repair-description"><span>Ghi chú thêm</span><textarea name="ghi_chu" maxlength="5000" rows="2" placeholder="Thông tin bổ sung (không bắt buộc)"><?=htmlspecialchars($dtXeRepairFormValues['ghi_chu'] ?? '', ENT_QUOTES, 'UTF-8')?></textarea></label>
					</div>
					<div class="dt-gv-repair-form-actions"><button type="submit"><i class="fas fa-save" aria-hidden="true"></i> Lưu nhật ký</button><a href="cong-giao-vien?tab=xe&amp;id_xe=<?=(int)$dtXeRepairSelectedId?>">Hủy</a></div>
				</form>
				<?php endif; ?>

				<div class="dt-gv-repair-history">
					<h4>Lịch sử sửa chữa <span><?=count($dtXeRepairRows)?></span></h4>
					<?php if($dtXeRepairRows): foreach($dtXeRepairRows as $repair): $repairImages = dt_xe_suachua_decode_images($repair['anh_minh_chung'] ?? ''); ?>
					<article class="dt-gv-repair-card">
						<div class="dt-gv-repair-card-top">
							<div><time datetime="<?=htmlspecialchars($repair['ngay_sua'], ENT_QUOTES, 'UTF-8')?>"><?=date('d/m/Y', strtotime($repair['ngay_sua']))?></time><span class="dt-gv-repair-status <?=$repair['trang_thai']==='hoan_tat'?'is-done':'is-progress'?>"><?=$repair['trang_thai']==='hoan_tat'?'Hoàn tất':'Đang sửa'?></span></div>
							<?php if(!$dtMustChange): ?>
							<form method="post" action="index.php?com=cong-giao-vien" class="dt-gv-status-form">
								<input type="hidden" name="dt_act" value="updateXeSuachuaStatus"><input type="hidden" name="dt_csrf" value="<?=htmlspecialchars(dt_gv_csrf_token(), ENT_QUOTES, 'UTF-8')?>"><input type="hidden" name="tab" value="xe"><input type="hidden" name="id_xe" value="<?=(int)$dtXeRepairSelectedId?>"><input type="hidden" name="id_suachua" value="<?=(int)$repair['id']?>"><input type="hidden" name="xe_kw" value="<?=htmlspecialchars($dtXeRepairKeyword, ENT_QUOTES, 'UTF-8')?>">
								<label for="dt-gv-status-<?=(int)$repair['id']?>">Cập nhật trạng thái</label><select id="dt-gv-status-<?=(int)$repair['id']?>" name="trang_thai"><option value="dang_sua" <?=$repair['trang_thai']==='dang_sua'?'selected':''?>>Đang sửa</option><option value="hoan_tat" <?=$repair['trang_thai']==='hoan_tat'?'selected':''?>>Hoàn tất</option></select><button type="submit">Cập nhật</button>
							</form>
							<?php endif; ?>
						</div>
						<h5><?=nl2br(htmlspecialchars($repair['noi_dung'], ENT_QUOTES, 'UTF-8'))?></h5>
						<div class="dt-gv-repair-meta">
							<?php if(!empty($repair['gara'])): ?><span><i class="fas fa-wrench" aria-hidden="true"></i><?=htmlspecialchars($repair['gara'], ENT_QUOTES, 'UTF-8')?></span><?php endif; ?>
							<?php if($repair['so_km'] !== null): ?><span><i class="fas fa-gauge" aria-hidden="true"></i><?=number_format((float)$repair['so_km'], 0, ',', '.')?> km</span><?php endif; ?>
							<?php if($repair['chi_phi'] !== null): ?><span><i class="fas fa-coins" aria-hidden="true"></i><?=number_format((float)$repair['chi_phi'], 0, ',', '.')?> ₫</span><?php endif; ?>
						</div>
						<?php if(!empty($repair['ghi_chu'])): ?><p class="dt-gv-repair-notes"><?=nl2br(htmlspecialchars($repair['ghi_chu'], ENT_QUOTES, 'UTF-8'))?></p><?php endif; ?>
						<?php if($repairImages): ?><div class="dt-gv-repair-images"><?php foreach($repairImages as $image): $imageUrl = 'index.php?com=cong-giao-vien&repair_image='.rawurlencode($image).'&id_xe='.(int)$dtXeRepairSelectedId.'&id_suachua='.(int)$repair['id']; ?><a href="<?=htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noopener noreferrer"><img src="<?=htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8')?>" alt="Ảnh hiện trạng xe" loading="lazy"></a><?php endforeach; ?></div><?php endif; ?>
					</article>
					<?php endforeach; else: ?>
					<div class="dt-gv-repair-empty"><i class="fas fa-clipboard-list" aria-hidden="true"></i><strong>Chưa có nhật ký sửa chữa</strong><span>Ghi nhận hiện trạng xe hoặc thêm lần sửa chữa đầu tiên để tiện theo dõi.</span></div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
		</section>
		<?php elseif($dtTab === 'luong'): ?>
		<div class="dt-gv-slip">
			<?php if(!$dtPayroll): ?>
			<div class="dt-gv-slip-empty">Chưa có dữ liệu lương. Vui lòng liên hệ trung tâm nếu bạn cần tra cứu.</div>
			<?php else: $slipRec = $dtPayroll['record']; $slip = payrollMonthlySlip($dtPayroll['fields']); ?>
			<div class="dt-gv-slip-info">
				<div><strong>Họ và tên:</strong><br><?=htmlspecialchars($dtGv['hoten'])?></div>
				<div><strong>CCCD:</strong><br><?=htmlspecialchars($dtGv['cccd'])?></div>
				<div><strong>Bộ phận:</strong><br><?=htmlspecialchars(payrollMonthlyDepartmentLabel($slipRec['bo_phan']))?></div>
			</div>
			<?php if($slip['note'] !== ''): ?><div class="dt-gv-slip-note"><strong>Ghi chú:</strong><br><?=nl2br(htmlspecialchars($slip['note']))?></div><?php endif; ?>
			<div class="dt-gv-slip-head">
				<div class="dt-gv-slip-title">Phiếu lương giáo viên</div>
				<form method="get" action="cong-giao-vien" class="dt-gv-slip-month">
					<input type="hidden" name="tab" value="luong">
					<label for="dt-gv-thang">Tháng</label>
					<select id="dt-gv-thang" name="thang" onchange="this.form.submit()">
						<?php foreach($dtPayroll['months'] as $m): ?><option value="<?=htmlspecialchars($m)?>" <?=$m===$slipRec['thang']?'selected':''?>><?=htmlspecialchars(payrollMonthlyLabel($m))?></option><?php endforeach; ?>
					</select>
					<noscript><button type="submit">Xem</button></noscript>
				</form>
			</div>
			<div class="dt-gv-slip-rows">
				<?php foreach($slip['top'] as $r): ?><div class="dt-gv-slip-row<?=$r['strong']?' is-strong':''?>"><span><?=htmlspecialchars($r['label'])?></span><strong><?=htmlspecialchars($r['value'])?></strong></div><?php endforeach; ?>
				<div class="dt-gv-slip-gap"></div>
				<?php foreach($slip['detail'] as $r): ?><div class="dt-gv-slip-row<?=$r['strong']?' is-strong':''?>"><span><?=htmlspecialchars($r['label'])?></span><strong><?=htmlspecialchars($r['value'])?></strong></div><?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php else: ?>

		<form method="get" action="cong-giao-vien" class="dt-gv-filter" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:14px;">
			<select name="khoa" style="height:38px;padding:0 10px;border:1px solid #ccc;border-radius:6px;min-width:200px;">
				<option value="0">— Tất cả khóa —</option>
				<?php foreach($dtKhoaList as $k): ?><option value="<?=$k['id']?>" <?=($dtFilterKhoa==$k['id'])?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
			</select>
			<input type="text" name="kw" value="<?=htmlspecialchars($dtFilterKw)?>" placeholder="Tên / CCCD / Mã HV" style="height:38px;padding:0 12px;border:1px solid #ccc;border-radius:6px;min-width:200px;">
			<select name="hinh_thieu" aria-label="Lọc học viên thiếu giờ/km thực hành hình" style="height:38px;padding:0 10px;border:1px solid #ccc;border-radius:6px;">
				<option value="0">Tất cả giờ/km</option>
				<option value="1" <?=$dtFilterHinhThieu?'selected':''?>>Thiếu giờ/km</option>
			</select>
			<button type="submit" style="height:38px;padding:0 18px;border:none;border-radius:6px;background:#2954f2;color:#fff;font-weight:700;cursor:pointer;"><i class="fas fa-search" aria-hidden="true"></i> Lọc</button>
			<?php if($dtFilterKhoa || $dtFilterKw !== '' || $dtFilterHinhThieu): ?><a href="cong-giao-vien" style="height:38px;line-height:38px;padding:0 14px;border:1px solid #ccc;border-radius:6px;color:#555;text-decoration:none;">Bỏ lọc</a><?php endif; ?>
			<span style="margin-left:auto;color:#64748b;font-size:13px;">Tổng: <strong><?=count($dtStudents)?></strong> học viên</span>
		</form>

		<?php
			$dtModuleStats = array(
				'ly_thuyet' => array('dat' => 0, 'chua_dat' => 0),
				'cabin' => array('dat' => 0, 'chua_dat' => 0, 'khong_yeu_cau' => 0),
				'hinh' => array('dat' => 0, 'chua_dat' => 0),
				'dat' => array('dat' => 0, 'chua_dat' => 0)
			);
			foreach($dtStudents as $it)
			{
				$summary = $it['sum'];
				foreach(array('ly_thuyet', 'cabin', 'hinh', 'dat') as $module)
				{
					if($module === 'cabin' && empty($summary['cabin']['yeu_cau']))
					{
						$dtModuleStats['cabin']['khong_yeu_cau']++;
						continue;
					}
					$dtModuleStats[$module][!empty($summary[$module]['dat']) ? 'dat' : 'chua_dat']++;
				}
			}
		?>
		<div class="dt-gv-module-stats" aria-label="Thống kê kết quả các mô-đun">
			<?php foreach(array(
				'ly_thuyet' => array($showLt, 'Lý thuyết', 'fa-book-open'),
				'cabin' => array($showCabin, 'Cabin', 'fa-car'),
				'hinh' => array($showHinh, 'Thực hành trong hình', 'fa-road'),
				'dat' => array($showDat, 'DAT', 'fa-route')
			) as $module => $meta) { if(!$meta[0]) continue; ?>
			<div class="dt-gv-module-stat">
				<strong><i class="fas <?=$meta[2]?>" aria-hidden="true"></i><?=$meta[1]?></strong>
				<span class="is-pass"><?=$dtModuleStats[$module]['dat']?> đạt</span>
				<span class="is-pending"><?=$dtModuleStats[$module]['chua_dat']?> chưa đạt</span>
				<?php if($module === 'cabin' && $dtModuleStats['cabin']['khong_yeu_cau'] > 0): ?><span class="is-not-required"><?=$dtModuleStats['cabin']['khong_yeu_cau']?> không yêu cầu</span><?php endif; ?>
			</div>
			<?php } ?>
		</div>
		<?php if($showHinh): ?>
		<details class="dt-gv-hinh-reference">
			<summary><i class="fas fa-info-circle" aria-hidden="true"></i> Hướng dẫn cập nhật Giờ/Km thực hành trong hình</summary>
			<div class="dt-gv-hinh-reference-body">
				<p>Giáo viên nhập số liệu thực tế trước ngày kiểm tra hoàn thành khóa học. Giờ phải lớn hơn ngưỡng tối thiểu; giờ và km không vượt quá giới hạn theo hạng.</p>
				<div class="table-responsive">
					<table>
						<thead><tr><th>Hạng</th><th>Giờ thực hành</th><th>Quãng đường (km)</th></tr></thead>
						<tbody>
							<?php foreach(array('B11' => 'B (TĐ)', 'B1' => 'B (SS)', 'C1' => 'C1', 'C' => 'C', 'CE' => 'CE') as $hinhKey => $hinhLabel): $rule = dt_nguong_hinh()[$hinhKey]; ?>
							<tr><th><?=$hinhLabel?></th><td>&gt;<?=$rule['gio']?> đến <?=$rule['gio_max']?></td><td><?=$rule['km']?>–<?=$rule['km_max']?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="dt-gv-hinh-warning"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Chỉ cập nhật số liệu đúng với thực tế giảng dạy; dữ liệu thiếu sẽ được đánh dấu để bổ sung.</div>
			</div>
		</details>
		<?php endif; ?>

		<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:14px;">
			<thead><tr style="background:#f2f4f8;">
				<th style="padding:10px;border:1px solid #e0e0e0;text-align:left;"><i class="fas fa-user-graduate dt-gv-head-icon" aria-hidden="true"></i>Học viên</th>
				<th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-folder-open dt-gv-head-icon" aria-hidden="true"></i>Khóa</th>
				<?php if($showLt): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-book-open dt-gv-head-icon" aria-hidden="true"></i>Lý thuyết <span class="dt-gv-head-note">7 môn</span></th><?php endif; ?>
				<?php if($showCabin): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-car dt-gv-head-icon" aria-hidden="true"></i>Cabin</th><?php endif; ?>
				<?php if($showHinh): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-road dt-gv-head-icon" aria-hidden="true"></i>Thực hành trong hình</th><?php endif; ?>
				<?php if($showDat): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-route dt-gv-head-icon" aria-hidden="true"></i>DAT</th><?php endif; ?>
				<?php if($showExam): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-clipboard-check dt-gv-head-icon" aria-hidden="true"></i>Sát hạch gần nhất</th><?php endif; ?>
				<?php if($showKl): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-clipboard-check dt-gv-head-icon" aria-hidden="true"></i>Kết luận</th><?php endif; ?>
			</tr></thead>
			<tbody>
				<?php
				if(!empty($dtStudents)): foreach($dtStudents as $it): $hv=$it['hv']; $s=$it['sum'];
					$shResult = $showExam ? ($dtExamResults[dt_portal_sathach_cccd_key($hv['cccd'])] ?? null) : null;
					$ltMiss = $showDetail && $showLt ? dt_portal_lythuyet_cell($hv, $s['ly_thuyet']['dat']) : array();
					// DAT: luôn hiển thị nội dung còn thiếu (giống cột DAT ở màn Tổng hợp admin), không phụ thuộc show_detail.
					$datMiss = $showDat && empty($s['dat']['dat']) && !empty($s['dat']['thieu']) ? $s['dat']['thieu'] : array();
					$hinhMiss = $showDetail && $showHinh && empty($s['hinh']['dat']) ? dt_portal_hinh_missing($hv['hang'], $s['hinh']['gio'], $s['hinh']['km']) : array();
					$hinhThieuDuLieu = $showHinh && dt_hinh_thieu_du_lieu($s['hinh']['gio'], $s['hinh']['km']);
				?>
				<tr>
					<td class="dt-gv-student-cell"><?=htmlspecialchars($hv['hoten'])?><span class="dt-gv-student-meta">CCCD: <?=htmlspecialchars($hv['cccd'])?> <span>•</span> Hạng <?=htmlspecialchars($hv['hang'])?></span></td>
					<td class="dt-gv-course-cell"><?php if(!empty($hv['ten_khoa'])): ?><strong><?=htmlspecialchars($hv['ten_khoa'])?></strong><span class="dt-gv-course-code"><?=htmlspecialchars($hv['ma_khoa'])?></span><?php else: ?><?=htmlspecialchars($hv['ma_khoa'])?><?php endif; ?></td>
					<?php if($showLt): ?>
					<td class="dt-gv-status-cell"><?=dt_gv_badge($s['ly_thuyet']['dat'])?>
						<?php if(!empty($ltMiss)): ?><div class="dt-gv-miss"><?php foreach($ltMiss as $m): ?><span><i class="fas fa-circle-exclamation" aria-hidden="true"></i><?=htmlspecialchars($m)?></span><?php endforeach; ?></div><?php endif; ?>
					</td>
					<?php endif; ?>
					<?php if($showCabin): ?>
					<td class="dt-gv-status-cell"><?php if(empty($s['cabin']['yeu_cau'])): ?><span class="dt-gv-badge is-pass" style="opacity:.7;">Không YC</span><?php else: ?><?=dt_gv_badge($s['cabin']['dat'])?><?php endif; ?></td>
					<?php endif; ?>
					<?php if($showHinh): ?>
					<td class="dt-gv-hinh-cell">
						<div class="dt-gv-measures"><span>Giờ <strong><?=htmlspecialchars((string)$s['hinh']['gio'])?></strong></span><span>Km <strong><?=htmlspecialchars((string)$s['hinh']['km'])?></strong></span></div>
						<?=dt_gv_badge($s['hinh']['dat'])?>
						<?php if(!empty($hinhMiss)): ?><div class="dt-gv-miss"><?php foreach($hinhMiss as $m): ?><span><i class="fas fa-circle-exclamation" aria-hidden="true"></i><?=htmlspecialchars($m)?></span><?php endforeach; ?></div><?php endif; ?>
						<?php if($allowHinh): ?>
						<?php $hinhLimit = dt_nguong_hinh()[dt_norm_hang($hv['hang'])] ?? array(); ?>
						<details class="dt-gv-edit">
							<summary><i class="fas fa-pen" aria-hidden="true"></i> Cập nhật Giờ/Km</summary>
							<form method="post" action="index.php?com=cong-giao-vien" class="dt-gv-hinh-form">
								<input type="hidden" name="dt_act" value="savehinh">
								<input type="hidden" name="dt_csrf" value="<?=htmlspecialchars(dt_gv_csrf_token())?>">
								<input type="hidden" name="cccd" value="<?=htmlspecialchars($hv['cccd'])?>">
								<input type="hidden" name="id_khoa" value="<?=(int)$hv['id_khoa']?>">
								<input type="hidden" name="khoa" value="<?=(int)$dtFilterKhoa?>">
								<input type="hidden" name="kw" value="<?=htmlspecialchars($dtFilterKw)?>">
								<input type="hidden" name="hinh_thieu" value="<?=$dtFilterHinhThieu?>">
								<?php if($hinhLimit): ?><div class="dt-gv-hinh-rule">Hạng <?=htmlspecialchars($hv['hang'])?>: cần &gt;<?=$hinhLimit['gio']?> đến <?=$hinhLimit['gio_max']?> giờ · <?=$hinhLimit['km']?>–<?=$hinhLimit['km_max']?> km</div><?php endif; ?>
								<label class="dt-gv-field"><span>Giờ thực hành (giờ)</span><input type="number" name="gio" min="0" <?=isset($hinhLimit['gio_max']) ? 'max="'.$hinhLimit['gio_max'].'"' : ''?> step="0.1" value="<?=htmlspecialchars((string)$s['hinh']['gio'])?>" required></label>
								<label class="dt-gv-field"><span>Quãng đường (km)</span><input type="number" name="km" min="0" <?=isset($hinhLimit['km_max']) ? 'max="'.$hinhLimit['km_max'].'"' : ''?> step="0.1" value="<?=htmlspecialchars((string)$s['hinh']['km'])?>" required></label>
								<div class="dt-gv-hinh-entry-note">Nhập đúng số liệu thực tế. Hồ sơ chỉ được đánh giá đạt khi đủ yêu cầu theo hạng.</div>
								<button type="submit"><i class="fas fa-save" aria-hidden="true"></i> Lưu</button>
							</form>
						</details>
						<?php endif; ?>
						<?php if($hinhThieuDuLieu): ?><div class="dt-gv-hinh-incomplete"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> Thiếu giờ/km thực tế</div><?php endif; ?>
					</td>
					<?php endif; ?>
					<?php if($showDat): ?>
					<td class="dt-gv-status-cell"><?=dt_gv_badge($s['dat']['dat'])?>
						<?php $dagg = isset($s['dat']['agg']) ? $s['dat']['agg'] : null; if($dagg): ?><div class="dt-gv-dat-agg">A <?=$dagg['a']?>h · B <?=$dagg['b']?>h · C <?=$dagg['c']?>h · D <?=$dagg['d']?>h · E <?=$dagg['e']?> km</div><?php endif; ?>
						<?php if(!empty($datMiss)): ?><div class="dt-gv-dat-missing"><?php foreach($datMiss as $m): ?><div class="dt-gv-dat-missing-item"><i class="fas fa-exclamation-circle" aria-hidden="true"></i><span><?=htmlspecialchars($m)?></span></div><?php endforeach; ?></div><?php endif; ?>
					</td>
					<?php endif; ?>
					<?php if($showExam): ?>
					<td class="dt-gv-status-cell">
						<?php if($shResult): $examLabel = trim((string)($shResult['ket_luan'] ?: $shResult['ket_qua'])); $examPassed = dt_portal_sathach_pass($examLabel); ?>
						<div><?=htmlspecialchars(dt_portal_sathach_display_date($shResult['ngay_sh']))?></div>
						<div class="dt-gv-exam-result"><span class="dt-gv-badge <?=$examPassed?'is-pass':'is-pending'?>"><?=htmlspecialchars($examLabel !== '' ? $examLabel : 'Chưa có kết luận')?></span> <span class="text-muted"><?=htmlspecialchars($shResult['hang'])?></span></div>
						<?php if(!empty($shResult['so_gplx'])): ?><div class="dt-gv-student-meta">GPLX: <?=htmlspecialchars($shResult['so_gplx'])?></div><?php endif; ?>
						<?php else: ?><span class="dt-gv-badge is-pending">Chưa có KQ</span><?php endif; ?>
					</td>
					<?php endif; ?>
					<?php if($showKl): ?><td class="dt-gv-conclusion-cell"><span class="dt-gv-conclusion <?=$s['du_dieu_kien']?'is-ready':'is-not-ready'?>"><?=$s['du_dieu_kien']?'Đủ ĐK':'Chưa đủ'?></span></td><?php endif; ?>
				</tr>
				<?php endforeach; else: ?>
				<tr><td colspan="<?=$colCount?>" style="padding:16px;text-align:center;color:#888;border:1px solid #e0e0e0;">Chưa có học viên nào được phân công cho bạn.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		</div>
		<details class="dt-gv-history">
			<summary><i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Lịch sử cập nhật gần đây <span class="dt-gv-history-sub">(30 ngày)</span><span class="dt-gv-history-count"><?=count($dtHistory)?></span></summary>
			<?php if(!empty($dtHistory)): ?>
			<ul class="dt-gv-history-list">
				<?php foreach($dtHistory as $h): ?>
				<li>
					<span class="dt-gv-history-time"><i class="far fa-calendar" aria-hidden="true"></i><?=date('d/m/Y H:i', (int)$h['ngaytao'])?></span>
					<span class="dt-gv-history-hv"><i class="fas fa-user-graduate" aria-hidden="true"></i><?=htmlspecialchars($h['hv_hoten'] ?: ('CCCD '.$h['hv_cccd']))?></span>
					<span class="dt-gv-history-act"><?=htmlspecialchars($h['noi_dung'])?></span>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php else: ?>
			<div class="dt-gv-history-empty">Chưa có cập nhật nào trong 30 ngày qua.</div>
			<?php endif; ?>
		</details>
		<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>

<style>
.dt-gv-tabs { display: flex; gap: 6px; margin-bottom: 16px; border-bottom: 2px solid #e2e8f0; }
.dt-gv-tabs a { display: inline-flex; align-items: center; gap: 7px; padding: 10px 20px; margin-bottom: -2px; border-bottom: 3px solid transparent; color: #475569; font-weight: 700; text-decoration: none; }
.dt-gv-tabs a:hover { color: #2954f2; }
.dt-gv-tabs a.is-active { color: #2954f2; border-bottom-color: #2954f2; }
.dt-gv-slip { max-width: 900px; border: 1px solid #d0d5dd; border-radius: 16px; padding: 18px; background: #fff; }
.dt-gv-slip-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 14px; color: #374151; }
.dt-gv-slip-info strong { color: #111827; }
.dt-gv-slip-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 8px; }
.dt-gv-slip-title { font-size: 17px; font-weight: 700; color: #0f766e; }
.dt-gv-slip-month { display: flex; align-items: center; gap: 8px; margin: 0; }
.dt-gv-slip-month label { margin: 0; font-weight: 600; color: #374151; }
.dt-gv-slip-month select { height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 8px; }
.dt-gv-slip-row { display: grid; grid-template-columns: minmax(200px, 1fr) auto; gap: 6px 12px; padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
.dt-gv-slip-row span { color: #374151; font-weight: 600; }
.dt-gv-slip-row strong { color: #111827; font-weight: 400; text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.dt-gv-slip-row.is-strong span, .dt-gv-slip-row.is-strong strong { color: #0f766e; font-weight: 700; }
.dt-gv-slip-gap { height: 10px; }
.dt-gv-slip-note { margin: 0 0 14px; padding: 10px 12px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 14px; }
.dt-gv-slip-empty { color: #64748b; padding: 8px 0; }
.w_1000.dt-gv-page { width: min(1400px, calc(100% - 32px)); max-width: 1400px; }
.dt-gv-content { max-width: 1360px; margin: 0 auto; }
.dt-gv-page table { table-layout: auto; }
.dt-gv-page th { min-width: 104px; color: #334155; white-space: nowrap; }
.dt-gv-head-icon { margin-right: 7px; color: #3155d9; }
.dt-gv-head-note { display: inline-block; color: #64748b; font-size: 11px; font-weight: 400; }
.dt-gv-student-cell { min-width: 190px; padding: 10px !important; border: 1px solid #e0e0e0; font-weight: 700; }
.dt-gv-student-meta { display: block; margin-top: 4px; color: #778397; font-size: 12px; font-weight: 400; }
.dt-gv-course-cell { min-width: 110px; padding: 10px !important; border: 1px solid #e0e0e0; text-align: center; font-weight: 600; }
.dt-gv-status-cell, .dt-gv-conclusion-cell { min-width: 145px; padding: 10px !important; border: 1px solid #e0e0e0; text-align: center; vertical-align: middle; }
.dt-gv-hinh-cell { min-width: 170px; padding: 10px !important; border: 1px solid #e0e0e0; text-align: center; vertical-align: middle; }
.dt-gv-miss { margin-top: 7px; display: flex; flex-direction: column; gap: 3px; text-align: left; }
.dt-gv-miss span { display: block; font-size: 12px; line-height: 1.35; color: #9a3412; background: #fff5f1; border-left: 2px solid #ef9a76; border-radius: 3px; padding: 4px 6px; }
.dt-gv-dat-agg { margin-top: 6px; font-size: 12px; line-height: 1.4; color: #475569; }
.dt-gv-module-stats { display: flex; flex-wrap: wrap; gap: 8px; margin: -4px 0 14px; }
.dt-gv-module-stat { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 8px 11px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #475569; font-size: 12px; }
.dt-gv-module-stat strong { color: #1e293b; font-size: 13px; }
.dt-gv-module-stat strong i { margin-right: 5px; color: #3155d9; }
.dt-gv-module-stat .is-pass { color: #176b3a; font-weight: 700; }
.dt-gv-module-stat .is-pending { color: #a9441b; font-weight: 700; }
.dt-gv-module-stat .is-not-required { color: #64748b; font-weight: 600; }
.dt-gv-edit { margin-top: 8px; }
.dt-gv-edit summary { display: inline-flex; align-items: center; gap: 5px; color: #3155d9; font-size: 12px; font-weight: 600; cursor: pointer; list-style: none; }
.dt-gv-edit summary::-webkit-details-marker { display: none; }
.dt-gv-edit[open] summary { margin-bottom: 8px; }
.dt-gv-hinh-form { margin: 0 auto; display: flex; flex-direction: column; gap: 7px; width: 100%; max-width: 190px; text-align: left; white-space: normal; }
.dt-gv-field { display: flex; flex-direction: column; gap: 3px; margin: 0; }
.dt-gv-field span { color: #64748b; font-size: 11px; font-weight: 600; }
.dt-gv-hinh-form input { width: 100%; height: 36px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
.dt-gv-hinh-form input:focus { outline: none; border-color: #3155d9; box-shadow: 0 0 0 2px rgba(49,85,217,.15); }
.dt-gv-hinh-form button { width: 100%; height: 36px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: none; border-radius: 6px; background: #1e9e4a; color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; }
.dt-gv-hinh-form button:hover { background: #18823d; }
.dt-gv-hinh-reference { margin: 0 0 14px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; overflow: hidden; }
.dt-gv-hinh-reference > summary { padding: 11px 14px; color: #1f3a8a; background: #f3f6ff; font-weight: 700; cursor: pointer; list-style: none; }
.dt-gv-hinh-reference > summary::-webkit-details-marker { display: none; }
.dt-gv-hinh-reference > summary i { margin-right: 6px; color: #3155d9; }
.dt-gv-hinh-reference-body { padding: 12px 14px; }
.dt-gv-hinh-reference-body p { margin: 0 0 9px; color: #475569; font-size: 13px; }
.dt-gv-hinh-reference table { width: 100%; border-collapse: collapse; text-align: center; font-size: 13px; }
.dt-gv-hinh-reference th, .dt-gv-hinh-reference td { padding: 6px 9px; border: 1px solid #d5dce7; }
.dt-gv-hinh-reference thead { background: #f1f5f9; }
.dt-gv-hinh-warning { margin-top: 9px; padding: 8px 10px; border-left: 3px solid #c0392b; border-radius: 4px; background: #fff4f2; color: #8d2922; font-size: 12px; font-weight: 600; }
.dt-gv-hinh-rule { padding: 7px 8px; border-radius: 5px; background: #f3f6ff; color: #2947a5; font-size: 11px; font-weight: 700; line-height: 1.4; }
.dt-gv-hinh-entry-note { color: #64748b; font-size: 11px; line-height: 1.35; }
.dt-gv-hinh-incomplete { margin-top: 5px; color: #a9441b; font-size: 11px; font-weight: 700; white-space: normal; }
.dt-gv-hinh-incomplete i { margin-right: 3px; }
.dt-gv-history { margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fbfdff; overflow: hidden; }
.dt-gv-history > summary { display: flex; align-items: center; gap: 8px; padding: 12px 16px; font-weight: 700; color: #1f3a8a; cursor: pointer; list-style: none; background: #eef3ff; }
.dt-gv-history > summary::-webkit-details-marker { display: none; }
.dt-gv-history-sub { color: #64748b; font-size: 12px; font-weight: 400; }
.dt-gv-history-count { margin-left: auto; min-width: 22px; height: 22px; padding: 0 7px; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; background: #3155d9; color: #fff; font-size: 12px; }
.dt-gv-history-list { list-style: none; margin: 0; padding: 6px 0; max-height: 280px; overflow-y: auto; }
.dt-gv-history-list li { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 14px; padding: 9px 16px; border-top: 1px solid #eef1f6; font-size: 13px; }
.dt-gv-history-list li:first-child { border-top: none; }
.dt-gv-history-time { color: #64748b; font-variant-numeric: tabular-nums; white-space: nowrap; }
.dt-gv-history-time i, .dt-gv-history-hv i { margin-right: 5px; color: #94a3b8; }
.dt-gv-history-hv { font-weight: 700; color: #0f172a; }
.dt-gv-history-act { color: #166534; background: #e7f5ec; border-radius: 5px; padding: 3px 9px; }
.dt-gv-history-empty { padding: 14px 16px; color: #94a3b8; font-size: 13px; }
@media (max-width: 640px){
	.w_1000.dt-gv-page { width: calc(100% - 20px); }
	.w_1000 table th, .w_1000 table td { font-size: 13px; padding: 6px !important; }
	.dt-gv-page th { min-width: 120px; }
	.dt-gv-student-cell { min-width: 165px; }
	.w_1000 form[action="index.php?com=cong-giao-vien"] input[type=text],
	.w_1000 form[action="index.php?com=cong-giao-vien"] input[type=password] { font-size: 16px; }
}
.dt-gv-badge { display: inline-block; min-width: 44px; padding: 4px 9px; border-radius: 999px; font-size: 12px; line-height: 1.15; font-weight: 700; }
.dt-gv-badge.is-pass { background: #e7f5ec; color: #176b3a; }
.dt-gv-badge.is-pending { background: #fff0e8; color: #a9441b; }
.dt-gv-hinh-cell { padding: 8px !important; border: 1px solid #e0e0e0; text-align: center; white-space: nowrap; }
.dt-gv-measures { display: flex; justify-content: center; gap: 12px; margin-bottom: 6px; color: #64748b; font-size: 12px; }
.dt-gv-measures strong { color: #334155; font-weight: 700; }
.dt-gv-conclusion { display: inline-block; padding: 5px 9px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.dt-gv-conclusion.is-ready { background: #e7f5ec; color: #176b3a; }
.dt-gv-conclusion.is-not-ready { background: #fff0e8; color: #a9441b; }
.dt-gv-miss { margin-top: 6px; display: flex; flex-direction: column; gap: 2px; text-align: left; }
.dt-gv-miss span { font-size: 11.5px; line-height: 1.3; color: #a9441b; background: #fff4f2; border-radius: 4px; padding: 2px 6px; }
.dt-gv-dat-missing { margin-top: 6px; display: flex !important; flex-direction: column !important; align-items: stretch; gap: 3px; color: #9f2525; font-size: 12px; line-height: 1.45; text-align: left; white-space: normal; }
.dt-gv-dat-missing-item { display: flex !important; align-items: flex-start; gap: 6px; width: 100%; box-sizing: border-box; padding: 3px 6px; background: #fff4f2; border-radius: 4px; }
.dt-gv-dat-missing-item i { flex: 0 0 auto; margin-top: 2px; color: #c2410c; }
.dt-gv-dat-missing-item span { display: block; min-width: 0; }
.dt-gv-miss i { margin-right: 5px; color: #c2410c; }
.dt-gv-hinh-form { margin-top: 8px; display: flex; gap: 4px; justify-content: center; flex-wrap: wrap; }
.dt-gv-hinh-form input { height: 30px; padding: 0 6px; border: 1px solid #ccc; border-radius: 5px; font-size: 12px; }
.dt-gv-hinh-form button { height: 30px; padding: 0 10px; border: none; border-radius: 5px; background: #1e9e4a; color: #fff; font-size: 12px; font-weight: 700; cursor: pointer; }
.dt-gv-hinh-form .dt-gv-field, .dt-gv-hinh-rule, .dt-gv-hinh-entry-note { width: 100%; }
.dt-gv-vehicles { max-width: 1050px; }
.dt-gv-vehicle-heading, .dt-gv-repair-title, .dt-gv-repair-form-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.dt-gv-vehicle-heading { margin: 4px 0 15px; }
.dt-gv-vehicle-heading h2 { margin: 0; color: #172554; font-size: 20px; }
.dt-gv-vehicle-heading h2 i { margin-right: 7px; color: #3155d9; }
.dt-gv-vehicle-heading p { margin: 5px 0 0; color: #64748b; font-size: 13px; }
.dt-gv-vehicle-count { padding: 6px 11px; border-radius: 999px; background: #eef3ff; color: #2947a5; font-size: 12px; font-weight: 700; white-space: nowrap; }
.dt-gv-vehicle-search { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.dt-gv-vehicle-search label { margin: 0; color: #475569; font-size: 13px; font-weight: 700; }
.dt-gv-vehicle-search input { flex: 1; min-width: 0; height: 40px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 7px; }
.dt-gv-vehicle-search button, .dt-gv-repair-add { height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 0 14px; border: 0; border-radius: 7px; background: #3155d9; color: white; font-weight: 700; text-decoration: none; cursor: pointer; }
.dt-gv-vehicle-search button:hover, .dt-gv-repair-add:hover { background: #2444bf; color: white; }
.dt-gv-vehicle-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 8px; }
.dt-gv-vehicle-item { min-width: 0; display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1px solid #e2e8f0; border-radius: 9px; background: white; color: #334155; text-decoration: none; transition: border-color .15s, box-shadow .15s; }
.dt-gv-vehicle-item:hover, .dt-gv-vehicle-item.is-selected { border-color: #93a8f4; box-shadow: 0 2px 8px rgba(49,85,217,.09); text-decoration: none; }
.dt-gv-vehicle-item.is-selected { background: #f7f8ff; }
.dt-gv-vehicle-icon { width: 36px; height: 36px; flex: 0 0 36px; display: grid; place-items: center; border-radius: 9px; background: #eef3ff; color: #3155d9; }
.dt-gv-vehicle-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.dt-gv-vehicle-info strong { color: #172554; font-size: 14px; }
.dt-gv-vehicle-info span, .dt-gv-vehicle-history { color: #64748b; font-size: 11px; }
.dt-gv-vehicle-history { display: flex; flex-direction: column; align-items: flex-end; gap: 3px; text-align: right; white-space: nowrap; }
.dt-gv-vehicle-history em { color: #9a5a00; font-style: normal; font-weight: 700; }
.dt-gv-vehicle-arrow { color: #94a3b8; font-size: 11px; }
.dt-gv-vehicle-empty, .dt-gv-repair-empty { display: flex; flex-direction: column; align-items: center; gap: 7px; padding: 24px; border: 1px dashed #cbd5e1; border-radius: 10px; color: #64748b; text-align: center; }
.dt-gv-vehicle-empty strong, .dt-gv-repair-empty strong { color: #334155; }
.dt-gv-repair-panel { margin-top: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; overflow: hidden; }
.dt-gv-repair-title { padding: 16px 18px; border-bottom: 1px solid #e8edf4; }
.dt-gv-repair-title > div > span { color: #64748b; font-size: 10px; font-weight: 700; letter-spacing: .08em; }
.dt-gv-repair-title h3 { margin: 3px 0; color: #172554; font-size: 20px; }
.dt-gv-repair-title p { margin: 0; color: #64748b; font-size: 12px; }
.dt-gv-repair-add { height: 38px; font-size: 13px; }
.dt-gv-repair-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e8edf4; }
.dt-gv-repair-stats > div { display: flex; flex-direction: column; gap: 3px; }
.dt-gv-repair-stats span { color: #64748b; font-size: 11px; }
.dt-gv-repair-stats strong { color: #172554; font-size: 16px; }
.dt-gv-repair-form { margin: 16px; padding: 15px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fbfdff; }
.dt-gv-repair-form-heading { align-items: flex-start; margin-bottom: 13px; }
.dt-gv-repair-form-heading > div { display: flex; flex-direction: column; gap: 3px; }
.dt-gv-repair-form-heading strong { color: #172554; font-size: 15px; }
.dt-gv-repair-form-heading span, .dt-gv-repair-form-heading a { color: #64748b; font-size: 12px; }
.dt-gv-repair-form-heading a { text-decoration: none; }
.dt-gv-repair-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 11px; }
.dt-gv-repair-fields label { min-width: 0; display: flex; flex-direction: column; gap: 5px; margin: 0; }
.dt-gv-repair-fields label > span { color: #475569; font-size: 12px; font-weight: 700; }
.dt-gv-repair-fields label b { color: #c0392b; }
.dt-gv-repair-fields input, .dt-gv-repair-fields textarea { width: 100%; min-height: 39px; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: white; color: #1e293b; font: inherit; font-size: 13px; box-sizing: border-box; }
.dt-gv-repair-fields input[type=file] { min-height: 43px; padding: 7px; }
.dt-gv-repair-fields textarea { resize: vertical; }
.dt-gv-repair-fields small { color: #a12a23; font-size: 11px; line-height: 1.4; }
.dt-gv-repair-fields .dt-gv-repair-description { grid-column: 1 / -1; }
.dt-gv-repair-fields .dt-gv-repair-description input[type=file] + small { color: #64748b; }
.dt-gv-upload-feedback { color: #64748b; font-size: 11px; }
.dt-gv-upload-feedback.is-error { color: #a12a23; font-weight: 700; }
.dt-gv-repair-form-actions { display: flex; align-items: center; gap: 12px; margin-top: 13px; }
.dt-gv-repair-form-actions button { min-height: 39px; padding: 0 15px; border: 0; border-radius: 6px; background: #1e9e4a; color: #fff; font-weight: 700; cursor: pointer; }
.dt-gv-repair-form-actions a { color: #64748b; font-size: 13px; text-decoration: none; }
.dt-gv-repair-history { padding: 15px 16px 4px; }
.dt-gv-repair-history > h4 { display: flex; align-items: center; gap: 7px; margin: 0 0 10px; color: #1e293b; font-size: 15px; }
.dt-gv-repair-history > h4 span { display: inline-grid; min-width: 21px; height: 21px; place-items: center; padding: 0 5px; border-radius: 999px; background: #eef3ff; color: #2947a5; font-size: 11px; }
.dt-gv-repair-card { margin-bottom: 10px; padding: 13px; border: 1px solid #e2e8f0; border-radius: 9px; }
.dt-gv-repair-card-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.dt-gv-repair-card-top > div { display: flex; align-items: center; gap: 8px; }
.dt-gv-repair-card time { color: #334155; font-size: 13px; font-weight: 700; }
.dt-gv-repair-status { padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.dt-gv-repair-status.is-progress { background: #fff3d6; color: #8a5a00; }
.dt-gv-repair-status.is-done { background: #e7f5ec; color: #176b3a; }
.dt-gv-status-form { display: flex; align-items: center; gap: 6px; }
.dt-gv-status-form label { margin: 0; color: #64748b; font-size: 10px; }
.dt-gv-status-form select { height: 31px; border: 1px solid #cbd5e1; border-radius: 5px; color: #334155; font-size: 11px; }
.dt-gv-status-form button { height: 31px; padding: 0 9px; border: 1px solid #3155d9; border-radius: 5px; background: white; color: #3155d9; font-size: 11px; font-weight: 700; cursor: pointer; }
.dt-gv-repair-card h5 { margin: 10px 0 7px; color: #1e293b; font-size: 14px; line-height: 1.5; }
.dt-gv-repair-meta { display: flex; flex-wrap: wrap; gap: 7px 15px; color: #64748b; font-size: 11px; }
.dt-gv-repair-meta i { width: 14px; color: #94a3b8; }
.dt-gv-repair-notes { margin: 8px 0 0; padding-top: 8px; border-top: 1px solid #f1f5f9; color: #64748b; font-size: 12px; }
.dt-gv-repair-images { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.dt-gv-repair-images img { width: 100px; height: 76px; object-fit: cover; border: 1px solid #e2e8f0; border-radius: 6px; }
.dt-gv-repair-empty { padding: 20px; border: 0; }
@media (max-width: 640px) {
	.dt-gv-vehicle-heading { align-items: flex-start; }
	.dt-gv-vehicle-search { flex-wrap: wrap; }
	.dt-gv-vehicle-search label { width: 100%; }
	.dt-gv-vehicle-search input { flex: 1 1 150px; }
	.dt-gv-vehicle-list { grid-template-columns: 1fr; }
	.dt-gv-repair-title { align-items: flex-start; }
	.dt-gv-repair-title h3 { font-size: 18px; }
	.dt-gv-repair-add { height: auto; min-height: 36px; padding: 0 10px; font-size: 12px; }
	.dt-gv-repair-stats { padding: 10px; }
	.dt-gv-repair-stats strong { font-size: 13px; }
	.dt-gv-repair-stats span { font-size: 10px; }
	.dt-gv-repair-form { margin: 10px; padding: 11px; }
	.dt-gv-repair-fields { grid-template-columns: 1fr; }
	.dt-gv-repair-fields .dt-gv-repair-description { grid-column: auto; }
	.dt-gv-repair-history { padding: 12px 10px 2px; }
	.dt-gv-repair-card { padding: 10px; }
	.dt-gv-repair-card-top { align-items: flex-start; flex-direction: column; }
	.dt-gv-status-form { flex-wrap: wrap; }
}
</style>
<script>
document.querySelectorAll('.dt-gv-repair-form input[type="file"]').forEach(function(input) {
	input.addEventListener('change', function() {
		var feedback = input.parentNode.querySelector('.dt-gv-upload-feedback');
		var files = Array.prototype.slice.call(input.files || []);
		var invalid = files.length > 10 || files.some(function(file) { return file.size > 5242880; });
		feedback.textContent = invalid ? 'Chọn tối đa 10 ảnh, mỗi ảnh không quá 5 MB.' : (files.length ? 'Đã chọn ' + files.length + ' ảnh.' : '');
		feedback.classList.toggle('is-error', invalid);
		if(invalid) input.value = '';
	});
});
</script>
