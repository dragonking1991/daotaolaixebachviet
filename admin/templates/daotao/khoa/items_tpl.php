<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Khóa đào tạo</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách khóa đào tạo</strong></h3>
			<div class="card-tools">
				<a href="index.php?com=daotao&act=khoaAdd" class="btn btn-sm bg-gradient-success"><i class="fas fa-plus mr-1"></i>Thêm khóa</a>
				<a href="index.php?com=daotao&act=uploadKhoa" class="btn btn-sm bg-gradient-primary"><i class="fas fa-upload mr-1"></i>Import khóa</a>
				<a href="<?=dt_export_link('exportKhoa', array('keyword','hang','he','tu_ngay','toi_ngay'))?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a>
				<a href="index.php?com=daotao&act=crudDeleteAll&entity=khoa" onclick="return confirm('Xóa toàn bộ khóa và dữ liệu đào tạo liên quan?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a>
			</div>
		</div>
		<div class="card-body pb-0">
			<form method="get" class="form-inline mb-0">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="khoa">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2 mb-1" placeholder="Mã / Tên khóa" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<select name="hang" class="form-control form-control-sm mr-2 mb-1">
					<option value="">— Hạng —</option>
					<?php foreach(array('B11','B1','C1','C','CE') as $h): ?><option value="<?=$h?>" <?=(isset($_REQUEST['hang'])&&$_REQUEST['hang']==$h)?'selected':''?>><?=$h?></option><?php endforeach; ?>
				</select>
				<input type="text" name="he" class="form-control form-control-sm mr-2 mb-1" placeholder="Hệ đào tạo" value="<?=isset($_REQUEST['he'])?htmlspecialchars($_REQUEST['he']):''?>">
				<input type="date" name="tu_ngay" class="form-control form-control-sm mr-2 mb-1" title="Khai giảng từ" value="<?=isset($_REQUEST['tu_ngay'])?htmlspecialchars($_REQUEST['tu_ngay']):''?>">
				<input type="date" name="toi_ngay" class="form-control form-control-sm mr-2 mb-1" title="Khai giảng đến" value="<?=isset($_REQUEST['toi_ngay'])?htmlspecialchars($_REQUEST['toi_ngay']):''?>">
				<button class="btn btn-sm bg-gradient-primary mb-1 mr-2">Lọc</button>
				<a href="index.php?com=daotao&act=khoa" class="btn btn-sm btn-outline-secondary mb-1">Xóa lọc</a>
			</form>
		</div>
		<div class="card-body p-0">
			<table class="table dt-list table-hover mb-0">
				<thead>
					<tr>
						<th>Khóa đào tạo</th><th>Đào tạo</th><th>Thời gian</th><th class="text-center">Sĩ số</th><th class="dt-actions">Hành động</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td>
							<div class="dt-title"><?=htmlspecialchars($it['ma_khoa'])?></div>
							<div class="dt-sub"><?=htmlspecialchars($it['ten_khoa'] ?: '—')?></div>
						</td>
						<td>
							<div><?php if(!empty($it['hang'])): ?><span class="dt-tag"><?=htmlspecialchars($it['hang'])?></span><?php endif; ?></div>
							<?php if(!empty($it['he_daotao'])): ?><div class="dt-sub"><i class="fas fa-layer-group"></i><?=htmlspecialchars($it['he_daotao'])?></div><?php endif; ?>
						</td>
						<td class="dt-sub"><i class="far fa-calendar-alt"></i><?=$it['ngay_khaigiang'] ? date('d/m/Y', strtotime($it['ngay_khaigiang'])) : '—'?> <span class="dt-sep">→</span> <?=$it['ngay_manhoa'] ? date('d/m/Y', strtotime($it['ngay_manhoa'])) : '—'?></td>
						<td class="text-center"><a href="index.php?com=daotao&act=hocvien&id_khoa=<?=$it['id']?>" class="dt-tag"><?=(int)$it['so_hoc_vien']?> HV</a></td>
						<td class="dt-actions">
							<a href="index.php?com=daotao&act=khoaEdit&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a>
							<a href="index.php?com=daotao&act=khoaDelete&id=<?=$it['id']?>" onclick="return confirm('Xóa khóa và toàn bộ dữ liệu liên quan?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a>
						</td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="5" class="text-center text-muted p-3">Chưa có khóa nào</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
