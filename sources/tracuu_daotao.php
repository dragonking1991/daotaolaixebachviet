<?php
if(!defined('SOURCES')) die("Error");

$seo->setSeo('h1', 'Tra cứu quá trình học');
$seo->setSeo('title', 'Tra cứu quá trình học lái xe');
$seo->setSeo('url', $func->getPageURL());

if(isset($title_crumb) && $title_crumb != '') $breadcr->setBreadCrumbs($com, $title_crumb);
$breadcrumbs = $breadcr->getBreadCrumbs();
