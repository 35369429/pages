<?php
/**
 * Class Item 
 * 单品数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 23:25:22
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                        

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Item extends Api {

	/**
	 * 单品数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条单品记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["item.item_id","item.name","item.params","item.price","item.price_low","item.price_in","item.price_val","item.promotion","item.payment","item.shipping_ids","item.weight","item.volume","item.sum","item.shipped_sum","item.available_sum","item.status","item.images","item.content","item.created_at","item.updated_at","shipping.company","shipping.name"]
	 * 				 $query['item_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["item.item_id","item.name","item.params","item.price","item.price_low","item.price_in","item.price_val","item.promotion","item.payment","item.shipping_ids","item.weight","item.volume","item.sum","item.shipped_sum","item.available_sum","item.status","item.images","item.content","item.created_at","item.updated_at","shipping.company","shipping.name"]
	 * 				 $data['item_id']  按查询 (多条用 "," 分割)
	 *
	 * @return array 单品记录 Key Value 结构数据 
	 *               	["item_id"],  // 单品ID 
	 *               	["goods_id"],  // 商品 
	*               	["_map_goods"][$goods_id[n]]["goods_id"], // goods.goods_id
	 *               	["name"],  // 名称 
	 *               	["params"],  // 参数 
	 *               	["price"],  // 单价 
	 *               	["price_low"],  // 底价 
	 *               	["price_in"],  // 进价 
	 *               	["price_val"],  // 保价 
	 *               	["promotion"],  // 优惠 
	 *               	["payment"],  // 付款方式 
	 *               	["shipping_ids"],  // 物流 
	*               	["_map_shipping"][$shipping_ids[n]]["shipping_id"], // shipping.shipping_id
	 *               	["weight"],  // 重量 
	 *               	["volume"],  // 体积 
	 *               	["sum"],  // 总数 
	 *               	["shipped_sum"],  // 货运装箱总数 
	 *               	["available_sum"],  // 可售数量 
	 *               	["status"],  // 状态 
	 *               	["images"],  // 图片 
	 *               	["content"],  // 详情 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["_map_goods"][$goods_id[n]]["created_at"], // goods.created_at
	*               	["_map_goods"][$goods_id[n]]["updated_at"], // goods.updated_at
	*               	["_map_goods"][$goods_id[n]]["instance"], // goods.instance
	*               	["_map_goods"][$goods_id[n]]["name"], // goods.name
	*               	["_map_goods"][$goods_id[n]]["slug"], // goods.slug
	*               	["_map_goods"][$goods_id[n]]["tags"], // goods.tags
	*               	["_map_goods"][$goods_id[n]]["category_ids"], // goods.category_ids
	*               	["_map_goods"][$goods_id[n]]["recommend_ids"], // goods.recommend_ids
	*               	["_map_goods"][$goods_id[n]]["summary"], // goods.summary
	*               	["_map_goods"][$goods_id[n]]["cover"], // goods.cover
	*               	["_map_goods"][$goods_id[n]]["images"], // goods.images
	*               	["_map_goods"][$goods_id[n]]["videos"], // goods.videos
	*               	["_map_goods"][$goods_id[n]]["params"], // goods.params
	*               	["_map_goods"][$goods_id[n]]["content"], // goods.content
	*               	["_map_goods"][$goods_id[n]]["content_faq"], // goods.content_faq
	*               	["_map_goods"][$goods_id[n]]["content_serv"], // goods.content_serv
	*               	["_map_goods"][$goods_id[n]]["sku_cnt"], // goods.sku_cnt
	*               	["_map_goods"][$goods_id[n]]["sku_sum"], // goods.sku_sum
	*               	["_map_goods"][$goods_id[n]]["shipped_sum"], // goods.shipped_sum
	*               	["_map_goods"][$goods_id[n]]["available_sum"], // goods.available_sum
	*               	["_map_goods"][$goods_id[n]]["lower_price"], // goods.lower_price
	*               	["_map_goods"][$goods_id[n]]["sale_way"], // goods.sale_way
	*               	["_map_goods"][$goods_id[n]]["opened_at"], // goods.opened_at
	*               	["_map_goods"][$goods_id[n]]["closed_at"], // goods.closed_at
	*               	["_map_goods"][$goods_id[n]]["pay_duration"], // goods.pay_duration
	*               	["_map_goods"][$goods_id[n]]["status"], // goods.status
	*               	["_map_goods"][$goods_id[n]]["events"], // goods.events
	*               	["_map_shipping"][$shipping_ids[n]]["created_at"], // shipping.created_at
	*               	["_map_shipping"][$shipping_ids[n]]["updated_at"], // shipping.updated_at
	*               	["_map_shipping"][$shipping_ids[n]]["company"], // shipping.company
	*               	["_map_shipping"][$shipping_ids[n]]["name"], // shipping.name
	*               	["_map_shipping"][$shipping_ids[n]]["products"], // shipping.products
	*               	["_map_shipping"][$shipping_ids[n]]["scope"], // shipping.scope
	*               	["_map_shipping"][$shipping_ids[n]]["formula"], // shipping.formula
	*               	["_map_shipping"][$shipping_ids[n]]["api"], // shipping.api
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["item.item_id","item.name","item.params","item.price","item.price_low","item.price_in","item.price_val","item.promotion","item.payment","item.shipping_ids","item.weight","item.volume","item.sum","item.shipped_sum","item.available_sum","item.status","item.images","item.content","item.created_at","item.updated_at","shipping.company","shipping.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按单品ID
		if ( !empty($data["item_id"]) ) {
			
			$keys = explode(',', $data["item_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Item;
				return $inst->getInByItemId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Item;
			return $inst->getByItemId($data["item_id"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}









}