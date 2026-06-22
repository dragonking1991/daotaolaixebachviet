<?php
if(!defined('SOURCES')) die("Error");

/* SEO cơ bản cho trang đăng ký cabin */
$seo->setSeo('h1', 'Đăng ký ngày học cabin');
$seo->setSeo('title', 'Đăng ký ngày học cabin');
$seo->setSeo('url', $func->getPageURL());

/* Lấy danh sách khóa cabin đang hiển thị */
$cabin_courses = $d->rawQuery(
    "select id, ten, ngay_batdau, ngay_ketthuc, suc_chua_ca, han_dangky from #_cabin_khoahoc where hienthi = 1 order by ngay_batdau desc, id desc"
);

/* breadCrumbs */
if(isset($title_crumb) && $title_crumb != '') $breadcr->setBreadCrumbs($com, $title_crumb);
$breadcrumbs = $breadcr->getBreadCrumbs();
