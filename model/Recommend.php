<?php
/**
 * Class Recommend 
 * 推荐数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-02-15 13:00:10
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


class Recommend extends Model {


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
	 * 推荐数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
        $this->table('recommend'); // 数据表名称 xpmsns_pages_recommend
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_pages_recommend:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

       
    }
    


    // @KEEP BEGIN
    
    /**
     * 查询推荐内容
     * @param array $query 查询条件
     * @return array 符合条件的 Items/Articles/Albums/Goods/Questions/Answers
     */
    function contents( $query ) {
        $this->buildQuery( $query );

        // 根据用户喜好推荐
        if ( $query["recommend"]  ) {
            return $this->recommend( $query );
        }

        

        switch( $query["ctype"] ) {
            case "article":
            
                return $this->articles( $query );
                break;
            
            case "album":
                return $this->albums( $query );
                break;
            
            case "event":
                return $this->events( $query );
                break;
            
            case "goods":
                return $this->goods( $query );
                break;

            case "question":
                return $this->questions( $query );
                break;
            
            case "answer":
                return $this->answers( $query );
                break;  
            
            case "fulltext":
                return $this->fulltext( $query );
                break;    
            default:
                return $this->fulltext( $query );
                break;
        }

        throw new Excp( "错误的查询条件", 402, ["query"=>$query]);
    }


    /**
     * 解析查询条件
     */
    private function buildQuery( & $query ) {
        
        $q = [
            "type" => trim($query["type"]),
            "ctype" => trim($query["ctype"]),
            "recommend" => $query["bigdata_engine"],

            "period" => trim($query["period"]),
            "thumb_only"=> $query["thumb_only"],
            "video_only"=> $query["video_only"],
            "audio_only"=> $query["audio_only"],
            "keywords" => trim($query["keywords"]),

            "series_ids" => array_filter($query["series"]),
            "special_ids" => array_filter($query["specials"]),
            "topic_ids"=> array_filter($query["topics"]),
            "category_ids"=> array_filter($query["categories"]),

            "user" => $query["user"],
            "withfavorite" => $query["withfavorite"],
            "withagree" => $query["withagree"],
            "withrelation" => $query["withrelation"],

            "page" => intval($query["page"]),
            "perpage" => empty($query["perpage"]) ? 20 : intval($query["perpage"]),

            "ttl" => $query["ttl"],
        ];

        
        
        switch( $q["ctype"] ) {
            case "article":
                $q["article_ids"] = array_filter($query["articles"]);
                $q["exclude_article_ids"] = array_filter($query["exclude_articles"]);
                $q["select"]  = $query["article_select"];
                $q["status"]  = $query["article_status"];
                break;
            
            case "album":
                $q["album_ids"] = array_filter($query["albums"]);
                $q["exclude_album_ids"] = array_filter($query["exclude_albums"]);
                $q["select"]  = $query["album_select"];
                $q["status"]  = $query["album_status"];
                break;
            
            case "event":
                $q["event_ids"] = array_filter($query["events"]);
                $q["exclude_event_ids"] = array_filter($query["exclude_events"]);
                $q["select"]  = $query["event_select"];
                $q["status"]  = $query["event_status"];
                break;
            
            case "goods":
                $q["goods_ids"] = array_filter($query["goods"]);
                $q["exclude_goods_ids"] = array_filter($query["exclude_goods"]);
                $q["select"]  = $query["goods_select"];
                $q["status"]  = $query["goods_status"];
                break;

            case "question":
                $q["question_ids"] = array_filter($query["questions"]);
                $q["exclude_question_ids"] = array_filter($query["exclude_questions"]);
                $q["select"]  = $query["question_select"];
                $q["status"]  = $query["question_status"];
                break;
            
            case "answer":
                $q["answer_ids"] = array_filter($query["answers"]);
                $q["exclude_answer_ids"] = array_filter($query["exclude_answers"]);
                $q["select"]  = $query["answer_select"];
                $q["status"]  = $query["answer_status"];
                break;

            default:
                break;
        }

        // 处理选择字段
        if ( !empty($query["select"]) ) {
            $q["select"] = $query["select"];
        }
        
        
        // 处理内容状态
        if ( !empty($query["content_status"]) ) {
            $q["status"]  = $query["content_status"];
        }


        // 处理空值
        $q["series_ids"] =  empty($q["series_ids"]) ? null : $q["series_ids"];
        $q["special_ids"] =  empty($q["special_ids"]) ? null : $q["special_ids"];
        $q["topic_ids"] =  empty($q["topic_ids"]) ? null : $q["topic_ids"];
        $q["category_ids"] =  empty($q["category_ids"]) ? null : $q["category_ids"];

        // 处理状态数值
        if( is_array($q["status"]) ) {
            $q["status"] = array_filter(array_map('trim', $q["status"]));
            $q["status"] = implode(",", $q["status"]);
        }

        if ( is_array($q["select"]) ) {
            $q["select"] = array_filter(array_map('trim', $q["select"]));
            $q["select"] = implode(",", $q["select"]);
        }
      
        // 时间范围
        if ( !empty($query['period']) ) {
            $now = empty($query['now']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($query['now']) );
			$now_t = strtotime( $now );
			switch ($query['period']) {

				case '24hours':  // 24小时
                    $from = date('Y-m-d H:i:s', strtotime("-24 hours",$now_t));
                    $q["begin"] = $from;
                    $q["end"] = $now;
                    unset($q["period"]);
					break;

				case 'daily' : // 当天
					$from = date('Y-m-d 00:00:00', $now_t);
					$end = date('Y-m-d 23:59:59', $now_t);
					$q["begin"] = $from;
                    $q["end"] = $end;
                    unset($q["period"]);
					break;

				case '7days': // 7天
					$end = date('Y-m-d H:i:s', $now_t);
					$end_t = strtotime($end);
					$from = date('Y-m-d H:i:s',  strtotime("-7 days",$end_t));
					$q["begin"] = $from;
                    $q["end"] = $end;
                    unset($q["period"]);
					break;

				case 'weekly': // 本周
					$from = date('Y-m-d 00:00:00', strtotime('-1 Monday',$now_t));
					$from_t = strtotime($from);
					$end = date('Y-m-d 00:00:00',  strtotime("+1 Weeks",$from_t));
					$q["begin"] = $from;
                    $q["end"] = $end;
                    unset($q["period"]);
					break;

				case '30days': // 30天
					$end = date('Y-m-d H:i:s', $now_t);
					$end_t = strtotime($end);
					$from = date('Y-m-d H:i:s',  strtotime("-30 days",$end_t));
					$q["begin"] = $from;
                    $q["end"] = $end;
                    unset($q["period"]);
					break;

				case 'monthly': // 本月
					$from = date('Y-m-01 00:00:00', $now_t);
					$from_t = strtotime($from);
					$end = date('Y-m-d 00:00:00',  strtotime("+1 Month",$from_t));
					$q["begin"] = $from;
                    $q["end"] = $end;
                    unset($q["period"]);
					break;

				case 'yearly':  // 今年
					$from = date('Y-01-01 00:00:00', $now_t);
					$end = date('Y-12-31 23:59:59',  $now_t);
					$q["begin"] = $from;
                    $q["end"] = $end;
                    unset($q["period"]);
					break;

				default: // 无限
                    # code...
                    unset($q["period"]);
					break;
            }
        }


        // 排序方式
        switch ($query['orderby']) {
			case 'publish_time': 
				$q['order'] =  "{$q["ctype"]}.publish_time desc";
                break;
            case 'publish_time_asc': 
				$q['order'] =  "{$q["ctype"]}.publish_time desc";
                break; 
            case 'agree_cnt':
				$q['order'] =  "{$q["ctype"]}.agree_cnt desc";
                break; 
            case 'answer_cnt':
				$q['order'] =  "{$q["ctype"]}.answer_cnt desc";
				break; 
			case 'view_cnt':
				$q['order'] =  "{$q["ctype"]}.view_cnt desc";
				break;
			case 'like_cnt':
				$q['order'] =  "{$q["ctype"]}.like_cnt desc";
				break;
			case 'dislike_cnt':
				$query['order'] =  "{$q["ctype"]}.dislike_cnt desc";
                break;
			case 'comment_cnt':
				$q['order'] =  "{$q["ctype"]}.comment_cnt desc";
                break;
            case 'custom':
				$q['order'] = $query['order'];
				break;
			default:
				$q['order'] =  "{$q["ctype"]}.publish_time desc";
				break;
        }
        
        $query = $q;
    }

    /**
     * 查询文章 
     * @param array $query 查询条件
     * @return array 符合条件的 Articles
     */
    function articles( $query ) {
        
        $user = $query["user"];
        unset( $query["user"] );
        $art = new Article();

        // 静态查询
        if ( $query["type"]  == "static" ) {
            if ( !empty($query["select"]) ) {
                $map = $art->getInByArticleId( $query["article_ids"], $query["select"]);
            } else {
                $map = $art->getInByArticleId( $query["article_ids"] );
            }
            $rows = [];
            if( is_array( $map ) ) {
                $rows = array_values( $map );
            }

            // 关联用户收藏数据
            if ( !empty($user["user_id"]) && $query["withfavorite"] == 1 ) {
                $art->withFavorite( $rows, $user["user_id"]);
            }
    
            // 关联用户赞赏数据
            if ( !empty($user["user_id"]) && $query["withagree"] == 1 ) {
                $art->withAgree( $rows, $user["user_id"]);
            }

            // 关联用户关系
            if ( !empty($user["user_id"]) && $query["withrelation"] == 1 ) {
                \Xpmsns\User\Model\User::withRelation( $rows, $user["user_id"] );
            }
            
            return [
                "total"=>count($rows),
                "data"=>$rows
            ];
        }


        // 动态查询
        $response =  $art->search( $query );

        // 关联用户收藏数据
        if ( !empty($user["user_id"]) && $query["withfavorite"] == 1 ) {
            $art->withFavorite( $response["data"], $user["user_id"]);
        }
 
        // 关联用户赞赏数据
        if ( !empty($user["user_id"]) && $query["withagree"] == 1 ) {
            $art->withAgree( $response["data"], $user["user_id"]);
        }

        // 关联用户关系
        if ( !empty($user["user_id"]) && $query["withrelation"] == 1  && !empty($response["data"]) ) {
            \Xpmsns\User\Model\User::withRelation( $response["data"], $user["user_id"] );
        }

        return $response;
    }

    /**
     * 查询图集 
     * @param array $query 查询条件
     * @return array 符合条件的 Albums
     */
    function albums( $query ) {
    }

    /**
     * 查询商品
     * @param array $query 查询条件
     * @return array 符合条件的 Goods
     */
    function goods( $query ) {
    }

    /**
     * 查询提问
     * @param array $query 查询条件
     * @return array 符合条件的 Questions
     */
    function questions( $query ) {
        $user = $query["user"];
        unset($query["user"]);
        $que = new \Xpmsns\Qanda\Model\Question();

        // 静态查询
        if ( $query["type"]  == "static" ) {
            if ( !empty($query["select"]) ) {
                $map = $que->getInByQuestionId( $query["question_ids"], $query["select"]);
            } else {
                $map = $que->getInByQuestionId( $query["question_ids"] );
            }

            $rows = [];
            if( is_array( $map ) ) {
                $rows = array_values( $map );
            }

            // 关联用户收藏数据
            // if ( !empty($user["user_id"]) && $query["withfavorite"] == 1 ) {
            //     $que->withFavorite( $rows, $user["user_id"]);
            // }
    
            // 关联用户赞赏数据
            if ( !empty($user["user_id"]) && $query["withagree"] == 1 ) {
                $que->withAgree( $rows, $user["user_id"]);
            }

            // 关联用户关系
            if ( !empty($user["user_id"]) && $query["withrelation"] == 1 ) {
                \Xpmsns\User\Model\User::withRelation( $rows, $user["user_id"] );
            }
            
            return [
                "total"=>count($rows),
                "data"=>$rows
            ];
        }


        // 动态查询
        $response =  $que->search( $query );

        // 关联用户收藏数据
        // if ( !empty($user["user_id"]) && $query["withfavorite"] == 1 ) {
        //     $que->withFavorite( $response["data"], $user["user_id"]);
        // }
 
        // 关联用户赞赏数据
        if ( !empty($user["user_id"]) && $query["withagree"] == 1 ) {
            $que->withAgree( $response["data"], $user["user_id"]);
        }

        // 关联用户关系
        if ( !empty($user["user_id"]) && $query["withrelation"] == 1  && !empty($response["data"]) ) {
            \Xpmsns\User\Model\User::withRelation( $response["data"], $user["user_id"] );
        }
        
        return $response;
    }

    /**
     * 查询回答
     * @param array $query 查询条件
     * @return array 符合条件的 Answers
     */
    function answers( $query ) {
    }

    /**
     * 查询活动
     * @param array $query 查询条件
     * @return array 符合条件的 Events
     */
    function events( $query ) {
    }

    /**
     * 全文检索 (从搜索引擎中查找)
     * @param array $query 查询条件
     * @return array 符合条件的 Items
     */
    function fulltext( $query ) {
    }

    /**
     * 根据用户行为推荐 (从推荐引擎中查找)
     * @param array $query 查询条件
     * @return array 符合条件的 Items
     */
    function recommend( $query ) {
    }
    // @KEEP END

	/**
	 * 自定义函数 选取推荐文章
	 */
	function getArticlesBy( $type,  $recommend_id,  $keywords=[], $now=null, $page=1, $perpage=20 ) {
	
		$select = ['recommend.title','recommend.summary',  'recommend.type', 'recommend.keywords', "recommend.period", "orderby", 'articles', 'categories'];
		$method = "getBy{$type}";
		if ( !method_exists( $this, $method) ) {
			throw new Excp( "推荐数据查询方法不存在", 404, ['method'=>$method] );
		}
      	$recommend = $this->$method( $recommend_id ,  $select );
		
		if ( empty($recommend) ) {
			throw new Excp( "推荐数据不存在", 404, ['recommend_id'=>$recommend_id] );
		}

		$art = new Article;
		$query = [];
      
		if ( !empty($now) ) {
          $query['now'] = $now;
        }
		
		// 数据排序
		switch ($recommend['orderby']) {
			case 'publish_time': 
				$query['order'] =  "publish_time desc";
				break;
			case 'view_cnt':
				$query['order'] =  "view_cnt desc";
				break;
			case 'like_cnt':
				$query['order'] =  "like_cnt desc";
				break;
			case 'dislike_cnt':
				$query['order'] =  "dislike_cnt desc";
				break;
			case 'comment_cnt':
				$query['order'] =  "comment_cnt desc";
				break;
			default:
				$query['order'] =  "publish_time desc";
				break;
		}

		if ( !empty($recommend['period']) ) {
			$query['period'] = $recommend['period'];
		}


		// 自动根据关键词关联
		if ( $recommend['type'] == 'auto' ) {

			$recommend['keywords'] = str_replace("\r", "", $recommend['keywords']);
			$recommend['keywords'] = str_replace("\n", "", $recommend['keywords']);
			$recommend['keywords'] = explode(',', trim($recommend['keywords']) );
			$keywords = is_string($keywords) ? explode(',',$keywords) : $keywords;
			$keywords = array_merge( $recommend['keywords'], $keywords );

			// 按关键词提取数据
			$query['keywords'] =$keywords;

			// 按分类提取数据
			if ( !empty($recommend['categories']) ) {
				$query['category_ids'] = $recommend['categories'];
			}

			return $art->search($query);

		// 静态关联
		} else if ( $recommend['type'] == 'static' )  {
			
			// 提取选定文章信息
			$query['article_ids'] = $recommend['articles'];
			return $art->search($query);
		}

	}
	/**
	 * 自定义函数 按推荐ID选取推荐文章 (废弃)
	 */
	function getArticles(  $recommend_id,  $keywords=[], $now=null, $page=1, $perpage=20 ) {
		return $this->getArticlesBy('recommend_id', $recommend_id, $keywords, $now, $page, $perpage );
	}
	/**
	 * 自定义函数 按别名选取推荐文章 (废弃)
	 */
	function getArticlesBySlug(  $recommend_id,  $keywords=[], $now=null, $page=1, $perpage=20 ) {
		return $this->getArticlesBy('slug', $recommend_id,  $keywords, $now, $page, $perpage );
	}
	/**
	 * 自定义函数 按别名选取推荐内容 (废弃)
	 */
function getContentsBySlug(  $recommend_id,  $keywords=[],  $series=[], $exclude_articles=[], $page=1, $perpage=20, $now=null ) {
		return $this->getContentsBy('slug', $recommend_id,  $keywords,  $series, $exclude_articles, $page, $perpage,$now );
	}
	/**
	 * 自定义函数 按推荐ID选取推荐内容 (废弃)
	 */
function getContents(  $recommend_id,  $keywords=[],  $series=[],  $exclude_articles=[],  $page=1, $perpage=20, $now=null ) {
		return $this->getContentsBy('recommendId', $recommend_id, $keywords, $series, $exclude_articles, $page, $perpage,$now );
	}
	/**
	 * 自定义函数 按Type选取推荐内容 (废弃)
	 */
