<?php

namespace Mina\Pages\Api;

use \Tuanduimao\Loader\App;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;
use \Tuanduimao\Api;
use \Mina\Pages\Model\Category;

/**
 * 文章数据模型
 */
class Article extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {
		parent::__construct();
	}

	function test(){

		try {
			$cate = new Category();
		} catch( Excp $e ) {
			Utils::out( $e->toArray() );
		}

		$resp = $cate->select();

		Utils::out($resp);
	}

}