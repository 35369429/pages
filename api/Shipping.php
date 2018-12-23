<?php
/**
 * Class Shipping 
 * 物流数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 23:10:18
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
           

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Shipping extends Api {

	/**
	 * 物流数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条物流记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["shipping.shipping_id","shipping.company","shipping.name","shipping.products","shipping.scope","shipping.created_at","shipping.updated_at"]
	 * 				 $query['shipping_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["shipping.shipping_id","shipping.company","shipping.name","shipping.products","shipping.scope","shipping.created_at","shipping.updated_at"]
	 * 				 $data['shipping_id']  按查询 (多条用 "," 分割)
	 *
	 * @return array 物流记录 Key Value 结构数据 
	 *               	["shipping_id"],  // 物流ID 
	 *               	["company"],  // 物流公司 
	 *               	["name"],  // 公司简称 
	 *               	["products"],  // 物流产品 
	 *               	["scope"],  // 配送范围 
	 *               	["formula"],  // 运费公式 
	 *               	["api"],  // 物流API 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["shipping.shipping_id","shipping.company","shipping.name","shipping.products","shipping.scope","shipping.created_at","shipping.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按物流ID
		if ( !empty($data["shipping_id"]) ) {
			
			$keys = explode(',', $data["shipping_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Shipping;
				return $inst->getInByShippingId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Shipping;
			return $inst->getByShippingId($data["shipping_id"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索物流记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["shipping.shipping_id","shipping.company","shipping.name","shipping.products","shipping.created_at","shipping.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["shipping_id"] 按物流ID查询 ( AND = )
	 *			      $query["company"] 按物流公司查询 ( AND LIKE )
	 *			      $query["name"] 按公司简称查询 ( AND LIKE )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=shipping_id","name=company","name=name","name=products","name=created_at","name=updated_at"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["shipping_id"] 按物流ID查询 ( AND = )
	 *			      $data["company"] 按物流公司查询 ( AND LIKE )
	 *			      $data["name"] 按公司简称查询 ( AND LIKE )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 物流记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["shipping_id"],  // 物流ID 
	 *               	["company"],  // 物流公司 
	 *               	["name"],  // 公司简称 
	 *               	["products"],  // 物流产品 
	 *               	["scope"],  // 配送范围 
	 *               	["formula"],  // 运费公式 
	 *               	["api"],  // 物流API 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["shipping.shipping_id","shipping.company","shipping.name","shipping.products","shipping.created_at","shipping.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Shipping;
		return $inst->search( $data );
	}


}