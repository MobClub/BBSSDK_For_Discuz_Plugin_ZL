<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
require_once 'vendor/autoload.php';
require_once 'lib/function.php';
$request_url = str_replace('&step='.$_GET['step'],'',$_SERVER['QUERY_STRING']);
$form_url = str_replace('action=','',$request_url);
showsubmenusteps($installlang['title'], array(
	array($installlang['check'], $_GET['step']==''),
	array($installlang['install'], $_GET['step']=='install'),
	array($installlang['succeed'], $_GET['step']=='ok')
));

$pluginName = 'bbssdk';
$final = false;
$delPlugin = $_SERVER['PHP_SELF'].'?action=plugins&operation=delete&pluginid='.$_GET['pluginid'];
switch($_GET['step']){
	default:
		require_once 'check.php';
		$srcFile = dirname(__FILE__) . '/files/remote.php';
		$final = getCheckJson();
		if(floatval($final['phpversion']) < 5.3){
			cpmsg($installlang['phpversion_msg'], $delPlugin, 'error');
		}
		if(floatval($final['mysqlversion']) < 5){
			cpmsg($installlang['mysql_msg'],$delPlugin,'error');
		}
		if(!$final['mysqlgrants']){
			cpmsg($installlang['dbuser_msg'],$delPlugin,'error');
		}
		install_action();
		C::t('common_plugin')->update($_GET['pluginid'], array('available' => '1'));
		updatecache(array('plugin', 'setting', 'styles'));
                dheader('location: '.$_SERVER['PHP_SELF']."?{$request_url}&step=install&modetype=1");
	case 'install':
		if(extension_loaded('curl')){
                    if($_GET['modetype'] == '1'){
                            dheader('location: '.$_SERVER['PHP_SELF']."?action=plugins&operation=config&do=".$plugin['pluginid']."&identifier=bbssdk&pmod=bbssdksetting");
                            $finish = TRUE;
                    }
		}else{
                    cpmsg($installlang['curl_unsupported'], $delPlugin, 'error');
		}
		break;
	case 'ok':
		$finish = TRUE;
		break;
}

function install_action()
{
	@include_once libfile('cache/setting', 'function');
	build_cache_setting();
        $sql = "CREATE TABLE IF NOT EXISTS `".DB::table('bbssdk_oauth')."` (
          `id` INT NOT NULL AUTO_INCREMENT , 
	  `uid` INT NULL DEFAULT NULL , 
          `wxOpenid` varchar(100) DEFAULT NULL,
          `wxUnionid` varchar(100) DEFAULT NULL,
          `qqOpenid` varchar(100) DEFAULT NULL,
          `qqUnionid` varchar(100) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE `uid` (`uid`), 
          index `wxOpenid` (`wxOpenid`), 
          index `wxUnionid` (`wxUnionid`), 
          index `qqOpenid` (`qqOpenid`), 
          index `qqUnionid` (`qqUnionid`)
          ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

	DB::query($sql);
//	for($i=0; $i < 60; $i++){
//		$times = array();
//		for($j=0;$j<12 && $i+$j<60;$j++){
//			array_push($times , intval($i+$j));
//		}
//		$i = $i+$j-1;
//		$sql = "INSERT INTO ".DB::table('common_cron')."(available,type,`name`,filename,weekday,`day`,`hour`,`minute`) value(1,'plugin','每日BBSSDK同步','bbssdk:cron_sync.php',-1,-1,-1,'".implode("\t",$times)."')";
//		DB::query($sql);
//	}

	return true;
}

function file_mode_info($file_path)
{
    /* 如果不存在，则不可读、不可写、不可改 */
    if (!file_exists($file_path))
    {
        return false;
    } 
    $mark = 0;
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
    {
        /* 测试文件 */
        $test_file = $file_path . '/cf_test.txt';
        /* 如果是目录 */
        if (is_dir($file_path))
        {
            /* 检查目录是否可读 */
            $dir = @opendir($file_path);
            if ($dir === false)
            {
                return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
            }
            if (@readdir($dir) !== false)
            {
                $mark ^= 1; //目录可读 001，目录不可读 000
            }
            @closedir($dir);
 
            /* 检查目录是否可写 */
            $fp = @fopen($test_file, 'wb');
            if ($fp === false)
            {
                return $mark; //如果目录中的文件创建失败，返回不可写。
            }
            if (@fwrite($fp, 'directory access testing.') !== false)
            {
                $mark ^= 2; //目录可写可读011，目录可写不可读 010
            }
            @fclose($fp);
 
            @unlink($test_file);
 
            /* 检查目录是否可修改 */
            $fp = @fopen($test_file, 'ab+');
            if ($fp === false)
            {
                return $mark;
            }
            if (@fwrite($fp, "modify test.rn") !== false){
                $mark ^= 4;
            }
            @fclose($fp);
            if (@rename($test_file, $test_file) !== false){
                $mark ^= 8;
            }
            @unlink($test_file);
        }elseif (is_file($file_path)){
            $fp = @fopen($file_path, 'rb');
            if ($fp){
                $mark ^= 1; //可读 001
            }
            @fclose($fp);
            $fp = @fopen($file_path, 'ab+');
            if ($fp && @fwrite($fp, '') !== false){
                $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
            }
            @fclose($fp);
            if (@rename($test_file, $test_file) !== false){
                $mark ^= 8;
            }
        }
    }else{
        if (@is_readable($file_path)){
            $mark ^= 1;
        }
        if (@is_writable($file_path)){
            $mark ^= 14;
        }
    }
    return $mark;
}
