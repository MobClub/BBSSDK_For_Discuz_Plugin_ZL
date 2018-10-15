<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_bbssdk_thread extends discuz_table
{
	public function __construct()
	{
		$this->_table = "forum_thread";
		$this->_pk = "tid";

		parent::__construct();
	}

	public function count_by_fid($fid)
	{
		return $fid ? (int) DB::result_first("SELECT COUNT(*) FROM %t WHERE fid=%d", array($this->_table, $fid)) : 0;
	}

	public function range_list($where , $start = 0, $limit = 0, $sort = 'desc')
	{
		$sql = "select * from ".DB::table($this->_table) . ( empty($where) ? '' : ' where ' . $where ). DB::limit($start, $limit);
		return DB::fetch_all($sql);
	}

}