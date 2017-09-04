<?php
namespace Mina\Pages\Model; 
define('__NS__', 'Mina\Pages\Model'); // 兼容旧版 App::M 方法调用

use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Mem as Mem;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Err as Err;
use \Tuanduimao\Conf as Conf;
use \Tuanduimao\Model as Model;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Wechat as Wechat;
use \Tuanduimao\Media as Media;
use \Mina\Delta\Render as Render;
use \Tuanduimao\Task as Task;
use \Exception as Exception;


/**
 * 图集数据模型
 */
class Gallery extends Model {

	function __construct( $param=[] ) {

		parent::__construct(['prefix'=>'mina_pages_']);
		$this->table('gallery');

		// 图集内图片数据表
		$this->image = Utils::getTab('gallery_image', "mina_pages_");  

		$this->media = new Media;
	}

	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
		
		// 图集表
		$this

			// 图集ID
			->putColumn( 'gallery_id', $this->type('string', ['length'=>128, 'unique'=>1]) )  
			
			// 图集主题
			->putColumn( 'title', $this->type('string',     ['length'=>128, 'index'=>1]) )   

			// 图集介绍
			->putColumn( 'intro', $this->type('string',     ['length'=>400]) )   
			
			// 图集类型 dynamic / static 
			->putColumn( 'type', $this->type('string',     ['length'=>10, 'index'=>1, 'default'=>'dynamic']) )

			// 动态图集模板 ( 图形编辑器 )
			->putColumn( 'template', $this->type('longText', ['json'=>true]) )

			// 动态图集栏位名称
			->putColumn( 'columns', $this->type('longText', ['json'=>true]) )

			// 动态图集模板用到资源映射 ( url => path )
			->putColumn( 'resource', $this->type('longText',     ['json'=>true]) )

			// 动态图集模板上次更新时间
			->putColumn( 'template_update_time', $this->type('timestampTz', ['index'=>1]) )

			// 动态图集生成图片时间
			->putColumn( 'generate_update_time', $this->type('timestampTz', ['index'=>1]) )			 

			// 图集内图片数量
			->putColumn( 'count', $this->type('integer', ['index'=>1]) )			 			 

