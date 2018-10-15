<?php
function noPrivileges($g){
    $p = explode(', ',$g[1]);
    $nop = '';
    if(strpos($p[0],'ALL PRIVILEGES')===false){
        foreach (array('SELECT','INSERT','UPDATE','DELETE','DROP','CREATE') as $i){
            if(!in_array($i, $p)){
                $nop.= $i.'<br/>';
            }
        }
    }
    return $nop;
}
function getBeijingTime(){
    $urls = array(
        'qq'=>'http://cgi.im.qq.com/cgi-bin/cgi_svrtime',//qq
        'taobao'=>'http://api.m.taobao.com/rest/api3.do?api=mtop.common.getTimestamp',//taobao
        'suning'=>'http://quan.suning.com/getSysTime.do',//suning
        'bjtime'=>'https://bjtime.cn/st.asp',//bjtime
        );
    foreach ($urls as $k=>$url){
        $res = file_get_contents($url);
        if($res === FALSE){
            continue;
        }
        $t = 0;
        switch ($k){
            case 'qq':
                $t = strtotime($res);
                break;
            case 'taobao':
                $tb = json_decode($res,1);
                $t  = strtotime(date('Y-m-d H:i:s',$tb['data']['t']*0.001));
                break;
            case 'suning':
                $sn = json_decode($res,1);
                $t = strtotime($sn['sysTime2']);
                break;
            case 'bjtime':
                //todo
                break;
        }
        if($t){
            break;
        }
    }
    return $t;
}
if((!isset($_POST['bbssdk_check'])||$_POST['bbssdk_check']!='checked')&&!isset($_POST['formhash'])){
    require_once 'check.php';
    $final = getCheckJson();
    $is_allow_dz_v = (in_array($final['discuzversion'], array('X3','X3.0', 'X3.1', 'X3.2','X3.3', 'X3.4')));

    preg_match("/^\d+\.\d+/", $final['phpversion'],$php);
    $is_allow_php_v =  $php[0]>=5.3?true:false;

    preg_match("/^\d+\.\d+/", $final['mysqlversion'],$mysql);
    $is_allow_mysql_v =  $mysql[0]>=5.0?true:false;
    
    $grants = DB::fetch_all('show grants');
    $grantsall = (array_values($grants[0]));
    preg_match("/GRANT (.*) ON/", strtoupper($grantsall[0]),$g);
    $nop = noPrivileges($g);//全局
    
    if($nop){//当前数据库
        @include DISCUZ_ROOT.'./config/config_global.php';
        foreach ($grants as $grant){
            $grant = array_values($grant);
            preg_match("/GRANT (.*) ON `{$_config['db'][1]['dbname']}`/i", stripslashes(strtoupper($grant[0])),$gdb);

            if(!empty($gdb)){
                $nop = noPrivileges($gdb);
            }
        }
    }
    //服务器时间
    $bjtime = getBeijingTime();
    $now = time();
    $timediff = abs($now-$bjtime);
    if($timediff<10){
        $timediff = 0;
    }
    if($_G['charset']=='gbk'){
        require_once "template/check_gbk.html"; 
    }else{
        require_once "template/check.html"; 
    }
    exit;
}

