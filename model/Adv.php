<?php
/**
 * Class Adv 
 * 广告数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-29 11:17:14
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;

                                                                                                                                                                                                                                                            
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Adv extends Model {


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
	 * 广告数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('adv'); // 数据表名称 xpmsns_pages_adv
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例
		$this->mediaPrivate = new Media(['host'=>Utils::getHome(), 'private'=>true]); // 私有媒体文件实例

	}
	

	/**
	 * 创建数据表
	 * @return $this
	 */
	function __schema() {

		// 广告ID
		$this->putColumn( 'adv_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 广告别名
		$this->putColumn( 'adv_slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 名称
		$this->putColumn( 'name', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 文案
		$this->putColumn( 'intro', $this->type("string", ["length"=>600, "null"=>true]));
		// 链接
		$this->putColumn( 'link', $this->type("string", ["length"=>400, "null"=>true]));
		// 广告图片(多图)
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 封面图片
		$this->putColumn( 'cover', $this->type("string", ["length"=>400, "null"=>true]));
		// 服务协议
		$this->putColumn( 'terms', $this->type("string", ["length"=>400, "null"=>true]));
		// 尺寸
		$this->putColumn( 'size', $this->type("string", ["length"=>400, "json"=>true, "null"=>true]));
		// 位置名称
		$this->putColumn( 'position_name', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 位置编号
		$this->putColumn( 'position_no', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 有效期
		$this->putColumn( 'expired', $this->type("timestampTz", ["index"=>true, "null"=>true]));
		// 点击量
		$this->putColumn( 'pageview', $this->type("bigInteger", ["index"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>50, "index"=>true, "default"=>"online", "null"=>true]));
		// 支付状态
		$this->putColumn( 'paystatus', $this->type("string", ["length"=>50, "index"=>true, "default"=>"unpayed", "null"=>true]));
		// 单价
		$this->putColumn( 'price', $this->type("integer", ["default"=>"100", "null"=>true]));
		// 优先级
		$this->putColumn( 'priority', $this->type("integer", ["index"=>true, "null"=>true]));
		// 操作者
		$this->putColumn( 'user', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 关键词
		$this->putColumn( 'keyword', $this->type("text", ["null"=>true]));

		return $this;
	}

	
	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	function format( & $rs ) {

		// 格式化广告图片(多图)
		// 返回: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
			$rs["images"] = !is_array($rs["images"]) ? [] : $rs["images"];
			foreach ($rs["images"] as & $file ) {
				$file =empty($file) ? [] : $this->media->get( $file );
			}
		}

		// 格式化封面图片
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('cover', $rs ) ) {
			$rs["cover"] = empty($rs["cover"]) ? [] : $this->media->get( $rs["cover"] );
		}

		// 格式化服务协议
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('terms', $rs ) ) {
			$rs["terms"] = empty($rs["terms"]) ? [] : $this->meidaPrivate->get( $rs["terms"] );
		}


		
		// 格式化支付状态
		// 返回: "_paystatus_types" 所有状态表述, "_paystatus_name" 状态名称,  "_paystatus" 当前状态表述, "paystatus" 当前状态数值
		if ( array_key_exists('paystatus', $rs ) && !empty($rs['paystatus']) ) {
			$rs["_paystatus_types"] = [
		  		"payed" => [
		  			"value" => "payed",
		  			"name" => "已付款",
		  			"style" => "success"
		  		],
		  		"unpayed" => [
		  			"value" => "unpayed",
		  			"name" => "待付款",
		  			"style" => "danger"
		  		]
			];
			$rs["_paystatus_name"] = "支付状态";
			$rs["_paystatus"] = $rs["_paystatus_types"][$rs["paystatus"]];
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
	 * 按广告ID查询一条广告记录
	 * @param string $adv_id 唯一主键
	 */
	function getByAdvId( $adv_id, $select=['*']) {

		$rs = $this->getBy('adv_id',$adv_id, $select);
		if ( empty($rs) ) {
			return $rs;
		}
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按广告ID查询一组广告记录
	 * @param array   $adv_ids 唯一主键数组 ["$adv_id1","$adv_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 广告记录MAP {"adv_id1":{"key":"value",...}...}
	 */
	function getIn($adv_ids, $order=[""=>"asc"], $select=["*"] ) {
		
		$qb = $this->query()->whereIn('adv_id', $adv_ids);
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray();
		$map = [];
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['adv_id']] = $rs;
		}

		return $map;
	}


	/**
	 * 按广告ID保存广告记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	function save( $data, $select=["*"] ) {
		$rs = $this->saveBy("adv_id", $data, ["adv_id", "adv_slug"], $select);
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按广告别名查询一条广告记录
	 * @param string $adv_slug 唯一主键
	 */
	function getByAdvSlug( $adv_slug, $select=['*']) {

		$rs = $this->getBy('adv_slug',$adv_slug, $select);
		if ( empty($rs) ) {
			return $rs;
		}
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按广告别名查询一组广告记录
	 * @param array   $adv_slugs 唯一主键数组 ["$adv_slug1","$adv_slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 广告记录MAP {"adv_slug1":{"key":"value",...}...}
	 */
	function getInByAdvSlug($adv_slugs, $order=[""=>"asc"], $select=["*"] ) {
		
		$qb = $this->query()->whereIn('adv_slug', $adv_slugs);
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray();
		$map = [];
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['adv_slug']] = $rs;
		}

		return $map;
	}


	/**
	 * 按广告别名保存广告记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	function saveByAdvSlug( $data, $select=["*"] ) {
		$rs = $this->saveBy("adv_slug", $data, ["adv_id", "adv_slug"], $select);
		$this->format($rs);
		return $rs;
	}





	/**
	 * 根据广告ID上传广告图片(多图)。
	 * @param string $adv_id 广告ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadImages($adv_id, $file_path, $index=null) {

		$rs = $this->getBy('adv_id', $adv_id, [$file_field_name]);
		$paths = empty($rs[$file_field_name]) ? [] : $rs[$file_field_name];
		$fs = $this->meida->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		$this->updateBy('adv_id', ["adv_id"=>$adv_id, "images"=>$paths] );

		return $fs;
	}


	/**
	 * 根据广告ID上传封面图片。
	 * @param string $adv_id 广告ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadCover($adv_id, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('adv_id', ["adv_id"=>$adv_id, "cover"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据广告ID上传服务协议。
	 * @param string $adv_id 广告ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadTerms($adv_id, $file_path) {

		$fs =  $this->meidaPrivate->uploadFile( $file_path );
		$this->updateBy('adv_id', ["adv_id"=>$adv_id, "terms"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据广告别名上传广告图片(多图)。
	 * @param string $adv_slug 广告别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadImagesByAdvSlug($adv_slug, $file_path, $index=null) {

		$rs = $this->getBy('adv_slug', $adv_slug, [$file_field_name]);
		$paths = empty($rs[$file_field_name]) ? [] : $rs[$file_field_name];
		$fs = $this->meida->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		$this->updateBy('adv_slug', ["adv_slug"=>$adv_slug, "images"=>$paths] );

		return $fs;
	}


	/**
	 * 根据广告别名上传封面图片。
	 * @param string $adv_slug 广告别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadCoverByAdvSlug($adv_slug, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('adv_slug', ["adv_slug"=>$adv_slug, "cover"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据广告别名上传服务协议。
	 * @param string $adv_slug 广告别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadTermsByAdvSlug($adv_slug, $file_path) {

		$fs =  $this->meidaPrivate->uploadFile( $file_path );
		$this->updateBy('adv_slug', ["adv_slug"=>$adv_slug, "terms"=>$fs['path']]);
		return $fs;
	}



	/**
	 * 添加广告记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["adv_id"]) ) { 
			$data["adv_id"] = $this->genId();
		}
		return parent::create( $data );
	}



	/**
	 * 查询前排广告记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select  选取字段，默认选取所有
	 * @return array 广告记录数组 [{"key":"value",...}...]
	 */
	function top( $limit=100, $order=[""=>"asc"], $select=["*"] ) {

		$qb = $this->query();
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		return $qb->get()->toArray();
	}


	/**
	 * 按条件检索广告记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["position_no","position_name","name","images","paystatus","status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["position_no"] 按位置编号查询 ( AND = )
	 *			      $query["pnos"] 按位置编号查询 ( AND IN )
	 *			      $query["slug"] 按广告别名查询 ( AND = )
	 *			      $query["adv_ids"] 按广告ID查询 ( AND IN )
	 *			      $query["name"] 按名称查询 ( AND LIKE )
	 *			      $query["paystatus"] 按支付状态查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["order_pri"]  按优先级 ASC 排序
	 *			      $query["orderby_expired_desc"]  按有效期 DESC 排序
	 *			      $query["orderby_expired_asc"]  按有效期 ASC 排序
	 *           
	 * @return array 广告记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 */
	function search( $query = [] ) {

		$select = empty($query['select']) ? ["position_no","position_name","name","images","paystatus","status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "adv_id");

		$qb = $this->query();

		// 按关键词查找 
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("adv_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("adv_slug","like", "%{$query['keyword']}%");
				$qb->orWhere("name","like", "%{$query['keyword']}%");
				$qb->orWhere("position_name","like", "%{$query['keyword']}%");
				$qb->orWhere("position_no","like", "%{$query['keyword']}%");
				$qb->orWhere("keyword","like", "%{$query['keyword']}%");
			});
		}


		// 按位置编号查询
		if ( array_key_exists("position_no", $query) &&!empty($query['position_no']) ) {
			$qb->where("position_no", '=', "{$query['position_no']}" );
		}
		  
		// 按位置编号查询
		if ( array_key_exists("pnos", $query) &&!empty($query['pnos']) ) {
			if ( is_string($query['pnos']) ) {
				$query['pnos'] = explode(',', $query['pnos']);
			}
			$qb->whereIn("position_no",  $query['pnos'] );
		}
		  
		// 按广告别名查询
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("adv_slug", '=', "{$query['slug']}" );
		}
		  
		// 按广告ID查询
		if ( array_key_exists("adv_ids", $query) &&!empty($query['adv_ids']) ) {
			if ( is_string($query['adv_ids']) ) {
				$query['adv_ids'] = explode(',', $query['adv_ids']);
			}
			$qb->whereIn("adv_id",  $query['adv_ids'] );
		}
		  
		// 按名称查询
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("name", 'like', "%{$query['name']}%" );
		}
		  
		// 按支付状态查询
		if ( array_key_exists("paystatus", $query) &&!empty($query['paystatus']) ) {
			$qb->where("paystatus", '=', "{$query['paystatus']}" );
		}
		  
		// 按状态查询
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("status", '=', "{$query['status']}" );
		}
		  

		// 按优先级 ASC 排序
		if ( array_key_exists("order_pri", $query) &&!empty($query['order_pri']) ) {
			$qb->orderBy("priority", "asc");
		}

		// 按有效期 DESC 排序
		if ( array_key_exists("orderby_expired_desc", $query) &&!empty($query['orderby_expired_desc']) ) {
			$qb->orderBy("expired", "desc");
		}

		// 按有效期 ASC 排序
		if ( array_key_exists("orderby_expired_asc", $query) &&!empty($query['orderby_expired_asc']) ) {
			$qb->orderBy("expired", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$advs = $qb->select( $select )->pgArray($perpage, ['_id'], 'page', $page);
		foreach ($advs['data'] as & $rs ) {
			$this->format($rs);
		}

		if ($_GET['debug'] == 1) { 
			$advs['_sql'] = $qb->getSql();
			$advs['_query'] = $query;
		}

		return $advs;
	}

}

?>