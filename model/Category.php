<?php
namespace Mina\Pages\Model; 
define('__NS__', 'Mina\Pages\Model'); // 兼容旧版 App::M 方法调用

use \Tuanduimao\Mem as Mem;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Err as Err;
use \Tuanduimao\Conf as Conf;
use \Tuanduimao\Model as Model;
use \Tuanduimao\Utils as Utils;


/**
 * 文章数据模型
 */
class Category extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'mina_pages_']);
		$this->table('category');
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
			$this->putColumn( 'category_id', $this->type('bigInteger', ['length'=>20]) )  // 类型ID ( 同 _id )
				 ->putColumn( 'project', $this->type('string',  ['length'=>128, 'index'=>1]) )  // 所属项目
				 ->putColumn( 'page', $this->type('string',     ['length'=>128, 'index'=>1]) )  // 正文(默认)页面
				 ->putColumn( 'name', $this->type('string',  ['length'=>128]) )  // 类型名称
				 ->putColumn( 'fullname', $this->type('string',  ['length'=>256]) )  // 类型全名
				 ->putColumn( 'parent_id', $this->type('bigInteger', ["default"=>"0", "index"=>1]) ) // 父类 ID 
				 ->putColumn( 'priority', $this->type('integer', ['index'=>1, 'default'=>"0"]) ) // 优先级排序
				 ->putColumn( 'hidden', $this->type('boolean', ['index'=>1, 'default'=>"0"]) ) // 是否隐藏
				 ->putColumn( 'param', $this->type('string',     ['length'=>128, 'index'=>1]) )  //自定义参数
				 ->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>1, 'default'=>'on']) )  // 类型状态 on/off
				
		;
	}

	function create( $data ) {
		$data['category_id'] = $this->nextid();
		return parent::create( $data );
	}


	function __clear() {
		$this->dropTable();
	}

}