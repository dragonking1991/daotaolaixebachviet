<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'lythuyet_online_lib.php';
$onlineTheoryUrl = dt_online_theory_url();
$seo->setSeo('h1', 'Lý thuyết online');
$seo->setSeo('title', 'Lý thuyết online - Bách Việt');
$seo->setSeo('url', $func->getPageURL());