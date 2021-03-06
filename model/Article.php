<?php
namespace Xpmsns\pages\Model; 
define('__NS__', 'Xpmsns\pages\Model'); // 兼容旧版 App::M 方法调用

use \Xpmse\Loader\App as App;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Model as Model;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Media as Media;
use \Xpmse\Nlp as NPL;
use \Mina\Delta\Render as Render;
use \Xpmse\Task as Task;
use \Xpmse\Job;
use \Xpmse\Content;
use \Mina\Cache\Redis as Cache;
use \Exception as Exception;

define('ARTICLE_PUBLISHED', 'published');  // 文章状态 已发布
define('ARTICLE_UNPUBLISHED', 'unpublished');  // 文章状态 未发布
define('ARTICLE_AUDITING', 'auditing');   // 文章状态 审核中
define('ARTICLE_PENDING', 'pending');  // 文章状态 未完成抓取

define('DRAFT_APPLIED', 'applied'); // 已合并到文章中 DRAFT
define('DRAFT_UNAPPLIED', 'unapplied'); // 未合并到文章中 DRAFT

define('STATUS_PUBLISHED', 'PUBLISHED');   // 已发布
define('STATUS_UNPUBLISHED', 'UNPUBLISHED');   // 未发布
define('STATUS_UNAPPLIED', 'UNAPPLIED');   // 有修改（尚未更新)
define('STATUS_PENDING', 'PENDING');   // 同步中（数据尚未准备好）
define('STATUS_AUDITING', "AUDITING"); // 审核中

define('DEFAULT_PROJECT_NAME', 'default');  // 默认项目名称
define('DEFAULT_PAGE_SLUG', '/article/detail');  // 默认页面地址
define('DEFAULT_PAGE_SLUG_V2', '/desktop/article/detail');  // 默认页面地址V2
define('DEFAULT_PAGE_SLUG_VIDEO', '/desktop/video/detail');  // 视频类默认地址

/**
 * 文章数据模型
 */
class Article extends Model {

