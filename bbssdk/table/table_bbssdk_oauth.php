<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
require_once 'bbssdk_common_sync.php';
class table_bbssdk_oauth extends bbssdk_common_sync
{
	public function __construct()
	{
		$this->_table = "bbssdk_oauth";
                $this->_pk = "id";
		parent::__construct();
	}
        public function getOauthUser($wxOpenid,$wxUnionid,$qqOpenid,$qqUnionid){
            $wxOpenid  = $wxOpenid?$wxOpenid:404;
            $wxUnionid = $wxUnionid?$wxUnionid:404;
            $qqOpenid  = $qqOpenid?$qqOpenid:404;
            $qqUnionid = $qqUnionid?$qqUnionid:404;
            return DB::result_first("SELECT uid FROM %t WHERE wxOpenid=%s OR wxUnionid=%s OR qqOpenid=%s OR qqUnionid=%s", array($this->_table, $wxOpenid,$wxUnionid,$qqOpenid,$qqUnionid));
        }
        public function getOauthByUid($uid){
            return DB::fetch_first("SELECT * FROM %t WHERE uid=%d ", array($this->_table, $uid));
        }

        public function recordOauth($uid,$wxOpenid,$wxUnionid,$qqOpenid,$qqUnionid) {
            if(!$wxOpenid&&!$wxUnionid&&!$qqOpenid&&!$qqUnionid){
                return array('code'=>403,'msg'=>'oAuth信息不能为空');
            }
            $res = $this->getOauthByUid($uid);
            try{
                if($res){
                    if($wxOpenid||$wxUnionid){
                        if($res['wxOpenid']||$res['wxUnionid']){
                            return array('code'=>701,'msg'=>'');
                        }
                        $this->update($res['id'], array('wxOpenid'=>$wxOpenid,'wxUnionid'=>$wxUnionid));
                    }else if($qqOpenid||$qqUnionid){
                        if($res['qqOpenid']||$res['qqUnionid']){
                            return array('code'=>702,'msg'=>'');
                        }
                        $this->update($res['id'], array('qqOpenid'=>$qqOpenid,'qqUnionid'=>$qqUnionid));
                    }
                }else{
                    $this->insert(array('uid'=>$uid,'wxOpenid'=>$wxOpenid,'wxUnionid'=>$wxUnionid,'qqOpenid'=>$qqOpenid,'qqUnionid'=>$qqUnionid));
                }
            } catch (Exception $e){
                return array('code'=>$e->getCode(),'msg'=>$e->getMessage());
            }
            return array('code'=>200,'msg'=>'');
        }
}