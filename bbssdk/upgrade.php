<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
@include_once libfile('cache/setting', 'function');
build_cache_setting();
upgrade();
loadcache('plugin');
global $_G;
//todo  删除以前 的delete_by_variable 设置选项；存储appkey 及secret
$appkey = $_G['cache']['plugin']['bbssdk']['appkey'];
$appsecret = $_G['cache']['plugin']['bbssdk']['appsecret'];

//存储到新的表中
if($appkey&&$appsecret){
    $setting = C::t('common_setting')->fetch_all(array('bbssdk_setting','portalstatus'));
    $portalstatus = $setting['portalstatus'];
    $setting = (array)unserialize($setting['bbssdk_setting']);
    $setting['appkey']    = $appkey;
    $setting['appsecret'] = $appsecret;
    C::t('common_setting')->update_batch(array('bbssdk_setting'=>$setting));
}

$plugin = C::t('common_plugin')->fetch_by_identifier('bbssdk');//删除老的数据
$pluginid = $plugin['pluginid'];
C::t('common_pluginvar')->delete_by_variable($pluginid, array('appkey','appsecret','notify_api'));//删除老的数据
updatecache(array('plugin', 'setting', 'styles'));
if(!$appkey || !$appsecret){
    dheader('location: '.$_SERVER['PHP_SELF']."?action=plugins&operation=config&do=".$pluginid."&identifier=bbssdk&pmod=bbssdksetting");
}

$mob_setting_url = $_G['siteurl'].'api/mobile/remote.php';

$appInfo = json_decode(utf8_encode(file_get_contents($mob_setting_url."?check=check")),true);

if(!$appInfo['plugin_info']['bbssdk']['enabled']){
        cpmsg($installlang['discuzurl_error'], "", 'error');
}

$mob_request_url = "http://admin.mob.com/api/bbs/info?appkey=$appkey&url=".urlencode($mob_setting_url);

$result = json_decode(utf8_encode(file_get_contents($mob_request_url)),true);

//write_log('upgrade query url ==>'.$mob_request_url."\t response ==>".json_encode($result));

if($result['status'] == 200 || $result['status'] == 502){
//        C::t('common_pluginvar')->update_by_variable($pluginid, 'appkey', array('value' => $appkey));
//        C::t('common_pluginvar')->update_by_variable($pluginid, 'appsecret', array('value' => $appsecret));
//        updatecache(array('plugin', 'setting', 'styles'));
//        cleartemplatecache();
        $finish = TRUE;
}else{
        $msg = $result['status'] == 503 ? $installlang['address_msg'] : $installlang['errmsg'] ;
        cpmsg_error($msg, '', diconv($result['msg'], 'UTF-8', CHARSET));
}

function upgrade(){
}