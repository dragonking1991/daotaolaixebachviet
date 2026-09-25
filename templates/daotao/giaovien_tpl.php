<div class="w_1000">
	<div class="title-main"><span><?=(@$title_crumb != '') ? $title_crumb : 'Cổng giáo viên'?></span></div>

	<?php if(!empty($dtGvMsg)): ?><div style="max-width:900px;margin:0 auto 12px;padding:10px 14px;background:#e6f7ec;border:1px solid #1e9e4a;border-radius:8px;color:#12673a;"><?=htmlspecialchars($dtGvMsg)?></div><?php endif; ?>
	<?php if(!empty($dtGvErr)): ?><div style="max-width:900px;margin:0 auto 12px;padding:10px 14px;background:#fdecea;border:1px solid #c0392b;border-radius:8px;color:#912018;"><?=htmlspecialchars($dtGvErr)?></div><?php endif; ?>

	<?php if(empty($dtGv)): ?>
	<div style="max-width:400px;margin:0 auto;border:1px solid #ddd;border-radius:12px;padding:24px;">
		<form method="post" action="cong-giao-vien">
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
	</div>
	<?php else: ?>
	<div style="max-width:1000px;margin:0 auto;">
		<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
			<div><strong style="font-size:16px;">Xin chào, <?=htmlspecialchars($dtGv['hoten'])?></strong> <span style="color:#888;">(CCCD: <?=htmlspecialchars($dtGv['cccd'])?>)</span></div>
			<div>
				<button type="button" onclick="document.getElementById('dt-changepass').style.display=(document.getElementById('dt-changepass').style.display==='none'?'block':'none')" style="height:36px;padding:0 14px;border:1px solid #2954f2;background:#fff;color:#2954f2;border-radius:6px;cursor:pointer;">Đổi mật khẩu</button>
				<form method="post" action="cong-giao-vien" style="display:inline;"><input type="hidden" name="dt_act" value="logout"><button type="submit" style="height:36px;padding:0 14px;border:none;background:#c0392b;color:#fff;border-radius:6px;cursor:pointer;">Đăng xuất</button></form>
			</div>
		</div>

		<div id="dt-changepass" style="display:none;max-width:420px;margin:0 0 16px;border:1px solid #ddd;border-radius:10px;padding:16px;">
			<form method="post" action="cong-giao-vien">
				<input type="hidden" name="dt_act" value="changepass">
				<div style="margin-bottom:10px;"><label style="display:block;font-size:13px;color:#555;">Mật khẩu hiện tại</label><input type="password" name="old_pass" required style="width:100%;height:38px;padding:0 10px;border:1px solid #ccc;border-radius:6px;"></div>
				<div style="margin-bottom:12px;"><label style="display:block;font-size:13px;color:#555;">Mật khẩu mới</label><input type="password" name="new_pass" required style="width:100%;height:38px;padding:0 10px;border:1px solid #ccc;border-radius:6px;"></div>
				<button type="submit" style="height:38px;padding:0 16px;border:none;background:#2954f2;color:#fff;border-radius:6px;cursor:pointer;">Lưu mật khẩu</button>
			</form>
		</div>

		<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:14px;">
			<thead><tr style="background:#f2f4f8;">
				<th style="padding:8px;border:1px solid #e0e0e0;text-align:left;">Học viên</th>
				<th style="padding:8px;border:1px solid #e0e0e0;">Khóa</th>
				<th style="padding:8px;border:1px solid #e0e0e0;">Lý thuyết</th>
				<th style="padding:8px;border:1px solid #e0e0e0;">Cabin</th>
				<th style="padding:8px;border:1px solid #e0e0e0;">Thực hành trong hình</th>
				<th style="padding:8px;border:1px solid #e0e0e0;">DAT</th>
				<th style="padding:8px;border:1px solid #e0e0e0;">Kết luận</th>
			</tr></thead>
			<tbody>
				<?php
				if(!function_exists('dt_gv_badge')){ function dt_gv_badge($ok){ return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:'.($ok?'#1e9e4a':'#c0392b').';color:#fff;font-size:12px;">'.($ok?'Đạt':'Chưa').'</span>'; } }
				if(!empty($dtStudents)): foreach($dtStudents as $it): $hv=$it['hv']; $s=$it['sum']; ?>
				<tr>
					<td style="padding:8px;border:1px solid #e0e0e0;"><?=htmlspecialchars($hv['hoten'])?><br><span style="color:#888;font-size:12px;">CCCD: <?=htmlspecialchars($hv['cccd'])?> • Hạng <?=htmlspecialchars($hv['hang'])?></span></td>
					<td style="padding:8px;border:1px solid #e0e0e0;text-align:center;"><?=htmlspecialchars($hv['ma_khoa'])?></td>
					<td style="padding:8px;border:1px solid #e0e0e0;text-align:center;"><?=dt_gv_badge($s['ly_thuyet']['dat'])?></td>
					<td style="padding:8px;border:1px solid #e0e0e0;text-align:center;"><?=dt_gv_badge($s['cabin']['dat'])?></td>
					<td style="padding:8px;border:1px solid #e0e0e0;">
						<form method="post" action="cong-giao-vien" style="display:flex;gap:4px;align-items:center;">
							<input type="hidden" name="dt_act" value="savehinh">
							<input type="hidden" name="id_khoa" value="<?=(int)$hv['id_khoa']?>">
							<input type="hidden" name="cccd" value="<?=htmlspecialchars($hv['cccd'])?>">
							<input type="number" step="0.01" name="gio" value="<?=$s['hinh']['gio']?>" placeholder="giờ" style="width:64px;height:32px;border:1px solid #ccc;border-radius:5px;padding:0 6px;">
							<input type="number" step="0.01" name="km" value="<?=$s['hinh']['km']?>" placeholder="km" style="width:64px;height:32px;border:1px solid #ccc;border-radius:5px;padding:0 6px;">
							<button type="submit" style="height:32px;padding:0 8px;border:none;background:#2954f2;color:#fff;border-radius:5px;cursor:pointer;">Lưu</button>
							<?=dt_gv_badge($s['hinh']['dat'])?>
						</form>
					</td>
					<td style="padding:8px;border:1px solid #e0e0e0;text-align:center;"><?=dt_gv_badge($s['dat']['dat'])?></td>
					<td style="padding:8px;border:1px solid #e0e0e0;text-align:center;"><?php if($s['du_dieu_kien']): ?><span style="color:#1e9e4a;font-weight:700;">Đủ ĐK</span><?php else: ?><span style="color:#c0392b;font-weight:700;">Chưa đủ</span><?php endif; ?></td>
				</tr>
				<?php endforeach; else: ?>
				<tr><td colspan="7" style="padding:16px;text-align:center;color:#888;border:1px solid #e0e0e0;">Chưa có học viên nào được phân công cho bạn.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		</div>
	</div>
	<?php endif; ?>
</div>
