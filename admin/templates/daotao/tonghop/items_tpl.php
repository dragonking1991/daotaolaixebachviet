<?php if(!defined('SOURCES')) die("Error"); $qs = http_build_query(array_filter(array('com'=>'daotao','act'=>'exportTonghop','id_khoa'=>$filters['id_khoa'],'tu_ngay'=>$filters['tu_ngay'],'toi_ngay'=>$filters['toi_ngay'],'cccd'=>$filters['cccd'],'hoten'=>$filters['hoten'],'hang'=>$filters['hang'],'gv'=>$filters['gv']))); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Tổng hợp đào tạo</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Tổng hợp Đạt / Không đạt</strong></h3>
			<div class="card-tools"><a href="index.php?<?=$qs?>" class="btn btn-sm bg-gradient-info"><i class="fas fa-file-excel mr-1"></i>Xuất Excel</a></div>
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
