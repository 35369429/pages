<?php
/**
 * Class Event 
 * 活动数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-02 22:06:00
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
	 *               $query['select']  读取字段, 默认 ["event.event_id","event.slug","event.title","event.link","event.categories","event.type","event.tags","event.summary","event.cover","event.images","event.begin","event.end","event.process_setting","event.process","event.area","event.prov","event.city","event.town","event.location","event.price","event.bonus","event.prize","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.content","event.publish_time","event.view_cnt","event.like_cnt","event.agree_cnt","event.dislike_cnt","event.comment_cnt","event.status","event.created_at","event.updated_at"]
	 * 				 $query['event_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["event.event_id","event.slug","event.title","event.link","event.categories","event.type","event.tags","event.summary","event.cover","event.images","event.begin","event.end","event.process_setting","event.process","event.area","event.prov","event.city","event.town","event.location","event.price","event.bonus","event.prize","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.content","event.publish_time","event.view_cnt","event.like_cnt","event.agree_cnt","event.dislike_cnt","event.comment_cnt","event.status","event.created_at","event.updated_at"]
	 * 				 $data['event_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 活动记录 Key Value 结构数据 
	 *               	["event_id"],  // 活动ID 
	 *               	["slug"],  // 别名 
	 *               	["title"],  // 主题 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 栏目 
	*               	["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *               	["type"],  // 类型 
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 简介 
	 *               	["cover"],  // 封面 
	 *               	["images"],  // 海报 
	 *               	["begin"],  // 开始时间 
	 *               	["end"],  // 结束时间 
	 *               	["quota"],  // 名额 
	 *               	["process_setting"],  // 流程设计 
	 *               	["process"],  // 当前进程 
	 *               	["area"],  // 国家/地区 
	 *               	["prov"],  // 省份 
	 *               	["city"],  // 城市 
	 *               	["town"],  // 区县 
	 *               	["location"],  // 地点 
	 *               	["price"],  // 费用 
	 *               	["bonus"],  // 奖金 
	 *               	["prize"],  // 奖项 
	 *               	["hosts"],  // 主办方 
	 *               	["organizers"],  // 承办方/组织者 
	 *               	["sponsors"],  // 赞助商 
	 *               	["medias"],  // 合作媒体 
	 *               	["speakers"],  // 嘉宾 
	 *               	["content"],  // 活动介绍 
	 *               	["desktop"],  // 桌面代码 
	 *               	["mobile"],  // 手机代码 
	 *               	["wxapp"],  // 小程序代码 
	 *               	["app"],  // APP代码 
	 *               	["publish_time"],  // 发布时间 
	 *               	["view_cnt"],  // 浏览量 
	 *               	["like_cnt"],  // 点赞量 
	 *               	["agree_cnt"],  // 同意量 
	 *               	["dislike_cnt"],  // 讨厌量 
	 *               	["comment_cnt"],  // 评论量 
	 *               	["status"],  // 状态 
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
		$select = empty($data['select']) ? ["event.event_id","event.slug","event.title","event.link","event.categories","event.type","event.tags","event.summary","event.cover","event.images","event.begin","event.end","event.process_setting","event.process","event.area","event.prov","event.city","event.town","event.location","event.price","event.bonus","event.prize","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.content","event.publish_time","event.view_cnt","event.like_cnt","event.agree_cnt","event.dislike_cnt","event.comment_cnt","event.status","event.created_at","event.updated_at"] : $data['select'];
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

		// 按别名
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
	 * 根据条件检索活动记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["event.event_id","event.slug","event.title","event.link","event.categories","event.type","event.tags","event.summary","event.cover","event.images","event.begin","event.end","event.process_setting","event.process","event.area","event.prov","event.city","event.town","event.location","event.price","event.bonus","event.prize","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.publish_time","event.view_cnt","event.like_cnt","event.dislike_cnt","event.comment_cnt","event.created_at","event.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["event_id"] 按活动ID查询 ( AND = )
	 *			      $query["slug"] 按别名查询 ( AND = )
	 *			      $query["begin"] 按开始时间查询 ( AND = )
	 *			      $query["end"] 按结束时间查询 ( AND = )
	 *			      $query["area"] 按国家/地区查询 ( AND = )
	 *			      $query["prov"] 按省份查询 ( AND = )
	 *			      $query["city"] 按城市查询 ( AND = )
	 *			      $query["town"] 按区县查询 ( AND = )
	 *			      $query["price"] 按费用查询 ( AND > )
	 *			      $query["price"] 按费用查询 ( AND < )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["type"] 按类型查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
	 *			      $query["orderby_publish_time_desc"]  按发布时间 DESC 排序
	 *			      $query["orderby_begin_desc"]  按开始时间 DESC 排序
	 *			      $query["orderby_end_desc"]  按结束时间 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=event_id","name=slug","name=title","name=link","name=categories","name=type","name=tags","name=summary","name=cover","name=images","name=begin","name=end","name=process_setting","name=process","name=area","name=prov","name=city","name=town","name=location","name=price","name=bonus","name=prize","name=hosts","name=organizers","name=sponsors","name=medias","name=speakers","name=publish_time","name=view_cnt","name=like_cnt","name=dislike_cnt","name=comment_cnt","name=created_at","name=updated_at"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keywords"] 按关键词查询
	 *			      $data["event_id"] 按活动ID查询 ( AND = )
	 *			      $data["slug"] 按别名查询 ( AND = )
	 *			      $data["begin"] 按开始时间查询 ( AND = )
	 *			      $data["end"] 按结束时间查询 ( AND = )
	 *			      $data["area"] 按国家/地区查询 ( AND = )
	 *			      $data["prov"] 按省份查询 ( AND = )
	 *			      $data["city"] 按城市查询 ( AND = )
	 *			      $data["town"] 按区县查询 ( AND = )
	 *			      $data["price"] 按费用查询 ( AND > )
	 *			      $data["price"] 按费用查询 ( AND < )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["type"] 按类型查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *			      $data["orderby_publish_time_desc"]  按发布时间 DESC 排序
	 *			      $data["orderby_begin_desc"]  按开始时间 DESC 排序
	 *			      $data["orderby_end_desc"]  按结束时间 DESC 排序
	 *
	 * @return array 活动记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["event_id"],  // 活动ID 
	 *               	["slug"],  // 别名 
	 *               	["title"],  // 主题 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 栏目 
	*               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["type"],  // 类型 
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 简介 
	 *               	["cover"],  // 封面 
	 *               	["images"],  // 海报 
	 *               	["begin"],  // 开始时间 
	 *               	["end"],  // 结束时间 
	 *               	["quota"],  // 名额 
	 *               	["process_setting"],  // 流程设计 
	 *               	["process"],  // 当前进程 
	 *               	["area"],  // 国家/地区 
	 *               	["prov"],  // 省份 
	 *               	["city"],  // 城市 
	 *               	["town"],  // 区县 
	 *               	["location"],  // 地点 
	 *               	["price"],  // 费用 
	 *               	["bonus"],  // 奖金 
	 *               	["prize"],  // 奖项 
	 *               	["hosts"],  // 主办方 
	 *               	["organizers"],  // 承办方/组织者 
	 *               	["sponsors"],  // 赞助商 
	 *               	["medias"],  // 合作媒体 
	 *               	["speakers"],  // 嘉宾 
	 *               	["content"],  // 活动介绍 
	 *               	["desktop"],  // 桌面代码 
	 *               	["mobile"],  // 手机代码 
	 *               	["wxapp"],  // 小程序代码 
	 *               	["app"],  // APP代码 
	 *               	["publish_time"],  // 发布时间 
	 *               	["view_cnt"],  // 浏览量 
	 *               	["like_cnt"],  // 点赞量 
	 *               	["agree_cnt"],  // 同意量 
	 *               	["dislike_cnt"],  // 讨厌量 
	 *               	["comment_cnt"],  // 评论量 
	 *               	["status"],  // 状态 
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
		$select = empty($data['select']) ? ["event.event_id","event.slug","event.title","event.link","event.categories","event.type","event.tags","event.summary","event.cover","event.images","event.begin","event.end","event.process_setting","event.process","event.area","event.prov","event.city","event.town","event.location","event.price","event.bonus","event.prize","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.publish_time","event.view_cnt","event.like_cnt","event.dislike_cnt","event.comment_cnt","event.created_at","event.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Event;
		return $inst->search( $data );
	}


}