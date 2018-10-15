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
    showtableheader('������Ϣ');
    showsetting('AppKey', 'setting[appkey]', $setting['appkey'], 'text', '', '', '����AppKey������"http://www.mob.com/developer/login"����Appkey');
    showsetting('AppSecret', 'setting[appsecret]', $setting['appsecret'], 'text', '', '');
//    showsetting('SDK��������ַ', '', '', '<span style="white-space:nowrap">http://data.bbssdk.mob.com/</span>');
    showtablefooter();
    showtableheader('ͬ������');
//    echo '<tr class="noborder" onmouseover="setfaq(this, \'faq9ca5\')"><td class="vtop rowform">
//        <ul onmouseover="altStyle(this);">
//        <li><input class="radio" type="radio" name="setting[init]" value="1" '.($setting['init']==1||!isset($setting['init'])?'checked':'').'>&nbsp;��̳</li>
//        <li><input class="radio" type="radio" name="setting[init]" value="2" '.($setting['init']==2?'checked':'').'>&nbsp;��̳+�Ż�</li>
//        </ul></td><td class="vtop tips2" s="1">����ѡ����̳+�Ż�������ȷ���Ż������ѿ���</td>
//        </tr>';
//    $tips = '<li>��������쳣���������³�ʼ��ͬ����BBSSDK���Զ�ͬ�����Ӻ�����</li>';
//    $tips.= '<li>һ���½������³�ʼ��ͬ��2��</li>';
//    $tips.= '<li>����ͬ���󣬽�ͬ����ǰʱ��ÿ�����ǰ200�������Լ�ÿ������ǰ200�����ۣ����ģ�BBSSDK�������ݲ��ᱻ���</li>';
//    showtips($tips);
    echo '<tr>'
        . '<td colspan="1"><div class="fixsel"><input type="button" class="btn" id="submit_settingsubmit" name="settingsubmit" onclick="document.getElementById(\'cpform\').submit();" title="�� Enter ������ʱ�ύ�����޸�" value="��ʼ��ͬ��">'
        . '   <input type="hidden" name="checksub" value="1">'
        . '</tr>';
    showtablefooter();
    showformfooter();
    
}else{
    if(!$_POST['setting']['appkey'] || !$_POST['setting']['appsecret']) cpmsg('����д�����ϲ�����,�뷵�ز�������', "", 'error');
    $appkey = (string) trim($_POST['setting']['appkey']);
    $appsecret = (string) trim($_POST['setting']['appsecret']);
    $mob_setting_url = $_G['siteurl'].'plugin.php?id=bbssdk:api';
    $_POST['setting'] = array_map('trim', $_POST['setting']);
    
    $appInfo = json_decode(utf8_encode(file_get_contents($mob_setting_url."&check=check")),true);
    if(!$appInfo['plugin_info']['bbssdk']['enabled']){
        cpmsg("��̳��ַ��������������".$mob_setting_url, "", 'error');
    }
//    if($portalstatus!=1&&$_POST['setting']['init']==2){
//        cpmsg("�Ż�������δ����", "", 'error');
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
        $msg = $result['status'] == 503 ? '��Appkey�Ѱ�������̳��ַ�����£�' : '��Ǹ,����BBSSDK��mobƽ̨ע����̷�����һЩ����,����������£�' ;
        cpmsg_error($msg, '',diconv($result['msg'], 'UTF-8', CHARSET));
    }
}