	public $article_category;
	public $article_tag;
	public $article_draft;
	private $option = null;
	private $npl = null;

    
	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {

		parent::__construct(['prefix'=>'xpmsns_pages_']);

		$this->table('article');
		$this->delta_render = new Render();
		$this->article_category = Utils::getTab('article_category', "xpmsns_pages_");  // 分类关联表
		$this->article_tag = Utils::getTab('article_tag', "xpmsns_pages_");    // 标签关联表
		$this->article_draft = Utils::getTab('article_draft', "xpmsns_pages_");  // 文章草稿箱
		$this->page = Utils::getTab('page', 'core_');  // 页面表
		$this->host = Utils::getHome();  // 页面根地址
		$this->option = new \Xpmse\Option('xpmsns/pages');

		// $root = Conf::G("storage/local/bucket/public/root");
		// $options = [
		// 	"prefix" => $root . '/media',
		// 	"url" => "/static-file/media",
		// 	"origin" => "/static-file/media",
		// 	"cache" => [
		// 		"engine" => 'redis',
		// 		"prefix" => '_mediaStorage:',
		// 		"host" => Conf::G("mem/redis/host"),
		// 		"port" => Conf::G("mem/redis/port"),
		// 		"raw" =>3600,  // 数据缓存 1小时
		// 		"info" => 3600   // 信息缓存 1小时
		// 	]
		// ];
		// $this->stor = new Local( $options );
        $this->media = new Media(['host'=>$this->host]);

        // 缓存
        $this->cache = new Cache( [
            "prefix" => "xpmsns_pages_article:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

    }

    /**
     * 读取上一篇文章ID
     * @param string $article_id 文章ID
     * @param string $category_id 文章类目ID
     * @return 上一篇文章标题和 article_id
     */
    function getPrev( $article_id, $category_id=null ) {
        
        $rows = $this->query()
                     ->where("article_id", "<", $article_id )
                     ->orderBY("article_id", "desc")
                     ->select(["article_id","title"])
                     ->limit(1)
                     ->get()
                     ->toArray()
                    ;
        if ( empty($rows) ) {
            return null;
        }

        return current($rows);
    }


    /**
     * 读取下一篇文章ID
     * @param string $article_id 文章ID
     * @param string $category_id 文章类目ID
     * @return 下一篇文章标题和 article_id
     */
    function getNext( $article_id, $category_id=null ) {
        
        $rows = $this->query()
                     ->where("article_id", ">", $article_id )
                     ->orderBY("article_id", "asc")
                     ->select(["article_id","title"])
                     ->limit(1)
                     ->get()
                     ->toArray()
                    ;
        if ( empty($rows) ) {
            return null;
        }

        return current($rows);
    }
    

    /**
     * 标记为打开，并记录打开时刻
     * @param string $article_id 文章ID
     * @return bool 如已打开返回false , 新打开返回true
     */
    function opened( $article_id ) {
        @session_start();
        if ( !empty($_SESSION["article_opened_{$article_id}"]) ) {
            return false;
        }

        $_SESSION["article_opened_{$article_id}"] = time();
        return true;
    }

    /**
     * 标记为关闭，并计算停留时长
     * @param string $article_id 文章ID
     * @return int $duration 停留时长
     */
    function closed( $article_id ) {
        @session_start();
        $start = $_SESSION["article_opened_{$article_id}"];
        unset( $_SESSION["article_opened_{$article_id}"] );
        if ( empty($start) ) {
            return 0;
        }
        return time()-intval($start);
    }


    /**
     * 关联某人收藏信息
     * @param array &$articles 文章信息
     * @param string $user_id 用户ID 
     * @return null
     */
    function withFavorite( & $articles, $user_id, $select=["favorite.origin_outer_id","favorite.favorite_id","user.user_id","user.name","user.nickname","user.mobile","favorite.origin","favorite.outer_id","favorite.created_at","favorite.updated_at"]) {

        $article_ids = array_column( $articles, "article_id");
        if ( empty( $article_ids) ) {
            return;
        }

        // 读取收藏信息
        $fav = new \Xpmsns\User\Model\Favorite;
        $origin_outer_ids = array_map(function($article_id) use( $user_id ){ return "article_{$user_id}_{$article_id}"; }, $article_ids);
        $favorites = $fav->getInByOriginOuterId($origin_outer_ids, $select);

        foreach($articles as & $article ) {
            $origin_outer_id = "article_{$user_id}_{$article['article_id']}";
            $article["favorite"] = $favorites[$origin_outer_id];
            if (is_null($article["favorite"]) ){
                $article["favorite"] = [];
            }
        }
    }


    /**
     * 读取专栏文章
     */
    function withSpecial( & $article, $special_id, $exclude=[] ) {
       
        // exclude_article_ids
        $query = [
            "special_id"=>$special_id, 
            "order" => "publish_time desc",
            "perpage"=>5
        ];

        if ( !empty($exclude) ) {
            $query["exclude_article_ids"] = $exclude;
        }

        $response = $this->search($query);
        $article["special_articles"] = $response["data"];

    }

    /**
     * 读取用户文章
     */
    function withUser( & $article, $user_id, $exclude=[] ) {
       
        $query = [
            "user_id"=>$user_id, 
            "order" => "publish_time desc",
            "perpage"=>5
        ];

        if ( !empty($exclude) ) {
            $query["exclude_article_ids"] = $exclude;
        }

        $response = $this->search($query);
        $article["user_articles"] = $response["data"];
    }


    /**
     * 关联某人赞同数据
     * @param array &$rows 回答数据
     * @param string $user_id 用户ID 
     * @return null
     */
    function withAgree( & $rows, $user_id, $select=["agree.origin_outer_id","agree.agree_id","user.user_id","user.name","user.nickname","user.mobile","agree.origin","agree.outer_id","agree.created_at","agree.updated_at"]) {

        if ( !class_exists("\\Xpmsns\\Comment\\Model\\Agree") ) {
            return;
        }
        
        $ids = array_column( $rows, "article_id");
        if ( empty( $ids) ) {
            return;
        }

        // 读取赞同信息
        $ag = new \Xpmsns\Comment\Model\Agree;
        $origin_outer_ids = array_map(function($id) use( $user_id ){ return "article_{$user_id}_{$id}"; }, $ids);
        $agrees = $ag->getInByOriginOuterId($origin_outer_ids, $select);

        // 合并到数据表
        foreach($rows as & $rs ) {
            $origin_outer_id = "article_{$user_id}_{$rs['article_id']}";
            $rs["agree"] = $agrees[$origin_outer_id];
            if (is_null($rs["agree"]) ){
                $rs["agree"] = [];
                $rs["has_agreed"] = false;
            }  else {
                $rs["has_agreed"] = true;
            }
        }
    }


    /**
     * 读取文章分类数据
     * @param array &$rows 文章数据
     * @param string $select 选中数据
     */
    function withCategory( & $rows, $select=["category.category_id","name","fullname","project","page","parent_id","priority","hidden","param"] ){
        $ids = array_column( $rows, "article_id");
        if ( empty( $ids) ) {
            return;
        }
        $categories = $this->getCategoriesGroup($ids, $select);
        foreach( $rows as & $rs ) {
            $id = $rs["article_id"];
            $rs['category'] = [];
            $rs['category_last'] = [];
            $rs['category_ids'] = [];

            if ( is_array($categories[$id]) ) {
                $rs['category'] = $categories[$id];
                $rs['category_last'] = end($rs['category']);
                $rs['category_ids'] =  array_column($rs["category"], "category_id");
			}
        }
    }


    /**
     * 读取文章标签数据
     * @param array &$rows 文章数据
     * @param string $select 选中数据
     */
    function withTag( & $rows, $select=['tag.tag_id', 'name', 'param'] ){
        $ids = array_column( $rows, "article_id");
        if ( empty( $ids) ) {
            return;
        }
        $tags = $this->getTagsGroup($ids, $select);
        foreach( $rows as & $rs ) {
            $id = $rs["article_id"];
            $rs['tag'] = [];
            $rs['tags'] = [];

            if ( is_array($tags[$id]) ) {
                $rs['tag'] = $tags[$id];
                $rs["tags"] =  array_column($rs["tag"], "name");
			}
        }
    }


    /**
     * 读取文章详情
     * @param string $article_id 文章ID
     * @param array $select 数据选项
     * @return array 文章结构体
     */
    function getByArticleId( $article_id, $select=["*"] ) {

        $getTag = false; $getCategory = false;
        $select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		foreach ($select as $idx => $field) {

			if ( $field == '*') {
				$getTag = true; $getCategory = true;
			}

			if ( $field == 'category' ) {
				$getCategory = true;
				unset( $select[$idx] );
			}

			if ( $field == 'tag' ) {
				$getTag = true;
				unset( $select[$idx] );
			}
        }
        
		$rs = $this->getLine("WHERE article_id=:article_id LIMIT 1", $select, ["article_id"=>$article_id]);
		if ( empty($rs) ) {
			throw new Excp("文章不存在", 404,  ['query'=>$query]);
		}

		$this->format($rs);

		if( $getCategory) {
			$rs['category'] = $this->getCategories($article_id,"category.category_id","name","fullname","project","page","parent_id","priority","hidden","param" );

			if ( is_array($rs['category']) ) {
                $rs['category_last'] = end($rs['category']);
                $rs['category_ids'] =  array_column($rs["category"], "category_id");
			}
		}

		if ( $getTag ) {
            $rs["tag"] = $this->getTags($article_id, 'tag.tag_id', 'name', 'param');
            $rs["tags"] = [];
            if ( is_array($rs["tag"]) ){
                $rs["tags"] =  array_column($rs["tag"], "name");
            }
        }
 
		return $rs;

    }

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
		$struct = [
			'article_id'=> ['string', ['length'=>128, 'unique'=>true]],  // 文章 ID  ( 同 _id )
			'outer_id'=> ['string', ['length'=>128, 'unique'=>1]],  // 外部ID用于数据同步下载 ( 同 _id )
			'cover'=> ['string',  ['length'=>600, "json"=>true]],   // 文章封面
			'thumbs' =>['text',  ["json"=>true]],     // 主题图片(废弃)
			'images'=> ['text',  ['json'=>true]],  // 图片内容
			'videos'=> ['text',  ['json'=>true]],  // 视频内容
            'audios'=> ['text',  ['json'=>true]],  // 音频内容
            'attachments'=> ['text',  ['json'=>true]],  // 附件内容
			'title'=>['string',  ['length'=>128, 'index'=>1]],  // 标题
			'author'=> ['string',  ['length'=>128, 'index'=>1]],  // 作者
			'origin'=> ['string',  ['length'=>128, 'index'=>1]],  // 来源
			'origin_url'=>['string',  ['length'=>256]],  // 来源网址
			'summary'=> ['string',  ['length'=>600]],  // 摘要
			'seo_title'=> ['string',  ['length'=>600]],  // 搜索引擎标题
			'seo_keywords'=> ['string',  ['length'=>600]],  // 搜索引擎关键词
			'seo_summary'=> ['string',  ['length'=>600]],   // 搜索引擎显示摘要
			'publish_time'=> ['timestampTz',  ["index"=>1]],   // 发表时间
			'update_time'=> ['timestampTz',  ["index"=>1]],  // 更新时间
			'create_time'=> ['timestampTz',  ["index"=>1]],  // 创建时间
			'baidulink_time'=> ['timestampTz',  ["index"=>1]],   // 提交到百度的时间
			'sync'=> ['string',  ["json"=>true, 'length'=>600]],  // 公众号同步状态
            'ap_content'=> ['longText',  ["json"=>true]],  // 小程序正文 (废弃)

            'content'=> ['longText',  [] ],  // 正文 (可编辑内容, 原始正文)
            'desktop'=> ['longText',[]],  // 阅读: 桌面浏览器
            'mobile'=> ['longText',[]],   // 阅读: 手机浏览器
            'app'=> ['longText',  ["json"=>true]],  // 阅读: 移动应用
            'wxapp'=> ['longText',  ["json"=>true]],  // 阅读: 小程序

			'delta'=> ['longText',  ["json"=>true]],  // 编辑状态文章 (Delta ) (废弃)
			'param'=> ['string', ['length'=>128,'index'=>1]],  // 自定义查询条件  (废弃)
			'stick'=> ['integer', ['index'=>1, 'default'=>"0"]],  // 置顶状态
			'preview' => ['longText', ['json'=>true]], // 预览链接  (废弃)
			'links' => ['longText', ['json'=>true]], // 访问链接  (废弃)
			'series' => ['string', ['json'=>true, 'length'=>400, 'index'=>1]], // 所属系列
			'user' => ['string', ['length'=>128,'index'=>1]], // 最后编辑用户ID
			'status'=> ['string', ['length'=>40,'index'=>1, 'default'=>ARTICLE_UNPUBLISHED]],  // 文章状态 unpublished/published/pending
            'keywords' => ['string',['length'=>600, 'index'=>1]],  // 提取关键词

            // 用户相关信息
            'user_id' => ["string", ["length"=>128, "index"=>true, "null"=>true]],// 用户ID
            'policies' => ['string', ["length"=>32, "index"=>true, "default"=>"public", "null"=>true]], // 文章访问策略  public/partially/private/follower-only
            'policies_detail' => ['string', ["length"=>600, "json"=>true, "null"=>true]], // 访问策略详情

            "policies_comment"=> ['string', ["length"=>32, "index"=>true, "default"=>"closed"]], // 评论许可策略  opened/closed/follower-only
            "policies_reward"=> ['string', ["length"=>32, "index"=>true, "default"=>"opened", ]], // 打赏许可策略  opened/closed

            // 追加信息
            'priority' => ["integer", ["length"=>1, "index"=>true, "default"=>99999]], // 优先级
            'coin_view' => ["integer", ["length"=>1, "index"=>true, "null"=>true]],  // 访问积分
            'money_view' => ["integer", ["length"=>1, "index"=>true, "null"=>true]],  // 访问金额
            'specials' => ['string', ['json'=>true, 'length'=>400, 'index'=>true]], // 所属专栏

            // + 样式呈现 ( default / col-2  )
            "style"=> ['string', ["length"=>32, "index"=>true, "default"=>"default" ]], // 呈现样式

		];

		$struct_draft_only = [
			'draft_status'=> ['string', ['length'=>40,'index'=>1, 'default'=>DRAFT_UNAPPLIED]],  // 草稿状态 unapplied/applied/pendding 
			'history'=>  ['longText', ['json'=>true] ],    // 上一次修改记录 (用于保存)
			'category'=> ['longText', ['json'=>true] ],    // 分类映射信息 ( 仅用于草稿信息 )
			'tag'=>['longText', ['json'=>true] ]   // 标签映射信息 ( 仅用于草稿信息 )
		];

		$article_only = [
			'view_cnt' => ['bigInteger', ['index'=>1, 'default'=>0]], // 浏览量
			'like_cnt' => ['bigInteger', ['index'=>1, 'default'=>0]],  // 点赞(喜欢)数量 
			'dislike_cnt' => ['bigInteger', ['index'=>1, 'default'=>0]],  // 讨厌 (不喜欢)数量 
            'comment_cnt'  => ['bigInteger', ['index'=>1, 'default'=>0]],   // 评论数量
            'agree_cnt' => ["integer", ["length"=>1, "index"=>true, "default"=>0]], // 赞同量
            'history' => ["longText", ["json"=>true, "null"=>true]],  // 修改历史( 逐步替代历史数据 )
        ];
        
		// 添加文章表和草稿表结构
		foreach ($struct as $field => $args ) {
			$this->putColumn( $field, $this->type($args[0], $args[1]) );
			$this->article_draft->putColumn( $field, $this->type($args[0], $args[1]) );
		}

		// 添加文章表表结构
		foreach ($article_only as $field => $args ) {
			$this->putColumn( $field, $this->type($args[0], $args[1]) );
		}

		// 添加草稿表结构
		foreach ($struct_draft_only as $field => $args ) {
			$this->article_draft->putColumn( $field, $this->type($args[0], $args[1]) );	
		}

		// 关联表 article_category
		// $article_category = $this->article_category ;
		// if ( $article_category->tableExists() === false) {
		$this->article_category->putColumn( 'article_id', $this->type('string', ['index'=>1 , 'length'=>128 ]) )  // 文章 ID 
				                ->putColumn( 'category_id', $this->type('string', ['index'=>1 , 'length'=>128]) )
				                ->putColumn( 'unique_id', $this->type('string', ['length'=>128, 'unique'=>true]) );

		// }

		// 关联表 article_tag
		// $article_tag = $this->article_tag;
		// if ( $article_tag->tableExists() === false) {
		$this->article_tag->putColumn( 'article_id', $this->type('string', ['index'=>1 , 'length'=>128]) )  // 文章 ID 
				           ->putColumn( 'tag_id', $this->type('string', ['index'=>1 , 'length'=>128]) )
				           ->putColumn( 'unique_id', $this->type('string', ['length'=>128, 'unique'=>true]) );
		// }
    }



     /**
     * 图文初始化( 注册行为/注册任务/设置默认值等... )
     */
    public function __defaults() {

        // 注册任务
        $tasks = [
            [
                "name"=>"阅读文章任务", "slug"=>"article-reading", "type"=>"repeatable",
                "daily_limit"=>1, "process"=>5, 
                "quantity" => [100,200,300,400,500],
                "auto_accept" => 0,
                "params" => [
                    "count"=>5
                ],
                "accept" => ["class"=>"\\xpmsns\\pages\\model\\article", "method"=>"onArticleReadingAccpet"],
                "status" => "online",
            ],[
                "name"=>"邀请好友阅读文章任务", "slug"=>"article-invitee-reading", "type"=>"repeatable",
                "daily_limit"=>1, "process"=>5, 
                "quantity" => [100,200,300,400,500],
                "auto_accept" => 0,
                "params" => [
                    "count"=>5,
                    "duration"=>4
                ],
                "accept" => ["class"=>"\\xpmsns\\pages\\model\\article", "method"=>"onArticleInviteeReadingAccpet"],
                "status" => "online",
            ]
        ];

        // 注册行为
        $behaviors =[
            [
                "name" => "打开文章", "slug"=>"xpmsns/pages/article/open",
                "intro" =>  "本行为当有访问者打开文章时触发",
                "params" => ["article_id"=>"文章ID", "time"=>"打开时刻", "inviter"=>"邀请者信息"],
                "status" => "online",
            ],[
                "name" => "关闭文章", "slug"=>"xpmsns/pages/article/close",
                "intro" =>  "本行为当有访问者关闭文章时触发",
                "params" => ["article_id"=>"文章ID", "time"=>"关闭时刻", "duration"=>"停留时长", "inviter"=>"邀请者信息"],
                "status" => "online",
            ]
        ];

        // 订阅行为( 响应任务处理 )
        $subscribers =[
            [
                "name" => "更新文章阅读量脚本",
                "behavior_slug"=>"xpmsns/pages/article/open",
                "outer_id" => "article-updateViewsScript",
                "origin" => "article",
                "timeout" => 30,
                "handler" => ["class"=>"\\xpmsns\\pages\\model\\article", "method"=>"updateViewsScript"],
                "status" => "on",
            ],[
                "name" => "阅读文章任务",
                "behavior_slug"=>"xpmsns/pages/article/open",
                "outer_id" => "article-reading",
                "origin" => "task",
                "timeout" => 30,
                "handler" => ["class"=>"\\xpmsns\\pages\\model\\article", "method"=>"onArticleReadingChange"],
                "status" => "on",
            ],[
                "name" => "邀请好友阅读文章任务",
                "behavior_slug"=>"xpmsns/pages/article/close",
                "outer_id" => "article-invitee-reading",
                "origin" => "task",
                "timeout" => 30,
                "handler" => ["class"=>"\\xpmsns\\pages\\model\\article", "method"=>"onArticleInviteeReadingChange"],
                "status" => "on",
            ],
        ];

        if ( !class_exists("\\Xpmsns\\User\\Model\\Task") ){
            return ;
        }

        $t = new \Xpmsns\User\Model\Task();
        $b = new \Xpmsns\User\Model\Behavior();
        $s = new \Xpmsns\User\Model\Subscriber();

        foreach( $tasks as $task ){
            try { $t->create($task); } catch( Excp $e) { $e->log(); }
        }

        foreach( $behaviors as $behavior ){
            try { $b->create($behavior); } catch( Excp $e) { $e->log(); }
        }
        foreach( $subscribers as $subscriber ){
            try { $s->create($subscriber); } catch( Excp $e) { $e->log(); }
        }
    }


    /**
     * 任务接受响应: 阅读文章任务(验证是否符合接受条件)
     * @return 符合返回 true, 不符合返回 false
     */
    public function onCheckinAccpet(){
        return true;
    }

    /**
     * 任务接受响应: 邀请好友阅读文章任务(验证是否符合接受条件)
     * @return 符合返回 true, 不符合返回 false
     */
    public function onArticleInviteeReadingAccpet(){
        return true;
    }


    /**
     * 订阅器: 更新文章阅读量脚本 (打开文章行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(打开文章)数据结构
     * @param array $subscriber  订阅者(更新文章阅读量脚本) 数据结构  ["outer_id"=>"article-updateViewsScript...", "origin"=>"article" ... ]
     * @param array $data  行为数据 ["article_id"=>"文章ID", "time"=>"打开时刻", "inviter"=>"邀请者信息"] ...
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    public function updateViewsScript( $behavior, $subscriber, $data, $env ) {
        $article_id = $data["article_id"];
        if ( empty( $article_id ) ) {
            return;
        }

        $this->updateBy( 'article_id', [
            "article_id"=>$article_id,
            "view_cnt" => 'DB::RAW(IFNULL(`view_cnt`, 0) + 1)'
        ]);
    }


    /**
     * 订阅器: 阅读文章任务 (打开文章行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(打开文章)数据结构
     * @param array $subscriber  订阅者(更新文章阅读量脚本) 数据结构  ["outer_id"=>"article-updateViewsScript...", "origin"=>"article" ... ]
     * @param array $data  行为数据 ["article_id"=>"文章ID", "time"=>"打开时刻", "inviter"=>"邀请者信息"] ...
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    public function onArticleReadingChange( $behavior, $subscriber, $data, $env ) {
        
        // 记录阅读历史 (下一版实现)
        $task_slug = $subscriber["outer_id"];
        $user_id = $env["user_id"];
        $article_id = $data["article_id"];
        $cache_name = "onArticleReadingChange:{$user_id}:{$article_id}";
        if (empty( $article_id )) {
            return;
        }

        if (empty( $user_id )) {
            return;
        }
        
        $job = new Job(["name"=>"XpmsnsUserBehavior"]);
        if ( $this->cache->get($cache_name) !== false ) {
            $job->info("\t用户已读过本篇文章(user={$user_id} article={$article_id})");
            $job->info("\t当前步骤: 维持不变");
            return;
        }

        // 读取任务信息
        $t = new \Xpmsns\User\Model\Usertask;
        $task = $t->getByTaskSlugAndUserId( $task_slug, $user_id );
        if ( empty($task) ) {
            throw new Excp("未找到任务信息({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]);
        }

        // 自动接受任务
        $usertask = $task["usertask"];
        if( 
            $task["auto_accept"] == 1 &&
            ( empty($usertask) || ( $usertask["status"] != "accepted" &&  $task["type"] == "repeatable" ) )
        ) {
            $task["usertask"] = $usertask = $t->acceptBySlug( $task_slug, $user_id );
        }

        if ( empty($task["usertask"]) ) {
            // throw new Excp("用户尚未接受该任务({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]); 
            return;
        }

        // 任务已完成忽略处理
        if ( !empty($task["usertask"]) && $usertask["status"] == "completed" ) {
            return;
        }


        // 缓存到第二日凌晨
        $tomorrow = strtotime("+1d", time());
        $tomorrow = strtotime(date("Y-m-d 00:00:00", $tomorrow));
        $tls = $tomorrow-time();
        $this->cache->set($cache_name, time(), $tls);
        $job->info("\t标记为已读有效期至".date("Y-m-d 00:00:00", $tomorrow). " (user={$user_id} article={$article_id}) tls={$tls}");

        // 扩展数量
        $params = is_array($task["params"]) ? $task["params"] : [];
        $params["count"] = empty($params["count"]) ?  intval($task["process"]) : intval($params["count"]);
        if ( $params["count"] != intval($task["process"]) ) {
            $tt = new  \Xpmsns\User\Model\Task;
            $quantity = []; 
            for( $i=0;$i<$params["count"]; $i++) {
                $quantity[$i] = 0;
            }
            $quantity[$params["count"]-1] = end($task["quantity"]);

            $tt->updateBy("task_id", [
                "task_id"=>$task["task_id"],
                "process" => $params["count"],
                "quantity" => $quantity,
            ]);
        }

        // 计算分数
        $process = intval($usertask["process"]) + 1;
        $job->info("\t当前步骤: process={$process}");
        $t->processByUsertaskId( $usertask["usertask_id"], $process );
    }


    /**
     * 订阅器: 邀请好友阅读文章任务 (关闭文章行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(打开文章)数据结构
     * @param array $subscriber  订阅者(更新文章阅读量脚本) 数据结构  ["outer_id"=>"article-updateViewsScript...", "origin"=>"article" ... ]
     * @param array $data  行为数据 ["article_id"=>"文章ID", "time"=>"关闭时刻", "duration"=>"停留时长", "inviter"=>"邀请者信息"],
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    public function onArticleInviteeReadingChange( $behavior, $subscriber, $data, $env ) {

        // 记录阅读历史 (下一版实现)
        $task_slug = $subscriber["outer_id"];
        $inviter = $data["inviter"];
        $user_id = $inviter["user_id"];
        $article_id = $data["article_id"];
        $cache_name = "onArticleInviteeReadingChange:{$user_id}:{$article_id}";
        if (empty( $article_id )) {
            return;
        }
        if (empty( $user_id )) {
            return;
        }

        $job = new Job(["name"=>"XpmsnsUserBehavior"]);
        if ( $this->cache->get($cache_name) !== false ) {
            $job->info("\t被邀请者已读过本篇文章(user={$user_id} article={$article_id})");
            $job->info("\t当前步骤: 维持不变");
            return;
        }

        // 排除自己
        if ( $env["user_id"] == $user_id ) {
            $job->info("\t被邀请者是用户自己(user={$user_id} article={$article_id})");
            $job->info("\t当前步骤: 维持不变");
            return;
        }

        $t = new \Xpmsns\User\Model\Usertask;
        $task = $t->getByTaskSlugAndUserId( $task_slug, $user_id );
        if ( empty($task) ) {
            throw new Excp("\t未找到任务信息({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]);
        }

        // 自动接受任务
        $usertask = $task["usertask"];
        if( 
            $task["auto_accept"] == 1 &&
            ( empty($usertask) || ( $usertask["status"] != "accepted" &&  $task["type"] == "repeatable" ) )
        ) {
            $task["usertask"] = $usertask = $t->acceptBySlug( $task_slug, $user_id );
        }

        if ( empty($task["usertask"]) ) {
            throw new Excp("\t用户尚未接受该任务({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]); 
            return;
        }

        // 扩展数量
        $params = is_array($task["params"]) ? $task["params"] : [];
        $params["count"] = empty($params["count"]) ?  intval($task["process"]) : intval($params["count"]);
        if ( $params["count"] != intval($task["process"]) ) {
            $tt = new  \Xpmsns\User\Model\Task;
            $quantity = []; 
            for( $i=0;$i<$params["count"]; $i++) {
                $quantity[$i] = 0;
            }
            $quantity[$params["count"]-1] = end($task["quantity"]);

            $tt->updateBy("task_id", [
                "task_id"=>$task["task_id"],
                "process" => $params["count"],
                "quantity" => $quantity,
            ]);
        }

        // 计算 $params["duration"]
        $params["duration"] = isset($params["duration"]) ? intval($params["duration"]) : 0;
        if ( $data["duration"] < $params["duration"] ) {
            return ;
        }

        $process = intval($usertask["process"]) + 1;
        $job->info("\t当前步骤: process={$process}");
        $t->processByUsertaskId( $usertask["usertask_id"], $process );
    }



	/**
	 * 读取微信小程序配置
	 * @return [type] [description]
	 */
	function wxapp() {

		$conf = Utils::getConf();
		$grops = is_array($conf['_groups']) ? $conf['_groups'] : []; 

		$items = [];
		foreach ($grops as $group => $cfg) {
			if ( $cfg['type'] == 3 && $cfg['appid'] <> '' ){
				return $group;
			}
		}
		return null;
	}


	function isExists( $title, $url="", $outer_id="") {
		$qb = $this->query();
		$qb->where("title", "=", $title);

		if ( !empty($outer_id) ) {
			$qb->orWhere("outer_id", "=", $outer_id);
		}

		if ( !empty($url) ) {
			$qb->orWhere("origin_url", "=", $url);
		}
				   
		$rows= $qb->limit(1)->select("article_id")->get()->toArray();
		if ( count($rows) == 0) {
			return false;
		}

		return current($rows)["article_id"];
	}


	/**
	 * 更新 Spider Data
	 * @param  array $data spider hook 返回数值
	 *    "content_id" 正文ID
	 *    "source_name" 内容原名称
	 *    "source_site" 内容源网址
	 *    "content" 正文 HTML
	 *    "url"     来源网站
	 *    "pubtime" 发布时间
	 *    "title"   标题
	 *    "summary" 摘要
	 *    "author"  作者
	 *    "keywords" 关键词
	 *    "cover"    封面图片 base64 图片 blob
	 *    "images"   图片列表 url : base64 图片 blob
	 *    "__params" 设定的参数表
	 *    "__event"  Spider时间信息
	 *
	 * @return [type]       [description]
	 */
	function spiderUpdate($data) {

		// 排重
		if ( $this->isExists( $data["title"], $data["url"], $data["content_id"]) ) {
			return ["code"=>0, "message"=>"article is existed"];
		}

		// Content 
		
		if ( empty($data["content"]) ) {
			throw new Excp("未找到内容正文，取消存储", 404);
		}

		// 不解析content
		$data["parse_content"] = "nope";

		// outer id
		$data["outer_id"] = $data["content_id"];

		// publish_time
		$publish_time = date("Y-m-d H:i:s");
		if ( !empty($data["pubtime"]) ) {
			if ( is_numeric($data["pubtime"])) {
				$publish_time =date("Y-m-d H:i:s", $data["pubtime"] );	
			} else {
				$publish_time =date("Y-m-d H:i:s", strtotime($data["pubtime"]));
			}			
		}

		$data["publish_time"] = $publish_time;

		// cover
		if ( !empty($data["cover"]) ) {
			$cover_blob = base64_decode($data["cover"]);
			$cover_name = time() . ".png";
			if ( !empty($data["content_id"]) ) {
				$cover_name = $data["content_id"] . ".png";
			}
			try {
				$cover_info = $this->media->appendFile( $cover_name, $cover_blob, true);
				$data["cover"] = $cover_info["path"];
			}catch( Excp $e ) {}
		}


		// images 
		if ( is_array( $data["images"]) ) {

			$images = [];
			foreach ($data["images"] as $img ) {
				$url = $img["url"];
				$blob = base64_decode($img["blob"]);
				$name = time();
				if ( !empty($data["content_id"]) ) {
					$name = $data["content_id"];
				}
				$name = md5( $name . $url ) . ".png";
				try {
					$uri = $this->media->appendFile( $name, $blob, true);
					$data["content"] = str_replace($url, $uri["url"], $data["content"]);
					array_push($images, [
						"url"=>$uri["url"], 
						"origin"=>$uri["origin"], 
						"path" => $uri["path"]
					]);
				}catch( Excp $e ) {}
			}

			$data["images"] = $images;
		}


		// delta 
		$this->delta_render->loadByHTML($data["content"]);
		$data["delta"] = $this->delta_render->delta();

		// 设定来源
		$data["origin"] = $data["source_name"];
		$data["origin_url"] = $data["url"];


		// 设定分类
		if ( !empty($data["__params"]["category"]) ) {
			$data["category"] = $data["__params"]["category"];
		}
		if ( !empty($data["__params"]["category_names"]) ) {
			$data["category_names"] = $data["__params"]["category_names"];
		}

		$data['status'] = ARTICLE_PUBLISHED;
		$this->save($data);
		return ["code"=>0, "message"=>"saved"];

		// $url = $data['url'];
		// if ( empty($url) ) {
		// 	throw new Excp("请提供目标网页地址", 404, ['data'=>$data]);
		// }

		// $spider = new Spider(['host'=>Utils::getHome(Utils::getLocation())]);
		// $page = $spider->crawl($url);
		// $data = array_merge($page, $data);

		// if ( empty($data['category']) ) {
		// 	$cate = new Category();
		// 	$data['category'] = $cate->getVar('category_id', "WHERE slug='default' LIMIT 1");
		// }

		// if( !empty($data['publish_date']) ) {
		// 	$time = strtotime($data['publish_date']);
		// 	$data['publish_time'] = date('Y-m-d H:i:s', $time);
		// 	unset($data['publish_date']);
		// }

		// if ( empty($data['status']) ) {
		// 	$data['status'] = ARTICLE_UNPUBLISHED;
		// }
	}


	/**
	 * 文章查询
	 */
	function search( $query = [] ) {

        
        // $qb = $this->query();
        $qb =  Utils::getTab("xpmsns_pages_article as article", "{none}")->query();

        $qb	->leftJoin("xpmsns_pages_article_draft as draft", "draft.article_id", "=", "article.article_id")
            ->leftJoin("xpmsns_pages_article_category as ac", "ac.article_id", "=", "article.article_id")
			->leftJoin("xpmsns_pages_category as c", "c.category_id", "=", "ac.category_id")
			->leftJoin("xpmsns_pages_article_tag as at", 'at.article_id', "=", "article.article_id")
            ->leftJoin("xpmsns_pages_tag as t", "t.tag_id", "=", "at.tag_id")
            ->leftJoin("xpmsns_user_user as user", "article.user_id", "=", "user.user_id")
            ->leftJoin("xpmsns_pages_special as special", "article.user_id", "=", "special.user_id")

        ;
        
        // 按文章状态查询 ( 默认查询已发布文章 )
        if ( empty($query["status"]) ) {
            $qb->where('article.status', '=', 'published');

        } else {
            
            if ( is_string( $query["status"] )  ) {
                $query["status"] = explode(",",  $query["status"]);
            }

            if ( !is_array($query["status"]) ) {
                $query["status"] =  ['published'];
            }

            $query["status"]=array_map('trim',$query["status"]);

            // 处理草稿
            if ( in_array("draft", $query["status"]) ) {
                array_push($query["status"], 'unpublished');
                $query["status"] = array_unique($query["status"]);
            }

            $qb->whereIn('article.status', $query["status"]);
        }

		$select_defaults = [
			"article.article_id", "article.title", "article.summary", 
            "article.origin", "article.origin_url", "article.author", 
            "article.seo_title","article.seo_keywords","article.seo_summary",
            "article.cover", "article.images", "article.thumbs", "article.thumbs", "article.videos",
            "article.stick", "article.priority",
            "article.view_cnt", "article.like_cnt", "article.dislike_cnt", "article.comment_cnt",
            "article.create_time","article.publish_time","article.update_time","article.status",
            "draft.status as draft_status",
            "article.user_id", 
            "user.nickname as user_nickname", "user.name as user_name", "user.mobile as user_mobile", "user.headimgurl as user_headimgurl",
        
            "article.specials",
            "special.special_id", "special.name as special_name", "special.path as special_path", "special.logo as special_logo",

            "article.series",

            "article.style",

            "category",
            "tag",
        ];
        
		$select = empty($query['select']) ? $select_defaults : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


        foreach ($select as $idx=>$field) {

            if ( strpos( $field, ".") === false ) {
				$select[$idx] = "article.{$field}";
            }
            
			if ( $field == '*') {
				$getTag = true; $getCategory = true;
			}

			if ( $field == 'category' ) {
				$getCategory = true;
				unset( $select[$idx] );
			}

			if ( $field == 'tag' ) {
				$getTag = true;
				unset( $select[$idx] );
			}
        }

        
        // 按用户ID查找
		if ( array_key_exists('user_id', $query)  && !empty($query['user_id']) ) {
			$qb->where("article.user_id", "=", $query['user_id']);
        }

		// 按文章ID查找
		if ( array_key_exists('article_ids', $query)  && !empty($query['article_ids']) ) {
			$aids = is_string($query['article_ids']) ? explode(',', $query['article_ids']) : $query['article_ids'];
			if ( !empty($aids) ) {
				$qb->whereIn('article.article_id', $aids );
			}
		}

		// 按关键词查找 
		if ( array_key_exists('keyword', $query) && !empty($query['keyword']) ) {
			$qb->where(function ( $qb ) use($query) {
			   	$qb->where("article.title", "like", "%{$query['keyword']}%");
			   	$qb->orWhere("article.keywords", "like", "%{$query['keyword']}%");
			   	$qb->orWhere("t.name", '=',  $query['keyword']);  // 或者标签符合关键词
			});
		}

		// 按关键词词组查找 ( 非搜索 )
		if ( array_key_exists('keywords', $query) && !empty($query['keywords']) ) {

			// 过滤空值
			$keywords = is_string($query['keywords']) ? explode(',', $query['keywords']) : $query['keywords'];
			foreach( $keywords as $idx=>$key ) {
				$keywords[$idx] = trim($key);
				if  ( empty($keywords[$idx]) ) {
					unset($keywords[$idx]);
				}
			}

			if ( !empty($keywords) ) {
				$qb->where(function ( $qb ) use($keywords) {
					$qb->whereIn("t.name", $keywords); // 标签符合关键词
					foreach( $keywords as $idx=>$keyword ) {
                        $qb->orWhere("article.title", "like", "%{$keyword}%");  // 名称符合关键词
						$qb->orWhere("article.keywords", "like", "%{$keyword}%");  // 名称符合关键词
					}
				});
			}
        }
        

		// 按时间范围
		if ( array_key_exists('period', $query) && !empty($query['period']) ) {
			$now = empty($query['now']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($query['now']) );
			$now_t = strtotime( $now );

			switch ($query['period']) {

				case '24hours':  // 24小时
					$from = date('Y-m-d H:i:s', strtotime("-24 hours",$now_t));
					$qb->where('article.publish_time' , '<=', $now );
					$qb->where('article.publish_time' , '>=', $from );
					break;

				case 'daily' : // 当天
					$from = date('Y-m-d 00:00:00', $now_t);
					$end = date('Y-m-d 23:59:59', $now_t);
					$qb->where('article.publish_time' , '<=', $end );
					$qb->where('article.publish_time' , '>=', $from );
					break;

				case '7days': // 7天
					$end = date('Y-m-d 00:00:00', $now_t);
					$end_t = strtotime($end);
					$from = date('Y-m-d 23:59:59',  strtotime("-7 days",$end_t));
					$qb->where('article.publish_time' , '<=', $end );
					$qb->where('article.publish_time' , '>=', $from );
					break;

				case 'weekly': // 本周
					$from = date('Y-m-d 00:00:00', strtotime('-1 Monday',$now_t));
					$from_t = strtotime($from);
					$end = date('Y-m-d 23:59:59',  strtotime("+1 Weeks",$from_t));
					$qb->where('article.publish_time' , '<=', $end );
					$qb->where('article.publish_time' , '>=', $from );
					break;

				case '30days': // 30天
					$end = date('Y-m-d 00:00:00', $now_t);
					$end_t = strtotime($end);
					$from = date('Y-m-d 23:59:59',  strtotime("-30 days",$end_t));
					$qb->where('article.publish_time' , '<=', $end );
					$qb->where('article.publish_time' , '>=', $from );
					break;

				case 'monthly': // 本月
					$from = date('Y-m-01 00:00:00', $now_t);
					$from_t = strtotime($from);
					$end = date('Y-m-d 23:59:59',  strtotime("+1 Month",$from_t));
					$qb->where('article.publish_time' , '<=', $end );
					$qb->where('article.publish_time' , '>=', $from );
					break;

				case 'yearly':  // 今年
					$from = date('Y-01-01 00:00:00', $now_t);
					$end = date('Y-12-31 23:59:59',  $now_t);
					$qb->where('article.publish_time' , '<=', $end );
					$qb->where('article.publish_time' , '>=', $from );
					break;

				default: // 无限
					# code...
					break;
			}
        }

        // 发布时间大于
        if ( array_key_exists('begin', $query) && !empty($query['begin']) ) {
            $begin = date("Y-m-d H:i:s", strtotime($query['begin']) );
            $qb->where('article.publish_time' , '>=', $begin );
        }

        // 发布时间小于
        if ( array_key_exists('end', $query) && !empty($query['end']) ) {
            $end = date("Y-m-d H:i:s", strtotime($query['end']) );
            $qb->where('article.publish_time' , '<=', $end );
        }

        // 更新时间大于
        if ( array_key_exists('update_begin', $query) && !empty($query['update_begin']) ) {
            $begin = date("Y-m-d H:i:s", strtotime($query['update_begin']) );
            $qb->where('article.update_time' , '>=', $begin );
        }

        // 更新时间大于
        if ( array_key_exists('update_end', $query) && !empty($query['update_end']) ) {
            $begin = date("Y-m-d H:i:s", strtotime($query['update_end']) );
            $qb->where('article.update_time' , '<=', $begin );
        }

		// 按分类ID查找
		if ( array_key_exists('category_ids', $query)  && !empty($query['category_ids']) && empty($query['subcategory_ids']) ) {
			$cids = is_string($query['category_ids']) ? explode(',', $query['category_ids']) : $query['category_ids'];
			if ( !empty($cids) ) {
				$qb->whereIn('ac.category_id', $cids );
			}
        }
        
        // 按二级分类ID查找
		if ( array_key_exists('subcategory_ids', $query)  && !empty($query['subcategory_ids']) ) {
			$cids = is_string($query['category_ids']) ? explode(',', $query['subcategory_ids']) : $query['subcategory_ids'];
			if ( !empty($cids) ) {
				$qb->whereIn('ac.category_id', $cids );
			}
		}

		// 按分类名称查找
		if ( array_key_exists('categories', $query)  && !empty($query['categories']) ) {
			$cates = is_string($query['categories']) ? explode(',', $query['categories']) : $query['categories'];
			if ( !empty($cates) ) {
				$qb->whereIn('c.name', $cates );
			}
		}

		// 按标签查找
		if ( array_key_exists('tags', $query)  && !empty($query['tags']) ) {
			$tags = is_string($query['tags']) ? explode(',', $query['tags']) : $query['tags'];
			if ( !empty($tags) ) {
				$qb->whereIn('t.name', $tags );
			}
        }

        // 按用户专栏ID查找(用户专栏)
        if ( array_key_exists('special_id', $query)  && !empty($query['special_id']) ) {
            $qb->where('special.special_id' , '=', $query['special_id'] );
        }

        // 按专栏查询数据
		if ( array_key_exists('special_ids', $query)  && !empty($query['special_ids']) ) {
			$sids = is_string($query['special_ids']) ? explode(',', $query['special_ids']) : $query['special_ids'];
			$sids = array_filter($sids);
          	if ( !empty($sids) ) {
				$qb->where(function ( $qb ) use($sids) {
					foreach( $sids as $sid ) {
						$qb->orWhere('article.specials', "like", "%{$sid}%");  // 名称符合关键词
					}
				});
			}
		}
        
        // 按系列查询数据
		if ( array_key_exists('series_ids', $query)  && !empty($query['series_ids']) ) {
			$sids = is_string($query['series_ids']) ? explode(',', $query['series_ids']) : $query['series_ids'];
			$sids = array_filter($sids);
          	if ( !empty($sids) ) {
				$qb->where(function ( $qb ) use($sids) {
					foreach( $sids as $sid ) {
						$qb->orWhere('article.series', "like", "%{$sid}%");  // 名称符合关键词
					}
				});
			}
		}
        
        // 排除文章数据
		if ( array_key_exists('exclude_article_ids', $query)  && !empty($query['exclude_article_ids']) ) {
			$exids = is_string($query['exclude_article_ids']) ? explode(',', $query['exclude_article_ids']) : $query['exclude_article_ids'];
			$exids = array_filter($exids);
          	if ( !empty($exids) ) {
				$qb->whereNotIn('article.article_id', $exids);
			}
		}

        // 必须包含视频
		if ( $query['video_only'] ) {
			$qb->whereNotNull('article.videos');
			$qb->where('article.videos', '<>', "");
			$qb->where('article.videos', '<>', "[]");
        }
        
        // 必须包含主题图片
		if ( $query['thumb_only'] ) {
			$qb->whereNotNull('article.cover');
			$qb->where('article.cover', '<>', "");
		}

		// 排序: 最新发表
		if ( array_key_exists('order', $query) && !empty($query['order'])  ) {
            $orders = explode(",", $query['order']);
            foreach( $orders as $order_str ) {
                $order_str = trim( $order_str );
                $order = explode(' ', $order_str);
                $order[1] = !empty($order[1]) ? trim($order[1]) : 'asc';
                $qb->orderBy(trim($order[0]), $order[1] );
            }

        // 排序: 默认排序
		} else {
            $qb->orderBy("article.stick", "desc");
            $qb->orderBy("article.priority", "asc");
            $qb->orderBy("article.publish_time", "desc");
        }
		
		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

        
		// 查询文章列表
        $response = $qb ->select($select)
                        ->distinct()
                        ->pgArray( $perpage, ['article._id'], 'page', $page );

		if ( $_GET['debug'] == 1 ) {
			$response['_sql'] = $qb->getSql();
			$response['_query'] = $query; 
		}

        // 格式化数据
        $special_ids = []; $specials = [];
        $series_ids = []; $series = [];
		foreach ($response['data'] as & $rs ) {
            
            $this->format($rs);

            // 专栏ID集合
            if ( is_array($rs["specials"]) && !empty($rs["specials"]) ) {
                $special_ids = array_merge($special_ids, $rs["specials"]);
            }

            // 系列ID集合
            if ( is_array($rs["series"]) && !empty($rs["series"]) ) {
                $series_ids = array_merge($series_ids, $rs["series"]);
            }
        }

        // 查找专栏信息
        if( !empty($special_ids) ) {
            $spe = new Special();
            $spe_selected = ["special.special_id", "special.name as special_name", "special.path as special_path", "special.logo as special_logo"];
            $specials = $spe->getInBySpecialId( $special_ids, $spe_selected);
        }

        // 查找系列信息
        if( !empty($series_ids) ) {
            $ser = new Series();
            $ser_selected = ["series.slug","series.name","series.status"];
            $series = $ser->getInBySeriesId( $series_ids, $ser_selected);
        }

        // 读取分类数据
        if ( $getCategory ) {
            $this->withCategory($response['data']);
        }

        // 读取标签数据
        if ( $getTag ) {
            $this->withTag($response['data']);
        }

        $response["specials"] = $specials;
        $response["series"] = $series;
		return $response;
	}


	/**
	 * + getInByArticleId 方法
	 * @return [type] [description]
	 */
	function getInByArticleId( $article_ids, $select=null, $order=[] ) {

        // 默认读取数据项
        if( $select == null ) {
            $select = [
                "article.article_id", "article.title", "article.summary", 
                "article.origin", "article.origin_url", "article.author", 
                "article.seo_title","article.seo_keywords","article.seo_summary",
                "article.cover", "article.images", "article.thumbs", "article.thumbs", "article.videos",
                "article.stick", "article.priority",
                "article.view_cnt", "article.like_cnt", "article.dislike_cnt", "article.comment_cnt",
                "article.create_time","article.publish_time","article.update_time","article.status",
                "article.user_id", 
                "user.nickname as user_nickname", "user.name as user_name", "user.mobile as user_mobile", "user.headimgurl as user_headimgurl",
        
                "article.specials",
                "special.special_id", "special.name as special_name", "special.path as special_path", "special.logo as special_logo",
    
                "article.series"
            ];
        }

        if ( is_string($select) ){
            $select = explode( ",", $select ); 
        }

        foreach( $select as & $field ) {
            if ( strpos($field, ".") === false ) {
                $field = "article.{$field}";
            }
        }


		$article_ids = is_array($article_ids) ? $article_ids  : [];

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
        array_push($select, "article.article_id");
        $select = array_filter(array_map('trim', $select));

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_article as article", "{none}")->query();
        $qb->whereIn('article_id', $article_ids)
           ->leftJoin("xpmsns_user_user as user", "article.user_id", "=", "user.user_id")
           ->leftJoin("xpmsns_pages_special as special", "article.user_id", "=", "special.user_id")
        ;
        
        // 排序
        if ( empty($order) ) {
            if (!empty($article_ids) ) {
                $ids_ordered = implode(',', $article_ids);
                $qb->orderByRaw("FIELD(article_id, $ids_ordered)");
            }
        } else {
            foreach ($order as $field => $des ) {
                if ( empty($des) ){
                    $des = "asc";
                }
                $qb->orderBy( $field, $des );
            }
        }

        $qb->select( $select );
		$data = $qb->get()->toArray(); 

     
		// 分类
		$qbc = Utils::getTab('xpmsns_pages_article_category as ac', "{none}")->query();
        $qbc->leftJoin('xpmsns_pages_category as c' , 'ac.category_id', '=', 'c.category_id');
        $qbc->whereIn('ac.article_id', $article_ids);
        $datac = $qbc->select('ac.article_id', 'c.category_id', 'c.slug', 'c.name', 'c.fullname', 'c.project', 'c.page', 'c.parent_id', 'c.isnav', 'c.param','c.priority')->get()->toArray();
        $cates = [];
        foreach ($datac as $c ) {
        	$id  = $c['article_id'];
        	if ( empty($cates[$id])) {
        		$cates[$id] = [$c];
        	} else {
        		array_push( $cates[$id], $c );
        	}
        }

        // 标签
        $qbt = Utils::getTab('xpmsns_pages_article_tag as at', "{none}")->query();
        $qbt->leftJoin('xpmsns_pages_tag as t' , 'at.tag_id', '=', 't.tag_id');
        $qbt->whereIn('at.article_id', $article_ids);
        $datat = $qbt->select('at.article_id', 't.tag_id','t.name')->get()->toArray();
        $tags = [];
        foreach ($datat as $t ) {
        	$id  = $t['article_id'];
        	$tags[$id][] = $t;
        }

		$map = [];
		foreach ($data as & $rs ) {
			$this->format($rs);
            // 增加分类
            if ( is_array($tags[$rs['article_id']]) ) {
                $rs['tags'] = array_column($tags[$rs['article_id']],"name");
                $rs['tag_ids'] = array_column($tags[$rs['article_id']],"tag_id");
            }

            $rs['tag'] = empty($tags[$rs['article_id']]) ?  [] : $tags[$rs['article_id']];
            $rs['category'] = empty($cates[$rs['article_id']]) ?  [] : $cates[$rs['article_id']];
            $rs['category_last'] = last($rs['category']);
            $rs['tag_last'] = last($rs['tag']);
			$map[$rs['article_id']] = $rs;
        }

		return $map;
	}




	// 采集
	function collect( $data ) {

		$url = $data['url'];
		if ( empty($url) ) {
			throw new Excp("请提供目标网页地址", 404, ['data'=>$data]);
		}

		$spider = new Spider(['host'=>Utils::getHome(Utils::getLocation())]);
		$page = $spider->crawl($url);
		$data = array_merge($page, $data);

		if ( empty($data['category']) ) {
			$cate = new Category();
			$data['category'] = $cate->getVar('category_id', "WHERE slug='default' LIMIT 1");
		}

		if( !empty($data['publish_date']) ) {
			$time = strtotime($data['publish_date']);
			$data['publish_time'] = date('Y-m-d H:i:s', $time);
			unset($data['publish_date']);
		}

		if ( empty($data['status']) ) {
			$data['status'] = ARTICLE_UNPUBLISHED;
		}

		return $this->save($data);
	}


	/**
	 * 从公众号(订阅号/服务号)下载文章
	 * $this
	 */
	function downloadFromWechat(  $appid, $offset = null ) {

		$perpage = 20;
		$cate = new Category();
		$settings = $cate->wechat();
		$c = $settings[$appid];

		if ( empty($c) ){
			throw new Excp('配置信息错误', 400, ['appid'=>$appid]);	
		}

		$wechat = new Wechat([
			"appid" => $c['appid'],
			'secret' => $c['secret']
		]);

		
		$count = $wechat->countMedia();
		if ( is_a($count, '\Xpmse\Err') ) { //  抛出异常
			throw new Excp($count->message, $count->code, $count->extra);
		}


		$offset = ($offset === null) ? intval($c['offset']) : intval( $offset );
		$total = intval($count['news_count']) - $offset;
		$page = ceil($total / $perpage );

		for( $i=0; $i<$page; $i++ ) {
			$from = $perpage * $i + $offset;
			$resp = $wechat->searchMedia($from, $perpage, 'news');
			foreach ($resp['item'] as $item ) {
				foreach ($item['content']['news_item'] as $idx=>$media ) {
					$this->importWechatMedia($c, $item['media_id'], $media, $idx );
				}
			}
		}

		// 更新 offset
		$cate->updateBy('category_id', ['category_id'=>$c['category_id'], 'wechat_offset'=>intval($count['news_count'])]);
		return $this;
	}


	/**
	 * 上传文章到公众号
	 * 
	 * @param  string $appid      [description]
	 * @param  [type] $article_id [description]
	 * @param  [type] $create     [description]
	 * @return [type]             [description]
	 */
	function uploadToWechat(  $appid, $article_id, $create=null ) {

		return $this;
	}




	/**
	 * 导入媒体文章
	 * @param  string  $media_id 公众平台 media_id
	 * @param  array   $media    公众平台图文消息数据结构
	 * @param  integer $index    item index ( 一篇图文，包含多个index )
	 * @return $this
	 */
	function importWechatMedia(  $c,  $media_id,  $media, $index = 0 ) {

		$outer_id = $media_id . $index;
		$rows = $this->query()->where("outer_id", '=', $outer_id)->limit(1)->select('article_id')->get()->toArray();
		$rs = current($rows);
		if ( isset($rs['article_id'] )) {
			$data['article_id'] = $rs['article_id'];
		}

		$this->delta_render->loadByHTML($media['content']);
		$delta = $this->delta_render->delta();
		$images =  $this->delta_render->images();
		$data['delta'] = $delta;
		$data['content'] = $media['content'];
		$data['images'] = $images;
		$data['title'] = $media['title'];
		$data['author'] = $media['author'];
		$data['cover'] = $media['thumb_url'];
		$data['summary'] = $media['digest'];
		$data['origin_url'] = $media['content_source_url'];
		$data['status'] = ARTICLE_PENDING;
		$data['category'] = $c['category_id'];
		$data['outer_id'] = $media_id . $index;
		$data['sync'] = [
			$c['appid'] => [
				"media_id" => $media_id,
				"index" => $index,
				"url" => $media['url'],
				"thumb_media_id" => $media['thumb_media_id'],
				"update_at" => time()
			]
		];

		$rs = $this->save( $data );
		$imgcnt = count($rs['images']);
		$article_id = $rs['article_id'];
		$t = new \Xpmse\Task;
		$task_id = $t->run('下载文章图片: ' . $rs['title'], [
			"app_name" => "xpmsns/pages",
			"c" => 'article',
			'a' => 'realdownloadimages',
			'data'=> [
				"article_id" => $rs['article_id'],
				"status" => ARTICLE_UNPUBLISHED,
				"task_id" => $task_id
			]
		], function( $status, $task, $job_id, $queue_time, $resp ) use( $imgcnt, $article_id ) {
			try {
				$art = new Article;
				$art->save([
					'article_id'=>$article_id,
					'status' => 'unpublished'
				]);
			} catch(Excp $e){
			} catch(Exception $e){}

			$t = new \Xpmse\Task;
			if ( $status == 'failure') {
				$t->progress($task['task_id'], 100,  "下载图片失败 文章 {$article_id} 图片（{$imgcnt}）");
			} else {
				$t->progress($task['task_id'], 100,  "下载图片成功 文章 {$article_id} 图片（{$imgcnt}）" );
			}
		});
	}


	/**
	 * HtmlToDelta
	 * @param  [type] $rs [description]
	 * @return [type]     [description]
	 */
	function contentToDelta( & $rs ) {
		$rs['content'] = empty($rs['content']) ? "" : $rs['content'];
		$this->delta_render->loadByHTML($rs['content']);
		$delta = $this->delta_render->delta();
		$images =  $this->delta_render->images();
		$videos =  $this->delta_render->videos();
		$rs['delta'] = $delta;
		$rs['images'] = $images;
		$rs['videos'] = $videos;
	}

	/**
	 * 插入视频
	 * @param  string $url
	 * @return
	 */
	function insertVideo( $url, & $rs ) {

		try { $v = $this->media->saveVideoUrl( $url ); } catch( Excp $e ) { return ;}
		$rs['delta'] = !is_array($rs['delta']) ?  [] : $rs['delta'];
		$rs['delta']['ops'] = !is_array( $rs['delta']['ops']) ? [] :  $rs['delta']['ops'];
		$vdelta[0]['insert'] = "\n";
		$vdelta[1]['insert']['cvideo'] = $v;
		$vdelta[2]['insert'] = "\n";
		$rs['delta']['ops'] = array_merge($vdelta, $rs['delta']['ops']);

		$this->delta_render->load($rs['delta']);
		$rs['videos'] =  $this->delta_render->videos();
		$rs['content'] = $this->delta_render->html();
	}



	function downloadImages( $article_id, $status=null ) {

		$rs = $this->load($article_id);

		if ( empty($rs) ) {
			throw new Excp("文章不存在( {$article_id})", 404, ['article_id'=>$article_id, $status=>$status] );
		}

		$delta = !is_array($rs['delta']) ? ["ops"=>[]] : $rs['delta'];
		$images = $rs['images'];
		$new_images = []; $new_images_map =[];

		// 抓取图片
		foreach ($images as $idx=>$img ) {
			$src = $img['src'];
			$ext = $this->media->getExt( $src );
			
			if ( !in_array($ext, ['png', 'jpg', 'gif', 'peg']) ) {
				$ext = 'png';
			}

			try {
				$nimg = $this->media->uploadImage($src, $ext, false);
				$new_images_map[$src] = $new_images[$idx] = [
					'src' => $nimg['url'],
					"ratio" => $img['data-ratio'],
					"s" => $img['data-s'],
					"type"=> $img['data-type'],
					"url" => $nimg['url'], 
					"origin"=> $nimg['origin'],
					"path" => $nimg['path'], 
					"media_id" => $nimg['media_id']
				];
			} catch( Excp $e ){}

		}

		// 替换图片
		foreach ( $delta['ops'] as $idx => $dt  ) {
			if ( is_array($dt['insert']) && isset($dt['insert']['cimage']) ) {
				$src = $dt['insert']['cimage']['src'];
				if ( !empty($new_images_map[$src]) ) {
					$delta['ops'][$idx]['insert']['cimage'] = $new_images_map[$src];
				}
			}
		}

		$updateData = [
			"article_id" => $article_id,
			"delta" =>$delta,
			"images" => $new_images
		];

		if ( !empty($status) ) {
			$updateData['status'] = $status;
		}

		// 替换 Cover 图片
		if ( !empty($rs['cover'])  && substr($rs['cover']['path'],0,4) == 'http' ) {
			$ext = $this->media->getExt($rs['cover']['path'] );
			if ( !in_array($ext, ['png', 'jpg', 'gif', 'peg']) ) {
				$ext = 'png';
			}
			try {
				$rs = $this->media->uploadImage($rs['cover']['path'], $ext, false);
			} catch( Excp $e ) {
				 $rs['path'] = '';	
			}
			$updateData['cover'] = $rs['path'];
		} else if ( !empty($rs['cover']) && substr($rs['cover']['path'],0,1) != '/' ) {
			$updateData['cover'] = '';
		}

		return $this->save( $updateData );
	}




	/**
	 * 保存文章 
	 */
	function save( $data ) {

		if ( is_string($data['tag']) ) {
			$data['tag'] = explode(',', $data['tag']);
        }
        

		// 按栏目名称设定栏目
		if (array_key_exists('category_names',$data)){
			$cates = $this->saveCategoryByName($data['category_names']);
			$data['category'] = array_column($cates, 'category_id');
		}

		// 按栏目ID 设定栏目
		if ( is_string($data['category'])) {
			$data['category'] = explode(',', $data['category']);
		}

		// 处理时间( 旧版 & 废弃)
		// if ( !empty($data['publish_date']) ) {

		// 	if ( empty($data['publish_time']) ) {
		// 		$data['publish_time'] = date('H:i:s');
		// 	}

		// 	$data['publish_time'] = str_replace('@', '', $data['publish_time']);
		// 	$data['publish_time'] = str_replace('时', ':', $data['publish_time']);
		// 	$data['publish_time'] = str_replace('分', ':', $data['publish_time']);
		// 	$data['publish_time'] = $data['publish_date'] . ' ' . $data['publish_time'];

		// } else if ( array_key_exists('publish_date', $data ) && empty($data['publish_date']) ) {
		// 	// $data['publish_date'] = date('Y-m-d');
		// 	$data['publish_time'] = date('Y-m-d H:i:s');
        // }
        
        // 处理日期时间格式
        if ( !empty($data['publish_datetime']) ) {
            $dateformat = !empty($data['dateformat']) ? trim($data['dateformat']) : 'Y-m-d H:i';
            $publish_time = \DateTime::createFromFormat($dateformat, $data['publish_datetime']);
            $data['publish_time'] = $publish_time->format('Y-m-d H:i:s');
        }


        

        // 旧版 废弃
		// if ( !empty($data['delta']) && empty($data["parse_content"]) ) {
			
		// 	$this->delta_render->load($data['delta']);

		// 	// 生成文章正文
		// 	$data['content'] = $this->delta_render->html();

		// 	// 获取图片信息
		// 	$data['images'] = $this->delta_render->images();

		// 	// 获取图形信息
		// 	$data['videos'] = $this->delta_render->videos();

		// 	// 生成小程序正文
		// 	$data['ap_content'] = $this->delta_render->wxapp();
		// }

        // 新版处理正文内容
        if ( !empty($data["content"]) ) {
            $c = new Content(["title"=> $data["title"]] );
            $c->loadContent($data["content"]);

            // 自然语言处理
            $nlp = $this->option->get("article/npl/api");			
			if ( $nlp != null  && !empty($nlp['config']['appid']) ) {
				$c->withNLP( $nlp['config'], $nlp['engine'] );
            }

            // 关键词摘要
            $data["keywords"] = $data["seo_keywords"] = implode(",", $c->keywords());
            $data["summary"] =  $data["seo_summary"] = !empty( $data["summary"]) ? $data["summary"] : $c->summary();

            // 解析内容
            $data["desktop"] =  $c->html(); // 桌面 HTML
            $data["mobile"] =  $c->mobile(); // 手机H5
            $data["wxapp"] =  $c->wxapp(); // 微信小程序
            $data["app"] =  $c->app(); // 手机应用

            // 资源内容
            $data['images'] = $c->images();
            $data['videos'] = $c->videos();
            $data['audios'] = $c->audios();
            $data['attachments'] = $c->attachments();

        }


		// 添加文章
		if ( empty($data['article_id']) ) {
		
		// if ( true ) {  // 4 debug

			if ( empty($data['create_time']) ) {
				$data['create_time'] = date('Y-m-d H:i:s');
			}

			try { $data = $this->create( $data ); } catch( Excp $e ){
				if  ( !empty($data['outer_id']) ) {
					$data = $this->saveBy( 'outer_id', $data );	
				}
			}

			unset($data['created_at']);
			unset($data['deleted_at']);
			unset($data['updated_at']);
			unset($data['_id']);
			$data['draft_status'] = DRAFT_APPLIED;
			$data['category'] = $this->getCategories($data['article_id'], 'category.category_id' );
			$data['tag'] = $this->getTags($data['article_id'], 'tag.name' );

		} else { 
			$data['draft_status'] = DRAFT_UNAPPLIED;
		}

		if ( empty($data['update_time']) ) {
			$data['update_time'] = date('Y-m-d H:i:s');
		}


		// 保存到草稿表
        $article_id = $data['article_id'];
        

		$data['history'] = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);
		if ( is_array($data['history']) && !is_null($data['history']['history'])) {
			unset( $data['history']['history']);
		}

		// 生成预览链接 (废弃)
		// $data['preview'] = $this->previewLinks( $article_id, $data['category']);

		// 历史记录
		if ( empty($data['history'])) {
			try { $draft = $this->article_draft->create( $data ); } catch( Excp $e ){
				if  ( !empty($data['outer_id']) ) {
					$draft = $this->article_draft->saveBy( 'outer_id', $data );	
				}
			}
		} else {
			$draft = $this->article_draft->updateBy( 'article_id', $data ); 
		}

		
		// 发布文章
		if ( $data['status'] == ARTICLE_PUBLISHED ) {
			return $this->published( $article_id );
		}

		// 转为草稿
		if ( $data['status'] == ARTICLE_UNPUBLISHED || empty($data["status"]) ) {
			return $this->unpublished( $article_id );	
		}

		// 转为PENDING
		if ( $data['status'] == ARTICLE_PENDING ) {
			return $this->pending( $article_id );	
        }
        
        // 转为审核中
        if ( $data["status"] == ARTICLE_AUDITING ) {
            return $this->auditing( $article_id );
        }
        
        // 读取草稿
        if ( empty($draft["status"]) ) {
            $draft["status"] = $this->isPublished($article_id) ? ARTICLE_PUBLISHED : ARTICLE_UNPUBLISHED;
        }

		return $draft;
	}

