<?php	
	if(!defined('SOURCES')) die("Error");

	/* Kiểm tra active import */
	if(isset($config['product']))
	{
		$arrCheck = array();
		foreach($config['product'] as $k => $v) if(isset($v['import']) && $v['import'] == true) $arrCheck[] = $k;
		if(!count($arrCheck) || !in_array($type,$arrCheck)) $func->transfer("Trang không tồn tại", "index.php", false);
	}
	else
	{
		$func->transfer("Trang không tồn tại", "index.php", false);
	}

	switch($act)
	{
		case "man":
			getImages();
			$template = "import/man/items";
			break;

		case "uploadImages":
			uploadImages();
			break;

		case "editImages":
			editImages();
			$template = "import/man/item_edit";
			break;

		case "saveImages":
			saveImages();
			break;

		case "deleteImages":
			deleteImages();
			break;

		case "uploadExcel":
			uploadExcel();
			break;

		default:
			$template = "404";
	}

	/* Get image */
	function getImages()
	{
		global $d, $func, $type, $curPage, $items, $paging;

		$per_page = 10;
		$startpoint = ($curPage * $per_page) - $per_page;
		$limit = " limit ".$startpoint.",".$per_page;
		$sql = "select * from #_excel where type = ? order by stt,id desc $limit";
		$items = $d->rawQuery($sql,array($type));
		$sqlNum = "select count(*) as 'num' from #_excel where type = ? order by stt,id desc";
		$count = $d->rawQueryOne($sqlNum,array($type));
		$total = $count['num'];
		$url = "index.php?com=import&act=man&type=".$type;
		$paging = $func->pagination($total,$per_page,$curPage,$url);
	}

	/* Edit image */
	function editImages()
	{
		global $d, $func, $item, $type, $curPage;

		$id = (isset($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(!$id) $func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);

		$item = $d->rawQueryOne("select * from #_excel where id = ? and type = ? limit 0,1",array($id,$type));

		if(!$item['id']) $func->transfer("Dữ liệu không có thực", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
	}

	/* Save image */
	function saveImages()
	{
		global $d, $item, $func, $type, $curPage, $config;

		if(empty($_POST)) $func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);

		$id = htmlspecialchars($_POST['id']);

		if($id)
		{
			if(isset($_FILES['file']))
			{
				$file_name = $func->uploadName($_FILES['file']["name"]);
				if($photo = $func->uploadImage("file", $config['import']['img_type'], UPLOAD_EXCEL, $file_name))
				{
					$data['photo'] = $photo;
					$row = $d->rawQueryOne("select id, photo from #_excel where id = ? and type = ? limit 0,1",array($id,$type));
					if(isset($row['id']) && $row['id'] > 0) $func->delete_file(UPLOAD_EXCEL.$row['photo']);
					
					$d->where('id', $id);
					$d->where('type', $type);
					if($d->update('excel',$data)) $func->transfer("Cập nhật dữ liệu thành công", "index.php?com=import&act=man&type=".$type."&p=".$curPage);
					else $func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
				}
				else
				{
					$func->transfer("Không nhận được hình ảnh mới", "index.php?com=import&act=editImages&id=".$id."&type=".$type."&p=".$curPage, false);
				}
			}
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
		}
	}

	/* Upload image */
	function uploadImages()
	{
		global $d, $type, $func, $config;

		if(isset($_POST['uploadImg']) && isset($_FILES['files'])) 
		{
			$arr_chuoi = '';
			$arr_file_del = array();

			if(isset($_POST['jfiler-items-exclude-files-0']))
			{
				$arr_chuoi = str_replace('"','',$_POST['jfiler-items-exclude-files-0']);
				$arr_chuoi = str_replace('[','',$arr_chuoi);
				$arr_chuoi = str_replace(']','',$arr_chuoi);
				$arr_chuoi = str_replace('\\','',$arr_chuoi);
				$arr_chuoi = str_replace('0://','',$arr_chuoi);
				$arr_file_del = explode(',',$arr_chuoi);
			}

			$dem = 0;
	        $myFile = $_FILES['files'];
	        $fileCount = count($myFile["name"]);

	        for($i=0; $i<$fileCount; $i++) 
	        {
	        	if(!in_array($myFile["name"][$i], $arr_file_del, true))
	        	{
	        		$_FILES['file'] = array('name' => $myFile['name'][$i],'type' => $myFile['type'][$i],'tmp_name' => $myFile['tmp_name'][$i],'error' => $myFile['error'][$i],'size' => $myFile['size'][$i]);
	        		$file_name = $func->uploadName($myFile["name"][$i]);
	        		if($photo = $func->uploadImage("file", $config['import']['img_type'], UPLOAD_EXCEL, $file_name))
	        		{
	        			$data['photo'] = $photo;
	        			$data['stt'] = (isset($_POST['stt-filer'][$dem]) && $_POST['stt-filer'][$dem] > 0) ? (int)$_POST['stt-filer'][$dem] : 0;
	        			$data['type'] = $type;
	        			$d->insert('excel',$data);
	        		}
	        		$dem++;
	        	}
	        }
	        $func->transfer("Lưu hình ảnh thành công", "index.php?com=import&act=man&type=".$type);
	    }
	    else
	    {
	    	$func->transfer("Dữ liệu rỗng", "index.php?com=import&act=man&type=".$type, false);
	    }
	}

	/* Delete image */
	function deleteImages()
	{
		global $d, $type, $func, $curPage;

		$id = (isset($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			$row = $d->rawQueryOne("select id, photo from #_excel where id = ? and type = ? limit 0,1",array($id,$type));

			if(isset($row['id']) && $row['id'] > 0)
			{
				$func->delete_file(UPLOAD_EXCEL.$row['photo']);
				$d->rawQuery("delete from #_excel where id = ? and type = ?",array($id,$type));
				$func->transfer("Xóa dữ liệu thành công", "index.php?com=import&act=man&type=".$type."&p=".$curPage);
			}
			else $func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);
				$row = $d->rawQueryOne("select id, photo from #_excel where id = ? and type = ? limit 0,1",array($id,$type));

				if(isset($row['id']) && $row['id'] > 0)
				{
					$func->delete_file(UPLOAD_EXCEL.$row['photo']);
					$d->rawQuery("delete from #_excel where id = ? and type = ?",array($id,$type));
				}
			}
			
			$func->transfer("Xóa dữ liệu thành công", "index.php?com=import&act=man&type=".$type."&p=".$curPage);
		} 
		else $func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
	}

	/* Transfer image */
	function transferphoto($photo)
	{
		global $d;

		$oldpath = UPLOAD_EXCEL.$photo;
		$newpath = UPLOAD_PRODUCT.$photo;

		if(file_exists($oldpath))
		{
			if(rename($oldpath,$newpath))
			{
				$d->rawQuery("delete from #_excel where photo = ?",array($photo));
			}
		}
	}

	/* Upload excel */
	function uploadExcel()
	{
		global $d, $type, $func, $config;

		if(isset($_POST['importExcel']))
		{
			$file_type = $_FILES['file-excel']['type'];

			if($file_type == "application/vnd.ms-excel" || $file_type == "application/x-ms-excel" || $file_type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
			{
				$mess = '';
				$filename = $func->changeTitle($_FILES["file-excel"]["name"]);
				move_uploaded_file($_FILES["file-excel"]["tmp_name"],$filename);			
				
				require LIBRARIES.'PHPExcel.php';
				require_once LIBRARIES.'PHPExcel/IOFactory.php';

				$objPHPExcel = PHPExcel_IOFactory::load($filename);

				foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) 
				{
					$worksheetTitle = $worksheet->getTitle();
					$highestRow = $worksheet->getHighestRow();
					$highestColumn = $worksheet->getHighestColumn();
					$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);

					$nrColumns = ord($highestColumn) - 64;

					for($row=2;$row<=$highestRow;++$row)
					{
						$cell = $worksheet->getCellByColumnAndRow(3, $row);
						$cccd = $cell->getValue();

						if($cccd!="")
						{
							$cell = $worksheet->getCellByColumnAndRow(0, $row);
							$stt = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(1, $row);
							$tenvi = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(2, $row);
							$ngaysinh = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(3, $row);
							$cccd = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(4, $row);
							if($type!='gplx') $hang = $cell->getValue();
							else $gplx = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(5, $row);
							if($type!='gxn') $gia = $cell->getValue();
							else $khoa = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(6, $row);
							$gxn = $cell->getValue();

							/* Gán dữ liệu */
							$data = array();
							$data['stt'] = (int)$stt;
							$data['tenvi'] = ($tenvi != '') ? htmlspecialchars($tenvi) : '';
							$data['tenkhongdauvi'] = ($data['tenvi'] != '') ? $func->changeTitle($data['tenvi']) : '';
							$data['ngaysinh'] = ($ngaysinh != '') ? htmlspecialchars($ngaysinh) : '';
							$data['cccd'] = ($cccd != '') ? htmlspecialchars($cccd) : '';
							$data['gplx'] = ($gplx != '') ? htmlspecialchars($gplx) : '';

							$data['hang'] = ($hang != '') ? htmlspecialchars($hang) : '';
							$data['khoa'] = ($khoa != '') ? htmlspecialchars($khoa) : '';
							$data['gxn'] = ($gxn != '') ? htmlspecialchars($gxn) : '';

							$data['gia'] = (isset($gia) && $gia != '') ? str_replace(".","",$gia) : 0;

							$data['type'] = $type;
							$data['hienthi'] = 1;

							/* Auto-generate QR image for type 'qr' */
							if($type == 'qr' && $cccd != '')
							{
								require_once LIBRARIES.'qr_helper.php';
								$qr_content = "TTGDNN BACH VIET\nHo ten: ".$data['tenvi']."\nCCCD: ".$data['cccd']."\nHang GPLX: ".$data['hang']."\nSo tien: ".$data['gia']." VND";
								$qr_filename = 'qr-'.$data['cccd'].'.png';
								$qr_filepath = ROOT.'/../upload/product/'.$qr_filename;
								$logo_path = ROOT.'/../assets/images/logo-vietcombank.png';
								generateQRWithLogo($qr_content, $qr_filepath, $logo_path);
								$data['photo'] = $qr_filename;
							}

							$proimport = $d->rawQueryOne("select id from #_product where cccd = ? and type = ? limit 0,1",array($cccd,$type));

							if(isset($proimport['id']) && $proimport['id'] > 0)
							{
								$d->where('type', $type);
								$d->where('cccd', $cccd);
								if($d->update('product',$data))
								{
								}
								else
								{
									$mess.='Lỗi tại dòng: '.$row."<br>";
								}
							}
							else
							{
								if($d->insert('product',$data))
								{
								}
								else
								{
									$mess.='Lỗi tại dòng: '.$row."<br>";
								}
							}
						}
					}
				}

				/* Xóa tập tin sau khi đã import xong */
				unlink($filename);

				/* Kiểm tra kết quả import */
				if($mess == '')
				{
					$mess = "Import danh sách thành công";
					$func->transfer($mess, "index.php?com=import&act=man&type=".$type);
				}
				else
				{
					$func->transfer($mess, "index.php?com=import&act=man&type=".$type, false);
				}
			}
			else
			{
				$mess = "Không hỗ trợ kiểu tập tin này";
				$func->transfer($mess, "index.php?com=import&act=man&type=".$type, false);
			}
		}
		else
		{
			$func->transfer("Dữ liệu rỗng", "index.php?com=import&act=man&type=".$type, false);
		}
	}
?>