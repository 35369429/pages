<?php
/**
 * Class Adv 
 * 广告数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-28 18:26:24
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                            

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Adv extends Api {

	/**
	 * 广告数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条广告记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["adv.adv_slug","adv.name","adv.intro","adv.link","adv.images","adv.cover","adv.position_name","adv.position_no","adv.expired","adv.status"]
	 * 				 $query['adv_id']  按查询 (多条用 "," 分割)
	 * 				 $query['adv_slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["adv.adv_slug","adv.name","adv.intro","adv.link","adv.images","adv.cover","adv.position_name","adv.position_no","adv.expired","adv.status"]
	 * 				 $data['adv_id']  按查询 (多条用 "," 分割)
	 * 				 $data['adv_slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 广告记录 Key Value 结构数据 
	 *               	["adv_id"],  // 广告ID 
	 *               	["adv_slug"],  // 广告别名 
	 *               	["categories"],  // 所属栏目 
	*               	["_map_category"][$categories[n]]["slug"], // category.slug
	 *               	["name"],  // 名称 
	 *               	["intro"],  // 文案 
	 *               	["link"],  // 链接 
	 *               	["images"],  // 广告图片(多图) 
	 *               	["cover"],  // 封面图片 
	 *               	["terms"],  // 服务协议 
	 *               	["size"],  // 尺寸 
	 *               	["position_name"],  // 位置名称 
	 *               	["position_no"],  // 位置编号 
	 *               	["expired"],  // 有效期 
	 *               	["pageview"],  // 点击量 
	 *               	["status"],  // 状态 
	 *               	["paystatus"],  // 支付状态 
	 *               	["price"],  // 单价 
	 *               	["priority"],  // 优先级 
	 *               	["user"],  // 操作者 
	 *               	["keyword"],  // 关键词 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["_map_category"][$categories[n]]["created_at"], // category.created_at
	*               	["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["_map_category"][$categories[n]]["category_id"], // category.category_id
	*               	["_map_category"][$categories[n]]["project"], // category.project
	*               	["_map_category"][$categories[n]]["page"], // category.page
	*               	["_map_category"][$categories[n]]["wechat"], // category.wechat
	*               	["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["_map_category"][$categories[n]]["name"], // category.name
	*               	["_map_category"][$categories[n]]["fullname"], // category.fullname
	*               	["_map_category"][$categories[n]]["link"], // category.link
	*               	["_map_category"][$categories[n]]["root_id"], // category.root_id
	*               	["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["_map_category"][$categories[n]]["priority"], // category.priority
	*               	["_map_category"][$categories[n]]["hidden"], // category.hidden
	*               	["_map_category"][$categories[n]]["isnav"], // category.isnav
	*               	["_map_category"][$categories[n]]["param"], // category.param
	*               	["_map_category"][$categories[n]]["status"], // category.status
	*               	["_map_category"][$categories[n]]["issubnav"], // category.issubnav
	*               	["_map_category"][$categories[n]]["highlight"], // category.highlight
	*               	["_map_category"][$categories[n]]["isfootnav"], // category.isfootnav
	*               	["_map_category"][$categories[n]]["isblank"], // category.isblank
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["adv.adv_slug","adv.name","adv.intro","adv.link","adv.images","adv.cover","adv.position_name","adv.position_no","adv.expired","adv.status"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按广告ID
		if ( !empty($data["adv_id"]) ) {
			
			$keys = explode(',', $data["adv_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Adv;
				return $inst->getInByAdvId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Adv;
			return $inst->getByAdvId($data["adv_id"], $select);
		}

		// 按广告别名
		if ( !empty($data["adv_slug"]) ) {
			
			$keys = explode(',', $data["adv_slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Adv;
				return $inst->getInByAdvSlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Adv;
			return $inst->getByAdvSlug($data["adv_slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索广告记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["adv.adv_id","adv.adv_slug","adv.name","adv.intro","adv.link","adv.images","adv.cover","adv.position_name","adv.position_no","adv.expired","adv.pageview","adv.status","adv.priority","category.slug","category.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["adv_id"] 按广告ID查询 ( AND = )
	 *			      $query["pnos"] 按广告别名查询 ( AND IN )
	 *			      $query["slug"] 按名称查询 ( AND = )
	 *			      $query["adv_ids"] 按位置名称查询 ( AND IN )
	 *			      $query["expired"] 按有效期查询 ( AND LIKE )
	 *			      $query["priority"] 按优先级查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["categories"] 按所属栏目查询 ( AND LIKE-MULTIPLE )
	 *			      $query["order_pri"]  按优先级 ASC 排序
	 *			      $query["orderby_pageview_desc"]  按点击量 DESC 排序
	 *			      $query["orderby_created_at_asc"]  按 ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=adv_id","name=adv_slug","name=name","name=intro","name=link","name=images","name=cover","name=position_name","name=position_no","name=expired","name=pageview","name=status","name=priority","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=slug&table=category&prefix=xpmsns_pages_&alias=category&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=category&type=inWhere"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["adv_id"] 按广告ID查询 ( AND = )
	 *			      $data["pnos"] 按广告别名查询 ( AND IN )
	 *			      $data["slug"] 按名称查询 ( AND = )
	 *			      $data["adv_ids"] 按位置名称查询 ( AND IN )
	 *			      $data["expired"] 按有效期查询 ( AND LIKE )
	 *			      $data["priority"] 按优先级查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["categories"] 按所属栏目查询 ( AND LIKE-MULTIPLE )
	 *			      $data["order_pri"]  按优先级 ASC 排序
	 *			      $data["orderby_pageview_desc"]  按点击量 DESC 排序
	 *			      $data["orderby_created_at_asc"]  按 ASC 排序
	 *
	 * @return array 广告记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["adv_id"],  // 广告ID 
	 *               	["adv_slug"],  // 广告别名 
	 *               	["categories"],  // 所属栏目 
	*               	["category"][$categories[n]]["slug"], // category.slug
	 *               	["name"],  // 名称 
	 *               	["intro"],  // 文案 
	 *               	["link"],  // 链接 
	 *               	["images"],  // 广告图片(多图) 
	 *               	["cover"],  // 封面图片 
	 *               	["terms"],  // 服务协议 
	 *               	["size"],  // 尺寸 
	 *               	["position_name"],  // 位置名称 
	 *               	["position_no"],  // 位置编号 
	 *               	["expired"],  // 有效期 
	 *               	["pageview"],  // 点击量 
	 *               	["status"],  // 状态 
	 *               	["paystatus"],  // 支付状态 
	 *               	["price"],  // 单价 
	 *               	["priority"],  // 优先级 
	 *               	["user"],  // 操作者 
	 *               	["keyword"],  // 关键词 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["category"][$categories[n]]["created_at"], // category.created_at
	*               	["category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["category"][$categories[n]]["category_id"], // category.category_id
	*               	["category"][$categories[n]]["project"], // category.project
	*               	["category"][$categories[n]]["page"], // category.page
	*               	["category"][$categories[n]]["wechat"], // category.wechat
	*               	["category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["category"][$categories[n]]["name"], // category.name
	*               	["category"][$categories[n]]["fullname"], // category.fullname
	*               	["category"][$categories[n]]["link"], // category.link
	*               	["category"][$categories[n]]["root_id"], // category.root_id
	*               	["category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["category"][$categories[n]]["priority"], // category.priority
	*               	["category"][$categories[n]]["hidden"], // category.hidden
	*               	["category"][$categories[n]]["isnav"], // category.isnav
	*               	["category"][$categories[n]]["param"], // category.param
	*               	["category"][$categories[n]]["status"], // category.status
	*               	["category"][$categories[n]]["issubnav"], // category.issubnav
	*               	["category"][$categories[n]]["highlight"], // category.highlight
	*               	["category"][$categories[n]]["isfootnav"], // category.isfootnav
	*               	["category"][$categories[n]]["isblank"], // category.isblank
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["adv.adv_id","adv.adv_slug","adv.name","adv.intro","adv.link","adv.images","adv.cover","adv.position_name","adv.position_no","adv.expired","adv.pageview","adv.status","adv.priority","category.slug","category.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Adv;
		return $inst->search( $data );
	}


}