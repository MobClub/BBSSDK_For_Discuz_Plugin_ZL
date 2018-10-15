<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
$pluginName = 'bbssdk';
$sql = "drop table  if exists `" . DB::table('bbssdk_oauth') . "`";
DB::query($sql);
C::t('common_setting')->update_batch(array('bbssdk_setting'=>array()));

$finish = TRUE;