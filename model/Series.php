<?php
/**
 * Class Series 
 * 系列数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-08-19 18:52:21
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Series extends Model {




	/**
	 * 系列数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('series'); // 数据表名称 xpmsns_pages_series

	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 系列ID
		$this->putColumn( 'series_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 系列名称
		$this->putColumn( 'name', $this->type("string", ["length"=>128, "null"=>false]));
		// 系列别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 所属栏目
		$this->putColumn( 'category_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 摘要
		$this->putColumn( 'summary', $this->type("string", ["length"=>400, "null"=>true]));
		// 排序方式
		$this->putColumn( 'orderby', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 参数
		$this->putColumn( 'param', $this->type("string", ["length"=>128, "index"=>true, "null"=>false]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>20, "index"=>true, "null"=>false]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"on" => [
		  			"value" => "on",
		  			"name" => "开启",
		  			"style" => "primary"
		  		],
		  		"off" => [
		  			"value" => "off",
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
	 * 按系列ID查询一条系列记录
	 * @param string $series_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["series_id"],  // 系列ID 
	 *          	  $rs["name"],  // 系列名称 
	 *          	  $rs["slug"],  // 系列别名 
	 *          	  $rs["category_id"],  // 所属栏目 
	 *                $rs["c_category_id"], // category.category_id
	 *          	  $rs["summary"],  // 摘要 
	 *          	  $rs["orderby"],  // 排序方式 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["c_created_at"], // category.created_at
	 *                $rs["c_updated_at"], // category.updated_at
	 *                $rs["c_slug"], // category.slug
	 *                $rs["c_project"], // category.project
	 *                $rs["c_page"], // category.page
	 *                $rs["c_wechat"], // category.wechat
	 *                $rs["c_wechat_offset"], // category.wechat_offset
	 *                $rs["c_name"], // category.name
	 *                $rs["c_fullname"], // category.fullname
	 *                $rs["c_link"], // category.link
	 *                $rs["c_root_id"], // category.root_id
	 *                $rs["c_parent_id"], // category.parent_id
	 *                $rs["c_priority"], // category.priority
	 *                $rs["c_hidden"], // category.hidden
	 *                $rs["c_isnav"], // category.isnav
	 *                $rs["c_param"], // category.param
	 *                $rs["c_status"], // category.status
	 *                $rs["c_issubnav"], // category.issubnav
	 *                $rs["c_highlight"], // category.highlight
	 *                $rs["c_isfootnav"], // category.isfootnav
	 *                $rs["c_isblank"], // category.isblank
	 */
	public function getBySeriesId( $series_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_series as series", "{none}")->query();
 		$qb->leftJoin("xpmsns_pages_category as c", "c.category_id", "=", "series.category_id"); // 连接栏目
		$qb->where('series_id', '=', $series_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 
 
		return $rs;
	}

		

	/**
	 * 按系列ID查询一组系列记录
	 * @param array   $series_ids 唯一主键数组 ["$series_id1","$series_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 系列记录MAP {"series_id1":{"key":"value",...}...}
	 */
	public function getInBySeriesId($series_ids, $select=["series.slug","series.name","c.name","series.status","series.created_at","series.updated_at"], $order=["series.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_series as series", "{none}")->query();
 		$qb->leftJoin("xpmsns_pages_category as c", "c.category_id", "=", "series.category_id"); // 连接栏目
		$qb->whereIn('series.series_id', $series_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['series_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按系列ID保存系列记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySeriesId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("series_id", $data, ["series_id", "slug"], ['_id', 'series_id']);
		return $this->getBySeriesId( $rs['series_id'], $select );
	}
	
	/**
	 * 按系列别名查询一条系列记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["series_id"],  // 系列ID 
	 *          	  $rs["name"],  // 系列名称 
	 *          	  $rs["slug"],  // 系列别名 
	 *          	  $rs["category_id"],  // 所属栏目 
	 *                $rs["c_category_id"], // category.category_id
	 *          	  $rs["summary"],  // 摘要 
	 *          	  $rs["orderby"],  // 排序方式 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["c_created_at"], // category.created_at
	 *                $rs["c_updated_at"], // category.updated_at
	 *                $rs["c_slug"], // category.slug
	 *                $rs["c_project"], // category.project
	 *                $rs["c_page"], // category.page
	 *                $rs["c_wechat"], // category.wechat
	 *                $rs["c_wechat_offset"], // category.wechat_offset
	 *                $rs["c_name"], // category.name
	 *                $rs["c_fullname"], // category.fullname
	 *                $rs["c_link"], // category.link
	 *                $rs["c_root_id"], // category.root_id
	 *                $rs["c_parent_id"], // category.parent_id
	 *                $rs["c_priority"], // category.priority
	 *                $rs["c_hidden"], // category.hidden
	 *                $rs["c_isnav"], // category.isnav
	 *                $rs["c_param"], // category.param
	 *                $rs["c_status"], // category.status
	 *                $rs["c_issubnav"], // category.issubnav
	 *                $rs["c_highlight"], // category.highlight
	 *                $rs["c_isfootnav"], // category.isfootnav
	 *                $rs["c_isblank"], // category.isblank
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_series as series", "{none}")->query();
 		$qb->leftJoin("xpmsns_pages_category as c", "c.category_id", "=", "series.category_id"); // 连接栏目
		$qb->where('slug', '=', $slug );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 
 
		return $rs;
	}

	

	/**
	 * 按系列别名查询一组系列记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 系列记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["series.slug","series.name","c.name","series.status","series.created_at","series.updated_at"], $order=["series.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_series as series", "{none}")->query();
 		$qb->leftJoin("xpmsns_pages_category as c", "c.category_id", "=", "series.category_id"); // 连接栏目
		$qb->whereIn('series.slug', $slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按系列别名保存系列记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("slug", $data, ["series_id", "slug"], ['_id', 'series_id']);
		return $this->getBySeriesId( $rs['series_id'], $select );
	}


	/**
	 * 添加系列记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["series_id"]) ) { 
			$data["series_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排系列记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 系列记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["series.slug","series.name","c.name","series.status","series.created_at","series.updated_at"], $order=["series.created_at"=>"asc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_series as series", "{none}")->query();
 		$qb->leftJoin("xpmsns_pages_category as c", "c.category_id", "=", "series.category_id"); // 连接栏目


		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		foreach ($data as & $rs ) {
			$this->format($rs);
			
 		}

 
		return $data;
	
	}


	/**
	 * 按条件检索系列记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["series.slug","series.name","c.name","series.status","series.created_at","series.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["series_id"] 按系列ID查询 ( = )
	 *			      $query["series_id"] 按系列IDS查询 ( IN )
	 *			      $query["param"] 按参数查询 ( = )
	 *			      $query["slug"] 按别名查询 ( = )
	 *			      $query["category_id"] 按栏目查询 ( = )
	 *			      $query["orderby"] 按 排序查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
	 *           
	 * @return array 系列记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["series_id"],  // 系列ID 
	 *               	["name"],  // 系列名称 
	 *               	["slug"],  // 系列别名 
	 *               	["category_id"],  // 所属栏目 
	 *               	["c_category_id"], // category.category_id
	 *               	["summary"],  // 摘要 
	 *               	["orderby"],  // 排序方式 
	 *               	["param"],  // 参数 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["c_created_at"], // category.created_at
	 *               	["c_updated_at"], // category.updated_at
	 *               	["c_slug"], // category.slug
	 *               	["c_project"], // category.project
	 *               	["c_page"], // category.page
	 *               	["c_wechat"], // category.wechat
	 *               	["c_wechat_offset"], // category.wechat_offset
	 *               	["c_name"], // category.name
	 *               	["c_fullname"], // category.fullname
	 *               	["c_link"], // category.link
	 *               	["c_root_id"], // category.root_id
	 *               	["c_parent_id"], // category.parent_id
	 *               	["c_priority"], // category.priority
	 *               	["c_hidden"], // category.hidden
	 *               	["c_isnav"], // category.isnav
	 *               	["c_param"], // category.param
	 *               	["c_status"], // category.status
	 *               	["c_issubnav"], // category.issubnav
	 *               	["c_highlight"], // category.highlight
	 *               	["c_isfootnav"], // category.isfootnav
	 *               	["c_isblank"], // category.isblank
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["series.slug","series.name","c.name","series.status","series.created_at","series.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "series.series_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_series as series", "{none}")->query();
 		$qb->leftJoin("xpmsns_pages_category as c", "c.category_id", "=", "series.category_id"); // 连接栏目

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("series.series_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("series.name","like", "%{$query['keyword']}%");
				$qb->orWhere("series.slug","like", "%{$query['keyword']}%");
			});
		}


		// 按系列ID查询 (=)  
		if ( array_key_exists("series_id", $query) &&!empty($query['series_id']) ) {
			$qb->where("series.series_id", '=', "{$query['series_id']}" );
		}
		  
		// 按系列IDS查询 (IN)  
		if ( array_key_exists("series_id", $query) &&!empty($query['series_id']) ) {
			if ( is_string($query['series_id']) ) {
				$query['series_id'] = explode(',', $query['series_id']);
			}
			$qb->whereIn("series.series_id",  $query['series_id'] );
		}
		  
		// 按参数查询 (=)  
		if ( array_key_exists("param", $query) &&!empty($query['param']) ) {
			$qb->where("series.param", '=', "{$query['param']}" );
		}
		  
		// 按别名查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("series.slug", '=', "{$query['slug']}" );
		}
		  
		// 按栏目查询 (=)  
		if ( array_key_exists("category_id", $query) &&!empty($query['category_id']) ) {
			$qb->where("series.category_id", '=', "{$query['category_id']}" );
		}
		  
		// 按 排序查询 (=)  
		if ( array_key_exists("orderby", $query) &&!empty($query['orderby']) ) {
			$qb->where("series.orderby", '=', "{$query['orderby']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("series.status", '=', "{$query['status']}" );
		}
		  

		// 按创建时间 ASC 排序
		if ( array_key_exists("orderby_created_at_asc", $query) &&!empty($query['orderby_created_at_asc']) ) {
			$qb->orderBy("series.created_at", "asc");
		}

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("series.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$seriess = $qb->select( $select )->pgArray($perpage, ['series._id'], 'page', $page);

 		foreach ($seriess['data'] as & $rs ) {
			$this->format($rs);
			
 		}

 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$seriess['_sql'] = $qb->getSql();
			$seriess['query'] = $query;
		}

		return $seriess;
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
				$select[$idx] = "series." .$select[$idx];
				continue;
			}
			
			//  连接栏目 (category as c )
			if ( trim($fd) == "category.*" || trim($fd) == "c.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getFields') ) {
					$fields = \Xpmsns\Pages\Model\Category::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "c.{$field} as c_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "series.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "category." ) === 0 ) {
				$as = str_replace('category.', 'c_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "c.") === 0 ) {
				$as = str_replace('c.', 'c_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
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
			"series_id",  // 系列ID
			"name",  // 系列名称
			"slug",  // 系列别名
			"category_id",  // 所属栏目
			"summary",  // 摘要
			"orderby",  // 排序方式
			"param",  // 参数
			"status",  // 状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>