<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if(!defined('DISABLEDEFENSE')){
        define('DISABLEDEFENSE', 1);
}
require_once 'lib/function.php';
global $_G;

$aid = intval($_REQUEST['aid']);
$type = intval($_REQUEST['type']);

if(strpos($_SERVER['HTTP_USER_AGENT'], 'obile') === FALSE){
    dheader('location: '.get_site_url().'portal.php?mod=view&aid='.$aid);
}

$article = getdetail($aid);
$category = C::t('portal_category')->fetch($article['catid']);
if($article['allowcomment'] != 1||$category['allowcomment']!=1) {
    $commentslist = array();
}else{
    $commentslist = get_commentslist($aid);
}

C::t('portal_article_count')->increase($aid, array('viewnum'=>1));

$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
$setting = (array)unserialize($setting['bbssdk_setting']);
$appkey  = $setting['appkey'];

$type = $type?$type:1;
if($_G['charset']=='gbk'){
    require_once "h5/portal/theme{$type}/html/share_gbk.html";
}else{
    require_once "h5/portal/theme{$type}//html/share.html"; 
}
function get_commentslist($id){
    $page    = 1;
    $perpage = 10;
    $idtype  = 'aid';

    $perpage = $perpage?$perpage:10;
    if($page<1) $page = 1;
    $start = ($page-1)*$perpage;

    $commentlist = array();
    $csubject = C::t('portal_article_count')->fetch($id);
    if($csubject['commentnum']) {
        $query = C::t('portal_comment')->fetch_all_by_id_idtype($id, $idtype, 'dateline', 'DESC', $start, $perpage);
        foreach($query as $value) {
            $value['avatar'] = avatar($value['uid'],'middle',1);
            $commentlist[] = $value;
        }
    }
    return $commentlist;
}
function getdetail($aid){
    global $_G;
    $content = C::t('portal_article_content')->fetch_by_aid_page($aid, 1);
    require_once libfile('function/blog');
    require_once libfile('function/portal');
    require_once libfile('function/home');
    $content['content'] = blog_bbcode($content['content']);
    $article = C::t('portal_article_title')->fetch($aid);

    $article_count = C::t('portal_article_count')->fetch($aid);
    if($article_count) $article = array_merge($article_count, $article);
    $article['related'] = array();
    if(($relateds = C::t('portal_article_related')->fetch_all_by_aid($aid))) {
            foreach(C::t('portal_article_title')->fetch_all(array_keys($relateds)) as $raid => $value) {
                    $value['uri'] = fetch_article_url($value);
                    if($value['pic']) {
                        $value['pic'] = get_site_url().pic_get($value['pic'], '', $value['thumb'], $value['remote'], 1, 1);
                    }
                    $article['related'][] = $value;
            }
    }
    if($article['pic']) {
        $article['pic'] = get_site_url().pic_get($article['pic'], '', $article['thumb'], $article['remote'], 1, 1);
    }
    return formatArticle($article,$content);
}
function formatArticle($article,$content){
    global $_G;
    $res['catid']      = $article['catid'];
    $res['cid']        = $content['cid'];
    $res['aid']        = $content['aid'];
    $res['title']      = htmlspecialchars_decode($article['title']);
    $res['author']     = htmlspecialchars_decode($article['username']);
    $res['authorid']   = $article['uid'];
    $res['avatar']     = avatar($article['uid'],'middle',1);
    $res['dateline']   = $content['dateline'];
    $res['viewnum']    = $article['viewnum'];
    $res['commentnum'] = $article['commentnum'];
    $res['sharetimes'] = $article['sharetimes'];
    $res['favtimes']   = $article['favtimes'];
    $res['summary']    = $article['summary'];
    $res['content']    = htmlspecialchars_decode(message_filter($content['content'],1));
    $res['related']   =  $article['related'];
    $res['pic']        = $article['pic'];
    $res['allowcomment']= $article['allowcomment'];
    $res['status']     = $article['status'];
    $res['click1']     = $article['click1'];
    $res['click2']     = $article['click2'];
    $res['click3']     = $article['click3'];
    $res['click4']     = $article['click4'];
    $res['click5']     = $article['click5'];
    $res['content'] = preg_replace("%<div[^<]*qb-sougou-search.*div>%isU", '', $res['content']);
    $res['attachment'] = $attachs = array();         
    foreach(C::t('portal_attachment')->fetch_all_by_aid($res['aid']) as $value) {
        if(!$value['isimage']) {
            $value['url'] = $value['remote'] ? $_G['setting']['ftp']['attachurl'].'portal/'.$value['attachment'] : rtrim(get_site_url(),'/').'/data/attachment/portal/'.$value['attachment'];
            $attachs[] = $value;

        } 
    }
    $res['attachment'] = $attachs;
    return $res;
}

function formatDate($timestamp){
    global $_G;
    $now  = time();
    $diff = $now-$timestamp;
    if($diff<60){
        $r = '刚刚';
    }else if($diff<3600){
        $r = floor($diff/60).'分钟';
    }else if($diff<86400){
        $r = floor($diff/3600).'小时';
    }else{
        $r = date('m-d H:i',$timestamp);
    }
    return $_G['charset']=='gbk'?diconv($r,'utf-8',$_G['charset']):$r;
}
