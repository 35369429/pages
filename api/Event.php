<?php
/**
 * Class Event 
 * 活动数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-06-24 16:02:35
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                    

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Event extends Api {

	/**
	 * 活动数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条活动记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["event.event_id","event.slug","event.name","event.link","event.categories","event.tags","event.summary","event.theme","event.images","event.begin","event.end","event.area","event.prov","event.city","event.town","event.location","event.price","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.content","event.created_at","event.updated_at","c.category_id","c.name","c.param"]
	 * 				 $query['event_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["event.event_id","event.slug","event.name","event.link","event.categories","event.tags","event.summary","event.theme","event.images","event.begin","event.end","event.area","event.prov","event.city","event.town","event.location","event.price","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.content","event.created_at","event.updated_at","c.category_id","c.name","c.param"]
	 * 				 $data['event_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 活动记录 Key Value 结构数据 
	 *               	["event_id"],  // 活动ID 
	 *               	["slug"],  // 活动别名 
	 *               	["name"],  // 活动主题 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 类型 
	*               	["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 活动简介 
	 *               	["theme"],  // 主题图 
	 *               	["images"],  // 活动海报 
	 *               	["begin"],  // 开始时间 
	 *               	["end"],  // 结束时间 
	 *               	["area"],  // 国家/地区 
	 *               	["prov"],  // 省份 
	 *               	["city"],  // 城市 
	 *               	["town"],  // 区县 
	 *               	["location"],  // 地点 
	 *               	["price"],  // 费用 
	 *               	["hosts"],  // 主办方 
	 *               	["organizers"],  // 承办方/组织者 
	 *               	["sponsors"],  // 赞助商 
	 *               	["medias"],  // 合作媒体 
	 *               	["speakers"],  // 嘉宾 
	 *               	["content"],  // 活动介绍 
	 *               	["status"],  // 活动状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["_map_category"][$categories[n]]["created_at"], // category.created_at
	*               	["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["_map_category"][$categories[n]]["slug"], // category.slug
	*               	["_map_category"][$categories[n]]["project"], // category.project
	*               	["_map_category"][$categories[n]]["page"], // category.page
	*               	["_map_category"][$categories[n]]["wechat"], // category.wechat
	*               	["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["_map_category"][$categories[n]]["name"], // category.name
	*               	["_map_category"][$categories[n]]["fullname"], // category.fullname
	*               	["_map_category"][$categories[n]]["root_id"], // category.root_id
	*               	["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["_map_category"][$categories[n]]["priority"], // category.priority
	*               	["_map_category"][$categories[n]]["hidden"], // category.hidden
	*               	["_map_category"][$categories[n]]["param"], // category.param
	*               	["_map_category"][$categories[n]]["status"], // category.status
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["event.event_id","event.slug","event.name","event.link","event.categories","event.tags","event.summary","event.theme","event.images","event.begin","event.end","event.area","event.prov","event.city","event.town","event.location","event.price","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.content","event.created_at","event.updated_at","c.category_id","c.name","c.param"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按活动ID
		if ( !empty($data["event_id"]) ) {
			
			$keys = explode(',', $data["event_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Event;
				return $inst->getInByEventId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Event;
			return $inst->getByEventId($data["event_id"], $select);
		}

		// 按活动别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Event;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Event;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条活动记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['event_id'] 活动ID
	 *               $data['slug'] 活动别名
	 *               $data['name'] 活动主题
	 *               $data['link'] 外部链接
	 *               $data['categories'] 类型
	 *               $data['tags'] 标签
	 *               $data['summary'] 活动简介
	 *               $data['theme'] 主题图
	 *               $data['images'] 活动海报
	 *               $data['begin'] 开始时间
	 *               $data['end'] 结束时间
	 *               $data['area'] 国家/地区
	 *               $data['prov'] 省份
	 *               $data['city'] 城市
	 *               $data['town'] 区县
	 *               $data['location'] 地点
	 *               $data['price'] 费用
	 *               $data['hosts'] 主办方
	 *               $data['organizers'] 承办方/组织者
	 *               $data['sponsors'] 赞助商
	 *               $data['medias'] 合作媒体
	 *               $data['speakers'] 嘉宾
	 *               $data['content'] 活动介绍
	 *               $data['status'] 活动状态
	 *
	 * @return array 新增的活动记录  @see get()
	 */
	protected function create( $query, $data ) {

		// 签名校验，一般用于后台程序调用
		$this->auth($query);

		if (empty($data['event_id'])) {
			throw new Excp("缺少必填字段活动ID (event_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['slug'])) {
			throw new Excp("缺少必填字段活动别名 (slug)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['name'])) {
			throw new Excp("缺少必填字段活动主题 (name)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\Pages\Model\Event;
		$rs = $inst->create( $data );
		return $inst->getByEventId($rs["event_id"]);
	}


	/**
	 * 更新一条活动记录
	 * @param  array $query GET 参数
	 * 				 $query['name=event_id']  按更新
	 * 				 $query['name=slug']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['event_id'] 活动ID
	 *               $data['slug'] 活动别名
	 *               $data['name'] 活动主题
	 *               $data['link'] 外部链接
	 *               $data['categories'] 类型
	 *               $data['tags'] 标签
	 *               $data['summary'] 活动简介
	 *               $data['theme'] 主题图
	 *               $data['images'] 活动海报
	 *               $data['begin'] 开始时间
	 *               $data['end'] 结束时间
	 *               $data['area'] 国家/地区
	 *               $data['prov'] 省份
	 *               $data['city'] 城市
	 *               $data['town'] 区县
	 *               $data['location'] 地点
	 *               $data['price'] 费用
	 *               $data['hosts'] 主办方
	 *               $data['organizers'] 承办方/组织者
	 *               $data['sponsors'] 赞助商
	 *               $data['medias'] 合作媒体
	 *               $data['speakers'] 嘉宾
	 *               $data['content'] 活动介绍
	 *               $data['status'] 活动状态
	 *
	 * @return array 更新的活动记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		// 签名校验，一般用于后台程序调用
		$this->auth($query);

		// 按活动ID
		if ( !empty($query["event_id"]) ) {
			$data = array_merge( $data, ["event_id"=>$query["event_id"]] );
			$inst = new \Xpmsns\Pages\Model\Event;
			$rs = $inst->updateBy("event_id",$data);
			return $inst->getByEventId($rs["event_id"]);
		}

		// 按活动别名
		if ( !empty($query["slug"]) ) {
			$data = array_merge( $data, ["slug"=>$query["slug"]] );
			$inst = new \Xpmsns\Pages\Model\Event;
			$rs = $inst->updateBy("slug",$data);
			return $inst->getBySlug($rs["slug"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条活动记录
	 * @param  array $query GET 参数
	 * 				 $query['event_id']  按活动ID 删除
	 * 				 $query['slug']  按活动别名 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		// 签名校验，一般用于后台程序调用
		$this->auth($query);

		// 按活动ID
		if ( !empty($query["event_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Event;
			$resp = $inst->remove($query['event_id'], "event_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		// 按活动别名
		if ( !empty($query["slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Event;
			$resp = $inst->remove($query['slug'], "slug");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索活动记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["event.event_id","event.slug","event.name","event.link","event.categories","event.tags","event.summary","event.theme","event.images","event.begin","event.end","event.area","event.prov","event.city","event.town","event.location","event.price","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.created_at","event.updated_at","c.category_id","c.slug","c.name","c.param"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["event_id"] 按活动ID查询 ( AND = )
	 *			      $query["slug"] 按活动别名查询 ( AND = )
	 *			      $query["begin"] 按开始时间查询 ( AND = )
	 *			      $query["end"] 按结束时间查询 ( AND = )
	 *			      $query["area"] 按国家/地区查询 ( AND = )
	 *			      $query["prov"] 按省份查询 ( AND = )
	 *			      $query["city"] 按城市查询 ( AND = )
	 *			      $query["town"] 按区县查询 ( AND = )
	 *			      $query["price"] 按费用查询 ( AND > )
	 *			      $query["price"] 按费用查询 ( AND < )
	 *			      $query["status"] 按活动状态查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=event_id","name=slug","name=name","name=link","name=categories","name=tags","name=summary","name=theme","name=images","name=begin","name=end","name=area","name=prov","name=city","name=town","name=location","name=price","name=hosts","name=organizers","name=sponsors","name=medias","name=speakers","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=slug&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=param&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keywords"] 按关键词查询
	 *			      $data["event_id"] 按活动ID查询 ( AND = )
	 *			      $data["slug"] 按活动别名查询 ( AND = )
	 *			      $data["begin"] 按开始时间查询 ( AND = )
	 *			      $data["end"] 按结束时间查询 ( AND = )
	 *			      $data["area"] 按国家/地区查询 ( AND = )
	 *			      $data["prov"] 按省份查询 ( AND = )
	 *			      $data["city"] 按城市查询 ( AND = )
	 *			      $data["town"] 按区县查询 ( AND = )
	 *			      $data["price"] 按费用查询 ( AND > )
	 *			      $data["price"] 按费用查询 ( AND < )
	 *			      $data["status"] 按活动状态查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 活动记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["event_id"],  // 活动ID 
	 *               	["slug"],  // 活动别名 
	 *               	["name"],  // 活动主题 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 类型 
	*               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 活动简介 
	 *               	["theme"],  // 主题图 
	 *               	["images"],  // 活动海报 
	 *               	["begin"],  // 开始时间 
	 *               	["end"],  // 结束时间 
	 *               	["area"],  // 国家/地区 
	 *               	["prov"],  // 省份 
	 *               	["city"],  // 城市 
	 *               	["town"],  // 区县 
	 *               	["location"],  // 地点 
	 *               	["price"],  // 费用 
	 *               	["hosts"],  // 主办方 
	 *               	["organizers"],  // 承办方/组织者 
	 *               	["sponsors"],  // 赞助商 
	 *               	["medias"],  // 合作媒体 
	 *               	["speakers"],  // 嘉宾 
	 *               	["content"],  // 活动介绍 
	 *               	["status"],  // 活动状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["category"][$categories[n]]["created_at"], // category.created_at
	*               	["category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["category"][$categories[n]]["slug"], // category.slug
	*               	["category"][$categories[n]]["project"], // category.project
	*               	["category"][$categories[n]]["page"], // category.page
	*               	["category"][$categories[n]]["wechat"], // category.wechat
	*               	["category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["category"][$categories[n]]["name"], // category.name
	*               	["category"][$categories[n]]["fullname"], // category.fullname
	*               	["category"][$categories[n]]["root_id"], // category.root_id
	*               	["category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["category"][$categories[n]]["priority"], // category.priority
	*               	["category"][$categories[n]]["hidden"], // category.hidden
	*               	["category"][$categories[n]]["param"], // category.param
	*               	["category"][$categories[n]]["status"], // category.status
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["event.event_id","event.slug","event.name","event.link","event.categories","event.tags","event.summary","event.theme","event.images","event.begin","event.end","event.area","event.prov","event.city","event.town","event.location","event.price","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.created_at","event.updated_at","c.category_id","c.slug","c.name","c.param"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Event;
		return $inst->search( $data );
	}


}