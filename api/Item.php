<?php
/**
 * Class Item 
 * 单品数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-22 19:42:25
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
	 *               $query['select']  读取字段, 默认 ["item.item_id","item.name","item.params","item.price","item.price_low","item.price_in","item.price_val","item.promotion","item.payment","item.delivery","item.weight","item.volume","item.sum","item.shipped_sum","item.available_sum","item.status","item.images","item.content","item.created_at","item.updated_at"]
	 * 				 $query['item_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["item.item_id","item.name","item.params","item.price","item.price_low","item.price_in","item.price_val","item.promotion","item.payment","item.delivery","item.weight","item.volume","item.sum","item.shipped_sum","item.available_sum","item.status","item.images","item.content","item.created_at","item.updated_at"]
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
	 *               	["delivery"],  // 配送方式 
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
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["item.item_id","item.name","item.params","item.price","item.price_low","item.price_in","item.price_val","item.promotion","item.payment","item.delivery","item.weight","item.volume","item.sum","item.shipped_sum","item.available_sum","item.status","item.images","item.content","item.created_at","item.updated_at"] : $data['select'];
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

	/**
	 * 添加一条单品记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['item_id'] 单品ID
	 *               $data['goods_id'] 商品
	 *               $data['name'] 名称
	 *               $data['params'] 参数
	 *               $data['price'] 单价
	 *               $data['price_low'] 底价
	 *               $data['price_in'] 进价
	 *               $data['price_val'] 保价
	 *               $data['promotion'] 优惠
	 *               $data['payment'] 付款方式
	 *               $data['delivery'] 配送方式
	 *               $data['weight'] 重量
	 *               $data['volume'] 体积
	 *               $data['sum'] 总数
	 *               $data['shipped_sum'] 货运装箱总数
	 *               $data['available_sum'] 可售数量
	 *               $data['status'] 状态
	 *               $data['images'] 图片
	 *               $data['content'] 详情
	 *
	 * @return array 新增的单品记录  @see get()
	 */
	protected function create( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}
		// 校验图形验证码
		$this->authVcode();

		if (empty($data['item_id'])) {
			throw new Excp("缺少必填字段单品ID (item_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['name'])) {
			throw new Excp("缺少必填字段名称 (name)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\Pages\Model\Item;
		$rs = $inst->create( $data );
		return $inst->getByItemId($rs["item_id"]);
	}


	/**
	 * 更新一条单品记录
	 * @param  array $query GET 参数
	 * 				 $query['name=item_id']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['item_id'] 单品ID
	 *               $data['goods_id'] 商品
	 *               $data['name'] 名称
	 *               $data['params'] 参数
	 *               $data['price'] 单价
	 *               $data['price_low'] 底价
	 *               $data['price_in'] 进价
	 *               $data['price_val'] 保价
	 *               $data['promotion'] 优惠
	 *               $data['payment'] 付款方式
	 *               $data['delivery'] 配送方式
	 *               $data['weight'] 重量
	 *               $data['volume'] 体积
	 *               $data['sum'] 总数
	 *               $data['shipped_sum'] 货运装箱总数
	 *               $data['available_sum'] 可售数量
	 *               $data['status'] 状态
	 *               $data['images'] 图片
	 *               $data['content'] 详情
	 *
	 * @return array 更新的单品记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		// 签名校验，一般用于后台程序调用
		$this->auth($query);

		// 按单品ID
		if ( !empty($query["item_id"]) ) {
			$data = array_merge( $data, ["item_id"=>$query["item_id"]] );
			$inst = new \Xpmsns\Pages\Model\Item;
			$rs = $inst->updateBy("item_id",$data);
			return $inst->getByItemId($rs["item_id"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条单品记录
	 * @param  array $query GET 参数
	 * 				 $query['item_id']  按单品ID 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}
		// 校验图形验证码
		$this->authVcode();

		// 按单品ID
		if ( !empty($query["item_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Item;
			$resp = $inst->remove($query['item_id'], "item_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索单品记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["item.item_id","item.name","item.params"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["item_id"] 按单品ID查询 ( AND = )
	 *			      $query["name"] 按名称查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["price"] 按单价查询 ( AND > )
	 *			      $query["price"] 按单价查询 ( AND < )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=item_id","name=name","name=params"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keywords"] 按关键词查询
	 *			      $data["item_id"] 按单品ID查询 ( AND = )
	 *			      $data["name"] 按名称查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["price"] 按单价查询 ( AND > )
	 *			      $data["price"] 按单价查询 ( AND < )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 单品记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["item_id"],  // 单品ID 
	 *               	["goods_id"],  // 商品 
	*               	["goods"][$goods_id[n]]["goods_id"], // goods.goods_id
	 *               	["name"],  // 名称 
	 *               	["params"],  // 参数 
	 *               	["price"],  // 单价 
	 *               	["price_low"],  // 底价 
	 *               	["price_in"],  // 进价 
	 *               	["price_val"],  // 保价 
	 *               	["promotion"],  // 优惠 
	 *               	["payment"],  // 付款方式 
	 *               	["delivery"],  // 配送方式 
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
	*               	["goods"][$goods_id[n]]["created_at"], // goods.created_at
	*               	["goods"][$goods_id[n]]["updated_at"], // goods.updated_at
	*               	["goods"][$goods_id[n]]["instance"], // goods.instance
	*               	["goods"][$goods_id[n]]["name"], // goods.name
	*               	["goods"][$goods_id[n]]["slug"], // goods.slug
	*               	["goods"][$goods_id[n]]["tags"], // goods.tags
	*               	["goods"][$goods_id[n]]["category_ids"], // goods.category_ids
	*               	["goods"][$goods_id[n]]["recommend_ids"], // goods.recommend_ids
	*               	["goods"][$goods_id[n]]["summary"], // goods.summary
	*               	["goods"][$goods_id[n]]["cover"], // goods.cover
	*               	["goods"][$goods_id[n]]["images"], // goods.images
	*               	["goods"][$goods_id[n]]["videos"], // goods.videos
	*               	["goods"][$goods_id[n]]["params"], // goods.params
	*               	["goods"][$goods_id[n]]["content"], // goods.content
	*               	["goods"][$goods_id[n]]["content_faq"], // goods.content_faq
	*               	["goods"][$goods_id[n]]["content_serv"], // goods.content_serv
	*               	["goods"][$goods_id[n]]["sku_cnt"], // goods.sku_cnt
	*               	["goods"][$goods_id[n]]["sku_sum"], // goods.sku_sum
	*               	["goods"][$goods_id[n]]["shipped_sum"], // goods.shipped_sum
	*               	["goods"][$goods_id[n]]["available_sum"], // goods.available_sum
	*               	["goods"][$goods_id[n]]["lower_price"], // goods.lower_price
	*               	["goods"][$goods_id[n]]["sale_way"], // goods.sale_way
	*               	["goods"][$goods_id[n]]["opened_at"], // goods.opened_at
	*               	["goods"][$goods_id[n]]["closed_at"], // goods.closed_at
	*               	["goods"][$goods_id[n]]["pay_duration"], // goods.pay_duration
	*               	["goods"][$goods_id[n]]["status"], // goods.status
	*               	["goods"][$goods_id[n]]["events"], // goods.events
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["item.item_id","item.name","item.params"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Item;
		return $inst->search( $data );
	}


}