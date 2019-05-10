<?php
/**
 * Class Event 
 * 活动数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-05-10 11:02:54
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


class Event extends Model {


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
	 * 活动数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
        $this->table('event'); // 数据表名称 xpmsns_pages_event
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_pages_event:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

       
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN

    /**
     * 关联活动报名信息
     * @param array &$articles 文章信息
     * @param string $user_id 用户ID 
     * @return null
     */
    function withEnter( & $events, $user_id, $select=["userevent.event_user_id", "userevent.userevent_id","userevent.signin_at","userevent.status"]) {

        $event_ids = array_column( $events, "event_id");
        if ( empty( $event_ids) ) {
            return;
        }


        // 读取活动报名信息
        $user_event = new UserEvent();
        $event_user_ids = array_map(function($event_id) use( $user_id ){ return "{$event_id}_{$user_id}"; }, $event_ids);
        $user_events = $user_event->getInByEventUserId($event_user_ids, $select);
        foreach($events as & $event ) {
            $event_user_id = "{$event["event_id"]}_{$user_id}";
            $event["enter"] = $user_events[$event_user_id];
            
            if ( in_array($event["enter"]["status"], ["signin", "paid", "checkin"]) ) {
                $event["entered"] = true;
            }else {
                $event["entered"] = false;
            }

            if (is_null($event["enter"]) ){
                $event["enter"] = [];
            }
        }
    }
    
    

    /**
     * 活动报名
     * @param string $event_id 活动ID
     * @param string $user_id 用户ID
     * @return [event_id, user_cnt, quota, deadline]
     */
    function enter($event_id, $user_id ) {

        // 校验数据
        $event = $this->getByEventId($event_id, "event_id,user_cnt,quota,deadline");

        // 校验 deadline
        $deadline = strtotime( $event["deadline"] );
        if ( $deadline - time() <= 0 ) {
            throw new Excp("活动报名已截止", 403, ["deadline"=>$event["deadline"], "event_id"=>$event_id]);
        }

        $user_event = new UserEvent();

        // 校验名额是否有限
        $quota = intval( $event["quota"] ) ;
        if ( $quota > 0 ) {
            $user_cnt = $user_event->query()
                              ->where("event_id", "=", $event_id)
                              ->where("status", "<>", "cancel")
                              ->count("event_id");
            
            // 校验名额
            if ( $user_cnt >= $quota ) {
                throw new Excp("活动参加名额已满", 403, ["quota"=>$event["quota"], "event_id"=>$event_id]);
            }

            $event["user_cnt"] = $user_cnt;
        }


        // 报名
        try {
            $respone = $user_event->create([
                "event_id" => $event_id,
                "user_id" => $user_id,
                "status" =>  "signin",
                "signin_at" => date('Y-m-d H:i:s')
            ]);
        }catch( Excp $e ) {
            if ( $e->getCode() == 1062 ) {
                throw new Excp("请勿重复报名", 1062, ["user_id"=>$user_id, "event_id"=>$event_id]);
            }
            throw $e;
        }

        // 更新统计数据
        $user_cnt = $user_event->query()
            ->where("event_id", "=", $event_id)
            ->where("status", "<>", "cancel")
            ->count("event_id");

        return $this->saveByEventId([
            "event_id" => $event_id,
            "user_cnt" => $user_cnt,
        ], "event_id,user_cnt,quota,deadline");

    }



    /**
     * 取消活动报名
     * @param string $event_id 活动ID
     * @param string $user_id 用户ID
     * @return [event_id, user_cnt, quota, deadline]
     */
    function cancelEnter($event_id, $user_id ) {

        $user_event = new UserEvent();
        $respone = $user_event->remove("{$event_id}_{$user_id}", "event_user_id");
        if ( $respone === false ) {
            throw new Excp("取消报名失败", 500, ["user_id"=>$user_id, "event_id"=>$event_id]);
        }

        // 更新统计数据
        $user_cnt = $user_event->query()
            ->where("event_id", "=", $event_id)
            ->where("status", "<>", "cancel")
            ->count("event_id");

        return $this->saveByEventId([
            "event_id" => $event_id,
            "user_cnt" => $user_cnt,
        ], "event_id,user_cnt,quota,deadline");

    }


    /**
     * 读取活动已报名用户
     * @param string $event_id 活动ID
     * @param array $query 更多查询条件
     */
    function getEnteredUsers( $event_id, $query=[] ){

        $user = new \Xpmsns\User\Model\User;
        $user_event = new UserEvent();
        $query = array_merge( $query, ["event_id" => $event_id]);
        $response =  $user_event->search($query);

        // 处理用户信息
        foreach( $response["data"] as & $rs ){
            if ( is_string($rs["user_headimgurl"]) ) {
                $rs["user_headimgurl"] = json_decode($rs["user_headimgurl"], true);
            }
            $this->__fileFields( $rs, ["user_headimgurl"] );
        }

        return $response;
    }


    /**
     * 查询用已报名的活动列表
     * 
     */
    function getUserEvents( $user_id, $query=[] ) {
        
        $user_event = new UserEvent();
        $query["user_id"] = $user_id;

        $select = empty($query['select']) ? ["event.event_id","event.slug","event.title","event.cover","event.begin","event.end","event.type","event.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
        $qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
        $qb->leftJoin("xpmsns_pages_userevent as userevent", "userevent.event_id", "event.event_id");
        $qb->where("userevent.user_id","=", $user_id);

        // 按栏目查询 (LIKE-MULTIPLE)  
		if ( array_key_exists("categories", $query) &&!empty($query['categories']) ) {
            $query['categories'] = explode(',', $query['categories']);
            $qb->where(function ( $qb ) use($query) {
                foreach( $query['categories'] as $idx=>$val )  {
                    $val = trim($val);
                    if ( $idx == 0 ) {
                        $qb->where("event.categories", 'like', "%{$val}%" );
                    } else {
                        $qb->orWhere("event.categories", 'like', "%{$val}%");
                    }
                }
            });
		}
		  
		// 按系列查询 (LIKE-MULTIPLE)  
		if ( array_key_exists("series", $query) &&!empty($query['series']) ) {
            $query['series'] = explode(',', $query['series']);
            $qb->where(function ( $qb ) use($query) {
                foreach( $query['series'] as $idx=>$val )  {
                    $val = trim($val);
                    if ( $idx == 0 ) {
                        $qb->where("event.series", 'like', "%{$val}%" );
                    } else {
                        $qb->orWhere("event.series", 'like', "%{$val}%");
                    }
                }
            });
        }
        
        // 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("event.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("event.updated_at", "desc");
		}

		// 按name=publish_time DESC 排序
		if ( array_key_exists("orderby_publish_time_desc", $query) &&!empty($query['orderby_publish_time_desc']) ) {
			$qb->orderBy("event.publish_time", "desc");
		}

		// 按name=begin DESC 排序
		if ( array_key_exists("orderby_begin_desc", $query) &&!empty($query['orderby_begin_desc']) ) {
			$qb->orderBy("event.begin", "desc");
		}

		// 按name=end DESC 排序
		if ( array_key_exists("orderby_end_desc", $query) &&!empty($query['orderby_end_desc']) ) {
			$qb->orderBy("event.end", "desc");
		}

		// 按name=deadline DESC 排序
		if ( array_key_exists("orderby_deadline_desc", $query) &&!empty($query['orderby_deadline_desc']) ) {
			$qb->orderBy("event.deadline", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$events = $qb->select( $select )->pgArray($perpage, ['event._id'], 'page', $page);

 		$categories_slugs = []; // 读取 inWhere category 数据
 		$series_slugs = []; // 读取 inWhere series 数据
		foreach ($events['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$categories_slugs = array_merge($categories_slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
            $series_slugs = array_merge($series_slugs, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
            
            // 关联报名条件
            $rs["enter"] = [];
            $rs["entered"] = true;
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$categories_slugs = array_unique($categories_slugs);
			$selectFields = $inwhereSelect["category"];
            $events["category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($categories_slugs, $selectFields);
            $events["category_data"] = array_values($events["category"]);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySlug') ) {
			$series_slugs = array_unique($series_slugs);
			$selectFields = $inwhereSelect["series"];
            $events["series"] = (new \Xpmsns\Pages\Model\Series)->getInBySlug($series_slugs, $selectFields);
            $events["series_data"] = array_values($events["series"]);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$events['_sql'] = $qb->getSql();
			$events['query'] = $query;
        }
        
       

		return $events;
  
    }


    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 活动ID
		$this->putColumn( 'event_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 主题
		$this->putColumn( 'title', $this->type("string", ["length"=>200, "index"=>true, "null"=>false]));
		// 外部链接
		$this->putColumn( 'link', $this->type("string", ["length"=>200, "null"=>true]));
		// 栏目
		$this->putColumn( 'categories', $this->type("string", ["length"=>256, "index"=>true, "json"=>true, "null"=>true]));
		// 系列
		$this->putColumn( 'series', $this->type("string", ["length"=>256, "index"=>true, "json"=>true, "null"=>true]));
		// 类型
		$this->putColumn( 'type', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 标签
		$this->putColumn( 'tags', $this->type("text", ["null"=>true]));
		// 简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>200, "null"=>true]));
		// 封面
		$this->putColumn( 'cover', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 海报
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 开始时间
		$this->putColumn( 'begin', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 结束时间
		$this->putColumn( 'end', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 报名截止时间
		$this->putColumn( 'deadline', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 名额
		$this->putColumn( 'quota', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 流程设计
		$this->putColumn( 'process_setting', $this->type("text", ["json"=>true, "null"=>true]));
		// 当前进程
		$this->putColumn( 'process', $this->type("string", ["length"=>500, "index"=>true, "null"=>true]));
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
		// 奖金
		$this->putColumn( 'bonus', $this->type("integer", ["index"=>true, "null"=>true]));
		// 奖项
		$this->putColumn( 'prize', $this->type("text", ["json"=>true, "null"=>true]));
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
		// 活动总结
		$this->putColumn( 'report', $this->type("longText", ["null"=>true]));
		// 桌面代码
		$this->putColumn( 'desktop', $this->type("longText", ["null"=>true]));
		// 手机代码
		$this->putColumn( 'mobile', $this->type("longText", ["null"=>true]));
		// 小程序代码
		$this->putColumn( 'wxapp', $this->type("longText", ["json"=>true, "null"=>true]));
		// APP代码
		$this->putColumn( 'app', $this->type("longText", ["json"=>true, "null"=>true]));
		// 发布时间
		$this->putColumn( 'publish_time', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 浏览量
		$this->putColumn( 'view_cnt', $this->type("bigInteger", ["length"=>1, "index"=>true, "null"=>true]));
		// 报名人数
		$this->putColumn( 'user_cnt', $this->type("bigInteger", ["length"=>1, "index"=>true, "null"=>true]));
		// 点赞量
		$this->putColumn( 'like_cnt', $this->type("bigInteger", ["length"=>1, "index"=>true, "null"=>true]));
		// 同意量
		$this->putColumn( 'agree_cnt', $this->type("bigInteger", ["length"=>1, "index"=>true, "null"=>true]));
		// 讨厌量
		$this->putColumn( 'dislike_cnt', $this->type("bigInteger", ["length"=>1, "index"=>true, "null"=>true]));
		// 评论量
		$this->putColumn( 'comment_cnt', $this->type("bigInteger", ["length"=>1, "index"=>true, "null"=>true]));
		// 管理链接
		$this->putColumn( 'admin_link', $this->type("string", ["length"=>200, "null"=>true]));
		// 管理链接名称
		$this->putColumn( 'admin_name', $this->type("string", ["length"=>200, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>200, "index"=>true, "default"=>"open", "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 
		// 格式化: 封面
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('cover', $rs ) ) {
            array_push($fileFields, 'cover');
		}
		// 格式化: 海报
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
            array_push($fileFields, 'images');
		}
		// 格式化: 主办方
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('hosts', $rs ) ) {
            array_push($fileFields, 'hosts');
		}
		// 格式化: 承办方/组织者
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('organizers', $rs ) ) {
            array_push($fileFields, 'organizers');
		}
		// 格式化: 赞助商
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('sponsors', $rs ) ) {
            array_push($fileFields, 'sponsors');
		}
		// 格式化: 合作媒体
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('medias', $rs ) ) {
            array_push($fileFields, 'medias');
		}
		// 格式化: 嘉宾
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('speakers', $rs ) ) {
            array_push($fileFields, 'speakers');
		}

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"draft" => [
		  			"value" => "draft",
		  			"name" => "草稿",
		  			"style" => "danger"
		  		],
		  		"preparing" => [
		  			"value" => "preparing",
		  			"name" => "筹备中",
		  			"style" => "warning"
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

		// 格式化: 类型
		// 返回值: "_type_types" 所有状态表述, "_type_name" 状态名称,  "_type" 当前状态表述, "type" 当前状态数值
		if ( array_key_exists('type', $rs ) && !empty($rs['type']) ) {
			$rs["_type_types"] = [
		  		"offline" => [
		  			"value" => "offline",
		  			"name" => "线下",
		  			"style" => "primary"
		  		],
		  		"online" => [
		  			"value" => "online",
		  			"name" => "线上",
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
	 * 按活动ID查询一条活动记录
	 * @param string $event_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["event_id"],  // 活动ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["title"],  // 主题 
	 *          	  $rs["link"],  // 外部链接 
	 *          	  $rs["categories"],  // 栏目 
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *          	  $rs["series"],  // 系列 
	 *                $rs["_map_series"][$series[n]]["slug"], // series.slug
	 *          	  $rs["type"],  // 类型 
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["images"],  // 海报 
	 *          	  $rs["begin"],  // 开始时间 
	 *          	  $rs["end"],  // 结束时间 
	 *          	  $rs["deadline"],  // 报名截止时间 
	 *          	  $rs["quota"],  // 名额 
	 *          	  $rs["process_setting"],  // 流程设计 
	 *          	  $rs["process"],  // 当前进程 
	 *          	  $rs["area"],  // 国家/地区 
	 *          	  $rs["prov"],  // 省份 
	 *          	  $rs["city"],  // 城市 
	 *          	  $rs["town"],  // 区县 
	 *          	  $rs["location"],  // 地点 
	 *          	  $rs["price"],  // 费用 
	 *          	  $rs["bonus"],  // 奖金 
	 *          	  $rs["prize"],  // 奖项 
	 *          	  $rs["hosts"],  // 主办方 
	 *          	  $rs["organizers"],  // 承办方/组织者 
	 *          	  $rs["sponsors"],  // 赞助商 
	 *          	  $rs["medias"],  // 合作媒体 
	 *          	  $rs["speakers"],  // 嘉宾 
	 *          	  $rs["content"],  // 活动介绍 
	 *          	  $rs["report"],  // 活动总结 
	 *          	  $rs["desktop"],  // 桌面代码 
	 *          	  $rs["mobile"],  // 手机代码 
	 *          	  $rs["wxapp"],  // 小程序代码 
	 *          	  $rs["app"],  // APP代码 
	 *          	  $rs["publish_time"],  // 发布时间 
	 *          	  $rs["view_cnt"],  // 浏览量 
	 *          	  $rs["user_cnt"],  // 报名人数 
	 *          	  $rs["like_cnt"],  // 点赞量 
	 *          	  $rs["agree_cnt"],  // 同意量 
	 *          	  $rs["dislike_cnt"],  // 讨厌量 
	 *          	  $rs["comment_cnt"],  // 评论量 
	 *          	  $rs["admin_link"],  // 管理链接 
	 *          	  $rs["admin_name"],  // 管理链接名称 
	 *          	  $rs["status"],  // 状态 
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
	 *                $rs["_map_category"][$categories[n]]["ismobnav"], // category.ismobnav
	 *                $rs["_map_category"][$categories[n]]["iswxappnav"], // category.iswxappnav
	 *                $rs["_map_category"][$categories[n]]["isappnav"], // category.isappnav
	 *                $rs["_map_category"][$categories[n]]["outer_id"], // category.outer_id
	 *                $rs["_map_category"][$categories[n]]["origin"], // category.origin
	 *                $rs["_map_series"][$series[n]]["created_at"], // series.created_at
	 *                $rs["_map_series"][$series[n]]["updated_at"], // series.updated_at
	 *                $rs["_map_series"][$series[n]]["series_id"], // series.series_id
	 *                $rs["_map_series"][$series[n]]["name"], // series.name
	 *                $rs["_map_series"][$series[n]]["category_id"], // series.category_id
	 *                $rs["_map_series"][$series[n]]["summary"], // series.summary
	 *                $rs["_map_series"][$series[n]]["orderby"], // series.orderby
	 *                $rs["_map_series"][$series[n]]["param"], // series.param
	 *                $rs["_map_series"][$series[n]]["status"], // series.status
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
  		$qb->where('event.event_id', '=', $event_id );
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
 		$slugs = []; // 读取 inWhere series 数据
		$slugs = array_merge($slugs, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["series"];
			$rs["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySlug($slugs, $selectFields);
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
	public function getInByEventId($event_ids, $select=["event.event_id","event.slug","event.title","event.cover","event.begin","event.end","event.type","event.status","event.event_id"], $order=["event.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
  		$qb->whereIn('event.event_id', $event_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$slugs = []; // 读取 inWhere category 数据
 		$slugs = []; // 读取 inWhere series 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['event_id']] = $rs;
			
 			// for inWhere category
			$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$slugs = array_merge($slugs, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["series"];
			$map["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySlug($slugs, $selectFields);
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
	 * 按别名查询一条活动记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["event_id"],  // 活动ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["title"],  // 主题 
	 *          	  $rs["link"],  // 外部链接 
	 *          	  $rs["categories"],  // 栏目 
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *          	  $rs["series"],  // 系列 
	 *                $rs["_map_series"][$series[n]]["slug"], // series.slug
	 *          	  $rs["type"],  // 类型 
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["images"],  // 海报 
	 *          	  $rs["begin"],  // 开始时间 
	 *          	  $rs["end"],  // 结束时间 
	 *          	  $rs["deadline"],  // 报名截止时间 
	 *          	  $rs["quota"],  // 名额 
	 *          	  $rs["process_setting"],  // 流程设计 
	 *          	  $rs["process"],  // 当前进程 
	 *          	  $rs["area"],  // 国家/地区 
	 *          	  $rs["prov"],  // 省份 
	 *          	  $rs["city"],  // 城市 
	 *          	  $rs["town"],  // 区县 
	 *          	  $rs["location"],  // 地点 
	 *          	  $rs["price"],  // 费用 
	 *          	  $rs["bonus"],  // 奖金 
	 *          	  $rs["prize"],  // 奖项 
	 *          	  $rs["hosts"],  // 主办方 
	 *          	  $rs["organizers"],  // 承办方/组织者 
	 *          	  $rs["sponsors"],  // 赞助商 
	 *          	  $rs["medias"],  // 合作媒体 
	 *          	  $rs["speakers"],  // 嘉宾 
	 *          	  $rs["content"],  // 活动介绍 
	 *          	  $rs["report"],  // 活动总结 
	 *          	  $rs["desktop"],  // 桌面代码 
	 *          	  $rs["mobile"],  // 手机代码 
	 *          	  $rs["wxapp"],  // 小程序代码 
	 *          	  $rs["app"],  // APP代码 
	 *          	  $rs["publish_time"],  // 发布时间 
	 *          	  $rs["view_cnt"],  // 浏览量 
	 *          	  $rs["user_cnt"],  // 报名人数 
	 *          	  $rs["like_cnt"],  // 点赞量 
	 *          	  $rs["agree_cnt"],  // 同意量 
	 *          	  $rs["dislike_cnt"],  // 讨厌量 
	 *          	  $rs["comment_cnt"],  // 评论量 
	 *          	  $rs["admin_link"],  // 管理链接 
	 *          	  $rs["admin_name"],  // 管理链接名称 
	 *          	  $rs["status"],  // 状态 
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
	 *                $rs["_map_category"][$categories[n]]["ismobnav"], // category.ismobnav
	 *                $rs["_map_category"][$categories[n]]["iswxappnav"], // category.iswxappnav
	 *                $rs["_map_category"][$categories[n]]["isappnav"], // category.isappnav
	 *                $rs["_map_category"][$categories[n]]["outer_id"], // category.outer_id
	 *                $rs["_map_category"][$categories[n]]["origin"], // category.origin
	 *                $rs["_map_series"][$series[n]]["created_at"], // series.created_at
	 *                $rs["_map_series"][$series[n]]["updated_at"], // series.updated_at
	 *                $rs["_map_series"][$series[n]]["series_id"], // series.series_id
	 *                $rs["_map_series"][$series[n]]["name"], // series.name
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
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
  		$qb->where('event.slug', '=', $slug );
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
 		$slugs = []; // 读取 inWhere series 数据
		$slugs = array_merge($slugs, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["series"];
			$rs["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySlug($slugs, $selectFields);
		}

		return $rs;
	}

	

	/**
	 * 按别名查询一组活动记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 活动记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["event.event_id","event.slug","event.title","event.cover","event.begin","event.end","event.type","event.status","event.event_id"], $order=["event.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "event.event_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_event as event", "{none}")->query();
  		$qb->whereIn('event.slug', $slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$slugs = []; // 读取 inWhere category 数据
 		$slugs = []; // 读取 inWhere series 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 			// for inWhere category
			$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$slugs = array_merge($slugs, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["series"];
			$map["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySlug($slugs, $selectFields);
		}


		return $map;
	}


	/**
	 * 按别名保存活动记录。(记录不存在则创建，存在则更新)
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
	 * 根据活动ID上传封面。
	 * @param string $event_id 活动ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverByEventId($event_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('event_id', ["event_id"=>$event_id, "cover"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据活动ID上传海报。
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
	 * 根据别名上传封面。
	 * @param string $slug 别名
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
	 * 根据别名上传海报。
	 * @param string $slug 别名
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
	 * 根据别名上传主办方。
	 * @param string $slug 别名
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
	 * 根据别名上传承办方/组织者。
	 * @param string $slug 别名
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
	 * 根据别名上传赞助商。
	 * @param string $slug 别名
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
	 * 根据别名上传合作媒体。
	 * @param string $slug 别名
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
	 * 根据别名上传嘉宾。
	 * @param string $slug 别名
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
	public function top( $limit=100, $select=["event.event_id","event.slug","event.title","event.cover","event.begin","event.end","event.type","event.status","event.event_id"], $order=["event.created_at"=>"desc"] ) {

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


 		$slugs = []; // 读取 inWhere category 数据
 		$slugs = []; // 读取 inWhere series 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$slugs = array_merge($slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$slugs = array_merge($slugs, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($slugs, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySlug') ) {
			$slugs = array_unique($slugs);
			$selectFields = $inwhereSelect["series"];
			$data["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySlug($slugs, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索活动记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["event.event_id","event.slug","event.title","event.cover","event.begin","event.end","event.type","event.status","event.event_id"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["event_id"] 按活动ID查询 ( = )
	 *			      $query["slug"] 按别名查询 ( = )
	 *			      $query["begin"] 按开始时间查询 ( = )
	 *			      $query["end"] 按结束时间查询 ( = )
	 *			      $query["area"] 按国家/地区查询 ( = )
	 *			      $query["prov"] 按省份查询 ( = )
	 *			      $query["city"] 按城市查询 ( = )
	 *			      $query["town"] 按区县查询 ( = )
	 *			      $query["price"] 按费用查询 ( > )
	 *			      $query["price"] 按费用查询 ( < )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["type"] 按类型查询 ( = )
	 *			      $query["categories"] 按栏目查询 ( LIKE-MULTIPLE )
	 *			      $query["series"] 按系列查询 ( LIKE-MULTIPLE )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *			      $query["orderby_publish_time_desc"]  按name=publish_time DESC 排序
	 *			      $query["orderby_begin_desc"]  按name=begin DESC 排序
	 *			      $query["orderby_end_desc"]  按name=end DESC 排序
	 *			      $query["orderby_deadline_desc"]  按name=deadline DESC 排序
	 *           
	 * @return array 活动记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["event_id"],  // 活动ID 
	 *               	["slug"],  // 别名 
	 *               	["title"],  // 主题 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 栏目 
	 *               	["category"][$categories[n]]["slug"], // category.slug
	 *               	["series"],  // 系列 
	 *               	["series"][$series[n]]["slug"], // series.slug
	 *               	["type"],  // 类型 
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 简介 
	 *               	["cover"],  // 封面 
	 *               	["images"],  // 海报 
	 *               	["begin"],  // 开始时间 
	 *               	["end"],  // 结束时间 
	 *               	["deadline"],  // 报名截止时间 
	 *               	["quota"],  // 名额 
	 *               	["process_setting"],  // 流程设计 
	 *               	["process"],  // 当前进程 
	 *               	["area"],  // 国家/地区 
	 *               	["prov"],  // 省份 
	 *               	["city"],  // 城市 
	 *               	["town"],  // 区县 
	 *               	["location"],  // 地点 
	 *               	["price"],  // 费用 
	 *               	["bonus"],  // 奖金 
	 *               	["prize"],  // 奖项 
	 *               	["hosts"],  // 主办方 
	 *               	["organizers"],  // 承办方/组织者 
	 *               	["sponsors"],  // 赞助商 
	 *               	["medias"],  // 合作媒体 
	 *               	["speakers"],  // 嘉宾 
	 *               	["content"],  // 活动介绍 
	 *               	["report"],  // 活动总结 
	 *               	["desktop"],  // 桌面代码 
	 *               	["mobile"],  // 手机代码 
	 *               	["wxapp"],  // 小程序代码 
	 *               	["app"],  // APP代码 
	 *               	["publish_time"],  // 发布时间 
	 *               	["view_cnt"],  // 浏览量 
	 *               	["user_cnt"],  // 报名人数 
	 *               	["like_cnt"],  // 点赞量 
	 *               	["agree_cnt"],  // 同意量 
	 *               	["dislike_cnt"],  // 讨厌量 
	 *               	["comment_cnt"],  // 评论量 
	 *               	["admin_link"],  // 管理链接 
	 *               	["admin_name"],  // 管理链接名称 
	 *               	["status"],  // 状态 
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
	 *               	["category"][$categories[n]]["ismobnav"], // category.ismobnav
	 *               	["category"][$categories[n]]["iswxappnav"], // category.iswxappnav
	 *               	["category"][$categories[n]]["isappnav"], // category.isappnav
	 *               	["category"][$categories[n]]["outer_id"], // category.outer_id
	 *               	["category"][$categories[n]]["origin"], // category.origin
	 *               	["series"][$series[n]]["created_at"], // series.created_at
	 *               	["series"][$series[n]]["updated_at"], // series.updated_at
	 *               	["series"][$series[n]]["series_id"], // series.series_id
	 *               	["series"][$series[n]]["name"], // series.name
	 *               	["series"][$series[n]]["category_id"], // series.category_id
	 *               	["series"][$series[n]]["summary"], // series.summary
	 *               	["series"][$series[n]]["orderby"], // series.orderby
	 *               	["series"][$series[n]]["param"], // series.param
	 *               	["series"][$series[n]]["status"], // series.status
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["event.event_id","event.slug","event.title","event.cover","event.begin","event.end","event.type","event.status","event.event_id"] : $query['select'];
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
				$qb->orWhere("event.title","like", "%{$query['keywords']}%");
				$qb->orWhere("event.type","like", "%{$query['keywords']}%");
				$qb->orWhere("event.process","like", "%{$query['keywords']}%");
				$qb->orWhere("event.area","like", "%{$query['keywords']}%");
				$qb->orWhere("event.prov","like", "%{$query['keywords']}%");
				$qb->orWhere("event.city","like", "%{$query['keywords']}%");
				$qb->orWhere("event.town","like", "%{$query['keywords']}%");
			});
		}


		// 按活动ID查询 (=)  
		if ( array_key_exists("event_id", $query) &&!empty($query['event_id']) ) {
			$qb->where("event.event_id", '=', "{$query['event_id']}" );
		}
		  
		// 按别名查询 (=)  
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
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("event.status", '=', "{$query['status']}" );
		}
		  
		// 按类型查询 (=)  
		if ( array_key_exists("type", $query) &&!empty($query['type']) ) {
			$qb->where("event.type", '=', "{$query['type']}" );
		}
		  
		// 按栏目查询 (LIKE-MULTIPLE)  
		if ( array_key_exists("categories", $query) &&!empty($query['categories']) ) {
            $query['categories'] = explode(',', $query['categories']);
            $qb->where(function ( $qb ) use($query) {
                foreach( $query['categories'] as $idx=>$val )  {
                    $val = trim($val);
                    if ( $idx == 0 ) {
                        $qb->where("event.categories", 'like', "%{$val}%" );
                    } else {
                        $qb->orWhere("event.categories", 'like', "%{$val}%");
                    }
                }
            });
		}
		  
		// 按系列查询 (LIKE-MULTIPLE)  
		if ( array_key_exists("series", $query) &&!empty($query['series']) ) {
            $query['series'] = explode(',', $query['series']);
            $qb->where(function ( $qb ) use($query) {
                foreach( $query['series'] as $idx=>$val )  {
                    $val = trim($val);
                    if ( $idx == 0 ) {
                        $qb->where("event.series", 'like', "%{$val}%" );
                    } else {
                        $qb->orWhere("event.series", 'like', "%{$val}%");
                    }
                }
            });
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("event.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("event.updated_at", "desc");
		}

		// 按name=publish_time DESC 排序
		if ( array_key_exists("orderby_publish_time_desc", $query) &&!empty($query['orderby_publish_time_desc']) ) {
			$qb->orderBy("event.publish_time", "desc");
		}

		// 按name=begin DESC 排序
		if ( array_key_exists("orderby_begin_desc", $query) &&!empty($query['orderby_begin_desc']) ) {
			$qb->orderBy("event.begin", "desc");
		}

		// 按name=end DESC 排序
		if ( array_key_exists("orderby_end_desc", $query) &&!empty($query['orderby_end_desc']) ) {
			$qb->orderBy("event.end", "desc");
		}

		// 按name=deadline DESC 排序
		if ( array_key_exists("orderby_deadline_desc", $query) &&!empty($query['orderby_deadline_desc']) ) {
			$qb->orderBy("event.deadline", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$events = $qb->select( $select )->pgArray($perpage, ['event._id'], 'page', $page);

 		$categories_slugs = []; // 读取 inWhere category 数据
 		$series_slugs = []; // 读取 inWhere series 数据
		foreach ($events['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$categories_slugs = array_merge($categories_slugs, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere series
			$series_slugs = array_merge($series_slugs, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInBySlug') ) {
			$categories_slugs = array_unique($categories_slugs);
			$selectFields = $inwhereSelect["category"];
            $events["category"] = (new \Xpmsns\Pages\Model\Category)->getInBySlug($categories_slugs, $selectFields);
            $events["category_data"] = array_values($events["category"]);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySlug') ) {
			$series_slugs = array_unique($series_slugs);
			$selectFields = $inwhereSelect["series"];
            $events["series"] = (new \Xpmsns\Pages\Model\Series)->getInBySlug($series_slugs, $selectFields);
            $events["series_data"] = array_values($events["series"]);
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
			
			// 添加本表前缀
			if ( !strpos( $fd, ".")  ) {
				$select[$idx] = "event." .$select[$idx];
				continue;
			}
			
			// 连接类型 (category as category )
			if ( strpos( $fd, "category." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "slug";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "event.categories");
				}
			}
			
			// 连接类型 (series as series )
			if ( strpos( $fd, "series." ) === 0 || strpos("series.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["series"][] = trim($arr[1]);
				$inwhereSelect["series"][] = "slug";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "event.series");
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
			"slug",  // 别名
			"title",  // 主题
			"link",  // 外部链接
			"categories",  // 栏目
			"series",  // 系列
			"type",  // 类型
			"tags",  // 标签
			"summary",  // 简介
			"cover",  // 封面
			"images",  // 海报
			"begin",  // 开始时间
			"end",  // 结束时间
			"deadline",  // 报名截止时间
			"quota",  // 名额
			"process_setting",  // 流程设计
			"process",  // 当前进程
			"area",  // 国家/地区
			"prov",  // 省份
			"city",  // 城市
			"town",  // 区县
			"location",  // 地点
			"price",  // 费用
			"bonus",  // 奖金
			"prize",  // 奖项
			"hosts",  // 主办方
			"organizers",  // 承办方/组织者
			"sponsors",  // 赞助商
			"medias",  // 合作媒体
			"speakers",  // 嘉宾
			"content",  // 活动介绍
			"report",  // 活动总结
			"desktop",  // 桌面代码
			"mobile",  // 手机代码
			"wxapp",  // 小程序代码
			"app",  // APP代码
			"publish_time",  // 发布时间
			"view_cnt",  // 浏览量
			"user_cnt",  // 报名人数
			"like_cnt",  // 点赞量
			"agree_cnt",  // 同意量
			"dislike_cnt",  // 讨厌量
			"comment_cnt",  // 评论量
			"admin_link",  // 管理链接
			"admin_name",  // 管理链接名称
			"status",  // 状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>