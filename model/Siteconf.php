<?php
/**
 * Class Siteconf 
 * 站点配置数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-28 20:36:49
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


class Siteconf extends Model {


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
	 * 站点配置数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
        $this->table('site'); // 数据表名称 xpmsns_pages_site
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_pages_site:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

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

		// 配制ID
		$this->putColumn( 'site_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 配制别名
		$this->putColumn( 'site_slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 呈现位置
		$this->putColumn( 'position', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 网站名称
		$this->putColumn( 'site_name', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 网站Slogen
		$this->putColumn( 'site_slogen', $this->type("string", ["length"=>400, "null"=>true]));
		// 网站图标
		$this->putColumn( 'icon', $this->type("string", ["length"=>200, "null"=>true]));
		// 浅色图标
		$this->putColumn( 'icon_light', $this->type("string", ["length"=>200, "null"=>true]));
		// 深色图标
		$this->putColumn( 'icon_dark', $this->type("string", ["length"=>200, "null"=>true]));
		// 网站简介
		$this->putColumn( 'site_intro', $this->type("string", ["length"=>400, "null"=>true]));
		// 官网地址
		$this->putColumn( 'site_homepage', $this->type("string", ["length"=>200, "null"=>true]));
		// 应用下载地址
		$this->putColumn( 'site_downloadpage', $this->type("string", ["length"=>200, "null"=>true]));
		// 网站LOGO
		$this->putColumn( 'site_logo', $this->type("string", ["length"=>200, "null"=>true]));
		// 浅色LOGO
		$this->putColumn( 'site_logo_light', $this->type("string", ["length"=>200, "null"=>true]));
		// 深色LOGO
		$this->putColumn( 'site_logo_dark', $this->type("string", ["length"=>200, "null"=>true]));
		// 网站备案号
		$this->putColumn( 'site_no', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 公司名称
		$this->putColumn( 'company', $this->type("string", ["length"=>400, "null"=>true]));
		// 公司地址
		$this->putColumn( 'address', $this->type("string", ["length"=>400, "null"=>true]));
		// 客服电话
		$this->putColumn( 'tel', $this->type("string", ["length"=>200, "null"=>true]));
		// 客服QQ
		$this->putColumn( 'qq', $this->type("string", ["length"=>200, "null"=>true]));
		// 客服邮箱
		$this->putColumn( 'email', $this->type("string", ["length"=>200, "null"=>true]));
		//  服务时间
		$this->putColumn( 'se_time', $this->type("string", ["length"=>200, "null"=>true]));
		// 合作联系人
		$this->putColumn( 'contact_name', $this->type("string", ["length"=>200, "null"=>true]));
		// 合作邮箱
		$this->putColumn( 'contact_email', $this->type("string", ["length"=>200, "null"=>true]));
		// 合作电话
		$this->putColumn( 'contact_tel', $this->type("string", ["length"=>200, "null"=>true]));
		// 合作QQ
		$this->putColumn( 'contact_qq', $this->type("string", ["length"=>200, "null"=>true]));
		// 小程序二维码
		$this->putColumn( 'qr_wxapp', $this->type("string", ["length"=>200, "null"=>true]));
		// 订阅号二维码
		$this->putColumn( 'qr_wxpub', $this->type("string", ["length"=>200, "null"=>true]));
		// 订阅号名称
		$this->putColumn( 'name_wxpub', $this->type("string", ["length"=>200, "null"=>true]));
		// 服务号二维码
		$this->putColumn( 'qr_wxse', $this->type("string", ["length"=>200, "null"=>true]));
		// 服务号名称
		$this->putColumn( 'name_wxse', $this->type("string", ["length"=>200, "null"=>true]));
		// 安卓应用二维码
		$this->putColumn( 'qr_android', $this->type("string", ["length"=>200, "null"=>true]));
		// 苹果应用二维码
		$this->putColumn( 'qr_ios', $this->type("string", ["length"=>200, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>50, "index"=>true, "default"=>"online", "null"=>true]));
		// 操作者
		$this->putColumn( 'user', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 自定义图片
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 自定义属性
		$this->putColumn( 'attrs', $this->type("text", ["json"=>true, "null"=>true]));
		// 自定义参数
		$this->putColumn( 'params', $this->type("text", ["json"=>true, "null"=>true]));
		// 头部脚本
		$this->putColumn( 'header', $this->type("text", ["null"=>true]));
		// 网站尾部
		$this->putColumn( 'footer', $this->type("text", ["null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 
		// 格式化: 网站图标
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('icon', $rs ) ) {
            array_push($fileFields, 'icon');
		}
		// 格式化: 浅色图标
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('icon_light', $rs ) ) {
            array_push($fileFields, 'icon_light');
		}
		// 格式化: 深色图标
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('icon_dark', $rs ) ) {
            array_push($fileFields, 'icon_dark');
		}
		// 格式化: 网站LOGO
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('site_logo', $rs ) ) {
            array_push($fileFields, 'site_logo');
		}
		// 格式化: 浅色LOGO
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('site_logo_light', $rs ) ) {
            array_push($fileFields, 'site_logo_light');
		}
		// 格式化: 深色LOGO
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('site_logo_dark', $rs ) ) {
            array_push($fileFields, 'site_logo_dark');
		}
		// 格式化: 小程序二维码
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_wxapp', $rs ) ) {
            array_push($fileFields, 'qr_wxapp');
		}
		// 格式化: 订阅号二维码
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_wxpub', $rs ) ) {
            array_push($fileFields, 'qr_wxpub');
		}
		// 格式化: 服务号二维码
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_wxse', $rs ) ) {
            array_push($fileFields, 'qr_wxse');
		}
		// 格式化: 安卓应用二维码
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_android', $rs ) ) {
            array_push($fileFields, 'qr_android');
		}
		// 格式化: 苹果应用二维码
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('qr_ios', $rs ) ) {
            array_push($fileFields, 'qr_ios');
		}
		// 格式化: 自定义图片
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
            array_push($fileFields, 'images');
		}

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

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
	 * 按配制ID查询一条站点配置记录
	 * @param string $site_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["site_id"],  // 配制ID 
	 *          	  $rs["site_slug"],  // 配制别名 
	 *          	  $rs["position"],  // 呈现位置 
	 *          	  $rs["site_name"],  // 网站名称 
	 *          	  $rs["site_slogen"],  // 网站Slogen 
	 *          	  $rs["icon"],  // 网站图标 
	 *          	  $rs["icon_light"],  // 浅色图标 
	 *          	  $rs["icon_dark"],  // 深色图标 
	 *          	  $rs["site_intro"],  // 网站简介 
	 *          	  $rs["site_homepage"],  // 官网地址 
	 *          	  $rs["site_downloadpage"],  // 应用下载地址 
	 *          	  $rs["site_logo"],  // 网站LOGO 
	 *          	  $rs["site_logo_light"],  // 浅色LOGO 
	 *          	  $rs["site_logo_dark"],  // 深色LOGO 
	 *          	  $rs["site_no"],  // 网站备案号 
	 *          	  $rs["company"],  // 公司名称 
	 *          	  $rs["address"],  // 公司地址 
	 *          	  $rs["tel"],  // 客服电话 
	 *          	  $rs["qq"],  // 客服QQ 
	 *          	  $rs["email"],  // 客服邮箱 
	 *          	  $rs["se_time"],  //  服务时间 
	 *          	  $rs["contact_name"],  // 合作联系人 
	 *          	  $rs["contact_email"],  // 合作邮箱 
	 *          	  $rs["contact_tel"],  // 合作电话 
	 *          	  $rs["contact_qq"],  // 合作QQ 
	 *          	  $rs["qr_wxapp"],  // 小程序二维码 
	 *          	  $rs["qr_wxpub"],  // 订阅号二维码 
	 *          	  $rs["name_wxpub"],  // 订阅号名称 
	 *          	  $rs["qr_wxse"],  // 服务号二维码 
	 *          	  $rs["name_wxse"],  // 服务号名称 
	 *          	  $rs["qr_android"],  // 安卓应用二维码 
	 *          	  $rs["qr_ios"],  // 苹果应用二维码 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["user"],  // 操作者 
	 *          	  $rs["images"],  // 自定义图片 
	 *          	  $rs["attrs"],  // 自定义属性 
	 *          	  $rs["params"],  // 自定义参数 
	 *          	  $rs["header"],  // 头部脚本 
	 *          	  $rs["footer"],  // 网站尾部 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getBySiteId( $site_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_pages_site as site", "{none}")->query();
		$qb->where('site.site_id', '=', $site_id );
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
	 * 按配制ID查询一组站点配置记录
	 * @param array   $site_ids 唯一主键数组 ["$site_id1","$site_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 站点配置记录MAP {"site_id1":{"key":"value",...}...}
	 */
	public function getInBySiteId($site_ids, $select=["site.site_id","site.site_slug","site.position","site.site_name","site.status"], $order=["site.updated_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('site_id', $site_ids);;
		
		// 排序
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
	public function saveBySiteId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("site_id", $data, ["site_id", "site_slug"], ['_id', 'site_id']);
		return $this->getBySiteId( $rs['site_id'], $select );
	}
	
	/**
	 * 按配制别名查询一条站点配置记录
	 * @param string $site_slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["site_id"],  // 配制ID 
	 *          	  $rs["site_slug"],  // 配制别名 
	 *          	  $rs["position"],  // 呈现位置 
	 *          	  $rs["site_name"],  // 网站名称 
	 *          	  $rs["site_slogen"],  // 网站Slogen 
	 *          	  $rs["icon"],  // 网站图标 
	 *          	  $rs["icon_light"],  // 浅色图标 
	 *          	  $rs["icon_dark"],  // 深色图标 
	 *          	  $rs["site_intro"],  // 网站简介 
	 *          	  $rs["site_homepage"],  // 官网地址 
	 *          	  $rs["site_downloadpage"],  // 应用下载地址 
	 *          	  $rs["site_logo"],  // 网站LOGO 
	 *          	  $rs["site_logo_light"],  // 浅色LOGO 
	 *          	  $rs["site_logo_dark"],  // 深色LOGO 
	 *          	  $rs["site_no"],  // 网站备案号 
	 *          	  $rs["company"],  // 公司名称 
	 *          	  $rs["address"],  // 公司地址 
	 *          	  $rs["tel"],  // 客服电话 
	 *          	  $rs["qq"],  // 客服QQ 
	 *          	  $rs["email"],  // 客服邮箱 
	 *          	  $rs["se_time"],  //  服务时间 
	 *          	  $rs["contact_name"],  // 合作联系人 
	 *          	  $rs["contact_email"],  // 合作邮箱 
	 *          	  $rs["contact_tel"],  // 合作电话 
	 *          	  $rs["contact_qq"],  // 合作QQ 
	 *          	  $rs["qr_wxapp"],  // 小程序二维码 
	 *          	  $rs["qr_wxpub"],  // 订阅号二维码 
	 *          	  $rs["name_wxpub"],  // 订阅号名称 
	 *          	  $rs["qr_wxse"],  // 服务号二维码 
	 *          	  $rs["name_wxse"],  // 服务号名称 
	 *          	  $rs["qr_android"],  // 安卓应用二维码 
	 *          	  $rs["qr_ios"],  // 苹果应用二维码 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["user"],  // 操作者 
	 *          	  $rs["images"],  // 自定义图片 
	 *          	  $rs["attrs"],  // 自定义属性 
	 *          	  $rs["params"],  // 自定义参数 
	 *          	  $rs["header"],  // 头部脚本 
	 *          	  $rs["footer"],  // 网站尾部 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getBySiteSlug( $site_slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_pages_site as site", "{none}")->query();
		$qb->where('site.site_slug', '=', $site_slug );
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
	 * 按配制别名查询一组站点配置记录
	 * @param array   $site_slugs 唯一主键数组 ["$site_slug1","$site_slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 站点配置记录MAP {"site_slug1":{"key":"value",...}...}
	 */
	public function getInBySiteSlug($site_slugs, $select=["site.site_id","site.site_slug","site.position","site.site_name","site.status"], $order=["site.updated_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('site_slug', $site_slugs);;
		
		// 排序
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
	public function saveBySiteSlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("site_slug", $data, ["site_id", "site_slug"], ['_id', 'site_id']);
		return $this->getBySiteId( $rs['site_id'], $select );
	}

	/**
	 * 根据配制ID上传网站图标。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIconBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "icon"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传浅色图标。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIconlightBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "icon_light"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传深色图标。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIcondarkBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "icon_dark"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传网站LOGO。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSitelogoBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "site_logo"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传浅色LOGO。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSitelogolightBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "site_logo_light"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传深色LOGO。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSitelogodarkBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "site_logo_dark"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传小程序二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrwxappBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "qr_wxapp"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传订阅号二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrwxpubBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "qr_wxpub"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传服务号二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrwxseBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "qr_wxse"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传安卓应用二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrandroidBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "qr_android"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传苹果应用二维码。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQriosBySiteId($site_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "qr_ios"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制ID上传自定义图片。
	 * @param string $site_id 配制ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesBySiteId($site_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('site_id', $site_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('site_id', ["site_id"=>$site_id, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据配制别名上传网站图标。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIconBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "icon"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传浅色图标。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIconlightBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "icon_light"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传深色图标。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIcondarkBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "icon_dark"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传网站LOGO。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSitelogoBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "site_logo"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传浅色LOGO。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSitelogolightBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "site_logo_light"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传深色LOGO。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadSitelogodarkBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "site_logo_dark"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传小程序二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrwxappBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_wxapp"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传订阅号二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrwxpubBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_wxpub"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传服务号二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrwxseBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_wxse"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传安卓应用二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQrandroidBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_android"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传苹果应用二维码。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadQriosBySiteSlug($site_slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "qr_ios"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据配制别名上传自定义图片。
	 * @param string $site_slug 配制别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesBySiteSlug($site_slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('site_slug', $site_slug, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('site_slug', ["site_slug"=>$site_slug, "images"=>$paths] );
		}

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
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 站点配置记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["site.site_id","site.site_slug","site.position","site.site_name","site.status"], $order=["site.updated_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();


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
	 * 按条件检索站点配置记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["site.site_id","site.site_slug","site.position","site.site_name","site.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["site_id"] 按配制ID查询 ( = )
	 *			      $query["site_slug"] 按配制别名查询 ( = )
	 *			      $query["site_name"] 按网站名称查询 ( LIKE )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["position"] 按呈现位置查询 ( = )
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *           
	 * @return array 站点配置记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["site_id"],  // 配制ID 
	 *               	["site_slug"],  // 配制别名 
	 *               	["position"],  // 呈现位置 
	 *               	["site_name"],  // 网站名称 
	 *               	["site_slogen"],  // 网站Slogen 
	 *               	["icon"],  // 网站图标 
	 *               	["icon_light"],  // 浅色图标 
	 *               	["icon_dark"],  // 深色图标 
	 *               	["site_intro"],  // 网站简介 
	 *               	["site_homepage"],  // 官网地址 
	 *               	["site_downloadpage"],  // 应用下载地址 
	 *               	["site_logo"],  // 网站LOGO 
	 *               	["site_logo_light"],  // 浅色LOGO 
	 *               	["site_logo_dark"],  // 深色LOGO 
	 *               	["site_no"],  // 网站备案号 
	 *               	["company"],  // 公司名称 
	 *               	["address"],  // 公司地址 
	 *               	["tel"],  // 客服电话 
	 *               	["qq"],  // 客服QQ 
	 *               	["email"],  // 客服邮箱 
	 *               	["se_time"],  //  服务时间 
	 *               	["contact_name"],  // 合作联系人 
	 *               	["contact_email"],  // 合作邮箱 
	 *               	["contact_tel"],  // 合作电话 
	 *               	["contact_qq"],  // 合作QQ 
	 *               	["qr_wxapp"],  // 小程序二维码 
	 *               	["qr_wxpub"],  // 订阅号二维码 
	 *               	["name_wxpub"],  // 订阅号名称 
	 *               	["qr_wxse"],  // 服务号二维码 
	 *               	["name_wxse"],  // 服务号名称 
	 *               	["qr_android"],  // 安卓应用二维码 
	 *               	["qr_ios"],  // 苹果应用二维码 
	 *               	["status"],  // 状态 
	 *               	["user"],  // 操作者 
	 *               	["images"],  // 自定义图片 
	 *               	["attrs"],  // 自定义属性 
	 *               	["params"],  // 自定义参数 
	 *               	["header"],  // 头部脚本 
	 *               	["footer"],  // 网站尾部 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["site.site_id","site.site_slug","site.position","site.site_name","site.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "site.site_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("site.site_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("site.site_slug","like", "%{$query['keyword']}%");
				$qb->orWhere("site.position","like", "%{$query['keyword']}%");
				$qb->orWhere("site.site_name","like", "%{$query['keyword']}%");
			});
		}


		// 按配制ID查询 (=)  
		if ( array_key_exists("site_id", $query) &&!empty($query['site_id']) ) {
			$qb->where("site.site_id", '=', "{$query['site_id']}" );
		}
		  
		// 按配制别名查询 (=)  
		if ( array_key_exists("site_slug", $query) &&!empty($query['site_slug']) ) {
			$qb->where("site.site_slug", '=', "{$query['site_slug']}" );
		}
		  
		// 按网站名称查询 (LIKE)  
		if ( array_key_exists("site_name", $query) &&!empty($query['site_name']) ) {
			$qb->where("site.site_name", 'like', "%{$query['site_name']}%" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("site.status", '=', "{$query['status']}" );
		}
		  
		// 按呈现位置查询 (=)  
		if ( array_key_exists("position", $query) &&!empty($query['position']) ) {
			$qb->where("site.position", '=', "{$query['position']}" );
		}
		  

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("site.updated_at", "desc");
		}

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("site.created_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$siteconfs = $qb->select( $select )->pgArray($perpage, ['site._id'], 'page', $page);

		foreach ($siteconfs['data'] as & $rs ) {
			$this->format($rs);
			
		}

	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$siteconfs['_sql'] = $qb->getSql();
			$siteconfs['query'] = $query;
		}

		return $siteconfs;
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
				$select[$idx] = "site." .$select[$idx];
				continue;
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
			"site_id",  // 配制ID
			"site_slug",  // 配制别名
			"position",  // 呈现位置
			"site_name",  // 网站名称
			"site_slogen",  // 网站Slogen
			"icon",  // 网站图标
			"icon_light",  // 浅色图标
			"icon_dark",  // 深色图标
			"site_intro",  // 网站简介
			"site_homepage",  // 官网地址
			"site_downloadpage",  // 应用下载地址
			"site_logo",  // 网站LOGO
			"site_logo_light",  // 浅色LOGO
			"site_logo_dark",  // 深色LOGO
			"site_no",  // 网站备案号
			"company",  // 公司名称
			"address",  // 公司地址
			"tel",  // 客服电话
			"qq",  // 客服QQ
			"email",  // 客服邮箱
			"se_time",  //  服务时间
			"contact_name",  // 合作联系人
			"contact_email",  // 合作邮箱
			"contact_tel",  // 合作电话
			"contact_qq",  // 合作QQ
			"qr_wxapp",  // 小程序二维码
			"qr_wxpub",  // 订阅号二维码
			"name_wxpub",  // 订阅号名称
			"qr_wxse",  // 服务号二维码
			"name_wxse",  // 服务号名称
			"qr_android",  // 安卓应用二维码
			"qr_ios",  // 苹果应用二维码
			"status",  // 状态
			"user",  // 操作者
			"images",  // 自定义图片
			"attrs",  // 自定义属性
			"params",  // 自定义参数
			"header",  // 头部脚本
			"footer",  // 网站尾部
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>