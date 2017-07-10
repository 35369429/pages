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



/**
 * 文章数据模型
 */
class Article extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'mina_pages_']);
		$this->table('article');
		$this->article_category = Utils::getTab('article_category', "mina_pages_");
		$this->article_tag = Utils::getTab('article_tag', "mina_pages_");
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
			$this->putColumn( 'article_id', $this->type('bigInteger', ['length'=>20, 'index'=>1]) )  // 文章 ID  ( 同 _id )
				 ->putColumn( 'cover', $this->type('string',  ['length'=>256]) )  // 文章封面
				 ->putColumn( 'title', $this->type('string',  ['length'=>128, 'index'=>1]) )  // 标题
				 ->putColumn( 'origin', $this->type('string',  ['length'=>128, 'index'=>1]) )  // 来源
				 ->putColumn( 'origin_url', $this->type('string',  ['length'=>256]) )  // 来源网址
				 ->putColumn( 'summary', $this->type('string',  ['length'=>600]) )  // 摘要
				 ->putColumn( 'seo_title', $this->type('string',  ['length'=>256]) )  // 搜索引擎标题
				 ->putColumn( 'seo_keywords', $this->type('string',  ['length'=>256]) )  // 搜索引擎关键词
				 ->putColumn( 'seo_summary', $this->type('string',  ['length'=>600]) )   // 搜索引擎显示摘要
				 ->putColumn( 'publish_time', $this->type('timestampTz',  ["index"=>1]) )   // 发表时间
				 ->putColumn( 'update_time', $this->type('timestampTz',  ["index"=>1]) )  // 更新时间
				 ->putColumn( 'create_time', $this->type('timestampTz',  ["index"=>1]) )  // 创建时间
				 ->putColumn( 'sync', $this->type('string',  ["json"=>true, 'length'=>600]) )  // 公众号同步状态
				 ->putColumn( 'content', $this->type('longText',  []) )  // 已发布正文 (WEB)
				 ->putColumn( 'ap_content', $this->type('longText',  ["json"=>true]) )  // 已发布小程序正文
				 ->putColumn( 'draft', $this->type('longText',  []) )  // 待发布正文(草稿)
				 ->putColumn( 'ap_draft', $this->type('longText',  ["json"=>true]) )  // 小程序待发布正文(草稿)
				 ->putColumn( 'delta', $this->type('longText',  ["json"=>true]) )  // 编辑状态文章 (Delta )
				 ->putColumn( 'history', $this->type('longText',  ["json"=>true]) )  // 上一次发布数据备份(Delta )
				 ->putColumn( 'param', $this->type('string', ['length'=>128,'index'=>1]) )  // 自定义查询条件
				 ->putColumn( 'stick', $this->type('integer', ['index'=>1, 'default'=>"0"]) )  // 置顶状态
				 // 文章状态 published/draft
				 ->putColumn( 'status', $this->type('string', ['length'=>40,'index'=>1, 'default'=>'published']) )  
				 // 文章编辑状态 'published'/draft
				 ->putColumn( 'editor_status', $this->type('string', ['length'=>40,  'index'=>1 , 'default'=>'published']) )
		;


		// 关联表 article_category
		$article_category = $this->article_category ;
		if ( $article_category->tableExists() === false) {
			$article_category->putColumn( 'article_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20 ]) )  // 文章 ID 
				             ->putColumn( 'category_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20]) )
				             ->putColumn( 'unique_id', $this->type('string', ['length'=>40, 'unique'=>1]) );

		}

		// 关联表 article_tag
		$article_tag = $this->article_tag;
		if ( $article_tag->tableExists() === false) {
			$article_tag->putColumn( 'article_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20]) )  // 文章 ID 
				        ->putColumn( 'tag_id', $this->type('bigInteger', ['index'=>1 , 'length'=>20]) )
				        ->putColumn( 'unique_id', $this->type('string', ['length'=>40, 'unique'=>1]) );
		}
	}


	function create( $data ) {
		$data['article_id'] = $this->nextid();
		$rs = parent::create( $data );

		if ( isset($data['category']) ) {
			$category = is_array($data['category']) ? $data['category'] : [$data['category']];

			foreach ($category as $cid ) {
				$this->article_category->createOrUpdate([
					"article_id" => $data['article_id'],
					"category_id" => $cid,
					"unique_id"=>'DB::RAW(CONCAT(`article_id`, `category_id`))'
				]);
			}
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
		$this->dropTable();
	}

}