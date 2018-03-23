<?php

namespace Xpmsns\pages\Api;

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;


/**
 * 分类API接口
 */
class Category extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {

		parent::__construct();
		$this->allowMethod('get', ["PHP",'GET'])
		     ->allowQuery('get',  ['categoryId', 'select'])
		     ->allowMethod('search', ["PHP",'GET'])
		     ->allowQuery('search',  [
		     	"select",
		     	'name','orName','inName',
		     	'fullname','orFullname','inFullname',
		     	'categoryId','orcategoryId','incategoryId',
		     	'parentId','orParentId','inParentId',
		     	'children',
		     	'hidden', 'orHidden',
		     	'status', 'orStatus',
		     	'praram','orParam',
		     	'order',
		     	'page','perpage'
		     ]);
	}

	
	/**
	 * 查询分类列表
	 *
	 * 读取字段 select 默认 *
	 *
	 *    示例:  ["*"] /["category_id", "title" ....] / "*" / "category_id,title"
	 *    许可值: "*","category_id","project","page","name","fullname","parent_id",
	 *           "priority","hidden","param","status"
	 * 
	 * 
	 * 查询条件
	 *    0. 按名称或者ID查询 slug | orSlug | inSlug
	 * 	  1. 按分类名称查询  name | orName | inName
	 * 	  2. 按分类全称查询  fullname | orFullname | inFullname
	 * 	  3. 按分类ID查询  categoryId | orCategoryId | inCategoryId 
	 * 	  4. 按父类ID查询  parentId | orParentId | inParentId  默认为 0
	 * 	  5. 是否包含子类  children 默认为 true
	 * 	  6. 按标签查询  hidden | orHidden
	 * 	  7. 按状态查询  status | orStatus
	 * 	  8. 按参数标记查询  param | orParam
	 * 	  
	 * 排序方式 order 默认 priority  priority asc, category_id desc
	 * 
	 *    1. 按分类指定顺序  priority
	 *    2. 按分类创建顺序  category_id  
	 *    
	 *
	 * 当前页码 page    默认 1 
	 * 每页数量 perpage 默认 50 
	 * 	
	 * 
	 * @param  array  $query 
	 * @return array 文章结果集列表
	 */
	protected function search( $query=[] ) {

		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$allowFields = ["*","category_id","project","page","name","fullname","parent_id","priority","hidden","param","status"];


		if ( !empty($query['slug']) ) {
			if ( is_numeric($query['slug']) ) {
				$query['categoryId'] = intval($query['slug']);
			} else {
				$query['name'] = trim($query['slug']);
			}
		}

		if ( !empty($query['orSlug']) ) {
			if ( is_numeric($query['orSlug']) ) {
				$query['orCategoryId'] = intval($query['orSlug']);
			} else {
				$query['orName'] = trim($query['orSlug']);
			}
		}

		if ( !empty($query['inSlug']) ) {
			if ( is_numeric($query['inSlug']) ) {
				$query['inCategoryId'] = intval($query['inSlug']);
			} else {
				$query['inName'] = trim($query['inSlug']);
			}
		}



		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query]);
			}
		}
		$select[] = 'category_id as _cid';


		// 是否包含子类
		$query['children'] = isset($query['children']) ? $query['children'] : true;
		$query['parentId'] = isset($query['parentId']) ? $query['parentId'] : null;


		// Order 默认参数
		$query['order'] = !empty($query['order']) ? $query['order'] : 'priority';
		$allowOrder = ["priority", "category_id"];
		$orderList = explode(',', $query['order']);


		// 分页参数
		$query['page'] = !empty($query['page']) ? intval($query['page']) : 1;
		$query['perpage'] = !empty($query['perpage']) ? intval($query['perpage']) : 50;



		// 查询数据表
		$c = new \Xpmsns\pages\Model\Category;
		$qb = $c->query();

		// 设定查询条件
		$this->qb( $qb, 'name', 'name', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'category_id', 'categoryId', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'fullname', 'fullname', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'parent_id', 'parentId', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'hidden', 'hidden', $query );
		$this->qb( $qb, 'status', 'status', $query );
		$this->qb( $qb, 'param', 'param', $query, ['and', 'or'], 'like');

		// 处理排序
		foreach ($orderList as $order) {
			$order = trim($order);
			$orderArr = preg_split('/[ ]+/', $order );
			$orderArr[1] = !empty($orderArr[1]) ? $orderArr[1] : 'desc';

			if ( !in_array($orderArr[0], $allowOrder)) {
				throw new Excp(" order 参数错误 ({$orderArr[0]} 非法字段)", 400, ['query'=>$query]);
			}

			$qb->orderBy($orderArr[0],$orderArr[1]);
		}
		
		// 查询数据
		$qb->select( $select );
		$result = $qb ->paginate($query['perpage'],['category_id'], 'page', $query['page'] );
		$resultData = $result->toArray();
		

		// 处理结果集
		$data = $resultData['data'];

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
	

		foreach ($data as $idx=>$rs ) {

			unset($resp['data'][$idx]['_cid']);

			if ( $query['children'] ) {
				$children = $this->search([
					'parentId'=>$rs['_cid'],
					'select' => $query['select']
				]);

				$resp['data'][$idx]['children'] = $children['data'];
			}
		}
		
		return $resp;

	}



	/**
	 * 读取分类详情信息
	 * @param  array  $query Query 查询
	 *                   int ["categoryId"]  分类ID
	 *                   
	 *          string|array ["select"] 读取字段  
	 *          			 示例:  ["*"] /["category_id", "title" ....] / "*" / "category_id,title"
	 *          		     许可值: "*","category_id","project","page","name","fullname","parent_id","priority","hidden","param","status"
	 *                    
	 * @return Array 文章数据
	 * 
	 */
	protected function get( $query=[] ) {

		// 验证数值ß
		if ( !preg_match("/^([0-9]+)/", $query['categoryId']) ) {
			throw new Excp(" categoryId 参数错误", 400, ['query'=>$query]);
		}

		$category_id = $query['categoryId'];
		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$allowFields = ["*","category_id","project","page","name","fullname","parent_id","priority","hidden","param","status"];

		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query]);
			}
		}
		
		$cate = new \Xpmsns\pages\Model\Category;
		$rs = $cate->getLine("WHERE category_id=:category_id LIMIT 1", $select, ["category_id"=>$category_id]);
		if ( empty($rs) ) {
			throw new Excp("分类不存在", 404,  ['query'=>$query]);
		}

		return $rs;
	}

}