	/**
	 * 文章是否发布
	 * @param  [type]  $article_id [description]
	 * @return boolean             [description]
	 */
	function isPublished( $article_id ) {

		$data = $this->query()
		  			 ->where("article_id", '=', $article_id)
					 ->where('status', '=', 'published')
					 ->limit(1)
					 ->select('article_id')
					 ->get()->toArray();
		if ( empty($data) ) {
			return false;
		}

		return true;
	}

	/**
	 * 删除
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function rm( $article_id ){

		$time = date('Y-m-d H:i:s');
		$resp = $this->updateBy( 'article_id', [
			"deleted_at"=>$time, 
			"article_id"=>$article_id,
			"outer_id" => NULL
		]);

		if ( $resp['deleted_at'] === $time ){
			$ret = $this->article_draft->updateBy( 'article_id', [
				"deleted_at"=>$time, 
				"article_id"=>$article_id,
				"outer_id" =>NULL
			]);
		}

		return ( $resp && $ret);
	}

	
	/**
	 * 提取文章
	 * @param  int  $article_id 文章ID
	 * @param  boolean $draft 为true 代表优先从草稿中提取
	 * @return 
	 */
	function load( $article_id, $draft = true ) {

		if ( $draft === true ) {

			$qb =$this->article_draft->query()->where('article_id', '=', $article_id)->limit(1)->select('*');
			$rows = $qb->get()->toArray();
			$rs = current($rows);

			if ( !empty($rs) ) {

                $this->format( $rs, false);
                
                // 读取类目
                // $rs['category'] = $this->getCategories($article_id,"category.category_id","name","fullname","project","page","parent_id","priority","hidden","param" );
                if ( is_array($rs['category']) ) {
                    $rs['category_last'] = end($rs['category']);
                    $rs['category_ids'] =  $rs['category'];
                }
        
                // $rs["tag"] = $this->getTags($article_id, 'tag.tag_id', 'name', 'param');
                if ( is_array($rs["tag"]) ){
                    $rs["tags"] =  $rs["tag"];
                }
        
                $rs['preview'] = $this->previewLinks( $article_id, $rs['category_ids']);  // 生成预览链接

                if ( empty($rs["status"]) ) {
                    $rs["status"] = $this->isPublished($article_id) ? ARTICLE_PUBLISHED : ARTICLE_UNPUBLISHED;
                }

				return $rs;
			}
		}

		// 如果没有草稿，则提取草稿
		return $this->saveAsDraft( $article_id );
	}


