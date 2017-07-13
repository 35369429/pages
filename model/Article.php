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

define('ARTICLE_PUBLISHED', 'published');  // 文章状态 已发布
define('ARTICLE_UNPUBLISHED', 'unpublished');  // 文章状态 未发布
define('DRAFT_APPLIED', 'applied'); // 已合并到文章中 DRAFT
define('DRAFT_UNAPPLIED', 'unapplied'); // 未合并到文章中 DRAFT


/**
 * 文章数据模型
 */
class Article extends Model {

	protected $article_category;
	protected $article_tag;
	protected $article_draft;


	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {

		parent::__construct(['prefix'=>'mina_pages_']);
		$this->table('article');
		$this->article_category = Utils::getTab('article_category', "mina_pages_");  // 分类关联表
		$this->article_tag = Utils::getTab('article_tag', "mina_pages_");    // 标签关联表
		$this->article_draft = Utils::getTab('article_draft', "mina_pages_");  // 文章草稿箱

	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
		$struct = [
			'article_id'=> ['bigInteger', ['length'=>20, 'unique'=>1]],  // 文章 ID  ( 同 _id )
			'cover'=> ['string',  ['length'=>256]],  // 文章封面
			'title'=>['string',  ['length'=>128, 'index'=>1]],  // 标题
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
			'status'=> ['string', ['length'=>40,'index'=>1, 'default'=>ARTICLE_UNPUBLISHED]],  // 文章状态 unpublished/published
		];

		$struct_draft_only = [
			'draft_status'=> ['string', ['length'=>40,'index'=>1, 'default'=>DRAFT_UNAPPLIED]],  // 草稿状态 unapplied/applied
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
	 * 保存文章 
	 */
	function save( $data ) {

		// 添加文章
		if ( empty($data['article_id']) ) { 
			$data = $this->create( $data );
			unset($data['created_at']);
			unset($data['deleted_at']);
			unset($data['updated_at']);
			unset($data['_id']);
			$data['draft_status'] = DRAFT_APPLIED;

		} else { 
			$data['draft_status'] = DRAFT_UNAPPLIED;
		}

		// 保存到草稿表
		$article_id = $data['article_id'];
		$data['history'] = $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);
		if ( is_array($data['history']) && !is_null($data['history']['history'])) {
			unset( $data['history']['history']);
		}

		if ( !empty($data['delta']) ) {
			// 生成文章正文
			$data['content'] = 'compile web content from delta' . json_encode($data['delta']);
			// 生成小程序正文
			$data['ap_content'] = 'compile wxapp content from delta' . json_encode($data['delta']);
		}

		$this->article_draft->createOrUpdate( $data ); 
		
		// 发布文章
		if ( $data['status'] == ARTICLE_PUBLISHED ) {
			return $this->published( $article_id );
		}
		return $this->article_draft->getLine("WHERE article_id=?", ['*'], [$article_id]);
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
				return $rs;
			}
		}

		return $this->saveAsDraft();
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
		$rs['draft_status'] = DRAFT_APPLIED;  // 标记草稿与文章同步

		return $this->article_draft->updateBy( 'article_id', $rs );
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
			$draft = $this->article_draft->updateBy('article_id', $draft);
		} else {  // 更新文章状态
			$draft = $this->getLine("WHERE article_id=?", ['*'], [$article_id]);
		}


		$draft['status'] = ARTICLE_PUBLISHED; // 文章ID 更新为已发布
		return $this->updateBy('article_id', $draft );
	}


	/**
	 * 取消发布文章
	 * @param  [type] $article_id [description]
	 * @return [type]             [description]
	 */
	function unpublished( $article_id ) {

		return $this->updateBy('article_id',[
			'article_id' => $article_id,
			'status' => ARTICLE_UNPUBLISHED
		]);
	}




	/**
	 * 更新文章
	 * @param  string $data 
	 * @return [type]       [description]
	 */
	function updateBy( $uni_key, $data ) {

		$rs = parent::updateBy( $uni_key, $data );

		if ( !empty($data['category']) ) {

			$article_id = $rs['article_id'];

			// 清除旧分类
			$this->article_category->runsql("update {{table}} set deleted_at=? where article_id=", false, [
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
			
			// 清除旧分类
			$this->article_category->runsql("update {{table}} set deleted_at=? where article_id=", false, [
				date('Y-m-d H:i:s'), 
				$article_id
			]);  


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
		// $draft = $data;
		$rs = parent::create( $data );  // 创建文章记录

		if ( isset($data['category']) ) {
			$category = is_array($data['category']) ? $data['category'] : [$data['category']];

			foreach ($category as $cid ) {
				$this->article_category->createOrUpdate([
					"article_id" => $data['article_id'],
					"category_id" => $cid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `category_id`))'
				]);
			}

			// $draft['category'] = $category;
		}

		if ( isset($data['tag']) ) {

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

			// $draft['tag'] = $tag;
		}

		// 创建草稿记录
		// $this->draft 
		
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