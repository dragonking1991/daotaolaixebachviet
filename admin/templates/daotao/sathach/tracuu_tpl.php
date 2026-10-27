<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item"><a href="index.php?com=daotao&act=sathach">Kết quả sát hạch</a></li>
			<li class="breadcrumb-item active">Tra cứu</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title"><strong>Tra cứu kết quả sát hạch</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3></div>
		<div class="card-body">
			<form method="get" class="form-inline mb-3">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="sathachTracuu">
				<input type="date" name="ngay_sh" class="form-control form-control-sm mr-2" value="<?=htmlspecialchars($tc_filters['ngay_sh'])?>" aria-label="Ngày sát hạch">
				<input type="text" name="hoten" class="form-control form-control-sm mr-2" placeholder="Tên học viên chính xác" value="<?=htmlspecialchars($tc_filters['hoten'])?>">
				<button class="btn btn-sm bg-gradient-primary">Tra cứu</button>
			</form>
			<div class="table-responsive">
			<?php $shB = function($diem, $kq) {
				$kq = trim((string)$kq); $diem = trim((string)$diem);
				$out = $diem !== '' ? htmlspecialchars($diem) : '';
				if($kq !== '') {
					$ok = dt_sathach_pass_label($kq);
					$out .= ($out !== '' ? ' ' : '').'<span class="badge badge-'.($ok ? 'success' : 'danger').'">'.htmlspecialchars($kq).'</span>';
				}
				return $out !== '' ? $out : '—';
			}; ?>
			<table class="table dt-list table-hover table-bordered mb-0" style="white-space:nowrap;">
				<thead><tr><th>Họ tên / Ngày sinh</th><th>CCCD</th><th>Hạng</th><th>Giáo viên</th><th>Ngày SH</th><th>LT</th><th>MP</th><th>TH / Hình</th><th>Đường</th><th>Kết luận</th><th>Ghi chú</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?><div class="dt-sub"><?=htmlspecialchars($it['nam_sinh'])?></div></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars($it['gv_roster'] ?? '')?></td>
						<td><?=htmlspecialchars(dt_sathach_display_date($it['ngay_sh']))?></td>
						<td><?=$shB(isset($it['diem_lt'])?$it['diem_lt']:'', isset($it['lt'])?$it['lt']:'')?></td>
						<td><?=$shB(isset($it['diem_mp'])?$it['diem_mp']:'', isset($it['mp'])?$it['mp']:'')?></td>
						<td><?=$shB(isset($it['diem_hinh'])?$it['diem_hinh']:'', isset($it['hinh'])?$it['hinh']:'')?></td>
						<td><?=$shB(isset($it['diem_duong'])?$it['diem_duong']:'', isset($it['duong'])?$it['duong']:'')?></td>
						<td><?php $kl = ($it['ket_luan']!=='')?$it['ket_luan']:$it['ket_qua']; if($kl!==''): $okl=dt_sathach_pass_label($kl); ?><span class="badge badge-<?=$okl?'success':'danger'?>"><?=htmlspecialchars($kl)?></span><?php else: ?>—<?php endif; ?></td>
						<td><?=htmlspecialchars($it['ghi_chu'])?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="11" class="text-center text-muted p-3">Nhập ngày sát hạch hoặc đúng họ tên để tra cứu.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</section>
