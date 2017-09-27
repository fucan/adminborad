<?php
namespace Admin\Controller;
use Think\Controller;

class AdminController extends Controller {
    /**
     * 登录用户信息
     * @var array
     */
    protected $userinfo;

    //用户所在权限组
    protected $usergroup;
    /*
     * 数据库模型*/
    protected $model;

    public function exitWithJson($array) {
        exit(json_encode($array));
    }

    /**
     * 1.判断是否登录
     * 2.权限设置
     * 3.全局变量
     */
    protected function _initialize(){
        //检查禁止访问的url
        $actions_whitelist = C('ACTION_WHITELIST');
        $this_action_url = __ACTION__;
        array_walk($actions_whitelist, array($this, "accomplishActionUrl"), '/index.php?s=/');
        if (in_array($this_action_url, $actions_whitelist)) {
            return;
        }
        define('UID',is_login());
        if( !UID ){
                $this->exitWithJson(array("code"=>-1,"msg"=>"登录过期，请重新登录"));
        }
        else {
            $this->userinfo = session('user_auth');
            $this->userinfo['lastLoginTime'] = date('Y-m-d H:i:s', $this->userinfo['last_login_time']);
            unset($this->userinfo['last_login_time']);
            define('IS_ROOT', is_administrator());
            //配置禁止访问的ip
            if (!IS_ROOT && C('ADMIN_ALLOW_IP')) {
                if (!in_array(get_client_ip(), explode(',', C('ADMIN_ALLOW_IP')))) {
                    $this->exitWithJson(array("code"=>2,"msg"=>"403:禁止访问"));
                }
            }

            //初始化数据库链接
            $this->model = M();

            $access = $this->accessControl();
            //所有的请求都需要验证，不只是同步的,登录之后的请求，登录和登出不在这里,登录之后的模块需要继承改类
            if ($access === false) {
                $this->exitWithJson(array("code"=>2,"msg"=>"403:禁止访问"));
            } elseif ($access === null) {
                $dynamic = $this->checkDynamic();//检测分类栏目有关的各项动态权限
                if ($dynamic === null) {
                    //检测非动态权限
                    $rule = strtolower(CONTROLLER_NAME . '/' . ACTION_NAME);
                    if (!$this->checkRule($rule)) {
                        $this->exitWithJson(array("code"=>2,"msg"=>"权限不足请联系管理员"));
                    }
                } elseif ($dynamic === false) {
                    $this->exitWithJson(array("code"=>2,"msg"=>"未授权访问请联系管理员"));
                }
            }
            //允许访问 ,初始化 其他信息

            //调用子类初始化函数
            if (method_exists($this, '_init'))
                $this->_init();//用于子类初始化
        }
    }

