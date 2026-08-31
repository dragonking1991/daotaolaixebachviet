<?php
	/* Request data */
	$com = (isset($_REQUEST['com'])) ? htmlspecialchars($_REQUEST['com']) : '';
	$act = (isset($_REQUEST['act'])) ? htmlspecialchars($_REQUEST['act']) : '';
	$type = (isset($_REQUEST['type'])) ? htmlspecialchars($_REQUEST['type']) : '';
	$kind = (isset($_REQUEST['kind'])) ? htmlspecialchars($_REQUEST['kind']) : '';
	$val = (isset($_REQUEST['val'])) ? htmlspecialchars($_REQUEST['val']) : '';
	$idc = (isset($_REQUEST['idc'])) ? htmlspecialchars($_REQUEST['idc']) : '';
	$id = (isset($_REQUEST['id'])) ? htmlspecialchars($_REQUEST['id']) : '';
	$curPage = (isset($_GET['p'])) ? htmlspecialchars($_GET['p']) : 1;
	if(isset($kind)) $dfgallery = ($kind == 'man_list') ? 'gallery_list' : 'gallery';
	else $dfgallery = '';

	/* Kiểm tra 2 máy đăng nhập cùng 1 tài khoản */
	if(array_key_exists($login_admin, $_SESSION) && (isset($_SESSION[$login_admin]['active']) || $_SESSION[$login_admin]['active'] == true))
	{
		$id_user = (int)$_SESSION[$login_admin]['id'];
		$timenow = time();

		$row = $d->rawQueryOne("select username, password, lastlogin, user_token from #_user WHERE id = ? limit 0,1",array($id_user));

		$sessionhash = md5(sha1($row['password'].$row['username']));
		
		if($_SESSION[$login_admin]['login_session'] != $sessionhash || ($timenow - $row['lastlogin']) > 86400)
		{
			session_destroy();
			$func->redirect("index.php?com=user&act=login");
		}

		if($_SESSION[$login_admin]['login_token'] !== $row['user_token']) $alertlogin = 'Có người đang đăng nhập tài khoản của bạn.';
		else $alertlogin ='';

		$token = md5(time());
		$_SESSION[$login_admin]['login_token'] = $token;

		/* Cập nhật lại thời gian hoạt động và token */
		$d->rawQuery("update #_user set lastlogin = ?, user_token = ? where id = ?",array($timenow,$token,$id_user));
	}

	/* Kiểm tra phân quyền */
	if(isset($config['permission']) && $config['permission'] == true && isset($_SESSION[$login_admin]['active']) && $_SESSION[$login_admin]['active'] == true)
	{
		/* Lấy quyền */
		$_SESSION['list_quyen'] = array();
		if(isset($_SESSION[$login_admin]['id']) && $_SESSION[$login_admin]['id'])
		{
			$id_nhomquyen = $d->rawQueryOne("select id_nhomquyen from #_user where id = ? and hienthi>0 limit 0,1",array($_SESSION[$login_admin]['id']));
			if(isset($id_nhomquyen['id_nhomquyen']) && $id_nhomquyen['id_nhomquyen'])
			{
				$nhomquyen = $d->rawQueryOne("select id from #_permission_group where id = ? and hienthi>0 limit 0,1",array($id_nhomquyen['id_nhomquyen']));
				if($nhomquyen['id'])
				{
					$quyenuser = $d->rawQuery("select quyen from #_permission where ma_nhom_quyen = ?",array($nhomquyen['id']));
					if(count($quyenuser))
					{
						foreach ($quyenuser as $value)
						{
							$_SESSION['list_quyen'][] = $value['quyen'];
						}
					}
				}
			}
		}

		/* Kiểm tra quyền */
		if($func->check_permission())
		{
			$kiemtra = true;
			if( $act != 'save' && 
				$act != 'save_list' && 
				$act != 'save_cat' && 
				$act != 'save_item' && 
				$act != 'save_sub' &&
				$act != 'save_brand' &&
				$act != 'save_mau' &&
				$act != 'save_size' &&
				$act != 'saveImages' &&
				$act != 'uploadExcel' &&
				$act != 'save_static' &&
				$act != 'save_photo')
			{
				if($com != 'user') 
				{
					if($com != '' && $com != 'index')
					{
						if($type != '')
							$quyen_user = $com.'_'.$act.'_'.$type;
						else
							$quyen_user = $com.'_'.$act;

						$quyen_aliases = array($quyen_user);
						if($com == 'cabin')
						{
							if(in_array($act, array('man', 'add', 'edit', 'delete', 'data', 'saveData', 'deleteData', 'deleteAllData', 'ajaxData', 'dangky', 'full_dangky', 'autofill_dangky', 'add_dangky', 'edit_dangky', 'save_dangky', 'delete_dangky', 'exportExcel')))
							{
								$quyen_aliases[] = 'cabin_man';
								$quyen_aliases[] = 'product_man_cabin';
							}
							if(in_array($act, array('upload', 'uploadExcel')))
							{
								$quyen_aliases[] = 'cabin_upload';
								$quyen_aliases[] = 'import_man_cabin';
							}
						}
						if($com == 'hoadon')
						{
							if(in_array($act, array('man', 'delete')))
							{
								$quyen_aliases[] = 'hoadon_man';
								$quyen_aliases[] = 'cabin_man';
								$quyen_aliases[] = 'product_man_cabin';
								$quyen_aliases[] = 'order_man';
							}
							if(in_array($act, array('upload', 'uploadExcel')))
							{
								$quyen_aliases[] = 'hoadon_upload';
								$quyen_aliases[] = 'cabin_upload';
								$quyen_aliases[] = 'import_man_cabin';
							}
						}
						if($com == 'xangdau')
						{
							$quyen_aliases[] = 'xangdau_man';
							$quyen_aliases[] = 'hoadon_man';
							$quyen_aliases[] = 'order_man';
							$quyen_aliases[] = 'product_man_cabin';
						}

						if($quyen_user == '_'){
							$quyen_user=='';
						}
						if(isset($_SESSION['list_quyen']))
						{
							$accessGranted = false;
							foreach($quyen_aliases as $quyen_alias)
							{
								if(in_array($quyen_alias, $_SESSION['list_quyen']))
								{
									$accessGranted = true;
									break;
								}
							}

							if(!$accessGranted)
							{
								$func->transfer("Bạn không có quyền vào khu vực này","index.php", false);
								exit;
							}
						}
					}
				}
			}
		}
	}

	/* Kiểm tra đăng nhập */
	if($func->check_login() == false && $act != "login")
	{
		$func->redirect("index.php?com=user&act=login");
	}

	/* Delete gallery */
	$func->deleteGallery();

	/* Delete cache */
	$cacheAction = array(
		'save',
		'save_copy',
		'save_list',
		'save_cat',
		'save_item',
		'save_sub',
		'save_brand',
		'save_city',
		'save_district',
		'save_wards',
		'save_street',
		'capnhat',
		'delete',
		'delete_list',
		'delete_cat',
		'delete_item',
		'delete_sub',
		'delete_brand',
		'delete_city',
		'delete_district',
		'delete_wards',
		'delete_street'
	);
	if(isset($_POST) && isset($cacheAction) && count($cacheAction) > 0) 
	{
		if(in_array($act, $cacheAction))
		{
			$cache->DeleteCache();
		}
	}
	
	/* Include sources */
	if(file_exists(SOURCES.$com.'.php')) include SOURCES.$com.".php";
	else $template = "index";
?>