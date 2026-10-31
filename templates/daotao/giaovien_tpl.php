<div class="w_1000 dt-gv-page">
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
		<div style="text-align:center;margin-top:14px;">
			<a href="javascript:void(0)" onclick="var f=document.getElementById('dt-forgot');f.style.display=(f.style.display==='none'?'block':'none');" style="font-size:13px;color:#2954f2;text-decoration:none;">Quên mật khẩu?</a>
		</div>
		<div id="dt-forgot" style="display:<?=(isset($dtAct)&&$dtAct==='resetpass')?'block':'none'?>;margin-top:14px;border-top:1px solid #eee;padding-top:14px;">
			<div style="font-size:13px;color:#555;margin-bottom:10px;">Xác minh bằng ngày sinh và số điện thoại đã đăng ký với trung tâm. Nếu hồ sơ chưa có đủ hai thông tin này, vui lòng liên hệ trung tâm để được cấp lại mật khẩu.</div>
			<form method="post" action="cong-giao-vien">
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
				<form method="post" action="cong-giao-vien" style="display:inline;"><input type="hidden" name="dt_act" value="logout"><input type="hidden" name="dt_csrf" value="<?=htmlspecialchars(dt_gv_csrf_token())?>"><button type="submit" style="height:36px;padding:0 14px;border:none;background:#c0392b;color:#fff;border-radius:6px;cursor:pointer;">Đăng xuất</button></form>
			</div>
		</div>

		<?php $dtMustChange = !empty($dtGv['must_change']); ?>
		<?php if($dtMustChange): ?><div style="max-width:420px;margin:0 0 10px;padding:10px 14px;background:#fff7e6;border:1px solid #f0ad4e;border-radius:8px;color:#8a5a00;">Bạn đang dùng mật khẩu mặc định (CCCD). Vui lòng đổi mật khẩu để tiếp tục sử dụng cổng giáo viên.</div><?php endif; ?>
		<div id="dt-changepass" style="display:<?=$dtMustChange?'block':'none'?>;max-width:420px;margin:0 0 16px;border:1px solid #ddd;border-radius:10px;padding:16px;">
			<form method="post" action="cong-giao-vien">
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
			$showKl = !empty($dtCfg['show_ketluan']); $showDetail = !empty($dtCfg['show_detail']);
			$allowHinh = !empty($dtCfg['allow_hinh_input']);
			$colCount = 2 + ($showLt?1:0) + ($showCabin?1:0) + ($showHinh?1:0) + ($showDat?1:0) + ($showKl?1:0);
		?>
		<details class="dt-gv-history" <?=!empty($dtHistory)?'open':''?>>
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

		<form method="get" action="cong-giao-vien" class="dt-gv-filter" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:14px;">
			<select name="khoa" style="height:38px;padding:0 10px;border:1px solid #ccc;border-radius:6px;min-width:200px;">
				<option value="0">— Tất cả khóa —</option>
				<?php foreach($dtKhoaList as $k): ?><option value="<?=$k['id']?>" <?=($dtFilterKhoa==$k['id'])?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
			</select>
			<input type="text" name="kw" value="<?=htmlspecialchars($dtFilterKw)?>" placeholder="Tên / CCCD / Mã HV" style="height:38px;padding:0 12px;border:1px solid #ccc;border-radius:6px;min-width:200px;">
			<button type="submit" style="height:38px;padding:0 18px;border:none;border-radius:6px;background:#2954f2;color:#fff;font-weight:700;cursor:pointer;"><i class="fas fa-search" aria-hidden="true"></i> Lọc</button>
			<?php if($dtFilterKhoa || $dtFilterKw !== ''): ?><a href="cong-giao-vien" style="height:38px;line-height:38px;padding:0 14px;border:1px solid #ccc;border-radius:6px;color:#555;text-decoration:none;">Bỏ lọc</a><?php endif; ?>
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

		<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:14px;">
			<thead><tr style="background:#f2f4f8;">
				<th style="padding:10px;border:1px solid #e0e0e0;text-align:left;"><i class="fas fa-user-graduate dt-gv-head-icon" aria-hidden="true"></i>Học viên</th>
				<th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-folder-open dt-gv-head-icon" aria-hidden="true"></i>Khóa</th>
				<?php if($showLt): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-book-open dt-gv-head-icon" aria-hidden="true"></i>Lý thuyết <span class="dt-gv-head-note">7 môn</span></th><?php endif; ?>
				<?php if($showCabin): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-car dt-gv-head-icon" aria-hidden="true"></i>Cabin</th><?php endif; ?>
				<?php if($showHinh): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-road dt-gv-head-icon" aria-hidden="true"></i>Thực hành trong hình</th><?php endif; ?>
				<?php if($showDat): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-route dt-gv-head-icon" aria-hidden="true"></i>DAT</th><?php endif; ?>
				<?php if($showKl): ?><th style="padding:10px;border:1px solid #e0e0e0;"><i class="fas fa-clipboard-check dt-gv-head-icon" aria-hidden="true"></i>Kết luận</th><?php endif; ?>
			</tr></thead>
			<tbody>
				<?php
				if(!empty($dtStudents)): foreach($dtStudents as $it): $hv=$it['hv']; $s=$it['sum'];
					$ltMiss = $showDetail && $showLt ? dt_portal_lythuyet_cell($hv, $s['ly_thuyet']['dat']) : array();
					// DAT: luôn hiển thị nội dung còn thiếu (giống cột DAT ở màn Tổng hợp admin), không phụ thuộc show_detail.
					$datMiss = $showDat && empty($s['dat']['dat']) && !empty($s['dat']['thieu']) ? $s['dat']['thieu'] : array();
					$hinhMiss = $showDetail && $showHinh && empty($s['hinh']['dat']) ? dt_portal_hinh_missing($hv['hang'], $s['hinh']['gio'], $s['hinh']['km']) : array();
				?>
				<tr>
					<td class="dt-gv-student-cell"><?=htmlspecialchars($hv['hoten'])?><span class="dt-gv-student-meta">CCCD: <?=htmlspecialchars($hv['cccd'])?> <span>•</span> Hạng <?=htmlspecialchars($hv['hang'])?></span></td>
					<td class="dt-gv-course-cell"><?=htmlspecialchars($hv['ma_khoa'])?></td>
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
							<form method="post" action="cong-giao-vien" class="dt-gv-hinh-form">
								<input type="hidden" name="dt_act" value="savehinh">
								<input type="hidden" name="dt_csrf" value="<?=htmlspecialchars(dt_gv_csrf_token())?>">
								<input type="hidden" name="cccd" value="<?=htmlspecialchars($hv['cccd'])?>">
								<input type="hidden" name="id_khoa" value="<?=(int)$hv['id_khoa']?>">
								<input type="hidden" name="khoa" value="<?=(int)$dtFilterKhoa?>">
								<input type="hidden" name="kw" value="<?=htmlspecialchars($dtFilterKw)?>">
								<label class="dt-gv-field"><span>Giờ thực hành<?=isset($hinhLimit['gio_max']) ? ' (tối đa '.$hinhLimit['gio_max'].')' : ''?></span><input type="number" name="gio" min="0" <?=isset($hinhLimit['gio_max']) ? 'max="'.$hinhLimit['gio_max'].'"' : ''?> step="0.1" value="<?=htmlspecialchars((string)$s['hinh']['gio'])?>" required></label>
								<label class="dt-gv-field"><span>Quãng đường (km)<?=isset($hinhLimit['km_max']) ? ' (tối đa '.$hinhLimit['km_max'].')' : ''?></span><input type="number" name="km" min="0" <?=isset($hinhLimit['km_max']) ? 'max="'.$hinhLimit['km_max'].'"' : ''?> step="0.1" value="<?=htmlspecialchars((string)$s['hinh']['km'])?>" required></label>
								<button type="submit"><i class="fas fa-save" aria-hidden="true"></i> Lưu</button>
							</form>
						</details>
						<?php endif; ?>
					</td>
					<?php endif; ?>
					<?php if($showDat): ?>
					<td class="dt-gv-status-cell"><?=dt_gv_badge($s['dat']['dat'])?>
						<?php $dagg = isset($s['dat']['agg']) ? $s['dat']['agg'] : null; if($dagg): ?><div class="dt-gv-dat-agg">A <?=$dagg['a']?>h · B <?=$dagg['b']?>h · C <?=$dagg['c']?>h · D <?=$dagg['d']?>h · E <?=$dagg['e']?> km</div><?php endif; ?>
						<?php if(!empty($datMiss)): ?><div class="dt-gv-dat-missing"><?php foreach($datMiss as $m): ?><div class="dt-gv-dat-missing-item"><i class="fas fa-exclamation-circle" aria-hidden="true"></i><span><?=htmlspecialchars($m)?></span></div><?php endforeach; ?></div><?php endif; ?>
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
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>

<style>
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
	.w_1000 form[action="cong-giao-vien"] input[type=text],
	.w_1000 form[action="cong-giao-vien"] input[type=password] { font-size: 16px; }
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
</style>
