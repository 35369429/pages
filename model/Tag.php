<?php
namespace Xpmsns\pages\Model; 
define('__NS__', 'Xpmsns\pages\Model'); // 兼容旧版 App::M 方法调用

use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Model as Model;
use \Xpmse\Utils as Utils;


/**
 * 文章数据模型
 */
class Tag extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'xpmsns_pages_']);
		$this->table('tag');
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
			$this->putColumn( 'tag_id', $this->type('string', ['length'=>128, 'unique'=>1]) )  // 标签ID ( 同 _id )
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

			if( empty(trim($tag)) ) {
				continue;
			}

			$rs = $this->create(["name"=>$tag]);
			array_push($tagids, $rs['tag_id']);
		}

		return  $tagids;

	}

	function genId() {
		return uniqid();
	}

	function create( $data ) {
		$data['tag_id'] = $this->genId();
		return parent::create( $data );
	}

	function __clear() {
		$this->dropTable();
	}

}