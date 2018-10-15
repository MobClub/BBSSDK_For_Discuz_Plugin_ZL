<?php
set_time_limit(0);
define('DISABLEXSSCHECK', true);
define('IN_MOBILE_API', 0);
define('IN_MOBILE', 0);
define('APPTYPEID', 700);
define('CURSCRIPT', 'bbssdkapi');
define('BBSSDK_DEBUG', false);
define('SUB_DIR', 'api/mobile/');
try {
    if(!defined('DISABLEDEFENSE')){
            define('DISABLEDEFENSE', 1);
    }
    chdir(dirname(__FILE__));
    
    require 'vendor/autoload.php';
    $coreFile = dirname(dirname(dirname(dirname(__FILE__)))).'/source/class/class_core.php';
    if(file_exists($coreFile)){
        require_once $coreFile;
        $b = C::app();
        $b->init();
        require_once libfile('function/core');
        loadcache('plugin');
        require_once 'lib/basecore.php';
        header('BBSSDK-Version:'.$_G['setting']['plugins']["version"]["bbssdk"]);
//         set_error_handler('handleError');
//         register_shutdown_function('handleError');
    }else{
        throw new Exception("Class Core Not Find");
    }
    
    $c = ucfirst($_REQUEST['mod']);
    $d = $_REQUEST['action'];
    $f = strtolower($_SERVER['REQUEST_METHOD']);
    $g = $f . "_" . $d;
    $h = 'controller/' . strtolower($c) . '_ctrl.php';
//    if (file_exists($h)) {
        require_once $h;
        if (class_exists($c)) {
            $i = new $c();
            if (method_exists($i, $g)){
            	$i->$g();
            }
        }
//    }
    return_status(404);
}
catch(\Exception $e) {
    write_log($e);
    return_status(500);
}
header("HTTP/1.1 404 Not Found");

function handleError($errno, $errstr, $errfile, $errline)
{
    var_dump($errno, $errstr, $errfile, $errline);
    exit;
}
function handleShutdown()
{
    $error = error_get_last();
    var_dump($error);exit;    
}