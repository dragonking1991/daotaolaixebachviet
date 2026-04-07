<?php
    include ("ajax_config.php");
    function phantrang_ajax($cur_p , $total_p , $tab_return, $page_per, $table_select, $type_select, $where_select, $id_danhmuc, $id_list='', $showphantrang=1) {
        $pageSize = 5;
        $offset = 2;
        $arr_url = explode('&p',$url);
        $url = $arr_url[0];
        // if ($totalRows<=0) return "";
        $totalPages = $total_p; /*ceil($totalRows/$pageSize);*/;
        if ($totalPages<=1) return "";
        $currentPage = $cur_p;
        settype($currentPage,"int");
        if ($currentPage <=0) $currentPage = 1;
        $firstLink = '<li><a  class="left" data-page="1" data-tab="'.($tab_return).'" data-per="'.($page_per).'" data-table="'.($table_select).'" data-type="'.($type_select).'" data-where="'.($where_select).'"  data-danhmuc="'.($id_danhmuc).'" data-list="'.($id_list).'"  data-showphantrang="'.($showphantrang).'"><i class="fas fa-caret-left"></i></a></li>';
        $lastLink = '<li><a  class="right" data-page="'.($totalPages).'" data-tab="'.($tab_return).'" data-per="'.($page_per).'" data-table="'.($table_select).'" data-type="'.($type_select).'" data-where="'.($where_select).'"  data-danhmuc="'.($id_danhmuc).'" data-list="'.($id_list).'"  data-showphantrang="'.($showphantrang).'"><i class="fas fa-caret-right"></i></a></li>';;
        $from = $currentPage - $offset;
        $to = $currentPage + $offset;
        if ($from <=0) { $from = 1;   $to = $offset*2; }
        if ($to > $totalPages) { $to = $totalPages; }
        for($j = $from; $j <= $to; $j++) {
          if ($j == $currentPage) $links = $links . '<li><a  class="active" data-page="'.($j).'" data-tab="'.($tab_return).'" data-per="'.($page_per).'" data-table="'.($table_select).'" data-type="'.($type_select).'" data-where="'.($where_select).'"  data-danhmuc="'.($id_danhmuc).'" data-list="'.($id_list).'"  data-showphantrang="'.($showphantrang).'">'.$j.'</a></li>';
          else{
        $qt = $url. "&p={$j}";
        $links= $links . '<li><a class="" data-page="'.($j).'" data-tab="'.($tab_return).'" data-per="'.($page_per).'" data-table="'.($table_select).'" data-type="'.($type_select).'" data-where="'.($where_select).'"  data-danhmuc="'.($id_danhmuc).'" data-list="'.($id_list).'"  data-showphantrang="'.($showphantrang).'">'.$j.'</a></li>';
          }
        } //for
        return '<ul class="pages paging-sm">'.$firstLink.$links.$lastLink.'</ul>';
    }
   
    global $config_base,$lang,$config;
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
    if($id_danhmuc) $where_tmp .= " and id_list=" . $id_danhmuc;
    if($id_list) $where_tmp .= " and id_cat=" . $id_list;
    $where = "hienthi=1 and type='$type_select' $where_select $where_tmp order by stt,id desc";
    $text_sql = "select * from table_$table_select where $where limit $start,$limit";

    $sqlc_num="SELECT count(id) as dem FROM table_$table_select where $where";
    $arr_sqlc_num=array(); 
    $count_num = $d->rawQueryOne($sqlc_num,$arr_sqlc_num);
    
    // Tổng số trang
    $page_count = ceil($count_num['dem'] / $page_per);
    
    // Câu truy vấn
    $arr_sqlc_records=array();
    $result_records = $d->rawQuery($text_sql,$arr_sqlc_records);
    
    //$result_records = get_result($text_sql);
    //die($config['product']['san-pham']['showbuttonmuahang']);
?>
<?php if($result_records) {

    switch($type_select) {
            case 'san-pham':                
                echo '<div class="pro-in">'.$func->lay_sanpham($result_records).'</div>';
                break;
            case 'thu-vien':
                echo ''.$func->lay_thuvien($result_records,'item',0,0,0,0,1,$type_select,0,_upload_tintuc_l).'';
                break;
            case 'cong-trinh':
                echo ''.$func->lay_congtrinh($result_records,'item',0,0,0,0,1,$type_select,1,_upload_tintuc_l,'catchuoi3','',1,0).'';
                break;
            case 'video':
                echo ''.$func->lay_video($result_records,'item',0,0,0,0,1,$type_select,0).'';
                break;
            case 'orther':
                echo ''.$func->lay_tinkhac($type_select,'',1,0,'','','',1,0,'catchuoi2',0,$result_records,_upload_tintuc_l,0,1,1).'';
                break;      
            default:
                echo ''.$func->lay_news($type_select,'',1,0,'','','',1,0,'catchuoi2',0,$result_records,_upload_tintuc_l,1,1,1).'';
                break;
        }     
 } ?>

<?php if($showphantrang==1){ if($page_count>1) echo phantrang_ajax($page,$page_count,$tab_return,$page_per,$table_select,$type_select,$where_select,$id_danhmuc,$id_list,$showphantrang); }?>