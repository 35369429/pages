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
class Category extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'xpmsns_pages_']);
		$this->table('category');
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
			$this->putColumn( 'category_id', $this->type('string', ['length'=>128, 'unique'=>true]) )  // 类型ID ( 同 _id )
				 ->putColumn( 'slug', $this->type('string',  ['length'=>128, 'unique'=>true]) )  // 类型别名
				 ->putColumn( 'project', $this->type('string',  ['length'=>128, 'index'=>1]) )  // 所属项目
				 ->putColumn( 'page', $this->type('string',     ['length'=>128, 'index'=>1]) )  // 正文(默认)页面
				 ->putColumn( 'wechat', $this->type('string', ['index'=>1, "length"=>64]) )      // 绑定公众号
				 ->putColumn( 'wechat_offset', $this->type('integer', ['default'=>"0"]) )      // 同步文章的 Offset
				 ->putColumn( 'name', $this->type('string',  ['length'=>128]) )  // 类型名称
				 ->putColumn( 'fullname', $this->type('string',  ['length'=>256]) )  // 类型全名
				 ->putColumn( 'parent_id', $this->type('string', ["default"=>"0", "index"=>1]) ) // 父类 ID 
				 ->putColumn( 'priority', $this->type('integer', ['index'=>1, 'default'=>"0"]) ) // 优先级排序
				 ->putColumn( 'hidden', $this->type('boolean', ['index'=>1, 'default'=>"0"]) )   // 是否隐藏
				 ->putColumn( 'param', $this->type('string',     ['length'=>128, 'index'=>1]) )  // 自定义参数
				 ->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>1, 'default'=>'on']) )  // 类型状态 on/off
		;
	}

	function __clear() {
		$this->dropTable();
	}


	function genId() {
		return uniqid();
	}


	function create( $data ) {
		$data['category_id'] = $this->genId();
		if ( empty($data['slug']) ) {
			$data['slug'] = $data['category_id'];
		}
		return parent::create( $data );
	}


	/**
	 * 遍历分类
	 * @param  [type]  $fn        [description]
	 * @param  integer $parent_id [description]
	 * @param  integer $depth     [description]
	 * @return [type]             [description]
	 */
	function each( $fn, $parent_id=0, $depth=0 ) {
		
		$depth ++;
		if ( !is_callable($fn) ) {
			$fn = function( $data, $depth ) {};
		}

		$resp = $this->select("where parent_id=? and status='on' ", ['*'], [$parent_id]);
		if ( empty($resp['data']) ) {
			return;
		}

		foreach ($resp['data'] as $rs ) {
			$fn( $rs, $depth );
			$this->each( $fn, $rs['category_id'], $depth);
		}
	}


	/**
	 * 读取/绑定公众号的分类信息
	 * @return [type] [description]
	 */
	function wechat() {

		$conf = Utils::getConf();
		$groups = $conf['_groups'];
		$tmap = ["2"=>'订阅号', "1"=>"服务号"];
		$appids = []; $wemap = []; $needcreate = [];
		foreach ($groups as $name => $we ) {
			// type = 1 订阅号  type = 2 服务号
			if( ($we['type'] == 1 || $we['type'] == 2 ) && !empty($we['appid'])) {
				$we['name'] = $name;
				$we['typename'] = $tmap[$we['type']];
				array_push( $appids, $we['appid']);
				$needcreate[$we['appid']] = $wemap[$we['appid']] = $we;
			}
		}

		$cates = $this->query()->whereIn('wechat', $appids)
						 ->select('category_id', 'name', 'wechat', 'wechat_offset as offset' )
						 ->get()->toArray();


		foreach ($cates as $c ) {
			$wemap[$c['wechat']]['category_id'] = $c['category_id'];
			$wemap[$c['wechat']]['category'] = $c['name'];
			$wemap[$c['wechat']]['offset'] = empty($c['offset']) ? 0 : intval($c['offset']);
			unset($needcreate[$c['wechat']]);
		}

		foreach ( $needcreate as $appid => $we ) {
			$c = $this->create([
				'hidden' => 1,
				'wechat' => $appid,
				'name' => $we['name']
			]);

			$wemap[$c['wechat']]['category_id'] = $c['category_id'];
			$wemap[$c['wechat']]['category'] = $c['name'];
			$wemap[$c['wechat']]['offset'] = $c['wechat_offset'];
		}

		return $wemap;
	}




	/**
	 * 读取所有分类
	 * @param  [type] $parent_id [description]
	 * @return [type]            [description]
	 */
	function parents(  $parent_id  ) {

		$parents = [];
		$rs = $this->getLine("where category_id=?  and status='on' LIMIT 1", ['*'], [$parent_id]);
		if ( !empty($rs)  ) {
			array_push( $parents , $rs );
			$parents = array_merge($this->parents($rs['parent_id']), $parents);
		}

		return $parents;
	}


	
}