    /**
     * 格式化数据
     * @param array $article 文章结构体
     * @param bool $readonly 是否只读，默认值为 true
     * @return $article
     */
	function format( & $article , $readonly=true ) {

        // (废弃 )
		// if ( !empty($article["content"]) ) {

		// 	$article["content"] = str_replace("\n", "", $article['content']);
		// 	$article["content"] = str_replace("\t", "", $article['content']);
		// 	$article["content"] = str_replace("\r", "", $article['content']);

		// 	$render = new \Mina\Delta\Render;
		// 	$article['ap_content'] = $render->loadByHTML($article["content"])->wxapp();
				
        //     // === 解析媒体数据 
        //     if ( !empty($article['delta']) ) {
        //         $mdUtils = new \Mina\Delta\Utils;
        //         $html = $mdUtils->load($article['delta'])->convert()->render();
        //         $article["images"] = $mdUtils->images();
        //         $article["videos"] = $mdUtils->videos();
        //         $article["files"] = $mdUtils->files();
        //     }
		// }

		// // 提取关键字(废弃 )
		// if ( !$readonly &&
        //      array_key_exists('keywords', $article) && 
		// 	 array_key_exists('title', $article) && 
		// 	 array_key_exists('status', $article) && 
		// 	 array_key_exists('article_id', $article) && 
		// 	 empty($article['keywords']) ) {
		// 	$article['keywords'] = $this->keywords($article['title'], $article['content'] );
		// 	$this->save([
		// 		'article_id' => $article['article_id'],
		// 		'keywords' => $article['keywords'],
		// 		'status' => $article['status']
		// 	]);
		// }

		// // 提取SEO关键字 (废弃 )
		// if ( !$readonly && 
        //     array_key_exists('seo_keywords', $article) && 
		// 	 array_key_exists('title', $article) && 
		// 	 array_key_exists('status', $article) && 
		// 	 array_key_exists('article_id', $article) && 
		// 	 empty($article['seo_keywords']) ) {
		// 	$article['seo_keywords'] = $this->keywords($article['title'], $article['content'] );
		// 	$this->save([
		// 		'article_id' => $article['article_id'],
		// 		'seo_keywords' => $article['seo_keywords'],
		// 		'status' => $article['status']
		// 	]);
		// }

		// // 提取摘要(废弃 )
		// if ( array_key_exists('summary', $article) && 
		// 	 array_key_exists('content', $article) && 
		// 	 !empty( $article['content']) && 
		// 	 empty($article['summary']) ) {
		// 	 $article['summary'] = $this->summary($article['content']);
		// }

		// // 提取SEO摘要(废弃 )
		// if ( array_key_exists('seo_summary', $article) && 
		// 	 array_key_exists('content', $article) && 
		// 	 !empty( $article['content']) && 
		// 	 empty($article['seo_summary']) ) {
		// 	 $article['seo_summary'] = $this->summary($article['content']);
		// }

        // // 发布时间(废弃 )
		// if ( !empty($article['publish_time']) ) {
		// 	$time = strtotime($article['publish_time']);
		// 	$article['publish_datetime'] = date('Y-m-d H:i:s', $time);
        //     $article['publish_time'] = null;
        //     $article['publish_date'] = null;
		// 	if ( $time > 0 ) {
		// 		$article['publish_time'] = date('@ H时i分', $time);
		// 		$article['publish_date'] = date('m/d/Y', $time);
		// 	}
		// } else if ( array_key_exists('publish_time', $article))  {
        //     $article['publish_datetime'] = null;
        //     $article['publish_time'] = null;
        //     $article['publish_date'] = null;
        // }
        
        // 发布系列
		if ( array_key_exists('series', $article)  && is_array($article['series']) && count($article['series']) > 0) {
			$article['series_param'] = implode(',', $article['series']);
        }

        // 标签
        if ( array_key_exists('tag', $article) ){
            if ( is_array($article["tag"]) ){
                $article["tags"] = array_column($article["tag"], "name");
            }
        }
        
        // 状态
        if ( array_key_exists('status', $article ) && !empty($article['status']) ) {
            
			$article["_status_types"] = [
                STATUS_UNPUBLISHED => [
		  			"value" => STATUS_UNPUBLISHED,
		  			"name" => "草稿",
		  			"style" => "danger"
		  		],
		  		STATUS_PENDING => [
		  			"value" => STATUS_PENDING,
		  			"name" => "同步中",
		  			"style" => "warning"
		  		],
		  		STATUS_AUDITING => [
		  			"value" => STATUS_AUDITING,
		  			"name" => "审核中",
		  			"style" => "warning"
		  		],
		  		STATUS_UNAPPLIED => [
		  			"value" => STATUS_UNAPPLIED,
		  			"name" => "待更新",
		  			"style" => "warning"
		  		],
		  		STATUS_PUBLISHED => [
		  			"value" => STATUS_PUBLISHED,
		  			"name" => "已发布",
		  			"style" => "success"
		  		],
            ];
            $status = $this->status( $article["status"], $article["draft_status"]);
			$article["_status_name"] = "status";
            $article["_status"] = $article["_status_types"][$status];
            
		}

        // 更新文件字段
        $this->__fileFields( $article, ["cover", "thumbs", "images","videos","audios", "attachments", "user_headimgurl", "special_logo"]);
		$article['home'] = $this->host;
		return $article;
	}



