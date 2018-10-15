<?php
set_time_limit(0);
try{
	require dirname(dirname(__FILE__)).'/vendor/autoload.php';
	require dirname(dirname(__FILE__)).'/lib/function.php';
//	loadcache('plugin');
	$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
        $setting = (array)unserialize($setting['bbssdk_setting']);
//	if(empty($_G['cache']['plugin']['bbssdk']['notify_api'])) throw new Exception("Notify_api is not exists!", 1);
//	if(empty($_G['cache']['plugin']['bbssdk']['appkey'])) throw new Exception("Appkey is Not Exists!", 1);
        if(empty($setting['appkey'])) throw new Exception("Appkey is Not Exists!", 1);
	require 'cron_class.php';

	$secend = intval(date('i'));
	$cron = new Cron();
	
	$cron->forum_sync();
	$cron->comment_sync();
	$cron->member_sync();
	$cron->usergroup_sync();
	
	if(!($secend % 5)){
		$cron->menu_sync();
	}
	return true;
}catch(\Exception $e){
	write_log('Cron Error:'.$e);
	return false;
}
