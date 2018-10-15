<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');
require_once 'table/table_bbssdk_menu.php';

class Menu extends BaseCore
{
	function __construct()
	{
		parent::__construct();
	}

	public function post_setting()
	{
		global $_G;
		$actset = $this->setting;
		$iconlevels = array();
		foreach (array_values($actset['heatthread']['iconlevels']) as $item) {
			array_push($iconlevels, intval($item));
		}
                $setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
                $setting = (array)unserialize($setting['bbssdk_setting']);
		sort($iconlevels);
		$setting = array(
			'iconlevels' => $iconlevels,
			'censoruser'=>$actset['censoruser'],
			'floodctrl'=>$actset['floodctrl'],
			'need_email'=>$actset['need_email'],
			'need_avatar'=>$actset['need_avatar'],
			'strongpw'=>is_array($actset['strongpw']) ? $actset['strongpw'] : ( $actset['strongpw'] > 0 ? array($actset['strongpw']) : array() ),
			'regverify'=>$actset['regverify'],
			'charset'=>$_G['charset'],
                        'portalstatus'=>(int)$actset['portalstatus'],
                        'bbssdk_init'=> isset($setting['init'])?$setting['init']:1,//1论坛  2论坛+门户
                        'bbssdk_version'=>$_G['setting']['plugins']['version']['bbssdk'],
                        "signurl"=> get_site_url().'plugin.php?id=bbssdk:sign',//签到页面地址
		);
		$this->success_result($setting);
	}
	public function post_list()
	{
            global $_G;
            $fup = intval($_REQUEST['fup']);		

            $pagesize = intval($_REQUEST['pagesize']);
            $pagesize = $pagesize ? $pagesize : 0;
            if( $pagesize > 50) $pagesize = 10;

            $page = intval($_REQUEST['page'])>0 ? intval($_REQUEST['page']) : 1;
            $start = ($page - 1) * $pagesize;
            
            $list = array();
            $flist = $this->_menu(0);
            if($flist){
                foreach ($flist as $l){
                    $clist = $this->_menu($l['fid'],$l['name']);
                    if($clist){
                        $list = array_merge($list,$clist);
                    }
                }
            }
            $data['forums'] = $list;

            $this->success_result($data);
	}
        private function _menu($fup,$groupName=''){
            $list = $t = array();
            $menus = c::t('bbssdk_menu')->fetch_all_forum($fup,0,0);
            foreach ($menus as $key => $item) {
                $item['allowPost'] = in_array($_G['groupid'], explode("\t", $item['postperm']))||empty($item['postperm'])?1:0;
                $item['allowReply'] = in_array($_G['groupid'], explode("\t", $item['replyperm']))||empty($item['replyperm'])?1:0;
                $item['viewperm'] = in_array($_G['groupid'], explode("\t", $item['viewperm']))||empty($item['viewperm'])?1:0;
                $t = $this->relation_item($item);
                $t['groupName'] = $groupName;
                if($t['status']==1){
                    $list[] = $t;
                }
            }
            return $list;
        }
        public function get_item()
	{
		$fid = intval($_REQUEST['fid']);
		if(!$fid) $this->return_status(403);

		$data = c::t('bbssdk_menu')->fetch_all_by_fid($fid);

		$data = $this->relation_item($data[0]);

		$this->success_result($data);
	}

	public function relation_item($item)
	{
            global $_G;
            $newItem = array();
		if(is_array($item))
		{
			$newItem = array(
				'name' => $item['name'],
				'fid' => (int)$item['fid'],
				'fup' => (int)$item['fup'],
				'status' => empty($item['redirect']) ? (int) $item['status'] : 0,
				'displayOrder' => (int)$item['displayorder'],
				'type' => $item['type'],
                                'description'=>$item['description'],
                                'viewperm' => (int)$item['viewperm'],
                                'allowPost' => (int)$item['allowPost'],
                                'allowReply' => (int)$item['allowReply'],
                                'allowAnonymous'=>(int)$item['allowanonymous'],
                                'threads'=>(int)$item['threads'],
                                'posts'=>(int)$item['posts'],
                                'forumPic'=>$item['icon']?$_G['siteurl'].'data/attachment/common/'.$item['icon']:'',
                                'todayposts'=>(int)$item['todayposts']
			);
		}
		return $newItem;
	}
}