    /**
     * 权限检测
     * @param string  $rule    请求访问的接口
     * @return boolean
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final protected function checkRule($rule){
        if(IS_ROOT){
            return true;//管理员允许访问任何页面
        }
        //var_dump("UID:".UID);

        if(!$this->check($rule,UID)){
            return false;
        }
        return true;
    }
	function check($rule,$uid){
		//检测该用户 是否有权限访问 页面
		$this->usergroup = $this->getUserGroup(UID);
        $userrules = explode(",", $this->usergroup['rules']);
		$ruleid = $this->model->query("SELECT id FROM auth_rule WHERE name=lower('".$rule."')");
		$ruleid = $ruleid[0]['id'];
		if(!empty($userrules) && !empty($ruleid) && in_array($ruleid, $userrules)){
			return true;
		}else{
			return false;
		}
	}

	protected function getUserGroup($uid){
        $uid = (int)$uid;
		$usergroup = $this->model->query("SELECT * FROM auth_group_access a INNER JOIN auth_group b ON a.group_id = b.id WHERE a.uid=$uid");
		return $usergroup[0];
	}
    /**
     * 检测是否是需要动态判断的权限
     * @return boolean|null
     *      返回true则表示当前访问有权限
     *      返回false则表示当前访问无权限
     *      返回null，则会进入checkRule根据节点授权判断权限
     *
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    protected function checkDynamic(){
        if(IS_ROOT){
            return true;//管理员允许访问任何页面
        }
        return null;//不明,需checkRule
    }


    /**
     * action访问控制,在 **登陆成功** 后执行的第一项权限检测任务
     *
     * @return boolean|null  返回值必须使用 `===` 进行判断
     *
     *   返回 **false**, 不允许任何人访问(超管除外)
     *   返回 **true**, 允许任何管理员访问,无需执行节点权限检测
     *   返回 **null**, 需要继续执行节点权限检测决定是否允许访问
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final protected function accessControl(){
        if(IS_ROOT){
            return true;//管理员允许访问任何页面
        }
		$allow = C('ALLOW_VISIT');
		$deny  = C('DENY_VISIT');
		$check = strtolower(CONTROLLER_NAME.'/'.ACTION_NAME);
        if ( !empty($deny)  && in_array_case($check,$deny) ) {
            return false;//非超管禁止访问deny中的方法
        }
        if ( !empty($allow) && in_array_case($check,$allow) ) {
            return true;
        }
        return null;//需要检测节点权限
    }

    /**
     * 对数据表中的单行或多行记录执行修改 GET参数id为数字或逗号分隔的数字
     *
     * @param string $model 模型名称,供M函数使用的参数
     * @param array  $data  修改的数据
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final protected function editRow ( $model ,$data, $where , $msg ){
        $id    = array_unique((array)I('id',0));
		
        $id    = is_array($id) ? implode(',',$id) : $id;
        $where = array_merge((array)$where);
        $msg   = array_merge( array( 'success'=>'操作成功！', 'error'=>'操作失败！', 'url'=>'' ,'ajax'=>IS_AJAX) , (array)$msg );
		$wherestr='';
		foreach ($where as $key => $value) {
			if(empty($wherestr))
				$wherestr .= $key."=".$value;
			else
				$wherestr .= " and ".$key."=".$value;
		}
		//echo $wherestr;
        if( D($model)->where($wherestr)->save($data)!==false ) {
            $this->exitWithJson(array("code"=>0,"msg"=>$msg['success']));
        }else{
            $this->exitWithJson(array("code"=>3,"msg"=>$msg['error']));
        }
    }

    /**
     * 禁用条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的 where()方法的参数
     * @param array  $msg   执行正确和错误的消息,可以设置四个元素 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function forbid ( $model , $where = array() , $msg = array( 'success'=>'状态禁用成功！', 'error'=>'状态禁用失败！')){
        $data    =  array('status' => 0);
        $this->editRow( $model , $data, $where, $msg);
    }

    /**
     * 恢复条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function resume (  $model , $where = array() , $msg = array( 'success'=>'状态恢复成功！', 'error'=>'状态恢复失败！')){
        $data    =  array('status' => 1);
        $this->editRow(   $model , $data, $where, $msg);
    }

    /**
     * 还原条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * @author huajie  <banhuajie@163.com>
     */
    protected function restore (  $model , $where = array() , $msg = array( 'success'=>'状态还原成功！', 'error'=>'状态还原失败！')){
        $data    = array('status' => 1);
        $where   = array_merge(array('status' => -1),$where);
        $this->editRow(   $model , $data, $where, $msg);
    }

    /**
     * 条目假删除
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function delete ( $model , $where = array() , $msg = array( 'success'=>'禁用成功！', 'error'=>'禁用失败！')) {
        $data['status']         =   -1;
        $this->editRow(   $model , $data, $where, $msg);
    }

    /**
     * 设置一条或者多条数据的状态
     */
    public function setStatus($Model=CONTROLLER_NAME){

        $ids    =   I('request.ids');
        $status =   I('request.status');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }

        $map['id'] = array('in',$ids);
        switch ($status){
            case -1 :
                $this->delete($Model, $map, array('success'=>'删除成功','error'=>'删除失败'));
                break;
            case 0  :
                $this->forbid($Model, $map, array('success'=>'禁用成功','error'=>'禁用失败'));
                break;
            case 1  :
                $this->resume($Model, $map, array('success'=>'启用成功','error'=>'启用失败'));
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }
    /**
     * 返回后台节点数据
     * @param boolean $tree    是否返回多维数组结构(生成菜单时用到),为false返回一维数组(生成权限节点时用到)
     * @retrun array
     *
     * 注意,返回的主菜单节点数组中有'controller'元素,以供区分子节点和主节点
     *
     * @author 朱亚杰 <xcoolcc@gmail.com>
     */
    final protected function returnNodes($tree = true){
        static $tree_nodes = array();
        if ( $tree && !empty($tree_nodes[(int)$tree]) ) {
            return $tree_nodes[$tree];
        }
        if((int)$tree){
            $list = M('qule_dc.Menu')->field('id,pid,title,url,tip,hide')->order('sort asc')->select();
            foreach ($list as $key => $value) {
                if( stripos($value['url'],MODULE_NAME)!==0 ){
                    $list[$key]['url'] = MODULE_NAME.'/'.$value['url'];
                }
            }
            $nodes = list_to_tree($list,$pk='id',$pid='pid',$child='operator',$root=0);
            foreach ($nodes as $key => $value) {
                if(!empty($value['operator'])){
                    $nodes[$key]['child'] = $value['operator'];
                    unset($nodes[$key]['operator']);
                }
            }
        }else{
            $nodes = M('qule_dc.Menu')->field('title,url,tip,pid')->order('sort asc')->select();
            foreach ($nodes as $key => $value) {
                if( stripos($value['url'],MODULE_NAME)!==0 ){
                    $nodes[$key]['url'] = MODULE_NAME.'/'.$value['url'];
                }
            }
        }
        $tree_nodes[(int)$tree]   = $nodes;
        return $nodes;
    }


    /**
     * 通用分页列表数据集获取方法
     *
     *  可以通过url参数传递where条件,例如:  index.html?name=asdfasdfasdfddds
     *  可以通过url空值排序字段和方式,例如: index.html?_field=id&_order=asc
     *  可以通过url参数r指定每页数据条数,例如: index.html?r=5
     *
     * @param sting|Model  $model   模型名或模型实例
     * @param array        $where   where查询条件(优先级: $where>$_REQUEST>模型设定)
     * @param array|string $order   排序条件,传入null时使用sql默认排序或模型属性(优先级最高);
     *                              请求参数中如果指定了_order和_field则据此排序(优先级第二);
     *                              否则使用$order参数(如果$order参数,且模型也没有设定过order,则取主键降序);
     *
     * @param array        $base    基本的查询条件
     * @param boolean      $field   单表模型用不到该参数,要用在多表join时为field()方法指定参数
     * @author 朱亚杰 <xcoolcc@gmail.com>
     *
     * @return array|false
     * 返回数据集
     */
    protected function lists ($model,$where=array(),$order='',$base = array('status'=>array('egt',0)),$field=true){
        $options    =   array();
        $REQUEST    =   (array)I('request.');
        if(is_string($model)){
            $model  =   M($model);
        }

        $OPT        =   new \ReflectionProperty($model,'options');
        $OPT->setAccessible(true);

        $pk         =   $model->getPk();
        if($order===null){
            //order置空
        }else if ( isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']),array('desc','asc')) ) {
            $options['order'] = '`'.$REQUEST['_field'].'` '.$REQUEST['_order'];
        }elseif( $order==='' && empty($options['order']) && !empty($pk) ){
            $options['order'] = $pk.' desc';
        }elseif($order){
            $options['order'] = $order;
        }
        unset($REQUEST['_order'],$REQUEST['_field']);

        $options['where'] = array_filter(array_merge( (array)$base, /*$REQUEST,*/ (array)$where ),function($val){
            if($val===''||$val===null){
                return false;
            }else{
                return true;
            }
        });
        if( empty($options['where'])){
            unset($options['where']);
        }
        $options      =   array_merge( (array)$OPT->getValue($model), $options );
        $total        =   $model->where($options['where'])->count();

        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p =$page->show();
        $this->assign('_page', $p? $p: '');
        $this->assign('_total',$total);
        $options['limit'] = $page->firstRow.','.$page->listRows;

        $model->setProperty('options',$options);

        return $model->field($field)->select();
    }

