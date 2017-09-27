<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 系统配文件
 * 所有系统级别的配置
 */

/**
 * UCenter客户端配置文件
 * 注意：该配置文件请使用常量方式定义
 */

define('UC_APP_ID', 1); //应用ID
define('UC_API_TYPE', 'Model'); //可选值 Model / Service
define('UC_AUTH_KEY', 'zSQwhfPu9b5"2d(W/FEOpiYTVUZ7D^{r%ga[c<o`'); //加密KEY
define('UC_DB_DSN', 'mysql://root:123456@127.0.0.1:3306/account#utf8'); // 数据库连接，使用Model方式调用API必须配置此项
define('UC_TABLE_PREFIX', ''); // 数据表前缀，使用Model方式调用API必须配置此项

return [
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => ['Addons' => ONETHINK_ADDON_PATH], //扩展模块列表
    'DEFAULT_MODULE'     => 'Admin',
    'MODULE_DENY_LIST'   => ['Common', 'User'],
    //'MODULE_ALLOW_LIST'  => array('Home','Admin'),

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'zSQwhfPu9b5"2d(W/FEOpiYTVUZ7D^{r%ga[c<o`', //默认数据加密KEY

    /* 调试配置 */
    'SHOW_PAGE_TRACE' => false,

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 3, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符

    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数

    /* 数据库配置 */
	'DB_DEPLOY_TYPE'=> 0, // 设置分布式数据库支持
	'DB_TYPE'       => 'mysql', //分布式数据库类型必须相同
	'DB_HOST'       => '127.0.0.1',
	'DB_NAME'       => 'manager', //如果相同可以不用定义多个
 	'DB_USER'       => 'root',
 	'DB_PWD'        => '123456',
	'DB_PORT'       => '3306',
	'DB_PREFIX'     => '',

    /* 文档模型配置 (文档模型核心配置，请勿更改) */
    'DOCUMENT_MODEL_TYPE' => [2 => '主题', 1 => '目录', 3 => '段落'],
    'LOG_RECORD' => true,
    //管理员才能看见的菜单ID
    "ADMIN_MENU"=> [
		126,127
	],
];
