<?php
/**
 * Class Shipping 
 * 物流数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 23:10:19
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
           
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Shipping extends Model {




	/**
	 * 物流数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('shipping'); // 数据表名称 xpmsns_pages_shipping

	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 物流ID
		$this->putColumn( 'shipping_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 物流公司
		$this->putColumn( 'company', $this->type("string", ["length"=>100, "index"=>true, "null"=>true]));
		// 公司简称
		$this->putColumn( 'name', $this->type("string", ["length"=>100, "index"=>true, "null"=>true]));
		// 物流产品
		$this->putColumn( 'products', $this->type("text", ["json"=>true, "null"=>true]));
		// 配送范围
		$this->putColumn( 'scope', $this->type("text", ["json"=>true, "null"=>true]));
		// 运费公式
		$this->putColumn( 'formula', $this->type("text", ["json"=>true, "null"=>true]));
		// 物流API
		$this->putColumn( 'api', $this->type("text", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按物流ID查询一条物流记录
	 * @param string $shipping_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["shipping_id"],  // 物流ID 
	 *          	  $rs["company"],  // 物流公司 
	 *          	  $rs["name"],  // 公司简称 
	 *          	  $rs["products"],  // 物流产品 
	 *          	  $rs["scope"],  // 配送范围 
	 *          	  $rs["formula"],  // 运费公式 
	 *          	  $rs["api"],  // 物流API 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getByShippingId( $shipping_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "shipping.shipping_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_pages_shipping as shipping", "{none}")->query();
		$qb->where('shipping_id', '=', $shipping_id );
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
	 * 按物流ID查询一组物流记录
	 * @param array   $shipping_ids 唯一主键数组 ["$shipping_id1","$shipping_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 物流记录MAP {"shipping_id1":{"key":"value",...}...}
	 */
	public function getInByShippingId($shipping_ids, $select=["shipping.shipping_id","shipping.name","shipping.company","shipping.created_at","shipping.updated_at"], $order=["shipping.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "shipping.shipping_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('shipping_id', $shipping_ids);;
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['shipping_id']] = $rs;
			
		}



		return $map;
	}


	/**
	 * 按物流ID保存物流记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByShippingId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "shipping.shipping_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("shipping_id", $data, ["shipping_id"], ['_id', 'shipping_id']);
		return $this->getByShippingId( $rs['shipping_id'], $select );
	}


	/**
	 * 添加物流记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["shipping_id"]) ) { 
			$data["shipping_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排物流记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 物流记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["shipping.shipping_id","shipping.name","shipping.company","shipping.created_at","shipping.updated_at"], $order=["shipping.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "shipping.shipping_id");
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
	 * 按条件检索物流记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["shipping.shipping_id","shipping.name","shipping.company","shipping.created_at","shipping.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["shipping_id"] 按物流ID查询 ( = )
	 *			      $query["company"] 按物流公司查询 ( LIKE )
	 *			      $query["name"] 按公司简称查询 ( LIKE )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 物流记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["shipping_id"],  // 物流ID 
	 *               	["company"],  // 物流公司 
	 *               	["name"],  // 公司简称 
	 *               	["products"],  // 物流产品 
	 *               	["scope"],  // 配送范围 
	 *               	["formula"],  // 运费公式 
	 *               	["api"],  // 物流API 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["shipping.shipping_id","shipping.name","shipping.company","shipping.created_at","shipping.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "shipping.shipping_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("shipping.shipping_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("shipping.company","like", "%{$query['keyword']}%");
				$qb->orWhere("shipping.name","like", "%{$query['keyword']}%");
			});
		}


		// 按物流ID查询 (=)  
		if ( array_key_exists("shipping_id", $query) &&!empty($query['shipping_id']) ) {
			$qb->where("shipping.shipping_id", '=', "{$query['shipping_id']}" );
		}
		  
		// 按物流公司查询 (LIKE)  
		if ( array_key_exists("company", $query) &&!empty($query['company']) ) {
			$qb->where("shipping.company", 'like', "%{$query['company']}%" );
		}
		  
		// 按公司简称查询 (LIKE)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("shipping.name", 'like', "%{$query['name']}%" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("shipping.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("shipping.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$shippings = $qb->select( $select )->pgArray($perpage, ['shipping._id'], 'page', $page);

		foreach ($shippings['data'] as & $rs ) {
			$this->format($rs);
			
		}

	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$shippings['_sql'] = $qb->getSql();
			$shippings['query'] = $query;
		}

		return $shippings;
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
				$select[$idx] = "shipping." .$select[$idx];
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
			"shipping_id",  // 物流ID
			"company",  // 物流公司
			"name",  // 公司简称
			"products",  // 物流产品
			"scope",  // 配送范围
			"formula",  // 运费公式
			"api",  // 物流API
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>