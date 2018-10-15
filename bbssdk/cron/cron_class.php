<?php
require dirname(dirname(__FILE__)).'/table/table_bbssdk_menu_sync.php';
require dirname(dirname(__FILE__)).'/table/table_bbssdk_forum_sync.php';
require dirname(dirname(__FILE__)).'/table/table_bbssdk_comment_sync.php';
require dirname(dirname(__FILE__)).'/table/table_bbssdk_member_sync.php';

class Cron
{
	private $notify_api;
	function __construct()
	{
		global $_G;
		$this->notify_api = 'http://data.bbssdk.mob.com/';//http://10.18.97.52:9000
	}

	public function menu_sync()
	{
		$total = c::t('bbssdk_menu_sync')->count_by_unsync();
		$pageLimit = 100;

		for($i=1; $i <= ceil($total/$pageLimit); $i++)
		{
			$start = ($i-1) * $pageLimit;
			$menus = c::t('bbssdk_menu_sync')->unsync_list($start, $pageLimit);

			$list = array();
			$ids = array();

			if(count($menus) > 0){
				foreach ($menus as $item) {
					$list[] = array(
						"fid" => $item['fid'],
						'type' => $item['flag'],
						'timestamp' => $item['modifytime'] 
					);
					array_push($ids, $item['syncid']);
				}

				$resp = push_http_query($this->notify_api.'/forum/update/notify',$list);

				$result = json_decode($resp,true);

				if(!empty($resp) && $result['status'] == 200)
					c::t('bbssdk_menu_sync')->change_status($ids);
			}
		}
		write_log('Menu Sync Success At:'.date('Y-m-d H:i:s'),'debug');
	}

	public function forum_sync()
	{
		$total = c::t('bbssdk_forum_sync')->count_by_unsync();
		$pageLimit = 100;

		for($i=1; $i <= ceil($total/$pageLimit); $i++)
		{
			$start = ($i-1) * $pageLimit;
			$forums = c::t('bbssdk_forum_sync')->unsync_list($start, $pageLimit);

			$list = array();
			$ids = array();

			if(count($forums) > 0)
			{
				foreach ($forums as $item) {
					$list[] = array(
						"fid" => $item['fid'],
						'tid' => $item['tid'],
						'type' => $item['flag'],
						'timestamp' => $item['modifytime'] 
					);
					array_push($ids, $item['syncid']);
				}

				$resp = push_http_query($this->notify_api.'/thread/update/notify',$list);

				@$result = json_decode($resp,true);

				if(!empty($resp) && $result['status'] == 200)
					c::t('bbssdk_forum_sync')->change_status($ids);
			}
		}
		write_log('Forum Sync Success At:'.date('Y-m-d H:i:s'),'debug');
	}

	public function comment_sync()
	{
		$total = c::t('bbssdk_comment_sync')->count_by_unsync();
		$pageLimit = 100;

		for($i=1; $i <= ceil($total/$pageLimit); $i++)
		{
			$start = ($i-1) * $pageLimit;
			$comments = c::t('bbssdk_comment_sync')->unsync_list($start, $pageLimit);

			$list = array();
			$ids = array();

			if(count($comments) > 0)
			{
				foreach ($comments as $item) {
					$list[] = array(
						"fid" => $item['fid'],
						'tid' => $item['tid'],
						'pid' => $item['pid'],
						'type' => $item['flag'],
						'timestamp' => $item['modifytime'] 
					);
					array_push($ids, $item['syncid']);
				}

				$resp = push_http_query($this->notify_api.'/post/update/notify',$list);

				$result = json_decode($resp,true);

				if(!empty($resp) && $result['status'] == 200){
					c::t('bbssdk_comment_sync')->change_status($ids);
				}
			}
		}
		write_log('Comment Sync Success At:'.date('Y-m-d H:i:s'),'debug');
	}

	public function member_sync()
	{
		$total = c::t('bbssdk_member_sync')->count_by_unsync();
		$pageLimit = 100;

		for($i=1; $i <= ceil($total/$pageLimit); $i++)
		{
			$start = ($i-1) * $pageLimit;
			$comments = c::t('bbssdk_member_sync')->unsync_list($start, $pageLimit);

			$list = array();
			$ids = array();

			if(count($comments) > 0)
			{
				foreach ($comments as $item) {
					$list[] = array(
						"uid" => $item['uid'],
						'type' => $item['flag'],
						'timestamp' => $item['modifytime'] 
					);
					array_push($ids, $item['syncid']);
				}

				$resp = push_http_query($this->notify_api.'/forum/user/updateNotify',$list);

				$result = json_decode($resp,true);

				if(!empty($resp) && $result['status'] == 200){
					c::t('bbssdk_member_sync')->change_status($ids);
				}
			}
		}
		write_log('Member Sync Success At:'.date('Y-m-d H:i:s'),'debug');
	}

	public function usergroup_sync()
	{
		$total = c::t('bbssdk_usergroup_sync')->count_by_unsync();
		$pageLimit = 100;

		for($i=1; $i <= ceil($total/$pageLimit); $i++)
		{
			$start = ($i-1) * $pageLimit;
			$comments = c::t('bbssdk_usergroup_sync')->unsync_list($start, $pageLimit);

			$list = array();
			$ids = array();

			if(count($comments) > 0)
			{
				foreach ($comments as $item) {
					$list[] = array(
						"groupId" => $item['groupid'],
						'type' => $item['flag'],
						'timestamp' => $item['modifytime'] 
					);
					array_push($ids, $item['syncid']);
				}

				$resp = push_http_query($this->notify_api.'/forum/group/updateNotify',$list);

				$result = json_decode($resp,true);

				if(!empty($resp) && $result['status'] == 200){
					c::t('bbssdk_usergroup_sync')->change_status($ids);
				}
			}
		}
		write_log('Usergroup Sync Success At:'.date('Y-m-d H:i:s'),'debug');
	}
}