function getContentsBy( $type,  $recommend_id,  $keywords=[], $series=[], $exclude_articles=[],  $page=1, $perpage=20, $now=null) {
		$select = [
					'recommend.title', 'recommend.summary', 'recommend.type', 'recommend.ctype', 'recommend.keywords', "recommend.period", "recommend.pos","recommend.style","recommend.status",
					'recommend.thumb_only', 'recommend.video_only',
          			'recommend.series','recommend.exclude_articles',
					"orderby", 'articles', 'albums', 'events', 'categories'
				];
		$method = "getBy{$type}";
		if ( !method_exists( $this, $method) ) {
			throw new Excp( "推荐数据查询方法不存在", 404, ['method'=>$method] );
		}
      	$recommend = $this->$method( $recommend_id ,  $select );
		
		if ( empty($recommend) ) {
			throw new Excp( "推荐数据不存在", 404, ['recommend_id'=>$recommend_id] );
		}


		// 静态关联
		if ( $recommend['type'] == 'static' )  {
			$recommend['contents']['data'] = [];

			switch ($recommend['ctype']) {
				case 'article':
					$ids = $recommend['articles'];
					break;
				case 'album':
					$ids  = $recommend['albums'];
					break;
				case 'event':
					$ids = $recommend['events'];
					break;
				// case 'all': // 使用搜索引擎来实现，先分开查询
					// break;
				default:
					$ids = $recommend['articles'];
					break;
			}

			$recommend['contents']['total'] = count($ids);
		
		// 智能关联
		} else { 
			// 根据类型选取内容
			switch ($recommend['ctype']) {
				case 'article':
					$qb =  Utils::getTab("xpmsns_pages_article as content", "{none}")->query()->where('status','=', 'published');
					$keywordFields = ["content.keywords", "content.title"];
					$qb->select('content.article_id as id');
					break;
				case 'album':
					$qb =  Utils::getTab("xpmsns_pages_album as content", "{none}")->query()->where('status','=', 'published');;
					$keywordFields = ["content.tags","content.title"];
					$qb->select('content.album_id as id');
					break;
				case 'event':
					$qb =  Utils::getTab("xpmsns_pages_event as content", "{none}")->query()->where('status', '<>', 'draft');
					$keywordFields = ["content.tags","content.name"];
					$qb->select('content.event_id as id');
					break;
				// case 'all': // 使用搜索引擎来实现，先分开查询
					// break;
				default:
					$qb =  Utils::getTab("xpmsns_pages_article as content", "{none}")->query();
					$keywordFields = ["content.keywords", "content.title"];
					$qb->select('content.article_id as id');
					break;
			}



	        // 排序条件
			switch ($recommend['orderby']) {
				case 'publish_time': 
					$query['order'] =  "publish_time desc";
					break;
               	case 'publish_time_asc': 
					$query['order'] =  "publish_time asc";
					break;
				case 'view_cnt':
					$query['order'] =  "view_cnt desc";
					break;
				case 'like_cnt':
					$query['order'] =  "like_cnt desc";
					break;
				case 'dislike_cnt':
					$query['order'] =  "dislike_cnt desc";
					break;
				case 'comment_cnt':
					$query['order'] =  "comment_cnt desc";
					break;
				default:
					$query['order'] =  "publish_time desc";
					break;
			}

			// 查询条件
			if ( !empty($now) ) {
	          $query['now'] = $now;
	        }

			if ( !empty($recommend['period']) ) {
				$query['period'] = $recommend['period'];
			}

			// 按分类提取数据
			if ( !empty($recommend['categories']) ) {
				$query['category_ids'] = $recommend['categories'];
			}

			// 按关键词提取数据
			$recommend['keywords'] = str_replace("\r", "", $recommend['keywords']);
			$recommend['keywords'] = str_replace("\n", "", $recommend['keywords']);
			$recommend['keywords'] = explode(',', trim($recommend['keywords']) );
			$keywords = is_string($keywords) ? explode(',',$keywords) : $keywords;
			$keywords = array_merge( $recommend['keywords'], $keywords );
			$keywords = array_filter($keywords);
			$query['keywords'] =$keywords;

			// 按系列提取数据
			$series = is_string($series) ? explode(',',$series) : $series;
          	$recommend['series'] = is_array($recommend['series']) ? $recommend['series'] : [];
			$series = array_merge( $recommend['series'], $series );
			$series = array_filter( $series);
			$query['series'] = $series;
          	
          	// 排除文章数据
			$exclude_articles = is_string($exclude_articles) ? explode(',',$exclude_articles) : $exclude_articles;
          	$recommend['exclude_articles'] = is_array($recommend['exclude_articles']) ? $recommend['exclude_articles'] : [];
			$exclude_articles = array_merge( $recommend['exclude_articles'], $exclude_articles );
			$exclude_articles = array_filter( $exclude_articles);
			$query['exclude_articles'] = $exclude_articles;
         
			if ( $recommend['thumb_only'] ) {
				$query['thumb_only'] = 1;
			}

			// 必须包含主题图片
			if ( $query['thumb_only'] ) {
				$qb->whereNotNull('content.cover');
				$qb->where('content.cover', '<>', "");
			}
          
          	if ( $recommend['video_only']) {
				$query['video_only'] = 1;
			}

			// 必须包含视频
			if ( $query['video_only'] ) {
				$qb->whereNotNull('content.videos');
				$qb->where('content.videos', '<>', "");
				$qb->where('content.videos', '<>', "[]");
			}

			// 按分类ID查找
			if ( array_key_exists('category_ids', $query)  && !empty($query['category_ids']) ) {
				$cids = is_string($query['category_ids']) ? explode(',', $query['category_ids']) : $query['category_ids'];
				$cids = array_filter($cids);
              	if ( !empty($cids) ) {
					if ( $recommend['ctype'] == 'article' || $recommend['ctype'] == 'all' ) {
						$qb->leftJoin("xpmsns_pages_article_category as ac", "ac.article_id", "=", "content.article_id");
						$qb->whereIn('ac.category_id', $cids );	
					}else {
						$qb->where(function ( $qb ) use($cids) {
							foreach( $cids as $cid ) {
								$qb->orWhere('categories', "like", "%{$cid}%");  // 名称符合关键词
							}
						});
					}
				}
			}
          
          	// 按系列查询数据
			if ( array_key_exists('series', $query)  && !empty($query['series']) ) {
				$sids = is_string($query['series']) ? explode(',', $query['series']) : $query['series'];
				$sids = array_filter($sids);
              	if ( !empty($sids) ) {
					$qb->where(function ( $qb ) use($sids) {
						foreach( $sids as $sid ) {
							$qb->orWhere('series', "like", "%{$sid}%");  // 名称符合关键词
						}
					});
				}
			}
          
            // 排除文章数据
			if ( array_key_exists('exclude_articles', $query)  && !empty($query['exclude_articles']) ) {
				$exids = is_string($query['exclude_articles']) ? explode(',', $query['exclude_articles']) : $query['exclude_articles'];
				$exids = array_filter($exids);
              	if ( !empty($exids) ) {
					$qb->whereNotIn('content.article_id', $exids);
				}
			}

			// 按关键词词组查找 ( 非搜索 )
			if ( array_key_exists('keywords', $query) && !empty($query['keywords']) ) {

				// 过滤空值
				$keywords = $query['keywords'];
				foreach( $keywords as $idx=>$key ) {
					$keywords[$idx] = trim($key);
					if  ( empty($keywords[$idx]) ) {
						unset($keywords[$idx]);
					}
				}

				if ( !empty($keywords) ) {
					$qb->where(function ( $qb ) use($keywords, $keywordFields) {
						foreach( $keywords as $idx=>$keyword ) {
							foreach ($keywordFields as $keywordField) {
								$qb->orWhere($keywordField, "like", "%{$keyword}%");  // 名称符合关键词
							}
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
						$qb->where('publish_time' , '<=', $now );
						$qb->where('publish_time' , '>=', $from );
						break;

					case 'daily' : // 当天
						$from = date('Y-m-d 00:00:00', $now_t);
						$end = date('Y-m-d 23:59:59', $now_t);
						$qb->where('publish_time' , '<=', $end );
						$qb->where('publish_time' , '>=', $from );
						break;

					case '7days': // 7天
						$end = date('Y-m-d H:i:s', $now_t);
						$end_t = strtotime($end);
						$from = date('Y-m-d H:i:s',  strtotime("-7 days",$end_t));
						$qb->where('publish_time' , '<=', $end );
						$qb->where('publish_time' , '>=', $from );
						break;

					case 'weekly': // 本周
						$from = date('Y-m-d 00:00:00', strtotime('-1 Monday',$now_t));
						$from_t = strtotime($from);
						$end = date('Y-m-d 00:00:00',  strtotime("+1 Weeks",$from_t));
						$qb->where('publish_time' , '<=', $end );
						$qb->where('publish_time' , '>=', $from );
						break;

					case '30days': // 30天
						$end = date('Y-m-d H:i:s', $now_t);
						$end_t = strtotime($end);
						$from = date('Y-m-d H:i:s',  strtotime("-30 days",$end_t));
						$qb->where('publish_time' , '<=', $end );
						$qb->where('publish_time' , '>=', $from );
						break;

					case 'monthly': // 本月
						$from = date('Y-m-01 00:00:00', $now_t);
						$from_t = strtotime($from);
						$end = date('Y-m-d 00:00:00',  strtotime("+1 Month",$from_t));
						$qb->where('publish_time' , '<=', $end );
						$qb->where('publish_time' , '>=', $from );
						break;

					case 'yearly':  // 今年
						$from = date('Y-01-01 00:00:00', $now_t);
						$end = date('Y-12-31 23:59:59',  $now_t);
						$qb->where('publish_time' , '<=', $end );
						$qb->where('publish_time' , '>=', $from );
						break;

					default: // 无限
						# code...
						break;
				}
			}


			// 排序: 
			if ( array_key_exists('order', $query) && !empty($query['order'])  ) {
				$order = explode(' ', $query['order']);
				$order[1] = !empty($order[1]) ? $order[1] : 'asc';
				$qb->orderBy($order[0], $order[1] );
			}


			// 查询文章列表
			$resp = $qb->distinct()->pgArray( $perpage, ['content._id'], 'page', $page );
			if ( $_GET['debug'] == 1 ) {
				$recommend['_sql'] = $qb->getSql();
				$recommend['_query'] = $query; 
			}

			$recommend['contents'] = $resp;

			if ( empty($resp['data']) ) {
				return $recommend;
			}

			$ids = array_column($resp['data'], 'id');

		}

		// 根据类型选取内容
		switch ($recommend['ctype']) {
			case 'article':
				$recommend['contents']['data'] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId( $ids, [
					'title', 'cover', 'article_id', 'images', 'thumbs', 'videos', 'author', 'origin', 'origin_url','summary','status', 'publish_time',
					'view_cnt','like_cnt','dislike_cnt','comment_cnt',
                  	'series'
				]);
				break;
			case 'album':
				$recommend['contents']['data'] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId( $ids, [
					'title', 'cover', 'album_id', 'images','link', 'tags', 'author', 'origin', 'origin_url','summary','status', 'publish_time',
					'view_cnt','like_cnt','dislike_cnt','comment_cnt',
					'c.name'
				]);

				break;
			case 'event':
				$recommend['contents']['data'] = (new \Xpmsns\Pages\Model\Event)->getInByEventId( $ids, [
					'name', 'cover', 'event_id', 'images','link', 'tags','summary','status', 'publish_time',
					'view_cnt','like_cnt','dislike_cnt','comment_cnt',
					'c.name'
				]);
				
				break;
			default:
				
				$recommend['contents']['data'] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId( $ids, [
					'title', 'cover', 'article_id', 'images', 'thumbs', 'videos', 'author', 'origin', 'origin_url','summary','status', 'publish_time',
					'view_cnt','like_cnt','dislike_cnt','comment_cnt'
				]);

				break;
		}
      	if ( is_array($recommend['contents']['data']) ){
			$data = [];
			foreach ($ids as $id ) {
				$data[] = $recommend['contents']['data'][$id];
			}
			$recommend['contents']['data'] = $data;
        }
		return $recommend;
	}

	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 推荐ID
		$this->putColumn( 'recommend_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 主题
		$this->putColumn( 'title', $this->type("string", ["length"=>100, "index"=>true, "null"=>true]));
		// 简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>200, "null"=>true]));
		// 图标
		$this->putColumn( 'icon', $this->type("string", ["length"=>200, "null"=>true]));
		// 别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>100, "unique"=>true, "null"=>true]));
		// 呈现位置
		$this->putColumn( 'pos', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 样式代码
		$this->putColumn( 'style', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 方式
		$this->putColumn( 'type', $this->type("string", ["length"=>20, "index"=>true, "default"=>"auto", "null"=>true]));
		// 内容类型
		$this->putColumn( 'ctype', $this->type("string", ["length"=>20, "index"=>true, "default"=>"article", "null"=>true]));
		// 必须包含主题图片
		$this->putColumn( 'thumb_only', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 必须包含视频
		$this->putColumn( 'video_only', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 根据用户喜好推荐
		$this->putColumn( 'bigdata_engine', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 周期
		$this->putColumn( 'period', $this->type("string", ["length"=>100, "index"=>true, "null"=>true]));
		// 摘要图片
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// PC端模板
		$this->putColumn( 'tpl_pc', $this->type("text", ["null"=>true]));
		// 手机端模板
		$this->putColumn( 'tpl_h5', $this->type("text", ["null"=>true]));
		// 小程序模板
		$this->putColumn( 'tpl_wxapp', $this->type("text", ["null"=>true]));
		// 安卓模板
		$this->putColumn( 'tpl_android', $this->type("text", ["null"=>true]));
		// iOS模板
		$this->putColumn( 'tpl_ios', $this->type("text", ["null"=>true]));
		// 关键词
		$this->putColumn( 'keywords', $this->type("text", ["null"=>true]));
		// 指定系列
		$this->putColumn( 'series', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定栏目
		$this->putColumn( 'categories', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定话题
		$this->putColumn( 'topics', $this->type("text", ["json"=>true, "null"=>true]));
		// 文章字段
		$this->putColumn( 'article_select', $this->type("text", ["json"=>true, "null"=>true]));
		// 文章状态
		$this->putColumn( 'article_status', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定文章
		$this->putColumn( 'articles', $this->type("text", ["json"=>true, "null"=>true]));
		// 排除文章
		$this->putColumn( 'exclude_articles', $this->type("text", ["json"=>true, "null"=>true]));
		// 活动字段
		$this->putColumn( 'event_select', $this->type("text", ["json"=>true, "null"=>true]));
		// 活动状态
		$this->putColumn( 'event_status', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定活动
		$this->putColumn( 'events', $this->type("text", ["json"=>true, "null"=>true]));
		// 排除活动
		$this->putColumn( 'exclude_events', $this->type("text", ["json"=>true, "null"=>true]));
		// 图集字段
		$this->putColumn( 'album_select', $this->type("text", ["json"=>true, "null"=>true]));
		// 图集状态
		$this->putColumn( 'album_status', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定图集
		$this->putColumn( 'albums', $this->type("text", ["json"=>true, "null"=>true]));
		// 排除图集
		$this->putColumn( 'exclude_albums', $this->type("text", ["json"=>true, "null"=>true]));
		// 提问字段
		$this->putColumn( 'question_select', $this->type("text", ["json"=>true, "null"=>true]));
		// 提问状态
		$this->putColumn( 'question_status', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定提问
		$this->putColumn( 'questions', $this->type("text", ["json"=>true, "null"=>true]));
		// 排除提问
		$this->putColumn( 'exclude_questions', $this->type("text", ["json"=>true, "null"=>true]));
		// 回答字段
		$this->putColumn( 'answer_select', $this->type("text", ["json"=>true, "null"=>true]));
		// 回答状态
		$this->putColumn( 'answer_status', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定回答
		$this->putColumn( 'answers', $this->type("text", ["json"=>true, "null"=>true]));
		// 排除回答
		$this->putColumn( 'exclude_answers', $this->type("text", ["json"=>true, "null"=>true]));
		// 商品字段
		$this->putColumn( 'goods_select', $this->type("text", ["json"=>true, "null"=>true]));
		// 商品状态
		$this->putColumn( 'goods_status', $this->type("text", ["json"=>true, "null"=>true]));
		// 指定商品
		$this->putColumn( 'goods', $this->type("text", ["json"=>true, "null"=>true]));
		// 排除商品
		$this->putColumn( 'exclude_goods', $this->type("text", ["json"=>true, "null"=>true]));
		// 排序方式
		$this->putColumn( 'orderby', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 缓存时间
		$this->putColumn( 'ttl', $this->type("integer", ["length"=>1, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>10, "index"=>true, "default"=>"on", "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 
		// 格式化: 图标
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('icon', $rs ) ) {
            array_push($fileFields, 'icon');
		}
		// 格式化: 摘要图片
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
            array_push($fileFields, 'images');
		}

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

		// 格式化: 方式
		// 返回值: "_type_types" 所有状态表述, "_type_name" 状态名称,  "_type" 当前状态表述, "type" 当前状态数值
		if ( array_key_exists('type', $rs ) && !empty($rs['type']) ) {
			$rs["_type_types"] = [
		  		"auto" => [
		  			"value" => "auto",
		  			"name" => "智能",
		  			"style" => "danger"
		  		],
		  		"static" => [
		  			"value" => "static",
		  			"name" => "静态",
		  			"style" => "primary"
		  		],
			];
			$rs["_type_name"] = "type";
			$rs["_type"] = $rs["_type_types"][$rs["type"]];
		}

		// 格式化: 内容类型
		// 返回值: "_ctype_types" 所有状态表述, "_ctype_name" 状态名称,  "_ctype" 当前状态表述, "ctype" 当前状态数值
		if ( array_key_exists('ctype', $rs ) && !empty($rs['ctype']) ) {
			$rs["_ctype_types"] = [
		  		"all" => [
		  			"value" => "all",
		  			"name" => "混合",
		  			"style" => "danger"
		  		],
		  		"article" => [
		  			"value" => "article",
		  			"name" => "图文",
		  			"style" => "primary"
		  		],
		  		"album" => [
		  			"value" => "album",
		  			"name" => "图集",
		  			"style" => "primary"
		  		],
		  		"event" => [
		  			"value" => "event",
		  			"name" => "活动",
		  			"style" => "primary"
		  		],
		  		"question" => [
		  			"value" => "question",
		  			"name" => "提问",
		  			"style" => "primary"
		  		],
		  		"answer" => [
		  			"value" => "answer",
		  			"name" => "回答",
		  			"style" => "primary"
		  		],
		  		"goods" => [
		  			"value" => "goods",
		  			"name" => "商品",
		  			"style" => "primary"
		  		],
			];
			$rs["_ctype_name"] = "ctype";
			$rs["_ctype"] = $rs["_ctype_types"][$rs["ctype"]];
		}

		// 格式化: 周期
		// 返回值: "_period_types" 所有状态表述, "_period_name" 状态名称,  "_period" 当前状态表述, "period" 当前状态数值
		if ( array_key_exists('period', $rs ) && !empty($rs['period']) ) {
			$rs["_period_types"] = [
		  		"24hours" => [
		  			"value" => "24hours",
		  			"name" => "24小时",
		  			"style" => "info"
		  		],
		  		"daily" => [
		  			"value" => "daily",
		  			"name" => "今日",
		  			"style" => "info"
		  		],
		  		"7days" => [
		  			"value" => "7days",
		  			"name" => "7天",
		  			"style" => "info"
		  		],
		  		"weekly" => [
		  			"value" => "weekly",
		  			"name" => "本周",
		  			"style" => "info"
		  		],
		  		"30days" => [
		  			"value" => "30days",
		  			"name" => "30天",
		  			"style" => "info"
		  		],
		  		"monthly" => [
		  			"value" => "monthly",
		  			"name" => "本月",
		  			"style" => "info"
		  		],
		  		"yearly" => [
		  			"value" => "yearly",
		  			"name" => "今年",
		  			"style" => "info"
		  		],
		  		"unlimited" => [
		  			"value" => "unlimited",
		  			"name" => "无限",
		  			"style" => "info"
		  		],
			];
			$rs["_period_name"] = "period";
			$rs["_period"] = $rs["_period_types"][$rs["period"]];
		}

		// 格式化: 排序方式
		// 返回值: "_orderby_types" 所有状态表述, "_orderby_name" 状态名称,  "_orderby" 当前状态表述, "orderby" 当前状态数值
		if ( array_key_exists('orderby', $rs ) && !empty($rs['orderby']) ) {
			$rs["_orderby_types"] = [
		  		"publish_time" => [
		  			"value" => "publish_time",
		  			"name" => "最新发布",
		  			"style" => "info"
		  		],
		  		"publish_time_asc" => [
		  			"value" => "publish_time_asc",
		  			"name" => "发布顺序",
		  			"style" => "info"
		  		],
		  		"view_cnt" => [
		  			"value" => "view_cnt",
		  			"name" => "最多浏览",
		  			"style" => "info"
		  		],
		  		"answer_cnt" => [
		  			"value" => "answer_cnt",
		  			"name" => "最多回答",
		  			"style" => "info"
		  		],
		  		"agree_cnt" => [
		  			"value" => "agree_cnt",
		  			"name" => "最多赞同",
		  			"style" => "info"
		  		],
		  		"like_cnt" => [
		  			"value" => "like_cnt",
		  			"name" => "最多点赞",
		  			"style" => "info"
		  		],
		  		"comment_cnt" => [
		  			"value" => "comment_cnt",
		  			"name" => "最多评论",
		  			"style" => "info"
		  		],
		  		"dislike_cnt" => [
		  			"value" => "dislike_cnt",
		  			"name" => "最多讨厌",
		  			"style" => "info"
		  		],
			];
			$rs["_orderby_name"] = "orderby";
			$rs["_orderby"] = $rs["_orderby_types"][$rs["orderby"]];
		}

		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"on" => [
		  			"value" => "on",
		  			"name" => "开启",
		  			"style" => "primary"
		  		],
		  		"off" => [
		  			"value" => "off",
		  			"name" => "关闭",
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
	 * 按推荐ID查询一条推荐记录
	 * @param string $recommend_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["recommend_id"],  // 推荐ID 
	 *          	  $rs["title"],  // 主题 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["icon"],  // 图标 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["pos"],  // 呈现位置 
	 *          	  $rs["style"],  // 样式代码 
	 *          	  $rs["type"],  // 方式 
	 *          	  $rs["ctype"],  // 内容类型 
	 *          	  $rs["thumb_only"],  // 必须包含主题图片 
	 *          	  $rs["video_only"],  // 必须包含视频 
	 *          	  $rs["bigdata_engine"],  // 根据用户喜好推荐 
	 *          	  $rs["period"],  // 周期 
	 *          	  $rs["images"],  // 摘要图片 
	 *          	  $rs["tpl_pc"],  // PC端模板 
	 *          	  $rs["tpl_h5"],  // 手机端模板 
	 *          	  $rs["tpl_wxapp"],  // 小程序模板 
	 *          	  $rs["tpl_android"],  // 安卓模板 
	 *          	  $rs["tpl_ios"],  // iOS模板 
	 *          	  $rs["keywords"],  // 关键词 
	 *          	  $rs["series"],  // 指定系列 
	 *                $rs["_map_series"][$series[n]]["series_id"], // series.series_id
	 *          	  $rs["categories"],  // 指定栏目 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["topics"],  // 指定话题 
	 *                $rs["_map_topic"][$topics[n]]["topic_id"], // topic.topic_id
	 *          	  $rs["article_select"],  // 文章字段 
	 *          	  $rs["article_status"],  // 文章状态 
	 *          	  $rs["articles"],  // 指定文章 
	 *                $rs["_map_article"][$articles[n]]["article_id"], // article.article_id
	 *          	  $rs["exclude_articles"],  // 排除文章 
	 *                $rs["_map_article"][$exclude_articles[n]]["article_id"], // article.article_id
	 *          	  $rs["event_select"],  // 活动字段 
	 *          	  $rs["event_status"],  // 活动状态 
	 *          	  $rs["events"],  // 指定活动 
	 *                $rs["_map_event"][$events[n]]["event_id"], // event.event_id
	 *          	  $rs["exclude_events"],  // 排除活动 
	 *                $rs["_map_event"][$exclude_events[n]]["event_id"], // event.event_id
	 *          	  $rs["album_select"],  // 图集字段 
	 *          	  $rs["album_status"],  // 图集状态 
	 *          	  $rs["albums"],  // 指定图集 
	 *                $rs["_map_album"][$albums[n]]["album_id"], // album.album_id
	 *          	  $rs["exclude_albums"],  // 排除图集 
	 *                $rs["_map_album"][$exclude_albums[n]]["album_id"], // album.album_id
	 *          	  $rs["question_select"],  // 提问字段 
	 *          	  $rs["question_status"],  // 提问状态 
	 *          	  $rs["questions"],  // 指定提问 
	 *                $rs["_map_question"][$questions[n]]["question_id"], // question.question_id
	 *          	  $rs["exclude_questions"],  // 排除提问 
	 *                $rs["_map_question"][$exclude_questions[n]]["question_id"], // question.question_id
	 *          	  $rs["answer_select"],  // 回答字段 
	 *          	  $rs["answer_status"],  // 回答状态 
	 *          	  $rs["answers"],  // 指定回答 
	 *                $rs["_map_answer"][$answers[n]]["answer_id"], // answer.answer_id
	 *          	  $rs["exclude_answers"],  // 排除回答 
	 *                $rs["_map_answer"][$exclude_answers[n]]["answer_id"], // answer.answer_id
	 *          	  $rs["goods_select"],  // 商品字段 
	 *          	  $rs["goods_status"],  // 商品状态 
	 *          	  $rs["goods"],  // 指定商品 
	 *                $rs["_map_goods"][$goods[n]]["goods_id"], // goods.goods_id
	 *          	  $rs["exclude_goods"],  // 排除商品 
	 *                $rs["_map_goods"][$exclude_goods[n]]["goods_id"], // goods.goods_id
	 *          	  $rs["orderby"],  // 排序方式 
	 *          	  $rs["ttl"],  // 缓存时间 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_article"][$articles[n]]["created_at"], // article.created_at
	 *                $rs["_map_article"][$articles[n]]["updated_at"], // article.updated_at
	 *                $rs["_map_article"][$articles[n]]["outer_id"], // article.outer_id
	 *                $rs["_map_article"][$articles[n]]["cover"], // article.cover
	 *                $rs["_map_article"][$articles[n]]["thumbs"], // article.thumbs
	 *                $rs["_map_article"][$articles[n]]["images"], // article.images
	 *                $rs["_map_article"][$articles[n]]["videos"], // article.videos
	 *                $rs["_map_article"][$articles[n]]["audios"], // article.audios
	 *                $rs["_map_article"][$articles[n]]["title"], // article.title
	 *                $rs["_map_article"][$articles[n]]["author"], // article.author
	 *                $rs["_map_article"][$articles[n]]["origin"], // article.origin
	 *                $rs["_map_article"][$articles[n]]["origin_url"], // article.origin_url
	 *                $rs["_map_article"][$articles[n]]["summary"], // article.summary
	 *                $rs["_map_article"][$articles[n]]["seo_title"], // article.seo_title
	 *                $rs["_map_article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	 *                $rs["_map_article"][$articles[n]]["seo_summary"], // article.seo_summary
	 *                $rs["_map_article"][$articles[n]]["publish_time"], // article.publish_time
	 *                $rs["_map_article"][$articles[n]]["update_time"], // article.update_time
	 *                $rs["_map_article"][$articles[n]]["create_time"], // article.create_time
	 *                $rs["_map_article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	 *                $rs["_map_article"][$articles[n]]["sync"], // article.sync
	 *                $rs["_map_article"][$articles[n]]["content"], // article.content
	 *                $rs["_map_article"][$articles[n]]["ap_content"], // article.ap_content
	 *                $rs["_map_article"][$articles[n]]["delta"], // article.delta
	 *                $rs["_map_article"][$articles[n]]["param"], // article.param
	 *                $rs["_map_article"][$articles[n]]["stick"], // article.stick
	 *                $rs["_map_article"][$articles[n]]["preview"], // article.preview
	 *                $rs["_map_article"][$articles[n]]["links"], // article.links
	 *                $rs["_map_article"][$articles[n]]["user"], // article.user
	 *                $rs["_map_article"][$articles[n]]["policies"], // article.policies
	 *                $rs["_map_article"][$articles[n]]["status"], // article.status
	 *                $rs["_map_article"][$articles[n]]["keywords"], // article.keywords
	 *                $rs["_map_article"][$articles[n]]["view_cnt"], // article.view_cnt
	 *                $rs["_map_article"][$articles[n]]["like_cnt"], // article.like_cnt
	 *                $rs["_map_article"][$articles[n]]["dislike_cnt"], // article.dislike_cnt
	 *                $rs["_map_article"][$articles[n]]["comment_cnt"], // article.comment_cnt
	 *                $rs["_map_article"][$articles[n]]["series"], // article.series
	 *                $rs["_map_article"][$articles[n]]["user_id"], // article.user_id
	 *                $rs["_map_article"][$articles[n]]["policies_detail"], // article.policies_detail
	 *                $rs["_map_article"][$articles[n]]["agree_cnt"], // article.agree_cnt
	 *                $rs["_map_article"][$articles[n]]["priority"], // article.priority
	 *                $rs["_map_article"][$articles[n]]["coin_view"], // article.coin_view
	 *                $rs["_map_article"][$articles[n]]["money_view"], // article.money_view
	 *                $rs["_map_article"][$articles[n]]["specials"], // article.specials
	 *                $rs["_map_article"][$articles[n]]["history"], // article.history
	 *                $rs["_map_article"][$articles[n]]["policies_comment"], // article.policies_comment
	 *                $rs["_map_article"][$articles[n]]["policies_reward"], // article.policies_reward
	 *                $rs["_map_article"][$exclude_articles[n]]["created_at"], // article.created_at
	 *                $rs["_map_article"][$exclude_articles[n]]["updated_at"], // article.updated_at
	 *                $rs["_map_article"][$exclude_articles[n]]["outer_id"], // article.outer_id
	 *                $rs["_map_article"][$exclude_articles[n]]["cover"], // article.cover
	 *                $rs["_map_article"][$exclude_articles[n]]["thumbs"], // article.thumbs
	 *                $rs["_map_article"][$exclude_articles[n]]["images"], // article.images
	 *                $rs["_map_article"][$exclude_articles[n]]["videos"], // article.videos
	 *                $rs["_map_article"][$exclude_articles[n]]["audios"], // article.audios
	 *                $rs["_map_article"][$exclude_articles[n]]["title"], // article.title
	 *                $rs["_map_article"][$exclude_articles[n]]["author"], // article.author
	 *                $rs["_map_article"][$exclude_articles[n]]["origin"], // article.origin
	 *                $rs["_map_article"][$exclude_articles[n]]["origin_url"], // article.origin_url
	 *                $rs["_map_article"][$exclude_articles[n]]["summary"], // article.summary
	 *                $rs["_map_article"][$exclude_articles[n]]["seo_title"], // article.seo_title
	 *                $rs["_map_article"][$exclude_articles[n]]["seo_keywords"], // article.seo_keywords
	 *                $rs["_map_article"][$exclude_articles[n]]["seo_summary"], // article.seo_summary
	 *                $rs["_map_article"][$exclude_articles[n]]["publish_time"], // article.publish_time
	 *                $rs["_map_article"][$exclude_articles[n]]["update_time"], // article.update_time
	 *                $rs["_map_article"][$exclude_articles[n]]["create_time"], // article.create_time
	 *                $rs["_map_article"][$exclude_articles[n]]["baidulink_time"], // article.baidulink_time
	 *                $rs["_map_article"][$exclude_articles[n]]["sync"], // article.sync
	 *                $rs["_map_article"][$exclude_articles[n]]["content"], // article.content
	 *                $rs["_map_article"][$exclude_articles[n]]["ap_content"], // article.ap_content
	 *                $rs["_map_article"][$exclude_articles[n]]["delta"], // article.delta
	 *                $rs["_map_article"][$exclude_articles[n]]["param"], // article.param
	 *                $rs["_map_article"][$exclude_articles[n]]["stick"], // article.stick
	 *                $rs["_map_article"][$exclude_articles[n]]["preview"], // article.preview
	 *                $rs["_map_article"][$exclude_articles[n]]["links"], // article.links
	 *                $rs["_map_article"][$exclude_articles[n]]["user"], // article.user
	 *                $rs["_map_article"][$exclude_articles[n]]["policies"], // article.policies
	 *                $rs["_map_article"][$exclude_articles[n]]["status"], // article.status
	 *                $rs["_map_article"][$exclude_articles[n]]["keywords"], // article.keywords
	 *                $rs["_map_article"][$exclude_articles[n]]["view_cnt"], // article.view_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["like_cnt"], // article.like_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["dislike_cnt"], // article.dislike_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["comment_cnt"], // article.comment_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["series"], // article.series
	 *                $rs["_map_article"][$exclude_articles[n]]["user_id"], // article.user_id
	 *                $rs["_map_article"][$exclude_articles[n]]["policies_detail"], // article.policies_detail
	 *                $rs["_map_article"][$exclude_articles[n]]["agree_cnt"], // article.agree_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["priority"], // article.priority
	 *                $rs["_map_article"][$exclude_articles[n]]["coin_view"], // article.coin_view
	 *                $rs["_map_article"][$exclude_articles[n]]["money_view"], // article.money_view
	 *                $rs["_map_article"][$exclude_articles[n]]["specials"], // article.specials
	 *                $rs["_map_article"][$exclude_articles[n]]["history"], // article.history
	 *                $rs["_map_article"][$exclude_articles[n]]["policies_comment"], // article.policies_comment
	 *                $rs["_map_article"][$exclude_articles[n]]["policies_reward"], // article.policies_reward
	 *                $rs["_map_event"][$events[n]]["created_at"], // event.created_at
	 *                $rs["_map_event"][$events[n]]["updated_at"], // event.updated_at
	 *                $rs["_map_event"][$events[n]]["slug"], // event.slug
	 *                $rs["_map_event"][$events[n]]["name"], // event.name
	 *                $rs["_map_event"][$events[n]]["link"], // event.link
	 *                $rs["_map_event"][$events[n]]["categories"], // event.categories
	 *                $rs["_map_event"][$events[n]]["type"], // event.type
	 *                $rs["_map_event"][$events[n]]["tags"], // event.tags
	 *                $rs["_map_event"][$events[n]]["summary"], // event.summary
	 *                $rs["_map_event"][$events[n]]["cover"], // event.cover
	 *                $rs["_map_event"][$events[n]]["images"], // event.images
	 *                $rs["_map_event"][$events[n]]["begin"], // event.begin
	 *                $rs["_map_event"][$events[n]]["end"], // event.end
	 *                $rs["_map_event"][$events[n]]["area"], // event.area
	 *                $rs["_map_event"][$events[n]]["prov"], // event.prov
	 *                $rs["_map_event"][$events[n]]["city"], // event.city
	 *                $rs["_map_event"][$events[n]]["town"], // event.town
	 *                $rs["_map_event"][$events[n]]["location"], // event.location
	 *                $rs["_map_event"][$events[n]]["price"], // event.price
	 *                $rs["_map_event"][$events[n]]["hosts"], // event.hosts
	 *                $rs["_map_event"][$events[n]]["organizers"], // event.organizers
	 *                $rs["_map_event"][$events[n]]["sponsors"], // event.sponsors
	 *                $rs["_map_event"][$events[n]]["medias"], // event.medias
	 *                $rs["_map_event"][$events[n]]["speakers"], // event.speakers
	 *                $rs["_map_event"][$events[n]]["content"], // event.content
	 *                $rs["_map_event"][$events[n]]["publish_time"], // event.publish_time
	 *                $rs["_map_event"][$events[n]]["view_cnt"], // event.view_cnt
	 *                $rs["_map_event"][$events[n]]["like_cnt"], // event.like_cnt
	 *                $rs["_map_event"][$events[n]]["dislike_cnt"], // event.dislike_cnt
	 *                $rs["_map_event"][$events[n]]["comment_cnt"], // event.comment_cnt
	 *                $rs["_map_event"][$events[n]]["status"], // event.status
	 *                $rs["_map_event"][$exclude_events[n]]["created_at"], // event.created_at
	 *                $rs["_map_event"][$exclude_events[n]]["updated_at"], // event.updated_at
	 *                $rs["_map_event"][$exclude_events[n]]["slug"], // event.slug
	 *                $rs["_map_event"][$exclude_events[n]]["name"], // event.name
	 *                $rs["_map_event"][$exclude_events[n]]["link"], // event.link
	 *                $rs["_map_event"][$exclude_events[n]]["categories"], // event.categories
	 *                $rs["_map_event"][$exclude_events[n]]["type"], // event.type
	 *                $rs["_map_event"][$exclude_events[n]]["tags"], // event.tags
	 *                $rs["_map_event"][$exclude_events[n]]["summary"], // event.summary
	 *                $rs["_map_event"][$exclude_events[n]]["cover"], // event.cover
	 *                $rs["_map_event"][$exclude_events[n]]["images"], // event.images
	 *                $rs["_map_event"][$exclude_events[n]]["begin"], // event.begin
	 *                $rs["_map_event"][$exclude_events[n]]["end"], // event.end
	 *                $rs["_map_event"][$exclude_events[n]]["area"], // event.area
	 *                $rs["_map_event"][$exclude_events[n]]["prov"], // event.prov
	 *                $rs["_map_event"][$exclude_events[n]]["city"], // event.city
	 *                $rs["_map_event"][$exclude_events[n]]["town"], // event.town
	 *                $rs["_map_event"][$exclude_events[n]]["location"], // event.location
	 *                $rs["_map_event"][$exclude_events[n]]["price"], // event.price
	 *                $rs["_map_event"][$exclude_events[n]]["hosts"], // event.hosts
	 *                $rs["_map_event"][$exclude_events[n]]["organizers"], // event.organizers
	 *                $rs["_map_event"][$exclude_events[n]]["sponsors"], // event.sponsors
	 *                $rs["_map_event"][$exclude_events[n]]["medias"], // event.medias
	 *                $rs["_map_event"][$exclude_events[n]]["speakers"], // event.speakers
	 *                $rs["_map_event"][$exclude_events[n]]["content"], // event.content
	 *                $rs["_map_event"][$exclude_events[n]]["publish_time"], // event.publish_time
	 *                $rs["_map_event"][$exclude_events[n]]["view_cnt"], // event.view_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["like_cnt"], // event.like_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["dislike_cnt"], // event.dislike_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["comment_cnt"], // event.comment_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["status"], // event.status
	 *                $rs["_map_album"][$albums[n]]["created_at"], // album.created_at
	 *                $rs["_map_album"][$albums[n]]["updated_at"], // album.updated_at
	 *                $rs["_map_album"][$albums[n]]["slug"], // album.slug
	 *                $rs["_map_album"][$albums[n]]["title"], // album.title
	 *                $rs["_map_album"][$albums[n]]["author"], // album.author
	 *                $rs["_map_album"][$albums[n]]["origin"], // album.origin
	 *                $rs["_map_album"][$albums[n]]["origin_url"], // album.origin_url
	 *                $rs["_map_album"][$albums[n]]["link"], // album.link
	 *                $rs["_map_album"][$albums[n]]["categories"], // album.categories
	 *                $rs["_map_album"][$albums[n]]["tags"], // album.tags
	 *                $rs["_map_album"][$albums[n]]["summary"], // album.summary
	 *                $rs["_map_album"][$albums[n]]["images"], // album.images
	 *                $rs["_map_album"][$albums[n]]["cover"], // album.cover
	 *                $rs["_map_album"][$albums[n]]["publish_time"], // album.publish_time
	 *                $rs["_map_album"][$albums[n]]["view_cnt"], // album.view_cnt
	 *                $rs["_map_album"][$albums[n]]["like_cnt"], // album.like_cnt
	 *                $rs["_map_album"][$albums[n]]["dislike_cnt"], // album.dislike_cnt
	 *                $rs["_map_album"][$albums[n]]["comment_cnt"], // album.comment_cnt
	 *                $rs["_map_album"][$albums[n]]["status"], // album.status
	 *                $rs["_map_album"][$albums[n]]["series"], // album.series
	 *                $rs["_map_album"][$exclude_albums[n]]["created_at"], // album.created_at
	 *                $rs["_map_album"][$exclude_albums[n]]["updated_at"], // album.updated_at
	 *                $rs["_map_album"][$exclude_albums[n]]["slug"], // album.slug
	 *                $rs["_map_album"][$exclude_albums[n]]["title"], // album.title
	 *                $rs["_map_album"][$exclude_albums[n]]["author"], // album.author
	 *                $rs["_map_album"][$exclude_albums[n]]["origin"], // album.origin
	 *                $rs["_map_album"][$exclude_albums[n]]["origin_url"], // album.origin_url
	 *                $rs["_map_album"][$exclude_albums[n]]["link"], // album.link
	 *                $rs["_map_album"][$exclude_albums[n]]["categories"], // album.categories
	 *                $rs["_map_album"][$exclude_albums[n]]["tags"], // album.tags
	 *                $rs["_map_album"][$exclude_albums[n]]["summary"], // album.summary
	 *                $rs["_map_album"][$exclude_albums[n]]["images"], // album.images
	 *                $rs["_map_album"][$exclude_albums[n]]["cover"], // album.cover
	 *                $rs["_map_album"][$exclude_albums[n]]["publish_time"], // album.publish_time
	 *                $rs["_map_album"][$exclude_albums[n]]["view_cnt"], // album.view_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["like_cnt"], // album.like_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["dislike_cnt"], // album.dislike_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["comment_cnt"], // album.comment_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["status"], // album.status
	 *                $rs["_map_album"][$exclude_albums[n]]["series"], // album.series
	 *                $rs["_map_question"][$questions[n]]["created_at"], // question.created_at
	 *                $rs["_map_question"][$questions[n]]["updated_at"], // question.updated_at
	 *                $rs["_map_question"][$questions[n]]["user_id"], // question.user_id
	 *                $rs["_map_question"][$questions[n]]["title"], // question.title
	 *                $rs["_map_question"][$questions[n]]["summary"], // question.summary
	 *                $rs["_map_question"][$questions[n]]["content"], // question.content
	 *                $rs["_map_question"][$questions[n]]["category_ids"], // question.category_ids
	 *                $rs["_map_question"][$questions[n]]["series_ids"], // question.series_ids
	 *                $rs["_map_question"][$questions[n]]["tags"], // question.tags
	 *                $rs["_map_question"][$questions[n]]["view_cnt"], // question.view_cnt
	 *                $rs["_map_question"][$questions[n]]["agree_cnt"], // question.agree_cnt
	 *                $rs["_map_question"][$questions[n]]["answer_cnt"], // question.answer_cnt
	 *                $rs["_map_question"][$questions[n]]["priority"], // question.priority
	 *                $rs["_map_question"][$questions[n]]["status"], // question.status
	 *                $rs["_map_question"][$questions[n]]["publish_time"], // question.publish_time
	 *                $rs["_map_question"][$questions[n]]["coin"], // question.coin
	 *                $rs["_map_question"][$questions[n]]["money"], // question.money
	 *                $rs["_map_question"][$questions[n]]["coin_view"], // question.coin_view
	 *                $rs["_map_question"][$questions[n]]["money_view"], // question.money_view
	 *                $rs["_map_question"][$questions[n]]["policies"], // question.policies
	 *                $rs["_map_question"][$questions[n]]["policies_detail"], // question.policies_detail
	 *                $rs["_map_question"][$questions[n]]["anonymous"], // question.anonymous
	 *                $rs["_map_question"][$questions[n]]["cover"], // question.cover
	 *                $rs["_map_question"][$questions[n]]["history"], // question.history
	 *                $rs["_map_question"][$exclude_questions[n]]["created_at"], // question.created_at
	 *                $rs["_map_question"][$exclude_questions[n]]["updated_at"], // question.updated_at
	 *                $rs["_map_question"][$exclude_questions[n]]["user_id"], // question.user_id
	 *                $rs["_map_question"][$exclude_questions[n]]["title"], // question.title
	 *                $rs["_map_question"][$exclude_questions[n]]["summary"], // question.summary
	 *                $rs["_map_question"][$exclude_questions[n]]["content"], // question.content
	 *                $rs["_map_question"][$exclude_questions[n]]["category_ids"], // question.category_ids
	 *                $rs["_map_question"][$exclude_questions[n]]["series_ids"], // question.series_ids
	 *                $rs["_map_question"][$exclude_questions[n]]["tags"], // question.tags
	 *                $rs["_map_question"][$exclude_questions[n]]["view_cnt"], // question.view_cnt
	 *                $rs["_map_question"][$exclude_questions[n]]["agree_cnt"], // question.agree_cnt
	 *                $rs["_map_question"][$exclude_questions[n]]["answer_cnt"], // question.answer_cnt
	 *                $rs["_map_question"][$exclude_questions[n]]["priority"], // question.priority
	 *                $rs["_map_question"][$exclude_questions[n]]["status"], // question.status
	 *                $rs["_map_question"][$exclude_questions[n]]["publish_time"], // question.publish_time
	 *                $rs["_map_question"][$exclude_questions[n]]["coin"], // question.coin
	 *                $rs["_map_question"][$exclude_questions[n]]["money"], // question.money
	 *                $rs["_map_question"][$exclude_questions[n]]["coin_view"], // question.coin_view
	 *                $rs["_map_question"][$exclude_questions[n]]["money_view"], // question.money_view
	 *                $rs["_map_question"][$exclude_questions[n]]["policies"], // question.policies
	 *                $rs["_map_question"][$exclude_questions[n]]["policies_detail"], // question.policies_detail
	 *                $rs["_map_question"][$exclude_questions[n]]["anonymous"], // question.anonymous
	 *                $rs["_map_question"][$exclude_questions[n]]["cover"], // question.cover
	 *                $rs["_map_question"][$exclude_questions[n]]["history"], // question.history
	 *                $rs["_map_answer"][$answers[n]]["created_at"], // answer.created_at
	 *                $rs["_map_answer"][$answers[n]]["updated_at"], // answer.updated_at
	 *                $rs["_map_answer"][$answers[n]]["question_id"], // answer.question_id
	 *                $rs["_map_answer"][$answers[n]]["user_id"], // answer.user_id
	 *                $rs["_map_answer"][$answers[n]]["content"], // answer.content
	 *                $rs["_map_answer"][$answers[n]]["publish_time"], // answer.publish_time
	 *                $rs["_map_answer"][$answers[n]]["policies"], // answer.policies
	 *                $rs["_map_answer"][$answers[n]]["policies_detail"], // answer.policies_detail
	 *                $rs["_map_answer"][$answers[n]]["priority"], // answer.priority
	 *                $rs["_map_answer"][$answers[n]]["view_cnt"], // answer.view_cnt
	 *                $rs["_map_answer"][$answers[n]]["agree_cnt"], // answer.agree_cnt
	 *                $rs["_map_answer"][$answers[n]]["coin"], // answer.coin
	 *                $rs["_map_answer"][$answers[n]]["money"], // answer.money
	 *                $rs["_map_answer"][$answers[n]]["coin_view"], // answer.coin_view
	 *                $rs["_map_answer"][$answers[n]]["money_view"], // answer.money_view
	 *                $rs["_map_answer"][$answers[n]]["anonymous"], // answer.anonymous
	 *                $rs["_map_answer"][$answers[n]]["accepted"], // answer.accepted
	 *                $rs["_map_answer"][$answers[n]]["status"], // answer.status
	 *                $rs["_map_answer"][$answers[n]]["history"], // answer.history
	 *                $rs["_map_answer"][$answers[n]]["summary"], // answer.summary
	 *                $rs["_map_answer"][$exclude_answers[n]]["created_at"], // answer.created_at
	 *                $rs["_map_answer"][$exclude_answers[n]]["updated_at"], // answer.updated_at
	 *                $rs["_map_answer"][$exclude_answers[n]]["question_id"], // answer.question_id
	 *                $rs["_map_answer"][$exclude_answers[n]]["user_id"], // answer.user_id
	 *                $rs["_map_answer"][$exclude_answers[n]]["content"], // answer.content
	 *                $rs["_map_answer"][$exclude_answers[n]]["publish_time"], // answer.publish_time
	 *                $rs["_map_answer"][$exclude_answers[n]]["policies"], // answer.policies
	 *                $rs["_map_answer"][$exclude_answers[n]]["policies_detail"], // answer.policies_detail
	 *                $rs["_map_answer"][$exclude_answers[n]]["priority"], // answer.priority
	 *                $rs["_map_answer"][$exclude_answers[n]]["view_cnt"], // answer.view_cnt
	 *                $rs["_map_answer"][$exclude_answers[n]]["agree_cnt"], // answer.agree_cnt
	 *                $rs["_map_answer"][$exclude_answers[n]]["coin"], // answer.coin
	 *                $rs["_map_answer"][$exclude_answers[n]]["money"], // answer.money
	 *                $rs["_map_answer"][$exclude_answers[n]]["coin_view"], // answer.coin_view
	 *                $rs["_map_answer"][$exclude_answers[n]]["money_view"], // answer.money_view
	 *                $rs["_map_answer"][$exclude_answers[n]]["anonymous"], // answer.anonymous
	 *                $rs["_map_answer"][$exclude_answers[n]]["accepted"], // answer.accepted
	 *                $rs["_map_answer"][$exclude_answers[n]]["status"], // answer.status
	 *                $rs["_map_answer"][$exclude_answers[n]]["history"], // answer.history
	 *                $rs["_map_answer"][$exclude_answers[n]]["summary"], // answer.summary
	 *                $rs["_map_goods"][$goods[n]]["created_at"], // goods.created_at
	 *                $rs["_map_goods"][$goods[n]]["updated_at"], // goods.updated_at
	 *                $rs["_map_goods"][$goods[n]]["instance"], // goods.instance
	 *                $rs["_map_goods"][$goods[n]]["name"], // goods.name
	 *                $rs["_map_goods"][$goods[n]]["slug"], // goods.slug
	 *                $rs["_map_goods"][$goods[n]]["tags"], // goods.tags
	 *                $rs["_map_goods"][$goods[n]]["category_ids"], // goods.category_ids
	 *                $rs["_map_goods"][$goods[n]]["recommend_ids"], // goods.recommend_ids
	 *                $rs["_map_goods"][$goods[n]]["summary"], // goods.summary
	 *                $rs["_map_goods"][$goods[n]]["cover"], // goods.cover
	 *                $rs["_map_goods"][$goods[n]]["images"], // goods.images
	 *                $rs["_map_goods"][$goods[n]]["videos"], // goods.videos
	 *                $rs["_map_goods"][$goods[n]]["params"], // goods.params
	 *                $rs["_map_goods"][$goods[n]]["content"], // goods.content
	 *                $rs["_map_goods"][$goods[n]]["content_faq"], // goods.content_faq
	 *                $rs["_map_goods"][$goods[n]]["content_serv"], // goods.content_serv
	 *                $rs["_map_goods"][$goods[n]]["sku_cnt"], // goods.sku_cnt
	 *                $rs["_map_goods"][$goods[n]]["sku_sum"], // goods.sku_sum
	 *                $rs["_map_goods"][$goods[n]]["shipped_sum"], // goods.shipped_sum
	 *                $rs["_map_goods"][$goods[n]]["available_sum"], // goods.available_sum
	 *                $rs["_map_goods"][$goods[n]]["lower_price"], // goods.lower_price
	 *                $rs["_map_goods"][$goods[n]]["sale_way"], // goods.sale_way
	 *                $rs["_map_goods"][$goods[n]]["opened_at"], // goods.opened_at
	 *                $rs["_map_goods"][$goods[n]]["closed_at"], // goods.closed_at
	 *                $rs["_map_goods"][$goods[n]]["pay_duration"], // goods.pay_duration
	 *                $rs["_map_goods"][$goods[n]]["status"], // goods.status
	 *                $rs["_map_goods"][$goods[n]]["events"], // goods.events
	 *                $rs["_map_goods"][$exclude_goods[n]]["created_at"], // goods.created_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["updated_at"], // goods.updated_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["instance"], // goods.instance
	 *                $rs["_map_goods"][$exclude_goods[n]]["name"], // goods.name
	 *                $rs["_map_goods"][$exclude_goods[n]]["slug"], // goods.slug
	 *                $rs["_map_goods"][$exclude_goods[n]]["tags"], // goods.tags
	 *                $rs["_map_goods"][$exclude_goods[n]]["category_ids"], // goods.category_ids
	 *                $rs["_map_goods"][$exclude_goods[n]]["recommend_ids"], // goods.recommend_ids
	 *                $rs["_map_goods"][$exclude_goods[n]]["summary"], // goods.summary
	 *                $rs["_map_goods"][$exclude_goods[n]]["cover"], // goods.cover
	 *                $rs["_map_goods"][$exclude_goods[n]]["images"], // goods.images
	 *                $rs["_map_goods"][$exclude_goods[n]]["videos"], // goods.videos
	 *                $rs["_map_goods"][$exclude_goods[n]]["params"], // goods.params
	 *                $rs["_map_goods"][$exclude_goods[n]]["content"], // goods.content
	 *                $rs["_map_goods"][$exclude_goods[n]]["content_faq"], // goods.content_faq
	 *                $rs["_map_goods"][$exclude_goods[n]]["content_serv"], // goods.content_serv
	 *                $rs["_map_goods"][$exclude_goods[n]]["sku_cnt"], // goods.sku_cnt
	 *                $rs["_map_goods"][$exclude_goods[n]]["sku_sum"], // goods.sku_sum
	 *                $rs["_map_goods"][$exclude_goods[n]]["shipped_sum"], // goods.shipped_sum
	 *                $rs["_map_goods"][$exclude_goods[n]]["available_sum"], // goods.available_sum
	 *                $rs["_map_goods"][$exclude_goods[n]]["lower_price"], // goods.lower_price
	 *                $rs["_map_goods"][$exclude_goods[n]]["sale_way"], // goods.sale_way
	 *                $rs["_map_goods"][$exclude_goods[n]]["opened_at"], // goods.opened_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["closed_at"], // goods.closed_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["pay_duration"], // goods.pay_duration
	 *                $rs["_map_goods"][$exclude_goods[n]]["status"], // goods.status
	 *                $rs["_map_goods"][$exclude_goods[n]]["events"], // goods.events
	 *                $rs["_map_series"][$series[n]]["created_at"], // series.created_at
	 *                $rs["_map_series"][$series[n]]["updated_at"], // series.updated_at
	 *                $rs["_map_series"][$series[n]]["name"], // series.name
	 *                $rs["_map_series"][$series[n]]["slug"], // series.slug
	 *                $rs["_map_series"][$series[n]]["category_id"], // series.category_id
	 *                $rs["_map_series"][$series[n]]["summary"], // series.summary
	 *                $rs["_map_series"][$series[n]]["orderby"], // series.orderby
	 *                $rs["_map_series"][$series[n]]["param"], // series.param
	 *                $rs["_map_series"][$series[n]]["status"], // series.status
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
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
	 *                $rs["_map_topic"][$topics[n]]["created_at"], // topic.created_at
	 *                $rs["_map_topic"][$topics[n]]["updated_at"], // topic.updated_at
	 *                $rs["_map_topic"][$topics[n]]["name"], // topic.name
	 *                $rs["_map_topic"][$topics[n]]["param"], // topic.param
	 *                $rs["_map_topic"][$topics[n]]["article_cnt"], // topic.article_cnt
	 *                $rs["_map_topic"][$topics[n]]["album_cnt"], // topic.album_cnt
	 *                $rs["_map_topic"][$topics[n]]["event_cnt"], // topic.event_cnt
	 *                $rs["_map_topic"][$topics[n]]["goods_cnt"], // topic.goods_cnt
	 *                $rs["_map_topic"][$topics[n]]["question_cnt"], // topic.question_cnt
	 */
	public function getByRecommendId( $recommend_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
               		$qb->where('recommend.recommend_id', '=', $recommend_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$article_ids = []; // 读取 inWhere article 数据
		$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 		$article_ids = []; // 读取 inWhere article 数据
		$article_ids = array_merge($article_ids, is_array($rs["exclude_articles"]) ? $rs["exclude_articles"] : [$rs["exclude_articles"]]);
 		$event_ids = []; // 读取 inWhere event 数据
		$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 		$event_ids = []; // 读取 inWhere event 数据
		$event_ids = array_merge($event_ids, is_array($rs["exclude_events"]) ? $rs["exclude_events"] : [$rs["exclude_events"]]);
 		$album_ids = []; // 读取 inWhere album 数据
		$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 		$album_ids = []; // 读取 inWhere album 数据
		$album_ids = array_merge($album_ids, is_array($rs["exclude_albums"]) ? $rs["exclude_albums"] : [$rs["exclude_albums"]]);
 		$question_ids = []; // 读取 inWhere question 数据
		$question_ids = array_merge($question_ids, is_array($rs["questions"]) ? $rs["questions"] : [$rs["questions"]]);
 		$question_ids = []; // 读取 inWhere question 数据
		$question_ids = array_merge($question_ids, is_array($rs["exclude_questions"]) ? $rs["exclude_questions"] : [$rs["exclude_questions"]]);
 		$answer_ids = []; // 读取 inWhere answer 数据
		$answer_ids = array_merge($answer_ids, is_array($rs["answers"]) ? $rs["answers"] : [$rs["answers"]]);
 		$answer_ids = []; // 读取 inWhere answer 数据
		$answer_ids = array_merge($answer_ids, is_array($rs["exclude_answers"]) ? $rs["exclude_answers"] : [$rs["exclude_answers"]]);
 		$goods_ids = []; // 读取 inWhere goods 数据
		$goods_ids = array_merge($goods_ids, is_array($rs["goods"]) ? $rs["goods"] : [$rs["goods"]]);
 		$goods_ids = []; // 读取 inWhere goods 数据
		$goods_ids = array_merge($goods_ids, is_array($rs["exclude_goods"]) ? $rs["exclude_goods"] : [$rs["exclude_goods"]]);
 		$series_ids = []; // 读取 inWhere series 数据
		$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 		$topic_ids = []; // 读取 inWhere topic 数据
		$topic_ids = array_merge($topic_ids, is_array($rs["topics"]) ? $rs["topics"] : [$rs["topics"]]);

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$rs["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$rs["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$rs["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$rs["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$rs["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$rs["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$rs["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$rs["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$rs["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$rs["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$rs["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$rs["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$rs["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere topic 数据
		if ( !empty($inwhereSelect["topic"]) && method_exists("\\Xpmsns\\Pages\\Model\\Topic", 'getInByTopicId') ) {
			$topic_ids = array_unique($topic_ids);
			$selectFields = $inwhereSelect["topic"];
			$rs["_map_topic"] = (new \Xpmsns\Pages\Model\Topic)->getInByTopicId($topic_ids, $selectFields);
		}

		return $rs;
	}

		/**
	 * 按推荐ID查询一组推荐记录
	 * @param array   $recommend_ids 唯一主键数组 ["$recommend_id1","$recommend_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 推荐记录MAP {"recommend_id1":{"key":"value",...}...}
	 */
	public function getIn($recommend_ids, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at","recommend.status"], $order=["recommend.created_at"=>"asc"] ) {
		return $this->getInByRecommendId( $recommend_ids, $select, $order);
	}
	

	/**
	 * 按推荐ID查询一组推荐记录
	 * @param array   $recommend_ids 唯一主键数组 ["$recommend_id1","$recommend_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 推荐记录MAP {"recommend_id1":{"key":"value",...}...}
	 */
	public function getInByRecommendId($recommend_ids, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at","recommend.status"], $order=["recommend.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
               		$qb->whereIn('recommend.recommend_id', $recommend_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$article_ids = []; // 读取 inWhere article 数据
 		$article_ids = []; // 读取 inWhere article 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$series_ids = []; // 读取 inWhere series 数据
 		$category_ids = []; // 读取 inWhere category 数据
 		$topic_ids = []; // 读取 inWhere topic 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['recommend_id']] = $rs;
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["exclude_articles"]) ? $rs["exclude_articles"] : [$rs["exclude_articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["exclude_events"]) ? $rs["exclude_events"] : [$rs["exclude_events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["exclude_albums"]) ? $rs["exclude_albums"] : [$rs["exclude_albums"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["questions"]) ? $rs["questions"] : [$rs["questions"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["exclude_questions"]) ? $rs["exclude_questions"] : [$rs["exclude_questions"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["answers"]) ? $rs["answers"] : [$rs["answers"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["exclude_answers"]) ? $rs["exclude_answers"] : [$rs["exclude_answers"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods"]) ? $rs["goods"] : [$rs["goods"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["exclude_goods"]) ? $rs["exclude_goods"] : [$rs["exclude_goods"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere topic
			$topic_ids = array_merge($topic_ids, is_array($rs["topics"]) ? $rs["topics"] : [$rs["topics"]]);
		}

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$map["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$map["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$map["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$map["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$map["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$map["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$map["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$map["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$map["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$map["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$map["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$map["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$map["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere topic 数据
		if ( !empty($inwhereSelect["topic"]) && method_exists("\\Xpmsns\\Pages\\Model\\Topic", 'getInByTopicId') ) {
			$topic_ids = array_unique($topic_ids);
			$selectFields = $inwhereSelect["topic"];
			$map["_map_topic"] = (new \Xpmsns\Pages\Model\Topic)->getInByTopicId($topic_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按推荐ID保存推荐记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByRecommendId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("recommend_id", $data, ["recommend_id", "slug"], ['_id', 'recommend_id']);
		return $this->getByRecommendId( $rs['recommend_id'], $select );
	}
	
	/**
	 * 按别名查询一条推荐记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["recommend_id"],  // 推荐ID 
	 *          	  $rs["title"],  // 主题 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["icon"],  // 图标 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["pos"],  // 呈现位置 
	 *          	  $rs["style"],  // 样式代码 
	 *          	  $rs["type"],  // 方式 
	 *          	  $rs["ctype"],  // 内容类型 
	 *          	  $rs["thumb_only"],  // 必须包含主题图片 
	 *          	  $rs["video_only"],  // 必须包含视频 
	 *          	  $rs["bigdata_engine"],  // 根据用户喜好推荐 
	 *          	  $rs["period"],  // 周期 
	 *          	  $rs["images"],  // 摘要图片 
	 *          	  $rs["tpl_pc"],  // PC端模板 
	 *          	  $rs["tpl_h5"],  // 手机端模板 
	 *          	  $rs["tpl_wxapp"],  // 小程序模板 
	 *          	  $rs["tpl_android"],  // 安卓模板 
	 *          	  $rs["tpl_ios"],  // iOS模板 
	 *          	  $rs["keywords"],  // 关键词 
	 *          	  $rs["series"],  // 指定系列 
	 *                $rs["_map_series"][$series[n]]["series_id"], // series.series_id
	 *          	  $rs["categories"],  // 指定栏目 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["topics"],  // 指定话题 
	 *                $rs["_map_topic"][$topics[n]]["topic_id"], // topic.topic_id
	 *          	  $rs["article_select"],  // 文章字段 
	 *          	  $rs["article_status"],  // 文章状态 
	 *          	  $rs["articles"],  // 指定文章 
	 *                $rs["_map_article"][$articles[n]]["article_id"], // article.article_id
	 *          	  $rs["exclude_articles"],  // 排除文章 
	 *                $rs["_map_article"][$exclude_articles[n]]["article_id"], // article.article_id
	 *          	  $rs["event_select"],  // 活动字段 
	 *          	  $rs["event_status"],  // 活动状态 
	 *          	  $rs["events"],  // 指定活动 
	 *                $rs["_map_event"][$events[n]]["event_id"], // event.event_id
	 *          	  $rs["exclude_events"],  // 排除活动 
	 *                $rs["_map_event"][$exclude_events[n]]["event_id"], // event.event_id
	 *          	  $rs["album_select"],  // 图集字段 
	 *          	  $rs["album_status"],  // 图集状态 
	 *          	  $rs["albums"],  // 指定图集 
	 *                $rs["_map_album"][$albums[n]]["album_id"], // album.album_id
	 *          	  $rs["exclude_albums"],  // 排除图集 
	 *                $rs["_map_album"][$exclude_albums[n]]["album_id"], // album.album_id
	 *          	  $rs["question_select"],  // 提问字段 
	 *          	  $rs["question_status"],  // 提问状态 
	 *          	  $rs["questions"],  // 指定提问 
	 *                $rs["_map_question"][$questions[n]]["question_id"], // question.question_id
	 *          	  $rs["exclude_questions"],  // 排除提问 
	 *                $rs["_map_question"][$exclude_questions[n]]["question_id"], // question.question_id
	 *          	  $rs["answer_select"],  // 回答字段 
	 *          	  $rs["answer_status"],  // 回答状态 
	 *          	  $rs["answers"],  // 指定回答 
	 *                $rs["_map_answer"][$answers[n]]["answer_id"], // answer.answer_id
	 *          	  $rs["exclude_answers"],  // 排除回答 
	 *                $rs["_map_answer"][$exclude_answers[n]]["answer_id"], // answer.answer_id
	 *          	  $rs["goods_select"],  // 商品字段 
	 *          	  $rs["goods_status"],  // 商品状态 
	 *          	  $rs["goods"],  // 指定商品 
	 *                $rs["_map_goods"][$goods[n]]["goods_id"], // goods.goods_id
	 *          	  $rs["exclude_goods"],  // 排除商品 
	 *                $rs["_map_goods"][$exclude_goods[n]]["goods_id"], // goods.goods_id
	 *          	  $rs["orderby"],  // 排序方式 
	 *          	  $rs["ttl"],  // 缓存时间 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_article"][$articles[n]]["created_at"], // article.created_at
	 *                $rs["_map_article"][$articles[n]]["updated_at"], // article.updated_at
	 *                $rs["_map_article"][$articles[n]]["outer_id"], // article.outer_id
	 *                $rs["_map_article"][$articles[n]]["cover"], // article.cover
	 *                $rs["_map_article"][$articles[n]]["thumbs"], // article.thumbs
	 *                $rs["_map_article"][$articles[n]]["images"], // article.images
	 *                $rs["_map_article"][$articles[n]]["videos"], // article.videos
	 *                $rs["_map_article"][$articles[n]]["audios"], // article.audios
	 *                $rs["_map_article"][$articles[n]]["title"], // article.title
	 *                $rs["_map_article"][$articles[n]]["author"], // article.author
	 *                $rs["_map_article"][$articles[n]]["origin"], // article.origin
	 *                $rs["_map_article"][$articles[n]]["origin_url"], // article.origin_url
	 *                $rs["_map_article"][$articles[n]]["summary"], // article.summary
	 *                $rs["_map_article"][$articles[n]]["seo_title"], // article.seo_title
	 *                $rs["_map_article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	 *                $rs["_map_article"][$articles[n]]["seo_summary"], // article.seo_summary
	 *                $rs["_map_article"][$articles[n]]["publish_time"], // article.publish_time
	 *                $rs["_map_article"][$articles[n]]["update_time"], // article.update_time
	 *                $rs["_map_article"][$articles[n]]["create_time"], // article.create_time
	 *                $rs["_map_article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	 *                $rs["_map_article"][$articles[n]]["sync"], // article.sync
	 *                $rs["_map_article"][$articles[n]]["content"], // article.content
	 *                $rs["_map_article"][$articles[n]]["ap_content"], // article.ap_content
	 *                $rs["_map_article"][$articles[n]]["delta"], // article.delta
	 *                $rs["_map_article"][$articles[n]]["param"], // article.param
	 *                $rs["_map_article"][$articles[n]]["stick"], // article.stick
	 *                $rs["_map_article"][$articles[n]]["preview"], // article.preview
	 *                $rs["_map_article"][$articles[n]]["links"], // article.links
	 *                $rs["_map_article"][$articles[n]]["user"], // article.user
	 *                $rs["_map_article"][$articles[n]]["policies"], // article.policies
	 *                $rs["_map_article"][$articles[n]]["status"], // article.status
	 *                $rs["_map_article"][$articles[n]]["keywords"], // article.keywords
	 *                $rs["_map_article"][$articles[n]]["view_cnt"], // article.view_cnt
	 *                $rs["_map_article"][$articles[n]]["like_cnt"], // article.like_cnt
	 *                $rs["_map_article"][$articles[n]]["dislike_cnt"], // article.dislike_cnt
	 *                $rs["_map_article"][$articles[n]]["comment_cnt"], // article.comment_cnt
	 *                $rs["_map_article"][$articles[n]]["series"], // article.series
	 *                $rs["_map_article"][$articles[n]]["user_id"], // article.user_id
	 *                $rs["_map_article"][$articles[n]]["policies_detail"], // article.policies_detail
	 *                $rs["_map_article"][$articles[n]]["agree_cnt"], // article.agree_cnt
	 *                $rs["_map_article"][$articles[n]]["priority"], // article.priority
	 *                $rs["_map_article"][$articles[n]]["coin_view"], // article.coin_view
	 *                $rs["_map_article"][$articles[n]]["money_view"], // article.money_view
	 *                $rs["_map_article"][$articles[n]]["specials"], // article.specials
	 *                $rs["_map_article"][$articles[n]]["history"], // article.history
	 *                $rs["_map_article"][$articles[n]]["policies_comment"], // article.policies_comment
	 *                $rs["_map_article"][$articles[n]]["policies_reward"], // article.policies_reward
	 *                $rs["_map_article"][$exclude_articles[n]]["created_at"], // article.created_at
	 *                $rs["_map_article"][$exclude_articles[n]]["updated_at"], // article.updated_at
	 *                $rs["_map_article"][$exclude_articles[n]]["outer_id"], // article.outer_id
	 *                $rs["_map_article"][$exclude_articles[n]]["cover"], // article.cover
	 *                $rs["_map_article"][$exclude_articles[n]]["thumbs"], // article.thumbs
	 *                $rs["_map_article"][$exclude_articles[n]]["images"], // article.images
	 *                $rs["_map_article"][$exclude_articles[n]]["videos"], // article.videos
	 *                $rs["_map_article"][$exclude_articles[n]]["audios"], // article.audios
	 *                $rs["_map_article"][$exclude_articles[n]]["title"], // article.title
	 *                $rs["_map_article"][$exclude_articles[n]]["author"], // article.author
	 *                $rs["_map_article"][$exclude_articles[n]]["origin"], // article.origin
	 *                $rs["_map_article"][$exclude_articles[n]]["origin_url"], // article.origin_url
	 *                $rs["_map_article"][$exclude_articles[n]]["summary"], // article.summary
	 *                $rs["_map_article"][$exclude_articles[n]]["seo_title"], // article.seo_title
	 *                $rs["_map_article"][$exclude_articles[n]]["seo_keywords"], // article.seo_keywords
	 *                $rs["_map_article"][$exclude_articles[n]]["seo_summary"], // article.seo_summary
	 *                $rs["_map_article"][$exclude_articles[n]]["publish_time"], // article.publish_time
	 *                $rs["_map_article"][$exclude_articles[n]]["update_time"], // article.update_time
	 *                $rs["_map_article"][$exclude_articles[n]]["create_time"], // article.create_time
	 *                $rs["_map_article"][$exclude_articles[n]]["baidulink_time"], // article.baidulink_time
	 *                $rs["_map_article"][$exclude_articles[n]]["sync"], // article.sync
	 *                $rs["_map_article"][$exclude_articles[n]]["content"], // article.content
	 *                $rs["_map_article"][$exclude_articles[n]]["ap_content"], // article.ap_content
	 *                $rs["_map_article"][$exclude_articles[n]]["delta"], // article.delta
	 *                $rs["_map_article"][$exclude_articles[n]]["param"], // article.param
	 *                $rs["_map_article"][$exclude_articles[n]]["stick"], // article.stick
	 *                $rs["_map_article"][$exclude_articles[n]]["preview"], // article.preview
	 *                $rs["_map_article"][$exclude_articles[n]]["links"], // article.links
	 *                $rs["_map_article"][$exclude_articles[n]]["user"], // article.user
	 *                $rs["_map_article"][$exclude_articles[n]]["policies"], // article.policies
	 *                $rs["_map_article"][$exclude_articles[n]]["status"], // article.status
	 *                $rs["_map_article"][$exclude_articles[n]]["keywords"], // article.keywords
	 *                $rs["_map_article"][$exclude_articles[n]]["view_cnt"], // article.view_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["like_cnt"], // article.like_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["dislike_cnt"], // article.dislike_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["comment_cnt"], // article.comment_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["series"], // article.series
	 *                $rs["_map_article"][$exclude_articles[n]]["user_id"], // article.user_id
	 *                $rs["_map_article"][$exclude_articles[n]]["policies_detail"], // article.policies_detail
	 *                $rs["_map_article"][$exclude_articles[n]]["agree_cnt"], // article.agree_cnt
	 *                $rs["_map_article"][$exclude_articles[n]]["priority"], // article.priority
	 *                $rs["_map_article"][$exclude_articles[n]]["coin_view"], // article.coin_view
	 *                $rs["_map_article"][$exclude_articles[n]]["money_view"], // article.money_view
	 *                $rs["_map_article"][$exclude_articles[n]]["specials"], // article.specials
	 *                $rs["_map_article"][$exclude_articles[n]]["history"], // article.history
	 *                $rs["_map_article"][$exclude_articles[n]]["policies_comment"], // article.policies_comment
	 *                $rs["_map_article"][$exclude_articles[n]]["policies_reward"], // article.policies_reward
	 *                $rs["_map_event"][$events[n]]["created_at"], // event.created_at
	 *                $rs["_map_event"][$events[n]]["updated_at"], // event.updated_at
	 *                $rs["_map_event"][$events[n]]["slug"], // event.slug
	 *                $rs["_map_event"][$events[n]]["name"], // event.name
	 *                $rs["_map_event"][$events[n]]["link"], // event.link
	 *                $rs["_map_event"][$events[n]]["categories"], // event.categories
	 *                $rs["_map_event"][$events[n]]["type"], // event.type
	 *                $rs["_map_event"][$events[n]]["tags"], // event.tags
	 *                $rs["_map_event"][$events[n]]["summary"], // event.summary
	 *                $rs["_map_event"][$events[n]]["cover"], // event.cover
	 *                $rs["_map_event"][$events[n]]["images"], // event.images
	 *                $rs["_map_event"][$events[n]]["begin"], // event.begin
	 *                $rs["_map_event"][$events[n]]["end"], // event.end
	 *                $rs["_map_event"][$events[n]]["area"], // event.area
	 *                $rs["_map_event"][$events[n]]["prov"], // event.prov
	 *                $rs["_map_event"][$events[n]]["city"], // event.city
	 *                $rs["_map_event"][$events[n]]["town"], // event.town
	 *                $rs["_map_event"][$events[n]]["location"], // event.location
	 *                $rs["_map_event"][$events[n]]["price"], // event.price
	 *                $rs["_map_event"][$events[n]]["hosts"], // event.hosts
	 *                $rs["_map_event"][$events[n]]["organizers"], // event.organizers
	 *                $rs["_map_event"][$events[n]]["sponsors"], // event.sponsors
	 *                $rs["_map_event"][$events[n]]["medias"], // event.medias
	 *                $rs["_map_event"][$events[n]]["speakers"], // event.speakers
	 *                $rs["_map_event"][$events[n]]["content"], // event.content
	 *                $rs["_map_event"][$events[n]]["publish_time"], // event.publish_time
	 *                $rs["_map_event"][$events[n]]["view_cnt"], // event.view_cnt
	 *                $rs["_map_event"][$events[n]]["like_cnt"], // event.like_cnt
	 *                $rs["_map_event"][$events[n]]["dislike_cnt"], // event.dislike_cnt
	 *                $rs["_map_event"][$events[n]]["comment_cnt"], // event.comment_cnt
	 *                $rs["_map_event"][$events[n]]["status"], // event.status
	 *                $rs["_map_event"][$exclude_events[n]]["created_at"], // event.created_at
	 *                $rs["_map_event"][$exclude_events[n]]["updated_at"], // event.updated_at
	 *                $rs["_map_event"][$exclude_events[n]]["slug"], // event.slug
	 *                $rs["_map_event"][$exclude_events[n]]["name"], // event.name
	 *                $rs["_map_event"][$exclude_events[n]]["link"], // event.link
	 *                $rs["_map_event"][$exclude_events[n]]["categories"], // event.categories
	 *                $rs["_map_event"][$exclude_events[n]]["type"], // event.type
	 *                $rs["_map_event"][$exclude_events[n]]["tags"], // event.tags
	 *                $rs["_map_event"][$exclude_events[n]]["summary"], // event.summary
	 *                $rs["_map_event"][$exclude_events[n]]["cover"], // event.cover
	 *                $rs["_map_event"][$exclude_events[n]]["images"], // event.images
	 *                $rs["_map_event"][$exclude_events[n]]["begin"], // event.begin
	 *                $rs["_map_event"][$exclude_events[n]]["end"], // event.end
	 *                $rs["_map_event"][$exclude_events[n]]["area"], // event.area
	 *                $rs["_map_event"][$exclude_events[n]]["prov"], // event.prov
	 *                $rs["_map_event"][$exclude_events[n]]["city"], // event.city
	 *                $rs["_map_event"][$exclude_events[n]]["town"], // event.town
	 *                $rs["_map_event"][$exclude_events[n]]["location"], // event.location
	 *                $rs["_map_event"][$exclude_events[n]]["price"], // event.price
	 *                $rs["_map_event"][$exclude_events[n]]["hosts"], // event.hosts
	 *                $rs["_map_event"][$exclude_events[n]]["organizers"], // event.organizers
	 *                $rs["_map_event"][$exclude_events[n]]["sponsors"], // event.sponsors
	 *                $rs["_map_event"][$exclude_events[n]]["medias"], // event.medias
	 *                $rs["_map_event"][$exclude_events[n]]["speakers"], // event.speakers
	 *                $rs["_map_event"][$exclude_events[n]]["content"], // event.content
	 *                $rs["_map_event"][$exclude_events[n]]["publish_time"], // event.publish_time
	 *                $rs["_map_event"][$exclude_events[n]]["view_cnt"], // event.view_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["like_cnt"], // event.like_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["dislike_cnt"], // event.dislike_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["comment_cnt"], // event.comment_cnt
	 *                $rs["_map_event"][$exclude_events[n]]["status"], // event.status
	 *                $rs["_map_album"][$albums[n]]["created_at"], // album.created_at
	 *                $rs["_map_album"][$albums[n]]["updated_at"], // album.updated_at
	 *                $rs["_map_album"][$albums[n]]["slug"], // album.slug
	 *                $rs["_map_album"][$albums[n]]["title"], // album.title
	 *                $rs["_map_album"][$albums[n]]["author"], // album.author
	 *                $rs["_map_album"][$albums[n]]["origin"], // album.origin
	 *                $rs["_map_album"][$albums[n]]["origin_url"], // album.origin_url
	 *                $rs["_map_album"][$albums[n]]["link"], // album.link
	 *                $rs["_map_album"][$albums[n]]["categories"], // album.categories
	 *                $rs["_map_album"][$albums[n]]["tags"], // album.tags
	 *                $rs["_map_album"][$albums[n]]["summary"], // album.summary
	 *                $rs["_map_album"][$albums[n]]["images"], // album.images
	 *                $rs["_map_album"][$albums[n]]["cover"], // album.cover
	 *                $rs["_map_album"][$albums[n]]["publish_time"], // album.publish_time
	 *                $rs["_map_album"][$albums[n]]["view_cnt"], // album.view_cnt
	 *                $rs["_map_album"][$albums[n]]["like_cnt"], // album.like_cnt
	 *                $rs["_map_album"][$albums[n]]["dislike_cnt"], // album.dislike_cnt
	 *                $rs["_map_album"][$albums[n]]["comment_cnt"], // album.comment_cnt
	 *                $rs["_map_album"][$albums[n]]["status"], // album.status
	 *                $rs["_map_album"][$albums[n]]["series"], // album.series
	 *                $rs["_map_album"][$exclude_albums[n]]["created_at"], // album.created_at
	 *                $rs["_map_album"][$exclude_albums[n]]["updated_at"], // album.updated_at
	 *                $rs["_map_album"][$exclude_albums[n]]["slug"], // album.slug
	 *                $rs["_map_album"][$exclude_albums[n]]["title"], // album.title
	 *                $rs["_map_album"][$exclude_albums[n]]["author"], // album.author
	 *                $rs["_map_album"][$exclude_albums[n]]["origin"], // album.origin
	 *                $rs["_map_album"][$exclude_albums[n]]["origin_url"], // album.origin_url
	 *                $rs["_map_album"][$exclude_albums[n]]["link"], // album.link
	 *                $rs["_map_album"][$exclude_albums[n]]["categories"], // album.categories
	 *                $rs["_map_album"][$exclude_albums[n]]["tags"], // album.tags
	 *                $rs["_map_album"][$exclude_albums[n]]["summary"], // album.summary
	 *                $rs["_map_album"][$exclude_albums[n]]["images"], // album.images
	 *                $rs["_map_album"][$exclude_albums[n]]["cover"], // album.cover
	 *                $rs["_map_album"][$exclude_albums[n]]["publish_time"], // album.publish_time
	 *                $rs["_map_album"][$exclude_albums[n]]["view_cnt"], // album.view_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["like_cnt"], // album.like_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["dislike_cnt"], // album.dislike_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["comment_cnt"], // album.comment_cnt
	 *                $rs["_map_album"][$exclude_albums[n]]["status"], // album.status
	 *                $rs["_map_album"][$exclude_albums[n]]["series"], // album.series
	 *                $rs["_map_question"][$questions[n]]["created_at"], // question.created_at
	 *                $rs["_map_question"][$questions[n]]["updated_at"], // question.updated_at
	 *                $rs["_map_question"][$questions[n]]["user_id"], // question.user_id
	 *                $rs["_map_question"][$questions[n]]["title"], // question.title
	 *                $rs["_map_question"][$questions[n]]["summary"], // question.summary
	 *                $rs["_map_question"][$questions[n]]["content"], // question.content
	 *                $rs["_map_question"][$questions[n]]["category_ids"], // question.category_ids
	 *                $rs["_map_question"][$questions[n]]["series_ids"], // question.series_ids
	 *                $rs["_map_question"][$questions[n]]["tags"], // question.tags
	 *                $rs["_map_question"][$questions[n]]["view_cnt"], // question.view_cnt
	 *                $rs["_map_question"][$questions[n]]["agree_cnt"], // question.agree_cnt
	 *                $rs["_map_question"][$questions[n]]["answer_cnt"], // question.answer_cnt
	 *                $rs["_map_question"][$questions[n]]["priority"], // question.priority
	 *                $rs["_map_question"][$questions[n]]["status"], // question.status
	 *                $rs["_map_question"][$questions[n]]["publish_time"], // question.publish_time
	 *                $rs["_map_question"][$questions[n]]["coin"], // question.coin
	 *                $rs["_map_question"][$questions[n]]["money"], // question.money
	 *                $rs["_map_question"][$questions[n]]["coin_view"], // question.coin_view
	 *                $rs["_map_question"][$questions[n]]["money_view"], // question.money_view
	 *                $rs["_map_question"][$questions[n]]["policies"], // question.policies
	 *                $rs["_map_question"][$questions[n]]["policies_detail"], // question.policies_detail
	 *                $rs["_map_question"][$questions[n]]["anonymous"], // question.anonymous
	 *                $rs["_map_question"][$questions[n]]["cover"], // question.cover
	 *                $rs["_map_question"][$questions[n]]["history"], // question.history
	 *                $rs["_map_question"][$exclude_questions[n]]["created_at"], // question.created_at
	 *                $rs["_map_question"][$exclude_questions[n]]["updated_at"], // question.updated_at
	 *                $rs["_map_question"][$exclude_questions[n]]["user_id"], // question.user_id
	 *                $rs["_map_question"][$exclude_questions[n]]["title"], // question.title
	 *                $rs["_map_question"][$exclude_questions[n]]["summary"], // question.summary
	 *                $rs["_map_question"][$exclude_questions[n]]["content"], // question.content
	 *                $rs["_map_question"][$exclude_questions[n]]["category_ids"], // question.category_ids
	 *                $rs["_map_question"][$exclude_questions[n]]["series_ids"], // question.series_ids
	 *                $rs["_map_question"][$exclude_questions[n]]["tags"], // question.tags
	 *                $rs["_map_question"][$exclude_questions[n]]["view_cnt"], // question.view_cnt
	 *                $rs["_map_question"][$exclude_questions[n]]["agree_cnt"], // question.agree_cnt
	 *                $rs["_map_question"][$exclude_questions[n]]["answer_cnt"], // question.answer_cnt
	 *                $rs["_map_question"][$exclude_questions[n]]["priority"], // question.priority
	 *                $rs["_map_question"][$exclude_questions[n]]["status"], // question.status
	 *                $rs["_map_question"][$exclude_questions[n]]["publish_time"], // question.publish_time
	 *                $rs["_map_question"][$exclude_questions[n]]["coin"], // question.coin
	 *                $rs["_map_question"][$exclude_questions[n]]["money"], // question.money
	 *                $rs["_map_question"][$exclude_questions[n]]["coin_view"], // question.coin_view
	 *                $rs["_map_question"][$exclude_questions[n]]["money_view"], // question.money_view
	 *                $rs["_map_question"][$exclude_questions[n]]["policies"], // question.policies
	 *                $rs["_map_question"][$exclude_questions[n]]["policies_detail"], // question.policies_detail
	 *                $rs["_map_question"][$exclude_questions[n]]["anonymous"], // question.anonymous
	 *                $rs["_map_question"][$exclude_questions[n]]["cover"], // question.cover
	 *                $rs["_map_question"][$exclude_questions[n]]["history"], // question.history
	 *                $rs["_map_answer"][$answers[n]]["created_at"], // answer.created_at
	 *                $rs["_map_answer"][$answers[n]]["updated_at"], // answer.updated_at
	 *                $rs["_map_answer"][$answers[n]]["question_id"], // answer.question_id
	 *                $rs["_map_answer"][$answers[n]]["user_id"], // answer.user_id
	 *                $rs["_map_answer"][$answers[n]]["content"], // answer.content
	 *                $rs["_map_answer"][$answers[n]]["publish_time"], // answer.publish_time
	 *                $rs["_map_answer"][$answers[n]]["policies"], // answer.policies
	 *                $rs["_map_answer"][$answers[n]]["policies_detail"], // answer.policies_detail
	 *                $rs["_map_answer"][$answers[n]]["priority"], // answer.priority
	 *                $rs["_map_answer"][$answers[n]]["view_cnt"], // answer.view_cnt
	 *                $rs["_map_answer"][$answers[n]]["agree_cnt"], // answer.agree_cnt
	 *                $rs["_map_answer"][$answers[n]]["coin"], // answer.coin
	 *                $rs["_map_answer"][$answers[n]]["money"], // answer.money
	 *                $rs["_map_answer"][$answers[n]]["coin_view"], // answer.coin_view
	 *                $rs["_map_answer"][$answers[n]]["money_view"], // answer.money_view
	 *                $rs["_map_answer"][$answers[n]]["anonymous"], // answer.anonymous
	 *                $rs["_map_answer"][$answers[n]]["accepted"], // answer.accepted
	 *                $rs["_map_answer"][$answers[n]]["status"], // answer.status
	 *                $rs["_map_answer"][$answers[n]]["history"], // answer.history
	 *                $rs["_map_answer"][$answers[n]]["summary"], // answer.summary
	 *                $rs["_map_answer"][$exclude_answers[n]]["created_at"], // answer.created_at
	 *                $rs["_map_answer"][$exclude_answers[n]]["updated_at"], // answer.updated_at
	 *                $rs["_map_answer"][$exclude_answers[n]]["question_id"], // answer.question_id
	 *                $rs["_map_answer"][$exclude_answers[n]]["user_id"], // answer.user_id
	 *                $rs["_map_answer"][$exclude_answers[n]]["content"], // answer.content
	 *                $rs["_map_answer"][$exclude_answers[n]]["publish_time"], // answer.publish_time
	 *                $rs["_map_answer"][$exclude_answers[n]]["policies"], // answer.policies
	 *                $rs["_map_answer"][$exclude_answers[n]]["policies_detail"], // answer.policies_detail
	 *                $rs["_map_answer"][$exclude_answers[n]]["priority"], // answer.priority
	 *                $rs["_map_answer"][$exclude_answers[n]]["view_cnt"], // answer.view_cnt
	 *                $rs["_map_answer"][$exclude_answers[n]]["agree_cnt"], // answer.agree_cnt
	 *                $rs["_map_answer"][$exclude_answers[n]]["coin"], // answer.coin
	 *                $rs["_map_answer"][$exclude_answers[n]]["money"], // answer.money
	 *                $rs["_map_answer"][$exclude_answers[n]]["coin_view"], // answer.coin_view
	 *                $rs["_map_answer"][$exclude_answers[n]]["money_view"], // answer.money_view
	 *                $rs["_map_answer"][$exclude_answers[n]]["anonymous"], // answer.anonymous
	 *                $rs["_map_answer"][$exclude_answers[n]]["accepted"], // answer.accepted
	 *                $rs["_map_answer"][$exclude_answers[n]]["status"], // answer.status
	 *                $rs["_map_answer"][$exclude_answers[n]]["history"], // answer.history
	 *                $rs["_map_answer"][$exclude_answers[n]]["summary"], // answer.summary
	 *                $rs["_map_goods"][$goods[n]]["created_at"], // goods.created_at
	 *                $rs["_map_goods"][$goods[n]]["updated_at"], // goods.updated_at
	 *                $rs["_map_goods"][$goods[n]]["instance"], // goods.instance
	 *                $rs["_map_goods"][$goods[n]]["name"], // goods.name
	 *                $rs["_map_goods"][$goods[n]]["slug"], // goods.slug
	 *                $rs["_map_goods"][$goods[n]]["tags"], // goods.tags
	 *                $rs["_map_goods"][$goods[n]]["category_ids"], // goods.category_ids
	 *                $rs["_map_goods"][$goods[n]]["recommend_ids"], // goods.recommend_ids
	 *                $rs["_map_goods"][$goods[n]]["summary"], // goods.summary
	 *                $rs["_map_goods"][$goods[n]]["cover"], // goods.cover
	 *                $rs["_map_goods"][$goods[n]]["images"], // goods.images
	 *                $rs["_map_goods"][$goods[n]]["videos"], // goods.videos
	 *                $rs["_map_goods"][$goods[n]]["params"], // goods.params
	 *                $rs["_map_goods"][$goods[n]]["content"], // goods.content
	 *                $rs["_map_goods"][$goods[n]]["content_faq"], // goods.content_faq
	 *                $rs["_map_goods"][$goods[n]]["content_serv"], // goods.content_serv
	 *                $rs["_map_goods"][$goods[n]]["sku_cnt"], // goods.sku_cnt
	 *                $rs["_map_goods"][$goods[n]]["sku_sum"], // goods.sku_sum
	 *                $rs["_map_goods"][$goods[n]]["shipped_sum"], // goods.shipped_sum
	 *                $rs["_map_goods"][$goods[n]]["available_sum"], // goods.available_sum
	 *                $rs["_map_goods"][$goods[n]]["lower_price"], // goods.lower_price
	 *                $rs["_map_goods"][$goods[n]]["sale_way"], // goods.sale_way
	 *                $rs["_map_goods"][$goods[n]]["opened_at"], // goods.opened_at
	 *                $rs["_map_goods"][$goods[n]]["closed_at"], // goods.closed_at
	 *                $rs["_map_goods"][$goods[n]]["pay_duration"], // goods.pay_duration
	 *                $rs["_map_goods"][$goods[n]]["status"], // goods.status
	 *                $rs["_map_goods"][$goods[n]]["events"], // goods.events
	 *                $rs["_map_goods"][$exclude_goods[n]]["created_at"], // goods.created_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["updated_at"], // goods.updated_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["instance"], // goods.instance
	 *                $rs["_map_goods"][$exclude_goods[n]]["name"], // goods.name
	 *                $rs["_map_goods"][$exclude_goods[n]]["slug"], // goods.slug
	 *                $rs["_map_goods"][$exclude_goods[n]]["tags"], // goods.tags
	 *                $rs["_map_goods"][$exclude_goods[n]]["category_ids"], // goods.category_ids
	 *                $rs["_map_goods"][$exclude_goods[n]]["recommend_ids"], // goods.recommend_ids
	 *                $rs["_map_goods"][$exclude_goods[n]]["summary"], // goods.summary
	 *                $rs["_map_goods"][$exclude_goods[n]]["cover"], // goods.cover
	 *                $rs["_map_goods"][$exclude_goods[n]]["images"], // goods.images
	 *                $rs["_map_goods"][$exclude_goods[n]]["videos"], // goods.videos
	 *                $rs["_map_goods"][$exclude_goods[n]]["params"], // goods.params
	 *                $rs["_map_goods"][$exclude_goods[n]]["content"], // goods.content
	 *                $rs["_map_goods"][$exclude_goods[n]]["content_faq"], // goods.content_faq
	 *                $rs["_map_goods"][$exclude_goods[n]]["content_serv"], // goods.content_serv
	 *                $rs["_map_goods"][$exclude_goods[n]]["sku_cnt"], // goods.sku_cnt
	 *                $rs["_map_goods"][$exclude_goods[n]]["sku_sum"], // goods.sku_sum
	 *                $rs["_map_goods"][$exclude_goods[n]]["shipped_sum"], // goods.shipped_sum
	 *                $rs["_map_goods"][$exclude_goods[n]]["available_sum"], // goods.available_sum
	 *                $rs["_map_goods"][$exclude_goods[n]]["lower_price"], // goods.lower_price
	 *                $rs["_map_goods"][$exclude_goods[n]]["sale_way"], // goods.sale_way
	 *                $rs["_map_goods"][$exclude_goods[n]]["opened_at"], // goods.opened_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["closed_at"], // goods.closed_at
	 *                $rs["_map_goods"][$exclude_goods[n]]["pay_duration"], // goods.pay_duration
	 *                $rs["_map_goods"][$exclude_goods[n]]["status"], // goods.status
	 *                $rs["_map_goods"][$exclude_goods[n]]["events"], // goods.events
	 *                $rs["_map_series"][$series[n]]["created_at"], // series.created_at
	 *                $rs["_map_series"][$series[n]]["updated_at"], // series.updated_at
	 *                $rs["_map_series"][$series[n]]["name"], // series.name
	 *                $rs["_map_series"][$series[n]]["slug"], // series.slug
	 *                $rs["_map_series"][$series[n]]["category_id"], // series.category_id
	 *                $rs["_map_series"][$series[n]]["summary"], // series.summary
	 *                $rs["_map_series"][$series[n]]["orderby"], // series.orderby
	 *                $rs["_map_series"][$series[n]]["param"], // series.param
	 *                $rs["_map_series"][$series[n]]["status"], // series.status
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
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
	 *                $rs["_map_topic"][$topics[n]]["created_at"], // topic.created_at
	 *                $rs["_map_topic"][$topics[n]]["updated_at"], // topic.updated_at
	 *                $rs["_map_topic"][$topics[n]]["name"], // topic.name
	 *                $rs["_map_topic"][$topics[n]]["param"], // topic.param
	 *                $rs["_map_topic"][$topics[n]]["article_cnt"], // topic.article_cnt
	 *                $rs["_map_topic"][$topics[n]]["album_cnt"], // topic.album_cnt
	 *                $rs["_map_topic"][$topics[n]]["event_cnt"], // topic.event_cnt
	 *                $rs["_map_topic"][$topics[n]]["goods_cnt"], // topic.goods_cnt
	 *                $rs["_map_topic"][$topics[n]]["question_cnt"], // topic.question_cnt
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
               		$qb->where('recommend.slug', '=', $slug );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$article_ids = []; // 读取 inWhere article 数据
		$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 		$article_ids = []; // 读取 inWhere article 数据
		$article_ids = array_merge($article_ids, is_array($rs["exclude_articles"]) ? $rs["exclude_articles"] : [$rs["exclude_articles"]]);
 		$event_ids = []; // 读取 inWhere event 数据
		$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 		$event_ids = []; // 读取 inWhere event 数据
		$event_ids = array_merge($event_ids, is_array($rs["exclude_events"]) ? $rs["exclude_events"] : [$rs["exclude_events"]]);
 		$album_ids = []; // 读取 inWhere album 数据
		$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 		$album_ids = []; // 读取 inWhere album 数据
		$album_ids = array_merge($album_ids, is_array($rs["exclude_albums"]) ? $rs["exclude_albums"] : [$rs["exclude_albums"]]);
 		$question_ids = []; // 读取 inWhere question 数据
		$question_ids = array_merge($question_ids, is_array($rs["questions"]) ? $rs["questions"] : [$rs["questions"]]);
 		$question_ids = []; // 读取 inWhere question 数据
		$question_ids = array_merge($question_ids, is_array($rs["exclude_questions"]) ? $rs["exclude_questions"] : [$rs["exclude_questions"]]);
 		$answer_ids = []; // 读取 inWhere answer 数据
		$answer_ids = array_merge($answer_ids, is_array($rs["answers"]) ? $rs["answers"] : [$rs["answers"]]);
 		$answer_ids = []; // 读取 inWhere answer 数据
		$answer_ids = array_merge($answer_ids, is_array($rs["exclude_answers"]) ? $rs["exclude_answers"] : [$rs["exclude_answers"]]);
 		$goods_ids = []; // 读取 inWhere goods 数据
		$goods_ids = array_merge($goods_ids, is_array($rs["goods"]) ? $rs["goods"] : [$rs["goods"]]);
 		$goods_ids = []; // 读取 inWhere goods 数据
		$goods_ids = array_merge($goods_ids, is_array($rs["exclude_goods"]) ? $rs["exclude_goods"] : [$rs["exclude_goods"]]);
 		$series_ids = []; // 读取 inWhere series 数据
		$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 		$topic_ids = []; // 读取 inWhere topic 数据
		$topic_ids = array_merge($topic_ids, is_array($rs["topics"]) ? $rs["topics"] : [$rs["topics"]]);

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$rs["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$rs["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$rs["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$rs["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$rs["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$rs["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$rs["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$rs["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$rs["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$rs["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$rs["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$rs["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$rs["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere topic 数据
		if ( !empty($inwhereSelect["topic"]) && method_exists("\\Xpmsns\\Pages\\Model\\Topic", 'getInByTopicId') ) {
			$topic_ids = array_unique($topic_ids);
			$selectFields = $inwhereSelect["topic"];
			$rs["_map_topic"] = (new \Xpmsns\Pages\Model\Topic)->getInByTopicId($topic_ids, $selectFields);
		}

		return $rs;
	}

	

	/**
	 * 按别名查询一组推荐记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 推荐记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at","recommend.status"], $order=["recommend.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
               		$qb->whereIn('recommend.slug', $slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$article_ids = []; // 读取 inWhere article 数据
 		$article_ids = []; // 读取 inWhere article 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$series_ids = []; // 读取 inWhere series 数据
 		$category_ids = []; // 读取 inWhere category 数据
 		$topic_ids = []; // 读取 inWhere topic 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["exclude_articles"]) ? $rs["exclude_articles"] : [$rs["exclude_articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["exclude_events"]) ? $rs["exclude_events"] : [$rs["exclude_events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["exclude_albums"]) ? $rs["exclude_albums"] : [$rs["exclude_albums"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["questions"]) ? $rs["questions"] : [$rs["questions"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["exclude_questions"]) ? $rs["exclude_questions"] : [$rs["exclude_questions"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["answers"]) ? $rs["answers"] : [$rs["answers"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["exclude_answers"]) ? $rs["exclude_answers"] : [$rs["exclude_answers"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods"]) ? $rs["goods"] : [$rs["goods"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["exclude_goods"]) ? $rs["exclude_goods"] : [$rs["exclude_goods"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere topic
			$topic_ids = array_merge($topic_ids, is_array($rs["topics"]) ? $rs["topics"] : [$rs["topics"]]);
		}

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$map["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$map["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$map["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$map["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$map["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$map["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$map["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$map["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$map["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$map["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$map["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$map["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$map["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere topic 数据
		if ( !empty($inwhereSelect["topic"]) && method_exists("\\Xpmsns\\Pages\\Model\\Topic", 'getInByTopicId') ) {
			$topic_ids = array_unique($topic_ids);
			$selectFields = $inwhereSelect["topic"];
			$map["_map_topic"] = (new \Xpmsns\Pages\Model\Topic)->getInByTopicId($topic_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按别名保存推荐记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("slug", $data, ["recommend_id", "slug"], ['_id', 'recommend_id']);
		return $this->getByRecommendId( $rs['recommend_id'], $select );
	}

	/**
	 * 根据推荐ID上传图标。
	 * @param string $recommend_id 推荐ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIconByRecommendId($recommend_id, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('recommend_id', ["recommend_id"=>$recommend_id, "icon"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据推荐ID上传摘要图片。
	 * @param string $recommend_id 推荐ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByRecommendId($recommend_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('recommend_id', $recommend_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('recommend_id', ["recommend_id"=>$recommend_id, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据别名上传图标。
	 * @param string $slug 别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadIconBySlug($slug, $file_path, $upload_only=false ) {

		$fs =  $this->media->uploadFile( $file_path );
		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "icon"=>$fs['path']]);
		}
		return $fs;
	}

	/**
	 * 根据别名上传摘要图片。
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
	 * 添加推荐记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["recommend_id"]) ) { 
			$data["recommend_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排推荐记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 推荐记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at","recommend.status"], $order=["recommend.created_at"=>"asc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
               

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$article_ids = []; // 读取 inWhere article 数据
 		$article_ids = []; // 读取 inWhere article 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$series_ids = []; // 读取 inWhere series 数据
 		$category_ids = []; // 读取 inWhere category 数据
 		$topic_ids = []; // 读取 inWhere topic 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["exclude_articles"]) ? $rs["exclude_articles"] : [$rs["exclude_articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["exclude_events"]) ? $rs["exclude_events"] : [$rs["exclude_events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["exclude_albums"]) ? $rs["exclude_albums"] : [$rs["exclude_albums"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["questions"]) ? $rs["questions"] : [$rs["questions"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["exclude_questions"]) ? $rs["exclude_questions"] : [$rs["exclude_questions"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["answers"]) ? $rs["answers"] : [$rs["answers"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["exclude_answers"]) ? $rs["exclude_answers"] : [$rs["exclude_answers"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods"]) ? $rs["goods"] : [$rs["goods"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["exclude_goods"]) ? $rs["exclude_goods"] : [$rs["exclude_goods"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere topic
			$topic_ids = array_merge($topic_ids, is_array($rs["topics"]) ? $rs["topics"] : [$rs["topics"]]);
		}

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$data["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$data["_map_article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$data["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$data["_map_event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$data["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$data["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$data["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$data["_map_question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$data["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$data["_map_answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$data["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$data["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$data["_map_series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere topic 数据
		if ( !empty($inwhereSelect["topic"]) && method_exists("\\Xpmsns\\Pages\\Model\\Topic", 'getInByTopicId') ) {
			$topic_ids = array_unique($topic_ids);
			$selectFields = $inwhereSelect["topic"];
			$data["_map_topic"] = (new \Xpmsns\Pages\Model\Topic)->getInByTopicId($topic_ids, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索推荐记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at","recommend.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["recommend_id"] 按推荐ID查询 ( = )
	 *			      $query["slug"] 按推荐别名查询 ( IN )
	 *			      $query["pos"] 按位置代码查询 ( = )
	 *			      $query["type"] 按推荐方式查询 ( = )
	 *			      $query["period"] 按统计周期查询 ( = )
	 *			      $query["title"] 按主题查询 ( LIKE )
	 *			      $query["ctype"] 按内容类型查询 ( = )
	 *			      $query["thumb_only"] 按必须有主题图片查询 ( = )
	 *			      $query["bigdata_engine"] 按喜好推荐查询 ( = )
	 *			      $query["video_only"] 按必须有视频内容查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_asc"]  按更新时间 ASC 排序
	 *           
	 * @return array 推荐记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["recommend_id"],  // 推荐ID 
	 *               	["title"],  // 主题 
	 *               	["summary"],  // 简介 
	 *               	["icon"],  // 图标 
	 *               	["slug"],  // 别名 
	 *               	["pos"],  // 呈现位置 
	 *               	["style"],  // 样式代码 
	 *               	["type"],  // 方式 
	 *               	["ctype"],  // 内容类型 
	 *               	["thumb_only"],  // 必须包含主题图片 
	 *               	["video_only"],  // 必须包含视频 
	 *               	["bigdata_engine"],  // 根据用户喜好推荐 
	 *               	["period"],  // 周期 
	 *               	["images"],  // 摘要图片 
	 *               	["tpl_pc"],  // PC端模板 
	 *               	["tpl_h5"],  // 手机端模板 
	 *               	["tpl_wxapp"],  // 小程序模板 
	 *               	["tpl_android"],  // 安卓模板 
	 *               	["tpl_ios"],  // iOS模板 
	 *               	["keywords"],  // 关键词 
	 *               	["series"],  // 指定系列 
	 *               	["series"][$series[n]]["series_id"], // series.series_id
	 *               	["categories"],  // 指定栏目 
	 *               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["topics"],  // 指定话题 
	 *               	["topic"][$topics[n]]["topic_id"], // topic.topic_id
	 *               	["article_select"],  // 文章字段 
	 *               	["article_status"],  // 文章状态 
	 *               	["articles"],  // 指定文章 
	 *               	["article"][$articles[n]]["article_id"], // article.article_id
	 *               	["exclude_articles"],  // 排除文章 
	 *               	["article"][$exclude_articles[n]]["article_id"], // article.article_id
	 *               	["event_select"],  // 活动字段 
	 *               	["event_status"],  // 活动状态 
	 *               	["events"],  // 指定活动 
	 *               	["event"][$events[n]]["event_id"], // event.event_id
	 *               	["exclude_events"],  // 排除活动 
	 *               	["event"][$exclude_events[n]]["event_id"], // event.event_id
	 *               	["album_select"],  // 图集字段 
	 *               	["album_status"],  // 图集状态 
	 *               	["albums"],  // 指定图集 
	 *               	["album"][$albums[n]]["album_id"], // album.album_id
	 *               	["exclude_albums"],  // 排除图集 
	 *               	["album"][$exclude_albums[n]]["album_id"], // album.album_id
	 *               	["question_select"],  // 提问字段 
	 *               	["question_status"],  // 提问状态 
	 *               	["questions"],  // 指定提问 
	 *               	["question"][$questions[n]]["question_id"], // question.question_id
	 *               	["exclude_questions"],  // 排除提问 
	 *               	["question"][$exclude_questions[n]]["question_id"], // question.question_id
	 *               	["answer_select"],  // 回答字段 
	 *               	["answer_status"],  // 回答状态 
	 *               	["answers"],  // 指定回答 
	 *               	["answer"][$answers[n]]["answer_id"], // answer.answer_id
	 *               	["exclude_answers"],  // 排除回答 
	 *               	["answer"][$exclude_answers[n]]["answer_id"], // answer.answer_id
	 *               	["goods_select"],  // 商品字段 
	 *               	["goods_status"],  // 商品状态 
	 *               	["goods"],  // 指定商品 
	 *               	["goods"][$goods[n]]["goods_id"], // goods.goods_id
	 *               	["exclude_goods"],  // 排除商品 
	 *               	["goods"][$exclude_goods[n]]["goods_id"], // goods.goods_id
	 *               	["orderby"],  // 排序方式 
	 *               	["ttl"],  // 缓存时间 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["article"][$articles[n]]["created_at"], // article.created_at
	 *               	["article"][$articles[n]]["updated_at"], // article.updated_at
	 *               	["article"][$articles[n]]["outer_id"], // article.outer_id
	 *               	["article"][$articles[n]]["cover"], // article.cover
	 *               	["article"][$articles[n]]["thumbs"], // article.thumbs
	 *               	["article"][$articles[n]]["images"], // article.images
	 *               	["article"][$articles[n]]["videos"], // article.videos
	 *               	["article"][$articles[n]]["audios"], // article.audios
	 *               	["article"][$articles[n]]["title"], // article.title
	 *               	["article"][$articles[n]]["author"], // article.author
	 *               	["article"][$articles[n]]["origin"], // article.origin
	 *               	["article"][$articles[n]]["origin_url"], // article.origin_url
	 *               	["article"][$articles[n]]["summary"], // article.summary
	 *               	["article"][$articles[n]]["seo_title"], // article.seo_title
	 *               	["article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	 *               	["article"][$articles[n]]["seo_summary"], // article.seo_summary
	 *               	["article"][$articles[n]]["publish_time"], // article.publish_time
	 *               	["article"][$articles[n]]["update_time"], // article.update_time
	 *               	["article"][$articles[n]]["create_time"], // article.create_time
	 *               	["article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	 *               	["article"][$articles[n]]["sync"], // article.sync
	 *               	["article"][$articles[n]]["content"], // article.content
	 *               	["article"][$articles[n]]["ap_content"], // article.ap_content
	 *               	["article"][$articles[n]]["delta"], // article.delta
	 *               	["article"][$articles[n]]["param"], // article.param
	 *               	["article"][$articles[n]]["stick"], // article.stick
	 *               	["article"][$articles[n]]["preview"], // article.preview
	 *               	["article"][$articles[n]]["links"], // article.links
	 *               	["article"][$articles[n]]["user"], // article.user
	 *               	["article"][$articles[n]]["policies"], // article.policies
	 *               	["article"][$articles[n]]["status"], // article.status
	 *               	["article"][$articles[n]]["keywords"], // article.keywords
	 *               	["article"][$articles[n]]["view_cnt"], // article.view_cnt
	 *               	["article"][$articles[n]]["like_cnt"], // article.like_cnt
	 *               	["article"][$articles[n]]["dislike_cnt"], // article.dislike_cnt
	 *               	["article"][$articles[n]]["comment_cnt"], // article.comment_cnt
	 *               	["article"][$articles[n]]["series"], // article.series
	 *               	["article"][$articles[n]]["user_id"], // article.user_id
	 *               	["article"][$articles[n]]["policies_detail"], // article.policies_detail
	 *               	["article"][$articles[n]]["agree_cnt"], // article.agree_cnt
	 *               	["article"][$articles[n]]["priority"], // article.priority
	 *               	["article"][$articles[n]]["coin_view"], // article.coin_view
	 *               	["article"][$articles[n]]["money_view"], // article.money_view
	 *               	["article"][$articles[n]]["specials"], // article.specials
	 *               	["article"][$articles[n]]["history"], // article.history
	 *               	["article"][$articles[n]]["policies_comment"], // article.policies_comment
	 *               	["article"][$articles[n]]["policies_reward"], // article.policies_reward
	 *               	["article"][$exclude_articles[n]]["created_at"], // article.created_at
	 *               	["article"][$exclude_articles[n]]["updated_at"], // article.updated_at
	 *               	["article"][$exclude_articles[n]]["outer_id"], // article.outer_id
	 *               	["article"][$exclude_articles[n]]["cover"], // article.cover
	 *               	["article"][$exclude_articles[n]]["thumbs"], // article.thumbs
	 *               	["article"][$exclude_articles[n]]["images"], // article.images
	 *               	["article"][$exclude_articles[n]]["videos"], // article.videos
	 *               	["article"][$exclude_articles[n]]["audios"], // article.audios
	 *               	["article"][$exclude_articles[n]]["title"], // article.title
	 *               	["article"][$exclude_articles[n]]["author"], // article.author
	 *               	["article"][$exclude_articles[n]]["origin"], // article.origin
	 *               	["article"][$exclude_articles[n]]["origin_url"], // article.origin_url
	 *               	["article"][$exclude_articles[n]]["summary"], // article.summary
	 *               	["article"][$exclude_articles[n]]["seo_title"], // article.seo_title
	 *               	["article"][$exclude_articles[n]]["seo_keywords"], // article.seo_keywords
	 *               	["article"][$exclude_articles[n]]["seo_summary"], // article.seo_summary
	 *               	["article"][$exclude_articles[n]]["publish_time"], // article.publish_time
	 *               	["article"][$exclude_articles[n]]["update_time"], // article.update_time
	 *               	["article"][$exclude_articles[n]]["create_time"], // article.create_time
	 *               	["article"][$exclude_articles[n]]["baidulink_time"], // article.baidulink_time
	 *               	["article"][$exclude_articles[n]]["sync"], // article.sync
	 *               	["article"][$exclude_articles[n]]["content"], // article.content
	 *               	["article"][$exclude_articles[n]]["ap_content"], // article.ap_content
	 *               	["article"][$exclude_articles[n]]["delta"], // article.delta
	 *               	["article"][$exclude_articles[n]]["param"], // article.param
	 *               	["article"][$exclude_articles[n]]["stick"], // article.stick
	 *               	["article"][$exclude_articles[n]]["preview"], // article.preview
	 *               	["article"][$exclude_articles[n]]["links"], // article.links
	 *               	["article"][$exclude_articles[n]]["user"], // article.user
	 *               	["article"][$exclude_articles[n]]["policies"], // article.policies
	 *               	["article"][$exclude_articles[n]]["status"], // article.status
	 *               	["article"][$exclude_articles[n]]["keywords"], // article.keywords
	 *               	["article"][$exclude_articles[n]]["view_cnt"], // article.view_cnt
	 *               	["article"][$exclude_articles[n]]["like_cnt"], // article.like_cnt
	 *               	["article"][$exclude_articles[n]]["dislike_cnt"], // article.dislike_cnt
	 *               	["article"][$exclude_articles[n]]["comment_cnt"], // article.comment_cnt
	 *               	["article"][$exclude_articles[n]]["series"], // article.series
	 *               	["article"][$exclude_articles[n]]["user_id"], // article.user_id
	 *               	["article"][$exclude_articles[n]]["policies_detail"], // article.policies_detail
	 *               	["article"][$exclude_articles[n]]["agree_cnt"], // article.agree_cnt
	 *               	["article"][$exclude_articles[n]]["priority"], // article.priority
	 *               	["article"][$exclude_articles[n]]["coin_view"], // article.coin_view
	 *               	["article"][$exclude_articles[n]]["money_view"], // article.money_view
	 *               	["article"][$exclude_articles[n]]["specials"], // article.specials
	 *               	["article"][$exclude_articles[n]]["history"], // article.history
	 *               	["article"][$exclude_articles[n]]["policies_comment"], // article.policies_comment
	 *               	["article"][$exclude_articles[n]]["policies_reward"], // article.policies_reward
	 *               	["event"][$events[n]]["created_at"], // event.created_at
	 *               	["event"][$events[n]]["updated_at"], // event.updated_at
	 *               	["event"][$events[n]]["slug"], // event.slug
	 *               	["event"][$events[n]]["name"], // event.name
	 *               	["event"][$events[n]]["link"], // event.link
	 *               	["event"][$events[n]]["categories"], // event.categories
	 *               	["event"][$events[n]]["type"], // event.type
	 *               	["event"][$events[n]]["tags"], // event.tags
	 *               	["event"][$events[n]]["summary"], // event.summary
	 *               	["event"][$events[n]]["cover"], // event.cover
	 *               	["event"][$events[n]]["images"], // event.images
	 *               	["event"][$events[n]]["begin"], // event.begin
	 *               	["event"][$events[n]]["end"], // event.end
	 *               	["event"][$events[n]]["area"], // event.area
	 *               	["event"][$events[n]]["prov"], // event.prov
	 *               	["event"][$events[n]]["city"], // event.city
	 *               	["event"][$events[n]]["town"], // event.town
	 *               	["event"][$events[n]]["location"], // event.location
	 *               	["event"][$events[n]]["price"], // event.price
	 *               	["event"][$events[n]]["hosts"], // event.hosts
	 *               	["event"][$events[n]]["organizers"], // event.organizers
	 *               	["event"][$events[n]]["sponsors"], // event.sponsors
	 *               	["event"][$events[n]]["medias"], // event.medias
	 *               	["event"][$events[n]]["speakers"], // event.speakers
	 *               	["event"][$events[n]]["content"], // event.content
	 *               	["event"][$events[n]]["publish_time"], // event.publish_time
	 *               	["event"][$events[n]]["view_cnt"], // event.view_cnt
	 *               	["event"][$events[n]]["like_cnt"], // event.like_cnt
	 *               	["event"][$events[n]]["dislike_cnt"], // event.dislike_cnt
	 *               	["event"][$events[n]]["comment_cnt"], // event.comment_cnt
	 *               	["event"][$events[n]]["status"], // event.status
	 *               	["event"][$exclude_events[n]]["created_at"], // event.created_at
	 *               	["event"][$exclude_events[n]]["updated_at"], // event.updated_at
	 *               	["event"][$exclude_events[n]]["slug"], // event.slug
	 *               	["event"][$exclude_events[n]]["name"], // event.name
	 *               	["event"][$exclude_events[n]]["link"], // event.link
	 *               	["event"][$exclude_events[n]]["categories"], // event.categories
	 *               	["event"][$exclude_events[n]]["type"], // event.type
	 *               	["event"][$exclude_events[n]]["tags"], // event.tags
	 *               	["event"][$exclude_events[n]]["summary"], // event.summary
	 *               	["event"][$exclude_events[n]]["cover"], // event.cover
	 *               	["event"][$exclude_events[n]]["images"], // event.images
	 *               	["event"][$exclude_events[n]]["begin"], // event.begin
	 *               	["event"][$exclude_events[n]]["end"], // event.end
	 *               	["event"][$exclude_events[n]]["area"], // event.area
	 *               	["event"][$exclude_events[n]]["prov"], // event.prov
	 *               	["event"][$exclude_events[n]]["city"], // event.city
	 *               	["event"][$exclude_events[n]]["town"], // event.town
	 *               	["event"][$exclude_events[n]]["location"], // event.location
	 *               	["event"][$exclude_events[n]]["price"], // event.price
	 *               	["event"][$exclude_events[n]]["hosts"], // event.hosts
	 *               	["event"][$exclude_events[n]]["organizers"], // event.organizers
	 *               	["event"][$exclude_events[n]]["sponsors"], // event.sponsors
	 *               	["event"][$exclude_events[n]]["medias"], // event.medias
	 *               	["event"][$exclude_events[n]]["speakers"], // event.speakers
	 *               	["event"][$exclude_events[n]]["content"], // event.content
	 *               	["event"][$exclude_events[n]]["publish_time"], // event.publish_time
	 *               	["event"][$exclude_events[n]]["view_cnt"], // event.view_cnt
	 *               	["event"][$exclude_events[n]]["like_cnt"], // event.like_cnt
	 *               	["event"][$exclude_events[n]]["dislike_cnt"], // event.dislike_cnt
	 *               	["event"][$exclude_events[n]]["comment_cnt"], // event.comment_cnt
	 *               	["event"][$exclude_events[n]]["status"], // event.status
	 *               	["album"][$albums[n]]["created_at"], // album.created_at
	 *               	["album"][$albums[n]]["updated_at"], // album.updated_at
	 *               	["album"][$albums[n]]["slug"], // album.slug
	 *               	["album"][$albums[n]]["title"], // album.title
	 *               	["album"][$albums[n]]["author"], // album.author
	 *               	["album"][$albums[n]]["origin"], // album.origin
	 *               	["album"][$albums[n]]["origin_url"], // album.origin_url
	 *               	["album"][$albums[n]]["link"], // album.link
	 *               	["album"][$albums[n]]["categories"], // album.categories
	 *               	["album"][$albums[n]]["tags"], // album.tags
	 *               	["album"][$albums[n]]["summary"], // album.summary
	 *               	["album"][$albums[n]]["images"], // album.images
	 *               	["album"][$albums[n]]["cover"], // album.cover
	 *               	["album"][$albums[n]]["publish_time"], // album.publish_time
	 *               	["album"][$albums[n]]["view_cnt"], // album.view_cnt
	 *               	["album"][$albums[n]]["like_cnt"], // album.like_cnt
	 *               	["album"][$albums[n]]["dislike_cnt"], // album.dislike_cnt
	 *               	["album"][$albums[n]]["comment_cnt"], // album.comment_cnt
	 *               	["album"][$albums[n]]["status"], // album.status
	 *               	["album"][$albums[n]]["series"], // album.series
	 *               	["album"][$exclude_albums[n]]["created_at"], // album.created_at
	 *               	["album"][$exclude_albums[n]]["updated_at"], // album.updated_at
	 *               	["album"][$exclude_albums[n]]["slug"], // album.slug
	 *               	["album"][$exclude_albums[n]]["title"], // album.title
	 *               	["album"][$exclude_albums[n]]["author"], // album.author
	 *               	["album"][$exclude_albums[n]]["origin"], // album.origin
	 *               	["album"][$exclude_albums[n]]["origin_url"], // album.origin_url
	 *               	["album"][$exclude_albums[n]]["link"], // album.link
	 *               	["album"][$exclude_albums[n]]["categories"], // album.categories
	 *               	["album"][$exclude_albums[n]]["tags"], // album.tags
	 *               	["album"][$exclude_albums[n]]["summary"], // album.summary
	 *               	["album"][$exclude_albums[n]]["images"], // album.images
	 *               	["album"][$exclude_albums[n]]["cover"], // album.cover
	 *               	["album"][$exclude_albums[n]]["publish_time"], // album.publish_time
	 *               	["album"][$exclude_albums[n]]["view_cnt"], // album.view_cnt
	 *               	["album"][$exclude_albums[n]]["like_cnt"], // album.like_cnt
	 *               	["album"][$exclude_albums[n]]["dislike_cnt"], // album.dislike_cnt
	 *               	["album"][$exclude_albums[n]]["comment_cnt"], // album.comment_cnt
	 *               	["album"][$exclude_albums[n]]["status"], // album.status
	 *               	["album"][$exclude_albums[n]]["series"], // album.series
	 *               	["question"][$questions[n]]["created_at"], // question.created_at
	 *               	["question"][$questions[n]]["updated_at"], // question.updated_at
	 *               	["question"][$questions[n]]["user_id"], // question.user_id
	 *               	["question"][$questions[n]]["title"], // question.title
	 *               	["question"][$questions[n]]["summary"], // question.summary
	 *               	["question"][$questions[n]]["content"], // question.content
	 *               	["question"][$questions[n]]["category_ids"], // question.category_ids
	 *               	["question"][$questions[n]]["series_ids"], // question.series_ids
	 *               	["question"][$questions[n]]["tags"], // question.tags
	 *               	["question"][$questions[n]]["view_cnt"], // question.view_cnt
	 *               	["question"][$questions[n]]["agree_cnt"], // question.agree_cnt
	 *               	["question"][$questions[n]]["answer_cnt"], // question.answer_cnt
	 *               	["question"][$questions[n]]["priority"], // question.priority
	 *               	["question"][$questions[n]]["status"], // question.status
	 *               	["question"][$questions[n]]["publish_time"], // question.publish_time
	 *               	["question"][$questions[n]]["coin"], // question.coin
	 *               	["question"][$questions[n]]["money"], // question.money
	 *               	["question"][$questions[n]]["coin_view"], // question.coin_view
	 *               	["question"][$questions[n]]["money_view"], // question.money_view
	 *               	["question"][$questions[n]]["policies"], // question.policies
	 *               	["question"][$questions[n]]["policies_detail"], // question.policies_detail
	 *               	["question"][$questions[n]]["anonymous"], // question.anonymous
	 *               	["question"][$questions[n]]["cover"], // question.cover
	 *               	["question"][$questions[n]]["history"], // question.history
	 *               	["question"][$exclude_questions[n]]["created_at"], // question.created_at
	 *               	["question"][$exclude_questions[n]]["updated_at"], // question.updated_at
	 *               	["question"][$exclude_questions[n]]["user_id"], // question.user_id
	 *               	["question"][$exclude_questions[n]]["title"], // question.title
	 *               	["question"][$exclude_questions[n]]["summary"], // question.summary
	 *               	["question"][$exclude_questions[n]]["content"], // question.content
	 *               	["question"][$exclude_questions[n]]["category_ids"], // question.category_ids
	 *               	["question"][$exclude_questions[n]]["series_ids"], // question.series_ids
	 *               	["question"][$exclude_questions[n]]["tags"], // question.tags
	 *               	["question"][$exclude_questions[n]]["view_cnt"], // question.view_cnt
	 *               	["question"][$exclude_questions[n]]["agree_cnt"], // question.agree_cnt
	 *               	["question"][$exclude_questions[n]]["answer_cnt"], // question.answer_cnt
	 *               	["question"][$exclude_questions[n]]["priority"], // question.priority
	 *               	["question"][$exclude_questions[n]]["status"], // question.status
	 *               	["question"][$exclude_questions[n]]["publish_time"], // question.publish_time
	 *               	["question"][$exclude_questions[n]]["coin"], // question.coin
	 *               	["question"][$exclude_questions[n]]["money"], // question.money
	 *               	["question"][$exclude_questions[n]]["coin_view"], // question.coin_view
	 *               	["question"][$exclude_questions[n]]["money_view"], // question.money_view
	 *               	["question"][$exclude_questions[n]]["policies"], // question.policies
	 *               	["question"][$exclude_questions[n]]["policies_detail"], // question.policies_detail
	 *               	["question"][$exclude_questions[n]]["anonymous"], // question.anonymous
	 *               	["question"][$exclude_questions[n]]["cover"], // question.cover
	 *               	["question"][$exclude_questions[n]]["history"], // question.history
	 *               	["answer"][$answers[n]]["created_at"], // answer.created_at
	 *               	["answer"][$answers[n]]["updated_at"], // answer.updated_at
	 *               	["answer"][$answers[n]]["question_id"], // answer.question_id
	 *               	["answer"][$answers[n]]["user_id"], // answer.user_id
	 *               	["answer"][$answers[n]]["content"], // answer.content
	 *               	["answer"][$answers[n]]["publish_time"], // answer.publish_time
	 *               	["answer"][$answers[n]]["policies"], // answer.policies
	 *               	["answer"][$answers[n]]["policies_detail"], // answer.policies_detail
	 *               	["answer"][$answers[n]]["priority"], // answer.priority
	 *               	["answer"][$answers[n]]["view_cnt"], // answer.view_cnt
	 *               	["answer"][$answers[n]]["agree_cnt"], // answer.agree_cnt
	 *               	["answer"][$answers[n]]["coin"], // answer.coin
	 *               	["answer"][$answers[n]]["money"], // answer.money
	 *               	["answer"][$answers[n]]["coin_view"], // answer.coin_view
	 *               	["answer"][$answers[n]]["money_view"], // answer.money_view
	 *               	["answer"][$answers[n]]["anonymous"], // answer.anonymous
	 *               	["answer"][$answers[n]]["accepted"], // answer.accepted
	 *               	["answer"][$answers[n]]["status"], // answer.status
	 *               	["answer"][$answers[n]]["history"], // answer.history
	 *               	["answer"][$answers[n]]["summary"], // answer.summary
	 *               	["answer"][$exclude_answers[n]]["created_at"], // answer.created_at
	 *               	["answer"][$exclude_answers[n]]["updated_at"], // answer.updated_at
	 *               	["answer"][$exclude_answers[n]]["question_id"], // answer.question_id
	 *               	["answer"][$exclude_answers[n]]["user_id"], // answer.user_id
	 *               	["answer"][$exclude_answers[n]]["content"], // answer.content
	 *               	["answer"][$exclude_answers[n]]["publish_time"], // answer.publish_time
	 *               	["answer"][$exclude_answers[n]]["policies"], // answer.policies
	 *               	["answer"][$exclude_answers[n]]["policies_detail"], // answer.policies_detail
	 *               	["answer"][$exclude_answers[n]]["priority"], // answer.priority
	 *               	["answer"][$exclude_answers[n]]["view_cnt"], // answer.view_cnt
	 *               	["answer"][$exclude_answers[n]]["agree_cnt"], // answer.agree_cnt
	 *               	["answer"][$exclude_answers[n]]["coin"], // answer.coin
	 *               	["answer"][$exclude_answers[n]]["money"], // answer.money
	 *               	["answer"][$exclude_answers[n]]["coin_view"], // answer.coin_view
	 *               	["answer"][$exclude_answers[n]]["money_view"], // answer.money_view
	 *               	["answer"][$exclude_answers[n]]["anonymous"], // answer.anonymous
	 *               	["answer"][$exclude_answers[n]]["accepted"], // answer.accepted
	 *               	["answer"][$exclude_answers[n]]["status"], // answer.status
	 *               	["answer"][$exclude_answers[n]]["history"], // answer.history
	 *               	["answer"][$exclude_answers[n]]["summary"], // answer.summary
	 *               	["goods"][$goods[n]]["created_at"], // goods.created_at
	 *               	["goods"][$goods[n]]["updated_at"], // goods.updated_at
	 *               	["goods"][$goods[n]]["instance"], // goods.instance
	 *               	["goods"][$goods[n]]["name"], // goods.name
	 *               	["goods"][$goods[n]]["slug"], // goods.slug
	 *               	["goods"][$goods[n]]["tags"], // goods.tags
	 *               	["goods"][$goods[n]]["category_ids"], // goods.category_ids
	 *               	["goods"][$goods[n]]["recommend_ids"], // goods.recommend_ids
	 *               	["goods"][$goods[n]]["summary"], // goods.summary
	 *               	["goods"][$goods[n]]["cover"], // goods.cover
	 *               	["goods"][$goods[n]]["images"], // goods.images
	 *               	["goods"][$goods[n]]["videos"], // goods.videos
	 *               	["goods"][$goods[n]]["params"], // goods.params
	 *               	["goods"][$goods[n]]["content"], // goods.content
	 *               	["goods"][$goods[n]]["content_faq"], // goods.content_faq
	 *               	["goods"][$goods[n]]["content_serv"], // goods.content_serv
	 *               	["goods"][$goods[n]]["sku_cnt"], // goods.sku_cnt
	 *               	["goods"][$goods[n]]["sku_sum"], // goods.sku_sum
	 *               	["goods"][$goods[n]]["shipped_sum"], // goods.shipped_sum
	 *               	["goods"][$goods[n]]["available_sum"], // goods.available_sum
	 *               	["goods"][$goods[n]]["lower_price"], // goods.lower_price
	 *               	["goods"][$goods[n]]["sale_way"], // goods.sale_way
	 *               	["goods"][$goods[n]]["opened_at"], // goods.opened_at
	 *               	["goods"][$goods[n]]["closed_at"], // goods.closed_at
	 *               	["goods"][$goods[n]]["pay_duration"], // goods.pay_duration
	 *               	["goods"][$goods[n]]["status"], // goods.status
	 *               	["goods"][$goods[n]]["events"], // goods.events
	 *               	["goods"][$exclude_goods[n]]["created_at"], // goods.created_at
	 *               	["goods"][$exclude_goods[n]]["updated_at"], // goods.updated_at
	 *               	["goods"][$exclude_goods[n]]["instance"], // goods.instance
	 *               	["goods"][$exclude_goods[n]]["name"], // goods.name
	 *               	["goods"][$exclude_goods[n]]["slug"], // goods.slug
	 *               	["goods"][$exclude_goods[n]]["tags"], // goods.tags
	 *               	["goods"][$exclude_goods[n]]["category_ids"], // goods.category_ids
	 *               	["goods"][$exclude_goods[n]]["recommend_ids"], // goods.recommend_ids
	 *               	["goods"][$exclude_goods[n]]["summary"], // goods.summary
	 *               	["goods"][$exclude_goods[n]]["cover"], // goods.cover
	 *               	["goods"][$exclude_goods[n]]["images"], // goods.images
	 *               	["goods"][$exclude_goods[n]]["videos"], // goods.videos
	 *               	["goods"][$exclude_goods[n]]["params"], // goods.params
	 *               	["goods"][$exclude_goods[n]]["content"], // goods.content
	 *               	["goods"][$exclude_goods[n]]["content_faq"], // goods.content_faq
	 *               	["goods"][$exclude_goods[n]]["content_serv"], // goods.content_serv
	 *               	["goods"][$exclude_goods[n]]["sku_cnt"], // goods.sku_cnt
	 *               	["goods"][$exclude_goods[n]]["sku_sum"], // goods.sku_sum
	 *               	["goods"][$exclude_goods[n]]["shipped_sum"], // goods.shipped_sum
	 *               	["goods"][$exclude_goods[n]]["available_sum"], // goods.available_sum
	 *               	["goods"][$exclude_goods[n]]["lower_price"], // goods.lower_price
	 *               	["goods"][$exclude_goods[n]]["sale_way"], // goods.sale_way
	 *               	["goods"][$exclude_goods[n]]["opened_at"], // goods.opened_at
	 *               	["goods"][$exclude_goods[n]]["closed_at"], // goods.closed_at
	 *               	["goods"][$exclude_goods[n]]["pay_duration"], // goods.pay_duration
	 *               	["goods"][$exclude_goods[n]]["status"], // goods.status
	 *               	["goods"][$exclude_goods[n]]["events"], // goods.events
	 *               	["series"][$series[n]]["created_at"], // series.created_at
	 *               	["series"][$series[n]]["updated_at"], // series.updated_at
	 *               	["series"][$series[n]]["name"], // series.name
	 *               	["series"][$series[n]]["slug"], // series.slug
	 *               	["series"][$series[n]]["category_id"], // series.category_id
	 *               	["series"][$series[n]]["summary"], // series.summary
	 *               	["series"][$series[n]]["orderby"], // series.orderby
	 *               	["series"][$series[n]]["param"], // series.param
	 *               	["series"][$series[n]]["status"], // series.status
	 *               	["category"][$categories[n]]["created_at"], // category.created_at
	 *               	["category"][$categories[n]]["updated_at"], // category.updated_at
	 *               	["category"][$categories[n]]["slug"], // category.slug
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
	 *               	["topic"][$topics[n]]["created_at"], // topic.created_at
	 *               	["topic"][$topics[n]]["updated_at"], // topic.updated_at
	 *               	["topic"][$topics[n]]["name"], // topic.name
	 *               	["topic"][$topics[n]]["param"], // topic.param
	 *               	["topic"][$topics[n]]["article_cnt"], // topic.article_cnt
	 *               	["topic"][$topics[n]]["album_cnt"], // topic.album_cnt
	 *               	["topic"][$topics[n]]["event_cnt"], // topic.event_cnt
	 *               	["topic"][$topics[n]]["goods_cnt"], // topic.goods_cnt
	 *               	["topic"][$topics[n]]["question_cnt"], // topic.question_cnt
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at","recommend.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "recommend.recommend_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_recommend as recommend", "{none}")->query();
               
		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("recommend.recommend_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("recommend.title","like", "%{$query['keyword']}%");
				$qb->orWhere("recommend.slug","like", "%{$query['keyword']}%");
			});
		}


		// 按推荐ID查询 (=)  
		if ( array_key_exists("recommend_id", $query) &&!empty($query['recommend_id']) ) {
			$qb->where("recommend.recommend_id", '=', "{$query['recommend_id']}" );
		}
		  
		// 按推荐别名查询 (IN)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			if ( is_string($query['slug']) ) {
				$query['slug'] = explode(',', $query['slug']);
			}
			$qb->whereIn("recommend.slug",  $query['slug'] );
		}
		  
		// 按位置代码查询 (=)  
		if ( array_key_exists("pos", $query) &&!empty($query['pos']) ) {
			$qb->where("recommend.pos", '=', "{$query['pos']}" );
		}
		  
		// 按推荐方式查询 (=)  
		if ( array_key_exists("type", $query) &&!empty($query['type']) ) {
			$qb->where("recommend.type", '=', "{$query['type']}" );
		}
		  
		// 按统计周期查询 (=)  
		if ( array_key_exists("period", $query) &&!empty($query['period']) ) {
			$qb->where("recommend.period", '=', "{$query['period']}" );
		}
		  
		// 按主题查询 (LIKE)  
		if ( array_key_exists("title", $query) &&!empty($query['title']) ) {
			$qb->where("recommend.title", 'like', "%{$query['title']}%" );
		}
		  
		// 按内容类型查询 (=)  
		if ( array_key_exists("ctype", $query) &&!empty($query['ctype']) ) {
			$qb->where("recommend.ctype", '=', "{$query['ctype']}" );
		}
		  
		// 按必须有主题图片查询 (=)  
		if ( array_key_exists("thumb_only", $query) &&!empty($query['thumb_only']) ) {
			$qb->where("recommend.thumb_only", '=', "{$query['thumb_only']}" );
		}
		  
		// 按喜好推荐查询 (=)  
		if ( array_key_exists("bigdata_engine", $query) &&!empty($query['bigdata_engine']) ) {
			$qb->where("recommend.bigdata_engine", '=', "{$query['bigdata_engine']}" );
		}
		  
		// 按必须有视频内容查询 (=)  
		if ( array_key_exists("video_only", $query) &&!empty($query['video_only']) ) {
			$qb->where("recommend.video_only", '=', "{$query['video_only']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("recommend.status", '=', "{$query['status']}" );
		}
		  

		// 按创建时间 ASC 排序
		if ( array_key_exists("orderby_created_at_asc", $query) &&!empty($query['orderby_created_at_asc']) ) {
			$qb->orderBy("recommend.created_at", "asc");
		}

		// 按更新时间 ASC 排序
		if ( array_key_exists("orderby_updated_at_asc", $query) &&!empty($query['orderby_updated_at_asc']) ) {
			$qb->orderBy("recommend.updated_at", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$recommends = $qb->select( $select )->pgArray($perpage, ['recommend._id'], 'page', $page);

 		$article_ids = []; // 读取 inWhere article 数据
 		$article_ids = []; // 读取 inWhere article 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$question_ids = []; // 读取 inWhere question 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$answer_ids = []; // 读取 inWhere answer 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$goods_ids = []; // 读取 inWhere goods 数据
 		$series_ids = []; // 读取 inWhere series 数据
 		$category_ids = []; // 读取 inWhere category 数据
 		$topic_ids = []; // 读取 inWhere topic 数据
		foreach ($recommends['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["exclude_articles"]) ? $rs["exclude_articles"] : [$rs["exclude_articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["exclude_events"]) ? $rs["exclude_events"] : [$rs["exclude_events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["exclude_albums"]) ? $rs["exclude_albums"] : [$rs["exclude_albums"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["questions"]) ? $rs["questions"] : [$rs["questions"]]);
 			// for inWhere question
			$question_ids = array_merge($question_ids, is_array($rs["exclude_questions"]) ? $rs["exclude_questions"] : [$rs["exclude_questions"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["answers"]) ? $rs["answers"] : [$rs["answers"]]);
 			// for inWhere answer
			$answer_ids = array_merge($answer_ids, is_array($rs["exclude_answers"]) ? $rs["exclude_answers"] : [$rs["exclude_answers"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods"]) ? $rs["goods"] : [$rs["goods"]]);
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["exclude_goods"]) ? $rs["exclude_goods"] : [$rs["exclude_goods"]]);
 			// for inWhere series
			$series_ids = array_merge($series_ids, is_array($rs["series"]) ? $rs["series"] : [$rs["series"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
 			// for inWhere topic
			$topic_ids = array_merge($topic_ids, is_array($rs["topics"]) ? $rs["topics"] : [$rs["topics"]]);
		}

 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$recommends["article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere article 数据
		if ( !empty($inwhereSelect["article"]) && method_exists("\\Xpmsns\\Pages\\Model\\Article", 'getInByArticleId') ) {
			$article_ids = array_unique($article_ids);
			$selectFields = $inwhereSelect["article"];
			$recommends["article"] = (new \Xpmsns\Pages\Model\Article)->getInByArticleId($article_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$recommends["event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere event 数据
		if ( !empty($inwhereSelect["event"]) && method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getInByEventId') ) {
			$event_ids = array_unique($event_ids);
			$selectFields = $inwhereSelect["event"];
			$recommends["event"] = (new \Xpmsns\Pages\Model\Event)->getInByEventId($event_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$recommends["album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$recommends["album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$recommends["question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere question 数据
		if ( !empty($inwhereSelect["question"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Question", 'getInByQuestionId') ) {
			$question_ids = array_unique($question_ids);
			$selectFields = $inwhereSelect["question"];
			$recommends["question"] = (new \Xpmsns\Qanda\Model\Question)->getInByQuestionId($question_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$recommends["answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere answer 数据
		if ( !empty($inwhereSelect["answer"]) && method_exists("\\Xpmsns\\Qanda\\Model\\Answer", 'getInByAnswerId') ) {
			$answer_ids = array_unique($answer_ids);
			$selectFields = $inwhereSelect["answer"];
			$recommends["answer"] = (new \Xpmsns\Qanda\Model\Answer)->getInByAnswerId($answer_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$recommends["goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$recommends["goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere series 数据
		if ( !empty($inwhereSelect["series"]) && method_exists("\\Xpmsns\\Pages\\Model\\Series", 'getInBySeriesId') ) {
			$series_ids = array_unique($series_ids);
			$selectFields = $inwhereSelect["series"];
			$recommends["series"] = (new \Xpmsns\Pages\Model\Series)->getInBySeriesId($series_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$recommends["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere topic 数据
		if ( !empty($inwhereSelect["topic"]) && method_exists("\\Xpmsns\\Pages\\Model\\Topic", 'getInByTopicId') ) {
			$topic_ids = array_unique($topic_ids);
			$selectFields = $inwhereSelect["topic"];
			$recommends["topic"] = (new \Xpmsns\Pages\Model\Topic)->getInByTopicId($topic_ids, $selectFields);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$recommends['_sql'] = $qb->getSql();
			$recommends['query'] = $query;
		}

		return $recommends;
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
				$select[$idx] = "recommend." .$select[$idx];
				continue;
			}
			
			// 连接文章 (article as a )
			if ( strpos( $fd, "a." ) === 0 || strpos("article.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["article"][] = trim($arr[1]);
				$inwhereSelect["article"][] = "article_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.articles");
				}
			}
			
			// 连接不包含文章 (article as ea )
			if ( strpos( $fd, "ea." ) === 0 || strpos("article.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["article"][] = trim($arr[1]);
				$inwhereSelect["article"][] = "article_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.exclude_articles");
				}
			}
			
			// 连接活动 (event as evt )
			if ( strpos( $fd, "evt." ) === 0 || strpos("event.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["event"][] = trim($arr[1]);
				$inwhereSelect["event"][] = "event_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.events");
				}
			}
			
			// 连接不包含活动 (event as eevt )
			if ( strpos( $fd, "eevt." ) === 0 || strpos("event.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["event"][] = trim($arr[1]);
				$inwhereSelect["event"][] = "event_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.exclude_events");
				}
			}
			
			// 连接图集 (album as al )
			if ( strpos( $fd, "al." ) === 0 || strpos("album.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["album"][] = trim($arr[1]);
				$inwhereSelect["album"][] = "album_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.albums");
				}
			}
			
			// 连接不包含图集 (album as eal )
			if ( strpos( $fd, "eal." ) === 0 || strpos("album.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["album"][] = trim($arr[1]);
				$inwhereSelect["album"][] = "album_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.exclude_albums");
				}
			}
			
			// 连接问题 (question as qu )
			if ( strpos( $fd, "qu." ) === 0 || strpos("question.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["question"][] = trim($arr[1]);
				$inwhereSelect["question"][] = "question_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.questions");
				}
			}
			
			// 连接不包含问题 (question as equ )
			if ( strpos( $fd, "equ." ) === 0 || strpos("question.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["question"][] = trim($arr[1]);
				$inwhereSelect["question"][] = "question_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.exclude_questions");
				}
			}
			
			// 连接回答 (answer as an )
			if ( strpos( $fd, "an." ) === 0 || strpos("answer.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["answer"][] = trim($arr[1]);
				$inwhereSelect["answer"][] = "answer_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.answers");
				}
			}
			
			// 连接不包含回答 (answer as ean )
			if ( strpos( $fd, "ean." ) === 0 || strpos("answer.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["answer"][] = trim($arr[1]);
				$inwhereSelect["answer"][] = "answer_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.exclude_answers");
				}
			}
			
			// 连接商品 (goods as g )
			if ( strpos( $fd, "g." ) === 0 || strpos("goods.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["goods"][] = trim($arr[1]);
				$inwhereSelect["goods"][] = "goods_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.goods");
				}
			}
			
			// 连接不包含商品 (goods as eg )
			if ( strpos( $fd, "eg." ) === 0 || strpos("goods.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["goods"][] = trim($arr[1]);
				$inwhereSelect["goods"][] = "goods_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.exclude_goods");
				}
			}
			
			// 连接系列 (series as s )
			if ( strpos( $fd, "s." ) === 0 || strpos("series.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["series"][] = trim($arr[1]);
				$inwhereSelect["series"][] = "series_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.series");
				}
			}
			
			// 连接栏目 (category as c )
			if ( strpos( $fd, "c." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "category_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.categories");
				}
			}
			
			// 连接话题 (topic as topic )
			if ( strpos( $fd, "topic." ) === 0 || strpos("topic.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["topic"][] = trim($arr[1]);
				$inwhereSelect["topic"][] = "topic_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "recommend.topics");
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
			"recommend_id",  // 推荐ID
			"title",  // 主题
			"summary",  // 简介
			"icon",  // 图标
			"slug",  // 别名
			"pos",  // 呈现位置
			"style",  // 样式代码
			"type",  // 方式
			"ctype",  // 内容类型
			"thumb_only",  // 必须包含主题图片
			"video_only",  // 必须包含视频
			"bigdata_engine",  // 根据用户喜好推荐
			"period",  // 周期
			"images",  // 摘要图片
			"tpl_pc",  // PC端模板
			"tpl_h5",  // 手机端模板
			"tpl_wxapp",  // 小程序模板
			"tpl_android",  // 安卓模板
			"tpl_ios",  // iOS模板
			"keywords",  // 关键词
			"series",  // 指定系列
			"categories",  // 指定栏目
			"topics",  // 指定话题
			"article_select",  // 文章字段
			"article_status",  // 文章状态
			"articles",  // 指定文章
			"exclude_articles",  // 排除文章
			"event_select",  // 活动字段
			"event_status",  // 活动状态
			"events",  // 指定活动
			"exclude_events",  // 排除活动
			"album_select",  // 图集字段
			"album_status",  // 图集状态
			"albums",  // 指定图集
			"exclude_albums",  // 排除图集
			"question_select",  // 提问字段
			"question_status",  // 提问状态
			"questions",  // 指定提问
			"exclude_questions",  // 排除提问
			"answer_select",  // 回答字段
			"answer_status",  // 回答状态
			"answers",  // 指定回答
			"exclude_answers",  // 排除回答
			"goods_select",  // 商品字段
			"goods_status",  // 商品状态
			"goods",  // 指定商品
			"exclude_goods",  // 排除商品
			"orderby",  // 排序方式
			"ttl",  // 缓存时间
			"status",  // 状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>