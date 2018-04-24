<?php
/**
 * Class Recommend 
 * 推荐数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-04-24 23:14:26
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Recommend extends Model {


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
	 * 推荐数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('recommend'); // 数据表名称 xpmsns_pages_recommend
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

		// 推荐ID
		$this->putColumn( 'recommend_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 主题
		$this->putColumn( 'title', $this->type("string", ["length"=>100, "index"=>true, "null"=>true]));
		// 方式
		$this->putColumn( 'type', $this->type("string", ["length"=>20, "index"=>true, "default"=>"auto", "null"=>true]));
		// 呈现图片
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 关键词
		$this->putColumn( 'keywords', $this->type("text", ["null"=>true]));
		// 相关栏目
		$this->putColumn( 'categories', $this->type("text", ["json"=>true, "null"=>true]));
		// 相关文章
		$this->putColumn( 'articles', $this->type("text", ["json"=>true, "null"=>true]));
		// 排序方式
		$this->putColumn( 'orderby', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {

		// 格式化: 呈现图片
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


		// 格式化: 方式
		// 返回值: "_type_types" 所有状态表述, "_type_name" 状态名称,  "_type" 当前状态表述, "type" 当前状态数值
		if ( array_key_exists('type', $rs ) && !empty($rs['type']) ) {
			$rs["_type_types"] = [
		  		"auto" => [
		  			"value" => "auto",
		  			"name" => "智能",
		  			"style" => "primary"
		  		],
		  		"static" => [
		  			"value" => "static",
		  			"name" => "静态",
		  			"style" => "info"
		  		],
			];
			$rs["_type_name"] = "type";
			$rs["_type"] = $rs["_type_types"][$rs["type"]];
		}

		// 格式化: 排序方式
		// 返回值: "_orderby_types" 所有状态表述, "_orderby_name" 状态名称,  "_orderby" 当前状态表述, "orderby" 当前状态数值
		if ( array_key_exists('orderby', $rs ) && !empty($rs['orderby']) ) {
			$rs["_orderby_types"] = [
		  		"publish_time" => [
		  			"value" => "publish_time",
		  			"name" => "最新发表",
		  			"style" => "info"
		  		],
		  		"page_view" => [
		  			"value" => "page_view",
		  			"name" => "最多浏览",
		  			"style" => "info"
		  		],
		  		"favorite" => [
		  			"value" => "favorite",
		  			"name" => "最多点赞",
		  			"style" => "info"
		  		],
		  		"comment" => [
		  			"value" => "comment",
		  			"name" => "最多评论",
		  			"style" => "info"
		  		],
			];
			$rs["_orderby_name"] = "orderby";
			$rs["_orderby"] = $rs["_orderby_types"][$rs["orderby"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按推荐ID查询一条推荐记录
	 * @param string $recommend_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["recommend_id"],  // 推荐ID 
	 *          	  $rs["title"],  // 主题 
	 *          	  $rs["type"],  // 方式 
	 *          	  $rs["images"],  // 呈现图片 
	 *          	  $rs["keywords"],  // 关键词 
	 *          	  $rs["categories"],  // 相关栏目 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["articles"],  // 相关文章 
	 *                $rs["_map_article"][$articles[n]]["article_id"], // article.article_id
	 *          	  $rs["orderby"],  // 排序方式 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_article"][$articles[n]]["created_at"], // article.created_at
	 *                $rs["_map_article"][$articles[n]]["updated_at"], // article.updated_at
	 *                $rs["_map_article"][$articles[n]]["outer_id"], // article.outer_id
	 *                $rs["_map_article"][$articles[n]]["cover"], // article.cover
	 *                $rs["_map_article"][$articles[n]]["thumbs"], // article.thumbs
	 *                $rs["_map_article"][$articles[n]]["images"], // article.images
	 *                $rs["_map_article"][$articles[n]]["videos"], // article.videos
	 *                $rs["_map_article"][$articles[n]]["audios"], // article.audios
	 *                $rs["_map_article"][$articles[n]]["title"], // article.title
	 *                $rs["_map_article"][$articles[n]]["author"], // article.author
	 *                $rs["_map_article"][$articles[n]]["origin"], // article.origin
	 *                $rs["_map_article"][$articles[n]]["origin_url"], // article.origin_url
	 *                $rs["_map_article"][$articles[n]]["summary"], // article.summary
	 *                $rs["_map_article"][$articles[n]]["seo_title"], // article.seo_title
	 *                $rs["_map_article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	 *                $rs["_map_article"][$articles[n]]["seo_summary"], // article.seo_summary
	 *                $rs["_map_article"][$articles[n]]["publish_time"], // article.publish_time
	 *                $rs["_map_article"][$articles[n]]["update_time"], // article.update_time
	 *                $rs["_map_article"][$articles[n]]["create_time"], // article.create_time
	 *                $rs["_map_article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	 *                $rs["_map_article"][$articles[n]]["sync"], // article.sync
	 *                $rs["_map_article"][$articles[n]]["content"], // article.content
	 *                $rs["_map_article"][$articles[n]]["ap_content"], // article.ap_content
	 *                $rs["_map_article"][$articles[n]]["delta"], // article.delta
	 *                $rs["_map_article"][$articles[n]]["param"], // article.param
	 *                $rs["_map_article"][$articles[n]]["stick"], // article.stick
	 *                $rs["_map_article"][$articles[n]]["preview"], // article.preview
	 *                $rs["_map_article"][$articles[n]]["links"], // article.links
	 *                $rs["_map_article"][$articles[n]]["user"], // article.user
	 *                $rs["_map_article"][$articles[n]]["policies"], // article.policies
	 *                $rs["_map_article"][$articles[n]]["status"], // article.status
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
	public function getByRecommendId( $recommend_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
  		$qb->where('recommend_id', '=', $recommend_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$article_ids = []; // 读取 inWhere article 数据
		$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$rs["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $rs;
	}

		

	/**
	 * 按推荐ID查询一组推荐记录
	 * @param array   $recommend_ids 唯一主键数组 ["$recommend_id1","$recommend_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 推荐记录MAP {"recommend_id1":{"key":"value",...}...}
	 */
	public function getInByRecommendId($recommend_ids, $select=["recommend.recommend_id","recommend.title","recommend.type","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at"], $order=["recommend.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
  		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$article_ids = []; // 读取 inWhere article 数据
 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['recommend_id']] = $rs;
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$map["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
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
	 * 按推荐ID保存推荐记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByRecommendId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("recommend_id", $data, ["recommend_id"], ['_id', 'recommend_id']);
		return $this->getByRecommendId( $rs['recommend_id'], $select );
	}

	/**
	 * 根据推荐ID上传呈现图片。
	 * @param string $recommend_id 推荐ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByRecommendId($recommend_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('recommend_id', $recommend_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('recommend_id', ["recommend_id"=>$recommend_id, "images"=>$paths] );
		}

		return $fs;
	}


	/**
	 * 添加推荐记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["recommend_id"]) ) { 
			$data["recommend_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排推荐记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 推荐记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["recommend.recommend_id","recommend.title","recommend.type","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at"], $order=["recommend.created_at"=>"asc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
  

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$article_ids = []; // 读取 inWhere article 数据
 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$data["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
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
	 * 按条件检索推荐记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["recommend.recommend_id","recommend.title","recommend.type","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["recommend_id"] 按推荐ID查询 ( = )
	 *			      $query["type"] 按类型查询 ( = )
	 *			      $query["title"] 按主题查询 ( LIKE )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_asc"]  按更新时间 ASC 排序
	 *           
	 * @return array 推荐记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["recommend_id"],  // 推荐ID 
	 *               	["title"],  // 主题 
	 *               	["type"],  // 方式 
	 *               	["images"],  // 呈现图片 
	 *               	["keywords"],  // 关键词 
	 *               	["categories"],  // 相关栏目 
	 *               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["articles"],  // 相关文章 
	 *               	["article"][$articles[n]]["article_id"], // article.article_id
	 *               	["orderby"],  // 排序方式 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["article"][$articles[n]]["created_at"], // article.created_at
	 *               	["article"][$articles[n]]["updated_at"], // article.updated_at
	 *               	["article"][$articles[n]]["outer_id"], // article.outer_id
	 *               	["article"][$articles[n]]["cover"], // article.cover
	 *               	["article"][$articles[n]]["thumbs"], // article.thumbs
	 *               	["article"][$articles[n]]["images"], // article.images
	 *               	["article"][$articles[n]]["videos"], // article.videos
	 *               	["article"][$articles[n]]["audios"], // article.audios
	 *               	["article"][$articles[n]]["title"], // article.title
	 *               	["article"][$articles[n]]["author"], // article.author
	 *               	["article"][$articles[n]]["origin"], // article.origin
	 *               	["article"][$articles[n]]["origin_url"], // article.origin_url
	 *               	["article"][$articles[n]]["summary"], // article.summary
	 *               	["article"][$articles[n]]["seo_title"], // article.seo_title
	 *               	["article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	 *               	["article"][$articles[n]]["seo_summary"], // article.seo_summary
	 *               	["article"][$articles[n]]["publish_time"], // article.publish_time
	 *               	["article"][$articles[n]]["update_time"], // article.update_time
	 *               	["article"][$articles[n]]["create_time"], // article.create_time
	 *               	["article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	 *               	["article"][$articles[n]]["sync"], // article.sync
	 *               	["article"][$articles[n]]["content"], // article.content
	 *               	["article"][$articles[n]]["ap_content"], // article.ap_content
	 *               	["article"][$articles[n]]["delta"], // article.delta
	 *               	["article"][$articles[n]]["param"], // article.param
	 *               	["article"][$articles[n]]["stick"], // article.stick
	 *               	["article"][$articles[n]]["preview"], // article.preview
	 *               	["article"][$articles[n]]["links"], // article.links
	 *               	["article"][$articles[n]]["user"], // article.user
	 *               	["article"][$articles[n]]["policies"], // article.policies
	 *               	["article"][$articles[n]]["status"], // article.status
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

		$select = empty($query['select']) ? ["recommend.recommend_id","recommend.title","recommend.type","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
  
		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("recommend.recommend_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("recommend.title","like", "%{$query['keyword']}%");
			});
		}


		// 按推荐ID查询 (=)  
		if ( array_key_exists("recommend_id", $query) &&!empty($query['recommend_id']) ) {
			$qb->where("recommend.recommend_id", '=', "{$query['recommend_id']}" );
		}
		  
		// 按类型查询 (=)  
		if ( array_key_exists("type", $query) &&!empty($query['type']) ) {
			$qb->where("recommend.type", '=', "{$query['type']}" );
		}
		  
		// 按主题查询 (LIKE)  
		if ( array_key_exists("title", $query) &&!empty($query['title']) ) {
			$qb->where("recommend.title", 'like', "%{$query['title']}%" );
		}
		  

		// 按创建时间 ASC 排序
		if ( array_key_exists("orderby_created_at_asc", $query) &&!empty($query['orderby_created_at_asc']) ) {
			$qb->orderBy("recommend.created_at", "asc");
		}

		// 按更新时间 ASC 排序
		if ( array_key_exists("orderby_updated_at_asc", $query) &&!empty($query['orderby_updated_at_asc']) ) {
			$qb->orderBy("recommend.updated_at", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$recommends = $qb->select( $select )->pgArray($perpage, ['recommend._id'], 'page', $page);

 		$article_ids = []; // 读取 inWhere article 数据
 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($recommends['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$recommends["article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$recommends["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$recommends['_sql'] = $qb->getSql();
			$recommends['query'] = $query;
		}

		return $recommends;
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
			
			// 连接文章 (article as a )
			if ( strpos( $fd, "a." ) === 0 || strpos("article.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["article"][] = trim($arr[1]);
				$inwhereSelect["article"][] = "article_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.articles");
				}
			}
			
			// 连接栏目 (category as c )
			if ( strpos( $fd, "c." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "category_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.categories");
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
			"recommend_id",  // 推荐ID
			"title",  // 主题
			"type",  // 方式
			"images",  // 呈现图片
			"keywords",  // 关键词
			"categories",  // 相关栏目
			"articles",  // 相关文章
			"orderby",  // 排序方式
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>