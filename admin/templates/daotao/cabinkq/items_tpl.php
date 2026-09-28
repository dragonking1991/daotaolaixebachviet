<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Cabin (kết quả)</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Kết quả cabin</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=cabinkq" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <a href="index.php?com=daotao&act=uploadCabin<?=$id_khoa_sel?'&id_khoa='.$id_khoa_sel:''?>" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import cabin</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=cabinkq" onclick="return confirm('Xóa toàn bộ kết quả cabin?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="cabinkq">
				<select name="id_khoa" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
					<option value="0">— Tất cả khóa —</option>
					<?php foreach($ds_khoa as $k): ?><option value="<?=$k['id']?>" <?=$id_khoa_sel==$k['id']?'selected':''?>><?=htmlspecialchars($k['ma_khoa'].' '.$k['ten_khoa'])?></option><?php endforeach; ?>
				</select>
			</form>
			<table class="table table-bordered table-sm table-hover mb-0">
				<thead><tr><th>Mã HV</th><th>Họ và tên</th><th>Tổng thời gian</th><th class="text-center">Số nội dung</th><th>Ghi chú</th><th class="text-center">Kết quả</th><th>Thao tác</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['ma_hv'])?></td>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['tong_thoigian'])?></td>
						<td class="text-center"><?=(int)$it['so_noidung']?></td>
						<td><?=htmlspecialchars($it['ghi_chu'])?></td>
						<td class="text-center"><?php if((int)$it['dat']===1): ?><span class="badge badge-success">Đạt</span><?php else: ?><span class="badge badge-danger">Không đạt</span><?php endif; ?></td>
						<td class="text-center"><a href="index.php?com=daotao&act=crudEdit&entity=cabinkq&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=cabinkq&id=<?=$it['id']?>" onclick="return confirm('Xóa kết quả cabin này?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="7" class="text-center text-muted p-3">Chưa có kết quả cabin</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
