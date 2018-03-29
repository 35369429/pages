<?php
/**
 * Class Siteconf 
 * 站点配置数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-29 11:09:18
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;

                                                                                                                                                                                                                                                                                                                   
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Siteconf extends Model {


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
	 * 站点配置数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('site'); // 数据表名称 xpmsns_pages_site
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

	}
	

	/**
	 * 创建数据表
	 * @return $this
	 */
	function __schema() {

		// 配制ID
		$this->putColumn( 'site_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 配制别名
		$this->putColumn( 'site_slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 呈现位置
		$this->putColumn( 'position', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 网站名称
		$this->putColumn( 'site_name', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 网站简介
		$this->putColumn( 'site_intro', $this->type("string", ["length"=>400, "null"=>true]));
		// 官网地址
		$this->putColumn( 'site_homepage', $this->type("string", ["length"=>200, "null"=>true]));
		// 应用下载地址
		$this->putColumn( 'site_downloadpage', $this->type("string", ["length"=>200, "null"=>true]));
		// 网站LOGO
		$this->putColumn( 'site_logo', $this->type("string", ["length"=>200, "null"=>true]));
		// 网站备案号
		$this->putColumn( 'site_no', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 公司名称
		$this->putColumn( 'company', $this->type("string", ["length"=>400, "null"=>true]));
		// 公司地址
		$this->putColumn( 'address', $this->type("string", ["length"=>400, "null"=>true]));
		// 客服电话
		$this->putColumn( 'tel', $this->type("string", ["length"=>200, "null"=>true]));
		// 小程序二维码
		$this->putColumn( 'qr_wxapp', $this->type("string", ["length"=>200, "null"=>true]));
		// 订阅号二维码
		$this->putColumn( 'qr_wxpub', $this->type("string", ["length"=>200, "null"=>true]));
		// 服务号二维码
		$this->putColumn( 'qr_wxse', $this->type("string", ["length"=>200, "null"=>true]));
		// 安卓应用二维码
		$this->putColumn( 'qr_android', $this->type("string", ["length"=>200, "null"=>true]));
		// 苹果应用二维码
		$this->putColumn( 'qr_ios', $this->type("string", ["length"=>200, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>50, "index"=>true, "default"=>"online", "null"=>true]));
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

		// 格式化网站LOGO
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('site_logo', $rs ) ) {
			$rs["site_logo"] = empty($rs["site_logo"]) ? [] : $this->media->get( $rs["site_logo"] );
		}

		// 格式化小程序二维码
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_wxapp', $rs ) ) {
			$rs["qr_wxapp"] = empty($rs["qr_wxapp"]) ? [] : $this->media->get( $rs["qr_wxapp"] );
		}

		// 格式化订阅号二维码
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_wxpub', $rs ) ) {
			$rs["qr_wxpub"] = empty($rs["qr_wxpub"]) ? [] : $this->media->get( $rs["qr_wxpub"] );
		}

		// 格式化服务号二维码
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_wxse', $rs ) ) {
			$rs["qr_wxse"] = empty($rs["qr_wxse"]) ? [] : $this->media->get( $rs["qr_wxse"] );
		}

		// 格式化安卓应用二维码
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_android', $rs ) ) {
			$rs["qr_android"] = empty($rs["qr_android"]) ? [] : $this->media->get( $rs["qr_android"] );
		}

		// 格式化苹果应用二维码
		// 返回: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_ios', $rs ) ) {
			$rs["qr_ios"] = empty($rs["qr_ios"]) ? [] : $this->media->get( $rs["qr_ios"] );
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
	 * 按配制ID查询一条站点配置记录
	 * @param string $site_id 唯一主键
	 */
	function getBySiteId( $site_id, $select=['*']) {

		$rs = $this->getBy('site_id',$site_id, $select);
		if ( empty($rs) ) {
			return $rs;
		}
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按配制ID查询一组站点配置记录
	 * @param array   $site_ids 唯一主键数组 ["$site_id1","$site_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 站点配置记录MAP {"site_id1":{"key":"value",...}...}
	 */
	function getIn($site_ids, $order=[], $select=["*"] ) {
		
		$qb = $this->query()->whereIn('site_id', $site_ids);
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray();
		$map = [];
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['site_id']] = $rs;
		}

		return $map;
	}


	/**
	 * 按配制ID保存站点配置记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	function save( $data, $select=["*"] ) {
		$rs = $this->saveBy("site_id", $data, ["site_id", "site_slug"], $select);
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按配制别名查询一条站点配置记录
	 * @param string $site_slug 唯一主键
	 */
	function getBySiteSlug( $site_slug, $select=['*']) {

		$rs = $this->getBy('site_slug',$site_slug, $select);
		if ( empty($rs) ) {
			return $rs;
		}
		$this->format($rs);
		return $rs;
	}

	
	/**
	 * 按配制别名查询一组站点配置记录
	 * @param array   $site_slugs 唯一主键数组 ["$site_slug1","$site_slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 站点配置记录MAP {"site_slug1":{"key":"value",...}...}
	 */
	function getInBySiteSlug($site_slugs, $order=[], $select=["*"] ) {
		
		$qb = $this->query()->whereIn('site_slug', $site_slugs);
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray();
		$map = [];
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['site_slug']] = $rs;
		}

		return $map;
	}


	/**
	 * 按配制别名保存站点配置记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	function saveBySiteSlug( $data, $select=["*"] ) {
		$rs = $this->saveBy("site_slug", $data, ["site_id", "site_slug"], $select);
		$this->format($rs);
		return $rs;
	}





	/**
	 * 根据配制ID上传网站LOGO。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadSitelogo($site_id, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_id', ["site_id"=>$site_id, "site_logo"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制ID上传小程序二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrwxapp($site_id, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_id', ["site_id"=>$site_id, "qr_wxapp"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制ID上传订阅号二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrwxpub($site_id, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_id', ["site_id"=>$site_id, "qr_wxpub"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制ID上传服务号二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrwxse($site_id, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_id', ["site_id"=>$site_id, "qr_wxse"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制ID上传安卓应用二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrandroid($site_id, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_id', ["site_id"=>$site_id, "qr_android"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制ID上传苹果应用二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrios($site_id, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_id', ["site_id"=>$site_id, "qr_ios"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制别名上传网站LOGO。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadSitelogoBySiteSlug($site_slug, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_slug', ["site_slug"=>$site_slug, "site_logo"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制别名上传小程序二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrwxappBySiteSlug($site_slug, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_wxapp"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制别名上传订阅号二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrwxpubBySiteSlug($site_slug, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_wxpub"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制别名上传服务号二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrwxseBySiteSlug($site_slug, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_wxse"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制别名上传安卓应用二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQrandroidBySiteSlug($site_slug, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_android"=>$fs['path']]);
		return $fs;
	}


	/**
	 * 根据配制别名上传苹果应用二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	function uploadQriosBySiteSlug($site_slug, $file_path) {

		$fs =  $this->meida->uploadFile( $file_path );
		$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_ios"=>$fs['path']]);
		return $fs;
	}



	/**
	 * 添加站点配置记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["site_id"]) ) { 
			$data["site_id"] = $this->genId();
		}
		return parent::create( $data );
	}



	/**
	 * 查询前排站点配置记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select  选取字段，默认选取所有
	 * @return array 站点配置记录数组 [{"key":"value",...}...]
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
	 * 按条件检索站点配置记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["site_slug","position","site_name","site_logo","status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["site_slug"] 按配制别名查询 ( AND = )
	 *			      $query["position"] 按呈现位置查询 ( AND = )
	 *			      $query["site_name"] 按网站名称查询 ( AND LIKE )
	 *			      $query["site_id"] 按配制ID查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["order_pri"]  按配制ID ASC 排序
	 *           
	 * @return array 站点配置记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 */
	function search( $query = [] ) {

		$select = empty($query['select']) ? ["site_slug","position","site_name","site_logo","status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "site_id");

		$qb = $this->query();

		// 按关键词查找 
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("site_slug", "like", "%{$query['keyword']}%");
				$qb->orWhere("position","like", "%{$query['keyword']}%");
				$qb->orWhere("site_name","like", "%{$query['keyword']}%");
				$qb->orWhere("site_no","like", "%{$query['keyword']}%");
			});
		}


		// 按配制别名查询
		if ( array_key_exists("site_slug", $query) &&!empty($query['site_slug']) ) {
			$qb->where("site_slug", '=', "{$query['site_slug']}" );
		}
		  
		// 按呈现位置查询
		if ( array_key_exists("position", $query) &&!empty($query['position']) ) {
			$qb->where("position", '=', "{$query['position']}" );
		}
		  
		// 按网站名称查询
		if ( array_key_exists("site_name", $query) &&!empty($query['site_name']) ) {
			$qb->where("site_name", 'like', "%{$query['site_name']}%" );
		}
		  
		// 按配制ID查询
		if ( array_key_exists("site_id", $query) &&!empty($query['site_id']) ) {
			$qb->where("site_id", '=', "{$query['site_id']}" );
		}
		  
		// 按状态查询
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("status", '=', "{$query['status']}" );
		}
		  

		// 按配制ID ASC 排序
		if ( array_key_exists("order_pri", $query) &&!empty($query['order_pri']) ) {
			$qb->orderBy("site_id", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$siteconfs = $qb->select( $select )->pgArray($perpage, ['_id'], 'page', $page);
		foreach ($siteconfs['data'] as & $rs ) {
			$this->format($rs);
		}

		if ($_GET['debug'] == 1) { 
			$siteconfs['_sql'] = $qb->getSql();
			$siteconfs['_query'] = $query;
		}

		return $siteconfs;
	}

}

?>