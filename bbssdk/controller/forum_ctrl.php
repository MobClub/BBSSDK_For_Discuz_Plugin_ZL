<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');
require_once 'table/table_bbssdk_thread.php';
require_once 'table/table_bbssdk_forum.php';

class Forum extends BaseCore
{
	private $MarkdownToHtml;
	function __construct()
	{
		$this->MarkdownToHtml = new Markdown();
		parent::__construct();
	}

	public function post_list()
	{
		global $_G;
		$data = array();
		
		$fid = intval($_REQUEST['fid']);
//		if(!$fid) $this->return_status(403);
                
                $selectType = trim($_REQUEST['selectType']);
                $selectType = $selectType&&$selectType != 'latest'?$selectType:'dateline';
                $orderType  = trim($_REQUEST['orderType']);
                if($orderType == 'lastPost'){
                    $orderType = 'replies';
                }else{
                    $orderType = 'lastpost';
                }
                if($selectType=='heats'){
                    $orderType = 'heats'; 
                }
		$pagesize = intval($_REQUEST['pageSize']);
		$pagesize = $pagesize ? $pagesize : 10;
		if( $pagesize > 20) $pagesize = 10;
		
		$page = intval($_REQUEST['pageIndex'])>0 ? intval($_REQUEST['pageIndex']) : 1;
		$start = ($page - 1) * $pagesize;

		
//		$data['total_count'] =  c::t('bbssdk_thread')->count_by_fid($fid);
//		$data['pagesize'] = $pagesize;
//		$data['currpage'] = $page;
//		$total_page = ceil($data['total_count']/$pagesize);
//		$data['nextpage'] = $page+1 <= $total_page ? $page+1 : $total_page;
//		$data['prepage'] = $page-1>0 ? $page-1 : 1;

		$list = array();
//`digest`>'0'  FORCE INDEX (digest) 
//		if($data['currpage'] <= $data['nextpage']){
                        $sort  = " order by tid desc";
                        if($fid){
                            $where = 'fid = '.$fid;
                        }else{
                            $where = '1 = 1';
                        }
                        
                        if($selectType!='displayOrder'){
                            $where.= ' and displayorder in(0,1,2,3,4)';
                            if($selectType=='digest'){
                                $where.= ' and `digest`>0';
                            }
//                            $sort  = " order by ".$selectType." desc";
                            $sort  = " order by displayorder desc";
                            //if($orderType&&$orderType!='dateline'){
                                $sort .= ",".$orderType." desc";
                            //}
                            if(!$fid){
                                $sort  = " order by ".$orderType." desc";
                            }
                            $where .= $sort;
                        }else{
                            $where .= ' and displayorder in(1,2,3,4) order by '.$orderType." desc";;
                        }
//                        echo $where;
			try{
				$thread_list = c::t('bbssdk_thread')->range_list($where , $start, $pagesize);
				$gids = $tids = array();
				foreach ($thread_list as $k=>$item) {
                                        array_push($gids, $item['fid']);
					array_push($tids, $item['tid']);
				}
                                if(!empty($gids)) {
                                        $gforumnames = C::t('forum_forum')->fetch_all_name_by_fid($gids);
                                        foreach($gforumnames as $fid => $val) {
                                                $forumnames[$fid] = $val;
                                        }
                                }
				if(count($tids) > 0 ){
					$query_message = c::t('bbssdk_forum')->fetch_threadpost_by_tid($tids);

					$messages = array();
					foreach ($query_message as $item) {
						$messages[$item['tid']] = $item;
					}
					foreach ($thread_list as $k=>$item) {
                                                $tmp = $this->relation_item($item, $messages[$item['tid']]);
                                                unset($tmp['message']);
                                                $tmp['forumName'] = isset($forumnames[$item['fid']])?$forumnames[$item['fid']]['name']:'';
						$list[$k] = $tmp;
					}

				}
			}catch(\Exception $e){
				write_log('Forum Error:'.$e);
				$this->return_status(500);
			}
//		}

		$data['threads'] = $list;

		$this->success_result($data);
	}

