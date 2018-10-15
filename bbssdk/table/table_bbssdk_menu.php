<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_bbssdk_menu extends discuz_table
{
	public function __construct()
	{
		$this->_table = "forum_forum";
		$this->_pk = "fid";

		parent::__construct();
	}

	public function count_by_fup($fup) {
		return DB::result_first("SELECT COUNT(*) FROM %t WHERE fup=%d", array($this->_table, $fup));
	}

	public function fetch_all_forum($fup,$start=0,$limit=0) {
		$fupsql = intval($fup) ? 'f.'.DB::field('fup', $fup) : 'f.fup = 0';
		return DB::fetch_all("SELECT ff.*, f.* FROM ".DB::table($this->_table)." f LEFT JOIN ".DB::table('forum_forumfield')." ff ON ff.fid=f.fid WHERE (ff.redirect='' or ff.redirect is null) and $fupsql ORDER BY f.type, f.displayorder".DB::limit($start, $limit));
	}

	public function fetch_all_by_fid($fid){
		$fidsql = 'f.'. DB::field( 'fid', intval($fid) );
		return DB::fetch_all("SELECT ff.*, f.* FROM ".DB::table($this->_table)." f LEFT JOIN ".DB::table('forum_forumfield')." ff ON ff.fid=f.fid WHERE $fidsql ORDER BY f.type, f.displayorder");
	}
}