<?php
/**
 * Class Order 
 * 订单数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-08 16:05:22
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                                
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Order extends Model {




	/**
	 * 订单数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('order'); // 数据表名称 xpmsns_pages_order

	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    
    /**
     * 生成订单
     * @param array $data 订单信息
     */
    public function make( $data ) {

        if ( empty($data["goods"]) ) {
            throw new Excp("未指定购买信息", 404, ["data"=>$data]);
        }

        if (empty($data["user_id"])){
            throw new Excp("未指定购买用户信息", 404, ["data"=>$data]);
        }

        $snapshot = $this->takeSnapshot($data["goods"]);
        $data["status"] = "wait_pay";  // 待支付
        $data = array_merge($data, $snapshot);

        // 校验金额
        $total_coin = $data["total_coin"];
        $u = new \Xpmsns\User\Model\User;
        $coin = $u->getCoin($user_id);
        if ( $coin < $total_coin ) {
            throw new Excp("用户账户积分余额不足", 402, ["user_id"=>$user_id, "coin"=>$coin, "quantity"=>$total_coin]);
        }

        // 计算运费

        
        // 检查库存
        foreach( $data["snapshot"] as $ss ) {
            $quantity = $ss["quantity"];
            $available = $ss["available"];
            if ($quantity > $available ) {
                throw new Excp("商品库存不足", 502, ["snapshot"=>$ss]);
            }
        }

         // 扣减库存信息
        $g = new Goods();
        foreach( $data["snapshot"] as $ss ) {
             $quantity = $ss["quantity"];
             $goods_id = $ss["goods_id"];
             $item_id  = $ss["item_id"];
             $g->shipping( $goods_id, $item_id, $quantity );
        }


        // 创建订单
        $rs = $this->create( $data );


        return $rs;
    }

     /**
     * 订单状态变更为已完整
     * @param string $order_id 订单ID
     * @param string $user_id 用户ID
     */
    function makeComplete( $order_id, $user_id ) {

        $order = $this->getBy("order_id", $order_id);
        if ( $order["user_id"] != $user_id ) {
            throw new Excp("下单用户和当前用户不一致", 402, ["order"=>$order, "user_id"=>$user_id]);
        }

         // 验证订单状态
         $allowPayment = ["shiping"];
         if ( !in_array( $order["status"], $allowPayment) ){
             throw new Excp("当前订单无法标记为完成(尚未发货)", 402, ["order"=>$order, "status"=>$status, "allowPayment"=>$allowPayment, "user_id"=>$user_id]);
         }

        return $this->updateBy("order_id",[
            "order_id" => $order_id,
            "status" => "complete",  // 设定为已完成
        ]);
    }


    /**
     * 积分付款
     * @param string $order_id 订单ID
     * @param string $user_id 用户ID
     */
    function payByCoin( $order_id, $user_id ) {

        $order = $this->getBy("order_id", $order_id);
        if ( $order["user_id"] != $user_id ) {
            throw new Excp("下单用户和付款用户不一致", 402, ["order"=>$order, "user_id"=>$user_id]);
        }

        // 验证订单状态
        $allowPayment = ["wait_pay"];
        if ( !in_array( $order["status"], $allowPayment) ){
            throw new Excp("当前订单无需付款", 402, ["order"=>$order, "status"=>$status, "allowPayment"=>$allowPayment, "user_id"=>$user_id]);
        }

        // 金额
        $quantity = ( $order["total_cost"] == 0 ) ? $order["total"] : $order["total_cost"];

        // 验证付款方式

        // 校验余额
        $u = new \Xpmsns\User\Model\User;
        $coin = $u->getCoin($user_id);
        if ( $coin < $quantity ) {
            throw new Excp("用户账户积分余额不足", 402, ["order"=>$order, "user_id"=>$user_id, "coin"=>$coin, "quantity"=>$quantity]);
        }

        // 付款
        $pay = new \Xpmsns\User\Model\Coin();
        $coin = $pay->create([
            "user_id" => $user_id,
            "quantity" => $quantity * -1,
            "type" => "decrease",
            "snapshot" => ["type"=>"order", "order_id"=>$order_id, "data"=>$order],
        ]);

        // 变更订单状态 & 数据
        unset( $coin["snapshot"] );
        return $this->updateBy("order_id",[
            "order_id" => $order_id,
            "payment_detail" => $coin,
            "coin_cost" => $quantity,  // 实付积分
            "payment" => "coin",  // 付款方式
            "status" => "pay_complete",  // 设定为已付款
        ]);
    }


    /**
     * 余额付款
     * @param string $order_id 订单ID
     * @param string $user_id 用户ID
     */
    function payByBalance( $order_id, $user_id) {

        $order = $this->getBy("order_id", $order_id);
        if ( $order["user_id"] != $user_id ) {
            throw new Excp("下单用户和付款用户不一致", 402, ["order"=>$order, "user_id"=>$user_id]);
        }

        // 验证订单状态
        $allowPayment = ["wait_pay"];
        if ( !in_array( $order["status"], $allowPayment) ){
            throw new Excp("当前订单无需付款", 402, ["order"=>$order, "status"=>$status, "allowPayment"=>$allowPayment, "user_id"=>$user_id]);
        }

        // 金额
        $quantity = ( $order["total_cost"] == 0 ) ? $order["total"] : $order["total_cost"];

        // 验证付款方式

        // 校验余额
        $u = new \Xpmsns\User\Model\User;
        $blc = $u->getBalance($user_id);
        if ( $blc < $quantity ) {
            throw new Excp("用户账户余额不足", 402, ["order"=>$order, "user_id"=>$user_id, "balance"=>$blc, "quantity"=>$quantity]);
        }

        // 付款
        $pay = new \Xpmsns\User\Model\Balance();
        $coin = $pay->create([
            "user_id" => $user_id,
            "quantity" => $quantity * -1,
            "type" => "decrease",
            "snapshot" => ["type"=>"order", "order_id"=>$order_id, "data"=>$order],
        ]);

        // 变更订单状态 & 数据
        unset( $coin["snapshot"] );
        return $this->updateBy("order_id",[
            "order_id" => $order_id,
            "payment_detail" => $coin,
            "money_cost" => $quantity,  // 实付金额
            "payment" => "balance",  // 付款方式
            "status" => "pay_complete",  // 设定为已付款
        ]);
    }


    /**
     * 根据商品信息描述字符串,获取全量商品信息
     * @param string $goods_text 商品信息集合字符串
     * 商品描述格式 :goods_id>:item_idX:quantity
     *            :goods_id  [必填]商品ID
     *            >:item_idX [选填]商品所属单品ID
     *            X:quantity [选填]数量, 默认为1
     * 
     *      示例 1190164007277187>4988649036969294X1,3102721829519507X2,1190164007277187
     *  
     */
    public function takeSnapshot( string $goods_text ) {
        $goods_arr =  explode(",",$goods_text);
        $snapshot = []; $goods_ids=[]; $item_ids=[]; $total=0; $total_coin=0;
        $g = new Goods();
        $it = new Item();

        foreach( $goods_arr as $txt ) {
            $arr = preg_split("/@|>|X|x/", $txt);
            $goods_id = $arr[0];
            $item_id = $arr[1];
            $quantity = empty($arr[2]) ? 1 : intval($arr[2]);
            $ss = ["type"=>"goods", "goods_id"=>$goods_id, "item_id"=>$item_id, "quantity"=>$quantity];
            $goods_detail = $g->getGoodsDetail($goods_id);
            if ( empty($goods_detail) ) {
                throw new Excp("商品信息不存在", 404, ["goods_text"=>$goods_text, "goods_id"=>$goods_id]);
            }

            unset( $goods_detail["items"]);
            $ss["price"] = $goods_detail["lower_price"];
            $ss["coin"] = $goods_detail["lower_coin"];
            $ss["coin_max"] = $item_detail["lower_coin_max"];
            $ss["available"] = $goods_detail["available_sum"];
            $ss["goods_detail"] = $goods_detail;
            $goods_ids[] = $goods_id;

            // 商品信息
            if ( !empty($item_id) ) {
                $item_detail = $it->itemDetail($item_id);
                $ss["type"] = "item";
                if ( empty($item_detail) ) {
                    throw new Excp("单品信息不存在", 404, ["goods_text"=>$goods_text,"item_id"=>$item_id, "goods_id"=>$goods_id]);
                }

                $ss["price"] = $item_detail["price"];
                $ss["coin"] =  $item_detail["coin"];
                $ss["coin_max"] = $item_detail["coin_max"];
                $ss["item_detail"] = $item_detail;
                $ss["available"] = $item_detail["available_sum"];
                $item_ids[] = $item_id;
            }

            $total = $total + intval($ss["price"]);
            $total_coin = $total_coin + intval($ss["coin"]);

            $snapshot[] = $ss;
        }

        return [
            "goods_ids" => $goods_ids,
            "item_ids" => $item_ids,
            "snapshot" => $snapshot,
            "total" => $total,
            "total_coin" => $total_coin,
        ];
    }

    // @KEEP END

	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 订单ID
		$this->putColumn( 'order_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 外部ID
		$this->putColumn( 'outer_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 商品清单
		$this->putColumn( 'goods_ids', $this->type("text", ["json"=>true, "null"=>true]));
		// 单品清单
		$this->putColumn( 'item_ids', $this->type("text", ["json"=>true, "null"=>true]));
		// 金额
		$this->putColumn( 'total', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 运费
		$this->putColumn( 'freight', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 实付金额
		$this->putColumn( 'total_cost', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 消费货币
		$this->putColumn( 'money_cost', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 消费积分
		$this->putColumn( 'coin_cost', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 消费代币
		$this->putColumn( 'bitcoin_cost', $this->type("float", ["index"=>true, "null"=>true]));
		// 实付运费
		$this->putColumn( 'freight_cost', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 支付明细
		$this->putColumn( 'payment_detail', $this->type("text", ["json"=>true, "null"=>true]));
		// 用户
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 收件人
		$this->putColumn( 'name', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 国家地区
		$this->putColumn( 'area', $this->type("string", ["length"=>50, "index"=>true, "null"=>true]));
		// 省份
		$this->putColumn( 'prvn', $this->type("string", ["length"=>50, "index"=>true, "null"=>true]));
		// 城市
		$this->putColumn( 'city', $this->type("string", ["length"=>50, "index"=>true, "null"=>true]));
		// 区县
		$this->putColumn( 'town', $this->type("string", ["length"=>50, "index"=>true, "null"=>true]));
		// 邮编
		$this->putColumn( 'zipcode', $this->type("string", ["length"=>50, "index"=>true, "null"=>true]));
		// 收货地址
		$this->putColumn( 'address', $this->type("string", ["length"=>600, "null"=>true]));
		// 联系电话
		$this->putColumn( 'mobile', $this->type("string", ["length"=>50, "index"=>true, "null"=>true]));
		// 付款方式
		$this->putColumn( 'payment', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 物流
		$this->putColumn( 'shipping_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 物流单号
		$this->putColumn( 'tracking_no', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 物流成本
		$this->putColumn( 'freight_in', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 订单状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 备注
		$this->putColumn( 'remark', $this->type("text", ["null"=>true]));
		// 快照
		$this->putColumn( 'snapshot', $this->type("longText", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


		// 格式化: 订单状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"wait_pay" => [
		  			"value" => "wait_pay",
		  			"name" => "待支付",
		  			"style" => "muted"
		  		],
		  		"pay_complete" => [
		  			"value" => "pay_complete",
		  			"name" => "已付款",
		  			"style" => "danger"
		  		],
		  		"wait_confirm" => [
		  			"value" => "wait_confirm",
		  			"name" => "待确认",
		  			"style" => "danger"
		  		],
		  		"wait_shipping" => [
		  			"value" => "wait_shipping",
		  			"name" => "待发货",
		  			"style" => "danger"
		  		],
		  		"shiping" => [
		  			"value" => "shiping",
		  			"name" => "已发货",
		  			"style" => "muted"
		  		],
		  		"wait_comment" => [
		  			"value" => "wait_comment",
		  			"name" => "待评价",
		  			"style" => "danger"
		  		],
		  		"cancel" => [
		  			"value" => "cancel",
		  			"name" => "已取消",
		  			"style" => "muted"
		  		],
		  		"apply_refund" => [
		  			"value" => "apply_refund",
		  			"name" => "申请退款",
		  			"style" => "danger"
		  		],
		  		"refunding" => [
		  			"value" => "refunding",
		  			"name" => "退款中",
		  			"style" => "muted"
		  		],
		  		"refund_complete" => [
		  			"value" => "refund_complete",
		  			"name" => "退款完毕",
		  			"style" => "success"
		  		],
		  		"apply_exchange" => [
		  			"value" => "apply_exchange",
		  			"name" => "申请换货",
		  			"style" => "danger"
		  		],
		  		"exchanging" => [
		  			"value" => "exchanging",
		  			"name" => "换货中",
		  			"style" => "muted"
		  		],
		  		"exchange_complete" => [
		  			"value" => "exchange_complete",
		  			"name" => "换货完毕",
		  			"style" => "success"
		  		],
		  		"complete" => [
		  			"value" => "complete",
		  			"name" => "完成",
		  			"style" => "success"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按订单ID查询一条订单记录
	 * @param string $order_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["order_id"],  // 订单ID 
	 *          	  $rs["outer_id"],  // 外部ID 
	 *          	  $rs["goods_ids"],  // 商品清单 
	 *                $rs["_map_goods"][$goods_ids[n]]["goods_id"], // goods.goods_id
	 *          	  $rs["item_ids"],  // 单品清单 
	 *                $rs["_map_item"][$item_ids[n]]["item_id"], // item.item_id
	 *          	  $rs["total"],  // 金额 
	 *          	  $rs["freight"],  // 运费 
	 *          	  $rs["total_cost"],  // 实付金额 
	 *          	  $rs["money_cost"],  // 消费货币 
	 *          	  $rs["coin_cost"],  // 消费积分 
	 *          	  $rs["bitcoin_cost"],  // 消费代币 
	 *          	  $rs["freight_cost"],  // 实付运费 
	 *          	  $rs["payment_detail"],  // 支付明细 
	 *          	  $rs["user_id"],  // 用户 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["name"],  // 收件人 
	 *          	  $rs["area"],  // 国家地区 
	 *          	  $rs["prvn"],  // 省份 
	 *          	  $rs["city"],  // 城市 
	 *          	  $rs["town"],  // 区县 
	 *          	  $rs["zipcode"],  // 邮编 
	 *          	  $rs["address"],  // 收货地址 
	 *          	  $rs["mobile"],  // 联系电话 
	 *          	  $rs["payment"],  // 付款方式 
	 *          	  $rs["shipping_id"],  // 物流 
	 *                $rs["shipping_shipping_id"], // shipping.shipping_id
	 *          	  $rs["tracking_no"],  // 物流单号 
	 *          	  $rs["freight_in"],  // 物流成本 
	 *          	  $rs["status"],  // 订单状态 
	 *          	  $rs["remark"],  // 备注 
	 *          	  $rs["snapshot"],  // 快照 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 *                $rs["_map_goods"][$goods_ids[n]]["created_at"], // goods.created_at
	 *                $rs["_map_goods"][$goods_ids[n]]["updated_at"], // goods.updated_at
	 *                $rs["_map_goods"][$goods_ids[n]]["instance"], // goods.instance
	 *                $rs["_map_goods"][$goods_ids[n]]["name"], // goods.name
	 *                $rs["_map_goods"][$goods_ids[n]]["slug"], // goods.slug
	 *                $rs["_map_goods"][$goods_ids[n]]["tags"], // goods.tags
	 *                $rs["_map_goods"][$goods_ids[n]]["category_ids"], // goods.category_ids
	 *                $rs["_map_goods"][$goods_ids[n]]["recommend_ids"], // goods.recommend_ids
	 *                $rs["_map_goods"][$goods_ids[n]]["summary"], // goods.summary
	 *                $rs["_map_goods"][$goods_ids[n]]["cover"], // goods.cover
	 *                $rs["_map_goods"][$goods_ids[n]]["images"], // goods.images
	 *                $rs["_map_goods"][$goods_ids[n]]["videos"], // goods.videos
	 *                $rs["_map_goods"][$goods_ids[n]]["params"], // goods.params
	 *                $rs["_map_goods"][$goods_ids[n]]["content"], // goods.content
	 *                $rs["_map_goods"][$goods_ids[n]]["content_faq"], // goods.content_faq
	 *                $rs["_map_goods"][$goods_ids[n]]["content_serv"], // goods.content_serv
	 *                $rs["_map_goods"][$goods_ids[n]]["sku_cnt"], // goods.sku_cnt
	 *                $rs["_map_goods"][$goods_ids[n]]["sku_sum"], // goods.sku_sum
	 *                $rs["_map_goods"][$goods_ids[n]]["shipped_sum"], // goods.shipped_sum
	 *                $rs["_map_goods"][$goods_ids[n]]["available_sum"], // goods.available_sum
	 *                $rs["_map_goods"][$goods_ids[n]]["lower_price"], // goods.lower_price
	 *                $rs["_map_goods"][$goods_ids[n]]["sale_way"], // goods.sale_way
	 *                $rs["_map_goods"][$goods_ids[n]]["opened_at"], // goods.opened_at
	 *                $rs["_map_goods"][$goods_ids[n]]["closed_at"], // goods.closed_at
	 *                $rs["_map_goods"][$goods_ids[n]]["pay_duration"], // goods.pay_duration
	 *                $rs["_map_goods"][$goods_ids[n]]["status"], // goods.status
	 *                $rs["_map_goods"][$goods_ids[n]]["events"], // goods.events
	 *                $rs["_map_item"][$item_ids[n]]["created_at"], // item.created_at
	 *                $rs["_map_item"][$item_ids[n]]["updated_at"], // item.updated_at
	 *                $rs["_map_item"][$item_ids[n]]["goods_id"], // item.goods_id
	 *                $rs["_map_item"][$item_ids[n]]["name"], // item.name
	 *                $rs["_map_item"][$item_ids[n]]["params"], // item.params
	 *                $rs["_map_item"][$item_ids[n]]["price"], // item.price
	 *                $rs["_map_item"][$item_ids[n]]["price_low"], // item.price_low
	 *                $rs["_map_item"][$item_ids[n]]["price_in"], // item.price_in
	 *                $rs["_map_item"][$item_ids[n]]["price_val"], // item.price_val
	 *                $rs["_map_item"][$item_ids[n]]["promotion"], // item.promotion
	 *                $rs["_map_item"][$item_ids[n]]["payment"], // item.payment
	 *                $rs["_map_item"][$item_ids[n]]["delivery"], // item.delivery
	 *                $rs["_map_item"][$item_ids[n]]["weight"], // item.weight
	 *                $rs["_map_item"][$item_ids[n]]["volume"], // item.volume
	 *                $rs["_map_item"][$item_ids[n]]["sum"], // item.sum
	 *                $rs["_map_item"][$item_ids[n]]["shipped_sum"], // item.shipped_sum
	 *                $rs["_map_item"][$item_ids[n]]["available_sum"], // item.available_sum
	 *                $rs["_map_item"][$item_ids[n]]["status"], // item.status
	 *                $rs["_map_item"][$item_ids[n]]["images"], // item.images
	 *                $rs["_map_item"][$item_ids[n]]["content"], // item.content
	 *                $rs["_map_item"][$item_ids[n]]["shipping_ids"], // item.shipping_ids
	 *                $rs["shipping_created_at"], // shipping.created_at
	 *                $rs["shipping_updated_at"], // shipping.updated_at
	 *                $rs["shipping_company"], // shipping.company
	 *                $rs["shipping_name"], // shipping.name
	 *                $rs["shipping_products"], // shipping.products
	 *                $rs["shipping_scope"], // shipping.scope
	 *                $rs["shipping_formula"], // shipping.formula
	 *                $rs["shipping_api"], // shipping.api
	 */
	public function getByOrderId( $order_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "order.order_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_order as order", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "order.user_id"); // 连接用户
   		$qb->leftJoin("xpmsns_pages_shipping as shipping", "shipping.shipping_id", "=", "order.shipping_id"); // 连接物流
		$qb->where('order_id', '=', $order_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

  		$goods_ids = []; // 读取 inWhere goods 数据
		$goods_ids = array_merge($goods_ids, is_array($rs["goods_ids"]) ? $rs["goods_ids"] : [$rs["goods_ids"]]);
 		$item_ids = []; // 读取 inWhere item 数据
		$item_ids = array_merge($item_ids, is_array($rs["item_ids"]) ? $rs["item_ids"] : [$rs["item_ids"]]);
 
  		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$rs["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere item 数据
		if ( !empty($inwhereSelect["item"]) && method_exists("\\Xpmsns\\Pages\\Model\\Item", 'getInByItemId') ) {
			$item_ids = array_unique($item_ids);
			$selectFields = $inwhereSelect["item"];
			$rs["_map_item"] = (new \Xpmsns\Pages\Model\Item)->getInByItemId($item_ids, $selectFields);
		}
 
		return $rs;
	}

		

	/**
	 * 按订单ID查询一组订单记录
	 * @param array   $order_ids 唯一主键数组 ["$order_id1","$order_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 订单记录MAP {"order_id1":{"key":"value",...}...}
	 */
	public function getInByOrderId($order_ids, $select=["order.order_id","goods.name","item.name","order.name","order.mobile","order.total_cost","order.created_at","order.updated_at","order.status"], $order=["order.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "order.order_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_order as order", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "order.user_id"); // 连接用户
   		$qb->leftJoin("xpmsns_pages_shipping as shipping", "shipping.shipping_id", "=", "order.shipping_id"); // 连接物流
		$qb->whereIn('order.order_id', $order_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

  		$goods_ids = []; // 读取 inWhere goods 数据
 		$item_ids = []; // 读取 inWhere item 数据
 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['order_id']] = $rs;
			
  			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_ids"]) ? $rs["goods_ids"] : [$rs["goods_ids"]]);
 			// for inWhere item
			$item_ids = array_merge($item_ids, is_array($rs["item_ids"]) ? $rs["item_ids"] : [$rs["item_ids"]]);
 		}

  		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$map["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere item 数据
		if ( !empty($inwhereSelect["item"]) && method_exists("\\Xpmsns\\Pages\\Model\\Item", 'getInByItemId') ) {
			$item_ids = array_unique($item_ids);
			$selectFields = $inwhereSelect["item"];
			$map["_map_item"] = (new \Xpmsns\Pages\Model\Item)->getInByItemId($item_ids, $selectFields);
		}
 

		return $map;
	}


	/**
	 * 按订单ID保存订单记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByOrderId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "order.order_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("order_id", $data, ["order_id"], ['_id', 'order_id']);
		return $this->getByOrderId( $rs['order_id'], $select );
	}


	/**
	 * 添加订单记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["order_id"]) ) { 
			$data["order_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排订单记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 订单记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["order.order_id","goods.name","item.name","order.name","order.mobile","order.total_cost","order.created_at","order.updated_at","order.status"], $order=["order.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "order.order_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_order as order", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "order.user_id"); // 连接用户
   		$qb->leftJoin("xpmsns_pages_shipping as shipping", "shipping.shipping_id", "=", "order.shipping_id"); // 连接物流


		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


  		$goods_ids = []; // 读取 inWhere goods 数据
 		$item_ids = []; // 读取 inWhere item 数据
 		foreach ($data as & $rs ) {
			$this->format($rs);
			
  			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_ids"]) ? $rs["goods_ids"] : [$rs["goods_ids"]]);
 			// for inWhere item
			$item_ids = array_merge($item_ids, is_array($rs["item_ids"]) ? $rs["item_ids"] : [$rs["item_ids"]]);
 		}

  		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$data["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere item 数据
		if ( !empty($inwhereSelect["item"]) && method_exists("\\Xpmsns\\Pages\\Model\\Item", 'getInByItemId') ) {
			$item_ids = array_unique($item_ids);
			$selectFields = $inwhereSelect["item"];
			$data["_map_item"] = (new \Xpmsns\Pages\Model\Item)->getInByItemId($item_ids, $selectFields);
		}
 
		return $data;
	
	}


	/**
	 * 按条件检索订单记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["order.order_id","goods.name","item.name","order.name","order.mobile","order.total_cost","order.created_at","order.updated_at","order.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["order_id"] 按订单ID查询 ( = )
	 *			      $query["outer_id"] 按外部ID查询 ( = )
	 *			      $query["status"] 按订单状态查询 ( = )
	 *			      $query["name"] 按收件人查询 ( LIKE )
	 *			      $query["user_user_id"] 按查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 订单记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["order_id"],  // 订单ID 
	 *               	["outer_id"],  // 外部ID 
	 *               	["goods_ids"],  // 商品清单 
	 *               	["goods"][$goods_ids[n]]["goods_id"], // goods.goods_id
	 *               	["item_ids"],  // 单品清单 
	 *               	["item"][$item_ids[n]]["item_id"], // item.item_id
	 *               	["total"],  // 金额 
	 *               	["freight"],  // 运费 
	 *               	["total_cost"],  // 实付金额 
	 *               	["money_cost"],  // 消费货币 
	 *               	["coin_cost"],  // 消费积分 
	 *               	["bitcoin_cost"],  // 消费代币 
	 *               	["freight_cost"],  // 实付运费 
	 *               	["payment_detail"],  // 支付明细 
	 *               	["user_id"],  // 用户 
	 *               	["user_user_id"], // user.user_id
	 *               	["name"],  // 收件人 
	 *               	["area"],  // 国家地区 
	 *               	["prvn"],  // 省份 
	 *               	["city"],  // 城市 
	 *               	["town"],  // 区县 
	 *               	["zipcode"],  // 邮编 
	 *               	["address"],  // 收货地址 
	 *               	["mobile"],  // 联系电话 
	 *               	["payment"],  // 付款方式 
	 *               	["shipping_id"],  // 物流 
	 *               	["shipping_shipping_id"], // shipping.shipping_id
	 *               	["tracking_no"],  // 物流单号 
	 *               	["freight_in"],  // 物流成本 
	 *               	["status"],  // 订单状态 
	 *               	["remark"],  // 备注 
	 *               	["snapshot"],  // 快照 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["user_created_at"], // user.created_at
	 *               	["user_updated_at"], // user.updated_at
	 *               	["user_group_id"], // user.group_id
	 *               	["user_name"], // user.name
	 *               	["user_idno"], // user.idno
	 *               	["user_idtype"], // user.idtype
	 *               	["user_iddoc"], // user.iddoc
	 *               	["user_nickname"], // user.nickname
	 *               	["user_sex"], // user.sex
	 *               	["user_city"], // user.city
	 *               	["user_province"], // user.province
	 *               	["user_country"], // user.country
	 *               	["user_headimgurl"], // user.headimgurl
	 *               	["user_language"], // user.language
	 *               	["user_birthday"], // user.birthday
	 *               	["user_bio"], // user.bio
	 *               	["user_bgimgurl"], // user.bgimgurl
	 *               	["user_mobile"], // user.mobile
	 *               	["user_mobile_nation"], // user.mobile_nation
	 *               	["user_mobile_full"], // user.mobile_full
	 *               	["user_email"], // user.email
	 *               	["user_contact_name"], // user.contact_name
	 *               	["user_contact_tel"], // user.contact_tel
	 *               	["user_title"], // user.title
	 *               	["user_company"], // user.company
	 *               	["user_zip"], // user.zip
	 *               	["user_address"], // user.address
	 *               	["user_remark"], // user.remark
	 *               	["user_tag"], // user.tag
	 *               	["user_user_verified"], // user.user_verified
	 *               	["user_name_verified"], // user.name_verified
	 *               	["user_verify"], // user.verify
	 *               	["user_verify_data"], // user.verify_data
	 *               	["user_mobile_verified"], // user.mobile_verified
	 *               	["user_email_verified"], // user.email_verified
	 *               	["user_extra"], // user.extra
	 *               	["user_password"], // user.password
	 *               	["user_pay_password"], // user.pay_password
	 *               	["user_status"], // user.status
	 *               	["user_inviter"], // user.inviter
	 *               	["user_follower_cnt"], // user.follower_cnt
	 *               	["user_following_cnt"], // user.following_cnt
	 *               	["goods"][$goods_ids[n]]["created_at"], // goods.created_at
	 *               	["goods"][$goods_ids[n]]["updated_at"], // goods.updated_at
	 *               	["goods"][$goods_ids[n]]["instance"], // goods.instance
	 *               	["goods"][$goods_ids[n]]["name"], // goods.name
	 *               	["goods"][$goods_ids[n]]["slug"], // goods.slug
	 *               	["goods"][$goods_ids[n]]["tags"], // goods.tags
	 *               	["goods"][$goods_ids[n]]["category_ids"], // goods.category_ids
	 *               	["goods"][$goods_ids[n]]["recommend_ids"], // goods.recommend_ids
	 *               	["goods"][$goods_ids[n]]["summary"], // goods.summary
	 *               	["goods"][$goods_ids[n]]["cover"], // goods.cover
	 *               	["goods"][$goods_ids[n]]["images"], // goods.images
	 *               	["goods"][$goods_ids[n]]["videos"], // goods.videos
	 *               	["goods"][$goods_ids[n]]["params"], // goods.params
	 *               	["goods"][$goods_ids[n]]["content"], // goods.content
	 *               	["goods"][$goods_ids[n]]["content_faq"], // goods.content_faq
	 *               	["goods"][$goods_ids[n]]["content_serv"], // goods.content_serv
	 *               	["goods"][$goods_ids[n]]["sku_cnt"], // goods.sku_cnt
	 *               	["goods"][$goods_ids[n]]["sku_sum"], // goods.sku_sum
	 *               	["goods"][$goods_ids[n]]["shipped_sum"], // goods.shipped_sum
	 *               	["goods"][$goods_ids[n]]["available_sum"], // goods.available_sum
	 *               	["goods"][$goods_ids[n]]["lower_price"], // goods.lower_price
	 *               	["goods"][$goods_ids[n]]["sale_way"], // goods.sale_way
	 *               	["goods"][$goods_ids[n]]["opened_at"], // goods.opened_at
	 *               	["goods"][$goods_ids[n]]["closed_at"], // goods.closed_at
	 *               	["goods"][$goods_ids[n]]["pay_duration"], // goods.pay_duration
	 *               	["goods"][$goods_ids[n]]["status"], // goods.status
	 *               	["goods"][$goods_ids[n]]["events"], // goods.events
	 *               	["item"][$item_ids[n]]["created_at"], // item.created_at
	 *               	["item"][$item_ids[n]]["updated_at"], // item.updated_at
	 *               	["item"][$item_ids[n]]["goods_id"], // item.goods_id
	 *               	["item"][$item_ids[n]]["name"], // item.name
	 *               	["item"][$item_ids[n]]["params"], // item.params
	 *               	["item"][$item_ids[n]]["price"], // item.price
	 *               	["item"][$item_ids[n]]["price_low"], // item.price_low
	 *               	["item"][$item_ids[n]]["price_in"], // item.price_in
	 *               	["item"][$item_ids[n]]["price_val"], // item.price_val
	 *               	["item"][$item_ids[n]]["promotion"], // item.promotion
	 *               	["item"][$item_ids[n]]["payment"], // item.payment
	 *               	["item"][$item_ids[n]]["delivery"], // item.delivery
	 *               	["item"][$item_ids[n]]["weight"], // item.weight
	 *               	["item"][$item_ids[n]]["volume"], // item.volume
	 *               	["item"][$item_ids[n]]["sum"], // item.sum
	 *               	["item"][$item_ids[n]]["shipped_sum"], // item.shipped_sum
	 *               	["item"][$item_ids[n]]["available_sum"], // item.available_sum
	 *               	["item"][$item_ids[n]]["status"], // item.status
	 *               	["item"][$item_ids[n]]["images"], // item.images
	 *               	["item"][$item_ids[n]]["content"], // item.content
	 *               	["item"][$item_ids[n]]["shipping_ids"], // item.shipping_ids
	 *               	["shipping_created_at"], // shipping.created_at
	 *               	["shipping_updated_at"], // shipping.updated_at
	 *               	["shipping_company"], // shipping.company
	 *               	["shipping_name"], // shipping.name
	 *               	["shipping_products"], // shipping.products
	 *               	["shipping_scope"], // shipping.scope
	 *               	["shipping_formula"], // shipping.formula
	 *               	["shipping_api"], // shipping.api
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["order.order_id","goods.name","item.name","order.name","order.mobile","order.total_cost","order.created_at","order.updated_at","order.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "order.order_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_order as order", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "order.user_id"); // 连接用户
   		$qb->leftJoin("xpmsns_pages_shipping as shipping", "shipping.shipping_id", "=", "order.shipping_id"); // 连接物流

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("order.order_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("order.outer_id","like", "%{$query['keyword']}%");
				$qb->orWhere("order.user_id","like", "%{$query['keyword']}%");
				$qb->orWhere("order.name","like", "%{$query['keyword']}%");
				$qb->orWhere("order.mobile","like", "%{$query['keyword']}%");
			});
		}


		// 按订单ID查询 (=)  
		if ( array_key_exists("order_id", $query) &&!empty($query['order_id']) ) {
			$qb->where("order.order_id", '=', "{$query['order_id']}" );
		}
		  
		// 按外部ID查询 (=)  
		if ( array_key_exists("outer_id", $query) &&!empty($query['outer_id']) ) {
			$qb->where("order.outer_id", '=', "{$query['outer_id']}" );
		}
		  
		// 按订单状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("order.status", '=', "{$query['status']}" );
		}
		  
		// 按收件人查询 (LIKE)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("order.name", 'like', "%{$query['name']}%" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("user_user_id", $query) &&!empty($query['user_user_id']) ) {
			$qb->where("user.user_id", '=', "{$query['user_user_id']}" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("order.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("order.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$orders = $qb->select( $select )->pgArray($perpage, ['order._id'], 'page', $page);

  		$goods_ids = []; // 读取 inWhere goods 数据
 		$item_ids = []; // 读取 inWhere item 数据
 		foreach ($orders['data'] as & $rs ) {
			$this->format($rs);
			
  			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_ids"]) ? $rs["goods_ids"] : [$rs["goods_ids"]]);
 			// for inWhere item
			$item_ids = array_merge($item_ids, is_array($rs["item_ids"]) ? $rs["item_ids"] : [$rs["item_ids"]]);
 		}

  		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$orders["goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere item 数据
		if ( !empty($inwhereSelect["item"]) && method_exists("\\Xpmsns\\Pages\\Model\\Item", 'getInByItemId') ) {
			$item_ids = array_unique($item_ids);
			$selectFields = $inwhereSelect["item"];
			$orders["item"] = (new \Xpmsns\Pages\Model\Item)->getInByItemId($item_ids, $selectFields);
		}
 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$orders['_sql'] = $qb->getSql();
			$orders['query'] = $query;
		}

		return $orders;
	}

	/**
	 * 格式化读取字段
	 * @param  array $select 选中字段
	 * @return array $inWhere 读取字段
	 */
	public function formatSelect( & $select ) {
		// 过滤 inWhere 查询字段
		$inwhereSelect = []; $linkSelect = [];
		foreach ($select as $idx=>$fd ) {
			
			// 添加本表前缀
			if ( !strpos( $fd, ".")  ) {
				$select[$idx] = "order." .$select[$idx];
				continue;
			}
			
			//  连接用户 (user as user )
			if ( trim($fd) == "user.*" || trim($fd) == "user.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\User", 'getFields') ) {
					$fields = \Xpmsns\User\Model\User::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "user.{$field} as user_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "order.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "user." ) === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "user.") === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			
			// 连接商品 (goods as goods )
			if ( strpos( $fd, "goods." ) === 0 || strpos("goods.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["goods"][] = trim($arr[1]);
				$inwhereSelect["goods"][] = "goods_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "order.goods_ids");
				}
			}
			
			// 连接单品 (item as item )
			if ( strpos( $fd, "item." ) === 0 || strpos("item.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["item"][] = trim($arr[1]);
				$inwhereSelect["item"][] = "item_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "order.item_ids");
				}
			}
			
			//  连接物流 (shipping as shipping )
			if ( trim($fd) == "shipping.*" || trim($fd) == "shipping.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getFields') ) {
					$fields = \Xpmsns\Pages\Model\Shipping::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "shipping.{$field} as shipping_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "order.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "shipping." ) === 0 ) {
				$as = str_replace('shipping.', 'shipping_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "shipping.") === 0 ) {
				$as = str_replace('shipping.', 'shipping_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

		}

		// filter 查询字段
		foreach ($inwhereSelect as & $iws ) {
			if ( is_array($iws) ) {
				$iws = array_unique(array_filter($iws));
			}
		}

		$select = array_unique(array_merge($linkSelect, $select));
		return $inwhereSelect;
	}

	/**
	 * 返回所有字段
	 * @return array 字段清单
	 */
	public static function getFields() {
		return [
			"order_id",  // 订单ID
			"outer_id",  // 外部ID
			"goods_ids",  // 商品清单
			"item_ids",  // 单品清单
			"total",  // 金额
			"freight",  // 运费
			"total_cost",  // 实付金额
			"money_cost",  // 消费货币
			"coin_cost",  // 消费积分
			"bitcoin_cost",  // 消费代币
			"freight_cost",  // 实付运费
			"payment_detail",  // 支付明细
			"user_id",  // 用户
			"name",  // 收件人
			"area",  // 国家地区
			"prvn",  // 省份
			"city",  // 城市
			"town",  // 区县
			"zipcode",  // 邮编
			"address",  // 收货地址
			"mobile",  // 联系电话
			"payment",  // 付款方式
			"shipping_id",  // 物流
			"tracking_no",  // 物流单号
			"freight_in",  // 物流成本
			"status",  // 订单状态
			"remark",  // 备注
			"snapshot",  // 快照
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>