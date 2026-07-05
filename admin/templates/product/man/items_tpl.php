<?php 
	$linkView = $config_base;
	$linkMan = $linkFilter = "index.php?com=product&act=man&type=".$type."&p=".$curPage;
	$linkAdd = "index.php?com=product&act=add&type=".$type."&p=".$curPage;
    $linkCopy = "index.php?com=product&act=copy&type=".$type."&p=".$curPage;
    $linkEdit = "index.php?com=product&act=edit&type=".$type."&p=".$curPage;
    $linkDelete = "index.php?com=product&act=delete&type=".$type."&p=".$curPage;
    $linkMulti = "index.php?com=product&act=man_photo&kind=man&type=".$type."&p=".$curPage;
    $copyImg = (isset($config['product'][$type]['copy_image']) && $config['product'][$type]['copy_image'] == true) ? TRUE : FALSE;
    $selectedBoPhan = (isset($_GET['bo_phan'])) ? trim((string)$_GET['bo_phan']) : '';
    $selectedChucVu = (isset($_GET['chuc_vu'])) ? trim((string)$_GET['chuc_vu']) : '';

    if(!function_exists('extractNhanVienGhiChu'))
    {
        function extractNhanVienGhiChu($row)
        {
            if(!isset($row['options2']) || trim((string)$row['options2']) === '') return '';
            $options2 = json_decode($row['options2'], true);
            if(!is_array($options2) || !isset($options2['detail']) || !is_array($options2['detail'])) return '';

            $detail = $options2['detail'];
            $aliases = array('ghichu', 'ghichudiengiai', 'diengiai', 'note', 'notes', 'ghichunhanvien');
            foreach($aliases as $key)
            {
                if(!isset($detail[$key])) continue;
                $value = trim((string)$detail[$key]);
                if($value !== '') return $value;
            }

            return '';
        }
    }
