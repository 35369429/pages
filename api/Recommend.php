<?php
/**
 * Class Recommend 
 * 推荐数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-06-30 22:44:57
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                             

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Recommend extends Api {

	/**
	 * 推荐数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 读取推荐文章
	 */
	protected function getArticles( $query, $data ) {
		
		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		$inst = new \Xpmsns\Pages\Model\Recommend;
		$keywords = !empty($data['keywords']) ?  explode(',',$data['keywords']) : []; 
		$page = !empty($data['page']) ?  $data['page'] : 1; 
		$perpage = !empty($data['perpage']) ?  $data['perpage'] : 20; 
      	$now = !empty($data['now']) ?  $data['now'] : null; 
         

		if ( array_key_exists('slug', $data) && !empty($data['slug']) ) {
			return $inst->getArticlesBySlug( $data['slug'], $keywords, $now, $page, $perpage);
		} else if ( array_key_exists('recommend_id', $data) && !empty($data['recommend_id']) ) {
			return $inst->getArticles( $data['recommend_id'], $keywords, $now, $page, $perpage);
		}

		throw new Excp('错误的查询参数', 402, ['query'=>$query, 'data'=>$data]);
	}
	/**
	 * 自定义函数 读取推荐内容
	 */
       protected function getContents( $query, $data ) {
		
		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		$inst = new \Xpmsns\Pages\Model\Recommend;
		$keywords = !empty($data['keywords']) ?  explode(',',$data['keywords']) : []; 
		$page = !empty($data['page']) ?  $data['page'] : 1; 
		$perpage = !empty($data['perpage']) ?  $data['perpage'] : 20; 
      	$now = !empty($data['now']) ?  $data['now'] : null; 
         

		if ( array_key_exists('slug', $data) && !empty($data['slug']) ) {
			return $inst->getContentsBySlug( $data['slug'], $keywords,$page, $perpage, $now);
		} else if ( array_key_exists('recommend_id', $data) && !empty($data['recommend_id']) ) {
			return $inst->getContents( $data['recommend_id'], $keywords, $page, $perpage, $now);
		}

		throw new Excp('错误的查询参数', 402, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 查询一条推荐记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["recommend.recommend_id","recommend.title","recommend.slug","recommend.type","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"]
	 * 				 $query['recommend_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["recommend.recommend_id","recommend.title","recommend.slug","recommend.type","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"]
	 * 				 $data['recommend_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 推荐记录 Key Value 结构数据 
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
	*               	["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *               	["articles"],  // 相关文章 
	*               	["_map_article"][$articles[n]]["article_id"], // article.article_id
	 *               	["events"],  // 相关活动 
	*               	["_map_event"][$events[n]]["event_id"], // event.event_id
	 *               	["albums"],  // 相关图集 
	*               	["_map_album"][$albums[n]]["album_id"], // album.album_id
	 *               	["orderby"],  // 排序方式 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["_map_article"][$articles[n]]["created_at"], // article.created_at
	*               	["_map_article"][$articles[n]]["updated_at"], // article.updated_at
	*               	["_map_article"][$articles[n]]["outer_id"], // article.outer_id
	*               	["_map_article"][$articles[n]]["cover"], // article.cover
	*               	["_map_article"][$articles[n]]["thumbs"], // article.thumbs
	*               	["_map_article"][$articles[n]]["images"], // article.images
	*               	["_map_article"][$articles[n]]["videos"], // article.videos
	*               	["_map_article"][$articles[n]]["audios"], // article.audios
	*               	["_map_article"][$articles[n]]["title"], // article.title
	*               	["_map_article"][$articles[n]]["author"], // article.author
	*               	["_map_article"][$articles[n]]["origin"], // article.origin
	*               	["_map_article"][$articles[n]]["origin_url"], // article.origin_url
	*               	["_map_article"][$articles[n]]["summary"], // article.summary
	*               	["_map_article"][$articles[n]]["seo_title"], // article.seo_title
	*               	["_map_article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	*               	["_map_article"][$articles[n]]["seo_summary"], // article.seo_summary
	*               	["_map_article"][$articles[n]]["publish_time"], // article.publish_time
	*               	["_map_article"][$articles[n]]["update_time"], // article.update_time
	*               	["_map_article"][$articles[n]]["create_time"], // article.create_time
	*               	["_map_article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	*               	["_map_article"][$articles[n]]["sync"], // article.sync
	*               	["_map_article"][$articles[n]]["content"], // article.content
	*               	["_map_article"][$articles[n]]["ap_content"], // article.ap_content
	*               	["_map_article"][$articles[n]]["delta"], // article.delta
	*               	["_map_article"][$articles[n]]["param"], // article.param
	*               	["_map_article"][$articles[n]]["stick"], // article.stick
	*               	["_map_article"][$articles[n]]["preview"], // article.preview
	*               	["_map_article"][$articles[n]]["links"], // article.links
	*               	["_map_article"][$articles[n]]["user"], // article.user
	*               	["_map_article"][$articles[n]]["policies"], // article.policies
	*               	["_map_article"][$articles[n]]["status"], // article.status
	*               	["_map_article"][$articles[n]]["keywords"], // article.keywords
	*               	["_map_article"][$articles[n]]["view_cnt"], // article.view_cnt
	*               	["_map_article"][$articles[n]]["like_cnt"], // article.like_cnt
	*               	["_map_article"][$articles[n]]["dislike_cnt"], // article.dislike_cnt
	*               	["_map_article"][$articles[n]]["comment_cnt"], // article.comment_cnt
	*               	["_map_event"][$events[n]]["created_at"], // event.created_at
	*               	["_map_event"][$events[n]]["updated_at"], // event.updated_at
	*               	["_map_event"][$events[n]]["slug"], // event.slug
	*               	["_map_event"][$events[n]]["name"], // event.name
	*               	["_map_event"][$events[n]]["categories"], // event.categories
	*               	["_map_event"][$events[n]]["tags"], // event.tags
	*               	["_map_event"][$events[n]]["summary"], // event.summary
	*               	["_map_event"][$events[n]]["theme"], // event.theme
	*               	["_map_event"][$events[n]]["images"], // event.images
	*               	["_map_event"][$events[n]]["begin"], // event.begin
	*               	["_map_event"][$events[n]]["end"], // event.end
	*               	["_map_event"][$events[n]]["area"], // event.area
	*               	["_map_event"][$events[n]]["prov"], // event.prov
	*               	["_map_event"][$events[n]]["city"], // event.city
	*               	["_map_event"][$events[n]]["town"], // event.town
	*               	["_map_event"][$events[n]]["location"], // event.location
	*               	["_map_event"][$events[n]]["price"], // event.price
	*               	["_map_event"][$events[n]]["hosts"], // event.hosts
	*               	["_map_event"][$events[n]]["organizers"], // event.organizers
	*               	["_map_event"][$events[n]]["sponsors"], // event.sponsors
	*               	["_map_event"][$events[n]]["medias"], // event.medias
	*               	["_map_event"][$events[n]]["speakers"], // event.speakers
	*               	["_map_event"][$events[n]]["content"], // event.content
	*               	["_map_event"][$events[n]]["status"], // event.status
	*               	["_map_event"][$events[n]]["link"], // event.link
	*               	["_map_event"][$events[n]]["type"], // event.type
	*               	["_map_album"][$albums[n]]["created_at"], // album.created_at
	*               	["_map_album"][$albums[n]]["updated_at"], // album.updated_at
	*               	["_map_album"][$albums[n]]["images"], // album.images
	*               	["_map_album"][$albums[n]]["title"], // album.title
	*               	["_map_album"][$albums[n]]["summary"], // album.summary
	*               	["_map_album"][$albums[n]]["slug"], // album.slug
	*               	["_map_album"][$albums[n]]["link"], // album.link
	*               	["_map_album"][$albums[n]]["categories"], // album.categories
	*               	["_map_album"][$albums[n]]["tags"], // album.tags
	*               	["_map_album"][$albums[n]]["theme"], // album.theme
	*               	["_map_album"][$albums[n]]["status"], // album.status
	*               	["_map_album"][$albums[n]]["author"], // album.author
	*               	["_map_album"][$albums[n]]["origin"], // album.origin
	*               	["_map_album"][$albums[n]]["origin_url"], // album.origin_url
	*               	["_map_category"][$categories[n]]["created_at"], // category.created_at
	*               	["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["_map_category"][$categories[n]]["slug"], // category.slug
	*               	["_map_category"][$categories[n]]["project"], // category.project
	*               	["_map_category"][$categories[n]]["page"], // category.page
	*               	["_map_category"][$categories[n]]["wechat"], // category.wechat
	*               	["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["_map_category"][$categories[n]]["name"], // category.name
	*               	["_map_category"][$categories[n]]["fullname"], // category.fullname
	*               	["_map_category"][$categories[n]]["root_id"], // category.root_id
	*               	["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["_map_category"][$categories[n]]["priority"], // category.priority
	*               	["_map_category"][$categories[n]]["hidden"], // category.hidden
	*               	["_map_category"][$categories[n]]["param"], // category.param
	*               	["_map_category"][$categories[n]]["status"], // category.status
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["recommend.recommend_id","recommend.title","recommend.slug","recommend.type","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按推荐ID
		if ( !empty($data["recommend_id"]) ) {
			
			$keys = explode(',', $data["recommend_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Recommend;
				return $inst->getInByRecommendId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Recommend;
			return $inst->getByRecommendId($data["recommend_id"], $select);
		}

		// 按别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Recommend;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Recommend;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条推荐记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['recommend_id'] 推荐ID
	 *               $data['title'] 主题
	 *               $data['summary'] 简介
	 *               $data['icon'] 图标
	 *               $data['slug'] 别名
	 *               $data['type'] 方式
	 *               $data['ctype'] 内容类型
	 *               $data['thumb_only'] 必须包含主题图片
	 *               $data['video_only'] 必须包含视频
	 *               $data['period'] 周期
	 *               $data['images'] 摘要图片
	 *               $data['tpl_pc'] PC端模板
	 *               $data['tpl_h5'] 手机端模板
	 *               $data['tpl_wxapp'] 小程序模板
	 *               $data['tpl_android'] 安卓模板
	 *               $data['tpl_ios'] iOS模板
	 *               $data['keywords'] 关键词
	 *               $data['categories'] 相关栏目
	 *               $data['articles'] 相关文章
	 *               $data['events'] 相关活动
	 *               $data['albums'] 相关图集
	 *               $data['orderby'] 排序方式
	 *
	 * @return array 新增的推荐记录  @see get()
	 */
	protected function create( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		if (empty($data['title'])) {
			throw new Excp("缺少必填字段主题 (title)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['type'])) {
			throw new Excp("缺少必填字段方式 (type)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['ctype'])) {
			throw new Excp("缺少必填字段内容类型 (ctype)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\Pages\Model\Recommend;
		$rs = $inst->create( $data );
		return $inst->getByRecommendId($rs["recommend_id"]);
	}






	/**
	 * 根据条件检索推荐记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["recommend.recommend_id","recommend.title","recommend.type","recommend.images","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["recommend_id"] 按推荐ID查询 ( AND = )
	 *			      $query["type"] 按方式查询 ( AND = )
	 *			      $query["period"] 按周期查询 ( AND = )
	 *			      $query["title"] 按主题查询 ( AND LIKE )
	 *			      $query["ctype"] 按内容类型查询 ( AND = )
	 *			      $query["thumb_only"] 按必须包含主题图片查询 ( AND = )
	 *			      $query["video_only"] 按必须包含视频查询 ( AND = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_asc"]  按更新时间 ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=recommend_id","name=title","name=type","name=images","name=keywords","name=orderby","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CArticle&name=article_id&table=article&prefix=xpmsns_pages_&alias=a&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CArticle&name=title&table=article&prefix=xpmsns_pages_&alias=a&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CEvent&name=event_id&table=event&prefix=xpmsns_pages_&alias=evt&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CEvent&name=name&table=event&prefix=xpmsns_pages_&alias=evt&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CAlbum&name=album_id&table=album&prefix=xpmsns_pages_&alias=al&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CAlbum&name=title&table=album&prefix=xpmsns_pages_&alias=al&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["recommend_id"] 按推荐ID查询 ( AND = )
	 *			      $data["type"] 按方式查询 ( AND = )
	 *			      $data["period"] 按周期查询 ( AND = )
	 *			      $data["title"] 按主题查询 ( AND LIKE )
	 *			      $data["ctype"] 按内容类型查询 ( AND = )
	 *			      $data["thumb_only"] 按必须包含主题图片查询 ( AND = )
	 *			      $data["video_only"] 按必须包含视频查询 ( AND = )
	 *			      $data["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $data["orderby_updated_at_asc"]  按更新时间 ASC 排序
	 *
	 * @return array 推荐记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
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
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["recommend.recommend_id","recommend.title","recommend.type","recommend.images","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Recommend;
		return $inst->search( $data );
	}


}