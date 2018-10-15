<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class bbssdk_common_sync extends discuz_table
{
	public function __construct()
	{
		parent::__construct();
	}

	public function count_by_unsync()
	{
		return (int) DB::result_first("SELECT COUNT(*) FROM %t WHERE synctime = 0", array($this->_table));
	}

	public function unsync_list($start = 0, $limit = 0, $sort = 'desc')
	{
		if($sort){
			$this->checkpk();
		}
		return DB::fetch_all("select * from ".DB::table($this->_table) . " where synctime = 0 order by ".DB::order($this->_pk,$sort) . DB::limit($start, $limit));
	}
        public function unsync_list_by_time($t = 0, $limit = 0, $sort = 'asc')
	{
		if($sort){
			$this->checkpk();
		}
		return DB::fetch_all("select * from ".DB::table($this->_table) . " where synctime = 0 or  modifytime>= ".$t." order by ".DB::order($this->_pk,$sort) . DB::limit(0, $limit));
	}
	public function change_status($ids)
	{
		$idstirng = is_array($ids) ? join(',',$ids) : $ids;
		if(!preg_match("%[\d\,]+%is", $idstirng))
			return false;
		return DB::query('update '.DB::table($this->_table).' SET synctime=unix_timestamp(now()) where syncid in('.$idstirng.')');
	}
}