<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');
/**
* BaseCore
*/
require_once 'function.php';

class BaseCore
{
	protected $bbcode = 0;
	protected $charset = 'UTF-8';
	protected $setting;
	private $params;
        protected $login = false;
        protected $appsecret = '';
        var $cachelist = array();
	protected $sync_mods = array(
		'forum'   => array('bbssdk_forum_sync','dateline',array('fid','tid')),
		'comment' => array('bbssdk_comment_sync','dateline',array('fid','tid','pid')),
		'member'  => array('bbssdk_member_sync','regdate',array('uid'))
	);
	function __construct()
	{
		global $_G;
		$this->initParams();
                $this->token = isset($_SERVER['HTTP_TOKEN'])?trim($_SERVER['HTTP_TOKEN']):$this->token;
                $this->uid = 0;
                if($this->token){
                    list($pwd, $uid) = explode("\t", authcode(base64_decode($this->token), 'DECODE','bbssdk'));
                    if($uid){
                        $user = getuserbyuid($uid, 1);
                    }

                    if(!empty($user) && $user['password'] == $pwd) {
                        if(isset($user['_inarchive'])) {
                                C::t('common_member_archive')->move_to_master($uid);
                        }
                        $this->login = true;
                        $this->uid = $uid;
                        C::app()->var['member'] = $user;
                    } else {
                        $user = array();
                        //$this->_init_guest();游客
                    }
                }
                $this->_initUser();
		$this->bbcode = isset($_G['setting']['bbclosed']) && $_G['setting']['bbclosed'] ? 1 : 0;
		$this->charset = strtoupper($_G['charset']);
		$this->setting = C::app()->var['setting'];
		$this->mod = strtolower($this->mod);
	}

	private function initParams()
	{
            $params = array();
            $keyVal = function($act) use(&$params){
                    $a = explode('=', $act);
                    $item = array();
                    if(preg_match('%^\d+$%is', $a[1])){
                            $item[$a[0]] = intval($a[1]);
                    }else{
                            $item[$a[0]] = urldecode($a[1]);
                    }
                    $params = array_merge($params,$item);
            };
            $request = file_get_contents("php://input");
            
            if(!( BBSSDK_DEBUG && isset($_REQUEST['debug']))){
                $request = base64_decode($request);
                $setting = C::t('common_setting')->fetch_all(array('bbssdk_setting','portalstatus'));
                $bbssdk = (array)unserialize($setting['bbssdk_setting']);
                
                if(empty($bbssdk['appkey']) || empty($bbssdk['appsecret'])){
			return_status(110);
                }
                
                $z = substr($bbssdk['appsecret'] , 0 , 16);
                require_once 'Aes.php';
                $aes = new Aes($z);
                $request = $aes->decrypt($request);
                preg_match('/{.*}/',$request,$m);
                $request = json_decode($m[0],true);
                if(!$request|| !is_array($request)){
                    //return_status(302);
                }
                $this->appsecret = $bbssdk['appsecret'];
                $_REQUEST = $params = array_merge($_REQUEST,$request);	
            }else{
                array_map($keyVal, explode('&',$request));
                $_REQUEST = $params = array_merge($_REQUEST,$params);	
            }
            foreach ($params as $key => $value) {
                    if('uid'!=$key){
                        $this->params[$key] = $value;
                    }                    
            }
	}
        private function _initUser(){
            global $_G;
            if($this->uid){
                $user = getuserbyuid($this->uid, 1);
                if($user){
                    $_G['groupid'] = $user['groupid'];
                    $this->cachelist[] = 'usergroup_'.$user['groupid'];
                    if($user['adminid'] > 0 && $user['groupid'] != $user['adminid']) {
                        $this->cachelist[] = 'admingroup_'.$user['adminid'];
                    }
                    !empty($this->cachelist) && loadcache($this->cachelist);
                    if($_G['group']['radminid'] == 0 && $user['adminid'] > 0 && $user['groupid'] != $user['adminid']&& !empty($_G['cache']['admingroup_'.$user['adminid']])) {
                            $_G['group'] = array_merge($_G['group'], $_G['cache']['admingroup_'.$user['adminid']]);
                    }
                }
            }else{
                $_G['groupid'] = 7;
            }
        }

