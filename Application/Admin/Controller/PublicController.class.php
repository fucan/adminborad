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
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class PublicController extends \Think\Controller {

    /**
     * 后台用户登录
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function login($username = null, $password = null, $verify = null){
        if(IS_POST){
            /* 调用UC登录接口登录 */
            $User = new UserApi;
            $uid = $User->login($username,$password);
            if(0 < $uid){ //UC登录成功
                /* 登录用户 */
                $Member = D('Member');
                if($Member->login($uid)){ //登录用户
                    $logindata["uid"]=$uid;
                    $logindata["sessid"]=session_id();
                    exit(json_encode(array(code=>0,"data"=>$logindata)));
                } else {
                    exit(json_encode(array(code=>-1,"msg"=>$Member->getError())));
                }
            } else { //登录失败
                switch($uid) {
                    case -1: $errmsg["code"]=1;$errmsg["msg"]= '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $errmsg["code"]=1;$errmsg["msg"]= '密码错误！'; break;
                    default: $errmsg["code"]=1;$errmsg["msg"]= '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                }
                exit(json_encode($errmsg));
            }
        }
    }

    /* 退出登录 */
    public function logout(){
        if(IS_POST){
            if (is_login()) {
                D('Member')->logout();
                session('[destroy]');
            }
            exit(json_encode(array("code"=>0,"msg"=>"成功退出")));
        }
    }

    public function verify(){
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

    public function changepwd() {
        if(is_login()) {
            if(IS_POST) {
                $username = session('user_auth.username');
                $uid = session('user_auth.uid');
                $oldpwd=I('post.oldpwd');
                $newpwd=I('post.newpwd');
                $verifypwd=I('post.verifypwd');

                $User = new UserApi;
                if($User->verifyPwd($uid,$oldpwd))
                {
                    if($newpwd != $verifypwd)
                    {
                        $this->error("两次输入的新密码不一样！");
                    }
                    else if(strlen($newpwd)<6){
                        $this->error("密码长度不能少于6位！");
                    }
                    else {
                        $data['password']=$newpwd;
                        $data['defaultpasswd']="";
                        $res=$User->updateInfo($uid,$oldpwd,$data);
                        if($res['status']==true){
                            $this->success('密码修改成功！','/',3);
                            }
                    }
                } else {
                    $this->error("旧密码错误！");
                }
            } else {
                $this->display();
            }
        } else {
            $this->redirect('/');
        }
    }
    /**
     * 加密密码
     * @param  string  $username 用户名
     * @param  string  $password 用户密码
     * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function encryptpassword(){
        $arr=array(
);
        foreach ($arr as $key => $value) {
            echo md5(sha1($value) . 'zSQwhfPu9b5"2d(W/FEOpiYTVUZ7D^{r%ga[c<o`');
            echo '<br>';
        }
        //var_dump(think_ucenter_md5($_GET['password'], UC_AUTH_KEY));exit;
    }
}
