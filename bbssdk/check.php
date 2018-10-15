<?php
try{
	if(isset($_REQUEST['check']))
	{ 
            
		$file = dirname(dirname(dirname(dirname(__FILE__)))).'/source/class/class_core.php';
		if(file_exists($file)){
			require_once $file;
			$discuz = C::app();
			$discuz->cachelist = array();
			$discuz->init();
			require_once 'lib/bbsjson.php';
		}else{
			die('File Path Error!');
		}
                if(isset($_REQUEST['share'])){//分享
                    require_once 'lib/function.php';
                    $tid = intval($_REQUEST['tid']);
                    $threadUrl = get_site_url().'plugin.php?id=bbssdk:share&tid='.$tid;
                    header('Location: '.$threadUrl);
                    exit;
                }
		if(isset($_REQUEST['comment_size']) && isset($_REQUEST['thread_size']))
		{
			$final = totalJson();
		}else{
			$final = getCheckJson();
			if($_REQUEST['from'] == "hb"){
				$data = array(
					"charset" => $final['charset'],
					"discuzversion" => $final['discuzversion'],
					"plugin_info" => $final['plugin_info'],
					"api_status" => $final['api_status'],
					"bbclosed" => $final['bbclosed'],
					"request_time" => $final['request_time'],
					"response_time" => $final['response_time'],
					"server_software" => $final['server_software']
				);
				$final = array('code'=>200,'data'=>$data,'message'=>'SUCCESS');
			}
		}
		
		header("Content-type:application/json;charset=".$_G['charset']);
	    if(preg_match('%^gb%is',$_G['charset']) && function_exists('json_encode_new')){
	        echo json_encode_new($final,true);
	    }else{
	        echo json_encode($final);
	    }
	    exit;
	}
}catch(\Exception $e){
	echo $e;
}

function totalJson()
{
	global $_G;
	$thread_size = intval($_REQUEST['thread_size']);
	$comment_size = intval($_REQUEST['comment_size']);
	$total = array();
	$total['forum'] = (int) DB::result_first("(SELECT count(*) FROM ".DB::table('forum_forum')." f LEFT JOIN ".DB::table('forum_forumfield')." ff ON ff.fid=f.fid WHERE (ff.redirect='' or ff.redirect is null))");
	$threadList = DB::fetch_all("(select fid,count(*) as total from ".DB::table('forum_thread')." where fid in(SELECT DISTINCT f.fid FROM ".DB::table('forum_forum')." f LEFT JOIN ".DB::table('forum_forumfield')." ff ON ff.fid=f.fid WHERE (ff.redirect='' or ff.redirect is null)) GROUP BY fid)");
	$total['thread'] = 0;
	$total['posts'] = 0;
	foreach ($threadList as $item) {
		$total['thread'] += (int) $item['total'] > $thread_size ? $thread_size : (int) $item['total'];
		$postList = DB::fetch_all("(select replies from ".DB::table('forum_thread')." where fid in({$item['fid']}) ORDER BY dateline limit $thread_size)");
		foreach ($postList as $it) {
			$total['posts'] += (int) $it['replies'] > $comment_size ? $comment_size : (int) $it['replies'];
		}
	}
	return $total;
}
function getCheckJson()
{
	global $_G;
	if(file_exists(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/data/sysdata/cache_mobile.php')){
		@require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/data/sysdata/cache_mobile.php';
	}else{
		$mobilecheck = '[]';
	}
	if(isset($mobilecheck)){
		$mobilecheck = json_decode($mobilecheck, true);
		if(!is_array($mobilecheck)){
			$mobilecheck = array();
		}
	}
	$mobilecheck['mob_setting_url'] = $_G['siteurl'].'remote.php';
	$mobilecheck['phpversion'] = function_exists('phpversion') ? phpversion() : '';
	$mobilecheck['mysqlversion'] = DB::result_first('select VERSION()');
	$grantString = '';
	$grants = DB::fetch_all('show grants');
	foreach ($grants as $list) {
		foreach ($list as $k => $item) {
			$grantString .= $item;
		}
	}
	$mobilecheck['mysqlgrants'] = preg_match('%(ALL\s+PRIVILEGES|TRIGGER)%is', $grantString);
	if(isset($_G['charset'])){
		$mobilecheck['charset'] = $_G['charset'];
	}
	if(isset($_G['setting']['version'])){
		$mobilecheck['discuzversion'] = $_G['setting']['version'];
	}
	if(isset($_G['setting']['sitename'])){
		$mobilecheck['sitename'] = $_G['setting']['sitename'];
	}
	if(isset($_G['setting']['ucenterurl'])){
		$mobilecheck['ucenterurl'] = $_G['setting']['ucenterurl'];
	}
	$mobilecheck['plugin_info'] = array('bbssdk' => array('enabled' => 0, 'version' => 'NA'));
	$lostPlugin = array();
	foreach ($mobilecheck['plugin_info'] as $plugin => &$info){
		if(isset($_G['setting']['plugins']['available']) && in_array($plugin, $_G['setting']['plugins']['available'])){
			$info['enabled'] = 1;
		}else{
			$lostPlugin[] = $plugin;
		}
		if(isset($_G['setting']['plugins']['version'][$plugin])){
			$info['version'] = $_G['setting']['plugins']['version'][$plugin];
		}
	}
	$mobilecheck['api_status'] = 'AVAILABLE';
	if(!empty($lostPlugin)){
		$mobilecheck['api_status'] = 'UNAVAILABLE, plugin(s) [ ' . implode(', ', $lostPlugin) . ' ] do not exist or have been closed';
	}
	$mobDir = dirname(dirname(__FILE__)) . '/mobile';
	if(!is_dir($mobDir)){
		$mobilecheck['plugin_info']['mobile']['dir_check'] = 0;
		$mobilecheck['bigapp_api_status'] = 'UNAVAILABLE, mobile plugin dir [' . $mobDir . '] does not exist';
	}
	$mobilecheck['mobile_enabled'] = 0;
	if(isset($_G['setting']['mobile']['allowmobile']) && $_G['setting']['mobile']['allowmobile']){
		$mobilecheck['mobile_enabled'] = 1;
	}
	$mobilecheck['bbclosed'] = 0;
	if(isset($_G['setting']['bbclosed']) && $_G['setting']['bbclosed']){
		$mobilecheck['bbclosed'] = 1;
	}
	$mobilecheck['request_time'] = $_SERVER['REQUEST_TIME_FLOAT'];
	$mobilecheck['response_time'] = microtime(true);
	$mobilecheck['server_software'] = $_SERVER['SERVER_SOFTWARE'];
	return $mobilecheck;
}