        public function bbcode_encode($message)
	{
		require_once libfile('class/bbcode');
		require_once libfile('function/editor');
		$bbcode = new bbcode();
		$message = urldecode($message);
		$html_s_str = array('<div>', '</div>','<p>','</p>','<span>','</span>');
		$html_r_str = array('[div]', '[/div]','[p]','[/p]','[span]','[/span]');
		// @$message = str_replace($html_s_str, $html_r_str,$message);
		// return $bbcode->html2bbcode($message);
		return html2bbcode($message);
	}
	public function success_result($params,$message="SUCCESS")
	{
	    global $_G;
//	    $final = array('status'=>201,'message'=>'无返回');
//	    if(!empty($params) && !( isset($params['list']) && empty($params['list']) ) ){
	        $final['status'] = 200;
                if($this->mod == 'comment' && !empty($params['tid'])){
//                        require_once dirname(dirname(__FILE__)).'/controller/forum_ctrl.php';
//                        $forum = new Forum();
//                        $params['thread'] = $forum->getItem($params['fid'],$params['tid']);
                }

	        $final['res'] = $params;
                unset($final['message']);
	        if($_SERVER['REQUEST_METHOD'] != 'GET'){
                    write_log (
                            'method=>'.$_SERVER['REQUEST_METHOD']
                            . "\t Request=>".json_encode($this->params)
                            . "\t Response=>".json_encode($final)
                            ,'debug'
                    );
                }
//	    }
	    header("Content-type:application/json;charset=utf-8");
	    if(preg_match('%^gb%is',$_G['charset'])){
                $final = gbkToUtf8($final,$_G['charset']);
	    }
            $r = json_encode($final,JSON_NUMERIC_CHECK);
                        
            if(!( BBSSDK_DEBUG && isset($_REQUEST['debug']))){
                $z = substr($this->appsecret , 0 , 16);
                require_once 'Aes.php';
                $aes = new Aes($z);
                $y = $aes->encrypt($r);

                echo base64_encode($y);exit;
            }
            echo $r;exit;
	}
        function return_status($code,$params=null)
        {
            global $_G;$_ERROR = $GLOBALS['BBSSDK_ERROR'];
            $data = array();
            $code = intval($code);
            $data['status'] = $code;
            $data['message'] = isset($_ERROR[$code]) ? $_ERROR[$code] : '未知错误';
            
            $code = $this->formatStatusCode($code);
            $data['status'] = intval($code);
            if(!empty($params)){
                if(is_array($params)){
                    $data = array_merge($data,$params);
                }else{
                    $data['message'] = $params;
                }
            }
            if($code!=200){
                write_log (
                    'ERROR Method=>' . $_SERVER['REQUEST_METHOD']
                    . "\t REQUEST=>".json_encode($_REQUEST)
                    . "\t Response=>".json_encode($data)
                );
            }
            
            header("Content-type:application/json;charset=utf8");
            $r = json_encode($data,JSON_NUMERIC_CHECK);
                                    
            if(!( BBSSDK_DEBUG && isset($_REQUEST['debug']))){
                $z = substr($this->appsecret , 0 , 16);
                require_once 'Aes.php';
                $aes = new Aes($z);
                $y = $aes->encrypt($r);

                echo base64_encode($y);exit;
            }
            echo $r;exit;
            exit;
        }
        public function formatStatusCode($code){
            if(in_array($code, array(304,305,306,406,408,499,500,601,602,603,604,605,606,607,608,609,610,611,612,614,615))){
                $code = '90'.$code;
            }else if(in_array($code,array(30103,30104,30105,30106))){
                $code = 601;
            }else if(in_array($code, array(30201901,30201902,302015,302016,302105))){
                $code = 602;
            }else if(in_array($code, array(302011,302012,302013,302014,302017,302018,302101,302102,302104))){
                $code = 603;
            }else if(in_array($code, array(302105,302106,302103))){
                $code = 604;
            }else if(in_array($code, array(30101))){
                $code = 605;
            }else if(in_array($code, array(30102,3021101))){
                $code = 606;
            }else if(in_array($code, array(3021102))){
                $code = 607;
            }else if(in_array($code, array(3021103))){
                $code = 608;
            }else if(in_array($code, array(301101,301102))){
                $code = 611;
            }
            return $code;
        }
        public function common_sync($params,$sync_params)
	{
		$method = strtolower($_SERVER['REQUEST_METHOD']);
		$final = is_array($params) ? array_merge($this->params,$params) : $this->params;
		$a = array_search($method, array('post','put','delete'));
		if( $a>-1 && isset($sync_params))
		{
			$a = $a + 1;
			$table = $sync_params[0];
			$needKey = $sync_params[1];
			$dateline = isset($final[$needKey]) && $a == 1 ? $final[$needKey] : time();
			$search = array();
			$where = array();
			foreach ($sync_params[2] as $key) {
				$search[$key] = intval($final[$key]);
				array_push($where , $key.'='.intval($final[$key]));
			}
			$where = implode(' and ', $where);
			$item = DB::fetch_first("select * from ".DB::table($table).' where '.$where);
			if($item){
				$sql = 'update '.DB::table($table)." set synctime=$dateline,modifytime=$dateline,flag=$a where syncid=".$item['syncid'];
			}else{
				$sqlKey = implode(',', array_keys($search));
				$values = implode(',', array_values($search));
				$sql = 'insert into '.DB::table($table).'('.$sqlKey.',synctime,modifytime,creattime,flag) value('.$values.",$dateline,$dateline,$dateline,$a)";
			}
			write_log('common sync method=>'.$this->method.' where=>'.$where.' sql=>'.$sql,'debug');
			DB::query($sql);
		}
	}

