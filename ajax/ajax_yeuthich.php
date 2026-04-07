<?php
	include "ajax_config.php";
    $id = (isset($_POST['id']) && $_POST['id'] > 0) ? htmlspecialchars($_POST['id']) : 0;
    
    if(!isset($_SESSION['sanphamyeuthich'])){
        $_SESSION['sanphamyeuthich'][]=$id;
    }else{
        if(in_array($id, $_SESSION['sanphamyeuthich'])==false){
            $_SESSION['sanphamyeuthich'][]=$id;
        }
    }

    $return['soluong'] = count($_SESSION['sanphamyeuthich']);
    echo json_encode($return);
?>