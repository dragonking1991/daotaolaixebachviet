<?php  
	if(!defined('SOURCES')) die("Error");

	/* Tìm kiếm sản phẩm */
	if(isset($_GET['keyword']))
	{
		$tukhoa = htmlspecialchars($_GET['keyword']);
		$tukhoa_khongdau = $func->changeTitle($tukhoa);

		$tukhoa = str_replace("đ","d",$tukhoa);
	    $tukhoa = str_replace(' ','%',$tukhoa);

		if($tukhoa)
		{
			$where = "";
			$where = " (type = 'dao-tao' or type = 'chieu-sinh-dao-tao' or type = 'tin-tuc' or type = 'tai-lieu') and (REPLACE(ten$lang, 'đ', 'd') LIKE ? or tenkhongdau$lang LIKE ?) and hienthi > 0";
			$params = array("%$tukhoa%","%$tukhoa_khongdau%");

			$curPage = $get_page;
			$per_page = $optsetting['soluong_sp'];
			$startpoint = ($curPage * $per_page) - $per_page;
			$limit = " limit ".$startpoint.",".$per_page;
			$sql = "select photo, ten$lang as ten, tenkhongdauvi, tenkhongdauen, mota$lang as mota, id from #_news where $where order by stt,id desc $limit";
			$news = $d->rawQuery($sql,$params);
			$sqlNum = "select count(*) as 'num' from #_news where $where order by stt,id desc";
			$count = $d->rawQueryOne($sqlNum,$params);
			$total = $count['num'];
			$url = $func->getCurrentPageURL();
			$paging = $func->pagination($total,$per_page,$curPage,$url);
		}
	}

	/* SEO */
	$seo->setSeo('title',$title_crumb);

	/* breadCrumbs */
	$breadcr->setBreadCrumbs('',$title_crumb);
	$breadcrumbs = $breadcr->getBreadCrumbs();
?>