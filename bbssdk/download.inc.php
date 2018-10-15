<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if(!defined('DISABLEDEFENSE')){
        define('DISABLEDEFENSE', 1);
}
$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
$setting = (array)unserialize($setting['bbssdk_setting']);

$setting['preview'] = $setting['preview']?$setting['preview']:'./source/plugin/bbssdk/template/assets/'.$setting['template'].'-nopre.png';
$setting['qrcode']  = $setting['qrcode']?$setting['qrcode']:'plugin.php?id=bbssdk:qrcode';
if(!$setting['android_addr']){
    global $_G;
    $appkey = $_G['cache']['plugin']['bbssdk']['appkey'];
    
    $url = "http://admin.mob.com/api/bbs/pkg/url/".$appkey;
    $result = json_decode(utf8_encode(file_get_contents($url)),true);

    if($result&&isset($result['info'])&&isset($result['info']['downloadUrl'])&&$result['info']['downloadUrl']){
        $setting['android_addr'] = $result['info']['downloadUrl'];
    }
}

$setting['template'] = $setting['template']?$setting['template']:1;
if($_G['charset']=='gbk'){
    require_once "template/{$setting['template']}_gbk.html"; 
}else{
    require_once "template/{$setting['template']}.html"; 
}