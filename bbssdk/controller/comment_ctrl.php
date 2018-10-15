<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');

class Comment extends BaseCore
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function post_list()
	{
                
		$fid = intval($_REQUEST['fid']);
		$tid = intval($_REQUEST['tid']);
		if(!$tid || !$fid) $this->return_status(403);

		$pagesize = intval($this->pageSize);
		$pagesize = $pagesize ? $pagesize : 20;
		if( $pagesize > 50) $pagesize = 20;
                
		$page = intval($this->pageIndex)>0 ? intval($this->pageIndex) : 1;
		$start = ($page - 1) * $pagesize;

		$thread = c::t('forum_thread')->fetch_by_tid_displayorder($tid);
                
//		$data['total_count'] =  (int) c::t('forum_post')->count_by_search($thread['posttableid'], $tid, null, null, null, null, null, null, null, null, 0);
//		$data['pagesize'] = $pagesize;
//		$data['currpage'] = $page;
//		$total_page = ceil($data['total_count']/$pagesize);
//		$data['nextpage'] = $page+1 <= $total_page ? $page+1 : $total_page;
//		$data['prepage'] = $page-1>0 ? $page-1 : 1;

		$list = array();

//		if($data['currpage'] <= $data['nextpage'] && $thread['fid'] == $fid){
			$where = 'fid = '. $fid;
			$postlist = c::t('forum_post')->fetch_all_by_tid($thread['posttableid'], $tid, true, '', $start, $pagesize,0,0);
                        $i = $pagesize*($page-1)+2;
			foreach ($postlist as $k=>$item) {
				$tmp = $this->relation_item($item,$thread);
                                $tmp['position'] = $i;
                                $list[] = $tmp;
                                $i++;
			}
//		}
				
		$data['posts'] = $list;

		$this->success_result($data);
	}

	public function get_item()
	{
		$fid = intval($_REQUEST['fid']);
		$tid = intval($_REQUEST['tid']);
		$pids = $_REQUEST['pid'];
		if(!$pids || !$tid || !$fid) $this->return_status(403);

		$thread = c::t('forum_thread')->fetch_by_tid_displayorder($tid);

		if(!empty($thread)) $query_item = c::t('forum_post')->fetch_all_by_pid($thread['posttableid'], $pids);

		if(!empty($query_item)){
			if(is_array($pids)){
				$list = array();
				foreach ($pids as $it) {
					if(is_array($query_item[$it]) && $query_item[$it]['fid'] == $fid && $query_item[$it]['tid'] == $tid){ 
						$thisItem = $this->relation_item($query_item[$it]);
						if(!empty($thisItem)) $list[] = $thisItem;
					}
				}
			}else{
				$list = null;
				if($query_item[$pids]['fid'] == $fid && $query_item[$pids]['tid'] == $tid){
					$list = $this->relation_item($query_item[$pids]);
				}
			}
		}
		$this->success_result($list);
	}
	public function post_item()
	{		
		global $_G;
                if(!$this->uid){
                    $this->return_status(111);//token 失败未登陆
                }
                $uid = $_G['uid'] = $this->uid;
                
		$_G['fid'] = $fid = intval($this->fid);
		$_G['tid'] = $tid = intval($this->tid);
		$reppid = intval($this->reppid);
		$clientip = $_G['clientip'];
                $this->message = str_replace(array("\r", "\n", "\r\n"), '<br>', $this->message);
		$message = htmlspecialchars_decode($this->bbcode_encode($this->message));
                $isanonymous = $this->isanonymous;

		if(!$fid || !$uid || !$tid || empty($clientip) || empty($message)){
			$this->return_status(403);
		}
                if(!preg_match('%utf%is', $this->charset)){
                        if(function_exists('iconv')){
                                $message = iconv('UTF-8', $this->charset . '//ignore', $message);
                        }else{
                                $message = mb_convert_encoding($message, $this->charset, 'UTF-8');
                        }
                }
		$member = getuserbyuid($uid, 1);
		C::app()->var['member'] = $member;
		$_G['groupid'] = $groupid = $member['groupid'];
		$groupid > 0 && $authAll = DB::fetch_all("select * from ".DB::table('common_usergroup')." a LEFT JOIN ".DB::table('common_usergroup_field')." b on a.groupid=b.groupid where a.groupid in($groupid)");
		count($authAll)>0 && C::app()->var['group'] = $authAll[0];

		$authForum = C::t('forum_forum')->fetch_all_info_by_fids($fid);
		if(count($authForum) > 0 ) {
			$tmpForum = $authForum[$fid];
			if(!empty($tmpForum['threadtypes'])) $tmpForum['threadtypes'] = unserialize($tmpForum['threadtypes']);
			if(!empty($tmpForum['formulaperm'])) $tmpForum['formulaperm'] = unserialize($tmpForum['formulaperm']);
			C::app()->var['forum'] = $tmpForum;
		}


		if(!$_G['uid'] && !((!$_G['forum']['replyperm'] && $_G['group']['allowreply']) || ($_G['forum']['replyperm'] && forumperm($_G['forum']['replyperm'])))) {
			$this->return_status(405,'抱歉，您尚未登录，没有权限在该版块回帖');
		} elseif(empty($_G['forum']['allowreply'])) {
			if(!$_G['forum']['replyperm'] && !$_G['group']['allowreply']) {
				$this->return_status(405,'抱歉，您没有权限在该版块回帖');
			} elseif($_G['forum']['replyperm'] && !forumperm($_G['forum']['replyperm'])) {
				$this->return_status(405,'抱歉，您没有权限在该版块回帖');
			}
		} elseif($_G['forum']['allowreply'] == -1) {
			$this->return_status(405,'抱歉，本版块只有特定用户组可以回复');
		}

		if(!$_G['uid'] && ($_G['setting']['need_avatar'] || $_G['setting']['need_email'] || $_G['setting']['need_friendnum'])) {
			$this->return_status(405,'抱歉，您尚未登录，没有权限在该版块回帖');
		}

		$thread = c::t('forum_thread')->fetch_by_tid_displayorder($tid);
		if(empty($thread)) {
			$this->return_status(405,'抱歉，指定的主题不存在或已被删除或正在被审核');
		} elseif($thread['price'] > 0 && $thread['special'] == 0 && !$_G['uid']) {
			$this->return_status(405,"抱歉，您所在的用户组({$authAll[0]['grouptitle']})无法进行此操作");
		}

		C::app()->var['thread'] = $thread;

		loadcache(array('bbcodes_display', 'bbcodes', 'smileycodes', 'smilies', 'smileytypes', 'domainwhitelist', 'albumcategory'));

		$modpost = C::m('forum_newpost', $tid);
		$bfmethods = $afmethods = array();

		$params = array(
			'message' => $message,
			'bbcodeoff' => 0,
			'smileyoff' => 0,
			'htmlon' => 0,
			'useip' => $clientip,
                        'isanonymous'=>$isanonymous
		);

		if($reppid>0){
			$params = array_merge($params,$this->replyItem($tid,$reppid,$thread));
		}

		$attentionon = empty($_GET['attention_add']) ? 0 : 1;
		$attentionoff = empty($attention_remove) ? 0 : 1;
		$bfmethods[] = array('class' => 'extend_thread_rushreply', 'method' => 'before_newreply');
		if($_G['group']['allowat']) {
			$bfmethods[] = array('class' => 'extend_thread_allowat', 'method' => 'before_newreply');
		}

		$bfmethods[] = array('class' => 'extend_thread_comment', 'method' => 'before_newreply');
		$modpost->attach_before_method('newreply', array('class' => 'extend_thread_filter', 'method' => 'before_newreply'));

		if($_G['group']['allowat']) {
			$afmethods[] = array('class' => 'extend_thread_allowat', 'method' => 'after_newreply');
		}

		$afmethods[] = array('class' => 'extend_thread_rushreply', 'method' => 'after_newreply');
		$afmethods[] = array('class' => 'extend_thread_comment', 'method' => 'after_newreply');

		if($thread['replycredit'] > 0 && $thread['authorid'] != $_G['uid'] && $_G['uid']) {
			$afmethods[] = array('class' => 'extend_thread_replycredit', 'method' => 'after_newreply');
		}


		$afmethods[] = array('class' => 'extend_thread_image', 'method' => 'after_newreply');
		$afmethods[] = array('class' => 'extend_thread_filter', 'method' => 'after_newreply');

		$modpost->attach_before_methods('newreply', $bfmethods);
		$modpost->attach_after_methods('newreply', $afmethods);

		$return = $modpost->newreply($params);
		$pid = $modpost->pid;

		if($specialextra) {
			@include_once DISCUZ_ROOT.'./source/plugin/'.$_G['setting']['threadplugins'][$specialextra]['module'].'.class.php';
			$classname = 'threadplugin_'.$specialextra;
			if(class_exists($classname) && method_exists($threadpluginclass = new $classname, 'newreply_submit_end')) {
				$threadpluginclass->newreply_submit_end($_G['fid'], $_G['tid']);
			}
		}
		if(in_array($return,array('post_reply_mod_succeed','post_reply_succeed'))){
			$query_item = c::t('forum_post')->fetch_all_by_pid($thread['posttableid'], $pid);
			$list = null;
			if($query_item[$pid]['fid'] == $fid && $query_item[$pid]['tid'] == $tid){
				$list = $this->relation_item($query_item[$pid]);
			}
			$this->success_result($list);
		}	

	}
        public function post_edit(){
            $this->put_item();
        }
        public function post_delete(){
            $this->delete_item();
        }
        public function put_item()
	{
		require_once libfile('function/forum');
		global $_G;
		$fid = intval($this->fid);
		$uid = intval($this->uid);
		$tid = intval($this->tid);
		$pid = intval($this->pid);
		$clientip = $this->clientip;
		$message = htmlspecialchars_decode($this->bbcode_encode($this->message));

		if(!$fid || !$uid || !$tid || empty($clientip) || empty($message)){
			$this->return_status(403);
		}
                if(!preg_match('%utf%is', $this->charset)){
			if(function_exists('iconv')){
				$message = iconv('UTF-8', $this->charset . '//ignore', $message);
			}else{
				$message = mb_convert_encoding($message, $this->charset, 'UTF-8');
			}
		}
		$_G['uid'] = $uid;

		$member = getuserbyuid($uid, 1);
		C::app()->var['member'] = $member;
		$_G['groupid'] = $groupid = $member['groupid'];
		$groupid > 0 && $authAll = DB::fetch_all("select * from ".DB::table('common_usergroup')." a LEFT JOIN ".DB::table('common_usergroup_field')." b on a.groupid=b.groupid where a.groupid in($groupid)");
		count($authAll)>0 && C::app()->var['group'] = $authAll[0];

		$authForum = C::t('forum_forum')->fetch_all_info_by_fids($fid);
		if(count($authForum) > 0 ) {
			$tmpForum = $authForum[$fid];
			if(!empty($tmpForum['threadtypes'])) $tmpForum['threadtypes'] = unserialize($tmpForum['threadtypes']);
			if(!empty($tmpForum['formulaperm'])) $tmpForum['formulaperm'] = unserialize($tmpForum['formulaperm']);
			C::app()->var['forum'] = $tmpForum;
		}

		if($_G['setting']['connect']['allow'] && $_G['setting']['accountguard']['postqqonly'] && !$_G['member']['conisbind']) {
			$this->return_status(405,'为避免您的帐号被盗用，请您绑定QQ帐号后发帖，绑定后请使用QQ帐号登录');
		}

		if(!$_G['uid'] && !((!$_G['forum']['postperm'] && $_G['group']['allowpost']) || ($_G['forum']['postperm'] && forumperm($_G['forum']['postperm'])))) {
				$this->return_status(405,'抱歉，您尚未登录，没有权限在该版块发帖');
		} elseif(empty($_G['forum']['allowpost'])) {
			if(!$_G['forum']['postperm'] && !$_G['group']['allowpost']) {
				$this->return_status(405,'抱歉，您没有权限在该版块发帖');
			} elseif($_G['forum']['postperm'] && !forumperm($_G['forum']['postperm'])) {
				$this->return_status(405,'抱歉，您没有权限在该版块发帖');
			}
		} elseif($_G['forum']['allowpost'] == -1) {
			$this->return_status(405,'抱歉，本版块只有特定用户组可以发新主题', NULL);
		}

		if(!$_G['uid'] && ($_G['setting']['need_avatar'] || $_G['setting']['need_email'] || $_G['setting']['need_friendnum'])) {
			$this->return_status(405,'抱歉，您尚未登录，没有权限在该版块发帖');
		}

		loadcache(array('bbcodes_display', 'bbcodes', 'smileycodes', 'smilies', 'smileytypes', 'domainwhitelist', 'albumcategory'));
		
		$thread = get_post_by_pid($pid);
		$modpost = C::m('forum_newpost', $tid, $thread['pid']);


		$params = array(
			'message' => $message,
			'typeid' => 0, //主题分类id
			'sortid' => 0, //分类信息id
			'special' => 0,	//特殊主题
			'clientip' => $clientip
		);

		$params['publishdate'] = $_G['timestamp'];

		$params['digest'] = 0;

		$params['tags'] = '';
		$params['bbcodeoff'] = 0;
		$params['smileyoff'] = 0;
		$params['htmlon'] = 0;

		$return = $modpost->editpost($params);
		$tid = $modthread->tid;
		$pid = $modthread->pid;

		$item = c::t('forum_thread')->fetch_by_tid_displayorder($tid);

		$data = $this->relation_item($item,$thread);

		$this->success_result($data);
	}
	public function delete_item()
	{		
		require_once libfile('function/forum');
		$fid = intval($this->fid);
		$tid = intval($this->tid);
		$uid = intval($this->uid);
		$pid = intval($this->pid);
		$clientip = $this->clientip;

		if(!$fid || !$tid || !$uid || empty($clientip)){
			$this->return_status(403);
		}


		$thread = get_post_by_pid($pid);

		if(empty($thread)){
			$this->return_status(405,'评论已删除');
		}

		if($thread['authorid'] != $uid){
			$this->return_status(405,'抱歉，用户不能删除其他人的评论');
		}

		$pid = $thread['pid'];
		$modpost = C::m('forum_newpost', $tid, $pid);
		
		$param = array('fid' => $fid, 'tid' => $tid, 'pid' => $pid);	
		
		$result = $modpost->deletepost($param);

		$this->success_result('删除成功');
	}

	protected function replyItem($tid,$reppid,$thread)
	{
		global $_G;
		require_once libfile('function/discuzcode');
		$noticeauthor = $noticetrimstr = '';
		$thaquote = C::t('forum_post')->fetch('tid:'.$tid, $reppid);
		if(!($thaquote && ($thaquote['invisible'] == 0 || $thaquote['authorid'] == $_G['uid'] && $thaquote['invisible'] == -2))) {
			$thaquote = array();
		}
		if($thaquote['tid'] != $_G['tid']) {
			$this->return_status(405,"禁止引用自己和主题帖之外的帖子");
		}

		if(getstatus($thread['status'], 2) && $thaquote['authorid'] != $_G['uid'] && $_G['uid'] != $thread['authorid'] && $thaquote['first'] != 1 && !$_G['forum']['ismoderator']) {
			$this->return_status(405,"禁止引用自己和主题帖之外的帖子");
		}
		if(!($thread['price'] && !$thread['special'] && $thaquote['first'])) {
			$message = $thaquote['message'];

			$time = dgmdate($thaquote['dateline']);
			$message = messagecutstr($message, 100);
			$message = implode("\n", array_slice(explode("\n", $message), 0, 3));

			$thaquote['useip'] = substr($thaquote['useip'], 0, strrpos($thaquote['useip'], '.')).'.x';
			if($thaquote['author'] && $thaquote['anonymous']) {
				$thaquote['author'] = lang('forum/misc', 'anonymoususer');
			} elseif(!$thaquote['author']) {
				$thaquote['author'] = lang('forum/misc', 'guestuser').' '.$thaquote['useip'];
			} else {
				$thaquote['author'] = $thaquote['author'];
			}

			$post_reply_quote = lang('forum/misc', 'post_reply_quote', array('author' => $thaquote['author'], 'time' => $time));
			$noticeauthormsg = dhtmlspecialchars($message);
			if(!defined('IN_MOBILE')) {
				$message = "[quote][size=2][url=forum.php?mod=redirect&goto=findpost&pid=$reppid&ptid={$_G['tid']}][color=#999999]{$post_reply_quote}[/color][/url][/size]\n{$message}[/quote]";
			} else {
				$message = "[quote][color=#999999]{$post_reply_quote}[/color]\n[color=#999999]{$message}[/color][/quote]";
			}
			$quotemessage = discuzcode($message, 0, 0);
			$noticeauthor = dhtmlspecialchars(authcode('q|'.$thaquote['authorid'], 'ENCODE'));
			$noticetrimstr = dhtmlspecialchars($message);
			$message = '';
		}
		return array('noticeauthor'=>$noticeauthor,'noticetrimstr'=>$noticetrimstr);
	}
	protected function relation_item($item,$thread=array())
	{
		try{
			require_once libfile('function/discuzcode');
			$newItem = array();
			if(is_array($item) && (int)$item['first'] == 0)
			{
				$message = discuzcode($item['message'], $item['smileyoff'], $item['bbcodeoff'], $item['htmlon'], 1);
				$newItem = array(
					"fid" => (int)$item['fid'],
					"tid" => (int)$item['tid'],
					"pid" => (int)$item['pid'],
					"author" => $item['author'],
					"authorId" => (int)$item['authorid'],
					"avatar" => avatar($item['authorid'],'middle',1),
					"createdOn" => (int)$item['dateline'],
					"useip" => $item['useip'],
					"invisible" => (int) $item['invisible'],
					"position" => (int)$item['position']
				);
                                
                                $hiddenreplies = getstatus($thread['status'], 2);
				$messgeArray = $this->filter_detail($message,$newItem['tid']);
				$newItem['message'] = $hiddenreplies!=1||($thread['authorid']==$this->uid||$item['authorid']==$this->uid)?$messgeArray[0]:'<p style="padding: 8px 8px 8px 24px;border: 1px dashed #FF9A9A;">此帖仅作者可见</p>';
                                if($messgeArray[1]){
                                    $newItem['prePost'] = $messgeArray[1]?$messgeArray[1]:(object)null;
                                }
			}
			return $newItem;
		}catch(\Exception $e){
			write_log('relation_item Error:'.$e);
			$this->return_status(500);
		}
	}

	/*
	 * 筛选前置评论，过滤相对图片地址
	 */
	protected function filter_detail($text,$tid)
	{
		global $_G;
		$precomment = null;
		$newMessage = preg_replace_callback("%<blockquote>([\s\S]*)</blockquote>%is", 
			function($matches) use (&$precomment) {
				$actTxt = preg_replace("%<[^>]*>%", '', $matches[0]);
				$text = explode("\n", $actTxt);
				preg_match("%^[\S]*%is", $text[0],$author);
				preg_match("%\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}%is", $actTxt, $dateline);
				if(!empty($author[0]) && !empty($text[1])){
					$precomment = array(
						"author" => $author[0],
						"dateline" => strtotime($dateline[0]),
						"message" => $text[1]
					);
					return '';
				}
				return $matches[0];
			}
		, $text);

		$newMessage = preg_replace_callback("%\[attach\]\s*(\d*)\[/attach]%is", 
						function($matches) use ($tid){
							$thisItem = C::t('forum_attachment_n')->fetch('tid:'.$tid, $matches[1]);
							if((int)$thisItem['isimage'] == 1 || preg_match('%\.(gif|jpg|png|jpeg)$%is', $thisItem['filename'])){
								return "<img src='".check_url($thisItem['attachment'])."'>";
							}
							return '';
						}
						,$newMessage);

		$newMessage = preg_replace("%<div\s*class=\"quote\"><\/div><br\s*\/>%is", '', $newMessage);

		$newMessage = preg_replace_callback("%(src=['\"])([^'\"]*)%", 
				function($matches) use ($_G) {
					if(!preg_match("%^http%is", $matches[2]))
					{
						return $matches[1] . get_site_url() . $matches[2];
					}
					return $matches[0];
				}
			, $newMessage);

		return array($newMessage,$precomment);
	}
}