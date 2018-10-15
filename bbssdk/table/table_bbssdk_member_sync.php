<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
require_once 'bbssdk_common_sync.php';
class table_bbssdk_usergroup_sync extends bbssdk_common_sync
{
	public function __construct()
	{
		$this->_table = "bbssdk_usergroup_sync";
		$this->_pk = "groupid";

		parent::__construct();
	}
}

class table_bbssdk_member_sync extends bbssdk_common_sync
{
	public function __construct()
	{
		$this->_table = "bbssdk_member_sync";
		$this->_pk = "uid";

		parent::__construct();
	}
}