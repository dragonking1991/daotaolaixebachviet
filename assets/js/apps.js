/* Validation form */
ValidationFormSelf("validation-newsletter");
ValidationFormSelf("validation-contact");

/* Exists */
$.fn.exists = function(){
    return this.length;
};

/* Back to top */
NN_FRAMEWORK.BackToTop = function(){
    $(window).scroll(function(){
        if(!$('.scrollToTop').length) $("body").append('<div class="scrollToTop"><img src="'+GOTOP+'" alt="Go Top"/></div>');
        if($(this).scrollTop() > 100) $('.scrollToTop').fadeIn();
        else $('.scrollToTop').fadeOut();
    });

    $('body').on("click",".scrollToTop",function() {
        $('html, body').animate({scrollTop : 0},800);
        return false; 
    });

    $('body').on("click",".frm_tracuu ul li",function() {
        $(".frm_tracuu ul li").removeClass('active');
        $(this).addClass('active');
        return false; 
    });

    $('body').on("click",".c_tracuu",function() {
        var cccd = $('.cccd').val();
        var type = $('.frm_tracuu ul li.active').data('type');
        if(cccd=='') {
            alert('Vui lòng nhập số CCCD');
            return false; 
        }
        else if (cccd.length < 11 || cccd.length > 12) {
            alert("CCCD phải từ 11 đến 12 ký tự!");
            return false; 
        }
        if(cccd)
        {             
            $.ajax({
                url:'ajax/tracuu.php',
                type: "POST",
                dataType: 'html',
                async: false,
                data: {cccd:cccd,type:type},
                success: function(result){
                    $(".ketqua").html(result);
                    $.fancybox.open($('.ketqua'));
                }
            });
        }
        return false; 
    });

};

/* Alt images */
NN_FRAMEWORK.AltImages = function(){
    $('img').each(function(index, element) {
        if(!$(this).attr('alt') || $(this).attr('alt')=='')
        {
            $(this).attr('alt',WEBSITE_NAME);
        }
    });
};

NN_FRAMEWORK.chaychu = function(){
    $(function () {
        $('.slogan').textillate({
            loop:true,
            in: {
                effect:'bounce',//fadeInLeftBig
                delayScale: 1.5,
                delay: 50,
                sync:false,
                reverse:false,
                shuffle:false,
                callback:function () {}},
            out: {
                effect:'wobble',
                delayScale: 1.5,
                delay: 100,
                sync:false,
                reverse:false,
                shuffle:false,
                callback:function () {}
            },
        });
    })
};


/* Fix menu */
NN_FRAMEWORK.FixMenu = function(){
    $(window).scroll(function(){
        if($(window).scrollTop() >= $(".wap_thongtin").height()) $(".wap_header").addClass('wap_header_fix');
        else $(".wap_header").removeClass('wap_header_fix');
    });

    $('.dmsp ul li').hover(function(){
       var vitri = $(this).position().top;
       $(this).children('ul').css({'top':vitri+'px'});
    });


    $(".menu ul li").hover(function(){
        $(this).find('ul:first').css({visibility: "visible",display: "none"}).show(300);
        },function(){
        $(this).find('ul:first').css({visibility: "hidden"});
        });
    $(".menu ul li").hover(function(){
            $(this).find('>a').addClass('active2');
        },function(){
            $(this).find('>a').removeClass('active2');
    });

    $("#danhmuc ul li a").click(function(){
      if($(this).parent('li').children('ul').find('li').length>0)
      {
          if($(this).hasClass('active'))
          {
            $(this).parent('li').find('ul:first').hide(300);
            $(this).removeClass('active');
          }
          else
          {
            $(this).parent('li').find('ul:first').show(300);
            $(this).addClass('active');
          }
        return false;
      }
    });
};

