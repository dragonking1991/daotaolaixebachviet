<?php
    include ("ajax_config.php");
   
    global $config_base,$lang,$config,$id_box;
    // Lấy trang hiện tại
    $id_danhmuc = (int)$_POST['id_danhmuc'];
    $id_list = (int)$_POST['id_list'];
    $page_per = (int)$_POST['page_per'];
    $table_select = (string)$_POST['table_select'];
    $type_select = (string)$_POST['type_select'];
    $where_select = (string)$_POST['where_select'];
    $tab_return = (string)$_POST['tab_return'];
    $page = (string)$_POST['page'];
    $showphantrang = (int)$_POST['showphantrang'];//echo ($showphantrang);
    // Kiểm tra trang hiện tại có bé hơn 1 hay không
    if ($page < 1) {
        $page = 1;
    }
    // Số record trên một trang
    $limit = $page_per;
    // Tìm start
    $start = ($limit * $page) - $limit;
    if($id_danhmuc){ $where_tmp .= " and id_list=" . $id_danhmuc; }
    if($id_list){ $where_tmp .= " and id_cat=" . $id_list; }
   
    $where = "hienthi=1 and type='$type_select' $where_select $where_tmp order by stt,id desc";
    $text_sql = "select * from table_$table_select where $where limit $start,$limit";
    $sqlc_num="SELECT count(id) as dem FROM table_$table_select where $where";
    $arr_sqlc_num=array(); 
    $count_num = $d->rawQueryOne($sqlc_num,$arr_sqlc_num);    
    // Câu truy vấn
    $arr_sqlc_records=array();
    $result_records = $d->rawQuery($text_sql,$arr_sqlc_records);
?>
<?php if($result_records) {

    switch($type_select) {
            case 'san-pham':                
                echo '<div class="run-slick run-cat'.$id_list.$id_danhmuc.'">'.$func->lay_sanpham($result_records).'</div>';
                break;           
        } ?>
        
    <script>
        $(document).ready(function(){
               
                    $('.run-cat<?=$id_list.$id_danhmuc?>').slick({
                        lazyLoad: 'progressive', infinite: true, accessibility:true, vertical:false, slidesToShow: 4,  
                        slidesToScroll: 1, autoplay:true,  autoplaySpeed:3000, speed:1000, arrows:true, 
                        centerMode:false,  dots:false,  draggable:true,  responsive: [ 
                        {  breakpoint: 800, settings: { slidesToShow: 2, slidesToScroll: 1,arrows:true } } ]
                    });               
        });            
    </script>

<?php } ?>

 
