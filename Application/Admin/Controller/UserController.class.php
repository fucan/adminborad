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
class UserController extends AdminController {

    /**
     * 菜单列表
     * @var array
     */
    private $menu = array();

    public function getMenus()
    {
        $this->initMenus();
        $this->exitWithJson(array("code"=>0,"user"=>$this->userinfo,"menu"=>$this->menu));
    }

    /**
     * 获取整个菜单树
     * @return array
     */
    protected function initMenus(){
        $this->menu = $this->getMyMenus($this->usergroup);
        return $this->menu;
    }
    /**
     * 生成菜单树
     * @param array $items
     * @param string $rid
     * @return array
     */
    protected function buildMenuTree($items,$rid) {
        $childs = $this->getChildMenu($items, $rid);
        if (isset($childs['children'])) {
            foreach ($childs['children'] as $key => $value) {
                $a = $this->buildMenuTree($value, $value['id']);
                if (null != $a['children']) {
                    $childs['children'][$key]['children'] = $a['children'];
                }
            }
        }
        return $childs;
    }

    /**
     * 获取子菜单
     * @param array $items
     * @param string $rid
     * @return array
     */
    protected function getChildMenu($items,$rid) {
        foreach ($this->menu as $key => $value) {
            if ($value['pid'] == $rid) {
                unset($this->menu[$key]);
                $items['children'][] = $value;
            }
        }
        return $items;
    }

    /**
     * 获取控制器菜单数组,二级菜单元素位于一级菜单的'_child'元素中
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    public function getMyMenus($usergroup=''){
        if(!C('DEVELOP_MODE')){ // 是否开发者模式
            $where['is_dev']    =   0;
        }
        if(!empty($usergroup)){
            $rules = explode(',', $usergroup['rules']);
            foreach ($rules as $k => $value) {
                $ruleinfo = M("auth_rule")->field("name")->where("id=$value")->find();
                //获得有权限的菜单名称
                $rulename = $ruleinfo['name'];
                //根据名称获得菜单ID
                $menusinfo = M("menu")->field("id")->where("url like '%{$rulename}%'")->find();
                if(!empty($menusinfo['id'])){
                    $menusid[] =  $menusinfo['id'];
                }
            }
        }
        $menuids = implode(",", $menusid);
        $topmenus  =   $this->getFmenus(0,$menuids);
        foreach ($topmenus as $key => $value) {
            $menus[$key] = $value;
            $menus[$key]['children'] = $this->getFmenus($value['id'],$menuids);
            if(!empty($menus[$key]['children'])){
                foreach ($menus[$key]['children'] as $k => $v) {
                    $menus[$key]['children'][$k]['children'] = $this->getFmenus($v['id'],$menuids);
                }
            }
        }
        return $menus;
    }

    public function getFmenus($fid=0,$menuids){

        if(UID!=1){
            $where = "id NOT IN (".implode(',', C('ADMIN_MENU')).") AND";
//            if($fid==0){
//                $where .= " pid=$fid AND hide = 0 AND is_dev = 0 ";
//            }else{
                $where .= " pid=$fid AND hide = 0 AND is_dev = 0 AND (id IN ($menuids) OR url='javascript:void(0);')";
//            }
        }else{
            $where = '';
            $where .= " pid=$fid AND hide = 0 AND is_dev = 0 ";
        }
        return M('menu')->where($where)->order('sort ASC')->field('name,path')->select();
    }
}