/* Fix menu */
NN_FRAMEWORK.mMenu = function(){
    /* tạo menu mobile */
    var menu_mobi = $('.menu ul').html();
    $('.menu_mobi_add').append('<span class="close_menu">X</span><ul>'+menu_mobi+'</ul>');
    $(".menu_mobi_add ul li").each(function(index, element) {
        if($(this).children('ul').children('li').length>0){
            $(this).children('a').append('<i class="fas fa-chevron-right"></i>');
        }
    });
    $(".menu_mobi_add ul li a i").click(function(){
        if($(this).parent('a').hasClass('active2')){
            $(this).parent('a').removeClass('active2');
            if($(this).parent('a').parent('li').children('ul').children('li').length > 0){
                $(this).parent('a').parent('li').children('ul').hide(300);
                return false;
            }
        }
        else{
            $(this).parent('a').addClass('active2');
            if($(this).parents('li').children('ul').children('li').length > 0){
                $(".menu_m ul li ul").hide(0);
                $(this).parents('li').children('ul').show(300);
                return false;
            }
        }
    });

    $('.icon_menu_mobi,.close_menu,.menu_baophu').click(function(){
        if($('.menu_mobi_add').hasClass('menu_mobi_active'))
        {
            $('.menu_mobi_add').removeClass('menu_mobi_active');
            $('.menu_baophu').fadeOut(300);
        }
        else
        {
            $('.menu_mobi_add').addClass('menu_mobi_active');
            $('.menu_baophu').fadeIn(300);
        }
        return false;
    });
};

/* Tools */
NN_FRAMEWORK.Tools = function(){
    if($(".toolbar").exists())
    {
        $(".wap_copy").css({marginBottom:$(".toolbar").innerHeight()});
    }
};
/* Popup */
NN_FRAMEWORK.Popup = function(){
    if($("#popup").exists()){
        $('#popup').modal('show');
    }
};
/* Wow */
NN_FRAMEWORK.WowAnimation = function(){
    WOW.prototype.addBox = function(element) {
        this.boxes.push(element);
      };
      var wow = new WOW();
      wow.init();

      $('.wow').on('scrollSpy:exit', function() {
        $(this).css({
          'visibility': 'hidden',
          'animation-name': 'none'
        }).removeClass('animated');
        wow.addBox(this);
      }).scrollSpy();
};
/* Toc */
NN_FRAMEWORK.Toc = function(){
    if($(".toc-list").exists())
    {
        $(".toc-list").toc({
            content: "div#toc-content",
            headings: "h2,h3,h4"
        });
        if(!$(".toc-list li").length) $(".meta-toc").hide();
        $('.toc-list').find('a').click(function(){
            var x = $(this).attr('data-rel');
            goToByScroll(x);
        });
    }
};

/* Tabs */
NN_FRAMEWORK.Tabs = function(){
    if($(".ul-tabs-pro-detail").exists())
    {
        $(".ul-tabs-pro-detail li").click(function(){
            var tabs = $(this).data("tabs");
            $(".content-tabs-pro-detail, .ul-tabs-pro-detail li").removeClass("active");
            $(this).addClass("active");
            $("."+tabs).addClass("active");
        });
    }
};

/* Sap Xep */
NN_FRAMEWORK.Sapxep = function(){
    if($(".sapxep").exists())
    {
        $(window).resize(function(){
            var manghinh = $(window).width();
            if(manghinh>1366){
                mosaicGrid('.sapxep', '.item_sx',[0,0,0,0]);
            }
            else if(manghinh>604){
                mosaicGrid('.sapxep', '.item_sx',[0,0,0]);
            }
            else {
                mosaicGrid('.sapxep', '.item_sx',[0,0]);
            }
        });

        $(window).load(function(){
            var manghinh = $(window).width();
            if(manghinh>1366){
                mosaicGrid('.sapxep', '.item_sx',[0,0,0,0]);
            }
            else if(manghinh>604){
                mosaicGrid('.sapxep', '.item_sx',[0,0,0]);
            }
            else {
                mosaicGrid('.sapxep', '.item_sx',[0,0]);
            }
        });
    }
};

/* Datetime picker */
NN_FRAMEWORK.DatetimePicker = function(){
    if($('#ngaysinh').exists())
    {
        $('#ngaysinh').datetimepicker({
            timepicker: false,
            format: 'd/m/Y',
            formatDate: 'd/m/Y',
            minDate: '01/01/1950',
            maxDate: TIMENOW
        });
    }
};

