<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if(!defined('DISABLEDEFENSE')){
        define('DISABLEDEFENSE', 1);
}

$downloadurl = '';

$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
$setting = (array)unserialize($setting['bbssdk_setting']);

global $_G;
$appkey = $_G['cache']['plugin']['bbssdk']['appkey'];
if($setting['qrcode']){
    $downloadurl = $setting['qrcode'];
}else{
    $url = "http://admin.mob.com/api/bbs/pkg/url/".$appkey;
    $result = json_decode(utf8_encode(file_get_contents($url)),true);

    if($result&&isset($result['info'])&&isset($result['info']['downloadUrl'])&&$result['info']['downloadUrl']){
        $downloadurl = $result['info']['downloadUrl'];
    }
}

if($downloadurl){
    include('lib/phpqrcode/qrlib.php');      
    QRcode::png($downloadurl,false,QR_ECLEVEL_Q,3,0);
}