?>
<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active">Quản lý <?=$config['product'][$type]['title_main']?></li>
            </ol>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="card-footer text-sm sticky-top">
    	<a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm mới"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
        <a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?><?=$strUrl?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
        <a class="btn btn-sm bg-gradient-dark text-white" href="#" id="btn-delete-all-data" title="Xóa toàn bộ dữ liệu"><i class="fas fa-trash mr-2"></i>Xóa toàn bộ</a>
        <div class="form-inline form-search d-inline-block align-middle ml-3">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar text-sm" type="search" id="keyword" placeholder="Tìm kiếm" aria-label="Tìm kiếm" value="<?=(isset($_GET['keyword'])) ? $_GET['keyword'] : ''?>" onkeypress="<?php if($_GET['type']=='nhan-vien') { ?>if(event.keyCode==13||event.which==13) onSearchNhanVien();<?php } else { ?>doEnter(event,'keyword','<?=$linkMan?>');<?php } ?>">
                <div class="input-group-append bg-primary rounded-right">
                    <button class="btn btn-navbar text-white" type="button" onclick="<?php if($_GET['type']=='nhan-vien') { ?>onSearchNhanVien();<?php } else { ?>onSearch('keyword','<?=$linkMan?>');<?php } ?>">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php if($_GET['type']=='nhan-vien') { ?>
        <div class="form-inline d-inline-flex align-middle ml-2">
            <select id="filter-bo-phan" class="form-control form-control-sm mr-2" onchange="applyNhanVienFilter(true)">
                <option value="">Tất cả bộ phận</option>
                <?php if(!empty($employeeDepartments)) { foreach($employeeDepartments as $departmentItem) {
                    $departmentName = trim((string)$departmentItem['hang']);
                    if($departmentName === '') continue;
                ?>
                    <option value="<?=htmlspecialchars($departmentName, ENT_QUOTES, 'UTF-8')?>" <?=($selectedBoPhan === $departmentName) ? 'selected' : ''?>><?=htmlspecialchars($departmentName)?></option>
                <?php } } ?>
            </select>

            <select id="filter-chuc-vu" class="form-control form-control-sm mr-2" onchange="applyNhanVienFilter(true)">
                <option value="">Tất cả chức vụ</option>
                <?php if(!empty($employeePositions)) { foreach($employeePositions as $positionItem) {
                    $positionName = trim((string)$positionItem['khoa']);
                    if($positionName === '') continue;
                ?>
                    <option value="<?=htmlspecialchars($positionName, ENT_QUOTES, 'UTF-8')?>" <?=($selectedChucVu === $positionName) ? 'selected' : ''?>><?=htmlspecialchars($positionName)?></option>
                <?php } } ?>
            </select>

            <button type="button" class="btn btn-sm btn-info" onclick="applyNhanVienFilter(true)">Lọc</button>
        </div>
        <?php } ?>
    </div>
    <?php if(
        (isset($config['product'][$type]['dropdown']) && $config['product'][$type]['dropdown'] == true) || 
        (isset($config['product'][$type]['brand']) && $config['product'][$type]['brand'] == true)
    ) { ?>
	    <div class="card-footer form-group-category text-sm bg-light row">
			<?php if(isset($config['product'][$type]['list']) && $config['product'][$type]['list'] == true) { ?>
				<div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-4 mb-2"><?=$func->get_link_category('product', 'list', $type)?></div>
			<?php } ?>
			<?php if(isset($config['product'][$type]['cat']) && $config['product'][$type]['cat'] == true) { ?>
				<div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-4 mb-2"><?=$func->get_link_category('product', 'cat', $type)?></div>
			<?php } ?>
			<?php if(isset($config['product'][$type]['item']) && $config['product'][$type]['item'] == true) { ?>
				<div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-4 mb-2"><?=$func->get_link_category('product', 'item', $type)?></div>
			<?php } ?>
			<?php if(isset($config['product'][$type]['sub']) && $config['product'][$type]['sub'] == true) { ?>
				<div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-4 mb-2"><?=$func->get_link_category('product', 'sub', $type)?></div>
			<?php } ?>
			<?php if(isset($config['product'][$type]['brand']) && $config['product'][$type]['brand'] == true) { ?>
				<div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-4 mb-2"><?=$func->get_link_category('product', 'brand', $type, 'Chọn hãng')?></div>
			<?php } ?>
	    </div>
	<?php } ?>
    <div class="card card-primary card-outline text-sm mb-0">
        <div class="card-header">
            <h3 class="card-title">Danh sách <?=$config['product'][$type]['title_main']?></h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="align-middle" width="5%">
                            <div class="custom-control custom-checkbox my-checkbox">
                                <input type="checkbox" class="custom-control-input" id="selectall-checkbox">
                                <label for="selectall-checkbox" class="custom-control-label"></label>
                            </div>
                        </th>
                        <?php if($_GET['type']!='nhan-vien') { ?>
                            <th class="align-middle text-center" width="10%">STT</th>
                        <?php } ?>
						<?php if(isset($config['product'][$type]['show_images']) && $config['product'][$type]['show_images'] == true) { ?>
							<th class="align-middle">Hình</th>
						<?php } ?>
						<th class="align-middle" style="width:15%">Tên</th>
                        <th class="align-middle" style="width:15%">Ngày sinh</th>
                        <?php if($_GET['type']=='nhan-vien') { ?>
                            <th class="align-middle" style="width:15%">Mã tra cứu</th>
                        <?php } ?>
                        <th class="align-middle" style="width:15%">CCCD</th>
                        <th class="align-middle" style="width:15%"><?php if($_GET['type']=='gxn') echo 'Giấy xác nhận'; else if($_GET['type']=='qr') echo 'Hạng'; else if($_GET['type']=='nhan-vien') echo 'Bộ phận'; else echo 'GPLX' ?></th>
                        <?php if($_GET['type']=='nhan-vien') { ?>
                            <th class="align-middle" style="width:15%">Chức vụ</th>
                            <th class="align-middle" style="width:20%">Ghi chú</th>
                            <th class="align-middle" style="width:15%">Lương thực nhận</th>
                        <?php } ?>

                        <?php if($_GET['type']=='qr') { ?>
                            <th class="align-middle" style="width:15%">Số tiền</th>
                        <?php } ?>

						<?php if(isset($config['product'][$type]['gallery']) && count($config['product'][$type]['gallery']) > 0) { ?>
							<th class="align-middle">Gallery</th>
						<?php } ?>
						<?php if(isset($config['product'][$type]['check'])) { foreach($config['product'][$type]['check'] as $key => $value) { ?>
							<th class="align-middle text-center"><?=$value?></th>
						<?php } } ?>
						<th class="align-middle text-center">Hiển thị</th>
                        <th class="align-middle text-center">Thao tác</th>
                    </tr>
                </thead>
                <?php if(empty($items)) { ?>
                    <tbody><tr><td colspan="100" class="text-center">Không có dữ liệu</td></tr></tbody>
                <?php } else { ?>
                    <tbody>
                        <?php for($i=0;$i<count($items);$i++) {
                            $departmentLabel = $items[$i]['hang'];
                            $employeeGhiChu = '';
                            if($_GET['type']=='nhan-vien')
                            {
                                $positionLabel = trim((string)$items[$i]['khoa']);
                                if(trim((string)$departmentLabel) === '' && mb_strtolower($positionLabel, 'UTF-8') === 'nhân viên')
                                {
                                    $departmentLabel = 'Bộ phận văn phòng';
                                }
								$employeeGhiChu = extractNhanVienGhiChu($items[$i]);
                            }
                            $payrollDetail = array(
                                'Số ngày làm việc' => $items[$i]['payroll_so_ngay_lam_viec'],
                                'Lương chính' => $items[$i]['payroll_luong_chinh'],
                                'Thưởng lễ tết' => $items[$i]['payroll_thuong_le_tet'],
                                'Tiền cơm' => $items[$i]['payroll_tien_com'],
                                'Phụ cấp xăng xe' => $items[$i]['payroll_phu_cap_xang_xe'],
                                'Dạy LT Sát hạch' => $items[$i]['payroll_day_lt_sat_hach'],
                                'Chiêu sinh TTTN' => $items[$i]['payroll_chieu_sinh_tttn'],
                                'Khác (DT - Khác)' => $items[$i]['payroll_khac_dt_khac'],
                                'Làm thêm giờ' => $items[$i]['payroll_lam_them_gio'],
                                'Điện thoại' => $items[$i]['payroll_dien_thoai'],
                                'Tổng thu nhập' => $items[$i]['payroll_tong_thu_nhap'],
                                'NLD Nộp BHXH 10.5%' => $items[$i]['payroll_nld_nop_bhxh_10_5'],
                                'TT Nộp BHXH 21.5%' => $items[$i]['payroll_tt_nop_bhxh_21_5'],
                                'Thu nhập chịu thuế' => $items[$i]['payroll_thu_nhap_chiu_thue'],
                                'Giảm trừ gia cảnh' => $items[$i]['payroll_giam_tru_gia_canh'],
                                'Số NPT' => $items[$i]['payroll_so_npt'],
                                'Người phụ thuộc' => $items[$i]['payroll_nguoi_phu_thuoc'],
                                'Thu nhập tính thuế' => $items[$i]['payroll_thu_nhap_tinh_thue'],
                                'Bậc' => $items[$i]['payroll_bac'],
                                'Thuế TNCN' => $items[$i]['payroll_thue_tncn'],
                                'Lương thực nhận' => $items[$i]['payroll_luong_thuc_nhan'],
                                'Nghĩa vụ GV' => $items[$i]['payroll_nghia_vu_gv'],
                                'TĐ' => $items[$i]['payroll_td'],
                                'SS' => $items[$i]['payroll_ss'],
                                'C1' => $items[$i]['payroll_c1'],
                                'CE' => $items[$i]['payroll_ce']
                            );
							if($_GET['type']=='nhan-vien' && $employeeGhiChu !== '') $payrollDetail['Ghi chú'] = $employeeGhiChu;
                            $payrollDetailJson = htmlspecialchars(json_encode($payrollDetail, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                        	$linkID = "";
							if($items[$i]['id_list']) $linkID .= "&id_list=".$items[$i]['id_list'];
							if($items[$i]['id_cat']) $linkID .= "&id_cat=".$items[$i]['id_cat'];
							if($items[$i]['id_item']) $linkID .= "&id_item=".$items[$i]['id_item'];
							if($items[$i]['id_sub']) $linkID .= "&id_sub=".$items[$i]['id_sub'];
							if($items[$i]['id_brand']) $linkID .= "&id_brand=".$items[$i]['id_brand']; ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="custom-control custom-checkbox my-checkbox">
                                        <input type="checkbox" class="custom-control-input select-checkbox" id="select-checkbox-<?=$items[$i]['id']?>" value="<?=$items[$i]['id']?>">
                                        <label for="select-checkbox-<?=$items[$i]['id']?>" class="custom-control-label"></label>
                                    </div>
                                </td>
                                <?php if($_GET['type']!='nhan-vien') { ?>
                                    <td class="align-middle">
                                        <input type="number" class="form-control form-control-mini m-auto update-stt" min="0" value="<?=$items[$i]['stt']?>" data-id="<?=$items[$i]['id']?>" data-table="product">
                                    </td>
                                <?php } ?>
                                <?php if(isset($config['product'][$type]['show_images']) && $config['product'][$type]['show_images'] == true) { ?>
                                    <td class="align-middle">
                                    	<a href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>"><img class="rounded img-preview" onerror="src='assets/images/noimage.png'" src="<?=THUMBS?>/<?=$config['product'][$type]['thumb']?>/<?=UPLOAD_PRODUCT_L.$items[$i]['photo']?>" alt="<?=$items[$i]['tenvi']?>"></a>
                                    </td>
                                <?php } ?>
                                <td class="align-middle">
                                    <a class="text-dark" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>"><?=$items[$i]['tenvi']?></a>
                                </td>

                                <td class="align-middle">
                                    <a class="text-dark" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>"><?=$items[$i]['ngaysinh']?></a>
                                </td>

                                <?php if($_GET['type']=='nhan-vien') { ?>
                                    <td class="align-middle">
                                        <a class="text-dark" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>"><?=$items[$i]['ma_tra_cuu']?></a>
                                    </td>
                                <?php } ?>

                                <td class="align-middle">
                                    <a class="text-dark" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>"><?=$items[$i]['cccd']?></a>
                                </td>

                                <td class="align-middle">
                                    <a class="text-dark" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>">
                                        <?php if($_GET['type']=='gxn')echo $items[$i]['gxn']; else if($_GET['type']=='gplx')echo $items[$i]['gplx']; else echo $departmentLabel;?>
                                    </a>
                                </td>

                                <?php if($_GET['type']=='nhan-vien') { ?>
                                    <td class="align-middle">
                                        <a class="text-dark" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>"><?=$items[$i]['khoa']?></a>
                                    </td>
                                    <td class="align-middle" style="white-space:normal; max-width: 380px;">
										<?php
											if($employeeGhiChu !== '')
											{
												echo nl2br(htmlspecialchars($employeeGhiChu));
											}
											else echo '-';
										?>
									</td>
                                    <td class="align-middle">
                                        <a class="text-dark" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['tenvi']?>"><?=$items[$i]['payroll_luong_thuc_nhan']?></a>
                                    </td>
                                <?php } ?>

                                <?php if($_GET['type']=='qr') { ?>
                                    <td class="align-middle"><?php if($items[$i]['gia']>0) echo $func->format_money($items[$i]['gia'])?></td>
                                <?php } ?>

                                <?php if(isset($config['product'][$type]['gallery']) && count($config['product'][$type]['gallery']) > 0) { ?>
		                            <td class="align-middle">
		                            	<div class="dropdown">
		                            		<button type="button" class="btn btn-sm bg-gradient-success dropdown-toggle" id="dropdown-gallery" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Thêm</button>
		                            		<div class="dropdown-menu" aria-labelledby="dropdown-gallery">
		                            			<?php foreach($config['product'][$type]['gallery'] as $key => $value) { ?>
					                                <a class="dropdown-item text-dark" href="<?=$linkMulti?>&idc=<?=$items[$i]['id']?>&val=<?=$key?>" title="<?=$value['title_sub_photo']?>"><i class="far fa-caret-square-right text-secondary mr-2"></i><?=$value['title_sub_photo']?></a>
					                            <?php } ?>
		                            		</div>
		                            	</div>
		                            </td>
		                        <?php } ?>
                                <?php if(isset($config['product'][$type]['check'])) { foreach($config['product'][$type]['check'] as $key => $value) { ?>
								  	<td class="align-middle text-center">
	                                	<div class="custom-control custom-checkbox my-checkbox">
	                                        <input type="checkbox" class="custom-control-input show-checkbox" id="show-checkbox-<?=$key?>-<?=$items[$i]['id']?>" data-table="product" data-id="<?=$items[$i]['id']?>" data-loai="<?=$key?>" <?=($items[$i][$key])?'checked':''?>>
	                                        <label for="show-checkbox-<?=$key?>-<?=$items[$i]['id']?>" class="custom-control-label"></label>
	                                    </div>
	                                </td>
								<?php } } ?>
								<td class="align-middle text-center">
                                	<div class="custom-control custom-checkbox my-checkbox">
                                        <input type="checkbox" class="custom-control-input show-checkbox" id="show-checkbox-<?=$items[$i]['id']?>" data-table="product" data-id="<?=$items[$i]['id']?>" data-loai="hienthi" <?=($items[$i]['hienthi'])?'checked':''?>>
                                        <label for="show-checkbox-<?=$items[$i]['id']?>" class="custom-control-label"></label>
                                    </div>
                                </td>
                                <td class="align-middle text-center text-md text-nowrap">
                                	<?php if(isset($config['product'][$type]['copy']) && $config['product'][$type]['copy'] == true) { ?>
                                    	<div class="dropdown d-inline-block align-middle">
		                            		<a id="dropdownCopy" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle text-success p-0 pr-2"><i class="far fa-clone"></i></a>
								            <ul aria-labelledby="dropdownCopy" class="dropdown-menu border-0 shadow">
								                <li><a href="#" class="dropdown-item copy-now" data-id="<?=$items[$i]['id']?>" data-table="product"><i class="far fa-caret-square-right text-secondary mr-2"></i>Sao chép ngay</a></li>
								                <li><a href="<?=$linkCopy?><?=$linkID?>&id=<?=$items[$i]['id']?>" class="dropdown-item"><i class="far fa-caret-square-right text-secondary mr-2"></i>Chỉnh sửa thông tin</a></li>
								            </ul>
		                            	</div>
                                    <?php } ?>
                                    <?php if($_GET['type']=='nhan-vien') { ?>
                                    	<a class="text-info mr-2 btn-payroll-detail" href="#" data-toggle="modal" data-target="#modal-payroll-detail" data-name="<?=htmlspecialchars($items[$i]['tenvi'], ENT_QUOTES, 'UTF-8')?>" data-reference="<?=htmlspecialchars($items[$i]['ma_tra_cuu'], ENT_QUOTES, 'UTF-8')?>" data-payroll="<?=$payrollDetailJson?>" title="chi tiết"><i class="fas fa-receipt"></i></a>
                                    <?php } ?>
                                    <a class="text-primary mr-2" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                    <a class="text-danger" id="delete-item" data-url="<?=$linkDelete?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
    <?php if($paging) { ?>
        <div class="card-footer text-sm pb-0"><?=$paging?></div>
    <?php } ?>
    <div class="card-footer text-sm">
    	<a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm mới"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
        <a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?><?=$strUrl?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
        <a class="btn btn-sm bg-gradient-dark text-white" href="#" id="btn-delete-all-data" title="Xóa toàn bộ dữ liệu"><i class="fas fa-trash mr-2"></i>Xóa toàn bộ</a>
    </div>
</section>

<?php if($_GET['type']=='nhan-vien') { ?>
<div class="modal fade" id="modal-payroll-detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">chi tiết</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Nhân viên:</strong> <span id="payroll-detail-name"></span></p>
                <p class="mb-3"><strong>Mã tra cứu:</strong> <span id="payroll-detail-reference"></span></p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th style="width:40%">Thông tin</th>
                                <th>Giá trị</th>
                            </tr>
                        </thead>
                        <tbody id="payroll-detail-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<script type="text/javascript">
$(document).ready(function(){
    window.applyNhanVienFilter = function(resetPage){
        var url = '<?=$linkMan?>';
        var keyword = ($('#keyword').val() || '').trim();
        var boPhan = ($('#filter-bo-phan').val() || '').trim();
        var chucVu = ($('#filter-chuc-vu').val() || '').trim();

        if(keyword !== '') url += '&keyword=' + encodeURIComponent(keyword);
        if(boPhan !== '') url += '&bo_phan=' + encodeURIComponent(boPhan);
        if(chucVu !== '') url += '&chuc_vu=' + encodeURIComponent(chucVu);
        if(resetPage) url += '&p=1';

        window.location.href = url;
    };

    window.onSearchNhanVien = function(){
        window.applyNhanVienFilter(true);
    };

	$('#btn-delete-all-data').click(function(e){
		e.preventDefault();
		if(confirm('Bạn có chắc chắn muốn XÓA TOÀN BỘ dữ liệu của phần này? Hành động này không thể hoàn tác!')){
			window.location.href = 'index.php?com=product&act=deleteAllData&type=<?=$type?><?=$strUrl?>';
		}
	});

    $('body').on('click', '.btn-payroll-detail', function(e){
        e.preventDefault();

        var name = $(this).data('name') || '';
        var reference = $(this).data('reference') || '';
        var payrollRaw = $(this).attr('data-payroll') || '{}';
        var payrollData = {};

        try {
            payrollData = JSON.parse(payrollRaw);
        } catch(err) {
            payrollData = {};
        }

        $('#payroll-detail-name').text(name);
        $('#payroll-detail-reference').text(reference);

        var rows = '';
        Object.keys(payrollData).forEach(function(label){
            var value = (payrollData[label] || '').toString().trim();
            if(value === '') value = '-';
            rows += '<tr><td><strong>' + $('<div>').text(label).html() + '</strong></td><td>' + $('<div>').text(value).html() + '</td></tr>';
        });

        if(rows === '') rows = '<tr><td colspan="2" class="text-center">Không có dữ liệu payroll</td></tr>';
        $('#payroll-detail-body').html(rows);
    });
});
</script>