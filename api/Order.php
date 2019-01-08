<?php
/**
 * Class Order 
 * 订单数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-08 16:05:22
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
     * 使用积分付款
     */
    protected function payByCoin( $query, $data ) {
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $order_id = $data["order_id"];
        if ( empty($order_id) ) {
            throw new Excp("请提供订单ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $o = new \Xpmsns\Pages\Model\Order;
        return $o->payByCoin( $order_id, $user_id );
    }


    /**
     * 使用余额付款
     */
    protected function payByBalance( $query, $data ) {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $order_id = $data["order_id"];
        if ( empty($order_id) ) {
            throw new Excp("请提供订单ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $o = new \Xpmsns\Pages\Model\Order;
        return $o->payByBalance( $order_id, $user_id );
    }

  
    /**
     * 生成订单并付款(或发起付款请求)
     */
    protected function makeAndPay( $query, $data ){

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $payment = $data["payment"];
        $paymentAllow = ["coin", "balance"];
        if ( empty($payment) ) {
            throw new Excp("请提供支付方式", 402, ["query"=>$query, "data"=>$data]);
        }

        if ( !in_array($payment, $paymentAllow) ) {
            throw new Excp("无效的支付方式", 402, ["query"=>$query,  "data"=>$data, "payment"=>$payment,"paymentAllow"=>$paymentAllow]);
        }

        $data["user_id"] = $user_id;
        $o = new \Xpmsns\Pages\Model\Order;
        $order = $o->make( $data );
        $order_id = $order["order_id"];
        $payMethod = "payBy{$payment}";
        return $o->$payMethod( $order_id, $user_id );
    }


    /**
     * 查询用户订单
     */
    protected function search( $query, $data ) {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        // 读取字段
		$select = empty($query['select']) ? [
            "order.order_id", "order.goods_ids", "order.item_ids", "order.created_at", "order.updated_at", "order.status",
            "order.total", "order.total_cost", "order.money_cost","order.coin_cost", "order.shipping_id", "order.tracking_no",
            "shipping.name",
            "order.name","order.mobile",
            "user.name", "user.nickname",
            "goods.name", "goods.goods_id",
            "item.name", "item.item_id","item.goods_id",
        ] : $query['select'];

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$query['select'] = $select;

        $o = new \Xpmsns\Pages\Model\Order;
        $query["user_user_id"] = $user_id;
        return $o->search( $query );
    }

    /**
     * 查询订单全量信息
     */
    protected function getDetail( $query, $data ){

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $order_id = $query["order_id"];
        if ( empty($order_id) ) {
            throw new Excp("请提供订单ID", 402, ["query"=>$query, "data"=>$data]);
        }

         // 读取字段
		$select = empty($query['select']) ? [
            "order.*",
            "shipping.*",
            "user.*",
            "goods.*",
            "item.*",
        ] : $query['select'];

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

        $o = new \Xpmsns\Pages\Model\Order;
        $order =$o->getByOrderId( $order_id,$select );
        if ( $order["user_id"] != $user_id ){
            throw new Excp("订单所有者与登录用户不一致", 402, ["order_id"=>$order_id, "order_user_id"=>$order["user_id"], "user_id"=>$user_id]);
        }

        return $order;
    }

    // @KEEP END









}