	public function post_detail()
	{            
                global $_G;
                $_G['uid'] = $this->uid;
		$tid = intval($_REQUEST['tid']);
		if(!$tid) $this->return_status(403);
                $_G['tid'] = $tid;
		$item = c::t('forum_thread')->fetch_by_tid_displayorder($tid);
		
		$current = c::t('forum_post')->fetch_threadpost_by_tid_invisible($item['tid']);

		$data = $this->relation_item($item,$current);
                $data['favid'] = 0;
                $fav = C::t('home_favorite')->fetch_by_id_idtype($tid, 'tid', $_G['uid']);
                if($fav){
                    $data['favid'] = (int)$fav['favid'];
                }
                $flag = C::t('home_follow')->fetch_status_by_uid_followuid($_G['uid'], $data['authorId']);
                C::t('forum_thread')->increase($item['tid'], array('views' => 1), true);
                $data['follow'] = isset($flag[$_G['uid']])?true:FALSE;
                $authorreplyexist =  C::t('forum_post')->fetch_pid_by_tid_authorid($_G['tid'], $_G['uid']);
                if($_G['uid'] == $data['authorId']||$authorreplyexist) {
                        $data['message'] = preg_replace("/\[hide\]\s*(.*?)\s*\[\/hide\]/is", '\\1', $data['message']);
                } else {
                        $data['message'] = preg_replace("/\[hide\](.*?)\[\/hide\]/is", '<p style="padding: 8px 8px 8px 24px;border: 1px dashed #FF9A9A;">此帖回复可见</p>', $data['message']);
                        $data['message'] = $data['message'];
                        $data['replyShow'] = 1;
                }

		$this->success_result($data);
	}

