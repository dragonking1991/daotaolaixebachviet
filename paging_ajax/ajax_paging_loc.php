<?php
	include ("../ajax/ajax_config.php");
	include_once "class_paging_ajax.php";

	$page = (int)$_POST["page"];
	$id_list = (int)$_POST["id_list"];
	$id_cat = (int)$_POST["id_cat"];
	$type = (string)$_POST["type"];

	$phanloai = (string)$_POST["phanloai"];
	$phanloai = trim(str_replace(",", " ", $phanloai));
	$phanloai = str_replace(" ", ",", $phanloai);

	$mucgia = (string)$_POST["mucgia"];
	$mucgia = trim(str_replace(",", " ", $mucgia));
	$mucgia = str_replace(" ", ",", $mucgia);
	$arr_mucgia = explode(',',$mucgia);

	if($page > 0)
    {
		$paging = new paging_ajax();
		$paging->class_pagination = "pagination";
		$paging->class_active = "active";
		$paging->class_inactive = "inactive";
		$paging->class_go_button = "go_button";
		$paging->class_text_total = "total";
		$paging->class_txt_goto = "txt_go_button";
		$paging->per_page = $optsetting['soluong_sp'];
		$paging->page = $page;
		$sql = "select photo, ten$lang as ten, tenkhongdauvi, tenkhongdauen, giamoi, gia, giakm, id, type, options2 from table_product where hienthi=1 and type='".$type."'";

		if($id_list > 0) $sql .= " and id_list='".$id_list."'";
		if($id_cat > 0 ) $sql .= " and id_cat='".$id_cat."'";
		if($id_brand > 0 ) $sql .= " and id_brand='".$id_brand."'";
		if($phanloai != '' && $phanloai!=0) $sql .= " and FIND_IN_SET(id_brand, '".$phanloai."')>0";
		if(count($arr_mucgia)>0){
			for($i=0; $i<count($arr_mucgia); $i++){
				if($arr_mucgia[$i]>0){
					if($i==0) $sql .= " and (";
						$mucgia = $d->rawQueryOne("select id,gia1,gia2 from #_news where id='".$arr_mucgia[$i]."'");
						if($i==0)$sql .= " (gia BETWEEN '".$mucgia['gia1']."' AND '".$mucgia['gia2']."')";
						else $sql .= " or (gia BETWEEN '".$mucgia['gia1']."' AND '".$mucgia['gia2']."')";
					if(($i+1)==count($arr_mucgia)) $sql .= " )";
				}
			}
		}
		
		$sql .= " order by stt,id desc";
		$paging->text_sql = $sql;
		$product = $paging->GetResult();
		$message = '';
		$paging->data = "".$message."";
    }
?>
<div class="wap_item wap_item2">
	<?php echo $func->get_product($product);?>
</div>
<?=$paging->Load(); ?>
