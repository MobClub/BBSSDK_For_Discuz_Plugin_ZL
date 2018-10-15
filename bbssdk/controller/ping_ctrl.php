<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');
require_once 'table/table_bbssdk_favorite_sync.php';
require_once 'table/table_bbssdk_menu_sync.php';
require_once 'table/table_bbssdk_member_sync.php';
require_once 'table/table_bbssdk_forum_sync.php';
require_once 'table/table_bbssdk_comment_sync.php';
require_once 'table/table_bbssdk_notification_sync.php';
require_once 'table/table_bbssdk_portal_article_sync.php';
require_once 'table/table_bbssdk_portal_category_sync.php';
require_once 'table/table_bbssdk_portal_comment_sync.php';

class Ping extends BaseCore
{
	function __construct()
	{
		parent::__construct();
	}
	public function get_new()
	{
            $t = intval($_GET['t']);
            //收藏
            $favorites = $syncids = array();
            $favs = c::t('bbssdk_favorite_sync')->unsync_list_by_time($t,100);
            if($favs){
                foreach ($favs as $fav){
                    array_push($syncids, $fav['syncid']);
                    
                    $fav['favid']*=$fav['flag']==3?-1:1;
                    array_push($favorites, $fav['favid']);
                }
                c::t('bbssdk_favorite_sync')->change_status($syncids);
            }
            //论坛版块
            $fids = $syncids = array();
            $menus = c::t('bbssdk_menu_sync')->unsync_list_by_time($t,100);
            if($menus){
                foreach ($menus as $menu){
                    array_push($syncids, $menu['syncid']);
                    
                    $menu['fid']*=$menu['flag']==3?-1:1;
                    array_push($fids, $menu['fid']);
                }
                c::t('bbssdk_menu_sync')->change_status($syncids);
            }
            //用户组
            $groupids = $syncids = array();
            $usergroups = c::t('bbssdk_usergroup_sync')->unsync_list_by_time($t,100);
            if($usergroups){
                foreach ($usergroups as $usergroup){
                    array_push($syncids, $usergroup['syncid']);
                    
                    $usergroup['groupid']*=$usergroup['flag']==3?-1:1;
                    array_push($groupids, $usergroup['groupid']);
                }
                c::t('bbssdk_usergroup_sync')->change_status($syncids);
            }
            $uids = $syncids = array();
            $members = c::t('bbssdk_member_sync')->unsync_list_by_time($t,100);
            if($members){
                foreach ($members as $member){
                    array_push($syncids, $member['syncid']);
                    
                    $member['uid']*=$member['flag']==3?-1:1;
                    array_push($uids, $member['uid']);
                }
                c::t('bbssdk_member_sync')->change_status($syncids);
            }
            //主题
            $threads = $syncids = array();
            $forums = c::t('bbssdk_forum_sync')->unsync_list_by_time($t,100);
            if($forums){
                foreach ($forums as $forum){
                    array_push($syncids, $forum['syncid']);
                    
                    $forum['fid']*=$forum['flag']==3?-1:1;                    
                    array_push($threads, $forum['fid'].'#'.$forum['tid']);
                }
                c::t('bbssdk_forum_sync')->change_status($syncids);
            }
            //评论
            $posts = $syncids = array();
            $comments = c::t('bbssdk_comment_sync')->unsync_list_by_time($t,100);
            if($comments){
                foreach ($comments as $comment){
                    array_push($syncids, $comment['syncid']);
                    
                    $comment['fid']*=$comment['flag']==3?-1:1;                    
                    array_push($posts, $comment['fid'].'#'.$comment['tid'].'#'.$comment['pid']);
                }
                c::t('bbssdk_comment_sync')->change_status($syncids);
            }
            //通知
            $notices = $syncids = array();
            $notifications = c::t('bbssdk_notification_sync')->unsync_list_by_time($t,100);
            if($notifications){
                foreach ($notifications as $n){
                    array_push($syncids, $n['syncid']);
                    
                    $n['noticeid']*=$n['flag']==3?-1:1;                    
                    array_push($notices, $n['noticeid']);
                }
                c::t('bbssdk_notification_sync')->change_status($syncids);
            }
            //门户文章
            $portal_article = $syncids = array();
            $articles = c::t('bbssdk_portal_article_sync')->unsync_list_by_time($t,100);
            if($articles){
                foreach ($articles as $article){
                    array_push($syncids, $article['syncid']);
                    
                    $article['aid']*=$article['flag']==3?-1:1;                    
                    array_push($portal_article, $article['aid']);
                }
                c::t('bbssdk_portal_article_sync')->change_status($syncids);
            }
            //门户栏目
            $portal_category = $syncids = array();
            $portal_cats = c::t('bbssdk_portal_category_sync')->unsync_list_by_time($t,100);
            if($portal_cats){
                foreach ($portal_cats as $portal_cat){
                    array_push($syncids, $portal_cat['syncid']);
                    
                    $portal_cat['catid']*=$portal_cat['flag']==3?-1:1;                    
                    array_push($portal_category, $portal_cat['catid']);
                }
                c::t('bbssdk_portal_category_sync')->change_status($syncids);
            }
            //门户评论
            $portal_comment = $syncids = array();
            $portal_cs = c::t('bbssdk_portal_comment_sync')->unsync_list_by_time($t,100);
            if($portal_cs){
                foreach ($portal_cs as $portal_c){
                    array_push($syncids, $portal_c['syncid']);
                    
                    $portal_c['cid']*=$portal_c['flag']==3?-1:1;                    
                    array_push($portal_comment, $portal_c['cid']);
                }
                c::t('bbssdk_portal_comment_sync')->change_status($syncids);
            }
            
            $t = DB::fetch_first('select UNIX_TIMESTAMP(NOW()) as timestamp');
            $data['t']          = $t['timestamp'];
            $data['favorites']  = $favorites;
            $data['forums']     = $fids;
            $data['usergroups'] = $groupids;
            $data['users']      = $uids;
            $data['threads']    = $threads;
            $data['posts']      = $posts;
            $data['notices']    = $notices;
            $data['portal_article']  = $portal_article;
            $data['portal_category'] = $portal_category;
            $data['portal_comment']  = $portal_comment;
            $this->success_result($data);
	}
}