/* Search */
NN_FRAMEWORK.Search = function(){
    if($(".tim").exists())
    {
        var lan = 0;
        $('.tim').click(function() {
            if(lan==0){
                $('.search').slideDown(300);
                lan = 1;
            }
            else{
                $('.search').slideUp(300);
                lan = 0;
            }
        });
    }
};
NN_FRAMEWORK.Lazy = function(){
    $("img:not('.no_lazy')").lazyload({
        placeholder : "./assets/images/lazy2.gif",
        effect : "fadeIn",
    });
};

NN_FRAMEWORK.SlickPage = function(){
    if($(".slider").exists()){
        $('.slider_slick').slick({
            //vertical:true,
            //fade: true,
            //slidesPerRow: 2,
            //cssEase: 'linear',
            //mobileFirst:true,
            //variableWidth: true,
            lazyLoad: 'ondemand',
            infinite: true,
            accessibility:false,
            slidesToShow: 1,
            slidesToScroll: 1,
            autoplay:true,
            autoplaySpeed:3000,
            speed:1500,
            arrows:false,
            centerMode:false,
            dots:false,
            draggable:true,
            pauseOnHover:false,
        });
    }

    if($(".slick321").exists()){
        $('.slick321').slick({
            lazyLoad: 'ondemand',
            infinite: true,
            accessibility:false,
            slidesToShow: 3,
            slidesToScroll: 1,
            autoplay:true,
            autoplaySpeed:3000,
            speed:1000,
            arrows:true,
            centerMode:false,
            dots:false,
            draggable:true,
            pauseOnHover:true,
            responsive: [
            {
              breakpoint: 800,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 490,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
              }
            }
            ]
        });
    }

    if($(".slick4321").exists()){
        $('.slick4321').slick({
            lazyLoad: 'ondemand',
            infinite: true,
            accessibility:false,
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay:true,
            autoplaySpeed:3000,
            speed:1000,
            arrows:true,
            centerMode:false,
            dots:false,
            draggable:true,
            pauseOnHover:true,
            responsive: [
            {
              breakpoint: 960,
              settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 800,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 490,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
              }
            }
            ]
        });
    }

    if($(".slick4322").exists()){
        $('.slick4322').slick({
            lazyLoad: 'ondemand',
            infinite: true,
            accessibility:false,
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay:true,
            autoplaySpeed:3000,
            speed:1000,
            arrows:true,
            centerMode:false,
            dots:false,
            draggable:true,
            pauseOnHover:true,
            responsive: [
            {
              breakpoint: 960,
              settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 800,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 490,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
              }
            }
            ]
        });
    }

    if($(".doitac").exists()){
        $('.doitac').slick({
            lazyLoad: 'ondemand',
            infinite: true,
            accessibility:false,
            slidesToShow: 6,
            slidesToScroll: 1,
            autoplay:true,
            autoplaySpeed:3000,
            speed:1000,
            arrows:true,
            centerMode:false,
            dots:false,
            draggable:true,
            pauseOnHover:true,
            responsive: [
            {
              breakpoint: 1025,
              settings: {
                slidesToShow: 5,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 800,
              settings: {
                slidesToShow: 4,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 461,
              settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
              }
            }
            ]
        });
    }

    if($(".news_right").exists()){
        $('.news_right').slick({
            vertical:true,
            lazyLoad: 'ondemand',
            infinite: true,
            accessibility:false,
            slidesToShow: 3,
            slidesToScroll: 1,
            autoplay:true,
            autoplaySpeed:3000,
            speed:1000,
            arrows:false,
            centerMode:false,
            dots:false,
            draggable:true,
            pauseOnHover:true,
        });
    }

    if($(".camnhan2").exists()){
        $('.camnhan2').slick({
            vertical:true,
            lazyLoad: 'ondemand',
            infinite: true,
            accessibility:false,
            slidesToShow: 2,
            slidesToScroll: 1,
            autoplay:true,
            autoplaySpeed:3000,
            speed:1000,
            arrows:false,
            centerMode:false,
            dots:false,
            draggable:true,
            pauseOnHover:true,
        });
    }

    if($(".album_pro").exists()){
        $('.album_pro').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            fade: true,
            autoplay:false,
            autoplaySpeed:5000,
            asNavFor: '.album_pro2'
        });
        $('.album_pro2').slick({
            slidesToShow: 4,
            slidesToScroll: 1,
            asNavFor: '.album_pro',
            dots: false,
            centerMode: false,
            focusOnSelect: true,
            responsive: [
            {
              breakpoint: 376,
              settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
              }
            }
            ]
        });
    }

};


