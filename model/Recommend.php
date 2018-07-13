<?php
/**
 * Class Recommend 
 * 推荐数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-07-05 14:26:48
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Recommend extends Model {


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
	 * 推荐数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('recommend'); // 数据表名称 xpmsns_pages_recommend
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

	}

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
	 * 自定义函数 按推荐ID选取推荐文章
	 */
	function getArticles(  $recommend_id,  $keywords=[], $now=null, $page=1, $perpage=20 ) {
		return $this->getArticlesBy('recommend_id', $recommend_id, $keywords, $now, $page, $perpage );
	}
	/**
	 * 自定义函数 按别名选取推荐文章
	 */
	function getArticlesBySlug(  $recommend_id,  $keywords=[], $now=null, $page=1, $perpage=20 ) {
		return $this->getArticlesBy('slug', $recommend_id,  $keywords, $now, $page, $perpage );
	}
	/**
	 * 自定义函数 按别名选取推荐内容
	 */
function getContentsBySlug(  $recommend_id,  $keywords=[],  $page=1, $perpage=20, $now=null ) {
		return $this->getContentsBy('slug', $recommend_id,  $keywords,  $page, $perpage,$now );
	}
	/**
	 * 自定义函数 按推荐ID选取推荐内容
	 */
function getContents(  $recommend_id,  $keywords=[],  $page=1, $perpage=20, $now=null ) {
		return $this->getContentsBy('recommendId', $recommend_id, $keywords,  $page, $perpage,$now );
	}
	/**
	 * 自定义函数 按Type选取推荐内容
	 */
