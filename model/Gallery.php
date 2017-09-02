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
			->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>1, 'default'=>'on']) )  
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

			// 动态图集模板 ( 图形编辑器 ) | 为 NULL 选取 gallery 中指定的模板
			->putColumn( 'template', $this->type('longText', ['json'=>true]) )

			// 动态图集栏位名称  | 为 NULL 选取 gallery 中指定的栏位
			->putColumn( 'columns', $this->type('longText', ['json'=>true]) )

			// 动态图集模板用到资源映射 ( url => path )  | 为 NULL 选取 gallery 中指定的模板
			->putColumn( 'resource', $this->type('longText',     ['json'=>true]) )

			// 动态图集模板上次更新时间  | 为 NULL 选取 gallery 的时间
			->putColumn( 'template_update_time', $this->type('timestampTz', ['index'=>1]) )

			// 图片生成图片时间 
			->putColumn( 'generate_update_time', $this->type('timestampTz', ['index'=>1]) )	

			// 图片状态 on / off / pending / draft
			->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>1, 'default'=>'on']) )  
		;
	}


	/**
	 * 保存图集
	 * @param  array  $gallery 图集字段清单
	 * @param  array  $images  图集中的图片清单
	 * @return 
	 */
	function save( $gallery, $images=[] ) {

		// 检查参数
		if ( !is_array($gallery)) {
			throw new Excp('参数错误', 402, ['gallery'=>$gallery, "images"=>$images] )
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
			$rs = $this->update_by( 'gallery_id', $gallery );
		}

		$this->saveImages( $rs['gallery_id'],  $images );
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
			$is_template_updated =  ($last !== $curr)
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
	 * 向图集中添加/更新一组图片
	 * @param [type] $gallery_id [description]
	 * @param [type] $images     [description]
	 */
	function saveImages( $gallery_id, $images ) {
	
	}



	/**
	 * 移除图片
	 * @param  [type] $image_id [description]
	 * @return [type]           [description]
	 */
	function removeImage( $image_id ) {

	}


	function __clear() {
		$this->image->dropTable();
		$this->dropTable();
	}

}
