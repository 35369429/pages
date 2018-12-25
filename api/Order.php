<?php
/**
 * Class Order 
 * 订单数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-25 21:07:33
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
    protected function custFun1() {

    }
    // @KEEP END


	/**
	 * 查询一条订单记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["order.order_id","order.outer_id","order.total","order.freight","order.total_cost","order.money_cost","order.coin_cost","order.bitcoin_cost","order.freight_cost","order.payment_detail","order.name","order.area","order.prvn","order.city","order.town","order.zipcode","order.address","order.mobile","order.tracking_no","order.status","order.created_at","order.updated_at","user.user_id"]
	 * 				 $query['order_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["order.order_id","order.outer_id","order.total","order.freight","order.total_cost","order.money_cost","order.coin_cost","order.bitcoin_cost","order.freight_cost","order.payment_detail","order.name","order.area","order.prvn","order.city","order.town","order.zipcode","order.address","order.mobile","order.tracking_no","order.status","order.created_at","order.updated_at","user.user_id"]
	 * 				 $data['order_id']  按查询 (多条用 "," 分割)
	 *
	 * @return array 订单记录 Key Value 结构数据 
	 *               	["order_id"],  // 订单ID 
	 *               	["outer_id"],  // 外部ID 
	 *               	["goods_ids"],  // 商品清单 
	*               	["_map_goods"][$goods_ids[n]]["goods_id"], // goods.goods_id
	 *               	["items_ids"],  // 单品清单 
	*               	["_map_item"][$items_ids[n]]["item_id"], // item.item_id
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
	*               	["_map_shipping"][$shipping_id[n]]["shipping_id"], // shipping.shipping_id
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
	*               	["_map_goods"][$goods_ids[n]]["created_at"], // goods.created_at
	*               	["_map_goods"][$goods_ids[n]]["updated_at"], // goods.updated_at
	*               	["_map_goods"][$goods_ids[n]]["instance"], // goods.instance
	*               	["_map_goods"][$goods_ids[n]]["name"], // goods.name
	*               	["_map_goods"][$goods_ids[n]]["slug"], // goods.slug
	*               	["_map_goods"][$goods_ids[n]]["tags"], // goods.tags
	*               	["_map_goods"][$goods_ids[n]]["category_ids"], // goods.category_ids
	*               	["_map_goods"][$goods_ids[n]]["recommend_ids"], // goods.recommend_ids
	*               	["_map_goods"][$goods_ids[n]]["summary"], // goods.summary
	*               	["_map_goods"][$goods_ids[n]]["cover"], // goods.cover
	*               	["_map_goods"][$goods_ids[n]]["images"], // goods.images
	*               	["_map_goods"][$goods_ids[n]]["videos"], // goods.videos
	*               	["_map_goods"][$goods_ids[n]]["params"], // goods.params
	*               	["_map_goods"][$goods_ids[n]]["content"], // goods.content
	*               	["_map_goods"][$goods_ids[n]]["content_faq"], // goods.content_faq
	*               	["_map_goods"][$goods_ids[n]]["content_serv"], // goods.content_serv
	*               	["_map_goods"][$goods_ids[n]]["sku_cnt"], // goods.sku_cnt
	*               	["_map_goods"][$goods_ids[n]]["sku_sum"], // goods.sku_sum
	*               	["_map_goods"][$goods_ids[n]]["shipped_sum"], // goods.shipped_sum
	*               	["_map_goods"][$goods_ids[n]]["available_sum"], // goods.available_sum
	*               	["_map_goods"][$goods_ids[n]]["lower_price"], // goods.lower_price
	*               	["_map_goods"][$goods_ids[n]]["sale_way"], // goods.sale_way
	*               	["_map_goods"][$goods_ids[n]]["opened_at"], // goods.opened_at
	*               	["_map_goods"][$goods_ids[n]]["closed_at"], // goods.closed_at
	*               	["_map_goods"][$goods_ids[n]]["pay_duration"], // goods.pay_duration
	*               	["_map_goods"][$goods_ids[n]]["status"], // goods.status
	*               	["_map_goods"][$goods_ids[n]]["events"], // goods.events
	*               	["_map_item"][$items_ids[n]]["created_at"], // item.created_at
	*               	["_map_item"][$items_ids[n]]["updated_at"], // item.updated_at
	*               	["_map_item"][$items_ids[n]]["goods_id"], // item.goods_id
	*               	["_map_item"][$items_ids[n]]["name"], // item.name
	*               	["_map_item"][$items_ids[n]]["params"], // item.params
	*               	["_map_item"][$items_ids[n]]["price"], // item.price
	*               	["_map_item"][$items_ids[n]]["price_low"], // item.price_low
	*               	["_map_item"][$items_ids[n]]["price_in"], // item.price_in
	*               	["_map_item"][$items_ids[n]]["price_val"], // item.price_val
	*               	["_map_item"][$items_ids[n]]["promotion"], // item.promotion
	*               	["_map_item"][$items_ids[n]]["payment"], // item.payment
	*               	["_map_item"][$items_ids[n]]["delivery"], // item.delivery
	*               	["_map_item"][$items_ids[n]]["weight"], // item.weight
	*               	["_map_item"][$items_ids[n]]["volume"], // item.volume
	*               	["_map_item"][$items_ids[n]]["sum"], // item.sum
	*               	["_map_item"][$items_ids[n]]["shipped_sum"], // item.shipped_sum
	*               	["_map_item"][$items_ids[n]]["available_sum"], // item.available_sum
	*               	["_map_item"][$items_ids[n]]["status"], // item.status
	*               	["_map_item"][$items_ids[n]]["images"], // item.images
	*               	["_map_item"][$items_ids[n]]["content"], // item.content
	*               	["_map_shipping"][$shipping_id[n]]["created_at"], // shipping.created_at
	*               	["_map_shipping"][$shipping_id[n]]["updated_at"], // shipping.updated_at
	*               	["_map_shipping"][$shipping_id[n]]["company"], // shipping.company
	*               	["_map_shipping"][$shipping_id[n]]["name"], // shipping.name
	*               	["_map_shipping"][$shipping_id[n]]["products"], // shipping.products
	*               	["_map_shipping"][$shipping_id[n]]["scope"], // shipping.scope
	*               	["_map_shipping"][$shipping_id[n]]["formula"], // shipping.formula
	*               	["_map_shipping"][$shipping_id[n]]["api"], // shipping.api
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["order.order_id","order.outer_id","order.total","order.freight","order.total_cost","order.money_cost","order.coin_cost","order.bitcoin_cost","order.freight_cost","order.payment_detail","order.name","order.area","order.prvn","order.city","order.town","order.zipcode","order.address","order.mobile","order.tracking_no","order.status","order.created_at","order.updated_at","user.user_id"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按订单ID
		if ( !empty($data["order_id"]) ) {
			
			$keys = explode(',', $data["order_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Order;
				return $inst->getInByOrderId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Order;
			return $inst->getByOrderId($data["order_id"], $select);
        }
        
        // @KEEP BEGIN
        $test = "MP";
        // @KEEP END

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条订单记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['order_id'] 订单ID
	 *               $data['outer_id'] 外部ID
	 *               $data['goods_ids'] 商品清单
	 *               $data['items_ids'] 单品清单
	 *               $data['total'] 金额
	 *               $data['freight'] 运费
	 *               $data['total_cost'] 实付金额
	 *               $data['money_cost'] 消费货币
	 *               $data['coin_cost'] 消费积分
	 *               $data['bitcoin_cost'] 消费代币
	 *               $data['freight_cost'] 实付运费
	 *               $data['payment_detail'] 支付明细
	 *               $data['user_id'] 用户
	 *               $data['name'] 收件人
	 *               $data['area'] 国家地区
	 *               $data['prvn'] 省份
	 *               $data['city'] 城市
	 *               $data['town'] 区县
	 *               $data['zipcode'] 邮编
	 *               $data['address'] 收货地址
	 *               $data['mobile'] 联系电话
	 *               $data['payment'] 付款方式
	 *               $data['shipping_id'] 物流
	 *               $data['tracking_no'] 物流单号
	 *               $data['freight_in'] 物流成本
	 *               $data['status'] 订单状态
	 *               $data['remark'] 备注
	 *               $data['snapshot'] 快照
	 *
	 * @return array 新增的订单记录  @see get()
	 */
	protected function create( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		if (empty($data['goods_ids'])) {
			throw new Excp("缺少必填字段商品清单 (goods_ids)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['user_id'])) {
			throw new Excp("缺少必填字段用户 (user_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['name'])) {
			throw new Excp("缺少必填字段收件人 (name)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\Pages\Model\Order;
		$rs = $inst->create( $data );
		return $inst->getByOrderId($rs["order_id"]);
	}


	/**
	 * 更新一条订单记录
	 * @param  array $query GET 参数
	 * 				 $query['name=order_id']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['order_id'] 订单ID
	 *               $data['outer_id'] 外部ID
	 *               $data['goods_ids'] 商品清单
	 *               $data['items_ids'] 单品清单
	 *               $data['total'] 金额
	 *               $data['freight'] 运费
	 *               $data['total_cost'] 实付金额
	 *               $data['money_cost'] 消费货币
	 *               $data['coin_cost'] 消费积分
	 *               $data['bitcoin_cost'] 消费代币
	 *               $data['freight_cost'] 实付运费
	 *               $data['payment_detail'] 支付明细
	 *               $data['user_id'] 用户
	 *               $data['name'] 收件人
	 *               $data['area'] 国家地区
	 *               $data['prvn'] 省份
	 *               $data['city'] 城市
	 *               $data['town'] 区县
	 *               $data['zipcode'] 邮编
	 *               $data['address'] 收货地址
	 *               $data['mobile'] 联系电话
	 *               $data['payment'] 付款方式
	 *               $data['shipping_id'] 物流
	 *               $data['tracking_no'] 物流单号
	 *               $data['freight_in'] 物流成本
	 *               $data['status'] 订单状态
	 *               $data['remark'] 备注
	 *               $data['snapshot'] 快照
	 *
	 * @return array 更新的订单记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按订单ID
		if ( !empty($query["order_id"]) ) {
			$data = array_merge( $data, ["order_id"=>$query["order_id"]] );
			$inst = new \Xpmsns\Pages\Model\Order;
			$rs = $inst->updateBy("order_id",$data);
			return $inst->getByOrderId($rs["order_id"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条订单记录
	 * @param  array $query GET 参数
	 * 				 $query['order_id']  按订单ID 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按订单ID
		if ( !empty($query["order_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Order;
			$resp = $inst->remove($query['order_id'], "order_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索订单记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["order.order_id","order.outer_id","order.total","order.freight","order.total_cost","order.money_cost","order.coin_cost","order.bitcoin_cost","order.freight_cost","order.payment_detail","order.user_id","order.name","order.area","order.prvn","order.city","order.town","order.address","order.mobile","order.tracking_no","order.status","order.remark"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["order_id"] 按订单ID查询 ( AND = )
	 *			      $query["outer_id"] 按外部ID查询 ( AND = )
	 *			      $query["status"] 按订单状态查询 ( AND = )
	 *			      $query["name"] 按收件人查询 ( AND LIKE )
	 *			      $query["user_user_id"] 按查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=order_id","name=outer_id","name=total","name=freight","name=total_cost","name=money_cost","name=coin_cost","name=bitcoin_cost","name=freight_cost","name=payment_detail","name=user_id","name=name","name=area","name=prvn","name=city","name=town","name=address","name=mobile","name=tracking_no","name=status","name=remark"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["order_id"] 按订单ID查询 ( AND = )
	 *			      $data["outer_id"] 按外部ID查询 ( AND = )
	 *			      $data["status"] 按订单状态查询 ( AND = )
	 *			      $data["name"] 按收件人查询 ( AND LIKE )
	 *			      $data["user_user_id"] 按查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 订单记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
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
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["order.order_id","order.outer_id","order.total","order.freight","order.total_cost","order.money_cost","order.coin_cost","order.bitcoin_cost","order.freight_cost","order.payment_detail","order.user_id","order.name","order.area","order.prvn","order.city","order.town","order.address","order.mobile","order.tracking_no","order.status","order.remark"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Order;
		return $inst->search( $data );
	}

	/**
	 * 文件上传接口 (上传控件名称 )
	 * @param  array $query [description]
	 *               $query["private"]  上传文件为私有文件
	 * @param  [type] $data  [description]
	 * @return array 文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	protected function upload( $query, $data, $files ) {
		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		$fname = $files['file']['tmp_name'];
		if ( $query['private'] ) {
			$media = new \Xpmse\Media(["host" => Utils::getHome(), 'private'=>true]);
		} else {
			$media = new \Xpmse\Media(["host" => Utils::getHome()]);
		}
		$ext = $media->getExt($fname);
		$rs = $media->uploadFile($fname, $ext);
		return $rs;
	}

}