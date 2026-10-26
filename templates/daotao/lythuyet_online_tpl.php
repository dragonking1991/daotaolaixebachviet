<?php if(!defined('SOURCES')) die("Error"); ?>
<section class="online-theory" aria-label="Lý thuyết online">
    <!--
    <div class="online-theory-toolbar">
        <h1>Lý thuyết online</h1>
        <a href="<?=htmlspecialchars($onlineTheoryUrl, ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt" aria-hidden="true"></i> Mở trang riêng</a>
    </div>
    -->
    <iframe id="online-theory-frame" src="<?=htmlspecialchars($onlineTheoryUrl, ENT_QUOTES, 'UTF-8')?>" title="Lý thuyết online Bách Việt" allow="fullscreen" referrerpolicy="no-referrer"></iframe>
</section>
<style>
.online-theory{width:100%;background:#fff;}
.online-theory-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:8px 16px;border-bottom:1px solid #e5e5e5;}
.online-theory-toolbar h1{margin:0;font-size:18px;line-height:1.5;}
.online-theory-toolbar a{display:inline-flex;align-items:center;gap:6px;color:#1b57a6;font-size:14px;}
#online-theory-frame{display:block;width:100%;height:80vh;min-height:360px;border:0;}
</style>
<script>
(function(){
    var frame = document.getElementById('online-theory-frame');
    function fitFrame(){
        frame.style.height = Math.max(360, window.innerHeight - frame.getBoundingClientRect().top) + 'px';
    }
    fitFrame();
    window.addEventListener('load', fitFrame);
    window.addEventListener('resize', fitFrame);
})();
</script>