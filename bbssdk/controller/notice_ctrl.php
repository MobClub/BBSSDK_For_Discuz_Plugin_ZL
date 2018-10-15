<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');

class Notice extends BaseCore
{
	function __construct()
	{
		parent::__construct();
	}
        private function _init_notice($uid){
            global $_G;
            $_G['uid'] = intval($uid);
            if(empty($_G['uid'])) {
                $this->return_status(601);
            }
            $data = array();
            $_G['member'] = getuserbyuid($_G['uid'], 1);
           
            if($_G['member']['newprompt']) {
                $p = C::t('common_member_newprompt')->fetch($_G['uid']);
                
                $_G['member']['newprompt_num'] = unserialize($p['data']);
                $_G['member']['category_num']  = helper_notification::get_categorynum($_G['member']['newprompt_num']);
            }
        }

        public function get_num(){
            global $_G;
            $_G['uid'] = intval($_GET['uid']);
            if(empty($_G['uid'])) {
                $this->return_status(601);
            }
            $this->_init_notice($_G['uid']);
            $data = array();
            if($_G['member']['newprompt']) {
                $data['newpm']     = $_G['member']['newpm'];
                $data['newprompt'] = $_G['member']['newprompt'];
                                
                $data['category_num']  = helper_notification::get_categorynum($_G['member']['newprompt_num']);
                $data['category_num']['follower'] = isset($_G['member']['newprompt_num']['follower'])?$_G['member']['newprompt_num']['follower']:0;
                $data['category_num']['follow']   = isset($_G['member']['newprompt_num']['follow'])?$_G['member']['newprompt_num']['follow']:0;
                
                //处理可能存在的脏数据情况
                $n = 0;
                foreach ($data['category_num'] as $v){
                    $n+= $v;
                }
                $data['newprompt'] = $n;
            }            

            $this->success_result($data);
        }
        public function get_item(){
            global $_G;
            $noticeid = empty($_GET['noticeid'])?0:dintval($_GET['noticeid'], is_array($_GET['noticeid']));
            $result = DB::fetch_all("SELECT * FROM %t WHERE id in(%n)", array('home_notification', $noticeid));
            return $this->success_result(array_map("prepend_site_url",$result));
        }
        public function post_list(){
            require_once libfile('function/home');
            global $_G;
            
            $perpage = empty($_GET['pageSize'])?10:intval($_GET['pageSize']);
            $perpage = mob_perpage($perpage);
            if(!$this->uid){
                $this->return_status(111);//token 失败未登陆
            }
            $_G['uid'] = $this->uid;
            
            $page = empty($_GET['pageIndex'])?0:intval($_GET['pageIndex']);
            if($page<1) $page = 1;
            $start = ($page-1)*$perpage;

            //ckstart($start, $perpage);
            $this->_init_notice($_G['uid']);
            $list = array();
            $mynotice = $count = 0;
            $multi = '';

            if(empty($_G['member']['category_num']['manage']) && !in_array($_G['adminid'], array(1,2,3))) {
                    unset($_G['notice_structure']['manage']);
            }
            $view = (!empty($_GET['view']) && (isset($_G['notice_structure'][$_GET[view]]) || in_array($_GET['view'], array('userapp'))))?$_GET['view']:'mypost';
            $categorynum = $newprompt = array();
            if($view == 'userapp') {

                    space_merge($space, 'status');

                    if($_GET['op'] == 'del') {
                            $appid = intval($_GET['appid']);
                            C::t('common_myinvite')->delete_by_appid_touid($appid, $_G['uid']);
                            showmessage('do_success', "home.php?mod=space&do=notice&view=userapp&quickforward=1");
                    }

                    $filtrate = 0;
                    $count = 0;
                    $apparr = array();
                    $type = intval($_GET['type']);
                    foreach(C::t('common_myinvite')->fetch_all_by_touid($_G['uid']) as $value) {
                            $count++;
                            $key = md5($value['typename'].$value['type']);
                            $apparr[$key][] = $value;
                            if($filtrate) {
                                    $filtrate--;
                            } else {
                                    if($count < $perpage) {
                                            if($type && $value['appid'] == $type) {
                                                    $list[$key][] = $value;
                                            } elseif(!$type) {
                                                    $list[$key][] = $value;
                                            }
                                    }
                            }
                    }
                    $mynotice = $count;

            } else {
                    
                    if(!empty($_GET['ignore'])) {
                            C::t('home_notification')->ignore($_G['uid']);
                    }

//                    foreach (array('wall', 'piccomment', 'blogcomment', 'clickblog', 'clickpic', 'sharecomment', 'doing', 'friend', 'credit', 'bbs', 'system', 'thread', 'task', 'group') as $key) {
//                            $noticetypes[$key] = lang('notification', "type_$key");
//                    }

                    $isread = in_array($_GET['isread'], array(0, 1)) ? intval($_GET['isread']) : 0;
                    $category = $type = '';
                    if(isset($_G['notice_structure'][$view])) {
                            if(!in_array($view, array('mypost', 'interactive'))) {
                                    $category = $view;
                            } else {
                                    $deftype = $_G['notice_structure'][$view][0];
                                    if($_G['member']['newprompt_num']) {
                                            foreach($_G['notice_structure'][$view] as $subtype) {
                                                    if($_G['member']['newprompt_num'][$subtype]) {
                                                            $deftype = $subtype;
                                                            break;
                                                    }
                                            }
                                    }
                                    $type = in_array($_GET['type'], $_G['notice_structure'][$view]) ? trim($_GET['type']) : $deftype;
                            }
                    }
                    $wherearr = array();
                    $new = -1;
                    if(!empty($type)) {
                            $wherearr[] = "`type`='$type'";
                    }

                    $sql = ' AND '.implode(' AND ', $wherearr);


                    $newnotify = false;
                    $count = C::t('home_notification')->count_by_uid($_G['uid'], $new, $type, $category);
                    if($count) {
                            if($new == 1 && $perpage == 30) {
                                    $perpage = 200;
                            }
                            foreach(C::t('home_notification')->fetch_all_by_uid($_G['uid'], $new, $type, $start, $perpage, $category) as $value) {
                                    if($value['new']) {
                                            $newnotify = true;
                                            $value['style'] = 'color:#000;font-weight:bold;';
                                    } else {
                                            $value['style'] = '';
                                    }
                                    $value['rowid'] = '';
                                    if(in_array($value['type'], array('friend', 'poke'))) {
                                            $value['rowid'] = ' id="'.($value['type'] == 'friend' ? 'pendingFriend_' : 'pokeQuery_').$value['authorid'].'" ';
                                    }
                                    if($value['from_num'] > 0) $value['from_num'] = $value['from_num'] - 1;
                                    $list[$value['id']] = $value;
                            }
                    }

                    if($newnotify) {
                            C::t('home_notification')->ignore($_G['uid'], $type, $category, true, true);
//                            if($_G['setting']['cloud_status']) {
//                                    $noticeService = Cloud::loadClass('Service_Client_Notification');
//                                    $noticeService->setNoticeFlag($_G['uid'], TIMESTAMP);
//                            }
                    }
                    helper_notification::update_newprompt($_G['uid'], ($type ? $type : $category));
                    if($_G['setting']['my_app_status']) {
                            $mynotice = C::t('common_myinvite')->count_by_touid($_G['uid']);
                    }
                    if($_G['member']['newprompt']) {
                            $recountprompt = 0;
                            foreach($_G['member']['category_num'] as $promptnum) {
                                    $recountprompt += $promptnum;
                            }
                            $recountprompt += $mynotice;
                            if($recountprompt == 0) {
                                    C::t('common_member')->update($_G['uid'], array('newprompt' => 0));
                            }
                    }
            }
//            $data['total_count'] = '';
//            $data['pagesize']    = $perpage;
//            $data['currpage']    = $page;
//            $data['nextpage']    = $page+1;
//            $data['prepage']     = $page>1?$page-1:1;
            $data['notifications'] = array_map("prepend_site_url",array_values($list));
            $this->success_result($data);
        }
        public function post_read(){
            global $_G;

            if(!$this->uid){
                $this->return_status(111);//token 失败未登陆
            }
            $uid = $_G['uid'] = $this->uid;
            
            
            $noticeid = empty($_GET['noid'])?0:dintval($_GET['noid'], is_array($_GET['noid']));
            $result = DB::fetch_all("SELECT * FROM %t WHERE id in(%n) and uid = %d", array('home_notification', $noticeid,$_G['uid']));
            
            $unread = $type = array();
            if($result){
                foreach ($result as $r){
                    if($r['new']){
                        $unread[] = $r['id'];
                        $type[$r['type']][] = $r['id']; 
                    }
                }
            
                $this->_init_notice($_G['uid']);
                if($unread&&$_G['member']['newprompt_num']) {//计数更新
                    $tmpprompt = $_G['member']['newprompt_num'];
                    $num = 0;
                    $updateprompt = 0;

                    foreach ($type as $t=>$v){
                        if(!empty($tmpprompt[$t])) {
                            $tmpprompt[$t] -= count($v);
                            if($tmpprompt[$t]<=0){
                                unset($tmpprompt[$t]);
                            }
                            $updateprompt = true;
                        }
                    }

                    foreach($tmpprompt as $key => $val) {
                            $num += $val;
                    }
                    if($num) {
                            if($updateprompt) {
                                    C::t('common_member_newprompt')->update($uid, array('data' => serialize($tmpprompt)));
                                    C::t('common_member')->update($uid, array('newprompt'=>$num));
                            }
                    } else {
                            C::t('common_member_newprompt')->delete($_G['uid']);
                            C::t('common_member')->update($_G['uid'], array('newprompt'=>0));
                    }
                }
                if($unread){
                    DB::update('home_notification', array('new'=>0,'from_num'=>0), "id in(". trim(implode(',', $unread),',').")");
                }
            }else{
                $this->return_status(403,'未查询到消息记录');
            }
            $this->return_status(200,'操作成功');
        }
}