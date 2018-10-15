<?php
$_GET['charset'] = 'UTF-8';
$oldRL = error_reporting();
if(defined(E_DEPRECATED)){
	error_reporting($oldRL & ~E_DEPRECATED & ~E_STRICT);
}else{
	error_reporting($oldRL & ~E_STRICT);
}
if(!empty($_SERVER['QUERY_STRING'])) {
	$plugin = 'bbssdk';
	$file = 'bbssdk.php';
	$dir = '../../source/plugin/'.$plugin.'/';
	if(!is_dir($dir)){
		echo "such directory does not exists [ $dir ].";
		die(0);
	}
	if(!defined('DISABLEDEFENSE')){
		define('DISABLEDEFENSE', 1);
	}
	chdir($dir);
	if((isset($_GET['check']) && $_GET['check'] == 'check') && is_file('check.php')) {
		$file = 'check.php';
	}
	if(is_file($file)){
		require_once $file;
		die(0);
	}
	echo "such file does not exists [ ${dir}${$file} ].";
}
header('HTTP/1.1 404 Not Found');