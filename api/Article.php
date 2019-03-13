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
     * 管理员接口: 读取文章(用于编辑)
     */
    protected function staffArticleDetail( $query, $data ) {

        // 读取用户资料
        $staff = \Xpmse\User::info();
        $user_id = $staff["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        // ? 验证用户登录等级(用户名密码/短信登录有该接口访问权限)

        // 读取用户ID 
        $article_id = $query["article_id"];
        if ( empty($article_id) ) {
            return [];
        }

        $art = new \Xpmsns\pages\Model\Article();
        $article = $art->load( $article_id );

        // 校验文章权限 ()
        // if ( $article["user_id"] != $user_id ) {
        //     throw new Excp("没有该文章的权限", 403, ["user_id"=>$user_id, "article.user_id"=>$article["user_id"]]);
        // }

        return $article;
    }

    /**
     * 管理员接口: 删除文章
     */
    protected function staffRemove( $query, $data ) {

        // 读取用户资料
        $staff = \Xpmse\User::info();
        $user_id = $staff["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("管理员尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        // ? 校验管理员权限

        $article_id = $data["article_id"];
        if ( empty($article_id) ) {
            throw new Excp("未提供待删除的文章ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $art = new \Xpmsns\pages\Model\Article();
        $resp = $art->rm($article_id, 'article_id');

		if ( $resp === false ){
			throw new Excp("删除失败 {$article_id}" , 500, ['resp'=>$resp]);
		}

        return ["code"=>0, "message"=>"删除成功"];
    }

    /**
     * 管理员接口: 发布文章
     */
    protected function staffSave( $query, $data ) {

        // 读取用户资料
        $staff = \Xpmse\User::info();
        $user_id = $staff["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("管理员尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        // ? 校验管理员权限

        //  读取管理员ID
        $data["staff_id"] = $data["user"] = $user_id;

        $art = new \Xpmsns\pages\Model\Article();

        // 处理封面
        if ( !empty($data['cover']) ) {
			$data['cover'] = json_decode($data['cover'], true);
        }

        // 默认访问策略
        if ( !array_key_exists("policies", $data) ) {
			$data["policies"] = "public";
        }

        // 默认文章置顶
        if ( !array_key_exists("stick", $data) ) {
			$data["stick"] = 0;
        }

        // 默认打赏策略
        if ( !array_key_exists("policies_reward", $data) ) {
			$data["policies_reward"] = "closed";
        }

        // 默认手动排序
        if ( empty($data["priority"]) ) {
			$data["priority"] = 99999;
        }

        return $art->save( $data );
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
            "policies_comment",  //  评论许可策略  opened/closed/follower-only
            "policies_reward", // 打赏许可策略  opened/closed
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
        if( in_array($policies, ["special-only", "audit-special-only"]) && $special["status"] != "on" ) {
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

        // 默认访问策略
        if ( !array_key_exists("policies", $data) ) {
			$data["policies"] = "public";
        }

        // 默认文章置顶
        if ( !array_key_exists("stick", $data) ) {
			$data["stick"] = 0;
        }

        // 默认打赏策略
        if ( !array_key_exists("policies_reward", $data) ) {
			$data["policies_reward"] = "closed";
        }

        // 默认手动排序
        if ( empty($data["priority"]) ) {
			$data["priority"] = 99999;
        }

        // 保存专栏信息
        if ( $special["status"] == "on" ) {
            $data["specials"] = [
                $special["special_id"]
            ];
        }

        return $art->save( $data );
    }


    /**
     * 读取用户的文章(用于编辑)
     */
    protected function userArticleDetail( $query, $data ) {

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
            return [];
        }

        $art = new \Xpmsns\pages\Model\Article();
        $article = $art->load( $article_id );

        if ( $article["user_id"] != $user_id ) {
            throw new Excp("没有该文章的权限", 403, ["user_id"=>$user_id, "article.user_id"=>$article["user_id"]]);
        }

        return $article;
    }


    /**
     * 读取用户的文章 (用于我的文章呈现)
     */
    protected function userArticles( $query, $data ){

        // 读取用户资料
        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }


        // 查找文章
        $query = array_merge( $query, $data );
        $query["user_id"] = $user_id;
        $art = new \Xpmsns\pages\Model\Article();

        return $art->search( $query );
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
  
	protected function search( $query=[], $data=[] ) {

        $data = array_merge( $query, $data );
        $art = new \Xpmsns\pages\Model\Article;
        $response = $art->search( $data );
         
        // 关联用户收藏数据
        $user = \Xpmsns\User\Model\User::info();
        if ( !empty($user["user_id"]) && $query["withfavorite"] == 1 ) {
            $art->withFavorite( $response["data"], $user["user_id"]);
        }
 
        // 关联用户赞赏数据
        if ( !empty($user["user_id"]) && $query["withagree"] == 1 ) {
            $art->withAgree( $response["data"], $user["user_id"]);
        }

        return $response;
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

        if ( empty($query["articleId"]) ) {
            $query["articleId"] = $query["article_id"];
        }

		// 验证数值
		if ( !preg_match("/^([0-9]+)/", $query['articleId']) ) {
			throw new Excp(" articleId 参数错误", 400, ['query'=>$query]);
		}

        $article_id = $query['articleId'];
		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

        
        

		// 验证 Select 参数
		$getTag = false; $getCategory = false;
		$allowFields = ["*","article_id","user_id","specials","cover","title","author","origin","origin_url","summary","seo_title","seo_keywords","seo_summary","publish_time","update_time","create_time","sync","content","ap_content","draft","ap_draft","history","stick","status","category", "tag"];

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
        
        // content 类型
        $content_type = empty($query["content_type"]) ? "desktop" : $query["content_type"];
        unset( $secret["content"]);
        array_push($select, "{$content_type} as content");

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
        
        // 读取专栏信息
        $rs['special'] = [];
        if( !empty($rs["user_id"]) ) {
            $spe = new \Xpmsns\Pages\Model\Special();
            $rs['special'] = $spe->getByUserId($rs["user_id"], ["special_id", "name", "path", "summary", "status", "logo"]);
            
            if ( !empty($rs["special"]) && $rs['special']["status"] == "on" ) {
                $art->withSpecial( $rs,$rs['special']["special_id"], [$article_id] );
            } else {
                $art->withUser( $rs,$rs["user_id"], [$article_id] );
            }
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