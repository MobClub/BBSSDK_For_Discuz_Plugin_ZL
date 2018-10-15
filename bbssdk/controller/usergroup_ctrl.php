<?php
if(!defined('DISABLEDEFENSE'))  exit('Access Denied!');

class Usergroup extends BaseCore
{
	function __construct()
	{
		parent::__construct();
	}

	public function post_list()
	{
		$pagesize = intval($_REQUEST['pagesize']);
		if( $pagesize <10 || $pagesize > 30) $pagesize = 10;
		
		$page = intval($_REQUEST['page'])>0 ? intval($_REQUEST['page']) : 1;
		$start = ($page - 1) * $pagesize;

		$data['total_count'] =  DB::result_first("select count(*) from ".DB::table('common_usergroup'));
		$data['pagesize'] = $pagesize;
		$data['currpage'] = $page;
		$total_page = ceil($data['total_count']/$pagesize);
		$data['nextpage'] = $page+1 <= $total_page ? $page+1 : $total_page;
		$data['prepage'] = $page-1>0 ? $page-1 : 1;

		$list = array();

		if($data['currpage'] <= $data['nextpage'] ){
			$postlist = DB::fetch_all("select * from ".DB::table('common_usergroup')." a LEFT JOIN ".DB::table('common_usergroup_field')." b on a.groupid=b.groupid LIMIT $pagesize OFFSET $start");
			foreach ($postlist as $k=>$item) {
				$list[] = $this->relation_item($item);
			}
		}
				
		$data['list'] = $list;

		$this->success_result($data);
	}

	public function get_item()
	{
		$groupid = intval($_REQUEST['groupid']);

		if(!$groupid) $this->return_status(403);

		$data = DB::fetch_all("select * from ".DB::table('common_usergroup')." a LEFT JOIN ".DB::table('common_usergroup_field')." b on a.groupid=b.groupid where a.groupid in($groupid)");

		$data = $this->relation_item($data[0]);

		$this->success_result($data);
	}

	protected function relation_item($item)
	{
		$actItem = array();
		foreach ($item as $key => $value) {
			if(preg_match('%\d+%is', $value))
			{
				$actItem[$key] = (int) $value;
			}

			else if(preg_match('%\s+%is', $value))
			{
				$actItem[$key] = null;
			}

			else if(preg_match('%\S+%', $value))
			{
				$actItem[$key] = (string) $value;
			}
			else{
				$actItem[$key] = $value;
			}
		}
		unset($actItem['groupid1']);
		return $actItem;
	}
}