<?php
$_GET['charset'] = 'UTF-8';
$oldRL = error_reporting();
if(defined(E_DEPRECATED)){
	error_reporting($oldRL & ~E_DEPRECATED & ~E_STRICT);
}else{
	error_reporting($oldRL & ~E_STRICT);
}
if(!empty($_SERVER['QUERY_STRING'])) {
        if(!defined('DISABLEDEFENSE')){
		define('DISABLEDEFENSE', 1);
	}
	$file = 'bbssdk.php';
	if((isset($_GET['check']) && $_GET['check'] == 'check')) {
		$file = 'check.php';
	}
        require_once $file;
        die(0);
}
header('HTTP/1.1 404 Not Found');