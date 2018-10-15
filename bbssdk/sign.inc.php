<?php
//ini_set("display_errors","On");
//error_reporting(E_ALL);
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if(!defined('DISABLEDEFENSE')){
        define('DISABLEDEFENSE', 1);
}
require_once 'lib/function.php';
global $_G;

$p['uid']  = $_REQUEST['uid'];
$p['type'] = $_REQUEST['type'];
$p['time'] = $_REQUEST['time'];
$p['nonce'] = $_REQUEST['nonce'];

$bbssdksetting = C::t('common_setting')->fetch_all(array('bbssdk_setting','portalstatus'));
$bbssdk = (array)unserialize($bbssdksetting['bbssdk_setting']);
$sign = sign($bbssdk['appkey'], $bbssdk['appsecret'], $p);

if(!preg_match('%[a-zA-Z\d]{8,}%is', $p['nonce'])){
    die('参数错误');
}
if(time()-$p['time']>3600){
    die('请求过期');
}
if($sign !=$_GET['sign'] ){
    die('签名错误');
}

$_G['uid'] = $p['uid'];
$member = getuserbyuid($_G['uid'], 1);
$_G['groupid'] = $member['groupid'];

if(!empty($_POST)){//ajax
    if(isset($_POST['qiandao'])){
        if(is_file(DISCUZ_ROOT.'./source/plugin/k_misign/module/module_qiandao.php')){
            qiandao1();
        }else{
            qiandao2();
        }
        exit;
    }
    $list = getList(intval($_POST['page']));
    $html = '';
    if(!empty($list)){
        foreach ($list as $l){
            $html .= '<div class="list">
                        <a href="javascript:;" class="avatar">'.$l['avatar'].'</a>
                        <div class="name">'.$l['username'].'</div>
                        <div class="time">'.$l['signtime'].' 签到</div>
                </div>';
        }
    }
    if($_G['charset']=='gbk'){
        if(function_exists('iconv')){
            $html = iconv('UTF-8','gbk//ignore', $html);
        }else{
            $html = mb_convert_encoding($html, 'gbk', 'UTF-8');
        }
    }
    echo $html;exit;
}else{//html
    if(isset($_G['cache']['plugin']['k_misign'])){
        $installed = true;
        $tdtime = gmmktime(0,0,0,dgmdate($_G['timestamp'], 'n',$setting['tos']),dgmdate($_G['timestamp'], 'j',$setting['tos']),dgmdate($_G['timestamp'], 'Y',$setting['tos'])) - $setting['tos']*3600;
//        $stats = C::t("#k_misign#plugin_k_misignset")->fetch(1);
//        $qiandaodb = C::t("#k_misign#plugin_k_misign")->fetch_by_uid($_G['uid']);
        $qiandaodb = DB::fetch_first("SELECT * FROM ".DB::table('plugin_k_misign')." WHERE uid='$_G[uid]'");
        $stats = DB::fetch_first("SELECT * FROM ".DB::table('plugin_k_misignset')." WHERE id='1'");
    }else{
        $installed = false;
        $msg = '本功能需要安装小米签到插件！';
        if($_G['charset']=='gbk'){
            if(function_exists('iconv')){
                $msg = iconv('UTF-8','gbk//ignore', $msg);
            }else{
                $msg = mb_convert_encoding($msg, 'gbk', 'UTF-8');
            }
        }
        die($msg);
    }
    
    if($_G['charset']=='gbk'){
        require_once 'h5/sign/html/sign_gbk_'.$p['type'].'.html';
    }else{
        require_once 'h5/sign/html/sign_'.$p['type'].'.html'; 
    }
}

/**
 * 签名
 * @param $appKey
 * @param $appSecret
 * @param $params
 * @return string
 */
