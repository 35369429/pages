<?php
/**
 * Class Album 
 * 图集数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-08-27 18:27:38
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                          

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Album extends Api {

	/**
	 * 图集数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条图集记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["album.album_id","album.slug","album.title","album.author","album.origin","album.origin_url","album.link","album.categories","album.tags","album.summary","album.images","album.cover","album.publish_time","album.view_cnt","album.like_cnt","album.dislike_cnt","album.comment_cnt","album.created_at","album.updated_at","c.category_id","c.name","c.param","s.series_id","s.name"]
	 * 				 $query['album_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["album.album_id","album.slug","album.title","album.author","album.origin","album.origin_url","album.link","album.categories","album.tags","album.summary","album.images","album.cover","album.publish_time","album.view_cnt","album.like_cnt","album.dislike_cnt","album.comment_cnt","album.created_at","album.updated_at","c.category_id","c.name","c.param","s.series_id","s.name"]
	 * 				 $data['album_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 图集记录 Key Value 结构数据 
	 *               	["album_id"],  // 图集ID 
	 *               	["slug"],  // 图集别名 
	 *               	["title"],  // 图集主题 
	 *               	["author"],  // 图集作者 
	 *               	["origin"],  // 图集来源 
	 *               	["origin_url"],  // 来源地址 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 类型 
	*               	["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *               	["series"],  // 系列 
	*               	["_map_series"][$series[n]]["series_id"], // series.series_id
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 图集简介 
	 *               	["images"],  // 图片列表 
	 *               	["cover"],  // 封面 
	 *               	["publish_time"],  // 发布时间 
	 *               	["view_cnt"],  // 浏览量 
	 *               	["like_cnt"],  // 赞赏量 
	 *               	["dislike_cnt"],  // 讨厌量 
	 *               	["comment_cnt"],  // 评论数据量 
	 *               	["status"],  // 图集状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["_map_category"][$categories[n]]["created_at"], // category.created_at
	*               	["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["_map_category"][$categories[n]]["slug"], // category.slug
	*               	["_map_category"][$categories[n]]["project"], // category.project
	*               	["_map_category"][$categories[n]]["page"], // category.page
	*               	["_map_category"][$categories[n]]["wechat"], // category.wechat
	*               	["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["_map_category"][$categories[n]]["name"], // category.name
	*               	["_map_category"][$categories[n]]["fullname"], // category.fullname
	*               	["_map_category"][$categories[n]]["link"], // category.link
	*               	["_map_category"][$categories[n]]["root_id"], // category.root_id
	*               	["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["_map_category"][$categories[n]]["priority"], // category.priority
	*               	["_map_category"][$categories[n]]["hidden"], // category.hidden
	*               	["_map_category"][$categories[n]]["isnav"], // category.isnav
	*               	["_map_category"][$categories[n]]["param"], // category.param
	*               	["_map_category"][$categories[n]]["status"], // category.status
	*               	["_map_category"][$categories[n]]["issubnav"], // category.issubnav
	*               	["_map_category"][$categories[n]]["highlight"], // category.highlight
	*               	["_map_category"][$categories[n]]["isfootnav"], // category.isfootnav
	*               	["_map_category"][$categories[n]]["isblank"], // category.isblank
	*               	["_map_series"][$series[n]]["created_at"], // series.created_at
	*               	["_map_series"][$series[n]]["updated_at"], // series.updated_at
	*               	["_map_series"][$series[n]]["name"], // series.name
	*               	["_map_series"][$series[n]]["slug"], // series.slug
	*               	["_map_series"][$series[n]]["category_id"], // series.category_id
	*               	["_map_series"][$series[n]]["summary"], // series.summary
	*               	["_map_series"][$series[n]]["orderby"], // series.orderby
	*               	["_map_series"][$series[n]]["param"], // series.param
	*               	["_map_series"][$series[n]]["status"], // series.status
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["album.album_id","album.slug","album.title","album.author","album.origin","album.origin_url","album.link","album.categories","album.tags","album.summary","album.images","album.cover","album.publish_time","album.view_cnt","album.like_cnt","album.dislike_cnt","album.comment_cnt","album.created_at","album.updated_at","c.category_id","c.name","c.param","s.series_id","s.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按图集ID
		if ( !empty($data["album_id"]) ) {
			
			$keys = explode(',', $data["album_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Album;
				return $inst->getInByAlbumId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Album;
			return $inst->getByAlbumId($data["album_id"], $select);
		}

		// 按图集别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Album;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Album;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条图集记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['album_id'] 图集ID
	 *               $data['slug'] 图集别名
	 *               $data['title'] 图集主题
	 *               $data['author'] 图集作者
	 *               $data['origin'] 图集来源
	 *               $data['origin_url'] 来源地址
	 *               $data['link'] 外部链接
	 *               $data['categories'] 类型
	 *               $data['series'] 系列
	 *               $data['tags'] 标签
	 *               $data['summary'] 图集简介
	 *               $data['images'] 图片列表
	 *               $data['cover'] 封面
	 *               $data['publish_time'] 发布时间
	 *               $data['view_cnt'] 浏览量
	 *               $data['like_cnt'] 赞赏量
	 *               $data['dislike_cnt'] 讨厌量
	 *               $data['comment_cnt'] 评论数据量
	 *               $data['status'] 图集状态
	 *
	 * @return array 新增的图集记录  @see get()
	 */
	protected function create( $query, $data ) {

		// 签名校验，一般用于后台程序调用
		$this->auth($query);


		$inst = new \Xpmsns\Pages\Model\Album;
		$rs = $inst->create( $data );
		return $inst->getByAlbumId($rs["album_id"]);
	}


	/**
	 * 更新一条图集记录
	 * @param  array $query GET 参数
	 * 				 $query['name=album_id']  按更新
	 * 				 $query['name=slug']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['album_id'] 图集ID
	 *               $data['slug'] 图集别名
	 *               $data['title'] 图集主题
	 *               $data['author'] 图集作者
	 *               $data['origin'] 图集来源
	 *               $data['origin_url'] 来源地址
	 *               $data['link'] 外部链接
	 *               $data['categories'] 类型
	 *               $data['series'] 系列
	 *               $data['tags'] 标签
	 *               $data['summary'] 图集简介
	 *               $data['images'] 图片列表
	 *               $data['cover'] 封面
	 *               $data['publish_time'] 发布时间
	 *               $data['view_cnt'] 浏览量
	 *               $data['like_cnt'] 赞赏量
	 *               $data['dislike_cnt'] 讨厌量
	 *               $data['comment_cnt'] 评论数据量
	 *               $data['status'] 图集状态
	 *
	 * @return array 更新的图集记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		// 签名校验，一般用于后台程序调用
		$this->auth($query);

		// 按图集ID
		if ( !empty($query["album_id"]) ) {
			$data = array_merge( $data, ["album_id"=>$query["album_id"]] );
			$inst = new \Xpmsns\Pages\Model\Album;
			$rs = $inst->updateBy("album_id",$data);
			return $inst->getByAlbumId($rs["album_id"]);
		}

		// 按图集别名
		if ( !empty($query["slug"]) ) {
			$data = array_merge( $data, ["slug"=>$query["slug"]] );
			$inst = new \Xpmsns\Pages\Model\Album;
			$rs = $inst->updateBy("slug",$data);
			return $inst->getBySlug($rs["slug"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条图集记录
	 * @param  array $query GET 参数
	 * 				 $query['album_id']  按图集ID 删除
	 * 				 $query['slug']  按图集别名 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		// 签名校验，一般用于后台程序调用
		$this->auth($query);

		// 按图集ID
		if ( !empty($query["album_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Album;
			$resp = $inst->remove($query['album_id'], "album_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		// 按图集别名
		if ( !empty($query["slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Album;
			$resp = $inst->remove($query['slug'], "slug");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索图集记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["album.album_id","album.slug","album.title","album.author","album.origin","album.origin_url","album.link","album.categories","album.tags","album.summary","album.images","album.cover","album.publish_time","album.view_cnt","album.like_cnt","album.dislike_cnt","album.comment_cnt","album.created_at","album.updated_at","c.category_id","c.slug","c.name","c.param","s.series_id","s.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["album_id"] 按图集ID查询 ( AND = )
	 *			      $query["slug"] 按图集别名查询 ( AND = )
	 *			      $query["status"] 按图集状态查询 ( AND = )
	 *			      $query["title"] 按图集主题查询 ( AND LIKE )
	 *			      $query["series"] 按系列查询 ( AND LIKE )
	 *			      $query["categories"] 按类型查询 ( AND LIKE )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=album_id","name=slug","name=title","name=author","name=origin","name=origin_url","name=link","name=categories","name=tags","name=summary","name=images","name=cover","name=publish_time","name=view_cnt","name=like_cnt","name=dislike_cnt","name=comment_cnt","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=slug&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=param&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CSeries&name=series_id&table=series&prefix=xpmsns_pages_&alias=s&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CSeries&name=name&table=series&prefix=xpmsns_pages_&alias=s&type=inWhere"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keywords"] 按关键词查询
	 *			      $data["album_id"] 按图集ID查询 ( AND = )
	 *			      $data["slug"] 按图集别名查询 ( AND = )
	 *			      $data["status"] 按图集状态查询 ( AND = )
	 *			      $data["title"] 按图集主题查询 ( AND LIKE )
	 *			      $data["series"] 按系列查询 ( AND LIKE )
	 *			      $data["categories"] 按类型查询 ( AND LIKE )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 图集记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["album_id"],  // 图集ID 
	 *               	["slug"],  // 图集别名 
	 *               	["title"],  // 图集主题 
	 *               	["author"],  // 图集作者 
	 *               	["origin"],  // 图集来源 
	 *               	["origin_url"],  // 来源地址 
	 *               	["link"],  // 外部链接 
	 *               	["categories"],  // 类型 
	*               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["series"],  // 系列 
	*               	["series"][$series[n]]["series_id"], // series.series_id
	 *               	["tags"],  // 标签 
	 *               	["summary"],  // 图集简介 
	 *               	["images"],  // 图片列表 
	 *               	["cover"],  // 封面 
	 *               	["publish_time"],  // 发布时间 
	 *               	["view_cnt"],  // 浏览量 
	 *               	["like_cnt"],  // 赞赏量 
	 *               	["dislike_cnt"],  // 讨厌量 
	 *               	["comment_cnt"],  // 评论数据量 
	 *               	["status"],  // 图集状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
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
	*               	["series"][$series[n]]["created_at"], // series.created_at
	*               	["series"][$series[n]]["updated_at"], // series.updated_at
	*               	["series"][$series[n]]["name"], // series.name
	*               	["series"][$series[n]]["slug"], // series.slug
	*               	["series"][$series[n]]["category_id"], // series.category_id
	*               	["series"][$series[n]]["summary"], // series.summary
	*               	["series"][$series[n]]["orderby"], // series.orderby
	*               	["series"][$series[n]]["param"], // series.param
	*               	["series"][$series[n]]["status"], // series.status
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["album.album_id","album.slug","album.title","album.author","album.origin","album.origin_url","album.link","album.categories","album.tags","album.summary","album.images","album.cover","album.publish_time","album.view_cnt","album.like_cnt","album.dislike_cnt","album.comment_cnt","album.created_at","album.updated_at","c.category_id","c.slug","c.name","c.param","s.series_id","s.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Album;
		return $inst->search( $data );
	}

	/**
	 * 文件上传接口 (上传控件名称 )
	 * @param  array $query [description]
	 *               $query["private"]  上传文件为私有文件
	 * @param  [type] $data  [description]
	 * @return array 文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	protected function upload( $query, $data, $files ) {
		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		$fname = $files['file']['tmp_name'];
		if ( $query['private'] ) {
			$media = new \Xpmse\Media(["host" => Utils::getHome(), 'private'=>true]);
		} else {
			$media = new \Xpmse\Media(["host" => Utils::getHome()]);
		}
		$ext = $media->getExt($fname);
		$rs = $media->uploadFile($fname, $ext);
		return $rs;
	}

}