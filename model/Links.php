<?php
/**
 * Class Links 
 * 友链数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-30 03:13:28
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;

                                                                                                                                                     
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Links extends Model {


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
	 * 友链数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('links'); // 数据表名称 xpmsns_pages_links
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

	}
	

	/**
	 * 创建数据表
	 * @return $this
	 */
	function __schema() {

		// 友链ID
		$this->putColumn( 'links_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 友链别名
		$this->putColumn( 'links_slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 名称
		$this->putColumn( 'name', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 摘要
		$this->putColumn( 'summary', $this->type("string", ["length"=>600, "null"=>true]));
		// 链接
		$this->putColumn( 'link', $this->type("string", ["length"=>400, "null"=>true]));
		// LOGO
		$this->putColumn( 'logo', $this->type("string", ["length"=>400, "null"=>true]));
		// 尺寸
		$this->putColumn( 'size', $this->type("string", ["length"=>400, "json"=>true, "null"=>true]));
		// 呈现位置
		$this->putColumn( 'position', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 点击量
		$this->putColumn( 'pageview', $this->type("bigInteger", ["index"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>50, "index"=>true, "default"=>"online", "null"=>true]));
		// 优先级
		$this->putColumn( 'priority', $this->type("integer", ["index"=>true, "default"=>"9999", "null"=>true]));
		// 操作者
		$this->putColumn( 'user', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));

		return $this;
	}

	
	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	function format( & $rs ) {

		// 格式化LOGO
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('logo', $rs ) ) {
			$rs["logo"] = empty($rs["logo"]) ? [] : $this->media->get( $rs["logo"] );
		}


		
		// 格式化状态
		// 返回: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"online" => [
		  			"value" => "online",
		  			"name" => "上线",
		  			"style" => "success"
		  		],
		  		"offline" => [
		  			"value" => "offline",
		  			"name" => "下线",
		  			"style" => "danger"
		  		]
			];
			$rs["_status_name"] = "激活状态";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}
 

		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}


	
	/**
	 * 按友链ID查询一条友链记录
	 * @param string $links_id 唯一主键
	 */
	function getByLinksId( $links_id, $select=['*']) {

		$rs = $this->getBy('links_id',$links_id, $select);
		if ( empty($rs) ) {
			return $rs;
		}
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按友链ID查询一组友链记录
	 * @param array   $links_ids 唯一主键数组 ["$links_id1","$links_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 友链记录MAP {"links_id1":{"key":"value",...}...}
	 */
	function getIn($links_ids, $order=[], $select=["*"] ) {
		
		$qb = $this->query()->whereIn('links_id', $links_ids);
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray();
		$map = [];
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['links_id']] = $rs;
		}

		return $map;
	}


	/**
	 * 按友链ID保存友链记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	function save( $data, $select=["*"] ) {
		$rs = $this->saveBy("links_id", $data, ["links_id", "links_slug"], $select);
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按友链别名查询一条友链记录
	 * @param string $links_slug 唯一主键
	 */
	function getByLinksSlug( $links_slug, $select=['*']) {

		$rs = $this->getBy('links_slug',$links_slug, $select);
		if ( empty($rs) ) {
			return $rs;
		}
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按友链别名查询一组友链记录
	 * @param array   $links_slugs 唯一主键数组 ["$links_slug1","$links_slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 友链记录MAP {"links_slug1":{"key":"value",...}...}
	 */
	function getInByLinksSlug($links_slugs, $order=[], $select=["*"] ) {
		
		$qb = $this->query()->whereIn('links_slug', $links_slugs);
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray();
		$map = [];
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['links_slug']] = $rs;
		}

		return $map;
	}


	/**
	 * 按友链别名保存友链记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	function saveByLinksSlug( $data, $select=["*"] ) {
		$rs = $this->saveBy("links_slug", $data, ["links_id", "links_slug"], $select);
		$this->format($rs);
		return $rs;
	}





	/**
	 * 根据友链ID上传LOGO。
	 * @param string $links_id 友链ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadLogo($links_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('links_id', ["links_id"=>$links_id, "logo"=>$fs['path']]);
		}
		return $fs;
	}


	/**
	 * 根据友链别名上传LOGO。
	 * @param string $links_slug 友链别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadLogoByLinksSlug($links_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('links_slug', ["links_slug"=>$links_slug, "logo"=>$fs['path']]);
		}
		return $fs;
	}



	/**
	 * 添加友链记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["links_id"]) ) { 
			$data["links_id"] = $this->genId();
		}
		return parent::create( $data );
	}



	/**
	 * 查询前排友链记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select  选取字段，默认选取所有
	 * @return array 友链记录数组 [{"key":"value",...}...]
	 */
	function top( $limit=100, $order=[], $select=["*"] ) {

		$qb = $this->query();
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		return $qb->get()->toArray();
	}


	/**
	 * 按条件检索友链记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["links_slug","name","logo","position","link","status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["name"] 按名称查询 ( AND LIKE )
	 *			      $query["links_slug"] 按友链别名查询 ( AND = )
	 *			      $query["position"] 按呈现位置查询 ( AND = )
	 *			      $query["priority"] 按优先级查询 ( AND > )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["order_pri"]  按优先级 ASC 排序
	 *           
	 * @return array 友链记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 */
	function search( $query = [] ) {

		$select = empty($query['select']) ? ["links_slug","name","logo","position","link","status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "links_id");

		$qb = $this->query();

		// 按关键词查找 
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("links_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("links_slug","like", "%{$query['keyword']}%");
				$qb->orWhere("name","like", "%{$query['keyword']}%");
				$qb->orWhere("position","like", "%{$query['keyword']}%");
			});
		}


		// 按名称查询
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("name", 'like', "%{$query['name']}%" );
		}
		  
		// 按友链别名查询
		if ( array_key_exists("links_slug", $query) &&!empty($query['links_slug']) ) {
			$qb->where("links_slug", '=', "{$query['links_slug']}" );
		}
		  
		// 按呈现位置查询
		if ( array_key_exists("position", $query) &&!empty($query['position']) ) {
			$qb->where("position", '=', "{$query['position']}" );
		}
		  
		// 按优先级查询
		if ( array_key_exists("priority", $query) &&!empty($query['priority']) ) {
			$qb->where("priority", '>', "{$query['priority']}" );
		}
		  
		// 按状态查询
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("status", '=', "{$query['status']}" );
		}
		  

		// 按优先级 ASC 排序
		if ( array_key_exists("order_pri", $query) &&!empty($query['order_pri']) ) {
			$qb->orderBy("priority", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$linkss = $qb->select( $select )->pgArray($perpage, ['_id'], 'page', $page);
		foreach ($linkss['data'] as & $rs ) {
			$this->format($rs);
		}

		if ($_GET['debug'] == 1) { 
			$linkss['_sql'] = $qb->getSql();
			$linkss['_query'] = $query;
		}

		return $linkss;
	}

}

?>