	public function __get($name){
		return $this->params[$name];
	}
	public function __set($name,$value){
		if(!empty($value)){
			$this->params[$name] = $value;
		}
	}
}

class model_forum_newpost extends model_forum_post
{
	public function showmessage(){
		$p = func_get_args();
		isset($p[0]) && $message = $p[0];
		isset($p[1]) && $url_forward = $p[1];
		isset($p[2]) && $values = $p[2];
		isset($p[3]) && $extraparam = $p[3];
		isset($p[4]) && $custom = $p[4];
		global $_G, $show_message;

		$navtitle = lang('core', 'title_board_message');

		if($custom) {
			$alerttype = 'alert_info';
			$show_message = $message;
                        $bc = new BaseCore();
                        $bc->return_status(405,$show_message);
		}

		$vars = explode(':', $message);
		if(count($vars) == 2) {
			$show_message = lang('plugin/'.$vars[0], $vars[1], $values);
		} else {
			$show_message = lang('message', $message, $values);
		}

		if($_G['connectguest']) {
			$param['login'] = false;
			$param['alert'] = 'info';
			if (defined('IN_MOBILE')) {
				if ($message == 'postperm_login_nopermission_mobile') {
					$show_message = lang('plugin/qqconnect', 'connect_register_mobile_bind_error');
				}
				$show_message = str_replace(lang('forum/misc', 'connectguest_message_mobile_search'), lang('forum/misc', 'connectguest_message_mobile_replace'), $show_message);
			} else {
				$show_message = str_replace(lang('forum/misc', 'connectguest_message_search'), lang('forum/misc', 'connectguest_message_replace'), $show_message);
			}
			if ($message == 'group_nopermission') {
				$show_message = lang('plugin/qqconnect', 'connectguest_message_complete_or_bind');
			}
		}
                $bc = new BaseCore();
		$bc->return_status(405,$show_message);
	}
}

class model_forum_newthread extends model_forum_thread
{
	public function showmessage(){
		$p = func_get_args();
		isset($p[0]) && $message = $p[0];
		isset($p[1]) && $url_forward = $p[1];
		isset($p[2]) && $values = $p[2];
		isset($p[3]) && $extraparam = $p[3];
		isset($p[4]) && $custom = $p[4];
		global $_G, $show_message;

		$navtitle = lang('core', 'title_board_message');

		if($custom) {
			$alerttype = 'alert_info';
			$show_message = $message;
                        $bc = new BaseCore();
                        $bc->return_status(405,$show_message);
		}

		$vars = explode(':', $message);
		if(count($vars) == 2) {
			$show_message = lang('plugin/'.$vars[0], $vars[1], $values);
		} else {
			$show_message = lang('message', $message, $values);
		}

		if($_G['connectguest']) {
			$param['login'] = false;
			$param['alert'] = 'info';
			if (defined('IN_MOBILE')) {
				if ($message == 'postperm_login_nopermission_mobile') {
					$show_message = lang('plugin/qqconnect', 'connect_register_mobile_bind_error');
				}
				$show_message = str_replace(lang('forum/misc', 'connectguest_message_mobile_search'), lang('forum/misc', 'connectguest_message_mobile_replace'), $show_message);
			} else {
				$show_message = str_replace(lang('forum/misc', 'connectguest_message_search'), lang('forum/misc', 'connectguest_message_replace'), $show_message);
			}
			if ($message == 'group_nopermission') {
				$show_message = lang('plugin/qqconnect', 'connectguest_message_complete_or_bind');
			}
		}
                $bc = new BaseCore();
		$bc->return_status(405,$show_message);
	}
}
