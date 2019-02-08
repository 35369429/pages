<?php

namespace Xpmsns\pages\Api;




use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;
use \Xpmse\Wechat as Wechat;


/**
 * 文章API接口
 */
class Article extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $option = [] ) {
		parent::__construct( $option );
		$this->allowMethod('get', ["PHP",'GET'])
		     ->allowQuery('get',  ['article_id', 'select'])
		     ->allowMethod('search', ["PHP",'GET'])
		     ->allowQuery('search',  [
		     	"select",
		     	'category', 'orCategory', 'inCategory',
		     	'categoryId','orcategoryId','incategoryId',
		     	'tag','orTag', 'inTag',
		     	'origin', 'orOrign',
		     	'title', 'orTitle',
		     	'project','orProject',
		     	'praram','orParam',
		     	'publish_time','orPublish_time','endPublish_time','orEndPublish_time',
		     	'update_time','orUpdate_time','endUpdate_time','orEndUpdate_time',
		     	'order',
		     	'page','perpage'
		     ]);
	}

    /**
     * 用于蜘蛛更新文章
     */
	protected function spiderUpdate( $data ) {
		
		$appid = $_SERVER["HTTP_AUTHORIZATION_APPID"];
		$secret = $_SERVER["HTTP_AUTHORIZATION_SECRET"];
		$params = $this->params["__params"];
		$this->authSecret($appid, $secret);

		$article = new \Xpmsns\Pages\Model\Article;
		return $article->spiderUpdate( $this->params );
    }
    

    /**
     * 发布文章接口 
     */
    protected function save( $query, $data ) {

        // 读取用户资料
        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        // ? 验证用户登录等级(用户名密码/短信登录有该接口访问权限)

        // 读取用户ID 
        $data["user_id"] = $user_id;
        
        // 许可字段清单
		$allowed =  [
            "article_id", // 文章ID 
            "user_id",  // 用户ID
            "author",   // 作者
            "title",  // 标题
            "keywords", // 关键词
            "summary",  // 摘要
            
            "cover",  // 封面 (JSON String)
            "thumbs", // 主题图 (JSON String)

            "images",  // 图集文章  (JSON String)
            "videos",  // 视频文章  (JSON String)
            "audios",  // 音频文章  (JSON String)

            "author",  // 作者
            "origin",  // 来源
            "origin_url",  // 来源网址
            "content",  //  正文
            
            "category",  // 类目(多个用"," 分割)
            "tag",       // 标签 (多个用"," 分割)
            "series",    // 系列
            "specials",  // 所属专栏 

			"policies",  // 访问策略
			"policies_detail",  // 访问策略详情  (JSON String)
            "status",  // 状态
            
            "seo_title",  // 标题
            "seo_keywords", // 关键词
            "seo_summary",  // 摘要

            "coin_view",  // 访问所需积分
            "money_view", // 访问所需金额

            "stick",    // 是否置顶状态
            "priority",  // 访问优先级
        ];
        
		$data = array_filter(
			$data,
			function ($key) use ($allowed) {
				return in_array($key, $allowed);
			},
			ARRAY_FILTER_USE_KEY
        );

        // 读取用户专栏信息
        $spe = new \Xpmsns\pages\Model\Special();
        $special = $spe->getByUserId( $user_id );
        

        // 读取权限配置
        $option = new \Xpmse\Option('xpmsns/pages');
        $ugc_policies = $option->get("article/ugc/policies");
        $policies = $ugc_policies["create"];

        // 校验权限
        if ($policies == "not-allowed" ) {
            throw new Excp("无接口访问权限", 403, ["policies"=>$policies]);
        }

        // 仅专栏可以访问
        if( in_array($policies, ["special-only", "audit-special-only"]) && empty($special) ) {
            throw new Excp("无接口访问权限", 403, ["policies"=>$policies]);
        }

        // 需要审核, 将权限设定为可访问
        $art = new \Xpmsns\pages\Model\Article();

        if( in_array($policies, ["audit-all", "audit-contribute-only", "audit-special-only"]) && 
            in_array($data["status"], ["published"]) ) {
            $data["status"] = "auditing";
        }

        // 处理封面
        if ( !empty($data['cover']) ) {
			$data['cover'] = json_decode($data['cover'], true);
        }

        return $art->save( $data );
    }


    protected function getUserArticle( $query, $data ) {

        // 读取用户资料
        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        // ? 验证用户登录等级(用户名密码/短信登录有该接口访问权限)

        // 读取用户ID 
        $data["user_id"] = $user_id;
        $article_id = $query["article_id"];
        if ( empty($article_id) ) {
            throw new Excp("未提供文章ID", 402, ["query"=>$query]);
        }

        $art = new \Xpmsns\pages\Model\Article();
        $article = $art->load( $article_id );

        if ( $article["user_id"] != $user_id ) {
            throw new Excp("没有该文章的权限", 403, ["user_id"=>$user_id, "article.user_id"=>$article["user_id"]]);
        }

        return $article;
    }


    /**
     * 修改文章接口
     */
    protected function update( $data ) {

    }


	/**
	 * 查询推荐信息 (即将废弃)
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function recommend( $query ) {
		
		$section =  !empty($query['section']) ? explode(',',$query['section']) : ['hot', 'focus', 'articles','dry'];
		$data = [];

		$query['order'] = 'publish_time desc';

		// 首页推荐的内容
		if ( in_array('articles', $section) ) {
			$query['param'] = '首页';
			$data['articles'] = $this->search($query);
		}

		// 焦点图
		if ( in_array('focus', $section) ) {
			$query['param'] = '焦点';
			$query['perpage'] = 10;
			$query['page'] = 1;
			$data['focus'] = $this->search($query);
		}

		// 热点
		if ( in_array('hot', $section) ) {
			$query['param'] = '热文';
			$query['perpage'] = 10;
			$query['page'] = 1;
			$data['hot'] = $this->search($query);
		}

		// 行业干货
		if ( in_array('dry', $section) ) {
			$query['param'] = '行业干货';
			$query['perpage'] =6;
			$query['page'] = 1;
			$data['dry'] = $this->search($query);
        }
        
        $data["__warning"] = "本方法将在1.7版本中废弃, 请慎重调用";
		return $data;
	}



	/**
	 * 查询文章列表
	 * @@@ 具体查询实现，应在Model中 @@@
	 *
	 * 读取字段 select 默认 *
	 *
	 *    示例:  ["*"] /["article_id", "title" ....] / "*" / "article_id,title"
	 *    许可值: "*","article_id","cover","title","author","origin","origin_url","summary","seo_title",
	 *    		"seo_keywords","seo_summary","publish_time","update_time","create_time","sync",
	 *    		"content","ap_content","draft","ap_draft","history","stick","status",
	 *    		"category", "tag"
	 * 
	 * 
	 * 查询条件
	 * 	  1. 按分类名称查询  category | orCategory | inCategory 
	 * 	  2. 按分类ID查询  categoryId | orcategoryId | incategoryId 
	 * 	  3. 按标签查询  tag | orTag | inTag 
	 * 	  4. 按来源查询  origin | orOrign
	 * 	  5. 按标题关键词查询  title | orTitle
	 * 	  6. 按项目查询   project | orProject 
	 * 	  7. 按参数标记查询  param | orParam
	 * 	  7. 按文章状态查询  status | orStatus
	 * 	  8. 按创建时间查询  publish_time | orPublish_time | endPublish_time | orEndPublish_time
	 * 	  9. 按更新时间查询  update_time  | orUpdate_time  |  endUpdate_time | orEndUpdate_time
	 * 	  
	 * 排序方式 order 默认 create_time  update_time asc, publish_time desc
	 * 
	 *    1. 按文章发布时间  publish_time
	 *    2. 按文章更新时间  update_time  
	 *    3. 按文章创建时间  create_time
	 *    4. 按置顶顺序 stick
	 *    
	 *
	 * 当前页码 page    默认 1 
	 * 每页数量 perpage 默认 50 
	 * 	
	 * 
	 * @param  array  $query [description]
	 * @return array 文章结果集列表
       * 
	 */
  
	protected function search( $query=[] ) {

		$select = empty($query['select']) ? [
			'article_id', 'cover', 'author', 'origin',"article_id","cover","title","author","origin","origin_url","summary","seo_title",
			"seo_keywords","seo_summary","publish_time","tag", "images", "thumbs", "videos","category", "stick", "audios",
			"param"
		] : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$getTag = false; $getCategory = false;
		$allowFields = ["*","article_id","cover","title","author","origin","origin_url","summary","seo_title","seo_keywords","seo_summary","publish_time","update_time","create_time","sync","content","ap_content","draft","ap_draft","history","stick","status","category", "tag", "images", "thumbs", "videos", "audios", "param"];

		foreach ($select as $idx => $field) {

			$vfield = $field; $tab = 'article'; $as = '';
			if ( strpos( $vfield, ' as ') !== false ) {
				$arr = explode(' as ', $vfield);
				if ( isset($arr[1]) ) {
					$as = " as {$arr[1]}";	
				}
				
				$arr = explode('.', $arr[0]);
				if ( isset($arr[1]) ) {
					$field = $arr[1];
					$tab = $arr[0];
				}
			}

			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query, 'arr'=>$arr]);
			}
			$select[$idx] = "{$tab}." . $field . $as ;

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

		$select[] = 'article.article_id as _aid';


		// 按子类查询
		if ( !empty($query['subcateId']) ) {
			$query['categoryId'] = $query['subcateId'];
		}

		// 按分类查询 ( 包含子分类 )
		if ( !empty($query['categoryId']) ) {

			$c = new \Xpmsns\Pages\Model\Category;
			$cids = [];

			$cids = $c->getCids( $query['categoryId']);
			if ( count($cids) > 1 ) {
				unset(  $query['categoryId'] );
				$query['inCategoryId'] = join(",", $cids);
			}
		}

		// if ( !empty($query['c']) ) {
		// 	if ( is_numeric($query['c']) ) {
		// 		$query['categoryId'] = intval($query['c']);
		// 	} else {
		// 		$query['category'] = trim($query['c']);
		// 	}
		// }

		if ( !empty($query['orC']) ) {
			if ( is_numeric($query['orC']) ) {
				$query['orCategoryId'] = intval($query['orC']);
			} else {
				$query['orCategory'] = trim($query['orC']);
			}
		}

		if ( !empty($query['inC']) ) {
			if ( is_numeric($query['inC']) ) {
				$query['inCategoryId'] = intval($query['inC']);
			} else {
				$query['inCategory'] = trim($query['inC']);
			}
		}



		// Utils::out($query);


		// Order 默认参数
		$query['order'] = !empty($query['order']) ? $query['order'] : 'create_time';
		$allowOrder = ["publish_time", "update_time", "stick" , "create_time"];
		$orderList = explode(',', $query['order']);

		// 分页参数
		$query['page'] = !empty($query['page']) ? intval($query['page']) : 1;
		$query['perpage'] = !empty($query['perpage']) ? intval($query['perpage']) : 50;



		// 查询数据表
		$art = new \Xpmsns\pages\Model\Article;
		$qb = $art->query()
				  ->leftJoin("article_category as ac", 'ac.article_id', '=', 'article.article_id')
				  ->leftJoin('category as c', "c.category_id", '=', 'ac.category_id')
				  ->leftJoin("article_tag as at", 'at.article_id', '=', 'article.article_id')
				  ->leftJoin("article_draft as draft", 'draft.article_id', '=', 'article.article_id')
				  ->leftJoin("tag as t", 't.tag_id', '=', 'at.tag_id');
			;

		// echo $qb->getSql();
			
		// 设定查询条件
		$this->qb( $qb, 'c.name', 'category', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'c.category_id', 'categoryId', $query, ["and", "or", "in"] );
		$this->qb( $qb, 't.name', 'tag', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'article.origin', 'origin', $query );
		$this->qb( $qb, 'article.project', 'project', $query);
		$this->qb( $qb, 'article.status', 'status', $query );
		$this->qb( $qb, 'article.param', 'param', $query, ['and', 'or'], 'like');
		$this->qb( $qb, 'article.title', 'title', $query, ['and', 'or'], 'like' );
		$this->qb( $qb, 'article.publish_time', 'publish_time', $query, ['and', 'or'], '>=' );
		$this->qb( $qb, 'article.publish_time', 'endPublish_time', $query, ['and', 'or'], '<=' );
		$this->qb( $qb, 'article.update_time', 'update_time', $query, ['and', 'or'], '>=' );
		$this->qb( $qb, 'article.update_time', 'endUpdate_time', $query, ['and', 'or'], '<=' );


		// 处理排序
		foreach ($orderList as $order) {
			$order = trim($order);
			$orderArr = preg_split('/[ ]+/', $order );
			$orderArr[1] = !empty($orderArr[1]) ? $orderArr[1] : 'desc';

			if ( !in_array($orderArr[0], $allowOrder)) {
				throw new Excp(" order 参数错误 ({$orderArr[0]} 非法字段)", 400, ['query'=>$query]);
			}

			// echo 'article.'. $orderArr[0] .  "  , " . $orderArr[1]; 

			$qb->orderBy('article.'.$orderArr[0],$orderArr[1]);
		}
		
		
		// echo $qb->getSql();


		// 查询数据
		$qb->select( $select )->distinct();
		$resultData = $qb->pgArray($query['perpage'],['article.article_id'], 'page', $query['page'] );
		// $resultData = $result->toArray();
		


		// 处理结果集
		$data = $resultData['data'];

		foreach ($data as & $rs ) {
			$art->format( $rs );
		}

		if ( $query['debug'] == 1) {
			$resp['_sql'] = $qb->getSQL();
			$resp['_query'] = $query;
		}

		$resp['curr'] = $resultData['current_page'];
		$resp['perpage'] = $resultData['per_page'];
		
		$resp['next'] = ( $resultData['next_page_url'] === null ) ? false : intval( str_replace('/?page=', '',$resultData['next_page_url']));
		$resp['prev'] = ( $resultData['prev_page_url'] === null ) ? false : intval( str_replace('/?page=', '',$resultData['prev_page_url']));

		$resp['from'] = $resultData['from'];
		$resp['to'] = $resultData['to'];
		
		$resp['last'] = $resultData['last_page'];
		$resp['total'] = $resultData['total'];
		$resp['data'] = $data;

		if ( empty($data) ) {
			return $resp;
		}

		$pad = [];
		if ( $getCategory ) {
			// echo 'getCategory';
			$pad = Utils::pad($data, '_aid');
			$categories = $art->getCategoriesGroup($pad['data'], "category.category_id","name","fullname","project","page","parent_id","priority","hidden","param" );
		}

		if ( $getTag ) {
			if ( empty($pad) ) {
				$pad = Utils::pad($data, '_aid');
			}
			$tags = $art->getTagsGroup($pad['data'], 'tag.tag_id', 'name', 'param' );
		}


		// 处理结果集数据
		// $resp['data'] = [];
		foreach ($resp['data'] as & $rs ) {
			$aid = $rs['_aid'];unset($rs['_aid']);

			if ( $getCategory) {
				$rs['category'] = $categories[$aid];
				if ( is_array($rs['category']) ) {
					$rs['category_last'] = end($rs['category']);
				}
			}
			if ( $getCategory) {
				$rs['tag'] = $tags[$aid];
			}
	
			// $resp['data'][$idx] = $rs;
		}


		$arr  =  [];
		if(!empty($resp['last'])){
			for ($i=1; $i <= $resp['last']; $i++) { 
				$arr[$i]  = $i;
			}
		}

		$resp['arr'] = $arr;
		$resp['end'] = $resultData['end'];
		$resp['frontend'] = $resultData['frontend'];
		$resp['frontstart'] = $resultData['frontstart'];
        
        // 关联用户收藏数据
        $user = \Xpmsns\User\Model\User::info();
        if ( !empty($user["user_id"]) && $query["withfavorite"] == 1 ) {
            $art->withFavorite( $resp["data"], $user["user_id"]);
        }

        // 关联用户赞赏数据
        if ( !empty($user["user_id"]) && $query["withagree"] == 1 ) {
            $art->withAgree( $resp["data"], $user["user_id"]);
        }

		return $resp;
	}



	/**
	 * 读取文章详情信息
	 * @param  array  $query Query 查询
	 *                   int ["articleId"]  文章ID
	 *                   
	 *          string|array ["select"] 读取字段  
	 *          			 示例:  ["*"] /["article_id", "title" ....] / "*" / "article_id,title"
	 *          		     许可值: "*","article_id","cover","title","author","origin","origin_url","summary","seo_title",
	 *          		     		"seo_keywords","seo_summary","publish_time","update_time","create_time","sync",
	 *          		     		"content","ap_content","draft","ap_draft","history","stick","status",
	 *          		     		"category", "tag"
	 *                    
	 * @return Array 文章数据
	 * 
	 */
	protected function get( $query=[] ) {

		// 验证数值
		if ( !preg_match("/^([0-9]+)/", $query['articleId']) ) {
			throw new Excp(" articleId 参数错误", 400, ['query'=>$query]);
		}

		$article_id = $query['articleId'];
		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$getTag = false; $getCategory = false;
		$allowFields = ["*","article_id","cover","title","author","origin","origin_url","summary","seo_title","seo_keywords","seo_summary","publish_time","update_time","create_time","sync","content","ap_content","draft","ap_draft","history","stick","status","category", "tag"];

		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query]);
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

		$art = new \Xpmsns\pages\Model\Article;
		$rs = $art->getLine("WHERE article_id=:article_id LIMIT 1", $select, ["article_id"=>$article_id]);
		if ( empty($rs) ) {
			throw new Excp("文章不存在", 404,  ['query'=>$query]);
		}

		$art->format($rs);

		if( $getCategory) {
			$rs['category'] = $art->getCategories($article_id,"category.category_id","name","fullname","project","page","parent_id","priority","hidden","param" );

			if ( is_array($rs['category']) ) {
				$rs['category_last'] = end($rs['category']);
			}
		}

		if ( $getTag ) {
			$rs['tag'] = $art->getTags($article_id, 'tag.tag_id', 'name', 'param');
        }

        try {  // 标记为已打开
            $response = $art->opened( $article_id ); 
        } catch(Excp $e) { $e->log(); }

        if ( $response === true ) {
            try {  // 触发打开文章行为
                \Xpmsns\User\Model\Behavior::trigger("xpmsns/pages/article/open", [
                    "article_id"=>$article_id,
                    "inviter" => \Xpmsns\User\Model\User::inviter(),
                    "time"=>time()
                ]);
            } catch(Excp $e) { $e->log(); }
        }        
		return $rs;
    }
    

    /**
     * 标记为离开文章(一般为当浏览器关闭/小程序/APP页面切换时调用)
     * @param string $articleId 文章ID
     */
    protected function leave( $query ){
        // 验证数值
		if ( !preg_match("/^([0-9]+)/", $query['articleId']) ) {
			throw new Excp(" articleId 参数错误", 400, ['query'=>$query]);
        }
        
        $article_id = $query['articleId'];
        $art = new \Xpmsns\pages\Model\Article;
        // 标记为关闭并记录阅读时长
        $duration = $art->closed( $article_id );

        try {  // 触发关闭文章行为
            \Xpmsns\User\Model\Behavior::trigger("xpmsns/pages/article/close", [
                "article_id"=>$article_id,
                "inviter" => \Xpmsns\User\Model\User::inviter(),
                "duration" => $duration,
                "time"=>time()
            ]);
        } catch(Excp $e) { $e->log(); }

        return $duration;
    }



	protected function qrcode( $query ) {
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/image';
		M('Media')->qrcode($_GET, $image );
		header('Content-type: image/png');
		if (isset($_GET['name'])) {
			header("Content-Disposition: attachment; filename=\"{$_GET['name']}.png\"");
		}
		echo $image;
	}


	/**
	 * 微信分享签名
	 */
	protected  function wechat($query=[]){

		$conf = Utils::getConf();
		$eftconf = $conf['_type'][2];
		if ( empty($eftconf) ) {
			$eftconf =  $conf['_type'][1];
		}

		$wxconf = current($eftconf);
		$appid = $query['appid'];
		if ( empty($appid) && !empty($conf['_map'][$appid]) ) {
			$wxconf = $conf['_map'][$appid];
		}

		if ( empty($wxconf) ) {
			return ["code"=>404,"message"=>"未发现有效公众号配置"];
		}

		$wechat = new Wechat($wxconf);

		try {
			// 自动获取地址 
			return $wechat->getSignature( $query['url'], $wxconf['appid'], $wxconf['secret']);	
		} catch( Excp $e ) {
			return ["code"=>500,"message"=>$e->getMessage()];
		}
		
	}
}