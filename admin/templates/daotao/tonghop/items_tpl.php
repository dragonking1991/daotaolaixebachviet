<?php
	if(!defined('SOURCES')) die("Error");
	$qs = http_build_query(array_filter(array('com'=>'daotao','act'=>'exportTonghop','id_khoa'=>$filters['id_khoa'],'tu_ngay'=>$filters['tu_ngay'],'toi_ngay'=>$filters['toi_ngay'],'cccd'=>$filters['cccd'],'hoten'=>$filters['hoten'],'hang'=>$filters['hang'],'gv'=>$filters['gv'])));
	$tong_hv = is_array($items) ? count($items) : 0;
	$so_du = 0; $so_dat_lt = 0;
	if(!empty($items)) foreach($items as $__it){
		if(!empty($__it['sum']['du_dieu_kien'])) $so_du++;
		if(!empty($__it['sum']['ly_thuyet']['dat'])) $so_dat_lt++;
	}
	$so_chua = $tong_hv - $so_du;
	$ty_le = $tong_hv > 0 ? round($so_du / $tong_hv * 100) : 0;
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Tổng hợp đào tạo</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="dt-banner">
		<div class="dt-banner-icon"><i class="fas fa-clipboard-check"></i></div>
		<div>
			<div class="dt-banner-kicker">Vận hành đào tạo</div>
			<h2>Tổng hợp Đạt / Không đạt</h2>
			<p>Theo dõi kết quả lý thuyết, cabin, hình, DAT và điều kiện dự thi theo từng học viên.</p>
		</div>
		<div class="dt-banner-stats">
			<div class="dt-banner-stat"><div class="num"><?=$so_du?></div><div class="lbl">Đủ điều kiện</div></div>
			<div class="dt-banner-stat"><div class="num"><?=$ty_le?>%</div><div class="lbl">Tỷ lệ đạt</div></div>
		</div>
	</div>
	<div class="dt-stats">
		<div class="dt-stat is-blue"><div class="dt-stat-label">Tổng học viên</div><div class="dt-stat-num"><?=$tong_hv?></div><div class="dt-stat-sub">Trong danh sách lọc</div></div>
		<div class="dt-stat is-green"><div class="dt-stat-label">Đủ điều kiện dự thi</div><div class="dt-stat-num"><?=$so_du?></div><div class="dt-stat-sub">Đã đạt tất cả nội dung</div></div>
		<div class="dt-stat is-amber"><div class="dt-stat-label">Đạt lý thuyết</div><div class="dt-stat-num"><?=$so_dat_lt?></div><div class="dt-stat-sub">Hoàn thành phần lý thuyết</div></div>
		<div class="dt-stat is-red"><div class="dt-stat-label">Chưa đủ điều kiện</div><div class="dt-stat-num"><?=$so_chua?></div><div class="dt-stat-sub">Còn thiếu nội dung</div></div>
	</div>
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Tổng hợp Đạt / Không đạt</strong></h3>
			<div class="card-tools"><a href="index.php?<?=$qs?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=all" onclick="return confirm('Xóa toàn bộ dữ liệu trong Quản lý đào tạo? Thao tác không thể hoàn tác.')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ dữ liệu</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="mb-3">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="tonghop">
				<div class="row">
					<div class="col-md-3 form-group mb-1"><select name="id_khoa" class="form-control form-control-sm"><option value="0">— Tất cả khóa —</option><?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$filters['id_khoa']==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?></select></div>
					<div class="col-md-2 form-group mb-1"><input type="date" name="tu_ngay" class="form-control form-control-sm" value="<?=htmlspecialchars($filters['tu_ngay'])?>" title="Từ ngày (DAT)"></div>
					<div class="col-md-2 form-group mb-1"><input type="date" name="toi_ngay" class="form-control form-control-sm" value="<?=htmlspecialchars($filters['toi_ngay'])?>" title="Tới ngày (DAT)"></div>
					<div class="col-md-2 form-group mb-1"><input type="text" name="cccd" class="form-control form-control-sm" placeholder="CCCD" value="<?=htmlspecialchars($filters['cccd'])?>"></div>
					<div class="col-md-3 form-group mb-1"><input type="text" name="hoten" class="form-control form-control-sm" placeholder="Họ tên" value="<?=htmlspecialchars($filters['hoten'])?>"></div>
				</div>
				<div class="row">
					<div class="col-md-2 form-group mb-1"><select name="hang" class="form-control form-control-sm"><option value="">— Hạng —</option><?php foreach(array('B11','B1','C1','C','CE') as $h): ?><option value="<?=$h?>" <?=$filters['hang']==$h?'selected':''?>><?=$h?></option><?php endforeach; ?></select></div>
					<div class="col-md-3 form-group mb-1"><input type="text" name="gv" class="form-control form-control-sm" placeholder="Giáo viên" value="<?=htmlspecialchars($filters['gv'])?>"></div>
					<div class="col-md-2 form-group mb-1"><button class="btn btn-sm bg-gradient-primary btn-block">Lọc</button></div>
				</div>
			</form>
			<div class="table-responsive">
			<table class="table table-bordered table-sm table-hover mb-0">
				<thead><tr><th>Mã HV</th><th>Họ và tên</th><th>CCCD</th><th>Hạng</th><th class="text-center">Lý thuyết</th><th class="text-center">Cabin</th><th class="text-center">Hình</th><th class="text-center">DAT</th><th class="text-center">Kết luận</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): $hv=$it['hv']; $s=$it['sum']; ?>
					<tr>
						<td><?=htmlspecialchars($hv['ma_hv'])?></td>
						<td><?=htmlspecialchars($hv['hoten'])?></td>
						<td><?=htmlspecialchars($hv['cccd'])?></td>
						<td><?=htmlspecialchars($hv['hang'])?></td>
						<td class="text-center"><span class="badge badge-<?=$s['ly_thuyet']['dat']?'success':'secondary'?>"><?=$s['ly_thuyet']['dat']?'Đạt':($s['ly_thuyet']['so_dat'].'/'.$s['ly_thuyet']['tong'])?></span></td>
						<td class="text-center"><span class="badge badge-<?=$s['cabin']['dat']?'success':'secondary'?>"><?=$s['cabin']['dat']?'Đạt':'Chưa'?></span></td>
						<td class="text-center"><span class="badge badge-<?=$s['hinh']['dat']?'success':'secondary'?>"><?=$s['hinh']['dat']?'Đạt':'Chưa'?></span></td>
						<td class="text-center"><span class="badge badge-<?=$s['dat']['dat']?'success':'secondary'?>" title="<?=htmlspecialchars(implode(', ',$s['dat']['thieu']))?>"><?=$s['dat']['dat']?'Đạt':'Chưa'?></span></td>
						<td class="text-center"><?php if($s['du_dieu_kien']): ?><span class="badge badge-success">Đủ ĐK</span><?php else: ?><span class="badge badge-danger">Chưa đủ</span><?php endif; ?></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="9" class="text-center text-muted p-3">Chọn khóa hoặc nhập tiêu chí để xem tổng hợp</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</section>