function getContentsBy( $type,  $recommend_id,  $keywords=[], $page=1, $perpage=20, $now=null) {
		$select = [
					'recommend.title', 'recommend.summary', 'recommend.type', 'recommend.ctype', 'recommend.keywords', "recommend.period", 
					'recommend.thumb_only', 'recommend.video_only',
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
					$qb =  Utils::getTab("xpmsns_pages_album as content", "{none}")->query()->where('status','=', 'on');;
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


			if ( $recommend['thumb_only'] ) {
				$query['thumb_only'] = 1;
			}

			// 必须包含主题图片
			if ( $query['thumb_only'] ) {
				$qb->whereNotNull('content.cover');
			}


			// 按分类ID查找
			if ( array_key_exists('category_ids', $query)  && !empty($query['category_ids']) ) {
				$cids = is_string($query['category_ids']) ? explode(',', $query['category_ids']) : $query['category_ids'];
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
			$recommend['contents']['data'] = array_values($recommend['contents']['data']);
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
		// 方式
		$this->putColumn( 'type', $this->type("string", ["length"=>20, "index"=>true, "default"=>"auto", "null"=>true]));
		// 内容类型
		$this->putColumn( 'ctype', $this->type("string", ["length"=>20, "index"=>true, "default"=>"article", "null"=>true]));
		// 必须包含主题图片
		$this->putColumn( 'thumb_only', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 必须包含视频
		$this->putColumn( 'video_only', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
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
		// 相关栏目
		$this->putColumn( 'categories', $this->type("text", ["json"=>true, "null"=>true]));
		// 相关文章
		$this->putColumn( 'articles', $this->type("text", ["json"=>true, "null"=>true]));
		// 相关活动
		$this->putColumn( 'events', $this->type("text", ["json"=>true, "null"=>true]));
		// 相关图集
		$this->putColumn( 'albums', $this->type("text", ["json"=>true, "null"=>true]));
		// 排序方式
		$this->putColumn( 'orderby', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {

		// 格式化: 图标
		// 返回值: {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
		if ( array_key_exists('icon', $rs ) ) {
			$rs["icon"] = empty($rs["icon"]) ? [] : $this->media->get( $rs["icon"] );
		}

		// 格式化: 摘要图片
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
			$rs["images"] = !is_array($rs["images"]) ? [] : $rs["images"];
			foreach ($rs["images"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
		}


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
		  			"name" => "最新发表",
		  			"style" => "info"
		  		],
		  		"view_cnt" => [
		  			"value" => "view_cnt",
		  			"name" => "最多浏览",
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
	 *          	  $rs["type"],  // 方式 
	 *          	  $rs["ctype"],  // 内容类型 
	 *          	  $rs["thumb_only"],  // 必须包含主题图片 
	 *          	  $rs["video_only"],  // 必须包含视频 
	 *          	  $rs["period"],  // 周期 
	 *          	  $rs["images"],  // 摘要图片 
	 *          	  $rs["tpl_pc"],  // PC端模板 
	 *          	  $rs["tpl_h5"],  // 手机端模板 
	 *          	  $rs["tpl_wxapp"],  // 小程序模板 
	 *          	  $rs["tpl_android"],  // 安卓模板 
	 *          	  $rs["tpl_ios"],  // iOS模板 
	 *          	  $rs["keywords"],  // 关键词 
	 *          	  $rs["categories"],  // 相关栏目 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["articles"],  // 相关文章 
	 *                $rs["_map_article"][$articles[n]]["article_id"], // article.article_id
	 *          	  $rs["events"],  // 相关活动 
	 *                $rs["_map_event"][$events[n]]["event_id"], // event.event_id
	 *          	  $rs["albums"],  // 相关图集 
	 *                $rs["_map_album"][$albums[n]]["album_id"], // album.album_id
	 *          	  $rs["orderby"],  // 排序方式 
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
	 *                $rs["_map_event"][$events[n]]["created_at"], // event.created_at
	 *                $rs["_map_event"][$events[n]]["updated_at"], // event.updated_at
	 *                $rs["_map_event"][$events[n]]["slug"], // event.slug
	 *                $rs["_map_event"][$events[n]]["name"], // event.name
	 *                $rs["_map_event"][$events[n]]["categories"], // event.categories
	 *                $rs["_map_event"][$events[n]]["tags"], // event.tags
	 *                $rs["_map_event"][$events[n]]["summary"], // event.summary
	 *                $rs["_map_event"][$events[n]]["theme"], // event.theme
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
	 *                $rs["_map_event"][$events[n]]["status"], // event.status
	 *                $rs["_map_event"][$events[n]]["link"], // event.link
	 *                $rs["_map_event"][$events[n]]["type"], // event.type
	 *                $rs["_map_album"][$albums[n]]["created_at"], // album.created_at
	 *                $rs["_map_album"][$albums[n]]["updated_at"], // album.updated_at
	 *                $rs["_map_album"][$albums[n]]["images"], // album.images
	 *                $rs["_map_album"][$albums[n]]["title"], // album.title
	 *                $rs["_map_album"][$albums[n]]["summary"], // album.summary
	 *                $rs["_map_album"][$albums[n]]["slug"], // album.slug
	 *                $rs["_map_album"][$albums[n]]["link"], // album.link
	 *                $rs["_map_album"][$albums[n]]["categories"], // album.categories
	 *                $rs["_map_album"][$albums[n]]["tags"], // album.tags
	 *                $rs["_map_album"][$albums[n]]["theme"], // album.theme
	 *                $rs["_map_album"][$albums[n]]["status"], // album.status
	 *                $rs["_map_album"][$albums[n]]["author"], // album.author
	 *                $rs["_map_album"][$albums[n]]["origin"], // album.origin
	 *                $rs["_map_album"][$albums[n]]["origin_url"], // album.origin_url
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$categories[n]]["project"], // category.project
	 *                $rs["_map_category"][$categories[n]]["page"], // category.page
	 *                $rs["_map_category"][$categories[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$categories[n]]["name"], // category.name
	 *                $rs["_map_category"][$categories[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
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
    		$qb->where('recommend_id', '=', $recommend_id );
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
 		$event_ids = []; // 读取 inWhere event 数据
		$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 		$album_ids = []; // 读取 inWhere album 数据
		$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

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
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$rs["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
	public function getIn($recommend_ids, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at"], $order=["recommend.created_at"=>"asc"] ) {
		return $this->getInByRecommendId( $recommend_ids, $select, $order);
	}
	

	/**
	 * 按推荐ID查询一组推荐记录
	 * @param array   $recommend_ids 唯一主键数组 ["$recommend_id1","$recommend_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 推荐记录MAP {"recommend_id1":{"key":"value",...}...}
	 */
	public function getInByRecommendId($recommend_ids, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at"], $order=["recommend.created_at"=>"asc"] ) {
		
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
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['recommend_id']] = $rs;
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
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
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$map["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
	 *          	  $rs["type"],  // 方式 
	 *          	  $rs["ctype"],  // 内容类型 
	 *          	  $rs["thumb_only"],  // 必须包含主题图片 
	 *          	  $rs["video_only"],  // 必须包含视频 
	 *          	  $rs["period"],  // 周期 
	 *          	  $rs["images"],  // 摘要图片 
	 *          	  $rs["tpl_pc"],  // PC端模板 
	 *          	  $rs["tpl_h5"],  // 手机端模板 
	 *          	  $rs["tpl_wxapp"],  // 小程序模板 
	 *          	  $rs["tpl_android"],  // 安卓模板 
	 *          	  $rs["tpl_ios"],  // iOS模板 
	 *          	  $rs["keywords"],  // 关键词 
	 *          	  $rs["categories"],  // 相关栏目 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["articles"],  // 相关文章 
	 *                $rs["_map_article"][$articles[n]]["article_id"], // article.article_id
	 *          	  $rs["events"],  // 相关活动 
	 *                $rs["_map_event"][$events[n]]["event_id"], // event.event_id
	 *          	  $rs["albums"],  // 相关图集 
	 *                $rs["_map_album"][$albums[n]]["album_id"], // album.album_id
	 *          	  $rs["orderby"],  // 排序方式 
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
	 *                $rs["_map_event"][$events[n]]["created_at"], // event.created_at
	 *                $rs["_map_event"][$events[n]]["updated_at"], // event.updated_at
	 *                $rs["_map_event"][$events[n]]["slug"], // event.slug
	 *                $rs["_map_event"][$events[n]]["name"], // event.name
	 *                $rs["_map_event"][$events[n]]["categories"], // event.categories
	 *                $rs["_map_event"][$events[n]]["tags"], // event.tags
	 *                $rs["_map_event"][$events[n]]["summary"], // event.summary
	 *                $rs["_map_event"][$events[n]]["theme"], // event.theme
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
	 *                $rs["_map_event"][$events[n]]["status"], // event.status
	 *                $rs["_map_event"][$events[n]]["link"], // event.link
	 *                $rs["_map_event"][$events[n]]["type"], // event.type
	 *                $rs["_map_album"][$albums[n]]["created_at"], // album.created_at
	 *                $rs["_map_album"][$albums[n]]["updated_at"], // album.updated_at
	 *                $rs["_map_album"][$albums[n]]["images"], // album.images
	 *                $rs["_map_album"][$albums[n]]["title"], // album.title
	 *                $rs["_map_album"][$albums[n]]["summary"], // album.summary
	 *                $rs["_map_album"][$albums[n]]["slug"], // album.slug
	 *                $rs["_map_album"][$albums[n]]["link"], // album.link
	 *                $rs["_map_album"][$albums[n]]["categories"], // album.categories
	 *                $rs["_map_album"][$albums[n]]["tags"], // album.tags
	 *                $rs["_map_album"][$albums[n]]["theme"], // album.theme
	 *                $rs["_map_album"][$albums[n]]["status"], // album.status
	 *                $rs["_map_album"][$albums[n]]["author"], // album.author
	 *                $rs["_map_album"][$albums[n]]["origin"], // album.origin
	 *                $rs["_map_album"][$albums[n]]["origin_url"], // album.origin_url
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$categories[n]]["project"], // category.project
	 *                $rs["_map_category"][$categories[n]]["page"], // category.page
	 *                $rs["_map_category"][$categories[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$categories[n]]["name"], // category.name
	 *                $rs["_map_category"][$categories[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
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
    		$qb->where('slug', '=', $slug );
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
 		$event_ids = []; // 读取 inWhere event 数据
		$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 		$album_ids = []; // 读取 inWhere album 数据
		$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

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
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$rs["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
	public function getInBySlug($slugs, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at"], $order=["recommend.created_at"=>"asc"] ) {
		
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
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
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
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$map["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
	public function top( $limit=100, $select=["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at"], $order=["recommend.created_at"=>"asc"] ) {

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
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
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
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$data["_map_album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索推荐记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["recommend_id"] 按推荐ID查询 ( = )
	 *			      $query["type"] 按推荐方式查询 ( = )
	 *			      $query["period"] 按统计周期查询 ( = )
	 *			      $query["title"] 按主题查询 ( LIKE )
	 *			      $query["ctype"] 按内容类型查询 ( = )
	 *			      $query["thumb_only"] 按必须有主题图片查询 ( = )
	 *			      $query["video_only"] 按必须有视频内容查询 ( = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_asc"]  按更新时间 ASC 排序
	 *           
	 * @return array 推荐记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["recommend_id"],  // 推荐ID 
	 *               	["title"],  // 主题 
	 *               	["summary"],  // 简介 
	 *               	["icon"],  // 图标 
	 *               	["slug"],  // 别名 
	 *               	["type"],  // 方式 
	 *               	["ctype"],  // 内容类型 
	 *               	["thumb_only"],  // 必须包含主题图片 
	 *               	["video_only"],  // 必须包含视频 
	 *               	["period"],  // 周期 
	 *               	["images"],  // 摘要图片 
	 *               	["tpl_pc"],  // PC端模板 
	 *               	["tpl_h5"],  // 手机端模板 
	 *               	["tpl_wxapp"],  // 小程序模板 
	 *               	["tpl_android"],  // 安卓模板 
	 *               	["tpl_ios"],  // iOS模板 
	 *               	["keywords"],  // 关键词 
	 *               	["categories"],  // 相关栏目 
	 *               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["articles"],  // 相关文章 
	 *               	["article"][$articles[n]]["article_id"], // article.article_id
	 *               	["events"],  // 相关活动 
	 *               	["event"][$events[n]]["event_id"], // event.event_id
	 *               	["albums"],  // 相关图集 
	 *               	["album"][$albums[n]]["album_id"], // album.album_id
	 *               	["orderby"],  // 排序方式 
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
	 *               	["event"][$events[n]]["created_at"], // event.created_at
	 *               	["event"][$events[n]]["updated_at"], // event.updated_at
	 *               	["event"][$events[n]]["slug"], // event.slug
	 *               	["event"][$events[n]]["name"], // event.name
	 *               	["event"][$events[n]]["categories"], // event.categories
	 *               	["event"][$events[n]]["tags"], // event.tags
	 *               	["event"][$events[n]]["summary"], // event.summary
	 *               	["event"][$events[n]]["theme"], // event.theme
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
	 *               	["event"][$events[n]]["status"], // event.status
	 *               	["event"][$events[n]]["link"], // event.link
	 *               	["event"][$events[n]]["type"], // event.type
	 *               	["album"][$albums[n]]["created_at"], // album.created_at
	 *               	["album"][$albums[n]]["updated_at"], // album.updated_at
	 *               	["album"][$albums[n]]["images"], // album.images
	 *               	["album"][$albums[n]]["title"], // album.title
	 *               	["album"][$albums[n]]["summary"], // album.summary
	 *               	["album"][$albums[n]]["slug"], // album.slug
	 *               	["album"][$albums[n]]["link"], // album.link
	 *               	["album"][$albums[n]]["categories"], // album.categories
	 *               	["album"][$albums[n]]["tags"], // album.tags
	 *               	["album"][$albums[n]]["theme"], // album.theme
	 *               	["album"][$albums[n]]["status"], // album.status
	 *               	["album"][$albums[n]]["author"], // album.author
	 *               	["album"][$albums[n]]["origin"], // album.origin
	 *               	["album"][$albums[n]]["origin_url"], // album.origin_url
	 *               	["category"][$categories[n]]["created_at"], // category.created_at
	 *               	["category"][$categories[n]]["updated_at"], // category.updated_at
	 *               	["category"][$categories[n]]["slug"], // category.slug
	 *               	["category"][$categories[n]]["project"], // category.project
	 *               	["category"][$categories[n]]["page"], // category.page
	 *               	["category"][$categories[n]]["wechat"], // category.wechat
	 *               	["category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *               	["category"][$categories[n]]["name"], // category.name
	 *               	["category"][$categories[n]]["fullname"], // category.fullname
	 *               	["category"][$categories[n]]["root_id"], // category.root_id
	 *               	["category"][$categories[n]]["parent_id"], // category.parent_id
	 *               	["category"][$categories[n]]["priority"], // category.priority
	 *               	["category"][$categories[n]]["hidden"], // category.hidden
	 *               	["category"][$categories[n]]["param"], // category.param
	 *               	["category"][$categories[n]]["status"], // category.status
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["recommend.slug","recommend.title","recommend.type","recommend.ctype","recommend.period","recommend.orderby","recommend.keywords","recommend.created_at","recommend.updated_at"] : $query['select'];
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
		  
		// 按必须有视频内容查询 (=)  
		if ( array_key_exists("video_only", $query) &&!empty($query['video_only']) ) {
			$qb->where("recommend.video_only", '=', "{$query['video_only']}" );
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
 		$event_ids = []; // 读取 inWhere event 数据
 		$album_ids = []; // 读取 inWhere album 数据
 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($recommends['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere article
			$article_ids = array_merge($article_ids, is_array($rs["articles"]) ? $rs["articles"] : [$rs["articles"]]);
 			// for inWhere event
			$event_ids = array_merge($event_ids, is_array($rs["events"]) ? $rs["events"] : [$rs["events"]]);
 			// for inWhere album
			$album_ids = array_merge($album_ids, is_array($rs["albums"]) ? $rs["albums"] : [$rs["albums"]]);
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
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
 		// 读取 inWhere album 数据
		if ( !empty($inwhereSelect["album"]) && method_exists("\\Xpmsns\\Pages\\Model\\Album", 'getInByAlbumId') ) {
			$album_ids = array_unique($album_ids);
			$selectFields = $inwhereSelect["album"];
			$recommends["album"] = (new \Xpmsns\Pages\Model\Album)->getInByAlbumId($album_ids, $selectFields);
		}
 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$recommends["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
			"type",  // 方式
			"ctype",  // 内容类型
			"thumb_only",  // 必须包含主题图片
			"video_only",  // 必须包含视频
			"period",  // 周期
			"images",  // 摘要图片
			"tpl_pc",  // PC端模板
			"tpl_h5",  // 手机端模板
			"tpl_wxapp",  // 小程序模板
			"tpl_android",  // 安卓模板
			"tpl_ios",  // iOS模板
			"keywords",  // 关键词
			"categories",  // 相关栏目
			"articles",  // 相关文章
			"events",  // 相关活动
			"albums",  // 相关图集
			"orderby",  // 排序方式
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>