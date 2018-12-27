<?php
/**
 * Class Order 
 * 订单数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-27 21:03:16
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Order extends Api {

	/**
	 * 订单数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN

    /**
     * 生成订单
     */
    protected function make( $query, $data ) {
        
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $data["user_id"] = $user_id;
        $o = new \Xpmsns\Pages\Model\Order;
        return $o->make( $data );
    }

    /**
     * 使用余额付款
     */
    protected function payByBalance( $query, $post ) {

    }

    /**
     * 使用积分付款
     */
    protected function payByCoin( $query, $post ) {

    }

    /**
     * 生成订单并付款(或发起付款请求)
     */
    protected function makeAndPay( $query, $post ){

    }

    // @KEEP END









}