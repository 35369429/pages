<?php
/**
 * Class Album 
 * 图集数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-08-27 18:27:39
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                          
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Album extends Model {


	/**
	 * 公有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	protected $media = null;

	/**
	 * 私有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	protected $mediaPrivate = null;

	/**
	 * 图集数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('album'); // 数据表名称 xpmsns_pages_album
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

		// 图集ID
		$this->putColumn( 'album_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 图集别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 图集主题
		$this->putColumn( 'title', $this->type("string", ["length"=>200, "index"=>true, "null"=>false]));
		// 图集作者
		$this->putColumn( 'author', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 图集来源
		$this->putColumn( 'origin', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 来源地址
		$this->putColumn( 'origin_url', $this->type("string", ["length"=>200, "null"=>true]));
		// 外部链接
		$this->putColumn( 'link', $this->type("string", ["length"=>200, "null"=>true]));
		// 类型
		$this->putColumn( 'categories', $this->type("string", ["length"=>400, "index"=>true, "json"=>true, "null"=>true]));
		// 系列
		$this->putColumn( 'series', $this->type("string", ["length"=>400, "index"=>true, "json"=>true, "null"=>true]));
		// 标签
		$this->putColumn( 'tags', $this->type("string", ["length"=>400, "index"=>true, "null"=>true]));
		// 图集简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>200, "null"=>true]));
		// 图片列表
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 封面
		$this->putColumn( 'cover', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 发布时间
		$this->putColumn( 'publish_time', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 浏览量
		$this->putColumn( 'view_cnt', $this->type("bigInteger", ["length"=>20, "index"=>true, "null"=>true]));
		// 赞赏量
		$this->putColumn( 'like_cnt', $this->type("bigInteger", ["length"=>20, "index"=>true, "null"=>true]));
		// 讨厌量
		$this->putColumn( 'dislike_cnt', $this->type("bigInteger", ["length"=>20, "index"=>true, "null"=>true]));
		// 评论数据量
		$this->putColumn( 'comment_cnt', $this->type("bigInteger", ["length"=>20, "index"=>true, "null"=>true]));
		// 图集状态
		$this->putColumn( 'status', $this->type("string", ["length"=>200, "index"=>true, "default"=>"draft", "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {

		// 格式化: 图片列表
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

		// 格式化: 封面
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('cover', $rs ) ) {
			$rs["cover"] = empty($rs["cover"]) ? [] : $this->media->get( $rs["cover"] );
		}


		// 格式化: 图集状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"draft" => [
		  			"value" => "draft",
		  			"name" => "草稿",
		  			"style" => "danger"
		  		],
		  		"published" => [
		  			"value" => "published",
		  			"name" => "已发布",
		  			"style" => "success"
		  		],
		  		"closed" => [
		  			"value" => "closed",
		  			"name" => "关闭",
		  			"style" => "danger"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按图集ID查询一条图集记录
	 * @param string $album_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["album_id"],  // 图集ID 
	 *          	  $rs["slug"],  // 图集别名 
	 *          	  $rs["title"],  // 图集主题 
	 *          	  $rs["author"],  // 图集作者 
	 *          	  $rs["origin"],  // 图集来源 
	 *          	  $rs["origin_url"],  // 来源地址 
	 *          	  $rs["link"],  // 外部链接 
	 *          	  $rs["categories"],  // 类型 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["series"],  // 系列 
	 *                $rs["_map_series"][$series[n]]["series_id"], // series.series_id
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["summary"],  // 图集简介 
	 *          	  $rs["images"],  // 图片列表 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["publish_time"],  // 发布时间 
	 *          	  $rs["view_cnt"],  // 浏览量 
	 *          	  $rs["like_cnt"],  // 赞赏量 
	 *          	  $rs["dislike_cnt"],  // 讨厌量 
	 *          	  $rs["comment_cnt"],  // 评论数据量 
	 *          	  $rs["status"],  // 图集状态 
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
	 *                $rs["_map_category"][$categories[n]]["link"], // category.link
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
	 *                $rs["_map_category"][$categories[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$categories[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$categories[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$categories[n]]["isblank"], // category.isblank
	 *                $rs["_map_series"][$series[n]]["created_at"], // series.created_at
	 *                $rs["_map_series"][$series[n]]["updated_at"], // series.updated_at
	 *                $rs["_map_series"][$series[n]]["name"], // series.name
	 *                $rs["_map_series"][$series[n]]["slug"], // series.slug
	 *                $rs["_map_series"][$series[n]]["category_id"], // series.category_id
	 *                $rs["_map_series"][$series[n]]["summary"], // series.summary
	 *                $rs["_map_series"][$series[n]]["orderby"], // series.orderby
	 *                $rs["_map_series"][$series[n]]["param"], // series.param
	 *                $rs["_map_series"][$series[n]]["status"], // series.status
	 */
	public function getByAlbumId( $album_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_album as album", "{none}")->query();
  		$qb->where('album_id', '=', $album_id );
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
 		$series_ids = []; // 读取 inWhere series 数据
		$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$rs["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}

		return $rs;
	}

		

	/**
	 * 按图集ID查询一组图集记录
	 * @param array   $album_ids 唯一主键数组 ["$album_id1","$album_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 图集记录MAP {"album_id1":{"key":"value",...}...}
	 */
	public function getInByAlbumId($album_ids, $select=["album.album_id","album.slug","album.title","c.name","s.name","album.author","album.cover","album.status","album.publish_time","album.created_at","album.updated_at"], $order=["album.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_album as album", "{none}")->query();
  		$qb->whereIn('album.album_id', $album_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$series_ids = []; // 读取 inWhere series 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['album_id']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$map["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按图集ID保存图集记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByAlbumId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("album_id", $data, ["album_id", "slug"], ['_id', 'album_id']);
		return $this->getByAlbumId( $rs['album_id'], $select );
	}
	
	/**
	 * 按图集别名查询一条图集记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["album_id"],  // 图集ID 
	 *          	  $rs["slug"],  // 图集别名 
	 *          	  $rs["title"],  // 图集主题 
	 *          	  $rs["author"],  // 图集作者 
	 *          	  $rs["origin"],  // 图集来源 
	 *          	  $rs["origin_url"],  // 来源地址 
	 *          	  $rs["link"],  // 外部链接 
	 *          	  $rs["categories"],  // 类型 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["series"],  // 系列 
	 *                $rs["_map_series"][$series[n]]["series_id"], // series.series_id
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["summary"],  // 图集简介 
	 *          	  $rs["images"],  // 图片列表 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["publish_time"],  // 发布时间 
	 *          	  $rs["view_cnt"],  // 浏览量 
	 *          	  $rs["like_cnt"],  // 赞赏量 
	 *          	  $rs["dislike_cnt"],  // 讨厌量 
	 *          	  $rs["comment_cnt"],  // 评论数据量 
	 *          	  $rs["status"],  // 图集状态 
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
	 *                $rs["_map_category"][$categories[n]]["link"], // category.link
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
	 *                $rs["_map_category"][$categories[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$categories[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$categories[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$categories[n]]["isblank"], // category.isblank
	 *                $rs["_map_series"][$series[n]]["created_at"], // series.created_at
	 *                $rs["_map_series"][$series[n]]["updated_at"], // series.updated_at
	 *                $rs["_map_series"][$series[n]]["name"], // series.name
	 *                $rs["_map_series"][$series[n]]["slug"], // series.slug
	 *                $rs["_map_series"][$series[n]]["category_id"], // series.category_id
	 *                $rs["_map_series"][$series[n]]["summary"], // series.summary
	 *                $rs["_map_series"][$series[n]]["orderby"], // series.orderby
	 *                $rs["_map_series"][$series[n]]["param"], // series.param
	 *                $rs["_map_series"][$series[n]]["status"], // series.status
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_album as album", "{none}")->query();
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
 		$series_ids = []; // 读取 inWhere series 数据
		$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$rs["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}

		return $rs;
	}

	

	/**
	 * 按图集别名查询一组图集记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 图集记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["album.album_id","album.slug","album.title","c.name","s.name","album.author","album.cover","album.status","album.publish_time","album.created_at","album.updated_at"], $order=["album.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_album as album", "{none}")->query();
  		$qb->whereIn('album.slug', $slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$series_ids = []; // 读取 inWhere series 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$map["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按图集别名保存图集记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("slug", $data, ["album_id", "slug"], ['_id', 'album_id']);
		return $this->getByAlbumId( $rs['album_id'], $select );
	}

	/**
	 * 根据图集ID上传图片列表。
	 * @param string $album_id 图集ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByAlbumId($album_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('album_id', $album_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('album_id', ["album_id"=>$album_id, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据图集ID上传封面。
	 * @param string $album_id 图集ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverByAlbumId($album_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('album_id', ["album_id"=>$album_id, "cover"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据图集别名上传图片列表。
	 * @param string $slug 图集别名
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
	 * 根据图集别名上传封面。
	 * @param string $slug 图集别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverBySlug($slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "cover"=>$fs['path']]);
		}
		return $fs;
	}


	/**
	 * 添加图集记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["album_id"]) ) { 
			$data["album_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排图集记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 图集记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["album.album_id","album.slug","album.title","c.name","s.name","album.author","album.cover","album.status","album.publish_time","album.created_at","album.updated_at"], $order=["album.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_album as album", "{none}")->query();
  

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$category_ids = []; // 读取 inWhere category 数据
 		$series_ids = []; // 读取 inWhere series 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$data["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索图集记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["album.album_id","album.slug","album.title","c.name","s.name","album.author","album.cover","album.status","album.publish_time","album.created_at","album.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["album_id"] 按图集ID查询 ( = )
	 *			      $query["slug"] 按图集别名查询 ( = )
	 *			      $query["status"] 按图集状态查询 ( = )
	 *			      $query["title"] 按图集主题查询 ( LIKE )
	 *			      $query["series"] 按系列查询 ( LIKE )
	 *			      $query["categories"] 按类型查询 ( LIKE )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 图集记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["album_id"],  // 图集ID 
	 *               	["slug"],  // 图集别名 
	 *               	["title"],  // 图集主题 
	 *               	["author"],  // 图集作者 
	 *               	["origin"],  // 图集来源 
	 *               	["origin_url"],  // 来源地址 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 类型 
	 *               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["series"],  // 系列 
	 *               	["series"][$series[n]]["series_id"], // series.series_id
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 图集简介 
	 *               	["images"],  // 图片列表 
	 *               	["cover"],  // 封面 
	 *               	["publish_time"],  // 发布时间 
	 *               	["view_cnt"],  // 浏览量 
	 *               	["like_cnt"],  // 赞赏量 
	 *               	["dislike_cnt"],  // 讨厌量 
	 *               	["comment_cnt"],  // 评论数据量 
	 *               	["status"],  // 图集状态 
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
	 *               	["series"][$series[n]]["created_at"], // series.created_at
	 *               	["series"][$series[n]]["updated_at"], // series.updated_at
	 *               	["series"][$series[n]]["name"], // series.name
	 *               	["series"][$series[n]]["slug"], // series.slug
	 *               	["series"][$series[n]]["category_id"], // series.category_id
	 *               	["series"][$series[n]]["summary"], // series.summary
	 *               	["series"][$series[n]]["orderby"], // series.orderby
	 *               	["series"][$series[n]]["param"], // series.param
	 *               	["series"][$series[n]]["status"], // series.status
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["album.album_id","album.slug","album.title","c.name","s.name","album.author","album.cover","album.status","album.publish_time","album.created_at","album.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "album.album_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_album as album", "{none}")->query();
  
		// 按关键词查找


		// 按图集ID查询 (=)  
		if ( array_key_exists("album_id", $query) &&!empty($query['album_id']) ) {
			$qb->where("album.album_id", '=', "{$query['album_id']}" );
		}
		  
		// 按图集别名查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("album.slug", '=', "{$query['slug']}" );
		}
		  
		// 按图集状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("album.status", '=', "{$query['status']}" );
		}
		  
		// 按图集主题查询 (LIKE)  
		if ( array_key_exists("title", $query) &&!empty($query['title']) ) {
			$qb->where("album.title", 'like', "%{$query['title']}%" );
		}
		  
		// 按系列查询 (LIKE)  
		if ( array_key_exists("series", $query) &&!empty($query['series']) ) {
			$qb->where("album.series", 'like', "%{$query['series']}%" );
		}
		  
		// 按类型查询 (LIKE)  
		if ( array_key_exists("categories", $query) &&!empty($query['categories']) ) {
			$qb->where("album.categories", 'like', "%{$query['categories']}%" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("album.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("album.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$albums = $qb->select( $select )->pgArray($perpage, ['album._id'], 'page', $page);

 		$category_ids = []; // 读取 inWhere category 数据
 		$series_ids = []; // 读取 inWhere series 数据
		foreach ($albums['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$albums["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$albums["series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$albums['_sql'] = $qb->getSql();
			$albums['query'] = $query;
		}

		return $albums;
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
				$select[$idx] = "album." .$select[$idx];
				continue;
			}
			
			// 连接类型 (category as c )
			if ( strpos( $fd, "c." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "category_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "album.categories");
				}
			}
			
			// 连接系列 (series as s )
			if ( strpos( $fd, "s." ) === 0 || strpos("series.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["series"][] = trim($arr[1]);
				$inwhereSelect["series"][] = "series_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "album.series");
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
			"album_id",  // 图集ID
			"slug",  // 图集别名
			"title",  // 图集主题
			"author",  // 图集作者
			"origin",  // 图集来源
			"origin_url",  // 来源地址
			"link",  // 外部链接
			"categories",  // 类型
			"series",  // 系列
			"tags",  // 标签
			"summary",  // 图集简介
			"images",  // 图片列表
			"cover",  // 封面
			"publish_time",  // 发布时间
			"view_cnt",  // 浏览量
			"like_cnt",  // 赞赏量
			"dislike_cnt",  // 讨厌量
			"comment_cnt",  // 评论数据量
			"status",  // 图集状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>