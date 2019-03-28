<?php
/**
 * Class Adv 
 * 广告数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-28 18:26:26
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                            
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Media;
use \Mina\Cache\Redis as Cache;
use \Xpmse\Loader\App as App;
use \Xpmse\Job;


class Adv extends Model {


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
     * 数据缓存对象
     */
    protected $cache = null;

	/**
	 * 广告数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
        $this->table('adv'); // 数据表名称 xpmsns_pages_adv
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_pages_adv:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例
		$this->mediaPrivate = new Media(['host'=>Utils::getHome(), 'private'=>true]); // 私有媒体文件实例

       
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 广告ID
		$this->putColumn( 'adv_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 广告别名
		$this->putColumn( 'adv_slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 所属栏目
		$this->putColumn( 'categories', $this->type("string", ["length"=>128, "index"=>true, "json"=>true, "null"=>true]));
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
	public function format( & $rs ) {
     
		$fileFields = []; 
		// 格式化: 广告图片(多图)
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
            array_push($fileFields, 'images');
		}
		// 格式化: 封面图片
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('cover', $rs ) ) {
            array_push($fileFields, 'cover');
		}
		// 格式化: 服务协议
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('terms', $rs ) ) {
            array_push($fileFields, 'terms');
		}

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

		// 格式化: 支付状态
		// 返回值: "_paystatus_types" 所有状态表述, "_paystatus_name" 状态名称,  "_paystatus" 当前状态表述, "paystatus" 当前状态数值
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
		  		],
			];
			$rs["_paystatus_name"] = "paystatus";
			$rs["_paystatus"] = $rs["_paystatus_types"][$rs["paystatus"]];
		}

		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
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
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按广告ID查询一条广告记录
	 * @param string $adv_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["adv_id"],  // 广告ID 
	 *          	  $rs["adv_slug"],  // 广告别名 
	 *          	  $rs["categories"],  // 所属栏目 
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["intro"],  // 文案 
	 *          	  $rs["link"],  // 链接 
	 *          	  $rs["images"],  // 广告图片(多图) 
	 *          	  $rs["cover"],  // 封面图片 
	 *          	  $rs["terms"],  // 服务协议 
	 *          	  $rs["size"],  // 尺寸 
	 *          	  $rs["position_name"],  // 位置名称 
	 *          	  $rs["position_no"],  // 位置编号 
	 *          	  $rs["expired"],  // 有效期 
	 *          	  $rs["pageview"],  // 点击量 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["paystatus"],  // 支付状态 
	 *          	  $rs["price"],  // 单价 
	 *          	  $rs["priority"],  // 优先级 
	 *          	  $rs["user"],  // 操作者 
	 *          	  $rs["keyword"],  // 关键词 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
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
	 */
	public function getByAdvId( $adv_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_adv as adv", "{none}")->query();
 		$qb->where('adv.adv_id', '=', $adv_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$slugs = []; // 读取 inWhere category 数据
		$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}

		return $rs;
	}

		

	/**
	 * 按广告ID查询一组广告记录
	 * @param array   $adv_ids 唯一主键数组 ["$adv_id1","$adv_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 广告记录MAP {"adv_id1":{"key":"value",...}...}
	 */
	public function getInByAdvId($adv_ids, $select=["adv.adv_id","adv.adv_slug","category.name","adv.name","adv.position_name","adv.status"], $order=["adv.priority"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_adv as adv", "{none}")->query();
 		$qb->whereIn('adv.adv_id', $adv_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$slugs = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['adv_id']] = $rs;
			
 			// for inWhere category
			$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}


		return $map;
	}


	/**
	 * 按广告ID保存广告记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByAdvId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("adv_id", $data, ["adv_id", "adv_slug"], ['_id', 'adv_id']);
		return $this->getByAdvId( $rs['adv_id'], $select );
	}
	
	/**
	 * 按广告别名查询一条广告记录
	 * @param string $adv_slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["adv_id"],  // 广告ID 
	 *          	  $rs["adv_slug"],  // 广告别名 
	 *          	  $rs["categories"],  // 所属栏目 
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["intro"],  // 文案 
	 *          	  $rs["link"],  // 链接 
	 *          	  $rs["images"],  // 广告图片(多图) 
	 *          	  $rs["cover"],  // 封面图片 
	 *          	  $rs["terms"],  // 服务协议 
	 *          	  $rs["size"],  // 尺寸 
	 *          	  $rs["position_name"],  // 位置名称 
	 *          	  $rs["position_no"],  // 位置编号 
	 *          	  $rs["expired"],  // 有效期 
	 *          	  $rs["pageview"],  // 点击量 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["paystatus"],  // 支付状态 
	 *          	  $rs["price"],  // 单价 
	 *          	  $rs["priority"],  // 优先级 
	 *          	  $rs["user"],  // 操作者 
	 *          	  $rs["keyword"],  // 关键词 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
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
	 */
	public function getByAdvSlug( $adv_slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_adv as adv", "{none}")->query();
 		$qb->where('adv.adv_slug', '=', $adv_slug );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$slugs = []; // 读取 inWhere category 数据
		$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}

		return $rs;
	}

	

	/**
	 * 按广告别名查询一组广告记录
	 * @param array   $adv_slugs 唯一主键数组 ["$adv_slug1","$adv_slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 广告记录MAP {"adv_slug1":{"key":"value",...}...}
	 */
	public function getInByAdvSlug($adv_slugs, $select=["adv.adv_id","adv.adv_slug","category.name","adv.name","adv.position_name","adv.status"], $order=["adv.priority"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_adv as adv", "{none}")->query();
 		$qb->whereIn('adv.adv_slug', $adv_slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$slugs = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['adv_slug']] = $rs;
			
 			// for inWhere category
			$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}


		return $map;
	}


	/**
	 * 按广告别名保存广告记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByAdvSlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("adv_slug", $data, ["adv_id", "adv_slug"], ['_id', 'adv_id']);
		return $this->getByAdvId( $rs['adv_id'], $select );
	}

	/**
	 * 根据广告ID上传广告图片(多图)。
	 * @param string $adv_id 广告ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByAdvId($adv_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('adv_id', $adv_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('adv_id', ["adv_id"=>$adv_id, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据广告ID上传封面图片。
	 * @param string $adv_id 广告ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverByAdvId($adv_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('adv_id', ["adv_id"=>$adv_id, "cover"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据广告ID上传服务协议。
	 * @param string $adv_id 广告ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadTermsByAdvId($adv_id, $file_path, $upload_only=false ) {

		$fs =  $this->meidaPrivate->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('adv_id', ["adv_id"=>$adv_id, "terms"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据广告别名上传广告图片(多图)。
	 * @param string $adv_slug 广告别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByAdvSlug($adv_slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('adv_slug', $adv_slug, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('adv_slug', ["adv_slug"=>$adv_slug, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据广告别名上传封面图片。
	 * @param string $adv_slug 广告别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverByAdvSlug($adv_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('adv_slug', ["adv_slug"=>$adv_slug, "cover"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据广告别名上传服务协议。
	 * @param string $adv_slug 广告别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadTermsByAdvSlug($adv_slug, $file_path, $upload_only=false ) {

		$fs =  $this->meidaPrivate->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('adv_slug', ["adv_slug"=>$adv_slug, "terms"=>$fs['path']]);
		}
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
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 广告记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["adv.adv_id","adv.adv_slug","category.name","adv.name","adv.position_name","adv.status"], $order=["adv.priority"=>"asc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_adv as adv", "{none}")->query();
 

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$slugs = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索广告记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["adv.adv_id","adv.adv_slug","category.name","adv.name","adv.position_name","adv.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["adv_id"] 按广告ID查询 ( = )
	 *			      $query["pnos"] 按广告别名查询 ( IN )
	 *			      $query["slug"] 按名称查询 ( = )
	 *			      $query["adv_ids"] 按位置名称查询 ( IN )
	 *			      $query["expired"] 按有效期查询 ( LIKE )
	 *			      $query["priority"] 按优先级查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["categories"] 按所属栏目查询 ( LIKE-MULTIPLE )
	 *			      $query["order_pri"]  按name=priority ASC 排序
	 *			      $query["orderby_pageview_desc"]  按name=pageview DESC 排序
	 *			      $query["orderby_created_at_asc"]  按name=created_at ASC 排序
	 *           
	 * @return array 广告记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
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
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["adv.adv_id","adv.adv_slug","category.name","adv.name","adv.position_name","adv.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "adv.adv_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_adv as adv", "{none}")->query();
 
		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("adv.adv_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("adv.adv_slug","like", "%{$query['keyword']}%");
				$qb->orWhere("adv.categories","like", "%{$query['keyword']}%");
				$qb->orWhere("adv.name","like", "%{$query['keyword']}%");
				$qb->orWhere("adv.position_name","like", "%{$query['keyword']}%");
			});
		}


		// 按广告ID查询 (=)  
		if ( array_key_exists("adv_id", $query) &&!empty($query['adv_id']) ) {
			$qb->where("adv.adv_id", '=', "{$query['adv_id']}" );
		}
		  
		// 按广告别名查询 (IN)  
		if ( array_key_exists("pnos", $query) &&!empty($query['pnos']) ) {
			if ( is_string($query['pnos']) ) {
				$query['pnos'] = explode(',', $query['pnos']);
			}
			$qb->whereIn("adv.adv_slug",  $query['pnos'] );
		}
		  
		// 按名称查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("adv.name", '=', "{$query['slug']}" );
		}
		  
		// 按位置名称查询 (IN)  
		if ( array_key_exists("adv_ids", $query) &&!empty($query['adv_ids']) ) {
			if ( is_string($query['adv_ids']) ) {
				$query['adv_ids'] = explode(',', $query['adv_ids']);
			}
			$qb->whereIn("adv.position_name",  $query['adv_ids'] );
		}
		  
		// 按有效期查询 (LIKE)  
		if ( array_key_exists("expired", $query) &&!empty($query['expired']) ) {
			$qb->where("adv.expired", 'like', "%{$query['expired']}%" );
		}
		  
		// 按优先级查询 (=)  
		if ( array_key_exists("priority", $query) &&!empty($query['priority']) ) {
			$qb->where("adv.priority", '=', "{$query['priority']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("adv.status", '=', "{$query['status']}" );
		}
		  
		// 按所属栏目查询 (LIKE-MULTIPLE)  
		if ( array_key_exists("categories", $query) &&!empty($query['categories']) ) {
            $query['categories'] = explode(',', $query['categories']);
            $qb->where(function ( $qb ) use($query) {
                foreach( $query['categories'] as $idx=>$val )  {
                    $val = trim($val);
                    if ( $idx == 0 ) {
                        $qb->where("adv.categories", 'like', "%{$val}%" );
                    } else {
                        $qb->orWhere("adv.categories", 'like', "%{$val}%");
                    }
                }
            });
		}
		  

		// 按name=priority ASC 排序
		if ( array_key_exists("order_pri", $query) &&!empty($query['order_pri']) ) {
			$qb->orderBy("adv.priority", "asc");
		}

		// 按name=pageview DESC 排序
		if ( array_key_exists("orderby_pageview_desc", $query) &&!empty($query['orderby_pageview_desc']) ) {
			$qb->orderBy("adv.pageview", "desc");
		}

		// 按name=created_at ASC 排序
		if ( array_key_exists("orderby_created_at_asc", $query) &&!empty($query['orderby_created_at_asc']) ) {
			$qb->orderBy("adv.created_at", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$advs = $qb->select( $select )->pgArray($perpage, ['adv._id'], 'page', $page);

 		$categories_slugs = []; // 读取 inWhere category 数据
		foreach ($advs['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$categories_slugs = array_merge($categories_slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$categories_slugs = array_unique($categories_slugs);
			$selectFields = $inwhereSelect["category"];
            $advs["category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($categories_slugs, $selectFields);
            $advs["category_data"] = array_values($advs["category"]);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$advs['_sql'] = $qb->getSql();
			$advs['query'] = $query;
		}

		return $advs;
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
				$select[$idx] = "adv." .$select[$idx];
				continue;
			}
			
			// 连接category (category as category )
			if ( strpos( $fd, "category." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "slug";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "adv.categories");
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
			"adv_id",  // 广告ID
			"adv_slug",  // 广告别名
			"categories",  // 所属栏目
			"name",  // 名称
			"intro",  // 文案
			"link",  // 链接
			"images",  // 广告图片(多图)
			"cover",  // 封面图片
			"terms",  // 服务协议
			"size",  // 尺寸
			"position_name",  // 位置名称
			"position_no",  // 位置编号
			"expired",  // 有效期
			"pageview",  // 点击量
			"status",  // 状态
			"paystatus",  // 支付状态
			"price",  // 单价
			"priority",  // 优先级
			"user",  // 操作者
			"keyword",  // 关键词
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>