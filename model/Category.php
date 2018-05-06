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
				 ->putColumn( 'root_id', $this->type('string', ["index"=>1] )) //  根ID 
				 ->putColumn( 'parent_id', $this->type('string', ["index"=>1] )) // 父类 ID 
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


	function getInByCategoryId( $ids, $select = ['*'] ) {

		$qb = $this->query();
		$qb->whereIn("category_id", $ids);
		$qb->select($select);
		$rows = $qb->get()->toArray();
		$map = [];
		foreach ($rows as & $rs ) {
			$this->format($rs);
			$map[$rs['category_id']] = $rs;
		}
		return $map;
	}

	/**
	 * 添加分类
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function create( $data ) {
		$data['category_id'] = $this->genId();
		if ( empty($data['slug']) ) {
			$data['slug'] = $data['category_id'];
		}


		return parent::create( $data );
	}


	/**
	 * 删除分类
	 * @param  [type] $category_id [description]
	 * @return [type]              [description]
	 */
	function removeById( $category_id ) {

		$cids  = [$category_id];
		$this->each( function($cate, $depth) use( & $cids ) {
			array_push($cids, $cate['category_id']);
		}, $category_id);

		$cidstr = '("'.implode('","', $cids).'")';

		// 删除分类和子类
		$this->runSql('
			UPDATE {{table}} 
			SET `slug`=NULL, `deleted_at`=CURRENT_TIME() 
			WHERE category_id in ' . $cidstr, 
			false);

		return $cids;

	}


	/**
	 * 保存分类 ( 创建或者添加 )
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function saveBySlug( $data ) {

		if ( empty($data['slug']) ) {
			throw new Excp("错误的参数，无 slug 数值", 402, ['data'=>$data]);
		}
		$c = $this->getBy('slug', $data['slug']);

		if ( !empty($c) ) {
			$data['category_id'] = $c['category_id'];
		}
		return $this->save($data);
	}


	/**
	 * 保存分类 ( 创建或者添加 )
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function save( $data ) {

		// 子分类
		if ( array_key_exists('parent_id', $data) &&  !empty($data['parent_id'])  ) {

			$pa = $this->getById($data['parent_id']);
			if (!empty($pa) ) {
				$data['root_id'] = !empty($pa['root_id']) ? $pa['parent_id'] : $data['parent_id'];
			} else {
				$data['parent_id'] = null;
			}
		}

		if ( array_key_exists('parent_id', $data) && empty($data['parent_id']) ) {
			$data['parent_id'] = null;
			$data['root_id'] = null;
		}

		// 创建分类
		if ( empty($data['category_id'])  ) {
			return $this->create($data);
		}

		// 更新分类
		$rs = $this->updateBy('category_id', $data);

		// 更新分类子类的 Root
		$cids = [];
		$this->each( function($cate, $depth) use( & $cids ) {
			array_push($cids, $cate['category_id']);
		}, $rs['category_id']);

		if ( !empty($cids) ) {
			// 更新子分类 root
			$root = !empty($rs['root_id']) ? $rs['root_id'] : $rs['category_id'];
			$cidstr = '("'.implode('","', $cids).'")';
			$this->runSql('
				UPDATE {{table}} 
				SET `root_id`="'.$root.'", `updated_at`=CURRENT_TIME() 
				WHERE category_id in ' . $cidstr, 
				false);

		}

		return $rs;
	}


	/**
	 * 读取分类信息
	 * @param  [type] $category_id [description]
	 * @return [type]              [description]
	 */
	function getById( $category_id ) {
		$qb = $this->query();
		$rows = $qb->where('category_id', '=', $category_id)->limit(1)->get()->toArray();
		$rs =  current($rows);
		$this->format($rs);
		return $rs;
	}


	/**
	 * 分类查询
	 */
	function search( $query = [] ) {

		$qb = $this->query();

		$select = empty($query['select']) ? ["*"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		
		// 按关键词查找 (昵称/手机号/邮箱)
		if ( array_key_exists('keyword', $query) && !empty($query['keyword']) ) {
			$qb->where(function ( $qb ) use($query) {
			   	$qb->where("name", "like", "%{$query['keyword']}%");
				$qb->orWhere("fullname","like", "%{$query['keyword']}%");
				$qb->orWhere("param","like", "%{$query['keyword']}%");
			});
		} else {
			$qb->whereNull('parent_id');
		}


		// 排序: 最新发表
		if ( array_key_exists('order', $query)  ) {
			$order = explode(' ', $query['order']);
			$order[1] = !empty($order[1]) ? $order[1] : 'asc';
			$qb->orderBy($order[0], $order[1] );
		}
		
		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 查询一级分类
		$cates = $qb->select($select)->pgArray($perpage, ['_id'], 'page', $page);

		// 查询一级分类全部字分类
		$root_ids = array_column($cates['data'], 'category_id');
		$sub_categories = $this->getSubCategories( $root_ids );

		foreach ($cates['data'] as & $ca ) {
			$category_id = $ca['category_id'];
			$this->format($ca);
			$ca['sub'] = [ 'total' => 0 ];
			if ( is_array($sub_categories['data'][$category_id]) ) {
				$ca['sub'] = [
					'data' => $sub_categories['data'][$category_id],
					'tree' => $sub_categories['tree'][$category_id],
					'total' => count($sub_categories['data'][$category_id])
				];
			}
		}
		return $cates;
	}

	function format( & $category ) {
		return $category;
	}



	/**
	 * 遍历分类
	 * @param  [type] $root_id [description]
	 * @param  [type] $cates   [description]
	 * @return [type]          [description]
	 */
	function walk( & $cates_tree, $fn, $depth=0 ) {
		$depth ++;
		if ( !is_callable($fn) ) {
			$fn = function( $data, $depth ) {};
		}

		if ( empty($cates_tree) ){
			return;
		}

		foreach ($cates_tree as $cate ) {
			$fn( $cate, $depth );

			$children = $cate['children'];
			if ( empty($children) ) {
				continue;
			}

			$this->walk( $children, $fn, $depth);

		}
	}


	/**
	 * 查询一级分类的所有子分类
	 */
	function getSubCategories( $root_ids ) {

		$qb = $this->query();
		$sub_categories = $qb->whereIn('root_id', $root_ids)->get()->toArray();

		// 遍历
		$map =[]; $data = [];
		foreach ($sub_categories as $ca ) {
			$category_id = $ca['category_id'];
			$parent_id = $ca['parent_id'];
			$root_id = $ca['root_id'];

			if ( !is_array($map[$parent_id]) ) {
				$map[$parent_id] = [];
			}

			if ( !is_array($data[$root_id]) ) {
				$data[$root_id] = [];
			}

			// 格式化数据
			$this->format($ca);
			array_push($map[$parent_id], $ca );
			array_push($data[$root_id], $ca );
		}


		foreach ($root_ids as $root) {
			$tree[$root] = $this->catesTree( $root, $map ) ;
		}

		return [ 'data'=>$data, 'map'=>$map, 'tree'=>$tree];
	}


	/**
	 * 生成分类树
	 * @param  [type] $parent_id [description]
	 * @param  [type] $cates_map [description]
	 * @return [type]            [description]
	 */
	function catesTree( $parent_id,  & $cates_map ) {

		$data = $cates_map[$parent_id]; 
		if ( $data == null ) {
			$data = [];
		}

		// 子集
		foreach ( $data  as $idx=>$ca ) {
			$parent_id = $ca['category_id'];

			if ( empty($parent_id) ) {
				$data[$idx]['children'] = [];
			} else {

				$data[$idx]['children'] = $this->catesTree( $parent_id, $cates_map );
			}
		}

		return $data;
	}



	/**
	 * 遍历分类
	 * @param  [type]  $fn        [description]
	 * @param  integer $parent_id [description]
	 * @param  integer $depth     [description]
	 * @return [type]             [description]
	 */
	function each( $fn, $parent_id=null, $depth=0 ) {
		
		$depth ++;
		if ( !is_callable($fn) ) {
			$fn = function( $data, $depth ) {};
		}
		
		$qb = $this->query()->where('status', '=', 'on');
		$qb->where('parent_id','=', $parent_id)	;
		$resp = $qb->get()->toArray();

		if ( empty($resp) ) {
			return;
		}

		foreach ($resp as $rs ) {
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