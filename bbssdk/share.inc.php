<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if(!defined('DISABLEDEFENSE')){
        define('DISABLEDEFENSE', 1);
}
define('IN_MOBILE', 0);
require_once 'lib/function.php';
global $_G;

$tid = intval($_REQUEST['tid']);

if(strpos($_SERVER['HTTP_USER_AGENT'], 'obile') === FALSE){
    dheader('location: '.get_site_url().'forum.php?mod=viewthread&tid='.$tid);
}
$item = c::t('forum_thread')->fetch_by_tid_displayorder($tid);
$current = c::t('forum_post')->fetch_threadpost_by_tid_invisible($item['tid']);

$thread = relation_item($item, $current);
$posts  = get_list($item['fid'], $tid);
$foruminfo = C::t('forum_forum')->fetch_info_by_fid($item['fid']);

$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
$setting = (array)unserialize($setting['bbssdk_setting']);
$appkey  = $setting['appkey'];

if($_G['charset']=='gbk'){
    require_once 'h5/forum/html/share_gbk.html';
}else{
    require_once 'h5/forum/html/share.html'; 
}

function relation_item($item, $current){
    global $_G;
    try{
        require_once libfile('function/discuzcode');
        $actItem = array();
        if(is_array($item)){
                $md = new Markdown();
                $actItem = array(
                        'tid' => (int)$item['tid'],
                        'fid' => (int)$item['fid'],
                        'subject' => $item['subject'],
                        'author' => $current['author'],
                        'authorid' => (int)$current['authorid'],
                        'dateline' => (int)$item['dateline'],
                        'useip' => $current['useip'],
                        'views' => (int)$item['views'],
                        'heats' => (int)$item['heats'],
                        'replies' => (int) $item['replies'],
                        'avatar' => avatar($item['authorid'],'middle',1),
                        'displayorder' => (int) $item['displayorder'],
                        'digest' => (int) $item['digest'],
                        'highlight' => (int) $item['highlight'],
                        'lastpost' => (int) $item['lastpost'],
                        'lastposter' => $item['lastposter'],
                        'favtimes' => (int) $item['favtimes'],
                        'recommend_add' => (int) $item['recommend_add'],
                        'recommend_sub' => (int) $item['recommend_sub'],
                        'recommends' => (int) $item['recommends'],
                        'threadurl'=> get_site_url().'forum.php?mod=viewthread&tid='.(int)$item['tid'],
                        'hiddenreplies' => getstatus($item['status'], 2),
                        'message' => isset($current['mdtype']) && $current['mdtype'] == 1 ? $md->transform($current['message']) : discuzcode($current['message'], $current['smileyoff'], $current['bbcodeoff'], $current['htmlon']),
                );
                $attachment = array();
                $actItem['message'] = preg_replace("%\[media\](.*)\[\/media\]%isU", "<iframe src='$1' width=100% name='video' frameborder=0 'allowfullscreen'></iframe>", $actItem['message']);
                preg_match_all("%\[attach\]\s*(\d*)\[%is", $current['message'],$matches);
                foreach ($matches[1] as $it) {
                        $thisItem = C::t('forum_attachment_n')->fetch('tid:'.$item['tid'], $it);
                        if(!empty($thisItem)) $attachment[$it] = $thisItem;
                }

                $files = C::t('forum_attachment_n')->fetch_all_by_id('tid:'.$item['tid'], 'tid', $item['tid'], 'dateline', 0);

                if(count($files) > 0){
                        foreach ($files as $key => $itm) {
                                if(empty($attachment[$key]))
                                        $attachment[$key] = $itm;
                        }
                }

                foreach ($attachment as $j => $obj) {
                        $isused = false;
                        $actItem['message'] = preg_replace_callback("%\[attach\]{$j}\[\/attach\]%is", 
                                function($matches) use ( $obj, &$isused ,&$actItem){
                                        if((int)$obj['isimage'] == 1 || preg_match('%\.(gif|jpg|png|jpeg)$%is', $obj['filename'])){
                                                $isused = true;
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
        return_status(500);
    }
}
function get_list($fid,$tid){
    
    $pagesize = intval($_REQUEST['pagesize']);
    $pagesize = $pagesize ? $pagesize : 20;
    if( $pagesize > 50) $pagesize = 20;

    $page = intval($_REQUEST['page'])>0 ? intval($_REQUEST['page']) : 1;
    $start = ($page - 1) * $pagesize;

    $thread = c::t('forum_thread')->fetch_by_tid_displayorder($tid);

    $data['total_count'] =  (int) c::t('forum_post')->count_by_search($thread['posttableid'], $tid, null, null, null, null, null, null, null, null, 0);
    $data['pagesize'] = $pagesize;
    $data['currpage'] = $page;
    $total_page = ceil($data['total_count']/$pagesize);
    $data['nextpage'] = $page+1 <= $total_page ? $page+1 : $total_page;
    $data['prepage'] = $page-1>0 ? $page-1 : 1;

    $list = array();

    if($data['currpage'] <= $data['nextpage'] && $thread['fid'] == $fid){
            $where = 'fid = '. $fid;
            $postlist = c::t('forum_post')->fetch_all_by_tid($thread['posttableid'], $tid, true, '', $start, $pagesize,0);
            foreach ($postlist as $k=>$item) {
                    $list[] = post_relation_item($item);
            }
    }

    return $list;
}
function post_relation_item($item){
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
                                "authorid" => (int)$item['authorid'],
                                "avatar" => avatar($item['authorid'],'middle',1),
                                "dateline" => (int)$item['dateline'],
                                "useip" => $item['useip'],
                                "invisible" => (int) $item['invisible'],
                                "position" => (int)$item['position'],
                                "anonymous"=>$item['anonymous']
                        );
                        $messgeArray = filter_detail($message,$newItem['tid']);
                        $newItem['message'] = $messgeArray[0];
                        $newItem['precomment'] = $messgeArray[1];
                }
                return $newItem;
        }catch(\Exception $e){
                write_log('relation_item Error:'.$e);
                return_status(500);
        }
}
/*
* 筛选前置评论，过滤相对图片地址
*/
function filter_detail($text,$tid)
{
       global $_G;
       $precomment = null;
       $newMessage = preg_replace_callback("%<blockquote>([\s\S]*)</blockquote>%is", 
               function($matches) use (&$precomment) {
                       $actTxt = preg_replace("%<[^>]*>%", '', $matches[0]);
                       $text = explode("\n", $actTxt);
//                       preg_match("%^[\S]*%is", $text[0],$author);
                       $author = explode(' ', $text[0]);
                       preg_match("%\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}%is", $actTxt, $dateline);
                       if(!empty($author[1]) && !empty($text[1])){
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
