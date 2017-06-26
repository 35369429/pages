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
class Tag extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'mina_pages_']);
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



	/**
	 * 插入标签，并返回标签 ID 列表
	 * @param  [type] $tagnames [description]
	 * @return [type]           [description]
	 */
	function put ( $tagnames ) {

		$resp = $this->query()->whereIn( "name", $tagnames )->select("name", "tag_id")->get();
		
		$havenames = [];$tagids = [];
		foreach ($resp as $rs) { array_push($havenames, $rs['name']); array_push($tagids, $rs['tag_id']);}

		$diffnames = array_diff( $tagnames, $havenames);

		foreach ($diffnames as $idx=>$tag ) {
			$rs = $this->create(["name"=>$tag]);
			array_push($tagids, $rs['tag_id']);
		}

		return  $tagids;

	}

	function create( $data ) {
		$data['tag_id'] = $this->nextid();
		return parent::create( $data );
	}

	function __clear() {
		$this->dropTable();
	}

}