	/**
	 * 保存为草稿
	 * @param  string  $article_id 文章ID
	 * @param  boolean $override  为true 代表覆盖现有信息
	 * @return
	 */
	function saveAsDraft( $article_id, $override = false ) {

		if( $override !== true ) {
			$rs = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);
			if ( !empty($rs) ) {
				throw new Excp("草稿已存在( {$article_id})", 403, ['article_id'=>$article_id, $override=>$override] );
			}
		}

		$rs = $this->getLine("WHERE article_id=?", ['*'], [$article_id]);
		if ( empty( $rs) ) {
			throw new Excp("文章不存在( {$article_id})", 404, ['article_id'=>$article_id, $override=>$override] );
        }
    

		$rs['category'] = $this->getCategories($article_id,"category.category_id","name","fullname","project","page","parent_id","priority","hidden","param" );
        if ( is_array($rs['category']) ) {
            $rs['category_last'] = end($rs['category']);
            $rs['category_ids'] =  array_column($rs["category"], "category_id");
        }

        $rs["tag"] = $this->getTags($article_id, 'tag.tag_id', 'name', 'param');
		if ( is_array($rs["tag"]) ){
            $rs["tags"] =  array_column($rs["tag"], "name");
        }

		$rs['history'] = [];
		$rs['preview'] = $this->previewLinks( $article_id, $rs['category_ids']);  // 生成预览链接
		$rs['draft_status'] = DRAFT_APPLIED;  // 标记草稿与文章同步

