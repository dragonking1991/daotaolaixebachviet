<?php
	if(!defined('LIBRARIES')) die("Error");
	
	/* Root */
	define('ROOT',__DIR__);

	/* Timezone */
	date_default_timezone_set('Asia/Ho_Chi_Minh');

	/* Cấu hình coder */
	define('NN_MSHD','');
	define('NN_AUTHOR','');

	/* Cấu hình chung */
	$config['order']['active'] = false;

	$config = array(
		'author' => array(
			'name' => '',
			'email' => '',
			'timefinish' => ''
		),
		'arrayDomainSSL' => array(),
		'database' => array(
			'server-name' => $_SERVER["SERVER_NAME"],
			'url' => '/',
			'type' => 'mysql',
			'host' => getenv('MYSQLHOST') ?: 'localhost',
			'username' => getenv('MYSQLUSER') ?: 'daotaola6686_db',
			'password' => getenv('MYSQLPASSWORD') ?: 'localpass123',
			'dbname' => getenv('MYSQLDATABASE') ?: 'daotaola6686_db',
			'port' => getenv('MYSQLPORT') ?: 3306,
			'prefix' => 'table_',
			'charset' => 'utf8'
		),
		'website' => array(
			'error-reporting' => false,
			'secret' => '$@tyutgt',
			'salt' => 'swKJjeS!t',
			'pass_admin' => 'admin123',
			'debug-developer' => false,
			'debug-css' => true,
			'debug-js' => true,
			'index' => false,
			'upload' => array(
				'max-width' => 1600,
				'max-height' => 1600
			),
			'lang' => array(
				'vi'=>'Tiếng Việt',
			),
			'lang-doc' => 'vi|en',
			'slug' => array(
				'vi'=>'Tiếng Việt',
				/*'en'=>'Tiếng Anh'*/
			),
			'seo' => array(
				'vi'=>'Tiếng Việt',
				/*'en'=>'Tiếng Anh'*/
			),
			'comlang' => array(
				"gioi-thieu" => array("vi"=>"gioi-thieu","en"=>"about-us"),
				"san-pham" => array("vi"=>"san-pham","en"=>"product"),
				"tin-tuc" => array("vi"=>"tin-tuc","en"=>"news"),
				"tuyen-dung" => array("vi"=>"tuyen-dung","en"=>"recruitment"),
				"thu-vien-anh" => array("vi"=>"thu-vien-anh","en"=>"gallery"),
				"video" => array("vi"=>"video","en"=>"video"),
				"lien-he" => array("vi"=>"lien-he","en"=>"contact")
			)
		),
		'order' => array(
			'ship' => false
		),
		'login' => array(
			'admin' => 'LoginAdmin'.NN_MSHD,
			'member' => 'LoginMember'.NN_MSHD,
			'attempt' => 5,
			'delay' => 15
		),
		'googleAPI' => array(
			'recaptcha' => array(
				'active' => true,
				'urlapi' => 'https://www.google.com/recaptcha/api/siteverify',
				'sitekey' => '6LeI7g0qAAAAANP8__JUQ-FX42Jr2Toqm5vkScAG',
				'secretkey' => '6LeI7g0qAAAAAKpR3t59ZM3ZyttJ2kVrJGO-dSTX'
			)
		),
		'oneSignal' => array(
			'active' => false,
			'id' => 'af12ae0e-cfb7-41d0-91d8-8997fca889f8',
			'restId' => 'MWFmZGVhMzYtY2U0Zi00MjA0LTg0ODEtZWFkZTZlNmM1MDg4'
		),
		'license' => array(
			'version' => "7.0.0",
			'powered' => "@gmail.com"
		),
		'company' => array(
			'design' => "",
			'logo' => "",
		)
	);

	/* Error reporting */
	error_reporting(($config['website']['error-reporting']) ? E_ALL : 0);

	/* Cấu hình base */
	/* Detect reverse proxy (Fly.io, Render, etc.) */
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
		$_SERVER['HTTPS'] = 'on';
	}
	if (!empty($_SERVER['HTTP_FLY_FORWARDED_PROTO']) && $_SERVER['HTTP_FLY_FORWARDED_PROTO'] === 'https') {
		$_SERVER['HTTPS'] = 'on';
	}
	$http = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';

	$server_name = $_SERVER["SERVER_NAME"];
	$external_port = $_SERVER["SERVER_PORT"] ?? '80';
	if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
		$external_port = $_SERVER['HTTP_X_FORWARDED_PORT'];
	} elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
		$external_port = '443';
	}
	if ($external_port != '80' && $external_port != '443') {
		$server_name .= ':'.$external_port;
	}
	$config_url = $server_name.$config['database']['url'];
	$config_base = $http.$config_url;

	/* Cấu hình login */
	$login_admin = $config['login']['admin'];
	$login_member = $config['login']['member'];

	/* Cấu hình upload */
	require_once LIBRARIES."constant.php";
?>