<?php

use \Tuanduimao\Mem as Mem;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Err as Err;
use \Tuanduimao\Conf as Conf;
use \Tuanduimao\Model as Model;
use \Tuanduimao\Utils as Utils;


/**
 * 文章数据模型
 */
class TagModel extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct();
		$this->table('tag');
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
			$this->putColumn( 'tag_id', $this->type('bigInteger', ['length'=>20]) )  // 标签ID ( 同 _id )
				 ->putColumn( 'name', $this->type('string',  ['length'=>128]) )  // 标签名称
				 ->putColumn( 'param', $this->type('string',     ['length'=>128, 'index'=>1]) )  //自定义参数
		;
	}


	function __clear() {
		$this->dropTable();
	}

}