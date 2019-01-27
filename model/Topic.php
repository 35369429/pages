<?php
/**
 * Class Topic 
 * 话题数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-27 21:12:54
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


class Topic extends Model {




    /**
     * 数据缓存对象
     */
    protected $cache = null;

	/**
	 * 话题数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
        $this->table('topic'); // 数据表名称 xpmsns_pages_topic
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_pages_topic:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);


       
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 话题ID
		$this->putColumn( 'topic_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
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
	 * 按话题ID查询一条话题记录
	 * @param string $topic_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["topic_id"],  // 话题ID 
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
	public function getByTopicId( $topic_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "topic.topic_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_pages_topic as topic", "{none}")->query();
		$qb->where('topic.topic_id', '=', $topic_id );
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
	 * 按话题ID查询一组话题记录
	 * @param array   $topic_ids 唯一主键数组 ["$topic_id1","$topic_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 话题记录MAP {"topic_id1":{"key":"value",...}...}
	 */
	public function getInByTopicId($topic_ids, $select=["topic.topic_id","topic.name","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"], $order=["topic.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "topic.topic_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('topic_id', $topic_ids);;
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['topic_id']] = $rs;
			
		}



		return $map;
	}


	/**
	 * 按话题ID保存话题记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByTopicId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "topic.topic_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("topic_id", $data, ["topic_id", "name"], ['_id', 'topic_id']);
		return $this->getByTopicId( $rs['topic_id'], $select );
	}
	
	/**
	 * 按名称查询一条话题记录
	 * @param string $name 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["topic_id"],  // 话题ID 
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
		array_push($select, "topic.topic_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_pages_topic as topic", "{none}")->query();
		$qb->where('topic.name', '=', $name );
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
	 * 按名称查询一组话题记录
	 * @param array   $names 唯一主键数组 ["$name1","$name2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 话题记录MAP {"name1":{"key":"value",...}...}
	 */
	public function getInByName($names, $select=["topic.topic_id","topic.name","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"], $order=["topic.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "topic.topic_id");
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
	 * 按名称保存话题记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByName( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "topic.topic_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("name", $data, ["topic_id", "name"], ['_id', 'topic_id']);
		return $this->getByTopicId( $rs['topic_id'], $select );
	}


	/**
	 * 添加话题记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["topic_id"]) ) { 
			$data["topic_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排话题记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 话题记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["topic.topic_id","topic.name","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"], $order=["topic.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "topic.topic_id");
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
	 * 按条件检索话题记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["topic.topic_id","topic.name","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["topic_id"] 按话题ID查询 ( = )
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
	 * @return array 话题记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["topic_id"],  // 话题ID 
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

		$select = empty($query['select']) ? ["topic.topic_id","topic.name","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "topic.topic_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("topic.topic_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("topic.name","like", "%{$query['keyword']}%");
			});
		}


		// 按话题ID查询 (=)  
		if ( array_key_exists("topic_id", $query) &&!empty($query['topic_id']) ) {
			$qb->where("topic.topic_id", '=', "{$query['topic_id']}" );
		}
		  
		// 按名称查询 (LIKE)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("topic.name", 'like', "%{$query['name']}%" );
		}
		  

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("created_desc", $query) &&!empty($query['created_desc']) ) {
			$qb->orderBy("topic.created_at", "desc");
		}

		// 按更新时间倒序 DESC 排序
		if ( array_key_exists("updated_desc", $query) &&!empty($query['updated_desc']) ) {
			$qb->orderBy("topic.updated_at", "desc");
		}

		// 按文章数量倒序 DESC 排序
		if ( array_key_exists("article_desc", $query) &&!empty($query['article_desc']) ) {
			$qb->orderBy("topic.article_cnt", "desc");
		}

		// 按文章数量正序 ASC 排序
		if ( array_key_exists("article_asc", $query) &&!empty($query['article_asc']) ) {
			$qb->orderBy("topic.article_cnt", "asc");
		}

		// 按图集数量倒序 DESC 排序
		if ( array_key_exists("album_desc", $query) &&!empty($query['album_desc']) ) {
			$qb->orderBy("topic.album_cnt", "desc");
		}

		// 按图集数量正序 ASC 排序
		if ( array_key_exists("album_asc", $query) &&!empty($query['album_asc']) ) {
			$qb->orderBy("topic.album_cnt", "asc");
		}

		// 按活动数量倒序 DESC 排序
		if ( array_key_exists("event_desc", $query) &&!empty($query['event_desc']) ) {
			$qb->orderBy("topic.event_cnt", "desc");
		}

		// 按活动数量正序 DESC 排序
		if ( array_key_exists("event_asc", $query) &&!empty($query['event_asc']) ) {
			$qb->orderBy("topic.event_cnt", "desc");
		}

		// 按商品数量倒序 DESC 排序
		if ( array_key_exists("goods_desc", $query) &&!empty($query['goods_desc']) ) {
			$qb->orderBy("topic.goods_cnt", "desc");
		}

		// 按商品数量正序 ASC 排序
		if ( array_key_exists("goods_asc", $query) &&!empty($query['goods_asc']) ) {
			$qb->orderBy("topic.goods_cnt", "asc");
		}

		// 按提问数量倒序 DESC 排序
		if ( array_key_exists("question_desc", $query) &&!empty($query['question_desc']) ) {
			$qb->orderBy("topic.question_cnt", "desc");
		}

		// 按提问数量正序 ASC 排序
		if ( array_key_exists("question_asc", $query) &&!empty($query['question_asc']) ) {
			$qb->orderBy("topic.question_cnt", "asc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$topics = $qb->select( $select )->pgArray($perpage, ['topic._id'], 'page', $page);

		foreach ($topics['data'] as & $rs ) {
			$this->format($rs);
			
		}

	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$topics['_sql'] = $qb->getSql();
			$topics['query'] = $query;
		}

		return $topics;
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
				$select[$idx] = "topic." .$select[$idx];
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
			"topic_id",  // 话题ID
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