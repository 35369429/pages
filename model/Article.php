<?php
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
class ArticleModel extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct();
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
			
			$this->putColumn( 'article_id', $this->type('bigInteger', ['length'=>20]) )  // 文章 ID  ( 同 _id )
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
				 ->putColumn( 'content', $this->type('longText',  []) )  // 已发布正文
				 ->putColumn( 'ap_content', $this->type('longText',  []) )  // 小程序正文
				 ->putColumn( 'draft', $this->type('longText',  []) )  // 待发布正文(草稿)
				 ->putColumn( 'ap_draft', $this->type('longText',  []) )  // 小程序待发布正文(草稿)
				 ->putColumn( 'history', $this->type('longText',  []) )  // 发布状态的正文备份
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

			$tag = App::M("Tag");
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


		// $rs['category'] = $data['category'];
		// $rs['tag'] => $data['tag'];

		return $rs;
		
	}


	function getCategories( $article_id ) {

		$resp = App::M('Category')->query()
		             ->rightJoin('article_category', 'article_category.category_id', '=', 'category.category_id')
		             ->where( "article_category.article_id", '=', $article_id )
		             ->select('Category.*')
		             ->limit( 50 );
		Utils::out( $resp );

	}

	function __clear() {
		Utils::getTab('article_category', "mina_pages_")->dropTable();
		Utils::getTab('article_tag', "mina_pages_")->dropTable();
		$this->dropTable();
	}

}