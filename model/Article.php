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

define('ARTICLE_PUBLISHED', 'published');  // 文章状态 已发布
define('ARTICLE_UNPUBLISHED', 'unpublished');  // 文章状态 未发布
define('ARTICLE_PENDING', 'pending');  // 文章状态 未完成抓取
define('DRAFT_APPLIED', 'applied'); // 已合并到文章中 DRAFT
define('DRAFT_UNAPPLIED', 'unapplied'); // 未合并到文章中 DRAFT

define('STATUS_PUBLISHED', 'PUBLISHED');   // 已发布
define('STATUS_UNPUBLISHED', 'UNPUBLISHED');   // 未发布
define('STATUS_UNAPPLIED', 'UNAPPLIED');   // 有修改（尚未更新)
define('STATUS_PENDING', 'PENDING');   // 同步中（数据尚未准备好）

define('DEFAULT_PROJECT_NAME', 'default');  // 默认项目名称
define('DEFAULT_PAGE_SLUG', '/article/detail');  // 默认页面地址
define('DEFAULT_PAGE_SLUG_V2', '/desktop/article/detail');  // 默认页面地址V2


/**
 * 文章数据模型
 */
class Article extends Model {

	public $article_category;
	public $article_tag;
	public $article_draft;


	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {

		parent::__construct(['prefix'=>'mina_pages_']);

		$this->table('article');
		$this->delta_render = new Render();
		$this->article_category = Utils::getTab('article_category', "mina_pages_");  // 分类关联表
		$this->article_tag = Utils::getTab('article_tag', "mina_pages_");    // 标签关联表
		$this->article_draft = Utils::getTab('article_draft', "mina_pages_");  // 文章草稿箱
		$this->page = Utils::getTab('page', 'core_');  // 页面表


		// $root = Conf::G("storage/local/bucket/public/root");
		// $options = [
		// 	"prefix" => $root . '/media',
		// 	"url" => "/static-file/media",
		// 	"origin" => "/static-file/media",
		// 	"cache" => [
		// 		"engine" => 'redis',
		// 		"prefix" => '_mediaStorage:',
		// 		"host" => Conf::G("mem/redis/host"),
		// 		"port" => Conf::G("mem/redis/port"),
		// 		"raw" =>3600,  // 数据缓存 1小时
		// 		"info" => 3600   // 信息缓存 1小时
		// 	]
		// ];
		// $this->stor = new Local( $options );
		$this->media = new Media;

	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
		$struct = [
			'article_id'=> ['bigInteger', ['length'=>20, 'unique'=>1]],  // 文章 ID  ( 同 _id )
			'outer_id'=> ['string', ['length'=>128, 'unique'=>1]],  // 外部ID用于数据同步下载 ( 同 _id )
			'cover'=> ['string',  ['length'=>256]],   // 文章封面
			'thumbs' =>['text',  ["json"=>true]],     // 主题图片(三张)
			'images'=> ['text',  ['json'=>true]],  // 图集文章
			'videos'=> ['text',  ['json'=>true]],  // 视频文章
			'audios'=> ['text',  ['json'=>true]],  // 音频文章
			'title'=>['string',  ['length'=>128, 'index'=>1]],  // 标题
			'author'=> ['string',  ['length'=>128, 'index'=>1]],  // 作者
			'origin'=> ['string',  ['length'=>128, 'index'=>1]],  // 来源
			'origin_url'=>['string',  ['length'=>256]],  // 来源网址
			'summary'=> ['string',  ['length'=>600]],  // 摘要
			'seo_title'=> ['string',  ['length'=>256]],  // 搜索引擎标题
			'seo_keywords'=> ['string',  ['length'=>256]],  // 搜索引擎关键词
			'seo_summary'=> ['string',  ['length'=>600]],   // 搜索引擎显示摘要
			'publish_time'=> ['timestampTz',  ["index"=>1]],   // 发表时间
			'update_time'=> ['timestampTz',  ["index"=>1]],  // 更新时间
			'create_time'=> ['timestampTz',  ["index"=>1]],  // 创建时间
			'sync'=> ['string',  ["json"=>true, 'length'=>600]],  // 公众号同步状态
			'content'=> ['longText',  []],  // 正文 (WEB)
			'ap_content'=> ['longText',  ["json"=>true]],  // 小程序正文
			'delta'=> ['longText',  ["json"=>true]],  // 编辑状态文章 (Delta )
			'param'=> ['string', ['length'=>128,'index'=>1]],  // 自定义查询条件
			'stick'=> ['integer', ['index'=>1, 'default'=>"0"]],  // 置顶状态
			'preview' => ['longText', ['json'=>true]], // 预览链接
			'links' => ['longText', ['json'=>true]], // 访问链接
			'user' => ['string', ['length'=>128,'index'=>1]], // 最后编辑用户ID
			'policies' => ['string', ['json'=>true]], // 文章权限预留字段
			'status'=> ['string', ['length'=>40,'index'=>1, 'default'=>ARTICLE_UNPUBLISHED]],  // 文章状态 unpublished/published/pending
		];

		$struct_draft_only = [
			'draft_status'=> ['string', ['length'=>40,'index'=>1, 'default'=>DRAFT_UNAPPLIED]],  // 草稿状态 unapplied/applied/pendding 
			'history'=>  ['longText', ['json'=>true] ],    // 上一次修改记录 (用于保存)
			'category'=> ['longText', ['json'=>true] ],    // 分类映射信息 ( 仅用于草稿信息 )
			'tag'=>['longText', ['json'=>true] ]   // 标签映射信息 ( 仅用于草稿信息 )
		];

		// 天剑文章表和草稿表结构
		foreach ($struct as $field => $args ) {
			$this->putColumn( $field, $this->type($args[0], $args[1]) );
			$this->article_draft->putColumn( $field, $this->type($args[0], $args[1]) );
		}

		// 添加草稿表结构
		foreach ($struct_draft_only as $field => $args ) {
			$this->article_draft->putColumn( $field, $this->type($args[0], $args[1]) );	
		}

		// 关联表 article_category
		// $article_category = $this->article_category ;
		// if ( $article_category->tableExists() === false) {
		//
		$this->article_category->putColumn( 'article_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20 ]) )  // 文章 ID 
				                ->putColumn( 'category_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20]) )
				                ->putColumn( 'unique_id', $this->type('string', ['length'=>40, 'unique'=>1]) );

		// }

		// 关联表 article_tag
		// $article_tag = $this->article_tag;
		// if ( $article_tag->tableExists() === false) {
		$this->article_tag->putColumn( 'article_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20]) )  // 文章 ID 
				           ->putColumn( 'tag_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20]) )
				           ->putColumn( 'unique_id', $this->type('string', ['length'=>40, 'unique'=>1]) );
		// }
	}


	/**
	 * 从公众号(订阅号/服务号)下载文章
	 * $this
	 */
	function downloadFromWechat(  $appid, $offset = null ) {

		$perpage = 20;
		$cate = new Category();
		$settings = $cate->wechat();
		$c = $settings[$appid];

		if ( empty($c) ){
			throw new Excp('配置信息错误', 400, ['appid'=>$appid]);	
		}

		$wechat = new Wechat([
			"appid" => $c['appid'],
			'secret' => $c['secret']
		]);

		
		$count = $wechat->countMedia();
		$offset = ($offset === null) ? intval($c['offset']) : intval( $offset );
		$total = intval($count['news_count']) - $offset;
		$page = ceil($total / $perpage );

		for( $i=0; $i<$page; $i++ ) {
			$from = $perpage * $i + $offset;
			$resp = $wechat->searchMedia($from, $perpage, 'news');
			foreach ($resp['item'] as $item ) {
				foreach ($item['content']['news_item'] as $idx=>$media ) {
					$this->importWechatMedia($c, $item['media_id'], $media, $idx );
				}
			}
		}

		// 更新 offset
		$cate->updateBy('category_id', ['category_id'=>$c['category_id'], 'wechat_offset'=>intval($count['news_count'])]);
		return $this;
	}


	/**
	 * 上传文章到公众号
	 * 
	 * @param  string $appid      [description]
	 * @param  [type] $article_id [description]
	 * @param  [type] $create     [description]
	 * @return [type]             [description]
	 */
	function uploadToWechat(  $appid, $article_id, $create=null ) {

		return $this;
	}




	/**
	 * 导入媒体文章
	 * @param  string  $media_id 公众平台 media_id
	 * @param  array   $media    公众平台图文消息数据结构
	 * @param  integer $index    item index ( 一篇图文，包含多个index )
	 * @return $this
	 */
	function importWechatMedia(  $c,  $media_id,  $media, $index = 0 ) {

		$outer_id = $media_id . $index;
		$rows = $this->query()->where("outer_id", '=', $outer_id)->limit(1)->select('article_id')->get()->toArray();
		$rs = current($rows);
		if ( isset($rs['article_id'] )) {
			$data['article_id'] = $rs['article_id'];
		}


		$this->delta_render->loadByHTML($media['content']);
		$delta = $this->delta_render->delta();
		$images =  $this->delta_render->images();
		$data['delta'] = $delta;
		$data['images'] = $images;
		$data['title'] = $media['title'];
		$data['author'] = $media['author'];
		$data['cover'] = $media['thumb_url'];
		$data['summary'] = $media['digest'];
		$data['origin_url'] = $media['content_source_url'];
		$data['status'] = ARTICLE_PENDING;
		$data['category'] = $c['category_id'];
		$data['outer_id'] = $media_id . $index;
		$data['sync'] = [
			$c['appid'] => [
				"media_id" => $media_id,
				"index" => $index,
				"url" => $media['url'],
				"thumb_media_id" => $media['thumb_media_id'],
				"update_at" => time()
			]
		];

		$rs = $this->save( $data );
		$imgcnt = count($rs['images']);
		$article_id = $rs['article_id'];
		$t = new \Tuanduimao\Task;
		$task_id = $t->run('下载文章图片: ' . $rs['title'], [
			"app_name" => "mina/pages",
			"c" => 'article',
			'a' => 'realdownloadimages',
			'data'=> [
				"article_id" => $rs['article_id'],
				"status" => ARTICLE_UNPUBLISHED,
				"task_id" => $task_id
			]
		], function( $status, $task, $job_id, $queue_time, $resp ) use( $imgcnt, $article_id ) {
			try {
				$art = new Article;
				$art->save([
					'article_id'=>$article_id,
					'status' => 'unpublished'
				]);
			} catch(Excp $e){
			} catch(Exception $e){}

			$t = new \Tuanduimao\Task;
			if ( $status == 'failure') {
				$t->progress($task['task_id'], 100,  "下载图片失败 文章 {$article_id} 图片（{$imgcnt}）");
			} else {
				$t->progress($task['task_id'], 100,  "下载图片成功 文章 {$article_id} 图片（{$imgcnt}）" );
			}
		});
	}


	function downloadImages( $article_id, $status=null ) {

		$rs = $this->load($article_id);

		if ( empty($rs) ) {
			throw new Excp("文章不存在( {$article_id})", 404, ['article_id'=>$article_id, $status=>$status] );
		}

		$delta = $rs['delta'];
		$images = $rs['images'];
		$new_images = []; $new_images_map =[];

		// 抓取图片
		foreach ($images as $idx=>$img ) {
			$src = $img['src'];
			$ext = $this->media->getExt( $src );
			
			if ( !in_array($ext, ['png', 'jpg', 'gif', 'peg']) ) {
				$ext = 'png';
			}

			try {
				$nimg = $this->media->uploadImage($src, $ext, false);
				$new_images_map[$src] = $new_images[$idx] = [
					'src' => $nimg['url'],
					"ratio" => $img['data-ratio'],
					"s" => $img['data-s'],
					"type"=> $img['data-type'],
					"url" => $nimg['url'], 
					"origin"=> $nimg['origin'],
					"path" => $nimg['path'], 
					"media_id" => $nimg['media_id']
				];
			} catch( Excp $e ){}

			
		}

		// 替换图片
		foreach ( $delta['ops'] as $idx => $dt  ) {
			if ( is_array($dt['insert']) && isset($dt['insert']['cimage']) ) {
				$src = $dt['insert']['cimage']['src'];
				if ( !empty($new_images_map[$src]) ) {
					$delta['ops'][$idx]['insert']['cimage'] = $new_images_map[$src];
				}
			}
		}

		$updateData = [
			"article_id" => $article_id,
			"delta" =>$delta,
			"images" => $new_images
		];

		if ( !empty($status) ) {
			$updateData['status'] = $status;
		}

		// 替换 Cover 图片
		if ( !empty($rs['cover']) ) {
			$rs = $this->media->uploadImage($rs['cover'], null, false);
			$updateData['cover'] = $rs['url'];
		}

		return $this->save( $updateData );
	}




	/**
	 * 保存文章 
	 */
	function save( $data ) {

		if ( is_string($data['tag']) ) {
			$data['tag'] = explode(',', $data['tag']);
		}


		if ( is_string($data['category'])) {
			$data['category'] = explode(',', $data['category']);
		}

		if ( isset($data['publish_date']) ) {

			if ( empty($data['publish_time']) ) {
				$data['publish_time'] = date('H:i:s');
			}

			$data['publish_time'] = str_replace('@', '', $data['publish_time']);
			$data['publish_time'] = str_replace('时', ':', $data['publish_time']);
			$data['publish_time'] = str_replace('分', ':', $data['publish_time']);
			$data['publish_time'] = $data['publish_date'] . ' ' . $data['publish_time'];

		}


		// 添加文章
		if ( empty($data['article_id']) ) {
		
		// if ( true ) {  // 4 debug

			if ( empty($data['create_time']) ) {
				$data['create_time'] = date('Y-m-d H:i:s');
			}

			$data = $this->create( $data );
			unset($data['created_at']);
			unset($data['deleted_at']);
			unset($data['updated_at']);
			unset($data['_id']);
			$data['draft_status'] = DRAFT_APPLIED;
			$data['category'] = $this->getCategories($data['article_id'], 'category.category_id' );
			$data['tag'] = $this->getTags($data['article_id'], 'tag.name' );

		} else { 
			$data['draft_status'] = DRAFT_UNAPPLIED;
		}

		if ( empty($data['update_time']) ) {
			$data['update_time'] = date('Y-m-d H:i:s');
		}



		// 保存到草稿表
		$article_id = $data['article_id'];

		if ( !empty($data['delta']) ) {
			
			$this->delta_render->load($data['delta']);

			// 生成文章正文
			$data['content'] = $this->delta_render->html();

			// 获取图片信息
			$data['images'] = $this->delta_render->images();

			// 生成小程序正文
			$data['ap_content'] = $this->delta_render->wxapp();
		}

		$data['history'] = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);
		if ( is_array($data['history']) && !is_null($data['history']['history'])) {
			unset( $data['history']['history']);
		}

		// 生成预览链接
		$data['preview'] = $this->previewLinks( $article_id, $data['category']);


		if ( empty($data['history'])) {
			$draft = $this->article_draft->create( $data ); 
		} else {
			$draft = $this->article_draft->updateBy( 'article_id', $data ); 
		}

		
		// 发布文章
		if ( $data['status'] == ARTICLE_PUBLISHED ) {
			return $this->published( $article_id );
		}

		// 转为草稿
		if ( $data['status'] == ARTICLE_UNPUBLISHED ) {
			return $this->unpublished( $article_id );	
		}

		// 转为PENDING
		if ( $data['status'] == ARTICLE_PENDING ) {
			return $this->pending( $article_id );	
		}
		
		return $draft;
	}

	/**
	 * 文章是否发布
	 * @param  [type]  $article_id [description]
	 * @return boolean             [description]
	 */
	function isPublished( $article_id ) {

		$data = $this->query()
		  			 ->where("article_id", '=', $article_id)
					 ->where('status', '=', 'published')
					 ->limit(1)
					 ->select('article_id')
					 ->get()->toArray();
		if ( empty($data) ) {
			return false;
		}

		return true;
	}

	/**
	 * 删除
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function rm( $article_id ){

		$time = date('Y-m-d H:i:s');
		$resp = $this->updateBy( 'article_id', [
			"deleted_at"=>$time, 
			"article_id"=>$article_id,
			"outer_id" => NULL
		]);

		if ( $resp['deleted_at'] === $time ){
			$ret = $this->article_draft->updateBy( 'article_id', [
				"deleted_at"=>$time, 
				"article_id"=>$article_id,
				"outer_id" =>NULL
			]);
		}

		return ( $resp && $ret);
	}

	
	/**
	 * 提取文章
	 * @param  int  $article_id 文章ID
	 * @param  boolean $draft 为true 代表优先从草稿中提取
	 * @return 
	 */
	function load( $article_id, $draft = true ) {

		if ( $draft === true ) {
			$rs = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);
			if ( !empty($rs) ) {
				$this->format( $rs );
				return $rs;
			}
		}

		// 如果没有草稿，则提取草稿
		return $this->saveAsDraft( $article_id );
	}


	function format( & $article ) {

		if ( isset($article['publish_time']) ) {
			$time = strtotime($article['publish_time']);
			$article['publish_time'] = null;
			if ( $time > 0 ) {
				$article['publish_time'] = date('@ H时i分', $time);
				$article['publish_date'] = date('m/d/2017', $time);
				// $article['time'] = $time;
			}
		}

		// if ( !isset($article['delta']) || empty($article['delta']) ) {
		// 	$article['delta'] = 'null';
		// }

		return $article;
	}



	/**
	 * 保存为草稿
	 * @param  string  $article_id 文章ID
	 * @param  boolean $override  为true 代表覆盖现有信息
	 * @return
	 */
	function saveAsDraft( $article_id, $override = false ) {

		if( $override !== true ) {
			$rs = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);
			if ( !empty($rs) ) {
				throw new Excp("草稿已存在( {$article_id})", 403, ['article_id'=>$article_id, $override=>$override] );
			}
		}

		$rs = $this->getLine("WHERE article_id=?", ['*'], [$article_id]);
		if ( empty( $rs) ) {
			throw new Excp("文章不存在( {$article_id})", 404, ['article_id'=>$article_id, $override=>$override] );
		}

		$rs['category'] = $this->getCategories($article_id, 'category_id');
		$rs['tag'] = $this->getTags($article_id, 'name');
		$rs['history'] = [];
		$rs['preview'] = $this->previewLinks( $article_id, $rs['category']);  // 生成预览链接
		$rs['draft_status'] = DRAFT_APPLIED;  // 标记草稿与文章同步

		$data =  $this->article_draft->updateBy( 'article_id', $rs );
		$this->format( $data );
		return $data;
	}




	/**
	 * 发布文章
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function published( $article_id ) {
		
		$draft = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);

		if ( !empty($draft) ) {
			$draft['draft_status'] = DRAFT_APPLIED;
			$draft['links'] = $this->links( $article_id ); // 生成链接地址
			$draft = $this->article_draft->updateBy('article_id', $draft);
		
		} else {  // 更新文章状态 （ 这个逻辑应该优化 )
			$draft = $this->getLine("WHERE article_id=?", ['*'], [$article_id]);
			$draft['links'] = $this->links( $article_id ); // 生成链接地址
		}

		$draft['status'] = ARTICLE_PUBLISHED; // 文章ID 更新为已发布
		$rs =  $this->updateBy('article_id', $draft );

		// 生成物料
		$this->makeMaterials( $rs );
		return $rs;
	}




	/**
	 * 生成物料
	 * @param  [type] $article [description]
	 * @return [type]             [description]
	 */
	function makeMaterials( $article ) {

		$article_id = $article['article_id'];
		if ( empty($article_id) ) {
			throw new Excp('制作物料失败, 参数错误', 402, ['article'=>$article]);
		}

		$param = "article_id:{$article_id}";
		$images = []; $thumbs = !empty($article['thumbs']) ? $article['thumbs'] : [];
		$image = [
			"A" => $article['title'],
			"B" => $article['summary'],
			"C" => !empty($article['cover']) ? $article['cover'] : "/s/mina/pages/static/defaults/950X500.png",
			"E" => $thumbs[0],
			"F" => $thumbs[1],
			"G" => $thumbs[2],
			"H" => $thumbs[3],
			"I" => $thumbs[4]
		];

		$g = new Gallery;
		$g->rmImagesByParam( $param );

		foreach ($article['links'] as $link ) {
			$link = !empty($link) ? $link['links']['desktop']  : "https://minapages.com";
			$image['D'] = $link;
			array_push($images, $image);
		}

		$images = $g->genImageData($images);
		$gallerys = $this->gallerys();
		foreach ($gallerys as $rs ) {
			$resp = $g->createImages( $rs['gallery_id'], $images, ['param'=>"article_id:{$article_id}"] );
			foreach ($resp as $im ) {
				$image_id = $im['data']['image_id'];
				$g->makeImage($image_id);
			}
		}
	}



	/**
	 * 取消发布文章
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function unpublished( $article_id ) {

		$this->article_draft->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_UNPUBLISHED
		]);

		return $this->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_UNPUBLISHED
		]);
	}


	/**
	 * 正在PENDING
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function pending( $article_id ) {
		$this->article_draft->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_PENDING
		]);

		return $this->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_PENDING
		]);
	}
	


	/**
	 * 读取文章状态名称
	 * @param  [type] $status       [description]
	 * @param  [type] $draft_status [description]
	 * @return [type]               [description]
	 */
	function cstatus( $status, $draft_status = null, $map = [] ) {
		if ( empty($map) ) {
			$map = [
				STATUS_UNPUBLISHED => '草稿',
				STATUS_PENDING => '同步中',
				STATUS_UNAPPLIED => '待更新',
				STATUS_PUBLISHED => '已发布'
			];
		}

		$status = $this->status($status, $draft_status);
		return $map[$status];
	}


	/**
	 * 读取文章状态码
	 * 
	 * @param  string $status       文章状态 unpublished 未发布/ published 已发布/ pending 数据尚未准备好
	 * @param  string $draft_status 草稿状态 unapplied 尚未更新/ applied 修改已更新/ pending 数据尚未准备好
	 * @return string 状态描述码 PUBLISHED 已发布 / UNPUBLISHED 未发布 / UNAPPLIED 有修改未更新  / PENDING 数据尚未准备好
	 */
	function status( $status, $draft_status = null ) {

		if ( $status == ARTICLE_UNPUBLISHED ) {  // 文章尚未发布
			return STATUS_UNPUBLISHED;
		
		} else if ( $status == ARTICLE_PENDING ) {
			return STATUS_PENDING;
		
		} else {

			if ( $draft_status == DRAFT_UNAPPLIED ) {
				return STATUS_UNAPPLIED;
			}

			return STATUS_PUBLISHED;
		}
	}


	/**
	 * 读取文章相关图集
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function gallerys() {
		$g = new Gallery();
		$gallerys = $g->getGallerys(1, ["param"=>'article'], 5);
		return $gallerys['data'];
	}


	/**
	 * 读取文章图集图片
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function galleryImages( $article_id ) {
		$g = new Gallery();
		$images = $g->getImages(1, ['param'=>"article_id:{$article_id}"], 5);
		return $images['data'];
	}



	/**
	 * 生成文章链接、生成二维码
	 * @param  string $article_id 
	 * @return 
	 */
	function links( $article_id,  $category = null ) {
		$default_home = Utils::getHome( $_SERVER['HTTP_TUANDUIMAO_LOCATION']);
		$uri = parse_url( $default_home);
		$default_project = Utils::getTab('project')->getVar('name', "LIMIT 1");
		if ( empty($default_project) ) {
			$default_project = DEFAULT_PROJECT_NAME;
		}
		$pages = [$default_project . DEFAULT_PAGE_SLUG, $default_project . DEFAULT_PAGE_SLUG_V2 ];


		if( $category === null ) {
			$category =  $this->getCategories( $article_id, 'category.category_id' );
		}

		// 根据类目信息，获取页面，并排重
		if ( !empty($category) ) {
			$cate = new Category();
			$cates = $cate->query()->whereIn('category_id', $category)->select('page', 'project')->get()->toArray();
			
			if ( !empty($cates) ) {

				foreach ($cates as $rs ) {
					$rs['project'] = !empty($rs['project']) ? $rs['project'] : $default_project;
					$rs['page'] = !empty($rs['page']) ? $rs['page'] : DEFAULT_PAGE_SLUG;
					array_push( $pages, $rs['project'] . $rs['page']);
				}

				$pages = array_unique($pages);

			}

		}


		// 读取页面详细信息
		$pages = $this->page->query()
						->leftJoin('project', 'project.name', '=', 'page.project')
						->whereIn('slug', $pages)
						->select(
							'page.cname as cname', 'page.name as name', 'page.slug as slug', 'align', 'adapt',
							'project.name as project', 'project.domain as domain'
						)
						->get()
						->toArray();
		
		$page_slugs = []; $page_slugs_map = [];  $proto = $uri['scheme'] . "://";
		foreach ($pages as $idx=>$pg ) {

			if ( empty($pg['domain']) ) {
				$pg['domain'] = $uri['host'];
			}

			$pg['home'] = $proto . $pg['domain'];
			
			foreach( $pg['adapt'] as $type ) { // 处理适配页面
				$pages[$idx]['links'][$type] = $pg['slug'];
				$page_slugs[] =  $pg['slug'];
				$page_slugs_map[$pg['slug']] = $pg;
			}

			foreach( $pg['align'] as $type => $pg_align ) {  // 处理联合页面
				if ( $type != 'wxapp') {
					$pages[$idx]['links'][$type] = $pg_align;
					$page_slugs[] =  $pg_align;
				} else {
					$pages[$idx]['links'][$type] = '/' . $pg_align . '?id=' . $article_id; 
				}
			}

			$pages[$idx]['article_id'] = $article_id;

			unset($pages[$idx]['align'] );
			unset($pages[$idx]['adapt'] );
		}

		


		// 获取适配链接
		$entry_maps = $this->getEntries( $article_id, $page_slugs );
		foreach ($pages as $idx=>$pg ) {

			$page = $page_slugs_map[$pg['slug']];
			$home= $page['home'];

			$desktop = $pages[$idx]['links']['desktop'];
			if( is_string($desktop) ) {
				$pages[$idx]['links']['desktop'] = $home.$entry_maps[$desktop]['latest'];
			}

			$mobile = $pages[$idx]['links']['mobile'];
			if( is_string($mobile) ) {
				$pages[$idx]['links']['mobile'] = $home.$entry_maps[$mobile]['latest'];
			}

			$wechat = $pages[$idx]['links']['wechat'];
			if( is_string($wechat) ) {
				$pages[$idx]['links']['wechat'] = $home.$entry_maps[$wechat]['latest'];
			}
		}

		return $pages;
	}


	/**
	 * 根据页面信息，计算入口数值
	 * @param  [type] $pages [description]
	 * @return [type]        [description]
	 */
	function getEntries(  $article_id,  $slugs ) {
		$slugs = array_unique( $slugs );
		$pages = $this->page->query()
						->whereIn('slug', $slugs)
						->select('slug','entries')
						->get()
						->toArray();

		if ( !is_array($pages) ) {
			throw new Excp('未查询到页面信息', 400, ['article_id'=>$article_id, 'pages'=>$pages]);
		}
		
		$resp = [];
		foreach ($pages as $rs ) {
			$slug = $rs['slug'];
			$resp[$slug] = ['entries'=>[], 'latest'=>''];
			$entries = $rs['entries'];
			foreach ($entries as $idx=>$entry ) {
				if ( $entry['method'] != 'GET') continue;

				$entry['router'] = str_replace('{id:\\d+}', $article_id,  $entry['router']);
				$entry['router'] = str_replace('{article_id:\\d+}', $article_id,  $entry['router']);
				$resp[$slug]['entries'][$idx] = $entry['router'];
				$resp[$slug]['latest'] = $entry['router'];
			}
		}

		return $resp;
	}



	/**
	 * 生成文章预览链接
	 */
	function previewLinks( $article_id,  $category = null ) {

		$pages = [DEFAULT_PAGE_SLUG];
		if( $category === null ) {
			$rs =  $this->article_draft->getLine("WHERE article_id=?", ['category'], [$article_id]);
			if ( empty($rs) ) {
				throw new Excp('草稿不存在', 400, ['article_id'=>$article_id]);
			}
			$category = $rs['category'];
		}



		// 根据类目信息，获取页面，并排重
		if ( !empty($category) ) {
			$cate = new Category();

			if ( !is_array($category) ) {
				$category = [$category];
			}

			$data = $cate->query()->whereIn('category_id', $category)->select('page')->get()->toArray();
			if ( !empty($data) ) {
				$data_pad = Utils::pad( $data, 'page');
				$pages= $data_pad['data'];
				$pages = array_unique($pages);
				foreach ($pages as $idx =>$page ) {
					if ( empty($page) ) {
						$pages[$idx] = DEFAULT_PAGE_SLUG;
					}
				}
			}
		}


		// 读取页面详细信息
		$pages = $this->page->query()
						->whereIn('slug', $pages)
						->select('cname', 'name', 'slug', 'align', 'adapt')
						->get()
						->toArray();
		// 获取适配链接
		foreach ($pages as $idx=>$pg ) {
			
			foreach( $pg['adapt'] as $type ) { // 处理适配页面
				$pages[$idx]['links'][$type] = App::NR('article' , 'preview', ['p'=>$pg['slug'], 'id'=>$article_id]);
			}

			foreach( $pg['align'] as $type => $pg_align ) {  // 处理联合页面
				if ( $type != 'wxapp') {
					$pages[$idx]['links'][$type] =  App::NR('article' , 'preview', ['p'=>$pg_align, 'id'=>$article_id]);
				} else {
					$pages[$idx]['links'][$type] = '/' . $pg_align . '?id=' . $article_id . '&preview=1'; 
				}
			}

			$pages[$idx]['article_id'] = $article_id;
			unset($pages[$idx]['align'] );
			unset($pages[$idx]['adapt'] );
		}

		return $pages;
	}



	/**
	 * 更新文章
	 * @param  string $data 
	 * @return [type]       [description]
	 */
	function updateBy( $uni_key, $data ) {

		if ( !isset($data['user']) ) {
			$data['user'] = App::$user['userid'];
		}

		if ( empty($data['update_time']) ) {
			$data['update_time'] = date('Y-m-d H:i:s');
		}
		

		$rs = parent::updateBy( $uni_key, $data );

		if ( !empty($data['category']) ) {

			$article_id = $rs['article_id'];

			// 清除旧分类
			$this->article_category->runsql("update {{table}} set deleted_at=? where article_id=? ", false, [
				date('Y-m-d H:i:s'), 
				$article_id
			]);  

			// 添加新分类
			$category = is_array($data['category']) ? $data['category'] : [$data['category']];
			foreach ($category as $cid ) {
				$this->article_category->createOrUpdate([
					"article_id" => $data['article_id'],
					"category_id" => $cid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `category_id`))'
				]);
			}
		}