	public function getItem($tid)
	{
		$tid = intval($tid);
		$item = c::t('forum_thread')->fetch_by_tid_displayorder($tid);
		
		$current = c::t('forum_post')->fetch_threadpost_by_tid_invisible($item['tid']);

		$data = $this->relation_item($item,$current);
		return $data;
	}
        public function post_search(){
            $type = trim($this->type);
            $type = $type?$type:'thread';
            $keyword = trim($this->wd);
            $page = $this->pageIndex;
            $page = $page<1?1:$page;
            $perpage = !isset($this->pageSize)||intval($this->pageSize)<1?10:intval($this->pageSize);
            $start = ($page-1)*$perpage;
            
            $list = array();
            if($type == 'article'){
                $articles = $this->searchArticle($keyword,$start);
                if($articles){
                    foreach ($articles as $k => $a){
                        $list[$k]['type'] = "article";
                        unset($a['content']);
                        $list[$k]['item'] = $a;
                    }
                }
                $data['list'] = $list;
                $this->success_result($data);
            }
            $threads = $this->searchThread($keyword,$start,$perpage);
            if($threads){
                foreach ($threads as $k=>$t){
                    $list[$k]['type'] = "thread";
                    $tmp = $this->getItem($t['tid']);
                    $tmp['message'] = $tmp['summary'];
                    $tmp['forumName'] = $t['forumName']?$t['forumName']:'';
                    $list[$k]['item'] = $tmp;
                }
            }
            $data['list'] = $list;
            $this->success_result($data);
        }
        public function post_item()
	{
		global $_G;
                if(!$this->uid){
                    $this->return_status(111);//token 失败未登陆
                }
                $uid = $_G['uid'] = $this->uid;
                
		$fid = intval($this->fid);
		$clientip = $_G['clientip'];
		$subject = urldecode($this->subject);
		$message = htmlspecialchars_decode($this->bbcode_encode($this->message));
                $isanonymous = intval($this->isanonymous);
                $hiddenreplies = intval($this->hiddenreplies);
		if(!$fid || !$uid || empty($clientip) || empty($subject) || empty($message)){
			$this->return_status(403);
		}
                if(!preg_match('%utf%is', $this->charset)){
			if(function_exists('iconv')){
				$subject = iconv('UTF-8', $this->charset . '//ignore', $subject);
				$message = iconv('UTF-8', $this->charset . '//ignore', $message);
			}else{
				$subject = mb_convert_encoding($subject, $this->charset, 'UTF-8');
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
			$this->return_status(405,'post_forum_newthread_nopermission');
		}
		if(!$_G['uid'] && ($_G['setting']['need_avatar'] || $_G['setting']['need_email'] || $_G['setting']['need_friendnum'])) {
			$this->return_status(405,'抱歉，您尚未登录，没有权限在该版块发帖');
		}

		loadcache(array('bbcodes_display', 'bbcodes', 'smileycodes', 'smilies', 'smileytypes', 'domainwhitelist', 'albumcategory'));
		$modthread = C::m('forum_newthread',$fid);
		$bfmethods = $afmethods = array();

		$params = array(
			'subject' => $subject,
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
                $params['isanonymous'] = $isanonymous;
                $params['hiddenreplies'] = $hiddenreplies;
		$threadsorts = $modthread->forum('threadsorts');
		if(!is_array($threadsorts)){
			$threadsorts = array(
				'expiration'=>array()
			);
			$modthread->forum('threadsorts',$threadsorts);
		}
                $_G['group']['allowanonymous'] = $_G['forum']['allowanonymous'] || $_G['group']['allowanonymous'] ? 1 : 0;
		$return = $modthread->newthread($params);
		$tid = $modthread->tid;
		$pid = $modthread->pid;
		$item = c::t('forum_thread')->fetch_by_tid_displayorder($tid);
		
		$current = c::t('forum_post')->fetch_threadpost_by_tid_invisible($item['tid']);

		$data = $this->relation_item($item,$current);
		$this->success_result($data);
	}
        public function post_edit(){
            $this->put_item();
        }
        public function post_delete(){
            $this->delete_item();
        }
        public function put_item()
	{
		global $_G;
		$fid = intval($this->fid);
		$uid = intval($this->uid);
		$tid = intval($this->tid);
		$clientip = $this->clientip;
		$subject = urldecode($this->subject);
		$message = htmlspecialchars_decode($this->bbcode_encode($this->message));

		if(!$fid || !$uid || !$tid || empty($clientip) || empty($subject) || empty($message)){
			$this->return_status(403);
		}
                if(!preg_match('%utf%is', $this->charset)){
			if(function_exists('iconv')){
				$subject = iconv('UTF-8', $this->charset . '//ignore', $subject);
				$message = iconv('UTF-8', $this->charset . '//ignore', $message);
			}else{
				$subject = mb_convert_encoding($subject, $this->charset, 'UTF-8');
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

		$thread = c::t('forum_post')->fetch_threadpost_by_tid_invisible($tid);
		$modpost = C::m('forum_newpost', $tid, $thread['pid']);


		$params = array(
			'subject' => $subject,
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
		C::t('forum_thread')->update($tid,array('subject'=>$subject));
		$pid = $modpost->pid;

		$item = c::t('forum_thread')->fetch_by_tid_displayorder($tid);

		$data = $this->relation_item($item,$thread);

		$this->success_result($data);
	}
        public function post_followings(){
            global $_G;
            if(!$this->uid){
                $this->return_status(111);//token 失败未登陆
            }
            $_G['uid'] = $this->uid;
            $page = $this->pageIndex;
            $page = $page<1?1:$page;
            $perpage = !isset($this->pageSize)||intval($this->pageSize)<1?10:intval($this->pageSize);
            $start = ($page-1)*$perpage;
            
            
            $followings = c::t('bbssdk_forum')->fetch_followings($_G['uid'],$start,$perpage);
            $list = array();
            if($followings){
                $gids = array();
                foreach ($followings as $k=>$item) {
                        array_push($gids, $item['fid']);
                }
                if(!empty($gids)) {
                    $gforumnames = C::t('forum_forum')->fetch_all_name_by_fid($gids);
                    foreach($gforumnames as $fid => $val) {
                            $forumnames[$fid] = $val;
                    }
                }
                foreach ($followings as $k=>$f){
                    $item = c::t('forum_thread')->fetch_by_tid_displayorder($f['tid']);
                    $tmp  = $this->relation_item($item,$f);
                    $tmp['forumName'] = isset($forumnames[$item['fid']])?$forumnames[$item['fid']]['name']:'';
                    $list[] = $tmp;
                }
            }
            $data['threads'] = $list;
            $this->success_result($data);
        }
        public function delete_item()
	{
		$fid = intval($this->fid);
		$tid = intval($this->tid);
		$uid = intval($this->uid);
		$clientip = $this->clientip;

		if(!$fid || !$tid || !$uid || empty($clientip)){
			$this->return_status(403);
		}


		$thread = c::t('forum_post')->fetch_threadpost_by_tid_invisible($tid);

		if(empty($thread)){
			$this->return_status(405,'帖子已删除');
		}

		if($thread['authorid'] != $uid){
			$this->return_status(405,'抱歉，用户不能删除其他人的帖子');
		}

		$pid = $thread['pid'];
		$modpost = C::m('forum_newpost', $tid, $pid);
		
		$param = array('fid' => $fid, 'tid' => $tid, 'pid' => $pid);	
		
		$result = $modpost->deletepost($param);

		$this->success_result('删除成功');
	}
	protected function relation_item($item, $current)
	{
		global $_G;
		try{
			require_once libfile('function/discuzcode');
			$actItem = array();
			if(is_array($item)){
				$actItem = array(
					'tid' => (int)$item['tid'],
					'fid' => (int)$item['fid'],
					'subject' => $item['subject'],
					'author' => $item['author'],
					'authorId' => (int)$current['authorid'],
					'createdOn' => (int)$item['dateline'],
					'useip' => $current['useip'],
					'views' => (int)$item['views'],
					'heats' => (int)$item['heats'],
					'replies' => (int) $item['replies'],
                                        'phoneReplies' => (int)$item['replies'],
                                        'phoneViews' => (int)$item['views'],
					'avatar' => avatar($item['authorid'],'middle',1),
					'displayOrder' => (int) $item['displayorder'],
					'digest' => (int) $item['digest'],
					'highLight' => (int) $item['highlight'],
					'lastpost' => (int) $item['lastpost'],
					'lastposter' => $item['lastposter'],
                                        'favtimes' => (int) $item['favtimes'],
                                        'recommend_add' => (int) $item['recommend_add'],
                                        'recommend_sub' => (int) $item['recommend_sub'],
                                        'recommends' => (int) $item['recommends'],
                                        'status' =>(int)$item['status'],
                                        'hiddenreplies' => getstatus($item['status'], 2),
                                        'threadurl'=> get_site_url().'plugin.php?id=bbssdk:share&tid='.(int)$item['tid'],
					'message' => isset($current['mdtype']) && $current['mdtype'] == 1 ? $this->MarkdownToHtml->transform($current['message']) : discuzcode($current['message'], $current['smileyoff'], $current['bbcodeoff'], $current['htmlon']),
				);
				$attachment = array();
                                $actItem['message'] = preg_replace("%\[media\](.*)\[\/media\]%isU", "<iframe src='$1'  width=100% name='video' frameborder=0 'allowfullscreen'></iframe>", $actItem['message']);
                                
                                $_post = C::t('forum_post')->fetch('tid:'.$_G['tid'], $current['pid']);
                                
                                $message = preg_replace("/\[[^\]]+\]\s*(.*?)\s*\[\/[^\]]+\]/is", '', $actItem['message']);
                                $actItem['summary'] = htmlspecialchars_decode(cutstr(strip_tags(str_replace('&nbsp;', ' ', $message)), 80));
				preg_match_all("%\[attach\]\s*(\d*)\[%is", $current['message'],$matches);
				foreach ($matches[1] as $it) {
					$thisItem = C::t('forum_attachment_n')->fetch('tid:'.$item['tid'], $it);
					if(!empty($thisItem)) $attachment[$it] = $thisItem;
				}

				$files = C::t('forum_attachment_n')->fetch_all_by_id('tid:'.$item['tid'], 'tid', $item['tid'], 'dateline', 0);
//                                print_r($current['message']);
//                                preg_match_all("%<img\s+src=\'(.*)\'>%is", $current['message'],$matches);
//                                print_r($matches);
				if(count($files) > 0){
					foreach ($files as $key => $itm) {
						if(empty($attachment[$key]))
							$attachment[$key] = $itm;
					}
				}
                                $actItem['images'] = array();
				foreach ($attachment as $j => $obj) {
					$isused = false;
					$actItem['message'] = preg_replace_callback("%\[attach\]{$j}\[\/attach\]%is", 
						function($matches) use ( $obj, &$isused ,&$actItem){
							if((int)$obj['isimage'] == 1 || preg_match('%\.(gif|jpg|png|jpeg)$%is', $obj['filename'])){
								$isused = true;
                                                                $actItem['images'][] = check_url($obj['attachment']);
								return "<img src='".check_url($obj['attachment'])."'>";
							}else{
								return '';
							}
						}, $actItem['message']);
					
					// 假如不是图片
					if ( !$isused && (int)$obj['isimage'] == 0 ){
						$actItem['attachment'][] = array(
								'aid' => (int)$obj['aid'],
								'filesize' => (int)$obj['filesize'],
								'filename' => $obj['filename'],
								'dateline' => (int)$obj['dateline'],
								'readperm' => (int)$obj['readperm'],
								'isimage' => (int)$obj['isimage'],
								'width' => (int)$obj['width'],
								'price' => (int)$obj['price'],
								'uid' => (int)$obj['uid'],
								'url' => check_url($obj['attachment'])
						);
					}
				}

				try{
					$actItem['message'] = message_filter($actItem['message']);
                                        preg_match_all("%<img[^>]*src=['\"]((?!.*smilieid)[^<]*)['\"][^>]*>%isU", $actItem['message'],$images);
                                        if(isset($images[1])&&$images[1]){
                                            $actItem['images'] = array_merge($actItem['images'], $images[1]);
                                        }
                                        $actItem['images'] = array_values(array_flip(array_flip($actItem['images'])));
				}catch(Exception $e){
					try{
						$actItem['message'] = message_filter($actItem['message']);
					}catch(\Exception $e){
						throw new Exception('relation Markdown Error:'.$e,1);
					}
				}
			}
			return $actItem;
		}catch(\Exception $e){
			write_log('relation_item Error:'.$e);
			$this->return_status(500);
		}
	}
        protected function searchArticle($keyword,$start=0,$pagesize=10) {
            $sql = "select a.*,b.* from ".DB::table('portal_article_content')." a left join ".DB::table('portal_article_title')." b on a.aid=b.aid where b.status=0 and (b.title like '%$keyword%' or a.content like '%$keyword%')"
                    . "order by b.aid desc limit " . $start. ',' . $pagesize;
            $results = DB::fetch_all($sql);
//            print_r($results);
            return $results;
        }
        protected function searchThread($keyword,$start=0,$pagesize=10) {
            require_once libfile('function/search');
            require_once libfile('function/core');
            $orderList = array();
            $sql = "select a.*,b.message,c.name as fname from ".DB::table('forum_thread')." a LEFT JOIN ".DB::table('forum_post')." b on a.tid=b.tid and b.first=1 left join ".DB::table('forum_forum')." c on a.fid = c.fid where a.displayorder >= 0 and (a.`subject` like '%$keyword%' or b.message like '%$keyword%')";
            $sql .= "order by a.dateline desc,a.views desc,a.replies desc limit " . $start. ',' . $pagesize;
            $results = DB::fetch_all($sql);
            foreach ($results as $item) {
                $thisRow = array(
                    'type'=>'forum',
                    'subject'=>bat_highlight($item['subject'],$keyword),
                    'message'=>$item['message'],//$this->getMainContent($item['message'],$keyword),
                    'dateline'=>dgmdate($item['dateline']),
                    'timestamp'=>$item['dateline'],
                    'username'=>$item['author'],
                    'url'=>'/thread-'.$item['tid'].'-1-1.html',
                    'uid'=>$item['authorid'],
                    'fid'=>$item['fid'],
                    'tid'=>$item['tid'],
                    'fname'=>$item['fname'],                    
                    'views'=>$item['views'],
                    'replies'=>$item['replies'],
                    'forumName'=>$item['fname']
                );
                array_push($orderList,$thisRow);
            }
            return $orderList;
        }
        private function getMainContent($text,$keyword)
	{
        require_once libfile('function/search');
        require_once libfile('function/home');
        $text = preg_replace(array('%<[^>]*>%is','%&nbsp;%','%\[[^\]]*\]%i'),'',$text);
        $text = bat_highlight($text, $keyword);
        preg_match('%([\S]*<strong><font color=\"#ff0000\">'.$keyword.'</font></strong>[\S]*)%i',$text,$findTxt);
        preg_match('%([\S]*<strong><font color=\"#ff0000\">'.$keyword.'</font></strong>[^>]*)%i',$findTxt[0],$findTxt);
        return $findTxt[0];
	}
}