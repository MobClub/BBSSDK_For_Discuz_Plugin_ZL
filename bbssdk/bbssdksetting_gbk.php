<?php
require_once 'vendor/autoload.php';
require_once 'lib/function.php';
$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting','portalstatus'));
//$portalstatus = $setting['portalstatus'];
$setting = (array)unserialize($setting['bbssdk_setting']);
//
//$limit_n = 0;
//if(isset($setting['init_count'])){
//    $time  = $setting['init_count']['time'];
//    $count = $setting['init_count']['count'];
//    if(date('n',$time)==date('n')){
//        $limit_n = 2-$count;
//        $limit_n = $limit_n>0?$limit_n:0;
//    }else{
//        $limit_n = 2;
//    }
//}else{
//    $limit_n = 2;
//}

if(!submitcheck('checksub')){
    showformheader('plugins&operation=config&do='.$pluginid.'&identifier=bbssdk&pmod=bbssdksetting', 'enctype');
    showtableheader('参数信息');
    showsetting('AppKey', 'setting[appkey]', $setting['appkey'], 'text', '', '', '如无AppKey，请至"http://www.mob.com/developer/login"申请Appkey');
    showsetting('AppSecret', 'setting[appsecret]', $setting['appsecret'], 'text', '', '');
//    showsetting('SDK服务器地址', '', '', '<span style="white-space:nowrap">http://data.bbssdk.mob.com/</span>');
    showtablefooter();
    showtableheader('同步设置');
//    echo '<tr class="noborder" onmouseover="setfaq(this, \'faq9ca5\')"><td class="vtop rowform">
//        <ul onmouseover="altStyle(this);">
//        <li><input class="radio" type="radio" name="setting[init]" value="1" '.($setting['init']==1||!isset($setting['init'])?'checked':'').'>&nbsp;论坛</li>
//        <li><input class="radio" type="radio" name="setting[init]" value="2" '.($setting['init']==2?'checked':'').'>&nbsp;论坛+门户</li>
//        </ul></td><td class="vtop tips2" s="1">若勾选【论坛+门户】，请确保门户功能已开启</td>
//        </tr>';
//    $tips = '<li>如非数据异常，无需重新初始化同步，BBSSDK会自动同步帖子和评论</li>';
//    $tips.= '<li>一个月仅可重新初始化同步2次</li>';
//    $tips.= '<li>开启同步后，将同步当前时间每个版块前200条主题以及每个主题前200条评论，放心，BBSSDK已有数据不会被清除</li>';
//    showtips($tips);
    echo '<tr>'
        . '<td colspan="1"><div class="fixsel"><input type="button" class="btn" id="submit_settingsubmit" name="settingsubmit" onclick="document.getElementById(\'cpform\').submit();" title="按 Enter 键可随时提交您的修改" value="初始化同步">'
        . '   <input type="hidden" name="checksub" value="1">'
        . '</tr>';
    showtablefooter();
    showformfooter();
    
}else{
    if(!$_POST['setting']['appkey'] || !$_POST['setting']['appsecret']) cpmsg('您填写的资料不完整,请返回补充完整', "", 'error');
    $appkey = (string) trim($_POST['setting']['appkey']);
    $appsecret = (string) trim($_POST['setting']['appsecret']);
    $mob_setting_url = $_G['siteurl'].'plugin.php?id=bbssdk:api';
    $_POST['setting'] = array_map('trim', $_POST['setting']);
    
    $appInfo = json_decode(utf8_encode(file_get_contents($mob_setting_url."&check=check")),true);
    if(!$appInfo['plugin_info']['bbssdk']['enabled']){
        cpmsg("论坛地址错误，请重新输入".$mob_setting_url, "", 'error');
    }
//    if($portalstatus!=1&&$_POST['setting']['init']==2){
//        cpmsg("门户功能尚未开启", "", 'error');
//    }
    $mob_request_url = "http://admin.mob.com/api/bbs/info/plugin?appkey=$appkey&url=".urlencode($mob_setting_url);//10.18.97.58:8808
    $result = json_decode(utf8_encode(file_get_contents($mob_request_url)),true);
    write_log('query url ==>'.$mob_request_url."\t response ==>".json_encode($result));
    if($result['status'] == 200 || $result['status'] == 502){
//        if(isset($setting['init_count'])){
//            $time  = $setting['init_count']['time'];
//            $count = $setting['init_count']['count'];
//            if(date('n',$time)==date('n')){
//                $setting['init_count'] = array('time'=>time(),'count'=>$count+1);
//            }else{
//                $setting['init_count'] = array('time'=>time(),'count'=>1);
//            }
//        }else{
//            $setting['init_count'] = array('time'=>time(),'count'=>1);
//        }
        
        C::t('common_setting')->update_batch(array('bbssdk_setting'=>($_POST['setting']+$setting)));
        cpmsg('setting_update_succeed', 'action=plugins&operation=config&do='.$pluginid.'&identifier=bbssdk&pmod=bbssdksetting', 'succeed');
    }else{
        $msg = $result['status'] == 503 ? '此Appkey已绑定其他论坛地址，如下：' : '抱歉,您的BBSSDK在mob平台注册过程发生了一些问题,具体错误如下：' ;
        cpmsg_error($msg, '',diconv($result['msg'], 'UTF-8', CHARSET));
    }
}