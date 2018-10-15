<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');

class Report extends BaseCore
{
	function __construct()
	{
		parent::__construct();
	}
        public function post_add(){
            global $_G;
            if(!$this->uid){
                $this->return_status(111);//token 失败未登陆
            }
            $uid = $_G['uid'] = $this->uid;

            $user = getuserbyuid($_G['uid'], 1);
            $_G['username'] = $user['username'];
            $rtype = $this->rtype;
            $rid = intval($this->rid);
            $fid = intval($this->fid);
            $default_url = array(
                    'user' => 'home.php?mod=space&uid=',
                    'post' => 'forum.php?mod=redirect&goto=findpost&ptid=0&pid=',
                    'thread' => 'forum.php?mod=viewthread&tid=',
                    'group' => 'forum.php?mod=group&fid=',
                    'album' => 'home.php?mod=space&do=album&uid='.$uid.'&id=',
                    'blog' => 'home.php?mod=space&do=blog&uid='.$uid.'&id=',
                    'pic' => 'home.php?mod=space&do=album&uid='.$uid.'&picid='
            );
            $url = '';
            if($rid && !empty($default_url[$rtype])) {
                $url = $default_url[$rtype].intval($rid);
            } else {
                $this->return_status(403,'rid为空或rtype不存在');
//                $url = addslashes(dhtmlspecialchars(base64_decode($_GET['url'])));
//                $url = preg_match("/^http[s]?:\/\/[^\[\"']+$/i", trim($url)) ? trim($url) : '';
            }
//            if(empty($url) || empty($_G['inajax'])) {
//                    showmessage('report_parameters_invalid');
//            }
            $urlkey = md5($url);
            $message = censor(cutstr(dhtmlspecialchars(trim($this->message)), 200, ''));
            $message = $_G['username'].'&nbsp;:&nbsp;FROM.BBSSDK'.rtrim($message, "\\");
            if($reportid = C::t('common_report')->fetch_by_urlkey($urlkey)) {
                    C::t('common_report')->update_num($reportid, $message);
            } else {
                    loadcache('setting');
                    $data = array('url' => $url, 'urlkey' => $urlkey, 'uid' => $_G['uid'], 'username' => $_G['username'], 'message' => $message, 'dateline' => TIMESTAMP);
                    if($fid) {
                            $data['fid'] = $fid;
                    }
                    C::t('common_report')->insert($data);
                    $report_receive = unserialize($_G['setting']['report_receive']);
                    $moderators = array();
                    if($report_receive['adminuser']) {
                            foreach($report_receive['adminuser'] as $touid) {
                                    notification_add($touid, 'report', 'new_report', array('from_id' => 1, 'from_idtype' => 'newreport'), 1);
                            }
                    }
                    if($fid && $rtype == 'post') {
                            foreach(C::t('forum_moderator')->fetch_all_by_fid($fid, false) as $row) {
                                    $moderators[] = $row['uid'];
                            }
                            if($report_receive['supmoderator']) {
                                    $moderators = array_unique(array_merge($moderators, $report_receive['supmoderator']));
                            }
                            foreach($moderators as $touid) {
                                    $touid != $_G['uid'] && !in_array($touid, $report_receive) && notification_add($touid, 'report', 'new_post_report', array('fid' => $fid, 'from_id' => 1, 'from_idtype' => 'newreport'), 1);
                            }
                    }
            }
            $this->return_status(200,'举报成功');
        } 
}