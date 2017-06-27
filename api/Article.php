<?php

namespace Mina\Pages\Api;

use \Tuanduimao\Loader\App;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;
use \Tuanduimao\Api;


/**
 * 文章API接口
 */
class Article extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {

		parent::__construct();
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
	 * 查询文章列表
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
	 * 	  8. 按创建时间查询  publish_time | orPublish_time | endPublish_time | orEndPublish_time
	 * 	  9. 按更新时间查询  update_time  | orUpdate_time  |  endUpdate_time | orEndUpdate_time
	 * 	  
	 * 排序方式 order 默认 publish_time  update_time asc, publish_time desc
	 * 
	 *    1. 按文章发布时间  publish_time
	 *    2. 按文章更新时间  update_time  
	 *    3. 按置顶顺序 stick
	 *    
	 *
	 * 当前页码 page    默认 1 
	 * 每页数量 perpage 默认 50 
	 * 	
	 * 
	 * @param  array  $query [description]
	 * @return array 文章结果集列表
	 */
	protected function search( $query=[] ) {

		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$getTag = false; $getCategory = false;
		$allowFields = ["*","article_id","cover","title","author","origin","origin_url","summary","seo_title","seo_keywords","seo_summary","publish_time","update_time","create_time","sync","content","ap_content","draft","ap_draft","history","stick","status","category", "tag"];

		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query]);
			}

			$select[$idx] = 'article.' . $field;

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



		// Order 默认参数
		$query['order'] = !empty($query['order']) ? $query['order'] : 'publish_time';
		$allowOrder = ["publish_time", "update_time", "stick" ];
		$orderList = explode(',', $query['order']);

		// 分页参数
		$query['page'] = !empty($query['page']) ? intval($query['page']) : 1;
		$query['prepage'] = !empty($query['prepage']) ? intval($query['prepage']) : 50;



		// 查询数据表
		$art = new \Mina\Pages\Model\Article;
		$qb = $art->query()
				  ->rightJoin("article_category as ac", 'ac.article_id', '=', 'article.article_id')
				  ->rightJoin('category as c', "c.category_id", '=', 'ac.category_id')
				  ->rightJoin("article_tag as at", 'at.article_id', '=', 'article.article_id')
				  ->rightJoin("tag as t", 't.tag_id', '=', 'at.tag_id');

		// 设定查询条件
		$this->qb( $qb, 'c.name', 'category', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'c.category_id', 'categoryId', $query, ["and", "or", "in"] );
		$this->qb( $qb, 't.name', 'tag', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'article.origin', 'origin', $query );
		$this->qb( $qb, 'article.project', 'project', $query);
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

			$qb->orderBy($orderArr[0],$orderArr[1]);
		}
		
		// 查询数据
		$qb->select( $select )->distinct();
		$result = $qb ->paginate($query['prepage'],['article.article_id'], 'page', $query['page'] );
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

		$pad = [];
		if ( $getCategory ) {
			$pad = $art->pad($data, '_aid');
			$categories = $art->getCategoriesGroup($pad['data'], "category.category_id","name","fullname","project","page","parent_id","priority","hidden","param" );
		}

		if ( $getTag ) {
			if ( empty($pad) ) {
				$pad = $art->pad($data, '_aid');
			}
			$tags = $art->getTagsGroup($pad['data'], 'tag.tag_id', 'name', 'param' );
		}


		// 处理结果集数据
		$resp['data'] = [];
		foreach ($data as $idx => $rs ) {
			$aid = $rs['_aid'];unset($rs['_aid']);

			if ( $getCategory) {
				$rs['category'] = $categories[$aid];
			}
			if ( $getCategory) {
				$rs['tag'] = $tags[$aid];
			}
			
			$resp['data'][$idx] = $rs;
		}

		return $resp;

	}


	/**
	 * 创建查询条件
	 */
	private function qb( & $qb, $field, $name, $query, $method=['and','or'], $operator='=') {


		$Ucname = ucfirst(strtolower($name));

		foreach ($method as $m ) {

			if ( $m == 'and' && isset($query[$name]) ) {

				$value = $query[$name];
				if ( $operator == 'like' ) {
					$value = "%{$value}%";
				}
				$qb->where($field,$operator, $value);

			} else if ( $m == 'or' && isset($query["or$Ucname"]) ){

				$value = $query["or$Ucname"];

				if ( $operator == 'like' ) {
					$value = "%{$value}%";
				}
				$qb->orWhere($field, $operator, $value);

			} else if ( $m == 'in' && isset($query["in$Ucname"])) {
				$str = $query["in$Ucname"];
				$arr = explode(',', $str);
				$qb->whereIn($field, $arr );
			}
		}
	} 



	/**
	 * 读取文章详情信息
	 * @param  array  $query Query 查询
	 *                   int ["article_id"]  文章ID
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
		if ( !preg_match("/^([0-9]+)/", $query['article_id']) ) {
			throw new Excp(" article_id 参数错误", 400, ['query'=>$query]);
		}

		$article_id = $query['article_id'];
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

		
		$art = new \Mina\Pages\Model\Article;
		$rs = $art->getLine("WHERE article_id=:article_id LIMIT 1", $select, ["article_id"=>$article_id]);
		if ( empty($rs) ) {
			throw new Excp("文章不存在", 404,  ['query'=>$query]);
		}

		if( $getCategory) {
			$rs['category'] = $art->getCategories($article_id,"category.category_id","name","fullname","project","page","parent_id","priority","hidden","param" );
		}

		if ( $getTag ) {
			$rs['tag'] = $art->getTags($article_id, 'tag.tag_id', 'name', 'param');
		}

		return $rs;
	}


}