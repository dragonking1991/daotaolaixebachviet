<?php

class paging_ajax
{
    public $data; // DATA
    public $per_page = 1; // SỐ RECORD TRÊN 1 TRANG
    public $page; // SỐ PAGE 
    public $text_sql; // CÂU TRUY VẤN
    
    //  THÔNG SỐ SHOW HAY HIDE 
    public $show_pagination = true;
    public $show_goto = false;
    public $show_total = true;
    
    // TÊN CÁC CLASS
    public $class_pagination; 
    public $class_active;
    public $class_inactive;
    public $class_go_button;
    public $class_text_total;
    public $class_txt_goto;    
    
    private $cur_page;  // PAGE HIỆN TẠI
    private $num_row; // SỐ RECORD
    
    // PHƯƠNG THỨC LẤY KẾT QUẢ CỦA TRANG 
    public function GetResult()
    {
        global $d; // BIỀN $db TOÀN CỤC
        
        // TÌNH TOÁN THÔNG SỐ LẤY KẾT QUẢ
        $this->cur_page = $this->page;
        $this->page -= 1;
        $this->per_page = $this->per_page;
        $start = $this->page * $this->per_page;
        
        // TÍNH TỔNG RECORD TRONG BẢNG

        $result = $d->rawQuery($this->text_sql);
        $this->num_row = count($result);
        // LẤY KẾT QUẢ TRANG HIỆN TẠI
        //$d->query($this->text_sql." LIMIT $start, $this->per_page");echo 'aaaaaaa';
        //return $d->rawQuery($this->text_sql." LIMIT $start, $this->per_page");
        return $d->rawQuery($this->text_sql." LIMIT $start, $this->per_page");
    }
    
    // PHƯƠNG THỨC XỬ LÝ KẾT QUẢ VÀ HIỂN THỊ PHÂN TRANG
    public function Load()
    {
         global $d; 
        // KHÔNG PHÂN TRANG THÌ TRẢ KẾT QUẢ VỀ
        if(!$this->show_pagination)
            return $this->data;
        
        // SHOW CÁC NÚT NEXT, PREVIOUS, FIRST & LAST
        $previous_btn = true;
        $next_btn = true;
        $first_btn = true;
        $last_btn = true;    
        
        // GÁN DATA CHO CHUỖI KẾT QUẢ TRẢ VỀ 
        $msg = $this->data;
        
        // TÍNH SỐ TRANG
        $count = $this->num_row;
        $no_of_paginations = ceil($count / $this->per_page);
        
        // TÍNH TOÁN GIÁ TRỊ BẮT ĐẦU & KẾT THÚC VÒNG LẶP
        if ($this->cur_page >= 5) {
            $start_loop = $this->cur_page - 3;
            if ($no_of_paginations > $this->cur_page + 3)
                $end_loop = $this->cur_page + 3;
            else if ($this->cur_page <= $no_of_paginations && $this->cur_page > $no_of_paginations - 5) {
                $start_loop = $no_of_paginations - 5;
                $end_loop = $no_of_paginations;
            } else {
                $end_loop = $no_of_paginations;
            }
        } else {
            $start_loop = 1;
            if ($no_of_paginations > 6)
                $end_loop = 6;
            else
                $end_loop = $no_of_paginations;
        }
		
		//Kiểm tra phần tử đủ nhiều để phân trang
		global $d; // BIỀN $db TOÀN CỤC
		
		// TÍNH TỔNG RECORD TRONG BẢNG

		$result = $d->rawQuery($this->text_sql);

		$this->num_row = count($result);
		if($this->num_row > $this->per_page){$hien_phantrang = 1;}

        
		
        $msg ='';
		if($hien_phantrang==1){
        // NỐI THÊM VÀO CHUỖI KẾT QUẢ & HIỂN THỊ NÚT FIRST 
			$msg .= "<div class='$this->class_pagination'><ul class='pages_ajax'>";
			if ($first_btn && $this->cur_page > 1) {
				$msg .= "<li p='1' class='active'>First</li>";
			} else if ($first_btn) {
				$msg .= "<li p='1' class='$this->class_inactive'>First</li>";
			}
		
			// HIỂN THỊ NÚT PREVIOUS
			if ($previous_btn && $this->cur_page > 1) {
				$pre = $this->cur_page - 1;
				$msg .= "<li p='$pre' class='active'>Prev</li>";
			} else if ($previous_btn) {
				$msg .= "<li class='$this->class_inactive'>Prev</li>";
			}
			for ($i = $start_loop; $i <= $end_loop; $i++) {
			
				if ($this->cur_page == $i){
                    if($i>0)
					$msg .= "<li p='$i' class='actived'><a>{$i}</a></li>";
                }
				else{
                    if($i>0)
					$msg .= "<li p='$i' class='active'><a>{$i}</a></li>";
                }
			}
			
			// HIỂN THỊ NÚT NEXT
			if ($next_btn && $this->cur_page < $no_of_paginations) {
				$nex = $this->cur_page + 1;
				$msg .= "<li p='$nex' class='active'>Next</li>";
			} else if ($next_btn) {
				$msg .= "<li class='$this->class_inactive'>Next</li>";
			}
			
			// HIỂN THỊ NÚT LAST
			if ($last_btn && $this->cur_page < $no_of_paginations) {
				$msg .= "<li p='$no_of_paginations' class='$this->class_active'>End</li>";
			} else if ($last_btn) {
				$msg .= "<li p='$no_of_paginations' class='$this->class_inactive'>End</li>";
			}
		}
        
        // SHOW TEXTBOX ĐỂ NHẬP PAGE KO ? 
        if($this->show_goto)
              $goto = "<input type='text' id='goto' class='$this->class_txt_goto' size='1' style='margin-top:-1px;margin-left:40px;margin-right:10px'/><input type='button' id='go_btn' class='$this->class_go_button' value='Đến'/>";
        if($this->show_total)
            $total_string = "<span class='$this->class_text_total' a='$no_of_paginations'>Page <b>" . $this->cur_page . "</b>/<b>$no_of_paginations</b></span>";
        $stradd =  $goto . $total_string;
        
        // TRẢ KẾT QUẢ TRỞ VỀ
        return $msg . "</ul>" . $stradd . "</div>";  // Content for pagination
    }     
            
}

function Get_zingmp3($link){ 
    $source = _viewSource($link); 
    $xml = explode('&amp;xmlURL=',$source); 
    $xml = explode('&amp;',$xml[1]); 
    $xml = $xml[0]; 
    $sourceXML = _viewSource($xml); 
    $dl = explode('<source><![CDATA[',$sourceXML); 
    $dl = explode(']]></source>',$dl[1]); 
    $dl = $dl[0]; 
    $link = showlink($dl);
    return $link;
} 

function _viewSource($url){ 
    $parse_url = parse_url($url); 
    $headers = array("Host: {$parse_url['host']}"); 
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,$url); 
    curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Linux; U; Android 4.0.3; ko-kr; LG-L160L Build/IML74K) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30"); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  
    curl_setopt($ch, CURLOPT_REFERER, $url);  
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); 
    curl_setopt($ch, CURLOPT_HEADER, false); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $result = curl_exec($ch); 
    curl_close($ch); 
    return $result; 
} 
function showlink($xmlURL)
{
    $ch = curl_init($xmlURL);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


    $data = curl_exec($ch);
    $data = curl_getinfo($ch);
    return $data['url'];
}

function youtobi($id)
{
    $ext = explode('=',$id);
    $vaich = $ext[1];
    return $vaich;
}
?>