		if ( !empty($data['tag']) ) {

			$article_id = $rs['article_id'];

			$time = date('Y-m-d H:i:s');
			// 清空旧 Tag
			$this->article_tag->runsql(
				"update {{table}} set deleted_at=? where article_id=? ", fasle, 
				[$time, $article_id]
			);  
			
			if ( is_string($data['tag']) ) {
				$data['tag'] = explode(',' , $data['tag']);
			}

			$tag = new Tag;
			$tagnames = is_array($data['tag']) ? $data['tag'] : [$data['tag']];
			$tagids = $tag->put( $tagnames );

			foreach ($tagids as $tid ) {
				$this->article_tag->createOrUpdate([
					"article_id" => $data['article_id'],
					"tag_id" => $tid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `tag_id`))'
				]);
			}
		}

		return $rs;
	}




	/**
	 * 添加文章
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function create( $data ) {

		$data['article_id'] = $this->nextid();

		if ( !isset($data['user']) ) {
			$data['user'] = App::$user['userid'];
		}


		// $draft = $data;
		$rs = parent::create( $data );  // 创建文章记录

		if ( !empty($data['category']) ) {

			$category = is_array($data['category']) ? $data['category'] : [$data['category']];

			foreach ($category as $cid ) {
				$this->article_category->createOrUpdate([
					"article_id" => $data['article_id'],
					"category_id" => $cid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `category_id`))'
				]);
			}
		}

		if ( !empty($data['tag']) ) {

			if ( is_string($data['tag']) ) {
				$data['tag'] = explode(',' , $data['tag']);
			}

			$tag = new Tag;
			$tagnames = is_array($data['tag']) ? $data['tag'] : [$data['tag']];
			$tagids = $tag->put( $tagnames );
			foreach ($tagids as $tid ) {
				$this->article_tag->createOrUpdate([
					"article_id" => $data['article_id'],
					"tag_id" => $tid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `tag_id`))'
				]);
			}

		}
		
		return $rs;
		
	}


	/**
	 * 读取一组文章分类
	 * @param  array  $article_ids 文章ID列表
	 * @param  string $field      [description]
	 * @return [type]             [description]
	 */
	function getCategoriesGroup( $article_ids, $field="*") {

		$c = new Category;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}

		$args = array_merge(['article_category.article_id as aid'], $args);
		$rows = $c->query()
		     ->rightJoin('article_category', 'article_category.category_id', '=', 'category.category_id')
		     ->whereIn( "article_category.article_id", $article_ids )
		     ->where("status", '=', "on")
		     ->select($args)
		     ->limit( 50 )
		     ->get()->toArray();

		  

		if ( empty($rows) ) return [];

		$resp = [];
		foreach ($rows as $idx=>$rs ) {

			$aid = $rs['aid']; unset( $rs['aid']);
			if ( !is_array($resp[$aid]) ) $resp[$aid] = [];

			if ( count($rs) == 1) { //如果仅取一个数值，则降维
				array_push($resp[$aid], end($rs));
			} else {
				array_push($resp[$aid], $rs);
			}
		}

		return $resp;
	}


	/**
	 * 读取一组文章标签信息
	 * @param  array  $article_ids 文章ID列表
	 * @param  string | array ...$field 读取字段
	 * @return array 标签数组
	 */
	function getTagsGroup( $article_ids, $field="*") {

		$t = new Tag;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}

		$args = array_merge(['article_tag.article_id as aid'], $args);
		$rows = $t->query()
		     ->rightJoin('article_tag', 'article_tag.tag_id', '=', 'tag.tag_id')
		     ->whereIn( "article_tag.article_id",  $article_ids )
		     ->select($args)
		     ->limit( 50 )
		     ->get()->toArray();


		if ( empty($rows) ) return [];

		$resp = [];
		foreach ($rows as $idx=>$rs ) {

			$aid = $rs['aid']; unset( $rs['aid']);
			if ( !is_array($resp[$aid]) ) $resp[$aid] = [];

			if ( count($rs) == 1) { //如果仅取一个数值，则降维
				array_push($resp[$aid], end($rs));
			} else {
				array_push($resp[$aid], $rs);
			}
		}

		return $resp;

	}




	/**
	 * 读取一篇文章分类信息
	 * @param  int $article_id 文章ID
	 * @param  string | array ...$field 读取字段
	 * @return array 分类数组
	 */
	function getCategories( $article_id, $field="*") {

		$c = new Category;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}


		$resp = $rows = $c->query()
		     ->rightJoin('article_category', 'article_category.category_id', '=', 'category.category_id')
		     ->where( "article_category.article_id", '=', $article_id )
		     ->where("status", '=', "on")
		     ->select($args)
		     ->limit( 50 )
		     ->get()->toArray();


		if ( empty($resp) ) return [];

		if  (count(end($rows)) == 1) {  // 如果仅取一个数值，则降维
			$resp = [];
			foreach ($rows as $idx=>$rs ) {
				array_push( $resp, end($rs) );
			}
		}

		return $resp;
	}

	/**
	 * 读取一篇文章标签信息
	 * @param  int $article_id 文章ID
	 * @param  string | array ...$field 读取字段
	 * @return array 分类数组
	 */
	function getTags( $article_id, $field="*") {

		$t = new Tag;

		if ( is_array($field) ) {
			$args = $field;
		} else {
			$args = func_get_args();
			array_shift($args);
		}


		$resp = $rows = $t->query()
		     ->rightJoin('article_tag', 'article_tag.tag_id', '=', 'tag.tag_id')
		     ->where( "article_tag.article_id", '=', $article_id )
		     ->select($args)
		     ->limit( 50 )
		     ->get()->toArray();


		if ( empty($resp) ) return [];

		if  (count(end($rows)) == 1) {  // 如果仅取一个数值，则降维
			$resp = [];
			foreach ($rows as $idx=>$rs ) {
				array_push( $resp, end($rs) );
			}
		}

		return $resp;

	}



	function __clear() {
		Utils::getTab('article_category', "mina_pages_")->dropTable();
		Utils::getTab('article_tag', "mina_pages_")->dropTable();
		Utils::getTab('article_draft', "mina_pages_")->dropTable();
		$this->dropTable();
	}

}