function sign($appKey,$appSecret, $params){
    ksort($params);
    foreach($params as $k => $v){
        $str .= $v . '%' . $k;
    }
    return md5(sha1($appKey . $str . $appSecret));
}
function qiandao1(){
    global $_G;
    $_GET['formhash'] = FORMHASH;
    $setting = $_G['cache']['plugin']['k_misign'];
    $setting['pluginurl'] = $setting['pluginurl'] ? $setting['pluginurl'] : 'plugin.php?id=k_misign:';
    require_once libfile('function/core', 'plugin/k_misign');
    $operation = $_GET['operation'];
    $inwsq = intval($_GET['wsq']);

    $tdtime = gmmktime(0,0,0,dgmdate($_G['timestamp'], 'n',$setting['tos']),dgmdate($_G['timestamp'], 'j',$setting['tos']),dgmdate($_G['timestamp'], 'Y',$setting['tos'])) - $setting['tos']*3600;
    $htime = dgmdate($_G['timestamp'], 'H',$setting['tos']);

    $nlvtext = str_replace(array("\r\n", "\n", "\r"), '/hhf/', $setting['lvtext']);
    $njlmain =str_replace(array("\r\n", "\n", "\r"), '/hhf/', $setting['jlmain']);
    list($lv1name, $lv2name, $lv3name, $lv4name, $lv5name, $lv6name, $lv7name, $lv8name, $lv9name, $lv10name, $lvmastername) = explode("/hhf/", $nlvtext);
    $extreward = explode("/hhf/", $njlmain);
    $extreward_num = count($extreward);
    $setting['groups'] = unserialize($setting['groups']);
    $setting['ban'] = explode(",",$setting['ban']);

//    $qiandaodb = C::t("#k_misign#plugin_k_misign")->fetch_by_uid($_G['uid']);
//    $num = C::t("#k_misign#plugin_k_misign")->count_by_time($tdtime);
//    $stats = C::t("#k_misign#plugin_k_misignset")->fetch(1);

    $qiandaodb = DB::fetch_first("SELECT * FROM ".DB::table('plugin_k_misign')." WHERE uid='$_G[uid]'");
    $num = DB::result_first("SELECT COUNT(*) FROM ".DB::table('plugin_k_misign')." WHERE time >= {$tdtime} ");
    $stats = DB::fetch_first("SELECT * FROM ".DB::table('plugin_k_misignset')." WHERE id='1'");
    
    $lastmonth = dgmdate(C::t("#k_misign#plugin_k_misign")->getlasttime(), 'm', $setting['tos']);
    $nowmonth = dgmdate($_G['timestamp'], 'm', $setting['tos']);
    if($nowmonth != $lastmonth)C::t("#k_misign#plugin_k_misign")->clearmdays();
    require_once DISCUZ_ROOT.'./source/plugin/k_misign/module/module_qiandao.php';
}
function qiandao2(){
    global $_G;
    $setting = $_G['cache']['plugin']['k_misign'];

    $tdtime = gmmktime(0,0,0,dgmdate($_G['timestamp'], 'n',$setting['tos']),dgmdate($_G['timestamp'], 'j',$setting['tos']),dgmdate($_G['timestamp'], 'Y',$setting['tos'])) - $setting['tos']*3600;
    $htime = dgmdate($_G['timestamp'], 'H',$setting['tos']);

    $nlvtext = str_replace(array("\r\n", "\n", "\r"), '/hhf/', $setting['lvtext']);
    $njlmain =str_replace(array("\r\n", "\n", "\r"), '/hhf/', $setting['jlmain']);
    list($lv1name, $lv2name, $lv3name, $lv4name, $lv5name, $lv6name, $lv7name, $lv8name, $lv9name, $lv10name, $lvmastername) = explode("/hhf/", $nlvtext);
    $extreward = explode("/hhf/", $njlmain);
    $extreward_num = count($extreward);
    $setting['groups'] = unserialize($setting['groups']);
    $setting['ban'] = explode(",",$setting['ban']);
    $credit = mt_rand($setting['mincredit'],$setting['maxcredit']);
    $qiandaodb = DB::fetch_first("SELECT * FROM ".DB::table('plugin_k_misign')." WHERE uid='$_G[uid]'");
    $num = DB::result_first("SELECT COUNT(*) FROM ".DB::table('plugin_k_misign')." WHERE time >= {$tdtime} ");
    if ($qiandaodb['days'] >= '1500') {
            $qiandaodb['level'] = 99;
    } elseif ($qiandaodb['days'] >= '750') {
            $qiandaodb['level'] = 10;
    } elseif ($qiandaodb['days'] >= '365') {
            $qiandaodb['level'] = 9;
    } elseif ($qiandaodb['days'] >= '240') {
            $qiandaodb['level'] = 8;
    } elseif ($qiandaodb['days'] >= '120') {
            $qiandaodb['level'] = 7;
    } elseif ($qiandaodb['days'] >= '60') {
            $qiandaodb['level'] = 6;
    } elseif ($qiandaodb['days'] >= '30') {
            $qiandaodb['level'] = 5;
    } elseif ($qiandaodb['days'] >= '15') {
            $qiandaodb['level'] = 4;
    } elseif ($qiandaodb['days'] >= '7') {
            $qiandaodb['level'] = 3;
    } elseif ($qiandaodb['days'] >= '3') {
            $qiandaodb['level'] = 2;
    } elseif ($qiandaodb['days'] >= '1') {
            $qiandaodb['level'] = 1;
    }
    $stats = DB::fetch_first("SELECT * FROM ".DB::table('plugin_k_misignset')." WHERE id='1'");
    $qddb = DB::fetch_first("SELECT time FROM ".DB::table('plugin_k_misign')." ORDER BY time DESC limit 0,1");
    $lastmonth = dgmdate($qddb['time'], 'm',$setting['tos']);
    $nowmonth = dgmdate($_G['timestamp'], 'm',$setting['tos']);
    if($nowmonth != $lastmonth){
            DB::query("UPDATE ".DB::table('plugin_k_misign')." SET mdays=0 WHERE uid");
    }
    $todaystar['uid'] = DB::result_first("SELECT uid FROM ".DB::table('plugin_k_misign')." WHERE time >= {$tdtime} ORDER BY time ASC");
    $todaystar = getuserbyuid($todaystar['uid']);

    
    if(!in_array($_G['groupid'], $setting['groups'])){
            include template('common/header_ajax');
            echo lang('plugin/k_misign', 'groupontallow');
            include template('common/footer_ajax');
            exit();
    }
    if(in_array($_G['uid'],$setting['ban'])){
            include template('common/header_ajax');
            echo lang('plugin/k_misign', 'uidinblack');
            include template('common/footer_ajax');
            exit();
    }
    if($qiandaodb['time']>$tdtime){
            include template('common/header_ajax');
            echo lang('plugin/k_misign', 'tdyq');
            include template('common/footer_ajax');
            exit();
    }
    if($setting['lockopen']){
            while(discuz_process::islocked('k_misign', 5)){
                    usleep(100000);
            }
    }
    if(!$qiandaodb['uid']) {
            DB::query("INSERT INTO ".DB::table('plugin_k_misign')." (uid,time) VALUES ('$_G[uid]',$_G[timestamp])");
    }
    $row = $num+1;
    if(($tdtime - $qiandaodb['time']) < 86400){
            DB::query("UPDATE ".DB::table('plugin_k_misign')." SET days=days+1,mdays=mdays+1,time='$_G[timestamp]',reward=reward+{$credit},lastreward='$credit',lasted=lasted+1, row='$row' WHERE uid='$_G[uid]'");
    } else {
            DB::query("UPDATE ".DB::table('plugin_k_misign')." SET days=days+1,mdays=mdays+1,time='$_G[timestamp]',reward=reward+{$credit},lastreward='$credit',lasted='1', row='$row' WHERE uid='$_G[uid]'");
    }
    updatemembercount($_G['uid'], array($setting['nrcredit'] => $credit));
    if($num <= ($extreward_num - 1) ) {
            list($exacr,$exacz) = explode("|", $extreward[$num]);
            $psc = $num+1;
            if($exacr && $exacz) updatemembercount($_G['uid'], array($exacr => $exacz));
    }
    if(memory('check')) memory('set', 'k_misign_'.$_G['uid'], $_G['timestamp'], 86400);
    if($num == 0) {
            if($stats['todayq'] > $stats['highestq']) DB::query("UPDATE ".DB::table('plugin_k_misignset')." SET highestq='$stats[todayq]' WHERE id='1'");
            DB::query("UPDATE ".DB::table('plugin_k_misignset')." SET yesterdayq='$stats[todayq]',todayq=1 WHERE id='1'");
    } else {
            DB::query("UPDATE ".DB::table('plugin_k_misignset')." SET todayq=todayq+1 WHERE id='1'");
    }
    if($setting['lockopen']) discuz_process::unlock('k_misign');
    $qiandaodb = DB::fetch_first("SELECT * FROM ".DB::table('plugin_k_misign')." WHERE uid='$_G[uid]'");
    if($_GET['from'] == 'wsq'){
            include template('k_misign:sign');
            exit();
    }else{
            include template('common/header_ajax');
            if($_GET['from'] == 'insign'){
                    echo '';
            }else{
                    echo "<div class=\"font\">".lang('plugin/k_misign','signed')."</div><span class=\"nums\">".lang('plugin/k_misign','row').$qiandaodb['lasted'].lang('plugin/k_misign','days')."</span><div class=\"fblock\"><div class=\"all\">".$stats['todayq'].lang('plugin/k_misign','people')."</div><div class=\"line\">".$qiandaodb['row']."</div></div>";
            }
            include template('common/footer_ajax');
    }
}
function getList($page = 1,$perpage = 10){
    global $_G;
    $page = $page<=1 ? 1 : $page;
    $start_limit = ($page-1)*$perpage;
    $list_type = 'q.time';
    $list_turn = 'DESC';
    $setting = $_G['cache']['plugin']['k_misign'];
    $list_tdtime = gmmktime(0,0,0,dgmdate($_G['timestamp'], 'n',$setting['tos']),dgmdate($_G['timestamp'], 'j',$setting['tos']),dgmdate($_G['timestamp'], 'Y',$setting['tos'])) - $setting['tos']*3600;
    $mrcs = array();
    foreach(getsignlist($list_type, $list_turn, $start_limit, $perpage, $list_tdtime) as $mrc) {
    //	if(defined('IN_MOBILE')){
    //		$mrc['time'] = dgmdate($mrc['time'], 'm-d H:i');
    //	}else{
    //		$mrc['time'] = dgmdate($mrc['time'], 'Y-m-d H:i');
    //	}
    //	if ($mrc['days'] >= '1500') {
    //		$mrc['level'] = "[LV.Master]{$lvmastername}";
    //	} elseif ($mrc['days'] >= '750') {
    //		$mrc['level'] = "[LV.10]{$lv10name}";
    //	} elseif ($mrc['days'] >= '365') {
    //		$mrc['level'] = "[LV.9]{$lv9name}";
    //	} elseif ($mrc['days'] >= '240') {
    //		$mrc['level'] = "[LV.8]{$lv8name}";
    //	} elseif ($mrc['days'] >= '120') {
    //		$mrc['level'] = "[LV.7]{$lv7name}";
    //	} elseif ($mrc['days'] >= '60') {
    //		$mrc['level'] = "[LV.6]{$lv6name}";
    //	} elseif ($mrc['days'] >= '30') {
    //		$mrc['level'] = "[LV.5]{$lv5name}";
    //	} elseif ($mrc['days'] >= '15') {
    //		$mrc['level'] = "[LV.4]{$lv4name}";
    //	} elseif ($mrc['days'] >= '7') {
    //		$mrc['level'] = "[LV.3]{$lv3name}";
    //	} elseif ($mrc['days'] >= '3') {
    //		$mrc['level'] = "[LV.2]{$lv2name}";
    //	} elseif ($mrc['days'] >= '1') {
    //		$mrc['level'] = "[LV.1]{$lv1name}";
    //	}
        $mrc['signtime'] = date('H:i',$mrc['time']);
        $mrc['avatar'] = avatar($mrc['uid']);
        $mrcs[] = $mrc;
    }
    return $mrcs;
}
function getsignlist($type, $turn = 'DESC', $start_limit = 0, $pnum = 10, $tdtime = '') {
    $turn = $turn == 'ASC' ? $turn : 'DESC';
    $wheresql = !empty($tdtime) ? 'AND q.time >= '.$tdtime : '';
    return DB::fetch_all("SELECT q.*,m.* FROM ".DB::table('plugin_k_misign')." q, %t m WHERE q.uid=m.uid %i ORDER BY %i %i LIMIT %d, %d", array('common_member', $wheresql, $type, $turn, $start_limit, $pnum));
}
