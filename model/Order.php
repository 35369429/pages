<?php
/**
 * Class Order 
 * 订单数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-25 21:07:35
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

        // 校验&读取商品信息
        $goods_ids = is_string($data["goods_ids"]) ?  explode(",",$data["goods_ids"]) : [];
        if ( empty($goods_ids) ) {
            throw New Excp("请提供商品IDs", 404, ["data"=>$data]);
        }
        
        // 生成商品快照
        $g = new Goods();
        $goods = $g->searchGoods([
            "goods_ids" => $goods_ids,
            "select" => ["*"],
        ]);

        // 保存单品信息
        $item_ids = is_string($data["item_ids"]) ?  explode(",",$data["item_ids"]) : [];
        
        

        return $item_ids;

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
		$this->putColumn( 'items_ids', $this->type("text", ["json"=>true, "null"=>true]));
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
		$this->putColumn( 'snapshot', $this->type("longText", ["null"=>true]));

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
	 *          	  $rs["items_ids"],  // 单品清单 
	 *                $rs["_map_item"][$items_ids[n]]["item_id"], // item.item_id
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
	 *                $rs["_map_shipping"][$shipping_id[n]]["shipping_id"], // shipping.shipping_id
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
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
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
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_idtype"], // user.idtype
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
	 *                $rs["_map_item"][$items_ids[n]]["created_at"], // item.created_at
	 *                $rs["_map_item"][$items_ids[n]]["updated_at"], // item.updated_at
	 *                $rs["_map_item"][$items_ids[n]]["goods_id"], // item.goods_id
	 *                $rs["_map_item"][$items_ids[n]]["name"], // item.name
	 *                $rs["_map_item"][$items_ids[n]]["params"], // item.params
	 *                $rs["_map_item"][$items_ids[n]]["price"], // item.price
	 *                $rs["_map_item"][$items_ids[n]]["price_low"], // item.price_low
	 *                $rs["_map_item"][$items_ids[n]]["price_in"], // item.price_in
	 *                $rs["_map_item"][$items_ids[n]]["price_val"], // item.price_val
	 *                $rs["_map_item"][$items_ids[n]]["promotion"], // item.promotion
	 *                $rs["_map_item"][$items_ids[n]]["payment"], // item.payment
	 *                $rs["_map_item"][$items_ids[n]]["delivery"], // item.delivery
	 *                $rs["_map_item"][$items_ids[n]]["weight"], // item.weight
	 *                $rs["_map_item"][$items_ids[n]]["volume"], // item.volume
	 *                $rs["_map_item"][$items_ids[n]]["sum"], // item.sum
	 *                $rs["_map_item"][$items_ids[n]]["shipped_sum"], // item.shipped_sum
	 *                $rs["_map_item"][$items_ids[n]]["available_sum"], // item.available_sum
	 *                $rs["_map_item"][$items_ids[n]]["status"], // item.status
	 *                $rs["_map_item"][$items_ids[n]]["images"], // item.images
	 *                $rs["_map_item"][$items_ids[n]]["content"], // item.content
	 *                $rs["_map_shipping"][$shipping_id[n]]["created_at"], // shipping.created_at
	 *                $rs["_map_shipping"][$shipping_id[n]]["updated_at"], // shipping.updated_at
	 *                $rs["_map_shipping"][$shipping_id[n]]["company"], // shipping.company
	 *                $rs["_map_shipping"][$shipping_id[n]]["name"], // shipping.name
	 *                $rs["_map_shipping"][$shipping_id[n]]["products"], // shipping.products
	 *                $rs["_map_shipping"][$shipping_id[n]]["scope"], // shipping.scope
	 *                $rs["_map_shipping"][$shipping_id[n]]["formula"], // shipping.formula
	 *                $rs["_map_shipping"][$shipping_id[n]]["api"], // shipping.api
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
		$item_ids = array_merge($item_ids, is_array($rs["items_ids"]) ? $rs["items_ids"] : [$rs["items_ids"]]);
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_id"]) ? $rs["shipping_id"] : [$rs["shipping_id"]]);

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
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$rs["_map_shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
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
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['order_id']] = $rs;
			
  			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_ids"]) ? $rs["goods_ids"] : [$rs["goods_ids"]]);
 			// for inWhere item
			$item_ids = array_merge($item_ids, is_array($rs["items_ids"]) ? $rs["items_ids"] : [$rs["items_ids"]]);
 			// for inWhere shipping
			$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_id"]) ? $rs["shipping_id"] : [$rs["shipping_id"]]);
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
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$map["_map_shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
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
   

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


  		$goods_ids = []; // 读取 inWhere goods 数据
 		$item_ids = []; // 读取 inWhere item 数据
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
  			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_ids"]) ? $rs["goods_ids"] : [$rs["goods_ids"]]);
 			// for inWhere item
			$item_ids = array_merge($item_ids, is_array($rs["items_ids"]) ? $rs["items_ids"] : [$rs["items_ids"]]);
 			// for inWhere shipping
			$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_id"]) ? $rs["shipping_id"] : [$rs["shipping_id"]]);
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
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$data["_map_shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
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
	 *               	["items_ids"],  // 单品清单 
	 *               	["item"][$items_ids[n]]["item_id"], // item.item_id
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
	 *               	["shipping"][$shipping_id[n]]["shipping_id"], // shipping.shipping_id
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
	 *               	["user_iddoc"], // user.iddoc
	 *               	["user_nickname"], // user.nickname
	 *               	["user_sex"], // user.sex
	 *               	["user_city"], // user.city
	 *               	["user_province"], // user.province
	 *               	["user_country"], // user.country
	 *               	["user_headimgurl"], // user.headimgurl
	 *               	["user_language"], // user.language
	 *               	["user_birthday"], // user.birthday
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
	 *               	["user_bio"], // user.bio
	 *               	["user_bgimgurl"], // user.bgimgurl
	 *               	["user_idtype"], // user.idtype
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
	 *               	["item"][$items_ids[n]]["created_at"], // item.created_at
	 *               	["item"][$items_ids[n]]["updated_at"], // item.updated_at
	 *               	["item"][$items_ids[n]]["goods_id"], // item.goods_id
	 *               	["item"][$items_ids[n]]["name"], // item.name
	 *               	["item"][$items_ids[n]]["params"], // item.params
	 *               	["item"][$items_ids[n]]["price"], // item.price
	 *               	["item"][$items_ids[n]]["price_low"], // item.price_low
	 *               	["item"][$items_ids[n]]["price_in"], // item.price_in
	 *               	["item"][$items_ids[n]]["price_val"], // item.price_val
	 *               	["item"][$items_ids[n]]["promotion"], // item.promotion
	 *               	["item"][$items_ids[n]]["payment"], // item.payment
	 *               	["item"][$items_ids[n]]["delivery"], // item.delivery
	 *               	["item"][$items_ids[n]]["weight"], // item.weight
	 *               	["item"][$items_ids[n]]["volume"], // item.volume
	 *               	["item"][$items_ids[n]]["sum"], // item.sum
	 *               	["item"][$items_ids[n]]["shipped_sum"], // item.shipped_sum
	 *               	["item"][$items_ids[n]]["available_sum"], // item.available_sum
	 *               	["item"][$items_ids[n]]["status"], // item.status
	 *               	["item"][$items_ids[n]]["images"], // item.images
	 *               	["item"][$items_ids[n]]["content"], // item.content
	 *               	["shipping"][$shipping_id[n]]["created_at"], // shipping.created_at
	 *               	["shipping"][$shipping_id[n]]["updated_at"], // shipping.updated_at
	 *               	["shipping"][$shipping_id[n]]["company"], // shipping.company
	 *               	["shipping"][$shipping_id[n]]["name"], // shipping.name
	 *               	["shipping"][$shipping_id[n]]["products"], // shipping.products
	 *               	["shipping"][$shipping_id[n]]["scope"], // shipping.scope
	 *               	["shipping"][$shipping_id[n]]["formula"], // shipping.formula
	 *               	["shipping"][$shipping_id[n]]["api"], // shipping.api
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
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		foreach ($orders['data'] as & $rs ) {
			$this->format($rs);
			
  			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_ids"]) ? $rs["goods_ids"] : [$rs["goods_ids"]]);
 			// for inWhere item
			$item_ids = array_merge($item_ids, is_array($rs["items_ids"]) ? $rs["items_ids"] : [$rs["items_ids"]]);
 			// for inWhere shipping
			$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_id"]) ? $rs["shipping_id"] : [$rs["shipping_id"]]);
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
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$orders["shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
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
					array_push($linkSelect, "order.items_ids");
				}
			}
			
			// 连接物流 (shipping as shipping )
			if ( strpos( $fd, "shipping." ) === 0 || strpos("shipping.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["shipping"][] = trim($arr[1]);
				$inwhereSelect["shipping"][] = "shipping_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "order.shipping_id");
				}
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
			"items_ids",  // 单品清单
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