<?php if(!defined('SOURCES')) die("Error"); $shExtra=true; foreach($sh_periods as $period) if((int)$period['id']===$sh_period_sel) $shExtra=$period['loai']!=='moto'; ?>
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
			<div class="card-tools"><?php if(dt_can('sathach','view')): ?><a href="index.php?com=daotao&act=sathachTracuu" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-search mr-1"></i>Tra cứu</a> <?php endif; ?><?php if(dt_can('sathach','import')): ?><a href="index.php?com=daotao&act=uploadSathach&id_ky=<?=$sh_period_sel?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import</a> <?php endif; ?><?php if(dt_can('sathach','export')): ?><a href="<?=dt_export_link('exportSathach', array('id_khoa','keyword','id_ky','ngay_sh'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a><?php endif; ?></div>
		</div>
		<div class="card-body">
			<?php if(dt_can('sathach','import')): ?><a href="index.php?com=daotao&act=uploadSathach&import_mode=summary" class="btn btn-sm bg-gradient-info mb-3"><i class="fas fa-file-import mr-1"></i>Import kết quả tổng hợp</a><?php endif; ?>
			<?php if(dt_can('sathach','edit')): ?>
			<form method="post" action="index.php?com=daotao&act=createSathachPeriod" class="form-inline mb-3">
				<input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['dt_sh_csrf'])?>">
				<input required name="ten" maxlength="255" placeholder="Tên kỳ sát hạch" class="form-control form-control-sm mr-2">
				<input required type="date" name="ngay" class="form-control form-control-sm mr-2">
				<select name="loai" class="form-control form-control-sm mr-2"><option value="oto">Ô tô</option><option value="moto">Mô tô</option></select>
				<button class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Tạo kỳ sát hạch</button>
			</form>
			<?php endif; ?>
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="sathach">
				<select name="id_ky" class="form-control form-control-sm mr-2"><option value="0">Tất cả kỳ</option><?php foreach($sh_periods as $period): ?><option value="<?=(int)$period['id']?>" <?=(int)$period['id']===$sh_period_sel?'selected':''?>><?=htmlspecialchars($period['ngay'].' / '.$period['ten'].' / '.($period['loai']==='moto'?'Mô tô':'Ô tô'))?></option><?php endforeach; ?></select>
				<input type="date" name="ngay_sh" class="form-control form-control-sm mr-2" value="<?=htmlspecialchars(dt_req('ngay_sh'))?>" aria-label="Ngày sát hạch">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Chọn khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD / Mã HV / Số GPLX" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Lọc</button>
			</form>
			<?php if(!$id_khoa_sel && empty($items)): ?>
			<div class="alert alert-info mb-0">Chọn khóa hoặc nhập CCCD/tên để xem kết quả sát hạch.</div>
			<?php else: ?>
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
				<thead><tr><th>Họ tên / Ngày sinh</th><th>CCCD</th><th>Hạng</th><th>Nội dung SH</th><th>Ngày SH</th><th>LT</th><?php if($shExtra): ?><th>MP</th><?php endif; ?><th>TH / Hình</th><?php if($shExtra): ?><th>Đường</th><?php endif; ?><th>Kết luận</th><th>Số GPLX</th><th>Ghi chú</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?><div class="dt-sub"><?=htmlspecialchars($it['nam_sinh'])?></div></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang'])?></td>
						<td><?=htmlspecialchars($it['noi_dung_sh'] ?? '')?></td>
						<td><?=htmlspecialchars($it['ngay_sh'])?></td>
						<td><?=$shB(isset($it['diem_lt'])?$it['diem_lt']:'', isset($it['lt'])?$it['lt']:'')?></td>
						<?php if($shExtra): ?><td><?=$shB(isset($it['diem_mp'])?$it['diem_mp']:'', isset($it['mp'])?$it['mp']:'')?></td><?php endif; ?>
						<td><?=$shB(isset($it['diem_hinh'])?$it['diem_hinh']:'', isset($it['hinh'])?$it['hinh']:'')?></td>
						<?php if($shExtra): ?><td><?=$shB(isset($it['diem_duong'])?$it['diem_duong']:'', isset($it['duong'])?$it['duong']:'')?></td><?php endif; ?>
						<td><?php $kl = ($it['ket_luan']!=='')?$it['ket_luan']:$it['ket_qua']; if($kl!==''): $okl=dt_sathach_pass_label($kl); ?><span class="badge badge-<?=$okl?'success':'danger'?>"><?=htmlspecialchars($kl)?></span><?php else: ?>—<?php endif; ?></td>
						<td><?=htmlspecialchars($it['so_gplx'])?></td>
						<td><?=htmlspecialchars($it['ghi_chu'])?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="12" class="text-center text-muted p-3">Chưa có kết quả sát hạch</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