    protected function curlHttpPost($url, $pstr) {
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    	//curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $pstr);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	//curl_setopt($ch, CURLOPT_USERAGENT, 'TEST PHP5 Client 1.1 (curl) ' . phpversion());
    	$result = curl_exec($ch);
    	curl_close($ch);
    	return $result;
    }
	//初始化游戏配置
	public function init_game_config() {
    	//if (!S('gameConfigData') || !S('gamesData')) {
	    	$config['games.list'] = C('games_list');
	    	
	    	$gameConfigData = array();
	    	foreach ($config['games.list'] as $k => $gameConfig) {
	    	
	    		$game_id = $gameConfig['quleid'];
	    		$db = $gameConfig['db'];
	    		$model = M('','',C($db.'_db'));
	    	
	    		$config_server_list = $model->query("SELECT * FROM config_server WHERE `status` > 0");
	    		$platformArr = $serverArr = $platform_server = array();
	    		foreach ($config_server_list as $k => $v) {
	    	
	    			$platformArr[$v['platform']] = array('pid'=>$v['platform'],'pname'=>$v['pname_cn']);
	    			$platform_server[$v['platform']][$v['server']] = array('serverId' =>$v['server'], 'serverName'=>$v['sname_cn']);
	    			$serverArr[$v['server']] = $v['sname_cn'];
	    		}
	    		//带游戏的列表
	    		// foreach ($platformArr as $platform) {
	    			// $platformArr[$platform['pid']]['gamelist'][$game_id]['gid'] = $game_id;
					// $platformArr[$platform['pid']]['gamelist'][$game_id]['gamename'] = $gameConfig['name'];
	    			// $platformArr[$platform['pid']]['gamelist'][$game_id]['gameservice'] = $platform_server[$platform['pid']];
	    		// }
	    		
	    		foreach ($platformArr as $key => $platform) {
	    			if($game_id==$this->gameId){
	    				$newplatformArr[$platform['pid']] = $platform;
	    				$newplatformArr[$platform['pid']]['gid'] = $game_id;
						$newplatformArr[$platform['pid']]['gamename'] = $gameConfig['name'];
		    			$newplatformArr[$platform['pid']]['gameservice'] = $platform_server[$platform['pid']];
	    			}
	    		}
	    		$gameConfigData[$game_id] = array(
	    				'platform' => $newplatformArr,
	    				'server' =>$serverArr,
	    				'gid' => $game_id,
	    				'gamename' => $gameConfig['name'],
	    				'gameweburl' => $gameConfig['loginurl'],
	    		);
	    	}
	    	S('platlist',$newplatformArr);
	    	S('gameConfigData', $gameConfigData);
	    	$gamesData = array();
	    	foreach ($gameConfigData as $k => $v) {
	    		
	    		$gamesData['count'] = $gamesData['count'] + 1;
	    		$gamesData[$v['gid']] = array('gid' => $v['gid'], 'gamename' => $v['gamename'], 'gameweburl' => $v['gameweburl']);
	    		
	    		foreach ($v['server'] as $sid => $server) {
	    			$gamesData[$v['gid']]['gameservice']['count'] = $gamesData[$v['gid']]['gameservice']['count'] + 1;
	    			$gamesData[$v['gid']]['gameservice'][$sid] = array('id' => $sid, 'servicename' => $server);
	    		}
	    	}
	    	
	    	S('gamesData', $gamesData);
    	}
    //}


    /**
     * fill server config data
     * @param $platform
     * @param $server
     * @param $cs_list
     * @return mixed
     */
    protected  function getServerCurrencyConfig($platform, $server, $cs_list) {
        $cs_key = $platform."_".$server;
        if (!array_key_exists($cs_key, $cs_list)) {
            $config_server_where = array(
                'platform' => $platform,
                'server' => $server
            );
            $cs_res = $this->configserver_model->field('platform, pname_cn, server, sname_cn, currency_ratio, ex_rate')
                ->where($config_server_where)->select();
            $cs_list[$cs_key] = $cs_res[0];
        }
        return $cs_list;
    }

    /**
     * 控制器白名单补全url地址
     * @param $item
     * @param $key
     * @param $prefix
     */
    private function accomplishActionUrl(&$item, $key, $prefix) {
        $item = $prefix . $item;
    }
}
