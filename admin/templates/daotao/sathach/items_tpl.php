<?php if(!defined('SOURCES')) die("Error"); $shExtra=true; foreach($sh_periods as $period) if((int)$period['id']===$sh_period_sel) $shExtra=$period['loai']!=='moto'; if(!$sh_period_sel && !empty($items)) { $shMoto=true; foreach($items as $shItem) if(strpos(strtoupper(trim($shItem['hang'])),'A')!==0) { $shMoto=false; break; } if($shMoto) $shExtra=false; } ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Kết quả sát hạch</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Kết quả sát hạch</strong> <span class="dt-count"><?=number_format((int)$total_items)?></span></h3>
			<div class="card-tools"><?php if(dt_can('sathach','view')): ?><a href="index.php?com=daotao&act=sathachTracuu" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-search mr-1"></i>Tra cứu</a> <?php endif; ?><?php if(dt_can('sathach','import')): ?><a href="index.php?com=daotao&act=uploadSathach" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import file mẫu</a> <?php endif; ?><?php if(dt_can('sathach','export')): ?><a href="<?=dt_export_link('exportSathach', array('id_khoa','keyword','id_ky','ngay_sh','id_hocvien'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a><?php endif; ?></div>
		</div>
		<div class="card-body">
			<a href="index.php?com=daotao&act=sathachPending" class="btn btn-sm btn-outline-warning mb-3"><i class="fas fa-list mr-1"></i>Dòng chờ bổ sung</a>
			<?php if(dt_can('sathach','edit')): ?>
			<form method="post" action="index.php?com=daotao&act=createSathachPeriod" class="form-inline mb-3">
				<input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>">
				<input required type="date" name="ngay" class="form-control form-control-sm mr-2">
				<select name="loai" class="form-control form-control-sm mr-2"><option value="oto">Ô tô</option><option value="moto">Mô tô</option></select>
				<button class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Tạo ngày sát hạch</button>
			</form>
			<?php endif; ?>
			<?php if(!empty($sh_periods)): ?>
			<?php if(dt_can('sathach','delete')): ?><form id="dt-bulk-sh-periods" method="post" action="index.php?com=daotao&act=deleteSathachPeriodsSelected" class="mb-2"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i>Xóa kỳ đã chọn</button></form><?php endif; ?>
			<div class="table-responsive mb-3"><table class="table table-sm table-bordered"><thead><tr><?php if(dt_can('sathach','delete')): ?><th class="text-center" style="width:34px"><input type="checkbox" class="dt-bulk-all" data-form="dt-bulk-sh-periods" title="Chọn tất cả kỳ"></th><?php endif; ?><th>Ngày sát hạch</th><th>Loại xe</th><th>Số học viên</th><th>Thao tác</th></tr></thead><tbody><?php foreach($sh_periods as $period): ?><tr><?php if(dt_can('sathach','delete')): ?><td class="text-center"><input type="checkbox" class="dt-bulk-item" form="dt-bulk-sh-periods" name="id_kys[]" value="<?=(int)$period['id']?>"></td><?php endif; ?><td><?=htmlspecialchars(dt_sathach_display_date($period['ngay']))?></td><td><?=$period['loai']==='moto'?'Mô tô':'Ô tô'?></td><td><?=(int)$period['so_hoc_vien']?></td><td><a class="btn btn-xs btn-outline-primary" href="index.php?com=daotao&act=sathach&id_ky=<?=(int)$period['id']?>#sh-results">Xem kết quả</a> <?php if(dt_can('sathach','delete')): ?><form method="post" action="index.php?com=daotao&act=deleteSathachPeriod" class="d-inline" onsubmit="return confirm('Xóa ngày sát hạch này và toàn bộ kết quả đã import trong ngày?')"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>"><input type="hidden" name="id_ky" value="<?=(int)$period['id']?>"><button class="btn btn-xs btn-outline-danger" title="Xóa ngày sát hạch"><i class="fas fa-trash"></i></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
			<form id="sh-results" method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="sathach">
				<select name="id_ky" class="form-control form-control-sm mr-2"><option value="0">Tất cả kỳ</option><?php foreach($sh_periods as $period): ?><option value="<?=(int)$period['id']?>" <?=(int)$period['id']===$sh_period_sel?'selected':''?>><?=htmlspecialchars(dt_sathach_display_date($period['ngay']).' / '.($period['loai']==='moto'?'Mô tô':'Ô tô'))?></option><?php endforeach; ?></select>
				<input type="date" name="ngay_sh" class="form-control form-control-sm mr-2" value="<?=htmlspecialchars(dt_req('ngay_sh'))?>" aria-label="Ngày sát hạch">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên chính xác" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<?php if(!$sh_period_sel && dt_req('ngay_sh')==='' && trim(dt_req('keyword'))==='' && empty($items)): ?>
			<div class="alert alert-info mb-0">Tạo/chọn ngày sát hạch hoặc nhập chính xác họ tên để xem kết quả.</div>
			<?php else: ?>
			<?php if(dt_can('sathach','edit')): ?><form id="dt-sh-note-form" method="post" action="index.php?com=daotao&act=saveSathachNotes" class="form-inline mb-2"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>"><input type="hidden" name="id_ky" value="<?=$sh_period_sel?>"><input type="text" name="ghi_chu" maxlength="255" required class="form-control form-control-sm mr-2" placeholder="Ghi chú cho học viên đã chọn"><button class="btn btn-sm bg-gradient-warning"><i class="fas fa-sticky-note mr-1"></i>Lưu ghi chú mục đã chọn</button></form><?php endif; ?>
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
				<thead><tr><?php if(dt_can('sathach','edit')): ?><th><input type="checkbox" class="dt-bulk-all" data-form="dt-sh-note-form" title="Chọn tất cả"></th><?php endif; ?><th>Họ tên / Ngày sinh</th><th>CCCD</th><th>Hạng</th><th>Giáo viên</th><th>Ngày SH</th><th>LT</th><?php if($shExtra): ?><th>MP</th><?php endif; ?><th>TH / Hình</th><?php if($shExtra): ?><th>Đường</th><?php endif; ?><th>Kết luận</th><th>Ghi chú</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<?php if(dt_can('sathach','edit')): ?><td><input type="checkbox" form="dt-sh-note-form" class="dt-bulk-item" name="ids[]" value="<?=(int)$it['id']?>"></td><?php endif; ?>
						<td><?=htmlspecialchars($it['hoten'])?><div class="dt-sub"><?=htmlspecialchars($it['nam_sinh'])?></div></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars($it['gv_roster'] ?? '')?></td>
						<td><?=htmlspecialchars(dt_sathach_display_date($it['ngay_sh']))?></td>
						<td><?=$shB(isset($it['diem_lt'])?$it['diem_lt']:'', isset($it['lt'])?$it['lt']:'')?></td>
						<?php if($shExtra): ?><td><?=$shB(isset($it['diem_mp'])?$it['diem_mp']:'', isset($it['mp'])?$it['mp']:'')?></td><?php endif; ?>
						<td><?=$shB(isset($it['diem_hinh'])?$it['diem_hinh']:'', isset($it['hinh'])?$it['hinh']:'')?></td>
						<?php if($shExtra): ?><td><?=$shB(isset($it['diem_duong'])?$it['diem_duong']:'', isset($it['duong'])?$it['duong']:'')?></td><?php endif; ?>
						<td><?php $kl = ($it['ket_luan']!=='')?$it['ket_luan']:$it['ket_qua']; if($kl!==''): $okl=dt_sathach_pass_label($kl); ?><span class="badge badge-<?=$okl?'success':'danger'?>"><?=htmlspecialchars($kl)?></span><?php else: ?>—<?php endif; ?></td>
						<td><?=htmlspecialchars($it['ghi_chu'])?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="<?=dt_can('sathach','edit')?($shExtra?12:10):($shExtra?11:9)?>" class="text-center text-muted p-3">Chưa có kết quả sát hạch</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
