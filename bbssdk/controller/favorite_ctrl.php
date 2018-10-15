<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');
require_once 'forum_ctrl.php';

class Favorite extends BaseCore
{
	function __construct()
	{
		parent::__construct();
	}
        /**
         * 通过favid获取内容
         * @param mix(int|array) $favid 
         * @return array
         */
	private function _item($favid){
            global $_G;
            $list = array();
            $idtypes = array('thread'=>'tid', 'forum'=>'fid', 'blog'=>'blogid', 'group'=>'gid', 'album'=>'albumid', 'space'=>'uid', 'article'=>'aid');
            $favid = empty($favid)?0:dintval($favid, is_array($favid));
            $result = DB::fetch_all("SELECT * FROM %t WHERE favid in(%n)", array('home_favorite', $favid));
            if($result){
                $icons = array(
                        'tid'=>get_site_url().'static/image/feed/thread.gif',
                        'fid'=>get_site_url().'static/image/feed/discuz.gif',
                        'blogid'=>get_site_url().'static/image/feed/blog.gif',
                        'gid'=>get_site_url().'static/image/feed/group.gif',
                        'uid'=>get_site_url().'static/image/feed/profile.gif',
                        'albumid'=>get_site_url().'static/image/feed/album.gif',
                        'aid'=>get_site_url().'static/image/feed/article.gif',
                );
                foreach ($result as &$value){
                    $value['icon'] = isset($icons[$value['idtype']]) ? $icons[$value['idtype']] : '';
                    $value['url'] = makeurl($value['id'], $value['idtype'], $value['spaceuid']);
                    $value['description'] = !empty($value['description']) ? nl2br($value['description']) : '';
                    $type = array_flip($idtypes);
                    $value['type'] = $type[$value['idtype']];
                    
                    $value['favid'] = (int)$value['favid'];
                    $value['uid']   = (int)$value['uid'];
                    $value['id']    = (int)$value['id'];
                    $value['dateline'] = (int)$value['dateline'];
                    
                    $list[$value['favid']] = $value;
                    if($value['idtype'] == 'aid') {
                            $articles[$value['favid']] = (int)$value['id'];
                    }
                }
                if(!empty($articles)) {
                            include_once libfile('function/portal');
                            $_urls = array();
                            foreach(C::t('portal_article_title')->fetch_all($articles) as $aid => $article) {
                                    $_urls[$aid] = fetch_article_url($article);
                            }
                            foreach ($articles as $favid => $aid) {
                                    $list[$favid]['url'] = $_urls[$aid];
                            }
                    }
            }
            return array_values($list);
        }
        public function post_add(){
            require_once libfile('function/home');
            global $_G;
            if(!$this->uid){
                $this->return_status(111);//token 失败未登陆
            }
            $_G['uid'] = $this->uid;
//            $_G['uid'] = intval($_GET['uid']);
//            $_POST['description']=$this->description;
//            if(empty($_G['uid'])) {
//                $this->return_status(601);
//            }
            
//            if(!in_array($_GET['type'], array("thread", "forum", "group", "blog", "album", "article", "all"))){
//                $this->return_status(602);
//            }
//            cknewuser();

//            $type = empty($_GET['type']) ? '' : $_GET['type'];
            $type = "thread";
            $id = intval($this->tid);
            $spaceuid = empty($_GET['spaceuid']) ? 0 : intval($_GET['spaceuid']);
            $idtype = $title = $icon = '';
            switch($type) {
                    case 'thread':
                            $idtype = 'tid';
                            $thread = C::t('forum_thread')->fetch($id);
                            $title = $thread['subject'];
                            $icon = get_site_url().'static/image/feed/thread.gif';
                            break;
                    case 'forum':
                            $idtype = 'fid';
                            $foruminfo = C::t('forum_forum')->fetch($id);
                            loadcache('forums');
                            $forum = $_G['cache']['forums'][$id];
                            if(!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t$_G[uid]\t")) {
                                    $title = $foruminfo['status'] != 3 ? $foruminfo['name'] : '';
                                    $icon = get_site_url().'static/image/feed/discuz.gif';
                            }
                            break;
                    case 'blog':
                            $idtype = 'blogid';
                            $bloginfo = C::t('home_blog')->fetch($id);
                            $title = ($bloginfo['uid'] == $spaceuid) ? $bloginfo['subject'] : '';
                            $icon = get_site_url().'static/image/feed/blog.gif';
                            break;
                    case 'group':
                            $idtype = 'gid';
                            $foruminfo = C::t('forum_forum')->fetch($id);
                            $title = $foruminfo['status'] == 3 ? $foruminfo['name'] : '';
                            $icon = get_site_url().'static/image/feed/group.gif';
                            break;
                    case 'album':
                            $idtype = 'albumid';
                            $result = C::t('home_album')->fetch($id, $spaceuid);
                            $title = $result['albumname'];
                            $icon = get_site_url().'static/image/feed/album.gif';
                            break;
                    case 'space':
                            $idtype = 'uid';
                            $_member = getuserbyuid($id);
                            $title = $_member['username'];
                            $unset($_member);
                            $icon = get_site_url().'static/image/feed/profile.gif';
                            break;
                    case 'article':
                            $idtype = 'aid';
                            $article = C::t('portal_article_title')->fetch($id);
                            $title = $article['title'];
                            $icon = get_site_url().'static/image/feed/article.gif';
                            break;
            }
            if(empty($idtype) || empty($title)) {
                    $this->return_status(605);
            }

            $fav = C::t('home_favorite')->fetch_by_id_idtype($id, $idtype, $_G['uid']);
            if($fav) {
                    $this->return_status(606);
            }
            $description = '';
            
            $arr = array(
                    'uid' => intval($_G['uid']),
                    'idtype' => $idtype,
                    'id' => $id,
                    'spaceuid' => $spaceuid,
                    'title' => getstr($title, 255),
                    'description' => getstr($_POST['description'], '', 0, 0, 1),
                    'dateline' => TIMESTAMP
            );
            
            $favid = C::t('home_favorite')->insert($arr, true);
            
//            if($_G['setting']['cloud_status']) {
//                    $favoriteService = Cloud::loadClass('Service_Client_Favorite');
//                    $favoriteService->add($arr['uid'], $favid, $arr['id'], $arr['idtype'], $arr['title'], $arr['description'], TIMESTAMP);
//            }
            
            switch($type) {
                    case 'thread':
                            C::t('forum_thread')->increase($id, array('favtimes'=>1));
                            require_once libfile('function/forum');
                            update_threadpartake($id);
                            break;
                    case 'forum':
                            C::t('forum_forum')->update_forum_counter($id, 0, 0, 0, 0, 1);
                            break;
                    case 'blog':
                            C::t('home_blog')->increase($id, $spaceuid, array('favtimes' => 1));
                            break;
                    case 'group':
                            C::t('forum_forum')->update_forum_counter($id, 0, 0, 0, 0, 1);
                            break;
                    case 'album':
                            C::t('home_album')->update_num_by_albumid($id, 1, 'favtimes', $spaceuid);
                            break;
                    case 'space':
                            C::t('common_member_status')->increase($id, array('favtimes' => 1));
                            break;
                    case 'article':
                            C::t('portal_article_count')->increase($id, array('favtimes' => 1));
                            break;
            }
            $r = $this->_item($favid);
            return $this->success_result($r[0]);//当前版本按一条处理       
        }
        
