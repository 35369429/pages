<?php
/**
 * Class Special 
 * 专栏数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-09 14:01:53
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                      
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Special extends Model {


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
	 * 专栏数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('special'); // 数据表名称 xpmsns_pages_special
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

		// 专栏ID
		$this->putColumn( 'special_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 专栏类型
		$this->putColumn( 'type', $this->type("string", ["length"=>128, "index"=>true, "default"=>"expert", "null"=>false]));
		// 专栏名称
		$this->putColumn( 'name', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 专栏地址
		$this->putColumn( 'path', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 专栏LOGO
		$this->putColumn( 'logo', $this->type("text", ["json"=>true, "null"=>true]));
		// 内容类目
		$this->putColumn( 'category_ids', $this->type("text", ["json"=>true, "null"=>true]));
		// 推荐内容
		$this->putColumn( 'recommend_ids', $this->type("text", ["json"=>true, "null"=>true]));
		// 简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>400, "null"=>true]));
		// 参数
		$this->putColumn( 'param', $this->type("string", ["length"=>128, "index"=>true, "null"=>false]));
		// 申请材料
		$this->putColumn( 'docs', $this->type("text", ["json"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>20, "index"=>true, "default"=>"on", "null"=>false]));
		// 消息
		$this->putColumn( 'message', $this->type("string", ["length"=>600, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 
		// 格式化: 专栏LOGO
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('logo', $rs ) ) {
            array_push($fileFields, 'logo');
		}
		// 格式化: 申请材料
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('docs', $rs ) ) {
            array_push($fileFields, 'docs');
		}

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"on" => [
		  			"value" => "on",
		  			"name" => "开启",
		  			"style" => "success"
		  		],
		  		"off" => [
		  			"value" => "off",
		  			"name" => "关闭",
		  			"style" => "danger"
		  		],
		  		"pending" => [
		  			"value" => "pending",
		  			"name" => "审核中",
		  			"style" => "warning"
		  		],
		  		"failure" => [
		  			"value" => "failure",
		  			"name" => "不通过",
		  			"style" => "danger"
		  		],
		  		"reset" => [
		  			"value" => "reset",
		  			"name" => "重新开通",
		  			"style" => "primary"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

		// 格式化: 专栏类型
		// 返回值: "_type_types" 所有状态表述, "_type_name" 状态名称,  "_type" 当前状态表述, "type" 当前状态数值
		if ( array_key_exists('type', $rs ) && !empty($rs['type']) ) {
			$rs["_type_types"] = [
		  		"special" => [
		  			"value" => "special",
		  			"name" => "内容专题",
		  			"style" => "primary"
		  		],
		  		"expert" => [
		  			"value" => "expert",
		  			"name" => "业界专家",
		  			"style" => "success"
		  		],
		  		"wemedia" => [
		  			"value" => "wemedia",
		  			"name" => "自媒体",
		  			"style" => "success"
		  		],
		  		"official" => [
		  			"value" => "official",
		  			"name" => "官方机构",
		  			"style" => "success"
		  		],
			];
			$rs["_type_name"] = "type";
			$rs["_type"] = $rs["_type_types"][$rs["type"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按专栏ID查询一条专栏记录
	 * @param string $special_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["special_id"],  // 专栏ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["type"],  // 专栏类型 
	 *          	  $rs["name"],  // 专栏名称 
	 *          	  $rs["path"],  // 专栏地址 
	 *          	  $rs["logo"],  // 专栏LOGO 
	 *          	  $rs["category_ids"],  // 内容类目 
	 *                $rs["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *          	  $rs["recommend_ids"],  // 推荐内容 
	 *                $rs["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["docs"],  // 申请材料 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["message"],  // 消息 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$category_ids[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$category_ids[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$category_ids[n]]["project"], // category.project
	 *                $rs["_map_category"][$category_ids[n]]["page"], // category.page
	 *                $rs["_map_category"][$category_ids[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$category_ids[n]]["name"], // category.name
	 *                $rs["_map_category"][$category_ids[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$category_ids[n]]["link"], // category.link
	 *                $rs["_map_category"][$category_ids[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$category_ids[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$category_ids[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$category_ids[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$category_ids[n]]["param"], // category.param
	 *                $rs["_map_category"][$category_ids[n]]["status"], // category.status
	 *                $rs["_map_category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$category_ids[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$category_ids[n]]["isblank"], // category.isblank
	 *                $rs["_map_recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *                $rs["_map_recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *                $rs["_map_recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *                $rs["_map_recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *                $rs["_map_recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *                $rs["_map_recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *                $rs["_map_recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *                $rs["_map_recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *                $rs["_map_recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *                $rs["_map_recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *                $rs["_map_recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *                $rs["_map_recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *                $rs["_map_recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *                $rs["_map_recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *                $rs["_map_recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *                $rs["_map_recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *                $rs["_map_recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *                $rs["_map_recommend"][$recommend_ids[n]]["series"], // recommend.series
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 */
	public function getBySpecialId( $special_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->where('special.special_id', '=', $special_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$rs["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 
		return $rs;
	}

		

	/**
	 * 按专栏ID查询一组专栏记录
	 * @param array   $special_ids 唯一主键数组 ["$special_id1","$special_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 专栏记录MAP {"special_id1":{"key":"value",...}...}
	 */
	public function getInBySpecialId($special_ids, $select=["special.logo","special.path","special.name","special.type","category.name","special.name","user.nickname","special.created_at","special.updated_at","special.status"], $order=["special.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->whereIn('special.special_id', $special_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['special_id']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$map["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 

		return $map;
	}


	/**
	 * 按专栏ID保存专栏记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySpecialId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("special_id", $data, ["special_id", "user_id", "name", "path"], ['_id', 'special_id']);
		return $this->getBySpecialId( $rs['special_id'], $select );
	}
	
	/**
	 * 按用户ID查询一条专栏记录
	 * @param string $user_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["special_id"],  // 专栏ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["type"],  // 专栏类型 
	 *          	  $rs["name"],  // 专栏名称 
	 *          	  $rs["path"],  // 专栏地址 
	 *          	  $rs["logo"],  // 专栏LOGO 
	 *          	  $rs["category_ids"],  // 内容类目 
	 *                $rs["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *          	  $rs["recommend_ids"],  // 推荐内容 
	 *                $rs["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["docs"],  // 申请材料 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["message"],  // 消息 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$category_ids[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$category_ids[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$category_ids[n]]["project"], // category.project
	 *                $rs["_map_category"][$category_ids[n]]["page"], // category.page
	 *                $rs["_map_category"][$category_ids[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$category_ids[n]]["name"], // category.name
	 *                $rs["_map_category"][$category_ids[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$category_ids[n]]["link"], // category.link
	 *                $rs["_map_category"][$category_ids[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$category_ids[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$category_ids[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$category_ids[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$category_ids[n]]["param"], // category.param
	 *                $rs["_map_category"][$category_ids[n]]["status"], // category.status
	 *                $rs["_map_category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$category_ids[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$category_ids[n]]["isblank"], // category.isblank
	 *                $rs["_map_recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *                $rs["_map_recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *                $rs["_map_recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *                $rs["_map_recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *                $rs["_map_recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *                $rs["_map_recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *                $rs["_map_recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *                $rs["_map_recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *                $rs["_map_recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *                $rs["_map_recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *                $rs["_map_recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *                $rs["_map_recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *                $rs["_map_recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *                $rs["_map_recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *                $rs["_map_recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *                $rs["_map_recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *                $rs["_map_recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *                $rs["_map_recommend"][$recommend_ids[n]]["series"], // recommend.series
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 */
	public function getByUserId( $user_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->where('special.user_id', '=', $user_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$rs["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 
		return $rs;
	}

	

	/**
	 * 按用户ID查询一组专栏记录
	 * @param array   $user_ids 唯一主键数组 ["$user_id1","$user_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 专栏记录MAP {"user_id1":{"key":"value",...}...}
	 */
	public function getInByUserId($user_ids, $select=["special.logo","special.path","special.name","special.type","category.name","special.name","user.nickname","special.created_at","special.updated_at","special.status"], $order=["special.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->whereIn('special.user_id', $user_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['user_id']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$map["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 

		return $map;
	}


	/**
	 * 按用户ID保存专栏记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByUserId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("user_id", $data, ["special_id", "user_id", "name", "path"], ['_id', 'special_id']);
		return $this->getBySpecialId( $rs['special_id'], $select );
	}
	
	/**
	 * 按专栏名称查询一条专栏记录
	 * @param string $name 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["special_id"],  // 专栏ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["type"],  // 专栏类型 
	 *          	  $rs["name"],  // 专栏名称 
	 *          	  $rs["path"],  // 专栏地址 
	 *          	  $rs["logo"],  // 专栏LOGO 
	 *          	  $rs["category_ids"],  // 内容类目 
	 *                $rs["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *          	  $rs["recommend_ids"],  // 推荐内容 
	 *                $rs["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["docs"],  // 申请材料 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["message"],  // 消息 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$category_ids[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$category_ids[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$category_ids[n]]["project"], // category.project
	 *                $rs["_map_category"][$category_ids[n]]["page"], // category.page
	 *                $rs["_map_category"][$category_ids[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$category_ids[n]]["name"], // category.name
	 *                $rs["_map_category"][$category_ids[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$category_ids[n]]["link"], // category.link
	 *                $rs["_map_category"][$category_ids[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$category_ids[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$category_ids[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$category_ids[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$category_ids[n]]["param"], // category.param
	 *                $rs["_map_category"][$category_ids[n]]["status"], // category.status
	 *                $rs["_map_category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$category_ids[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$category_ids[n]]["isblank"], // category.isblank
	 *                $rs["_map_recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *                $rs["_map_recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *                $rs["_map_recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *                $rs["_map_recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *                $rs["_map_recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *                $rs["_map_recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *                $rs["_map_recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *                $rs["_map_recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *                $rs["_map_recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *                $rs["_map_recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *                $rs["_map_recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *                $rs["_map_recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *                $rs["_map_recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *                $rs["_map_recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *                $rs["_map_recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *                $rs["_map_recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *                $rs["_map_recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *                $rs["_map_recommend"][$recommend_ids[n]]["series"], // recommend.series
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 */
	public function getByName( $name, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->where('special.name', '=', $name );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$rs["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 
		return $rs;
	}

	

	/**
	 * 按专栏名称查询一组专栏记录
	 * @param array   $names 唯一主键数组 ["$name1","$name2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 专栏记录MAP {"name1":{"key":"value",...}...}
	 */
	public function getInByName($names, $select=["special.logo","special.path","special.name","special.type","category.name","special.name","user.nickname","special.created_at","special.updated_at","special.status"], $order=["special.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->whereIn('special.name', $names);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['name']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$map["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 

		return $map;
	}


	/**
	 * 按专栏名称保存专栏记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByName( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("name", $data, ["special_id", "user_id", "name", "path"], ['_id', 'special_id']);
		return $this->getBySpecialId( $rs['special_id'], $select );
	}
	
	/**
	 * 按专栏地址查询一条专栏记录
	 * @param string $path 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["special_id"],  // 专栏ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["type"],  // 专栏类型 
	 *          	  $rs["name"],  // 专栏名称 
	 *          	  $rs["path"],  // 专栏地址 
	 *          	  $rs["logo"],  // 专栏LOGO 
	 *          	  $rs["category_ids"],  // 内容类目 
	 *                $rs["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *          	  $rs["recommend_ids"],  // 推荐内容 
	 *                $rs["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["docs"],  // 申请材料 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["message"],  // 消息 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$category_ids[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$category_ids[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$category_ids[n]]["project"], // category.project
	 *                $rs["_map_category"][$category_ids[n]]["page"], // category.page
	 *                $rs["_map_category"][$category_ids[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$category_ids[n]]["name"], // category.name
	 *                $rs["_map_category"][$category_ids[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$category_ids[n]]["link"], // category.link
	 *                $rs["_map_category"][$category_ids[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$category_ids[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$category_ids[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$category_ids[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$category_ids[n]]["param"], // category.param
	 *                $rs["_map_category"][$category_ids[n]]["status"], // category.status
	 *                $rs["_map_category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$category_ids[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$category_ids[n]]["isblank"], // category.isblank
	 *                $rs["_map_recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *                $rs["_map_recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *                $rs["_map_recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *                $rs["_map_recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *                $rs["_map_recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *                $rs["_map_recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *                $rs["_map_recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *                $rs["_map_recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *                $rs["_map_recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *                $rs["_map_recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *                $rs["_map_recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *                $rs["_map_recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *                $rs["_map_recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *                $rs["_map_recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *                $rs["_map_recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *                $rs["_map_recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *                $rs["_map_recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *                $rs["_map_recommend"][$recommend_ids[n]]["series"], // recommend.series
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 */
	public function getByPath( $path, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->where('special.path', '=', $path );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$rs["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 
		return $rs;
	}

	

	/**
	 * 按专栏地址查询一组专栏记录
	 * @param array   $paths 唯一主键数组 ["$path1","$path2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 专栏记录MAP {"path1":{"key":"value",...}...}
	 */
	public function getInByPath($paths, $select=["special.logo","special.path","special.name","special.type","category.name","special.name","user.nickname","special.created_at","special.updated_at","special.status"], $order=["special.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户
		$qb->whereIn('special.path', $paths);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['path']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$map["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 

		return $map;
	}


	/**
	 * 按专栏地址保存专栏记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByPath( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("path", $data, ["special_id", "user_id", "name", "path"], ['_id', 'special_id']);
		return $this->getBySpecialId( $rs['special_id'], $select );
	}

	/**
	 * 根据专栏ID上传专栏LOGO。
	 * @param string $special_id 专栏ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadLogoBySpecialId($special_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('special_id', $special_id, ["logo"]);
		$paths = empty($rs["logo"]) ? [] : $rs["logo"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('special_id', ["special_id"=>$special_id, "logo"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据专栏ID上传申请材料。
	 * @param string $special_id 专栏ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadDocsBySpecialId($special_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('special_id', $special_id, ["docs"]);
		$paths = empty($rs["docs"]) ? [] : $rs["docs"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('special_id', ["special_id"=>$special_id, "docs"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据用户ID上传专栏LOGO。
	 * @param string $user_id 用户ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadLogoByUserId($user_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('user_id', $user_id, ["logo"]);
		$paths = empty($rs["logo"]) ? [] : $rs["logo"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('user_id', ["user_id"=>$user_id, "logo"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据用户ID上传申请材料。
	 * @param string $user_id 用户ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadDocsByUserId($user_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('user_id', $user_id, ["docs"]);
		$paths = empty($rs["docs"]) ? [] : $rs["docs"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('user_id', ["user_id"=>$user_id, "docs"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据专栏名称上传专栏LOGO。
	 * @param string $name 专栏名称
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadLogoByName($name, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('name', $name, ["logo"]);
		$paths = empty($rs["logo"]) ? [] : $rs["logo"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('name', ["name"=>$name, "logo"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据专栏名称上传申请材料。
	 * @param string $name 专栏名称
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadDocsByName($name, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('name', $name, ["docs"]);
		$paths = empty($rs["docs"]) ? [] : $rs["docs"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('name', ["name"=>$name, "docs"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据专栏地址上传专栏LOGO。
	 * @param string $path 专栏地址
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadLogoByPath($path, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('path', $path, ["logo"]);
		$paths = empty($rs["logo"]) ? [] : $rs["logo"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('path', ["path"=>$path, "logo"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据专栏地址上传申请材料。
	 * @param string $path 专栏地址
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadDocsByPath($path, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('path', $path, ["docs"]);
		$paths = empty($rs["docs"]) ? [] : $rs["docs"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('path', ["path"=>$path, "docs"=>$paths] );
		}

		return $fs;
	}


	/**
	 * 添加专栏记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["special_id"]) ) { 
			$data["special_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排专栏记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 专栏记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["special.logo","special.path","special.name","special.type","category.name","special.name","user.nickname","special.created_at","special.updated_at","special.status"], $order=["special.created_at"=>"asc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户


		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
 		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$data["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 
		return $data;
	
	}


	/**
	 * 按条件检索专栏记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["special.logo","special.path","special.name","special.type","category.name","special.name","user.nickname","special.created_at","special.updated_at","special.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["special_id"] 按专栏ID查询 ( = )
	 *			      $query["special_id"] 按专栏IDS查询 ( IN )
	 *			      $query["param"] 按参数查询 ( = )
	 *			      $query["path"] 按地址查询 ( = )
	 *			      $query["uname"] 按用户查询 ( LIKE )
	 *			      $query["name"] 按名称查询 ( LIKE )
	 *			      $query["type"] 按类型查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
	 *           
	 * @return array 专栏记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["special_id"],  // 专栏ID 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["type"],  // 专栏类型 
	 *               	["name"],  // 专栏名称 
	 *               	["path"],  // 专栏地址 
	 *               	["logo"],  // 专栏LOGO 
	 *               	["category_ids"],  // 内容类目 
	 *               	["category"][$category_ids[n]]["category_id"], // category.category_id
	 *               	["recommend_ids"],  // 推荐内容 
	 *               	["recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *               	["summary"],  // 简介 
	 *               	["param"],  // 参数 
	 *               	["docs"],  // 申请材料 
	 *               	["status"],  // 状态 
	 *               	["message"],  // 消息 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["category"][$category_ids[n]]["created_at"], // category.created_at
	 *               	["category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *               	["category"][$category_ids[n]]["slug"], // category.slug
	 *               	["category"][$category_ids[n]]["project"], // category.project
	 *               	["category"][$category_ids[n]]["page"], // category.page
	 *               	["category"][$category_ids[n]]["wechat"], // category.wechat
	 *               	["category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *               	["category"][$category_ids[n]]["name"], // category.name
	 *               	["category"][$category_ids[n]]["fullname"], // category.fullname
	 *               	["category"][$category_ids[n]]["link"], // category.link
	 *               	["category"][$category_ids[n]]["root_id"], // category.root_id
	 *               	["category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *               	["category"][$category_ids[n]]["priority"], // category.priority
	 *               	["category"][$category_ids[n]]["hidden"], // category.hidden
	 *               	["category"][$category_ids[n]]["isnav"], // category.isnav
	 *               	["category"][$category_ids[n]]["param"], // category.param
	 *               	["category"][$category_ids[n]]["status"], // category.status
	 *               	["category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *               	["category"][$category_ids[n]]["highlight"], // category.highlight
	 *               	["category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *               	["category"][$category_ids[n]]["isblank"], // category.isblank
	 *               	["recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *               	["recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *               	["recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *               	["recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *               	["recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *               	["recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *               	["recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *               	["recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *               	["recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *               	["recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *               	["recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *               	["recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *               	["recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *               	["recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *               	["recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *               	["recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *               	["recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *               	["recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *               	["recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *               	["recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *               	["recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *               	["recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *               	["recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *               	["recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *               	["recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *               	["recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *               	["recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *               	["recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *               	["recommend"][$recommend_ids[n]]["series"], // recommend.series
	 *               	["user_created_at"], // user.created_at
	 *               	["user_updated_at"], // user.updated_at
	 *               	["user_group_id"], // user.group_id
	 *               	["user_name"], // user.name
	 *               	["user_idno"], // user.idno
	 *               	["user_idtype"], // user.idtype
	 *               	["user_iddoc"], // user.iddoc
	 *               	["user_nickname"], // user.nickname
	 *               	["user_sex"], // user.sex
	 *               	["user_city"], // user.city
	 *               	["user_province"], // user.province
	 *               	["user_country"], // user.country
	 *               	["user_headimgurl"], // user.headimgurl
	 *               	["user_language"], // user.language
	 *               	["user_birthday"], // user.birthday
	 *               	["user_bio"], // user.bio
	 *               	["user_bgimgurl"], // user.bgimgurl
	 *               	["user_mobile"], // user.mobile
	 *               	["user_mobile_nation"], // user.mobile_nation
	 *               	["user_mobile_full"], // user.mobile_full
	 *               	["user_email"], // user.email
	 *               	["user_contact_name"], // user.contact_name
	 *               	["user_contact_tel"], // user.contact_tel
	 *               	["user_title"], // user.title
	 *               	["user_company"], // user.company
	 *               	["user_zip"], // user.zip
	 *               	["user_address"], // user.address
	 *               	["user_remark"], // user.remark
	 *               	["user_tag"], // user.tag
	 *               	["user_user_verified"], // user.user_verified
	 *               	["user_name_verified"], // user.name_verified
	 *               	["user_verify"], // user.verify
	 *               	["user_verify_data"], // user.verify_data
	 *               	["user_mobile_verified"], // user.mobile_verified
	 *               	["user_email_verified"], // user.email_verified
	 *               	["user_extra"], // user.extra
	 *               	["user_password"], // user.password
	 *               	["user_pay_password"], // user.pay_password
	 *               	["user_status"], // user.status
	 *               	["user_inviter"], // user.inviter
	 *               	["user_follower_cnt"], // user.follower_cnt
	 *               	["user_following_cnt"], // user.following_cnt
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["special.logo","special.path","special.name","special.type","category.name","special.name","user.nickname","special.created_at","special.updated_at","special.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "special.special_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_special as special", "{none}")->query();
   		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "special.user_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("special.special_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("special.name","like", "%{$query['keyword']}%");
				$qb->orWhere("special.path","like", "%{$query['keyword']}%");
			});
		}


		// 按专栏ID查询 (=)  
		if ( array_key_exists("special_id", $query) &&!empty($query['special_id']) ) {
			$qb->where("special.special_id", '=', "{$query['special_id']}" );
		}
		  
		// 按专栏IDS查询 (IN)  
		if ( array_key_exists("special_id", $query) &&!empty($query['special_id']) ) {
			if ( is_string($query['special_id']) ) {
				$query['special_id'] = explode(',', $query['special_id']);
			}
			$qb->whereIn("special.special_id",  $query['special_id'] );
		}
		  
		// 按参数查询 (=)  
		if ( array_key_exists("param", $query) &&!empty($query['param']) ) {
			$qb->where("special.param", '=', "{$query['param']}" );
		}
		  
		// 按地址查询 (=)  
		if ( array_key_exists("path", $query) &&!empty($query['path']) ) {
			$qb->where("special.path", '=', "{$query['path']}" );
		}
		  
		// 按用户查询 (LIKE)  
		if ( array_key_exists("uname", $query) &&!empty($query['uname']) ) {
			$qb->where("special.", 'like', "%{$query['uname']}%" );
		}
		  
		// 按名称查询 (LIKE)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("special.name", 'like', "%{$query['name']}%" );
		}
		  
		// 按类型查询 (=)  
		if ( array_key_exists("type", $query) &&!empty($query['type']) ) {
			$qb->where("special.type", '=', "{$query['type']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("special.status", '=', "{$query['status']}" );
		}
		  

		// 按创建时间 ASC 排序
		if ( array_key_exists("orderby_created_at_asc", $query) &&!empty($query['orderby_created_at_asc']) ) {
			$qb->orderBy("special.created_at", "asc");
		}

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("special.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$specials = $qb->select( $select )->pgArray($perpage, ['special._id'], 'page', $page);

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
 		foreach ($specials['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
 		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$specials["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$specials["recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$specials['_sql'] = $qb->getSql();
			$specials['query'] = $query;
		}

		return $specials;
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
				$select[$idx] = "special." .$select[$idx];
				continue;
			}
			
			// 连接栏目 (category as category )
			if ( strpos( $fd, "category." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "category_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "special.category_ids");
				}
			}
			
			// 连接推荐 (recommend as recommend )
			if ( strpos( $fd, "recommend." ) === 0 || strpos("recommend.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["recommend"][] = trim($arr[1]);
				$inwhereSelect["recommend"][] = "recommend_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "special.recommend_ids");
				}
			}
			
			//  连接用户 (user as user )
			if ( trim($fd) == "user.*" || trim($fd) == "user.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\User", 'getFields') ) {
					$fields = \Xpmsns\User\Model\User::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "user.{$field} as user_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "special.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "user." ) === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "user.") === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
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
			"special_id",  // 专栏ID
			"user_id",  // 用户ID
			"type",  // 专栏类型
			"name",  // 专栏名称
			"path",  // 专栏地址
			"logo",  // 专栏LOGO
			"category_ids",  // 内容类目
			"recommend_ids",  // 推荐内容
			"summary",  // 简介
			"param",  // 参数
			"docs",  // 申请材料
			"status",  // 状态
			"message",  // 消息
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>