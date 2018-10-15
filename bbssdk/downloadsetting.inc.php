<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
if(preg_match('%utf%is', $_G['charset'])){
    require_once 'downloadsetting_utf8.php';
}else{
    require_once 'downloadsetting_gbk.php';
}