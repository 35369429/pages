<?php
/**
 * Class Tag 
 * 标签数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-27 17:19:40
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Mina\Cache\Redis as Cache;
use \Xpmse\Loader\App as App;
use \Xpmse\Job;


class Tag extends Model {




    /**
     * 数据缓存对象
     */
    protected $cache = null;

	/**
	 * 标签数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
        $this->table('tag'); // 数据表名称 xpmsns_pages_tag
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_pages_tag:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);


       
	}

	/**
	 * 自定义函数 
	 */


    // @KEEP BEGIN

    /**
	 * 插入标签，并返回标签 ID 列表 (兼容旧系统)
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
    
    // @KEEP END

	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 标签ID
		$this->putColumn( 'tag_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 名称
		$this->putColumn( 'name', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 参数
		$this->putColumn( 'param', $this->type("string", ["length"=>600, "json"=>true, "null"=>true]));
		// 文章数
		$this->putColumn( 'article_cnt', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 图集数
		$this->putColumn( 'album_cnt', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 活动数
		$this->putColumn( 'event_cnt', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 商品数
		$this->putColumn( 'goods_cnt', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 提问数
		$this->putColumn( 'question_cnt', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按标签ID查询一条标签记录
	 * @param string $tag_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["tag_id"],  // 标签ID 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["article_cnt"],  // 文章数 
	 *          	  $rs["album_cnt"],  // 图集数 
	 *          	  $rs["event_cnt"],  // 活动数 
	 *          	  $rs["goods_cnt"],  // 商品数 
	 *          	  $rs["question_cnt"],  // 提问数 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getByTagId( $tag_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_pages_tag as tag", "{none}")->query();
		$qb->where('tag.tag_id', '=', $tag_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);



		return $rs;
	}

		

	/**
	 * 按标签ID查询一组标签记录
	 * @param array   $tag_ids 唯一主键数组 ["$tag_id1","$tag_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 标签记录MAP {"tag_id1":{"key":"value",...}...}
	 */
	public function getInByTagId($tag_ids, $select=["tag.tag_id","tag.name","tag.article_cnt","tag.album_cnt","tag.event_cnt","tag.goods_cnt","tag.question_cnt","tag.created_at","tag.updated_at"], $order=["tag.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('tag_id', $tag_ids);;
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['tag_id']] = $rs;
			
		}



		return $map;
	}


	/**
	 * 按标签ID保存标签记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByTagId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("tag_id", $data, ["tag_id", "name"], ['_id', 'tag_id']);
		return $this->getByTagId( $rs['tag_id'], $select );
	}
	
	/**
	 * 按名称查询一条标签记录
	 * @param string $name 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["tag_id"],  // 标签ID 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["param"],  // 参数 
	 *          	  $rs["article_cnt"],  // 文章数 
	 *          	  $rs["album_cnt"],  // 图集数 
	 *          	  $rs["event_cnt"],  // 活动数 
	 *          	  $rs["goods_cnt"],  // 商品数 
	 *          	  $rs["question_cnt"],  // 提问数 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getByName( $name, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_pages_tag as tag", "{none}")->query();
		$qb->where('tag.name', '=', $name );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);



		return $rs;
	}

	

	/**
	 * 按名称查询一组标签记录
	 * @param array   $names 唯一主键数组 ["$name1","$name2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 标签记录MAP {"name1":{"key":"value",...}...}
	 */
	public function getInByName($names, $select=["tag.tag_id","tag.name","tag.article_cnt","tag.album_cnt","tag.event_cnt","tag.goods_cnt","tag.question_cnt","tag.created_at","tag.updated_at"], $order=["tag.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('name', $names);;
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['name']] = $rs;
			
		}



		return $map;
	}


	/**
	 * 按名称保存标签记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByName( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("name", $data, ["tag_id", "name"], ['_id', 'tag_id']);
		return $this->getByTagId( $rs['tag_id'], $select );
	}


	/**
	 * 添加标签记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["tag_id"]) ) { 
			$data["tag_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排标签记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 标签记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["tag.tag_id","tag.name","tag.article_cnt","tag.album_cnt","tag.event_cnt","tag.goods_cnt","tag.question_cnt","tag.created_at","tag.updated_at"], $order=["tag.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();


		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


		foreach ($data as & $rs ) {
			$this->format($rs);
			
		}


		return $data;
	
	}


	/**
	 * 按条件检索标签记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["tag.tag_id","tag.name","tag.article_cnt","tag.album_cnt","tag.event_cnt","tag.goods_cnt","tag.question_cnt","tag.created_at","tag.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["tag_id"] 按标签ID查询 ( = )
	 *			      $query["name"] 按名称查询 ( LIKE )
	 *			      $query["created_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["updated_desc"]  按更新时间倒序 DESC 排序
	 *			      $query["article_desc"]  按文章数量倒序 DESC 排序
	 *			      $query["article_asc"]  按文章数量正序 ASC 排序
	 *			      $query["album_desc"]  按图集数量倒序 DESC 排序
	 *			      $query["album_asc"]  按图集数量正序 ASC 排序
	 *			      $query["event_desc"]  按活动数量倒序 DESC 排序
	 *			      $query["event_asc"]  按活动数量正序 DESC 排序
	 *			      $query["goods_desc"]  按商品数量倒序 DESC 排序
	 *			      $query["goods_asc"]  按商品数量正序 ASC 排序
	 *			      $query["question_desc"]  按提问数量倒序 DESC 排序
	 *			      $query["question_asc"]  按提问数量正序 ASC 排序
	 *           
	 * @return array 标签记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["tag_id"],  // 标签ID 
	 *               	["name"],  // 名称 
	 *               	["param"],  // 参数 
	 *               	["article_cnt"],  // 文章数 
	 *               	["album_cnt"],  // 图集数 
	 *               	["event_cnt"],  // 活动数 
	 *               	["goods_cnt"],  // 商品数 
	 *               	["question_cnt"],  // 提问数 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["tag.tag_id","tag.name","tag.article_cnt","tag.album_cnt","tag.event_cnt","tag.goods_cnt","tag.question_cnt","tag.created_at","tag.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "tag.tag_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("tag.tag_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("tag.name","like", "%{$query['keyword']}%");
			});
		}


		// 按标签ID查询 (=)  
		if ( array_key_exists("tag_id", $query) &&!empty($query['tag_id']) ) {
			$qb->where("tag.tag_id", '=', "{$query['tag_id']}" );
		}
		  
		// 按名称查询 (LIKE)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("tag.name", 'like', "%{$query['name']}%" );
		}
		  

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("created_desc", $query) &&!empty($query['created_desc']) ) {
			$qb->orderBy("tag.created_at", "desc");
		}

		// 按更新时间倒序 DESC 排序
		if ( array_key_exists("updated_desc", $query) &&!empty($query['updated_desc']) ) {
			$qb->orderBy("tag.updated_at", "desc");
		}

		// 按文章数量倒序 DESC 排序
		if ( array_key_exists("article_desc", $query) &&!empty($query['article_desc']) ) {
			$qb->orderBy("tag.article_cnt", "desc");
		}

		// 按文章数量正序 ASC 排序
		if ( array_key_exists("article_asc", $query) &&!empty($query['article_asc']) ) {
			$qb->orderBy("tag.article_cnt", "asc");
		}

		// 按图集数量倒序 DESC 排序
		if ( array_key_exists("album_desc", $query) &&!empty($query['album_desc']) ) {
			$qb->orderBy("tag.album_cnt", "desc");
		}

		// 按图集数量正序 ASC 排序
		if ( array_key_exists("album_asc", $query) &&!empty($query['album_asc']) ) {
			$qb->orderBy("tag.album_cnt", "asc");
		}

		// 按活动数量倒序 DESC 排序
		if ( array_key_exists("event_desc", $query) &&!empty($query['event_desc']) ) {
			$qb->orderBy("tag.event_cnt", "desc");
		}

		// 按活动数量正序 DESC 排序
		if ( array_key_exists("event_asc", $query) &&!empty($query['event_asc']) ) {
			$qb->orderBy("tag.event_cnt", "desc");
		}

		// 按商品数量倒序 DESC 排序
		if ( array_key_exists("goods_desc", $query) &&!empty($query['goods_desc']) ) {
			$qb->orderBy("tag.goods_cnt", "desc");
		}

		// 按商品数量正序 ASC 排序
		if ( array_key_exists("goods_asc", $query) &&!empty($query['goods_asc']) ) {
			$qb->orderBy("tag.goods_cnt", "asc");
		}

		// 按提问数量倒序 DESC 排序
		if ( array_key_exists("question_desc", $query) &&!empty($query['question_desc']) ) {
			$qb->orderBy("tag.question_cnt", "desc");
		}

		// 按提问数量正序 ASC 排序
		if ( array_key_exists("question_asc", $query) &&!empty($query['question_asc']) ) {
			$qb->orderBy("tag.question_cnt", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$tags = $qb->select( $select )->pgArray($perpage, ['tag._id'], 'page', $page);

		foreach ($tags['data'] as & $rs ) {
			$this->format($rs);
			
		}

	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$tags['_sql'] = $qb->getSql();
			$tags['query'] = $query;
		}

		return $tags;
	}

	/**
	 * 格式化读取字段
	 * @param  array $select 选中字段
	 * @return array $inWhere 读取字段
	 */
	public function formatSelect( & $select ) {
		// 过滤 inWhere 查询字段
		$inwhereSelect = []; $linkSelect = [];
		foreach ($select as $idx=>$fd ) {
			
			// 添加本表前缀
			if ( !strpos( $fd, ".")  ) {
				$select[$idx] = "tag." .$select[$idx];
				continue;
			}
		}

		// filter 查询字段
		foreach ($inwhereSelect as & $iws ) {
			if ( is_array($iws) ) {
				$iws = array_unique(array_filter($iws));
			}
		}

		$select = array_unique(array_merge($linkSelect, $select));
		return $inwhereSelect;
	}

	/**
	 * 返回所有字段
	 * @return array 字段清单
	 */
	public static function getFields() {
		return [
			"tag_id",  // 标签ID
			"name",  // 名称
			"param",  // 参数
			"article_cnt",  // 文章数
			"album_cnt",  // 图集数
			"event_cnt",  // 活动数
			"goods_cnt",  // 商品数
			"question_cnt",  // 提问数
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>