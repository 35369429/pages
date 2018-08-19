<?php
/**
 * Class Series 
 * 系列数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-08-19 18:26:51
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
             

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Series extends Api {

	/**
	 * 系列数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */



	/**
	 * 查询一条系列记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["series.series_id","series.name","series.slug","series.summary","series.orderby","series.param","series.status","series.created_at","series.updated_at","c.category_id","c.name"]
	 * 				 $query['series_id']  按查询 (多条用 "," 分割)
	 * 				 $query['name']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["series.series_id","series.name","series.slug","series.summary","series.orderby","series.param","series.status","series.created_at","series.updated_at","c.category_id","c.name"]
	 * 				 $data['series_id']  按查询 (多条用 "," 分割)
	 * 				 $data['name']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 系列记录 Key Value 结构数据 
	 *               	["series_id"],  // 系列ID 
	 *               	["name"],  // 系列名称 
	 *               	["slug"],  // 系列别名 
	 *               	["category_id"],  // 所属栏目 
	*               	["c_category_id"], // category.category_id
	 *               	["summary"],  // 摘要 
	 *               	["orderby"],  // 排序方式 
	 *               	["param"],  // 参数 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["c_created_at"], // category.created_at
	*               	["c_updated_at"], // category.updated_at
	*               	["c_slug"], // category.slug
	*               	["c_project"], // category.project
	*               	["c_page"], // category.page
	*               	["c_wechat"], // category.wechat
	*               	["c_wechat_offset"], // category.wechat_offset
	*               	["c_name"], // category.name
	*               	["c_fullname"], // category.fullname
	*               	["c_root_id"], // category.root_id
	*               	["c_parent_id"], // category.parent_id
	*               	["c_priority"], // category.priority
	*               	["c_hidden"], // category.hidden
	*               	["c_param"], // category.param
	*               	["c_status"], // category.status
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["series.series_id","series.name","series.slug","series.summary","series.orderby","series.param","series.status","series.created_at","series.updated_at","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按系列ID
		if ( !empty($data["series_id"]) ) {
			
			$keys = explode(',', $data["series_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Series;
				return $inst->getInBySeriesId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Series;
			return $inst->getBySeriesId($data["series_id"], $select);
		}

		// 按系列名称
		if ( !empty($data["name"]) ) {
			
			$keys = explode(',', $data["name"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Series;
				return $inst->getInByName($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Series;
			return $inst->getByName($data["name"], $select);
		}

		// 按系列别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Series;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Series;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条系列记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['series_id'] 系列ID
	 *               $data['name'] 系列名称
	 *               $data['slug'] 系列别名
	 *               $data['category_id'] 所属栏目
	 *               $data['summary'] 摘要
	 *               $data['orderby'] 排序方式
	 *               $data['param'] 参数
	 *               $data['status'] 状态
	 *
	 * @return array 新增的系列记录  @see get()
	 */
	protected function create( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		if (empty($data['series_id'])) {
			throw new Excp("缺少必填字段系列ID (series_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['name'])) {
			throw new Excp("缺少必填字段系列名称 (name)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\Pages\Model\Series;
		$rs = $inst->create( $data );
		return $inst->getBySeriesId($rs["series_id"]);
	}


	/**
	 * 更新一条系列记录
	 * @param  array $query GET 参数
	 * 				 $query['name=series_id']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['series_id'] 系列ID
	 *               $data['name'] 系列名称
	 *               $data['slug'] 系列别名
	 *               $data['category_id'] 所属栏目
	 *               $data['summary'] 摘要
	 *               $data['orderby'] 排序方式
	 *               $data['param'] 参数
	 *               $data['status'] 状态
	 *
	 * @return array 更新的系列记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		// 按系列ID
		if ( !empty($query["series_id"]) ) {
			$data = array_merge( $data, ["series_id"=>$query["series_id"]] );
			$inst = new \Xpmsns\Pages\Model\Series;
			$rs = $inst->updateBy("series_id",$data);
			return $inst->getBySeriesId($rs["series_id"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条系列记录
	 * @param  array $query GET 参数
	 * 				 $query['series_id']  按系列ID 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		// 按系列ID
		if ( !empty($query["series_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Series;
			$resp = $inst->remove($query['series_id'], "series_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索系列记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["series.series_id","series.name","series.slug","series.summary","series.orderby","series.param","series.status","series.created_at","series.updated_at","c.category_id","c.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["series_id"] 按系列ID查询 ( AND = )
	 *			      $query["series_id"] 按系列ID查询 ( AND IN )
	 *			      $query["param"] 按参数查询 ( AND = )
	 *			      $query["slug"] 按系列别名查询 ( AND = )
	 *			      $query["category_id"] 按所属栏目查询 ( AND = )
	 *			      $query["orderby"] 按排序方式查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=series_id","name=name","name=slug","name=summary","name=orderby","name=param","name=status","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=leftJoin","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=leftJoin"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["series_id"] 按系列ID查询 ( AND = )
	 *			      $data["series_id"] 按系列ID查询 ( AND IN )
	 *			      $data["param"] 按参数查询 ( AND = )
	 *			      $data["slug"] 按系列别名查询 ( AND = )
	 *			      $data["category_id"] 按所属栏目查询 ( AND = )
	 *			      $data["orderby"] 按排序方式查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $data["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
	 *
	 * @return array 系列记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["series_id"],  // 系列ID 
	 *               	["name"],  // 系列名称 
	 *               	["slug"],  // 系列别名 
	 *               	["category_id"],  // 所属栏目 
	*               	["c_category_id"], // category.category_id
	 *               	["summary"],  // 摘要 
	 *               	["orderby"],  // 排序方式 
	 *               	["param"],  // 参数 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["c_created_at"], // category.created_at
	*               	["c_updated_at"], // category.updated_at
	*               	["c_slug"], // category.slug
	*               	["c_project"], // category.project
	*               	["c_page"], // category.page
	*               	["c_wechat"], // category.wechat
	*               	["c_wechat_offset"], // category.wechat_offset
	*               	["c_name"], // category.name
	*               	["c_fullname"], // category.fullname
	*               	["c_root_id"], // category.root_id
	*               	["c_parent_id"], // category.parent_id
	*               	["c_priority"], // category.priority
	*               	["c_hidden"], // category.hidden
	*               	["c_param"], // category.param
	*               	["c_status"], // category.status
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["series.series_id","series.name","series.slug","series.summary","series.orderby","series.param","series.status","series.created_at","series.updated_at","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Series;
		return $inst->search( $data );
	}


}