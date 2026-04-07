<?php
	include ("../ajax/ajax_config.php");
	include_once "class_paging_ajax.php";

	$page = (int)$_POST["page"];
	$id_list = (int)$_POST["id_list"];
	$id_cat = (int)$_POST["id_cat"];

	if($page > 0)
    {
		$paging = new paging_ajax();
		$paging->class_pagination = "pagination";
		$paging->class_active = "active";
		$paging->class_inactive = "inactive";
		$paging->class_go_button = "go_button";
		$paging->class_text_total = "total";
		$paging->class_txt_goto = "txt_go_button";
		$paging->per_page = 8;
		$paging->page = $page;
		$sql = "select photo, ten$lang as ten, tenkhongdauvi, tenkhongdauen, giamoi, gia, giakm, id, type from table_product where hienthi=1 and noibat > 0 and type='san-pham'";
		if($id_list > 0) $sql .= " and id_list='".$id_list."'";
		if($id_cat >0 ) $sql .= " and id_cat='".$id_cat."'";
		$sql .= " order by stt,id desc";

		$paging->text_sql = $sql;
		$product = $paging->GetResult();
		$message = '';
		$paging->data = "".$message."";
    }
?>
<div class="wap_item">
	<?php echo $func->get_product($product);?>
</div>
<?=$paging->Load(); ?>