        public function get_status(){
            global $_G;
            $type = empty($_GET['type']) ? '' : $_GET['type'];
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
            
            $_G['uid'] = intval($_GET['uid']);
            if(empty($_G['uid'])) {
                $this->return_status(601);
            }
            
            $c = array("thread"=>"tid", "forum"=>"fid", "group"=>"gid", "blog"=>"blogid", "album"=>"albumid", "article"=>"aid", "space"=>"uid");
            if(!isset($c[$type])){
                $this->return_status(602);
            }
            $idtype = $c[$type];
            
            $fav = C::t('home_favorite')->fetch_by_id_idtype($id, $idtype, $_G['uid']);
            return $this->success_result($fav);  
        }

        public function post_mine(){
            global $_G;
            if(!$this->uid){
                $this->return_status(111);//token 失败未登陆
            }
            $_G['uid'] = $this->uid;
            
            $page = $this->pageIndex;
            $perpage = !isset($this->pageSize)||intval($this->pageSize)<1?10:intval($this->pageSize);

            $_G['disabledwidthauto'] = 0;

            $start = ($page-1)*$perpage;
            //ckstart($start, $perpage);

//            $idtypes = array('thread'=>'tid', 'forum'=>'fid', 'blog'=>'blogid', 'group'=>'gid', 'album'=>'albumid', 'space'=>'uid', 'article'=>'aid');
//            if(!isset($idtypes[$_GET['type']])&&$_GET['type']!='all'){
//                $this->return_status(602);
//            }

            $data = $list = array();
            $favid = $this->favid;
//            $idtype = isset($idtypes[$_GET['type']]) ? $idtypes[$_GET['type']] : '';
            $idtype = 'tid';
//            $count = C::t('home_favorite')->count_by_uid_idtype($_G['uid'], $idtype, $favid);
//            if($count) {
                    $icons = array(
                            'tid'    => get_site_url().'static/image/feed/thread.gif',
                            'fid'    => get_site_url().'static/image/feed/discuz.gif',
                            'blogid' => get_site_url().'static/image/feed/blog.gif',
                            'gid'    => get_site_url().'static/image/feed/group.gif',
                            'uid'    => get_site_url().'static/image/feed/profile.gif',
                            'albumid'=> get_site_url().'static/image/feed/album.gif',
                            'aid'    => get_site_url().'static/image/feed/article.gif',
                    );
                    $articles = array();
                    $forum = new Forum();
                    foreach(C::t('home_favorite')->fetch_all_by_uid_idtype($_G['uid'], $idtype, $favid, $start,$perpage) as $value) {
//                            $value['icon'] = isset($icons[$value['idtype']]) ? $icons[$value['idtype']] : '';
//                            $value['url'] = makeurl($value['id'], $value['idtype'], $value['spaceuid']);
//                            $value['description'] = !empty($value['description']) ? nl2br($value['description']) : '';
//                            $type = array_flip($idtypes);
//                            $value['type'] = $type[$value['idtype']];
//                            $list[$value['favid']] = $value;
//                            if($value['idtype'] == 'aid') {
//                                    $articles[$value['favid']] = $value['id'];
//                            }
                            if($value['idtype'] == 'tid'){
                                $thread = $forum->getItem($value['id']);
                                $f = C::t('forum_forum')->fetch_info_by_fid($thread['fid']);
                                $thread['forumName'] = $f['name'];
                                $thread['favid'] = (int)$value['favid'];
                                $list[] = $thread;
                            }
                    }
//                    if(!empty($articles)) {
//                            include_once libfile('function/portal');
//                            $_urls = array();
//                            foreach(C::t('portal_article_title')->fetch_all($articles) as $aid => $article) {
//                                    $_urls[$aid] = fetch_article_url($article);
//                            }
//                            foreach ($articles as $favid => $aid) {
//                                    $list[$favid]['url'] = $_urls[$aid];
//                            }
//                    }
                    
//                    $forum->getItem($value['id']);
//            }
            
//            $data['total_count'] = $count;
//            $data['pagesize'] = $perpage;
//            $data['currpage'] = $_GET['page'];
//            $data['nextpage'] = $_GET['page']+1;
//            $data['prepage'] = $_GET['page']>1?$_GET['page']-1:1;
            
            $data['favorites'] = $list;
            
            return $this->success_result($data);
        }
        public function get_item(){
            global $_G;
            $data = $list = array();
            $idtypes = array('thread'=>'tid', 'forum'=>'fid', 'blog'=>'blogid', 'group'=>'gid', 'album'=>'albumid', 'space'=>'uid', 'article'=>'aid');
            $favid = empty($_GET['favid'])?0:dintval($_GET['favid'], is_array($_GET['favid']));
            
            $data['list'] = $this->_item($favid);
            return $this->success_result($data);
        }
        public function post_delete(){
            global $_G;
            if(!$this->uid){
                $this->return_status(111);//token 失败未登陆
            }
            $_G['uid'] = $this->uid;
            $_GET['favorite'] = $this->favid;
            if($_GET['favorite']) {
                $_GET['favorite'] = explode(',', $_GET['favorite']);
                $_GET['favorite'] = dintval($_GET['favorite'], is_array($_GET['favorite']));
                $deletecounter = array();
                $data = C::t('home_favorite')->fetch_all($_GET['favorite']);
                foreach($data as $dataone) {
                        switch($dataone['idtype']) {
                                case 'fid':
                                        $deletecounter['fids'][] = $dataone['id'];
                                        break;
                                default:
                                        break;
                        }
                }
                if($deletecounter['fids']) {
                        C::t('forum_forum')->update_forum_counter($deletecounter['fids'], 0, 0, 0, 0, -1);
                }
                C::t('home_favorite')->delete($_GET['favorite'], false, $_G['uid']);
//                if($_G['setting']['cloud_status']) {
//                        $favoriteService = Cloud::loadClass('Service_Client_Favorite');
//                        $favoriteService->remove($_G['uid'], $_GET['favorite'], TIMESTAMP);
//                }
                $this->return_status(200,'删除成功');
            }
            $this->return_status(403);
        }
}
function makeurl($id, $idtype, $spaceuid=0) {
	$url = '';
	switch($idtype) {
		case 'tid':
			$url = get_site_url().'forum.php?mod=viewthread&tid='.$id;
			break;
		case 'fid':
			$url = get_site_url().'forum.php?mod=forumdisplay&fid='.$id;
			break;
		case 'blogid':
			$url = get_site_url().'home.php?mod=space&uid='.$spaceuid.'&do=blog&id='.$id;
			break;
		case 'gid':
			$url = get_site_url().'forum.php?mod=group&fid='.$id;
			break;
		case 'uid':
			$url = get_site_url().'home.php?mod=space&uid='.$id;
			break;
		case 'albumid':
			$url = get_site_url().'home.php?mod=space&uid='.$spaceuid.'&do=album&id='.$id;
			break;
		case 'aid':
			$url = get_site_url().'portal.php?mod=view&aid='.$id;
			break;
	}
	return $url;
}