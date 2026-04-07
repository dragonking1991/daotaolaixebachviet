<div class="wap_menu clear hidden_m">
    <div class="menu">
        <ul>
            <li><a class="<?php if($com=='' || $com=='index') echo 'active'; ?>" href="" title="<?=trangchu?>"><?=trangchu?></a></li>
            
            <li><a class="<?php if($com=='gioi-thieu') echo 'active'; ?>" href="gioi-thieu" title="<?=gioithieu?>"><?=gioithieu?></a></li>
            
            <li><a class="<?php if($com=='dao-tao') echo 'active'; ?>" href="dao-tao" title="Hạng Đào Tạo">Hạng Đào Tạo</a>
                <?=$func->for1('news','dao-tao');?>
            </li>

            <li><a class="<?php if($com=='chieu-sinh-dao-tao') echo 'active'; ?>" href="chieu-sinh-dao-tao" title="Chiêu sinh - đào tạo">Chiêu sinh - đào tạo</a>
                <?=$func->for1('news_list','chieu-sinh-dao-tao');?>
            </li>

             <li><a class="<?php if($com=='tin-tuc') echo 'active'; ?>" href="tin-tuc" title="Thông tin mới nhất">Thông tin mới nhất</a>
            </li>
            
            <li><a class="<?php if($com=='tai-lieu') echo 'active'; ?>" href="tai-lieu" title="Tài liệu">Tài liệu</a></li>
            
            <li><a class="<?php if($com=='lien-he') echo 'active'; ?>" href="lien-he" title="Đăng ký tận nơi - thủ tục đơn giản">Đăng ký tận nơi - thủ tục đơn giản</a></li> 

            <li><a  href="admin/" title="Đăng nhập">Đăng nhập</a></li>       
        </ul>

        <div class="wap_search">
            <i class="fas fa-search tim"></i>
            <div class="search">
                <input type="text" id="keyword" placeholder="<?=nhaptukhoatimkiem?>" onkeypress="doEnter(event,'keyword');"/>
                <p onclick="onSearch('keyword');"><i class="fas fa-search"></i></p>
            </div>
        </div>
    </div>
</div>