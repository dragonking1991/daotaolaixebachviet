<div class="bix-hotro">
<style type="text/css">
	
	/*-------- Hỗ trợ trực tuyến -------------*/	
	div.bix-hotro{
		width:340px;
		position:fixed;
		top:216px;
		right: 0;
		transition:0.7s;
		z-index:99999;
		}
	div.bix-hotro .box_news a img{    max-width: 90px !important;
    border-radius: 50%;}
	div.bix-hotro .box_news{padding:10px;border: 0px;border-bottom: 1px dashed #ccc;}
	div.bix-hotro .nut-hotro{
		width:33px;
		float:left;
		height:230px;
		display:block;cursor: pointer;
		}
	div.bix-hotro .nut-hotro a{
		color: #ff0;
		font-size: 15px;
		cursor: pointer;
		text-decoration: none;
		width: 40px;
		display: block;
		transform: rotate(91deg);
		height: 20px;
		position: absolute;
		left: -32px;
		top: 80px;
		overflow:hidden;
		}
		
	div.bix-hotro .cont-hotro{
		background:#fff;
		width:300px;
		min-height:230px;
		box-sizing:border-box;
		border:2px solid #3f85ca;
		float:left;
		padding:10px;
		}
		
	div.item-yahoo{
		padding:10px 0px;
		border-bottom:1px solid #ccc;		
		}
	div.name-yaoo{
		font-size:16px;color: #3f85ca;font-weight:bold;
		}
	div.name-yaoo span{
		color:#f00;
		}
	div.icon-yaoo{
		text-align:center;
		}
	div.icon-yaoo img{max-width: 40px !important}
	div.dienthoai-yahoo span a{
		color:#000;
		text-decoration:none;		
		font-size:15px;
		}
	div.dienthoai-yahoo span a:hover{
		color:#00C;
		}

</style>  

<?php
	$yahoo = $d->rawQuery("select ten$lang, ngaytao, id, options2 from #_news where type = ? and hienthi > 0 order by stt,id desc",array('ho-tro-truc-tuyen'));
?>
<div class="nut-hotro">	
	<img src="assets/images/hotro_main.png" alt="Hỗ trợ"  id="close" />
    <img src="assets/images/hotro_main.png" alt="Hỗ trợ"  id="open" />	
</div>
<div class="cont-hotro">

<?php if(count($yahoo)!=0){?>
	<?php for($i=0;$i<count($yahoo);$i++){
		$optyahoo = (isset($yahoo[$i]['options2']) && $yahoo[$i]['options2'] != '') ? json_decode($yahoo[$i]['options2'],true) : null;
	?>
	<div class="item-yahoo">
    	<div class="name-yaoo"><?=$yahoo[$i]['ten'.$lang]?></div>
    	<div class="dienthoai-yahoo">Điện thoại: <span><a href="tel:<?=$optyahoo['dienthoai']?>"><?=$optyahoo['dienthoai']?></a></span></div>
    	<div class="dienthoai-yahoo"><span>Email: </span><?=$optyahoo['email']?></div>
    	<div class="icon-yaoo">
    		<a href="<?=$optyahoo['facebook']?>"><img src="assets/images/hotro_face.png" /></a>
    		&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="https://zalo.me/<?=preg_replace('/[^0-9]/','',$optyahoo['dienthoai']);?>"><img src="assets/images/zalo-combo.png" /></a>
            &nbsp;&nbsp;&nbsp;&nbsp;
        	<a href="skype:<?=$optyahoo['skype']?>?chat"><img src="assets/images/hotro_skype.png" /></a>            
        </div>
    </div>
    <?php }?>
<?php }?> 
 
</div>
<div class="clear"></div>
<script>
	$('document').ready(function(){		
		var width_right = $('.cont-hotro').width()+31;
		
		$('.bix-hotro').css({right: '-'+width_right+'px'});
		$('#close').css({display:'none'});
		
		$('#open').click(function(){
			$('#open').css({display:'none'});
			$('#close').css({display:'block'});
			$('.bix-hotro').css({right: '0px'});
		});
		$('#close').click(function(){
			$('#close').css({display:'none'});
			$('#open').css({display:'block'});
			$('.bix-hotro').css({right: '-'+width_right+'px'});
		});
	});
</script> 
</div>