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
		
		$art = new \Mina\Pages\Model\Article;
		
		$rs = $art->getLine("WHERE article_id=:article_id LIMIT 1", $select, ["article_id"=>$article_id]);
		if ( empty($rs) ) {
			throw new Excp("文章不存在", 404,  ['query'=>$query, 'data'=>$data, 'files'=>$files]);
		}

		$rs['category'] = $art->getCategories($article_id);
		$rs['tag'] = $art->getTags($article_id);

		return $rs;
	}

	

}