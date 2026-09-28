<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<ol class="breadcrumb float-sm-left">
			<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
			<li class="breadcrumb-item active">Giáo viên</li>
		</ol>
	</div>
</section>
<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header">
			<h3 class="card-title"><strong>Danh sách giáo viên</strong></h3>
			<div class="card-tools"><a href="index.php?com=daotao&act=crudEdit&entity=giaovien" class="btn btn-sm bg-gradient-primary"><i class="fas fa-plus mr-1"></i>Thêm</a> <a href="index.php?com=daotao&act=uploadGiaovien" class="btn btn-sm bg-gradient-success"><i class="fas fa-upload mr-1"></i>Import giáo viên</a> <a href="index.php?com=daotao&act=crudDeleteAll&entity=giaovien" onclick="return confirm('Xóa toàn bộ giáo viên?')" class="btn btn-sm bg-gradient-danger"><i class="fas fa-trash mr-1"></i>Xóa toàn bộ</a></div>
		</div>
		<div class="card-body">
			<form method="get" class="form-inline mb-2">
				<input type="hidden" name="com" value="daotao"><input type="hidden" name="act" value="giaovien">
				<input type="text" name="keyword" class="form-control form-control-sm mr-2" placeholder="Tên / CCCD" value="<?=isset($_REQUEST['keyword'])?htmlspecialchars($_REQUEST['keyword']):''?>">
				<button class="btn btn-sm bg-gradient-primary">Tìm</button>
			</form>
			<table class="table table-bordered table-sm table-hover mb-0">
				<thead><tr><th>Họ và tên</th><th>CCCD</th><th>Hạng GPLX</th><th>Hạng ĐT được phép</th><th>Điện thoại</th><th class="text-center">Thao tác</th></tr></thead>
				<tbody>
					<?php if(!empty($items)): foreach($items as $it): ?>
					<tr>
						<td><?=htmlspecialchars($it['hoten'])?></td>
						<td><?=htmlspecialchars($it['cccd'])?></td>
						<td><?=htmlspecialchars($it['hang_gplx'])?></td>
						<td><?=htmlspecialchars($it['hang_daotao_phep'])?></td>
						<td><?=htmlspecialchars($it['sdt'])?></td>
						<td class="text-center"><a href="index.php?com=daotao&act=crudEdit&entity=giaovien&id=<?=$it['id']?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-edit"></i></a> <a href="index.php?com=daotao&act=gvResetPass&id=<?=$it['id']?>" onclick="return confirm('Đặt lại mật khẩu cổng giáo viên về mặc định (= CCCD)?')" class="btn btn-xs bg-gradient-warning" title="Đặt lại mật khẩu"><i class="fas fa-key"></i></a> <a href="index.php?com=daotao&act=crudDelete&entity=giaovien&id=<?=$it['id']?>" onclick="return confirm('Xóa giáo viên này?')" class="btn btn-xs bg-gradient-danger"><i class="fas fa-trash"></i></a></td>
					</tr>
					<?php endforeach; else: ?>
					<tr><td colspan="6" class="text-center text-muted p-3">Chưa có giáo viên</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer"><?php if(isset($paging)) echo $paging; ?></div>
	</div>
</section>