		$data =  $this->article_draft->updateBy( 'article_id', $rs );
		$this->format( $data, false);
		return $data;
	}




	/**
	 * 发布文章
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function published( $article_id ) {
		
        $draft = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);

		if ( !empty($draft) ) {    
			$draft['draft_status'] = DRAFT_APPLIED;
			$draft['links'] = $this->links( $article_id ); // 生成链接地址
            $this->autoFill($draft);
            unset( $draft["_id"]);
			$draft = $this->article_draft->updateBy('article_id', $draft);
		
		} else {  // 更新文章状态 （ 这个逻辑应该优化 )
            $draft = $this->getLine("WHERE article_id=?", ['*'], [$article_id]);
            unset( $draft["_id"]);
			$this->autoFill($draft);
			$draft['links'] = $this->links( $article_id ); // 生成链接地址
		}

        $draft['status'] = ARTICLE_PUBLISHED; // 文章ID 更新为已发布
		return $this->updateBy('article_id', $draft );
	}


	/**
	 * 自动tian'cho
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function autoFill( & $data ) {
		// 提取摘要
		if ( empty(trim($data['summary'])) && !empty($data['content']) ) {
			$data['summary'] = $this->summary( $data['content']);
		}

		// 提取关键词
		if ( empty(trim($data['keywords'])) && !empty($data['title']) ) {
			$data['keywords'] = $this->keywords( $data['title'], $data['content'] );
		}

		// SEO TITLE
		if ( empty( $data['seo_title']) ) {
			 $data['seo_title'] = $data['title'];
		}

		// SEO SUMMARY
		if ( empty( $data['seo_summary']) ) {
			 $data['seo_summary'] = $data['summary'];
		}

		// SEO keywords
		if ( empty( $data['seo_keywords']) ) {
			 $data['seo_keywords'] = $data['keywords'];
		}
	}



	/**
	 * 生成物料 (废弃)
	 * @param  [type] $article [description]
	 * @return [type]             [description]
	 */
	function makeMaterials( $article ) {

		$article_id = $article['article_id'];
		if ( empty($article_id) ) {
			throw new Excp('制作物料失败, 参数错误', 402, ['article'=>$article]);
		}

		$param = "article_id:{$article_id}";
		$thumbs = !empty($article['thumbs']) ? $article['thumbs'] : [];
		$image = [
			"A" => $article['title'],
			"B" => $article['summary'],
			"C" => !empty($article['cover']) ? $article['cover'] : "/s/xpmsns/pages/static/defaults/950X500.png",
			"E" => $thumbs[0],
			"F" => $thumbs[1],
			"G" => $thumbs[2],
			"H" => $thumbs[3],
			"I" => $thumbs[4]
		];

		$gallerys = $this->gallerys();
		$g = new Gallery;
		$g->rmImagesByParam( $param );

		foreach ($article['links'] as $link ) {
			$title = !empty($link) ? $link['cname']  : "";
			
			$link = !empty($link) ? $link['links']['mobile']  : "https://xpmsns.com";
			$image['D'] = $link;
			$images = $g->genImageData([$image]);
			foreach ($gallerys as $rs ) {
				$resp = $g->createImages( 
					$rs['gallery_id'], 
					$images, 
					['param'=>"article_id:{$article_id}", "title"=>$title] 
				);

				foreach ($resp as $im ) {
					$image_id = $im['data']['image_id'];
					$g->makeImage($image_id);
				}
			}
		}
		
	}



	/**
	 * 取消发布文章
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function unpublished( $article_id ) {

		$this->article_draft->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_UNPUBLISHED
		]);

		return $this->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_UNPUBLISHED
		]);
	}


	/**
	 * 正在PENDING
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function pending( $article_id ) {
		$this->article_draft->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_PENDING
		]);

		return $this->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_PENDING
		]);
	}
	

    /**
     * 设定状态为审核中
     * @param string $article_id 文章ID
     * @return array 文章结构体
     */
    function auditing( $article_id ) {

        $this->article_draft->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_AUDITING
		]);

		return $this->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_AUDITING
		]);
    }



	/**
	 * 读取文章状态名称
	 * @param  [type] $status       [description]
	 * @param  [type] $draft_status [description]
	 * @return [type]               [description]
	 */
	function cstatus( $status, $draft_status = null, $map = [] ) {
		if ( empty($map) ) {
			$map = [
				STATUS_UNPUBLISHED => '草稿',
				STATUS_PENDING => '同步中',
                STATUS_AUDITING => '审核中',
                STATUS_UNAPPLIED => '待更新',
                STATUS_PUBLISHED => '已发布',
			];
		}

		$status = $this->status($status, $draft_status);
		return $map[$status];
	}


	/**
	 * 读取文章状态码
	 * 
	 * @param  string $status       文章状态 unpublished 未发布/ published 已发布/ auditing 审核中 / pending 数据尚未准备好
	 * @param  string $draft_status 草稿状态 unapplied 尚未更新/ applied 修改已更新/ auditing 审核中 / pending 数据尚未准备好
	 * @return string 状态描述码 PUBLISHED 已发布 / UNPUBLISHED 未发布 / UNAPPLIED 有修改未更新  / PENDING 数据尚未准备好 / AUDITING 审核中
	 */
	function status( $status, $draft_status = null ) {

		if ( $status == ARTICLE_UNPUBLISHED ) {  // 文章尚未发布
			return STATUS_UNPUBLISHED;
		
		} else if ( $status == ARTICLE_PENDING ) {
			return STATUS_PENDING;
		
		} else if ( $status == ARTICLE_AUDITING ) {
			return STATUS_AUDITING;
		
		}else {

			if ( $draft_status == DRAFT_UNAPPLIED ) {
				return STATUS_UNAPPLIED;
			}

			return STATUS_PUBLISHED;
		}
	}


	/**
	 * 读取文章相关图集 (即将废弃)
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function gallerys() {
		$g = new Gallery();
		$gallerys = $g->getGallerys(1, ["param"=>'article'], 5);
		return $gallerys['data'];
	}


	/**
	 * 读取文章图集图片 (即将废弃)
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function galleryImages( $article_id, $group=true ) {
		$g = new Gallery();
		$query  = ['param'=>"article_id:{$article_id}"];
		
		$images = $g->getImages(1, $query, 5);
		$data = $images['data'];
		
		if ( $group === true ) {
			$resp = [];
			foreach ( $data as $im ) {
				$title = $im['title'];
				if ( !is_array($resp[$title])) {
					$resp[$title] = [];
				}

				array_push($resp[$title], $im );
			}

			return $resp;
		}

		return $data;
	}



	/**
	 * 生成文章链接、生成二维码
	 * @param  string $article_id 
	 * @return 
	 */
	function links( $article_id,  $category = null ) {
		// $default_home = Utils::getHome( $_SERVER['HTTP_TUANDUIMAO_LOCATION']);
		$default_home = Utils::getHome();
		$uri = parse_url( $default_home);
		$default_project = Utils::getTab('project')->getVar('name', "WHERE `default`=1 LIMIT 1");
		$video =  $this->article_draft->getVar('videos', "WHERE `article_id`=? LIMIT 1", [$article_id]);

		if ( empty($default_project) ) {
			$default_project = DEFAULT_PROJECT_NAME;
		}

		$pages = [
			$default_project . DEFAULT_PAGE_SLUG, 
			$default_project . DEFAULT_PAGE_SLUG_V2
		];

		// 视频正文页
		if ( count($video)  > 0 ) {
			array_push( $pages, $default_project . DEFAULT_PAGE_SLUG_VIDEO );
		}

		if( $category === null ) {
			$category =  $this->getCategories( $article_id, 'category.category_id' );
		}

		// 根据类目信息，获取页面，并排重
		if ( !empty($category) ) {
			$cate = new Category();
			$cates = $cate->query()->whereIn('category_id', $category)->select('page', 'project')->get()->toArray();
			
			if ( !empty($cates) ) {

				foreach ($cates as $rs ) {
					$rs['project'] = !empty($rs['project']) ? $rs['project'] : $default_project;
					$rs['page'] = !empty($rs['page']) ? $rs['page'] : DEFAULT_PAGE_SLUG;
					array_push( $pages, $rs['project'] . $rs['page']);
				}

				$pages = array_unique($pages);

			}
		}

		// 读取页面详细信息
		$pages = $this->page->query()
						->leftJoin('project', 'project.name', '=', 'page.project')
						->whereIn('slug', $pages)
						->select(
							'page.cname as cname', 'page.name as name', 'page.slug as slug', 'alias', 'adapt',
							'project.name as project', 'project.domain as domain'
						)
						->get()
						->toArray();


		$page_slugs = []; $page_slugs_map = [];  $proto = $uri['scheme'] . "://";
		foreach ($pages as $idx=>$pg ) {

			if ( empty($pg['domain']) ) {
				$pg['domain'] = $uri['host'];
			}

			$pg['home'] = $proto . $pg['domain'];
			
			foreach( $pg['adapt'] as $type ) { // 处理适配页面
				$pages[$idx]['links'][$type] = $pg['slug'];
				$page_slugs[] =  $pg['slug'];
				$page_slugs_map[$pg['slug']] = $pg;
			}

			foreach( $pg['alias'] as $type => $pg_alias ) {  // 处理联合页面
				if ( $type != 'wxapp') {
					$pages[$idx]['links'][$type] = $pg_alias;
					$page_slugs[] =  $pg_alias;
				} else {
					$pages[$idx]['links'][$type] = '/' . $pg_alias . '?id=' . $article_id; 
				}
			}

			$pages[$idx]['article_id'] = $article_id;

			unset($pages[$idx]['alias'] );
			unset($pages[$idx]['adapt'] );
		}

		// 获取适配链接
		$entry_maps = $this->getEntries( $article_id, $page_slugs );


		foreach ($pages as $idx=>$pg ) {

			$page = $page_slugs_map[$pg['slug']];
			$home= $page['home'];

			$desktop = $pages[$idx]['links']['desktop'];
			if( is_string($desktop) ) {
				$pages[$idx]['links']['desktop'] = $home.$entry_maps[$desktop]['first'];
			}

			$mobile = $pages[$idx]['links']['mobile'];
			if( is_string($mobile) ) {
				$pages[$idx]['links']['mobile'] = $home.$entry_maps[$mobile]['first'];
			}

			$wechat = $pages[$idx]['links']['wechat'];
			if( is_string($wechat) ) {
				$pages[$idx]['links']['wechat'] = $home.$entry_maps[$wechat]['first'];
			}
		}

		return $pages;
	}


	/**
	 * 根据页面信息，计算入口数值
	 * @param  [type] $pages [description]
	 * @return [type]        [description]
	 */
	function getEntries(  $article_id,  $slugs ) {
		$slugs = array_unique( $slugs );
		$pages = $this->page->query()
						->whereIn('slug', $slugs)
						->select('slug','entries')
						->get()
						->toArray();

		if ( !is_array($pages) ) {
			throw new Excp('未查询到页面信息', 400, ['article_id'=>$article_id, 'pages'=>$pages]);
		}
		
		$resp = [];
		foreach ($pages as $rs ) {
			$slug = $rs['slug'];
			$resp[$slug] = ['entries'=>[], 'latest'=>''];
			$entries = $rs['entries'];
			foreach ($entries as $idx=>$entry ) {
				if ( $entry['method'] != 'GET') continue;
				$entry['router'] = preg_replace('/\{(.+)\}/', $article_id,  $entry['router']);
				$resp[$slug]['entries'][$idx] = $entry['router'];
			}

			$resp[$slug]['first'] = current($resp[$slug]['entries'] );
			$resp[$slug]['latest'] = end($resp[$slug]['entries'] );
		}

		return $resp;
	}



	/**
	 * 生成文章预览链接
	 */
	function previewLinks( $article_id,  $category = null ) {

		$pages = [DEFAULT_PAGE_SLUG];
		if( $category === null ) {
			$rs =  $this->article_draft->getLine("WHERE article_id=?", ['category'], [$article_id]);
			if ( empty($rs) ) {
				throw new Excp('草稿不存在', 400, ['article_id'=>$article_id]);
			}
			$category = $rs['category'];
		}



		// 根据类目信息，获取页面，并排重
		if ( !empty($category) ) {
			$cate = new Category();

			if ( !is_array($category) ) {
				$category = [$category];
			}

			$data = $cate->query()->whereIn('category_id', $category)->select('page')->get()->toArray();
			if ( !empty($data) ) {
				$data_pad = Utils::pad( $data, 'page');
				$pages= $data_pad['data'];
				$pages = array_unique($pages);
				foreach ($pages as $idx =>$page ) {
					if ( empty($page) ) {
						$pages[$idx] = DEFAULT_PAGE_SLUG;
					}
				}
			}
		}


		// 读取页面详细信息
		$pages = $this->page->query()
						->whereIn('slug', $pages)
						->select('cname', 'name', 'slug', 'alias', 'adapt')
						->get()
						->toArray();
		// 获取适配链接
		foreach ($pages as $idx=>$pg ) {
			
			foreach( $pg['adapt'] as $type ) { // 处理适配页面
				$pages[$idx]['links'][$type] = App::NR('article' , 'preview', ['p'=>$pg['slug'], 'id'=>$article_id]);
			}

			foreach( $pg['alias'] as $type => $pg_alias ) {  // 处理联合页面
				if ( $type != 'wxapp') {
					$pages[$idx]['links'][$type] =  App::NR('article' , 'preview', ['p'=>$pg_alias, 'id'=>$article_id]);
				} else {
					$pages[$idx]['links'][$type] = '/' . $pg_alias . '?id=' . $article_id . '&preview=1'; 
				}
			}

			$pages[$idx]['article_id'] = $article_id;
			unset($pages[$idx]['alias'] );
			unset($pages[$idx]['adapt'] );
		}

		return $pages;
	}



	/**
	 * 更新文章
	 * @param  string $data 
	 * @return [type]       [description]
	 */
	function updateBy( $uni_key, $data ) {

		if ( !isset($data['user']) ) {
			$data['user'] = App::$user['userid'];
		}

		if ( empty($data['update_time']) ) {
			$data['update_time'] = date('Y-m-d H:i:s');
		}
		
        if ( isset($data["_id"])) {
            unset($data["_id"]);
        }
		$rs = parent::updateBy( $uni_key, $data );

		if ( !empty($data['category']) ) {

			$article_id = $rs['article_id'];

			// 清除旧分类
			// $this->article_category->runsql("update {{table}} set deleted_at=? where article_id=? ", false, [
			// 	date('Y-m-d H:i:s'), 
			// 	$article_id
			// ]);


			// 添加新分类
			$category = is_array($data['category']) ? $data['category'] : [$data['category']];
			$rows = $this->article_category->query()->where('article_id', '=', $article_id)->select('category_id')->get()->toArray();
			$oldcates = array_column($rows, 'category_id');
			$removeCates = array_diff( $oldcates,$category);
			foreach ($removeCates as $cid ) {
				$this->article_category->remove( $data['article_id']. $cid, 'unique_id',false);
			}

			foreach ($category as $cid ) {
				$this->article_category->createOrUpdate([
					"article_id" => $data['article_id'],
					"category_id" => $cid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `category_id`))'
				]);
			}
		}


		if ( !empty($data['tag']) ) {

			$article_id = $rs['article_id'];

			// $time = date('Y-m-d H:i:s');
			// // 清空旧 Tag
			// $this->article_tag->runsql(
			// 	"update {{table}} set deleted_at=? where article_id=? ", fasle, 
			// 	[$time, $article_id]
			// );  
			

			$rows = $this->article_tag->query()->where('article_id', '=', $article_id)->select('tag_id')->get()->toArray();
			$oldtags = array_column($rows, 'tag_id');

			if ( is_string($data['tag']) ) {
				$data['tag'] = explode(',' , $data['tag']);
			}


			$tag = new Tag;
			$tagnames = is_array($data['tag']) ? $data['tag'] : [$data['tag']];
			$tagids = $tag->put( $tagnames );
			$removeTags = array_diff( $oldtags, $tagids);
			foreach ($removeTags as $tid ) {
				$this->article_tag->remove( $data['article_id']. $tid, 'unique_id',false);
			}

			foreach ($tagids as $tid ) {
				$this->article_tag->createOrUpdate([
					"article_id" => $data['article_id'],
					"tag_id" => $tid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `tag_id`))'
				]);
			}
		}

		return $rs;
	}


	/**
	 * 添加文章
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function create( $data ) {

		$data['article_id'] = $this->genId();

		if ( !isset($data['user']) ) {
			$data['user'] = App::$user['userid'];
		}


		// $draft = $data;
		$rs = parent::create( $data );  // 创建文章记录

		if ( !empty($data['category']) ) {

			$category = is_array($data['category']) ? $data['category'] : [$data['category']];

			foreach ($category as $cid ) {
				$this->article_category->createOrUpdate([
					"article_id" => $data['article_id'],
					"category_id" => $cid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `category_id`))'
				]);
			}
		}

		if ( !empty($data['tag']) ) {

			if ( is_string($data['tag']) ) {
				$data['tag'] = explode(',' , $data['tag']);
			}

			$tag = new Tag;
			$tagnames = is_array($data['tag']) ? $data['tag'] : [$data['tag']];
			$tagids = $tag->put( $tagnames );
			foreach ($tagids as $tid ) {
				$this->article_tag->createOrUpdate([
					"article_id" => $data['article_id'],
					"tag_id" => $tid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `tag_id`))'
				]);
			}

		}
		
		return $rs;
		
	}


	/**
	 * 读取一组文章分类
	 * @param  array  $article_ids 文章ID列表
	 * @param  string $field      [description]
	 * @return [type]             [description]
	 */
	function getCategoriesGroup( $article_ids, $field="*") {

		$c = new Category;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}

		$args = array_merge(['article_category.article_id as aid'], $args);
		$qb = $c->query()
		     ->leftJoin('article_category', 'article_category.category_id', '=', 'category.category_id')
		     ->whereIn( "article_category.article_id", $article_ids )
		     ->where("status", '=', "on")
		     ->select($args)
		     ->limit( 50 );

		 // echo $qb->getSql();

		$rows = $qb ->get()->toArray();

		  

		if ( empty($rows) ) return [];

		$resp = [];
		foreach ($rows as $idx=>$rs ) {

			$aid = $rs['aid']; unset( $rs['aid']);
			if ( !is_array($resp[$aid]) ) $resp[$aid] = [];

			if ( count($rs) == 1) { //如果仅取一个数值，则降维
				array_push($resp[$aid], end($rs));
			} else {
				array_push($resp[$aid], $rs);
			}
		}

		return $resp;
	}


	/**
	 * 读取一组文章标签信息
	 * @param  array  $article_ids 文章ID列表
	 * @param  string | array ...$field 读取字段
	 * @return array 标签数组
	 */
	function getTagsGroup( $article_ids, $field="*") {

		$t = new Tag;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}

		$args = array_merge(['article_tag.article_id as aid'], $args);
		$rows = $t->query()
		     ->rightJoin('article_tag', 'article_tag.tag_id', '=', 'tag.tag_id')
		     ->whereIn( "article_tag.article_id",  $article_ids )
		     ->select($args)
		     ->limit( 50 )
		     ->get()->toArray();


		if ( empty($rows) ) return [];

		$resp = [];
		foreach ($rows as $idx=>$rs ) {

			$aid = $rs['aid']; unset( $rs['aid']);
			if ( !is_array($resp[$aid]) ) $resp[$aid] = [];

			if ( count($rs) == 1) { //如果仅取一个数值，则降维
				array_push($resp[$aid], end($rs));
			} else {
				array_push($resp[$aid], $rs);
			}
		}

		return $resp;

	}


	function saveCategoryByName( $category_names ) {
		$c = new Category;
		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}

		$cates = [];
		$category_names = is_string($category_names) ? explode(',', $category_names): $category_names;

		foreach ($category_names as $cname ) {
			$cates[] = $c->save([
				'name'=>$cname, 
				'fullname'=>$cname
			]);
		}

		return $cates;
	}


	/**
	 * 读取一篇文章分类信息
	 * @param  int $article_id 文章ID
	 * @param  string | array ...$field 读取字段
	 * @return array 分类数组
	 */
	function getCategories( $article_id, $field="category.*") {

		$c = new Category;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}


		$resp = $rows = $c->query()
		     ->rightJoin('article_category', 'article_category.category_id', '=', 'category.category_id')
		     ->where( "article_category.article_id", '=', $article_id )
		     ->where("status", '=', "on")
		     ->select($args)
		     ->limit( 50 )
		     ->get()->toArray();


		if ( empty($resp) ) return [];

		if  (count(end($rows)) == 1) {  // 如果仅取一个数值，则降维
			$resp = [];
			foreach ($rows as $idx=>$rs ) {
				array_push( $resp, end($rs) );
			}
		}

		return $resp;
	}

	/**
	 * 读取一篇文章标签信息
	 * @param  int $article_id 文章ID
	 * @param  string | array ...$field 读取字段
	 * @return array 分类数组
	 */
	function getTags( $article_id, $field="*") {

		$t = new Tag;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}


		$resp = $rows = $t->query()
		     ->rightJoin('article_tag', 'article_tag.tag_id', '=', 'tag.tag_id')
		     ->where( "article_tag.article_id", '=', $article_id )
		     ->select($args)
		     ->limit( 50 )
		     ->get()->toArray();

		if ( empty($resp) ) return [];

		if  (count(end($rows)) == 1) {  // 如果仅取一个数值，则降维
			$resp = [];
			foreach ($rows as $idx=>$rs ) {
				array_push( $resp, end($rs) );
			}
		}

		return $resp;

	}


	/**
	 * 根据标题和内容分析关键词
	 * @param  [type] $title   [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	function keywords( $title, $content ) {

		// 自然语言处理引擎
		if ( $this->npl == null ) {
			$nplapi = $this->option->get("article/npl/api");			
			if ( $nplapi == null || empty($nplapi['config']['appid']) ) {
				return null;
			}
			$this->npl = new NPL( $nplapi['config'], $nplapi['engine'] );
		}

		$content = trim(strip_tags($content));
		$resp = $this->npl->keyword( $title, $content );
		if ( isset($resp['error_code']) ) {
			return null;
		}

		if ( empty($resp['items']) ) {
			return $this->keywordsByLexer( $title  );
		}
		$keywords = array_column($resp['items'], 'tag');
		return implode(',', $keywords);
	}

	function keywordsByLexer( $title ) {
		
		if ( $this->npl == null ) {
			$nplapi = $this->option->get("article/npl/api");			
			if ( $nplapi == null || empty($nplapi['config']['appid']) ) {
				return null;
			}
			$this->npl = new NPL( $nplapi['config'], $nplapi['engine'] );
		}

		$resp = $this->npl->lexer( $title );
		
		if( empty($resp['items']) || !is_array($resp['items']) ) {
			return null;
		}


		// 只保留名词 
		// "n"=>"普通名词","f"=>"方位名词	","s"=>"处所名词","t"=>"时间名词",
		// "nr"=>"人名	","ns"=>"地名","nt"=>"机构团体名","nw"=>"作品名",
		// "nz"=>"其他专名"
		$allowpos = ['n', 'f', 's', 't', 'nr', 'ns', 'nt', 'nw', 'nz'];
		$keywords = [];
		foreach ($resp['items'] as $it ) {
			if ( in_array($it['pos'], $allowpos) ) {
				array_push($keywords, $it['item']);
			}
		}

		return implode(',', $keywords);
	}



	/**
	 * 根据内容分析摘要
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	function summary( $content ) {
		$content = trim(strip_tags($content));
		$content = str_replace('。', '.', $content);
		$arrs = explode('.', $content);
		$summary = current($arrs);
		$summary = mb_substr(trim($summary), 0, 54, 'UTF-8');
		return $summary;
	}

	function __clear() {
		Utils::getTab('article_category', "xpmsns_pages_")->dropTable();
		Utils::getTab('article_tag', "xpmsns_pages_")->dropTable();
		Utils::getTab('article_draft', "xpmsns_pages_")->dropTable();
		$this->dropTable();
	}

}