/* xemnhanh product detail */
NN_FRAMEWORK.xemnhanh_sanpham = function(){
    $("body").on("click",".xemnhanh",function(){       
        var id = $(this).data("id");        

        if(id)
        {             
            $.ajax({
                url:'ajax/ajax_xemnhanh.php',
                type: "POST",
                dataType: 'html',
                async: false,
                data: {id:id},
                success: function(result){
                    $("#popup-pro-detail .modal-body").html(result);
                    $('#popup-pro-detail').modal('show');
                    MagicZoom.refresh("Zoom-1");
                    NN_FRAMEWORK.OwlProDetail();
                }
            });
        }
    });
};

/* Cart */
NN_FRAMEWORK.Cart = function(){
    $("body").on("click",".addcart",function(){
        var mau = ($(".color-pro-detail input:checked").val()) ? $(".color-pro-detail input:checked").val() : 0;
        var size = ($(".size-pro-detail input:checked").val()) ? $(".size-pro-detail input:checked").val() : 0;
        var id = $(this).data("id");
        var action = $(this).data("action");
        var quantity = ($(".qty-pro").val()) ? $(".qty-pro").val() : 1;

        if(id)
        {
            $.ajax({
                url:'ajax/ajax_cart.php',
                type: "POST",
                dataType: 'json',
                async: false,
                data: {cmd:'add-cart',id:id,mau:mau,size:size,quantity:quantity},
                success: function(result){
                    if(action=='addnow')
                    {
                        $('.count-cart').html(result.max);
                        $.ajax({
                            url:'ajax/ajax_cart.php',
                            type: "POST",
                            dataType: 'html',
                            async: false,
                            data: {cmd:'popup-cart'},
                            success: function(result){
                                $("#popup-cart .modal-body").html(result);
                                $('#popup-cart').modal('show');
                            }
                        });
                    }
                    else if(action=='buynow')
                    {
                        window.location = CONFIG_BASE + "gio-hang";
                    }
                }
            });
        }
    });

    $("body").on("click",".del-procart",function(){
        if(confirm(LANG['delete_product_from_cart']))
        {
            var code = $(this).data("code");
            var ship = $(".price-ship").val();

            $.ajax({
                type: "POST",
                url:'ajax/ajax_cart.php',
                dataType: 'json',
                data: {cmd:'delete-cart',code:code,ship:ship},
                success: function(result){
                    $('.count-cart').html(result.max);
                    if(result.max)
                    {
                        $('.price-temp').val(result.temp);
                        $('.load-price-temp').html(result.tempText);
                        $('.price-total').val(result.total);
                        $('.load-price-total').html(result.totalText);
                        $(".procart-"+code).remove();
                    }
                    else
                    {
                        $(".wrap-cart").html('<a href="" class="empty-cart text-decoration-none"><i class="fa fa-cart-arrow-down"></i><p>'+LANG['no_products_in_cart']+'</p><span>'+LANG['back_to_home']+'</span></a>');
                    }
                }
            });
        }
    });

    $("body").on("click",".btn-cart",function(){
        $('.load-cart').addClass('load_active')
    });

    $("body").on("click",".counter-procart",function(){
        var $button = $(this);
        var quantity = 1;
        var input = $button.parent().find("input");
        var id = input.data('pid');
        var code = input.data('code');
        var oldValue = $button.parent().find("input").val();
        if($button.text() == "+") quantity = parseFloat(oldValue) + 1;
        else if(oldValue > 1) quantity = parseFloat(oldValue) - 1;
        $button.parent().find("input").val(quantity);
        update_cart(id,code,quantity);
    });

    $("body").on("change","input.quantity-procat",function(){
        var quantity = $(this).val();
        var id = $(this).data("pid");
        var code = $(this).data("code");
        update_cart(id,code,quantity);
    });

    if($(".select-city-cart").exists())
    {
        $(".select-city-cart").change(function(){
            var id = $(this).val();
            load_district(id);
            load_ship();
        });
    }

    if($(".select-district-cart").exists())
    {
        $(".select-district-cart").change(function(){
            var id = $(this).val();
            load_wards(id);
            load_ship();
        });
    }

    if($(".select-wards-cart").exists())
    {
        $(".select-wards-cart").change(function(){
            var id = $(this).val();
            load_ship(id);
        });
    }

    if($(".payments-label").exists())
    {
        $(".payments-label").click(function(){
            var payments = $(this).data("payments");
            $(".payments-cart .payments-label, .payments-info").removeClass("active");
            $(this).addClass("active");
            $(".payments-info-"+payments).addClass("active");
        });
    }

    if($(".color-pro-detail").exists())
    {
        $(".color-pro-detail").click(function(){
            $(".color-pro-detail").removeClass("active");
            $(this).addClass("active");
            
            var id_mau=$("input[name=color-pro-detail]:checked").val();
            var idpro=$(this).data('idpro');

            $.ajax({
                url:'ajax/ajax_color.php',
                type: "POST",
                dataType: 'html',
                data: {id_mau:id_mau,idpro:idpro},
                success: function(result){
                    if(result!='')
                    {
                        $('.left-pro-detail').html(result);
                        MagicZoom.refresh("Zoom-1");
                        NN_FRAMEWORK.OwlProDetail();
                    }
                }
            });
        });
    }

    if($(".size-pro-detail").exists())
    {
        $(".size-pro-detail").click(function(){
            $(".size-pro-detail").removeClass("active");
            $(this).addClass("active");
        });
    }

    if($(".quantity-pro-detail span").exists())
    {
        $(".quantity-pro-detail span").click(function(){
            var $button = $(this);
            var oldValue = $button.parent().find("input").val();
            if($button.text() == "+")
            {
                var newVal = parseFloat(oldValue) + 1;
            }
            else
            {
                if(oldValue > 1) var newVal = parseFloat(oldValue) - 1;
                else var newVal = 1;
            }
            $button.parent().find("input").val(newVal);
            var gia = parseIntt($('.price-new-pro-detail').data('gia'));
            $.ajax({
                url:'ajax/tinhgia.php',
                type: "POST",
                data: {gia:gia,sl:newVal},
                success: function(result){
                    if(result!=''){
                        $('.price-new-pro-detail').html(result);
                    }
                }
            });


        });
    }
};

/* Ready */
$(document).ready(function(){
    NN_FRAMEWORK.BackToTop();
    NN_FRAMEWORK.AltImages();
    NN_FRAMEWORK.Tools();
    NN_FRAMEWORK.FixMenu();
    NN_FRAMEWORK.mMenu();
    NN_FRAMEWORK.Search();
    //NN_FRAMEWORK.Lazy();
    NN_FRAMEWORK.SlickPage();
    //NN_FRAMEWORK.Popup();
    //NN_FRAMEWORK.Cart();
    //NN_FRAMEWORK.Tabs();
    //NN_FRAMEWORK.Sapxep();
    //NN_FRAMEWORK.chaychu();
    //NN_FRAMEWORK.WowAnimation();
    //NN_FRAMEWORK.Toc();
    //NN_FRAMEWORK.Photobox();
    //NN_FRAMEWORK.DatetimePicker();
    //NN_FRAMEWORK.xemnhanh_sanpham();
    //NN_FRAMEWORK.yeuthich_sanpham();
});