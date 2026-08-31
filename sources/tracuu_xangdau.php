<?php
if(!defined('SOURCES')) die("Error");

/* SEO cơ bản cho trang tra cứu chi phí xăng dầu (dành cho giáo viên) */
$seo->setSeo('h1', 'Tra cứu chi phí xăng dầu');
$seo->setSeo('title', 'Tra cứu chi phí xăng dầu');
$seo->setSeo('url', $func->getPageURL());

/* breadCrumbs */
if(isset($title_crumb) && $title_crumb != '') $breadcr->setBreadCrumbs($com, $title_crumb);
$breadcrumbs = $breadcr->getBreadCrumbs();
