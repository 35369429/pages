<?php
/**
 * Class Event 
 * 活动数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-06-24 15:54:18
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                                   
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Event extends Model {


	/**
	 * 公有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	private $media = null;

	/**
	 * 私有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	private $mediaPrivate = null;

	/**
	 * 活动数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('event'); // 数据表名称 xpmsns_pages_event
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 活动ID
		$this->putColumn( 'event_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 活动别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 活动主题
		$this->putColumn( 'name', $this->type("string", ["length"=>200, "index"=>true, "null"=>false]));
		// 类型
		$this->putColumn( 'categories', $this->type("text", ["json"=>true, "null"=>true]));
		// 标签
		$this->putColumn( 'tags', $this->type("text", ["null"=>true]));
		// 活动简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>200, "null"=>true]));
		// 主题图
		$this->putColumn( 'theme', $this->type("string", ["length"=>200, "null"=>true]));
		// 活动海报
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 开始时间
		$this->putColumn( 'begin', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 结束时间
		$this->putColumn( 'end', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 国家/地区
		$this->putColumn( 'area', $this->type("string", ["length"=>200, "index"=>true, "default"=>"中国", "null"=>true]));
		// 省份
		$this->putColumn( 'prov', $this->type("string", ["length"=>100, "index"=>true, "null"=>true]));
		// 城市
		$this->putColumn( 'city', $this->type("string", ["length"=>100, "index"=>true, "null"=>true]));
		// 区县
		$this->putColumn( 'town', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 地点
		$this->putColumn( 'location', $this->type("string", ["length"=>200, "null"=>true]));
		// 费用
		$this->putColumn( 'price', $this->type("integer", ["index"=>true, "null"=>true]));
		// 主办方
		$this->putColumn( 'hosts', $this->type("text", ["json"=>true, "null"=>true]));
		// 承办方/组织者
		$this->putColumn( 'organizers', $this->type("text", ["json"=>true, "null"=>true]));
		// 赞助商
		$this->putColumn( 'sponsors', $this->type("text", ["json"=>true, "null"=>true]));
		// 合作媒体
		$this->putColumn( 'medias', $this->type("text", ["json"=>true, "null"=>true]));
		// 嘉宾
		$this->putColumn( 'speakers', $this->type("text", ["json"=>true, "null"=>true]));
		// 活动介绍
		$this->putColumn( 'content', $this->type("longText", ["null"=>true]));
		// 活动状态
		$this->putColumn( 'status', $this->type("string", ["length"=>200, "index"=>true, "default"=>"open", "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {

		// 格式化: 主题图
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('theme', $rs ) ) {
			$rs["theme"] = empty($rs["theme"]) ? [] : $this->media->get( $rs["theme"] );
		}

		// 格式化: 活动海报
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
			$rs["images"] = !is_array($rs["images"]) ? [] : $rs["images"];
			foreach ($rs["images"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
		}

		// 格式化: 主办方
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('hosts', $rs ) ) {
			$rs["hosts"] = !is_array($rs["hosts"]) ? [] : $rs["hosts"];
			foreach ($rs["hosts"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
		}

		// 格式化: 承办方/组织者
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('organizers', $rs ) ) {
			$rs["organizers"] = !is_array($rs["organizers"]) ? [] : $rs["organizers"];
			foreach ($rs["organizers"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
		}

		// 格式化: 赞助商
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('sponsors', $rs ) ) {
			$rs["sponsors"] = !is_array($rs["sponsors"]) ? [] : $rs["sponsors"];
			foreach ($rs["sponsors"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
		}

		// 格式化: 合作媒体
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('medias', $rs ) ) {
			$rs["medias"] = !is_array($rs["medias"]) ? [] : $rs["medias"];
			foreach ($rs["medias"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
		}

		// 格式化: 嘉宾
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('speakers', $rs ) ) {
			$rs["speakers"] = !is_array($rs["speakers"]) ? [] : $rs["speakers"];
			foreach ($rs["speakers"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
		}


		// 格式化: 活动状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"draft" => [
		  			"value" => "draft",
		  			"name" => "草稿",
		  			"style" => "danger"
		  		],
		  		"open" => [
		  			"value" => "open",
		  			"name" => "报名中",
		  			"style" => "success"
		  		],
		  		"close" => [
		  			"value" => "close",
		  			"name" => "报名截止",
		  			"style" => "default"
		  		],
		  		"off" => [
		  			"value" => "off",
		  			"name" => "活动关闭",
		  			"style" => "default"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按活动ID查询一条活动记录
	 * @param string $event_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["event_id"],  // 活动ID 
	 *          	  $rs["slug"],  // 活动别名 
	 *          	  $rs["name"],  // 活动主题 
	 *          	  $rs["categories"],  // 类型 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["summary"],  // 活动简介 
	 *          	  $rs["theme"],  // 主题图 
	 *          	  $rs["images"],  // 活动海报 
	 *          	  $rs["begin"],  // 开始时间 
	 *          	  $rs["end"],  // 结束时间 
	 *          	  $rs["area"],  // 国家/地区 
	 *          	  $rs["prov"],  // 省份 
	 *          	  $rs["city"],  // 城市 
	 *          	  $rs["town"],  // 区县 
	 *          	  $rs["location"],  // 地点 
	 *          	  $rs["price"],  // 费用 
	 *          	  $rs["hosts"],  // 主办方 
	 *          	  $rs["organizers"],  // 承办方/组织者 
	 *          	  $rs["sponsors"],  // 赞助商 
	 *          	  $rs["medias"],  // 合作媒体 
	 *          	  $rs["speakers"],  // 嘉宾 
	 *          	  $rs["content"],  // 活动介绍 
	 *          	  $rs["status"],  // 活动状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$categories[n]]["project"], // category.project
	 *                $rs["_map_category"][$categories[n]]["page"], // category.page
	 *                $rs["_map_category"][$categories[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$categories[n]]["name"], // category.name
	 *                $rs["_map_category"][$categories[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
	 */
	public function getByEventId( $event_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
 		$qb->where('event_id', '=', $event_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $rs;
	}

		

	/**
	 * 按活动ID查询一组活动记录
	 * @param array   $event_ids 唯一主键数组 ["$event_id1","$event_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 活动记录MAP {"event_id1":{"key":"value",...}...}
	 */
	public function getInByEventId($event_ids, $select=["event.event_id","event.slug","event.name","event.categories","event.theme","event.images","event.begin","event.end","event.status"], $order=["event.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
 		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['event_id']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按活动ID保存活动记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByEventId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("event_id", $data, ["event_id", "slug"], ['_id', 'event_id']);
		return $this->getByEventId( $rs['event_id'], $select );
	}
	
	/**
	 * 按活动别名查询一条活动记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["event_id"],  // 活动ID 
	 *          	  $rs["slug"],  // 活动别名 
	 *          	  $rs["name"],  // 活动主题 
	 *          	  $rs["categories"],  // 类型 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["summary"],  // 活动简介 
	 *          	  $rs["theme"],  // 主题图 
	 *          	  $rs["images"],  // 活动海报 
	 *          	  $rs["begin"],  // 开始时间 
	 *          	  $rs["end"],  // 结束时间 
	 *          	  $rs["area"],  // 国家/地区 
	 *          	  $rs["prov"],  // 省份 
	 *          	  $rs["city"],  // 城市 
	 *          	  $rs["town"],  // 区县 
	 *          	  $rs["location"],  // 地点 
	 *          	  $rs["price"],  // 费用 
	 *          	  $rs["hosts"],  // 主办方 
	 *          	  $rs["organizers"],  // 承办方/组织者 
	 *          	  $rs["sponsors"],  // 赞助商 
	 *          	  $rs["medias"],  // 合作媒体 
	 *          	  $rs["speakers"],  // 嘉宾 
	 *          	  $rs["content"],  // 活动介绍 
	 *          	  $rs["status"],  // 活动状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$categories[n]]["project"], // category.project
	 *                $rs["_map_category"][$categories[n]]["page"], // category.page
	 *                $rs["_map_category"][$categories[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$categories[n]]["name"], // category.name
	 *                $rs["_map_category"][$categories[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
 		$qb->where('slug', '=', $slug );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $rs;
	}

	

	/**
	 * 按活动别名查询一组活动记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 活动记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["event.event_id","event.slug","event.name","event.categories","event.theme","event.images","event.begin","event.end","event.status"], $order=["event.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
 		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按活动别名保存活动记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("slug", $data, ["event_id", "slug"], ['_id', 'event_id']);
		return $this->getByEventId( $rs['event_id'], $select );
	}

	/**
	 * 根据活动ID上传主题图。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadThemeByEventId($event_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "theme"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据活动ID上传活动海报。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByEventId($event_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('event_id', $event_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动ID上传主办方。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadHostsByEventId($event_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('event_id', $event_id, ["hosts"]);
		$paths = empty($rs["hosts"]) ? [] : $rs["hosts"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "hosts"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动ID上传承办方/组织者。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadOrganizersByEventId($event_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('event_id', $event_id, ["organizers"]);
		$paths = empty($rs["organizers"]) ? [] : $rs["organizers"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "organizers"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动ID上传赞助商。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSponsorsByEventId($event_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('event_id', $event_id, ["sponsors"]);
		$paths = empty($rs["sponsors"]) ? [] : $rs["sponsors"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "sponsors"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动ID上传合作媒体。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadMediasByEventId($event_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('event_id', $event_id, ["medias"]);
		$paths = empty($rs["medias"]) ? [] : $rs["medias"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "medias"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动ID上传嘉宾。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSpeakersByEventId($event_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('event_id', $event_id, ["speakers"]);
		$paths = empty($rs["speakers"]) ? [] : $rs["speakers"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "speakers"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动别名上传主题图。
	 * @param string $slug 活动别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadThemeBySlug($slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "theme"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据活动别名上传活动海报。
	 * @param string $slug 活动别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动别名上传主办方。
	 * @param string $slug 活动别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadHostsBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["hosts"]);
		$paths = empty($rs["hosts"]) ? [] : $rs["hosts"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "hosts"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动别名上传承办方/组织者。
	 * @param string $slug 活动别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadOrganizersBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["organizers"]);
		$paths = empty($rs["organizers"]) ? [] : $rs["organizers"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "organizers"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动别名上传赞助商。
	 * @param string $slug 活动别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSponsorsBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["sponsors"]);
		$paths = empty($rs["sponsors"]) ? [] : $rs["sponsors"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "sponsors"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动别名上传合作媒体。
	 * @param string $slug 活动别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadMediasBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["medias"]);
		$paths = empty($rs["medias"]) ? [] : $rs["medias"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "medias"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据活动别名上传嘉宾。
	 * @param string $slug 活动别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSpeakersBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["speakers"]);
		$paths = empty($rs["speakers"]) ? [] : $rs["speakers"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "speakers"=>$paths] );
		}

		return $fs;
	}


	/**
	 * 添加活动记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["event_id"]) ) { 
			$data["event_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排活动记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 活动记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["event.event_id","event.slug","event.name","event.categories","event.theme","event.images","event.begin","event.end","event.status"], $order=["event.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
 

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索活动记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["event.event_id","event.slug","event.name","event.categories","event.theme","event.images","event.begin","event.end","event.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["event_id"] 按活动ID查询 ( = )
	 *			      $query["slug"] 按活动别名查询 ( = )
	 *			      $query["begin"] 按开始时间查询 ( = )
	 *			      $query["end"] 按结束时间查询 ( = )
	 *			      $query["area"] 按国家/地区查询 ( = )
	 *			      $query["prov"] 按省份查询 ( = )
	 *			      $query["city"] 按城市查询 ( = )
	 *			      $query["town"] 按区县查询 ( = )
	 *			      $query["price"] 按费用查询 ( > )
	 *			      $query["price"] 按费用查询 ( < )
	 *			      $query["status"] 按活动状态查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 活动记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["event_id"],  // 活动ID 
	 *               	["slug"],  // 活动别名 
	 *               	["name"],  // 活动主题 
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
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["event.event_id","event.slug","event.name","event.categories","event.theme","event.images","event.begin","event.end","event.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
 
		// 按关键词查找
		if ( array_key_exists("keywords", $query) && !empty($query["keywords"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("event.event_id", "like", "%{$query['keywords']}%");
				$qb->orWhere("event.slug","like", "%{$query['keywords']}%");
				$qb->orWhere("event.name","like", "%{$query['keywords']}%");
				$qb->orWhere("event.prov","like", "%{$query['keywords']}%");
				$qb->orWhere("event.city","like", "%{$query['keywords']}%");
				$qb->orWhere("event.town","like", "%{$query['keywords']}%");
			});
		}


		// 按活动ID查询 (=)  
		if ( array_key_exists("event_id", $query) &&!empty($query['event_id']) ) {
			$qb->where("event.event_id", '=', "{$query['event_id']}" );
		}
		  
		// 按活动别名查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("event.slug", '=', "{$query['slug']}" );
		}
		  
		// 按开始时间查询 (=)  
		if ( array_key_exists("begin", $query) &&!empty($query['begin']) ) {
			$qb->where("event.begin", '=', "{$query['begin']}" );
		}
		  
		// 按结束时间查询 (=)  
		if ( array_key_exists("end", $query) &&!empty($query['end']) ) {
			$qb->where("event.end", '=', "{$query['end']}" );
		}
		  
		// 按国家/地区查询 (=)  
		if ( array_key_exists("area", $query) &&!empty($query['area']) ) {
			$qb->where("event.area", '=', "{$query['area']}" );
		}
		  
		// 按省份查询 (=)  
		if ( array_key_exists("prov", $query) &&!empty($query['prov']) ) {
			$qb->where("event.prov", '=', "{$query['prov']}" );
		}
		  
		// 按城市查询 (=)  
		if ( array_key_exists("city", $query) &&!empty($query['city']) ) {
			$qb->where("event.city", '=', "{$query['city']}" );
		}
		  
		// 按区县查询 (=)  
		if ( array_key_exists("town", $query) &&!empty($query['town']) ) {
			$qb->where("event.town", '=', "{$query['town']}" );
		}
		  
		// 按费用查询 (>)  
		if ( array_key_exists("price", $query) &&!empty($query['price']) ) {
			$qb->where("event.price", '>', "{$query['price']}" );
		}
		  
		// 按费用查询 (<)  
		if ( array_key_exists("price", $query) &&!empty($query['price']) ) {
			$qb->where("event.price", '<', "{$query['price']}" );
		}
		  
		// 按活动状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("event.status", '=', "{$query['status']}" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("event.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("event.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$events = $qb->select( $select )->pgArray($perpage, ['event._id'], 'page', $page);

 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($events['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$events["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$events['_sql'] = $qb->getSql();
			$events['query'] = $query;
		}

		return $events;
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
			
			// 连接类型 (category as c )
			if ( strpos( $fd, "c." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "category_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "event.categories");
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
			"event_id",  // 活动ID
			"slug",  // 活动别名
			"name",  // 活动主题
			"categories",  // 类型
			"tags",  // 标签
			"summary",  // 活动简介
			"theme",  // 主题图
			"images",  // 活动海报
			"begin",  // 开始时间
			"end",  // 结束时间
			"area",  // 国家/地区
			"prov",  // 省份
			"city",  // 城市
			"town",  // 区县
			"location",  // 地点
			"price",  // 费用
			"hosts",  // 主办方
			"organizers",  // 承办方/组织者
			"sponsors",  // 赞助商
			"medias",  // 合作媒体
			"speakers",  // 嘉宾
			"content",  // 活动介绍
			"status",  // 活动状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>