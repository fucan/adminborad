<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi;

/**
 * 后台用户控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class MemberController extends AdminController {

    /**
     * 用户管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */

    protected function _getAllUser() {
        $sqlstr='SELECT a.*,b.defaultpasswd from member as a,ucenter_member as b where a.uid=b.id and a.status=1 and a.uid>1';
        $result = D('Member')->query($sqlstr);
        $list=[];
        $uids="";
        foreach($result as $item) {
            $item["group_id"]="0";
            $item["title"]="无权限";
            $list[$item['uid']]=$item;
            $uids.=$item['uid'].",";
        }
        $uids=rtrim($uids,",");
        $sqlstr='select d.uid,d.title,d.group_id from (SELECT a.*,b.title from auth_group_access as a,auth_group as b where a.group_id=b.id) as d where d.uid in ('.$uids.')';
        $result=D('auth_group')->query($sqlstr);
        foreach($result as $item) {
            $list[$item["uid"]]=array_merge($list[$item["uid"]],$item);
        }
        return $list;
    }

    public function getAllUser() {
        $this->exitWithJson(array("code"=>0,"data"=>$this->_getAllUser()));
    }

    /**
     * 修改昵称初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updateNickname(){
        $nickname = D('Member')->getFieldByUid(UID, 'nickname');
        $this->assign('nickname', $nickname);
        $this->meta_title = '修改昵称';
        $this->display();
    }

    /**
     * 修改昵称提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitNickname(){
        //获取参数
        $nickname = I('post.nickname');
        $password = I('post.password');
        empty($nickname) && $this->error('请输入昵称');
        empty($password) && $this->error('请输入密码');

        //密码验证
        $User   =   new UserApi();
        $uid    =   $User->login(UID, $password, 4);
        ($uid == -2) && $this->error('密码不正确');

        $Member =   D('Member');
        $data   =   $Member->create(array('nickname'=>$nickname));
        if(!$data){
            $this->error($Member->getError());
        }

        $res = $Member->where(array('uid'=>$uid))->save($data);

        if($res){
            $user               =   session('user_auth');
            $user['username']   =   $data['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('修改昵称成功！');
        }else{
            $this->error('修改昵称失败！');
        }
    }

    /**
     * 修改密码初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updatePassword(){
        $this->meta_title = '修改密码';
        $this->display();
    }

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitPassword(){
        //获取参数
        $password   =   I('post.old');
        empty($password) && $this->error('请输入原密码');
        $data['password'] = I('post.password');
        empty($data['password']) && $this->error('请输入新密码');
        $repassword = I('post.repassword');
        empty($repassword) && $this->error('请输入确认密码');

        if($data['password'] !== $repassword){
            $this->error('您输入的新密码与确认密码不一致');
        }

        $Api    =   new UserApi();
        $res    =   $Api->updateInfo(UID, $password, $data);
        if($res['status']){
            $this->success('修改密码成功！');
        }else{
            $this->error($res['info']);
        }
    }

    /**
     * 用户行为列表
     * @author huajie <banhuajie@163.com>
     */
    public function action(){
        //获取列表数据
        $Action =   M('Action')->where(array('status'=>array('gt',-1)));
        $list   =   $this->lists($Action);
        int_to_string($list);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '用户行为';
        $this->display();
    }

    /**
     * 新增行为
     * @author huajie <banhuajie@163.com>
     */
    public function addAction(){
        $this->meta_title = '新增行为';
        $this->assign('data',null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author huajie <banhuajie@163.com>
     */
    public function editAction(){
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data',$data);
        $this->meta_title = '编辑行为';
        $this->display();
    }

    /**
     * 更新行为
     * @author huajie <banhuajie@163.com>
     */
    public function saveAction(){
        $res = D('Action')->update();
        if(!$res){
            $this->error(D('Action')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！', Cookie('__forward__'));
        }
    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus(){
        $id = array_unique((array)I('userid',0));
		$method = I('method',null);
        if( in_array(C('USER_ADMINISTRATOR'), $id)){
            $this->error("不允许对超级管理员执行该操作!");
        }
        $id = is_array($id) ? implode(',',$id) : $id;
        if ( empty($id) ) {
            $this->exitWithJson(array("code"=>3,"msg"=>'请选择要操作的数据!'));
        }
        $map['uid'] =   $id;
        switch ( strtolower($method) ){
            case 'forbiduser':
                $this->forbid('Member', $map );
                break;
//            case 'resumeuser':
//                $this->resume('Member', $map );
//                break;
//            case 'deleteuser':
//                $this->delete('Member', $map );
//                break;
            default:
                $this->error('参数非法');
        }
    }
	public function authmanage(){
		if(IS_POST){
			$selectuid = trim($_POST['userid']);
			$selectgroupid = trim($_POST['groupid']);
			//更新用户组信息
			$data['uid'] = $selectuid;
			$data['group_id'] = $selectgroupid;
            if(empty($selectuid)) {
                $this->exitWithJson(array("code"=>3,"msg"=>"参数错误"));
            }
			$groupinfo = M("auth_group_access")->where("uid=$selectuid")->find();
			if(!$groupinfo){
				M("auth_group_access")->add($data);
			}else{
                M("auth_group_access")->where("uid=$selectuid")->save($data);
			}
            //更新权限
            $this->exitWithJson(array("code"=>0,"msg"=>"更新权限成功"));

		}
	}

    protected function  addOne($username,$beizhu)
    {
        $password = $this->randpasswd()[0];
        $email = $username . "@ztgame.com";
        $User = new UserApi;
        $uid = $User->register($username, $password, $email);
        if (0 < $uid) { //注册成功
            $user = array('uid' => $uid, 'nickname' => $username, 'status' => 1, 'beizhu' => $beizhu);
            if (!D('Member')->add($user)) {
                $data['flag'] = false;
                $data['msg'] = "用户添加失败";
            } else {
                $data['flag'] = true;
                $data['msg'] = "用户添加成功";
            }
        } else { //注册失败，显示错误信息
            $data['flag'] = false;
            $data['msg'] = $this->showRegError($uid);
        }
        return $data;
    }

    public function add(){
        if (IS_POST) {
            $usernamestr = trim($_POST['userstr']);
            $beizhu = trim($_POST['commit']);
            $userarr=explode(";",$usernamestr);

            $count=sizeof($userarr);
            $resarr['success']=array();
            $resarr['failed']=array();

            foreach($userarr as $item)
            {
                $res=$this->addOne($item,$beizhu);
                $res['account']=$item;
                if ($res['flag']=== true)
                {
                    $resarr['success'][]=$res;
                } else {
                    $resarr['failed'][]=$res;
                }
            }
            $resarr['code']=0;
            $resarr['total']=$count;

            if ($count==0)
            {
                $resarr['code']=1;
                $resarr['msg']="没有需要添加的用户";
            }
            $this->exitWithJson($resarr);
        }
    }
	public function UpdUser()
    {
        if (IS_POST) {
            $uid = trim($_POST['userid']);
            $pwd = trim($_POST['passwd']);
            $data['beizhu'] = trim($_POST['commit']);
            $Api = new UserApi();
            $res = $Api->updateInfo($uid, $pwd, array('password' => $pwd, 'defaultpasswd' => ""));
            if ($res['status']) {
                D('Member')->where("uid=$uid")->save($data);
                $data['code'] = 0;
                $data['msg'] = "修改成功";
            } else {
                $data['code'] = 3;
                $data['msg'] = $res['info'];
            }
            $this->exitWithJson($data);
        }
    }
    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0){
        switch ($code) {
            case -1:  $error = '用户名长度必须在16个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            default:  $error = '未知错误';
        }
        return $error;
    }

    public function CreatePassWord()
    {

    }

    protected function randpasswd($num=1,$length=6)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}~`+=,.;:?|';
        for($i=0;$i<$num;$i++)
        {
            $password = '';
            for ( $i = 0; $i < $length; $i++ )
            {
                // 这里提供两种字符获取方式
                // 第一种是使用 substr 截取$chars中的任意一位字符；
                // 第二种是取字符数组 $chars 的任意元素
                // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
                $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
            }
            $passwd[]=$password;
        }
        return $passwd;
    }

}
