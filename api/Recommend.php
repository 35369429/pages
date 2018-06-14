<?php
/**
 * Class Recommend 
 * 推荐数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-05-06 23:36:37
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

		echo "<pre>";
		var_dump($keywords);
		echo "</pre>";

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
	 * 查询一条推荐记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["recommend.recommend_id","recommend.title","recommend.slug","recommend.type","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","c.category_id","c.name"]
	 * 				 $query['recommend_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["recommend.recommend_id","recommend.title","recommend.slug","recommend.type","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","c.category_id","c.name"]
	 * 				 $data['recommend_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 推荐记录 Key Value 结构数据 
	 *               	["recommend_id"],  // 推荐ID 
	 *               	["title"],  // 主题 
	 *               	["slug"],  // 别名 
	 *               	["type"],  // 方式 
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
	*               	["_map_article"][$articles[n]]["page_view"], // article.page_view
	*               	["_map_article"][$articles[n]]["favorite"], // article.favorite
	*               	["_map_article"][$articles[n]]["comment"], // article.comment
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
		$select = empty($data['select']) ? ["recommend.recommend_id","recommend.title","recommend.slug","recommend.type","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.article_id","a.title","c.category_id","c.name"] : $data['select'];
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
	 *               $data['slug'] 别名
	 *               $data['type'] 方式
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

		$inst = new \Xpmsns\Pages\Model\Recommend;
		$rs = $inst->create( $data );
		return $inst->getByRecommendId($rs["recommend_id"]);
	}






	/**
	 * 根据条件检索推荐记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["recommend.recommend_id","recommend.title","recommend.type","recommend.images","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.title","c.category_id","c.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["recommend_id"] 按推荐ID查询 ( AND = )
	 *			      $query["type"] 按方式查询 ( AND = )
	 *			      $query["period"] 按周期查询 ( AND = )
	 *			      $query["title"] 按主题查询 ( AND LIKE )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_asc"]  按更新时间 ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=recommend_id","name=title","name=type","name=images","name=keywords","name=orderby","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CArticle&name=title&table=article&prefix=xpmsns_pages_&alias=a&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["recommend_id"] 按推荐ID查询 ( AND = )
	 *			      $data["type"] 按方式查询 ( AND = )
	 *			      $data["period"] 按周期查询 ( AND = )
	 *			      $data["title"] 按主题查询 ( AND LIKE )
	 *			      $data["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $data["orderby_updated_at_asc"]  按更新时间 ASC 排序
	 *
	 * @return array 推荐记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["recommend_id"],  // 推荐ID 
	 *               	["title"],  // 主题 
	 *               	["slug"],  // 别名 
	 *               	["type"],  // 方式 
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
	*               	["article"][$articles[n]]["page_view"], // article.page_view
	*               	["article"][$articles[n]]["favorite"], // article.favorite
	*               	["article"][$articles[n]]["comment"], // article.comment
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
		$select = empty($data['select']) ? ["recommend.recommend_id","recommend.title","recommend.type","recommend.images","recommend.keywords","recommend.orderby","recommend.created_at","recommend.updated_at","a.title","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Recommend;
		return $inst->search( $data );
	}


}