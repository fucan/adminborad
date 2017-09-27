<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 朱亚杰 <zhuyajie@topthink.net>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use Admin\Model\AuthRuleModel;
use Admin\Model\AuthGroupModel;

/**
 * 权限管理控制器
 * Class AuthManagerController
 * @author 朱亚杰 <zhuyajie@topthink.net>
 */
class AuthManagerController extends AdminController{
    /**
     * 权限管理首页
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function index(){
    	$id = empty(trim($_GET['groupid'])) ? 1 : trim($_GET['groupid']);
        $list = $this->lists('auth_group',array('module'=>'admin'),'id asc');
		//var_dump($list);
        $list = int_to_string($list);
        $this->assign( '_list', $list );
        $this->meta_title = '权限管理';
        //var_dump($this->getAllRolues());
		$this->assign( 'rolues', $this->getAllRolues());
		//获取选择的用户组的权限
		$model = M('auth_group');
		$rules = $model->field("rules")->where("id=$id")->find();
		$myrules = explode(",", $rules['rules']);
		$this->assign( 'myrules',$myrules);
		$this->assign( 'group_id',$id);
        $arr=array(2=>"审核管理",3=>"首页",4=>"付费用户管理",5=>"游戏管理",6=>"权限管理",7=>"后台操作记录");
        //var_dump($arr);
		$this->assign( 'typenames',$arr);
        $this->display();
    }

    public function  getAllGroups() {
        if (IS_POST) {
            $this->exitWithJson(array("code" => 0, "data" => $this->_getAllGroups()));
        }
    }

    protected function  _getAllGroups() {
        if (IS_POST) {
            return $this->lists('auth_group',array('module'=>'admin'),'id asc');
        }
    }

	//分组获取所有权限信息
	public function getAllRules(){
        $this->exitWithJson(array("code"=>0,"data"=>$this->_getAllRules()));
	}
	protected function _getAllRules() {
		$model = M('auth_rule');
		$list = $model->field("type")->where("module='admin' AND status=2")->group("type")->select();
		foreach ($list as  $group) {
			$type = $group['type'];
			$lists[$type] = $model->where("module='admin' AND status=2 AND type={$type}")->order("id asc")->select();
		}
		return $lists;
	}
	// //获得某个组的权限
	// public function getRules($gid){
		// $groupinfo =  M('auth_group')->where("id=$gid")->find();
		// $rules = explode(",", $groupinfo['rules']);
		// echo json_encode($groupinfo);
	// }
	//提交增加权限
	public function updateGroupRules(){
        if (IS_POST) {
            $id = $_POST['groupid'];
            //获得提交的所选权限
            $rules = $_POST['rules'];
//            debug_print_backtrace();
            if(is_array($rules))
                $rulesstr = "1,".implode(",", $rules);
            else
                $rulesstr = '1,2';
            $data['rules'] = $rulesstr;
            $model = M('auth_group');
            $result = $model->where("id=$id")->save($data);
            if($result){
                $this->exitWithJson(array("code"=>0,"msg"=>'更新成功'));
            } elseif($result===FALSE) {
                $this->exitWithJson(array("code"=>2,"msg"=>'更新失败'));
            }else{
                $this->exitWithJson(array("code"=>2,"msg"=>'当前权限无任何变更'));
            }
        }
	}
	//增加用户组
	public function addgroup(){
		$title = trim($_POST['title']);
		$data['title'] = $title;
		$data['module'] = "admin";
		$data['type'] = 1;
		$data['description'] = $title;
		$data['rules'] = "1,2";
		$model = M('auth_group');
		$result = $model->add($data);
		if($result>0){
			$data = array(
				'code'=> 0,
				'msg'=>"添加成功",
				'id'=>$result,
                'rules'=>$data['rules']
			);
		}else{
			$data = array(
				'code'=> 2,
				'msg'=>"添加失败"
			);
		}
		echo json_encode($data);
	}
	//删除用户组
	public function delgroup(){
		$gid = trim($_POST['groupid']);
		$model = M('auth_group');
		$result = $model->where("id=$gid")->delete();
		if($result===FALSE){
			$data = array(
				'code'=> 2,
				'msg'=>"删除失败"
			);
		}elseif($result===0){
			$data = array(
				'code'=> 2,
				'msg'=>"删除失败,未找到指定的用户组"
			);
		}else{
			$data = array(
				'code'=> 0,
				'msg'=>"删除成功"
			);
		}
		echo json_encode($data);
	}

	//更新用户组
	public function updategroup(){
        $savedata['id'] = trim(I('groupid'));
        $savedata['title']=trim(I('newtitle'));
		$model = M('auth_group');
		$result = $model->save($savedata);
		if($result===FALSE){
			$data = array(
					'code'=> 2,
					'msg'=>"更新失败"
			);
		}elseif($result===0){
			$data = array(
					'code'=> 2,
					'msg'=>"更新失败"
			);
		}else{
			$data = array(
					'code'=> 0,
					'msg'=>"更新成功"
			);
		}
        $this->exitWithJson($data);
	}

    /*封禁记录查询*/
    function flexigrid() {

        list($colkey, $colsinfo, $where, $sortname, $sortorder, $offset, $rp, $page) = get_flexigrid_params();
        $model=D('Member');
        $total = $model->count();
        $orderby = $sortname ? "{$sortname} {$sortorder} " : "";

        $res=$model->field("{$colsinfo}")->where($where)->order("{$orderby}")->limit("{$offset}","{$rp}")->select();
        if ($res !== false)
        {
            foreach($res as $k=>$value)
            {
                $value['last_login_time']=date('Y-m-d H:i:s',$value['last_login_time']);
                $rows[]=array('id'=>$value['id'],'cell'=>$value);
            }
        }
        $result = array(
            'page' => $page,
            'total' => $total,
            'rows' => $rows
        );
        echo json_encode($result);
    }
}
