  <div class="title-main"><span><?=$row_detail['ten']?></span></div>
     
  <?php /*<div class="meta-toc">
      <div class="box-readmore">
          <ul class="toc-list" data-toc="article" data-toc-headings="h1, h2, h3"></ul>
      </div>
  </div>?>

  <?php if(count($hinhanhtt) > 0) { ?>
      <div style="text-align: center;">
        <div class="fotorama fotorama5" data-nav="thumbs" data-maxheight="700" data-arrows="true" data-thumbwidth="" data-thumbheight="" data-loop="true" data-autoplay="4000" data-fit="contain" data-allowfullscreen="true" data-stopautoplayontouch="false">
                <?php foreach($hinhanhtt as $k => $v) { ?>
                    <img src="<?=UPLOAD_NEWS_L.$v['photo']; ?>" />
                <?php } ?>
        </div>
    </div>
  <?php }*/ ?>

  <div class="content-main" id="toc-content"><?=htmlspecialchars_decode($row_detail['noidung'])?></div>
  <?php include TEMPLATE.LAYOUT."share.php"; ?>

  <div class="share othernews">
      <b><?=baivietkhac?>:</b>
      <ul class="list-news-other">
          <?php foreach($news as $k => $v) { ?>
              <li><a href="<?=$v[$sluglang]?>" title="<?=$v['ten']?>"><?=$v['ten']?></a></li>
          <?php } ?>
      </ul>
      <div class="pagination-home"><?=(isset($paging) && $paging != '') ? $paging : ''?></div>
  </div>
