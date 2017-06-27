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
		     ->allowQuery('get',  ['article_id', 'select']);
	}


	protected function get( $query=[], $data=[], $files=null ) {

		// 验证数值
		if ( !preg_match("/^([0-9]+)/", $query['article_id']) ) {
			throw new Excp(" article_id 参数错误", 400, ['query'=>$query, 'data'=>$data, 'files'=>$files]);
		}

		$article_id = $query['article_id'];
		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$getTag = false; $getCategory = false;
		$allowFields = ["*","article_id","cover","title","author","origin","origin_url","summary","seo_title","seo_keywords","seo_summary","publish_time","update_time","create_time","sync","content","ap_content","draft","ap_draft","history","stick","status","category", "tag"];

		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query, 'data'=>$data, 'files'=>$files]);
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
			throw new Excp("文章不存在", 404,  ['query'=>$query, 'data'=>$data, 'files'=>$files]);
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