			// 动态图集状态 on / off / pending / draft
			->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>1, 'default'=>'draft']) )
		;


		// 图集图片表
		$this->image

			// 图片ID
			->putColumn( 'image_id', $this->type('string', ['length'=>128, 'unique'=>1]) )

			// 图片指纹 (用于排重 )
			->putColumn( 'fingerprint', $this->type('string', ['length'=>128, 'unique'=>1]) )

			// 所属图集ID
			->putColumn( 'gallery_id', $this->type('string', ['length'=>128, 'index'=>1]) )

			// 关联 Meida ID 
			->putColumn( 'media_id', $this->type('string', ['length'=>128, 'index'=>1]) )

			// 动态数据
			->putColumn( 'data', $this->type('longText', ['json'=>true]) )

			// 动态图集模板 ( 图形编辑器 ) | 为 NULL 选取 gallery 中指定的模板
			->putColumn( 'template', $this->type('longText', ['json'=>true]) )

			// 动态图集栏位名称  | 为 NULL 选取 gallery 中指定的栏位
			->putColumn( 'columns', $this->type('longText', ['json'=>true]) )

			// 动态图集模板用到资源映射 ( url => path )  | 为 NULL 选取 gallery 中指定的模板
			->putColumn( 'resource', $this->type('longText',     ['json'=>true]) )

			// 动态图集模板上次更新时间  | 为 NULL 选取 gallery 的时间
			->putColumn( 'template_update_time', $this->type('timestampTz', ['index'=>1]) )

			// 图片数据模板上次更新时间  | 为 NULL 选取 gallery 的时间
			->putColumn( 'data_update_time', $this->type('timestampTz', ['index'=>1]) )

			// 图片生成图片时间 
			->putColumn( 'generate_update_time', $this->type('timestampTz', ['index'=>1]) )	

			// 图片状态 on / off / pending / draft
			->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>1, 'default'=>'draft']) )  
		;
	}

	/**
	 * 编辑器提交的模板数据格式转换
	 * @param  [type] $images [description]
	 * @return [type]         [description]
	 */
	function editorToImage( $images ) {

		$data = [];
		foreach ($images as $idx=>$img ) {
			
			$rs = $img['data'];

			$__tmp = null;
			$__row = $img['row'];
			$__unikey = $img['unikey'];
			$__value = $img['value'];

			if ( empty($__unikey) ) {
				$keys = array_keys($img['data']);
				$__unikey = end($keys);
			}

			$image_id = $rs[$__unikey];
			if ( strpos( $image_id ,'tmp_') === 0 ) {
				$__tmp = $image_id;
				$rs[$__unikey] = $image_id = null;
			}

			array_push($data, [
				"image_id" => $image_id,
				"tmp" => $__tmp,
				"row" => $__row,
				"key" => $__unikey,
				"value" => $__value,
				"data" => $rs
			]);
		}

		return $data;
	}


	/**
	 * 编辑器提交的模板数据格式转换
	 * @param  [type] $template [description]
	 * @return [type]           [description]
	 */
	function editorToGallery( $template ) {

		if ( empty($template['page']) || !is_array($template['page'])) {
			throw new Excp('参数错误 ( page 信息格式不正确  )', 402, ['template'=>$template] );
		}

		if ( !is_array($template['items'])) {
			throw new Excp('参数错误 ( items 信息格式不正确 )', 402, ['template'=>$template] );
		}

		$page  = $template['page'];
		$items = $template['items'];

		unset($page['index']);

		$resource = []; 

		$data = [
			"gallery_id" => !empty($page['id']) ? $page['id'] : $this->genGalleryId(),
			"title" => $page['title'],
			"intro" => !empty($page['intro']) ? $page['intro'] :  $page['title'],
			"type" => 'dynamic',
			"template" => [ "page"=>$page, "items"=>[] ],
		];

		if ( !empty($page['bgimage']) ) {
			$resource[$page['bgimage']] = null;
		}

		foreach ($items as $it ) {

			if ( $it['name'] == 'image' && !empty($it['option']['src']) ) {
				$resource[$it['option']['src']] = null;

				if ( is_numeric($it['option']['origin']) && intval($it['option']['origin']) >= 0 ) {
					unset($it['option']['src']);
				}


			} else if  ( $it['name'] == 'qrcode'  ) {
				if (  !empty($it['option']['logo']) ) {
					$resource[$it['option']['logo']] = null;
				}

				if ( is_numeric($it['option']['origin']) && intval($it['option']['origin']) >= 0 ) {
					unset($it['option']['text']);
				}

				unset( $it['option']['src']);
			} else if ( $it['name'] == 'text'  ) {
				
				if ( is_numeric($it['option']['origin']) && intval($it['option']['origin']) >= 0 ) {
					unset($it['option']['text']);
				}

				unset( $it['option']['src']);

			}

			array_push( $data['template']['items'], [
				$it['name'],
				$it['option'], 
				$it['pos']
			]);
		}

		$data['resource'] = $this->download( $resource );
		return $data;
	}




	/**
	 * 将网络图片转存到本地 
	 */
	function download( $resource ) {
		return $resource;
	}


	/**
	 * 保存图集
	 * @param  array  $gallery 图集字段清单
	 * @return 
	 */
	function save( $gallery ) {

		// 检查参数
		if ( !is_array($gallery)) {
			throw new Excp('参数错误', 402, ['gallery'=>$gallery] );
		}

		if ( empty($gallery['gallery_id']) ){
			$gallery['gallery_id'] = $this->genGalleryId();
		}

		$last_gallery = $this->getLine("WHERE `gallery_id`=? LIMIT 1", ["*"], [$gallery['gallery_id']]);
		
		if ( $last_gallery == null ) {  // 创建
			$gallery['template_update_time'] = "DB::RAW(CURRENT_TIMESTAMP)";
			$rs = $this->create( $gallery );

		} else {  // 更新

			// 检查模板是否更新
			if ( $this->isTemplateUpdated($last_gallery['template'], $gallery['template']) ) {
				$gallery['template_update_time'] = "DB::RAW(CURRENT_TIMESTAMP)";
			}
			$rs = $this->updateBy( 'gallery_id', $gallery );
		}

		return $rs;
	}


	/**
	 * 检查模板是否生效
	 * @param  [type]  $last    [description]
	 * @param  [type]  $current [description]
	 * @return boolean          [description]
	 */
	function isTemplateUpdated( $last, $current ) {
		$is_template_updated  = true;
		if ( !empty($last)  &&  !empty($current) ) {
			$last = hash('md4',  json_encode($last));	
			$curr = hash('md4',  json_encode($current));
			$is_template_updated =  ($last !== $curr);
		}
		return $is_template_updated;
	}


	/**
	 * 生成图集 ID
	 * @return
	 */
	function genGalleryId() {
		return time() . rand(10000,99999);
	}


	/**
	 * 生成图片 ID
	 */
	function genImageId() {
		return time() . rand(10000,99999);
	}



	/**
	 * 查询图片清单
	 * @param  [type]  $page    [description]
	 * @param  [type]  $query   [description]
	 * @param  integer $perpage [description]
	 * @return [type]           [description]
	 */
	function getImages( $page=1, $query=[],  $perpage=12 ) {

		$qb = $this->image->query()
			   ->orderBy('created_at', 'desc')
			;

		if ( !empty($query['keyword']) ) {
			$qb->where('data', 'like', "%{$query['keyword']}%")
			   // ->orWhere('gallery.template', 'like', "%{$query['keyword']}%")
			   ;
		}

		if ( !empty($query['gallery_id']) ) {
			$qb->where('gallery_id', '=', "{$query['gallery_id']}");
		}

		$qb->select('image_id', 'gallery_id', 'media_id',  'status');
		$resp = $qb->pgArray($perpage, ['_id'], 'page', $page);


		// 处理结果
		foreach ($resp['data'] as $idx => $rs ) {
			$this->formatImage($resp['data'][$idx]);
		}

		return $resp;
	}


	// 处理图片数据
	function formatImage( & $rs ){

		if ( !empty($rs['media_id']) ) {
			$rs['origin'] = $this->media->getImageUrl($rs['media_id'], 'origin');
			$rs['small'] = $this->media->getImageUrl($rs['media_id'], 'small');
			$rs['url'] = $this->media->getImageUrl($rs['media_id'], 'url');
		} else if (  !empty($rs['image_id']) ) {
			$rs['origin'] = APP::NR("gallery", "image", ["image_id"=>$rs["image_id"],  "size"=>"origin"]);
			$rs['small'] = APP::NR("gallery", "image", ["image_id"=>$rs["image_id"],  "size"=>"small"]);
			$rs['url'] = APP::NR("gallery", "image", ["image_id"=>$rs["image_id"],  "size"=>"url"]);
		}

		if ( empty($rs['src'])) {
			$rs['src'] = $rs['origin'];
			$rs['w'] = 0;
			$rs['h'] = 0;
		}

		return $rs;
	}




	/**
	 * 读取图片
	 * @param  integer $page    [description]
	 * @param  array   $query   [description]
	 * @param  integer $perpage [description]
	 * @return [type]           [description]
	 */
	function getImagesData( $page=1, $query=[], $perpage=40 ) {

		$qb = $this->image->query()
				->leftjoin('gallery', 'gallery.gallery_id', '=', 'gallery_image.gallery_id')
				->whereNull('gallery_image.deleted_at')
				->orderBy('gallery_image._id')
			;

		if ( !empty($query['keyword']) ) {
			$qb->where('gallery_image.data', 'like', "%{$query['keyword']}%")
			   ;
		}

		if ( !empty($query['gallery_id']) ) {
			$qb->where('gallery_image.gallery_id', '=', "{$query['gallery_id']}")
				;
		}


		$qb->select(
			'gallery_image.image_id', 'gallery_image.gallery_id', 'gallery_image.data',
			'gallery_image.columns', 'gallery_image.template', 'gallery_image.status',
			'gallery.columns as columns_default','gallery.template as template_default'
		);

		$resp = $qb->pgArray($perpage, ['gallery_image._id'], 'page', $page);
		$resp = $this->formatImageData( $resp );
		return $resp;
	}


	// 转换为表格需要数据
	function formatImageData( $resp, $default_only = true ) {

		$data = [];  $cols = []; $columns =[];  $colHeaders =[];  $pagination = []; $template = [];

		foreach ( $resp['data'] as $rs ) {
			array_push( $data, array_values($rs['data']) );
			$cols = array_merge($cols, array_keys($rs['data']));
			
			if ( !empty($rs['columns']) ) {
				$colHeaders = array_merge($colHeaders, $rs['columns']);
			}

			if ( $default_only !== true && !empty($rs['template']) ) {
				$template = $rs['template'];
			} else {
				$template = $rs['template_default'];
			}
		}

		$pagination = $resp; unset($pagination['data']);

		$cols = array_unique($cols);
		$lastColIndex = count($cols) - 1;
		foreach ($cols as $idx => $col) {
			if ( $idx == $lastColIndex ) {
				array_push( $columns, ["name"=>$col, 'readOnly'=>true, "renderer"=>"{{unikey}}"] );
			} else {
				array_push( $columns, ["name"=>$col] );
			}
		}

		if ( !empty($template) && is_string($template) ) {
			$template = json_decode($template, true);
		}

		return [
			"data" => $data,
			"columns" => $columns,
			"colHeaders" => $colHeaders,
			"pagination" => $pagination,
			"template" => $template
		];
	}


	function emptyImageData() {

		$bgimage = rand(1,9);
		$maxcol = 20; $columns = []; 
		$data = [ 
			["示例文字", "/s/mina/pages/static/defaults/p{$bgimage}.jpg", "https://minapages.com"]
		];

		for( $i=0; $i<$maxcol; $i++ ) {
			$name = chr( $i + 65 );
			array_push($columns,["name"=>$name]);
		}
		array_push($columns,["name"=>chr(85), 'readOnly'=>true, "renderer"=>"{{unikey}}"]);

		$template = [
			"page" => [
				"bgimage" => "/s/mina/pages/static/defaults/{$bgimage}.jpg",
				"origin" =>1
			],
			"items" => [
				[ 
					"text", ["origin"=>0,
						"type"=>'vertical', 
						"dir"=>'rtl', 
						"size"=>24, "width"=>24,"height"=>168
						], 
					["x"=>750, 'y'=>30] 
				],
				[ "qrcode", ["origin"=>2, "width"=>120], ["x"=>30, "y"=>30]]
			]
		];

		return [
			"data" => $data,
			"columns" => $columns,
			"colHeaders" =>[],
			"pagination" => [
				"total" => 1,
				"per_page" => 40,
				"current_page" => 1,
				"last_page" => 1,
				"from" => 1, 
				"to" => 1,
				"next" => false,
				"prev" => false,
				"curr" => 1, 
				"last" => 1, 
				"perpage" => 40
			],
			"template" => $template
		];
	}




	/**
	 * 查询图集清单
	 */
	function getGallerys( $page=1, $query=[], $perpage=8 ) {

		$qb = $this->query()
			   ->join("gallery_image", "gallery_image.gallery_id", 'gallery.gallery_id')
			   ->whereNull('gallery_image.deleted_at')
			   ->groupBy('gallery.gallery_id')
			   ->orderBy('gallery.created_at', 'desc')
			;

		if ( !empty($query['keyword']) ) {
			$qb->where('title', 'like', "%{$query['keyword']}%")
			   ->where(function ( $qb ){
			   	   $qb->where('gallery.template', 'like', "%{$query['keyword']}%");
				   $qb->orWhere('gallery_image.template', 'like', "%{$query['keyword']}%");
				   $qb->orWhere('gallery_image.data', 'like', "%{$query['keyword']}%");
			   });
		}

		$qb->select(
			"gallery.title", "gallery.intro",  
			"gallery.type",  "gallery.gallery_id as gallery_id", 
			"gallery.status as status",
			"gallery_image.media_id as media_id",
			"gallery_image.image_id as image_id"
		);
		$qb->selectRaw("count(image_id) as count");
		$resp = $qb->pgArray($perpage, ['gallery._id'], 'page', $page);
		foreach ($resp['data'] as $idx => $rs) {
			$this->formatGallery($resp['data'][$idx]);
		}
		return $resp;
	}



	/**
	 * 查询图集
	 * @param  [type] $gallery_id [description]
	 * @return [type]             [description]
	 */
	function getGallery( $gallery_id ) {

		$qb = $this->query()
			   ->join("gallery_image", "gallery_image.gallery_id", 'gallery.gallery_id')
			   ->groupBy('gallery.gallery_id')
			   ->orderBy('gallery.created_at', 'desc')
			;

		$qb->where('gallery.gallery_id', '=', $gallery_id);
		$qb->select(
			"gallery.title", "gallery.intro",  
			"gallery.type",  "gallery.gallery_id as gallery_id", 
			"gallery.status as status",
			"gallery_image.media_id as media_id",
			"gallery_image.image_id as image_id"
		);
		$qb->selectRaw("count(image_id) as count");
		$resp = $qb->limit(1)->get()->toArray();
		$resp = end($resp);
		$this->formatGallery( $resp );
		return $resp;
	}




	// 处理图集封面
	function formatGallery( & $rs ){

		if ( !empty($rs['media_id']) ) {
			$rs['small'] = $this->media->getImageUrl($rs['media_id'], 'small');
		} else if (  !empty($rs['image_id']) ) {
			$rs['small'] = APP::NR("gallery", "image", ["image_id"=>$rs["image_id"],  "size"=>"small"]);
		}

		return $rs;
	}






	/**
	 * 制作图片并上传到 Media 中
	 * @param  [type] $image_id [description]
	 * @return [type]           [description]
	 */
	function makeImage( $image_id ) {

		return '8b93fd6dba07fd3c19f86b1f83ec5bc8';
	}


	/**
	 * 向图集中添加一组图片数据
	 * @param [type] $gallery_id [description]
	 * @param [type] $images     [description]
	 */
	function createImages( $gallery_id, $images ) {

		$resp = [];
		foreach ($images as $idx => $image) {
			$key = $image['key'];
			$image['gallery_id'] = $gallery_id;
			$unikey =  $image['image_id'] = $image['data'][$key] = $this->genImageId();
			$row = $image['row'];
			$tmp = $image['tmp'];

			$resp[$row] = [
				'data' =>$this->image->create( $image ),
				'method'=>'create',
				"tmp" => $tmp,
				'unikey' => $unikey
			];
		}
		return $resp;
	}


	/**
	 * 更新一组图片数据
	 * @param  [type] $images     [description]
	 */
	function updateImages( $images ) {

		$resp = [];
		foreach ($images as $idx => $image) {

			$key = $image['key'];
			$unikey =  $image['image_id'] = $image['data'][$key];
			$row = $image['row'];
			$resp[$row] =  [
				'data' =>$this->image->updateBy( "image_id",  $image ),
				'method'=>'update',
				'unikey' => $unikey
			];
		}

		return $resp;
	}


	/**
	 * 移除图片数据
	 * @param  [type] $image_id [description]
	 * @return [type]           [description]
	 */
	function removeImages( $images ) {
		$resp = [];
		foreach ($images as $idx => $image) {
			// $resp[] = $this->image->remove( $image_id, 'image_id');
			$key = $image['key'];
			$unikey =  $image['value'];
			if ( empty($unikey) ) {
				continue;
			}

			$row = $image['row'];
			$resp["-" . $row] =  [
				'data' =>$this->image->remove( $unikey, 'image_id'),
				'method'=>'remove',
				'unikey' => $unikey
			];
		}
		return $resp;
	}

	function __clear() {
		$this->image->dropTable();
		$this->dropTable();
	}

}
