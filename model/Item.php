<?php
/**
 * Class Item 
 * 单品数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-08 16:13:55
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                        
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Item extends Model {


	/**
	 * 公有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	protected $media = null;

	/**
	 * 私有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	protected $mediaPrivate = null;

	/**
	 * 单品数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('item'); // 数据表名称 xpmsns_pages_item
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

	}

	/**
	 * 自定义函数 
	 */


    //  @KEEP BEGIN

    /**
     * 根据单品ID读取单品信息
     * @param string $item_id 单品信息
     */
    function itemDetail( $item_id ) {

        $qb = $this->query();
        $qb->where("item_id", "=", $item_id);
        $rows = $qb->get()->toArray();
        if ( empty( $rows) ) {
            return $rows;
        }

        $rs = current( $rows );
        $this->format($rs);

        // 计算SKU, 实际价格等信息
        $this->countItem( $rs );
        return $rs;
    }

    /**
     * 将指定数量的商品库存设定为运送中
     * @param string $goods_id 商品ID
     * @param string $item_id  单品ID
     * @param int $quantity  数量
     */
    function shipping( $item_id, $quantity ) {

        $this->updateBy("item_id", [
            "item_id"=>$item_id,
            "sum" => 'DB::RAW(sum-'.intval($quantity).')',
            "shipped_sum" => 'DB::RAW(shipped_sum+'.intval($quantity).')',
       ]);
    }

    function cancel( $item_id, $quantity ) {

        $this->updateBy("item_id", [
            "item_id"=>$item_id,
            "sum" => 'DB::RAW(sum-'.intval($quantity).')',
            "shipped_sum" => 'DB::RAW(shipped_sum+'.intval($quantity).')',
       ]);
    }

    function success( $item_id, $quantity ) {
        $this->updateBy("item_id", [
            "item_id"=>$item_id,
            "shipped_sum" => 'DB::RAW(shipped_sum-'.intval($quantity).')',
       ]);
    }


    /**
     * 读取商品的所有单品
     * @param string $goods_id 商品ID
     * @param array $goods 商品信息
     */
    function goodsItems( $goods_id, $goods = [] ) {

        $qb = $this->query();
        $qb->where("goods_id", "=", $goods_id);
        $rows = $qb->get()->toArray();
        if ( empty( $rows) ) {
            return $rows;
        }

        $copyFromGoods = ["images", "content"];
        foreach( $rows as & $rs ) {
            $this->format( $rs );

            // 处理默认值
            foreach( $copyFromGoods as $field ) {
                if ( empty($rs["$field"]) ){
                    $rs["$field"] = $goods["$field"];
                }
            }

            // 计算SKU, 实际价格等信息
            $this->countItem( $rs );
        }
        
        return $rows;
    }


    /**
     * 根据单品信息, 计算SKU、实际价格等
     * @param array &$goods 商品全量资料(涵盖单品)
     */
    function countItem( & $item ) {

        // 计算商品价格
        if( array_key_exists("price", $item) && array_key_exists("promotion", $item) ) {
            
            $PI = $this->promotion( $item["price"], $item["promotion"]);

            // 计算可售价格
            $item["price_real"] = $PI["price"];

            // 最少可用价格
            $item["price_min"] = $PI["price_min"];

            // 计算积分价格
            $item["coin"] = $PI["coin"];

            // 最多可用积分
            $item["coin_max"] = $PI["coin_max"];

        }

        // 计算SKU
        if( array_key_exists("sum", $item) && 
            array_key_exists("shipped_sum", $item) && 
            array_key_exists("available_sum", $item) ){
            $item["available_sum"] = $item["sum"] - $item["shipped_sum"];
        }

    }

    /**
     * 根据优惠方式，计算实际价格
     * @param int $price 价格(单位:分)
     * @param string $promotion  优惠名称(许可数值)
     * @param string $user 当前访问用户
     *          
     */
    function promotion( $price, $promotion, $user=null ) {

        // 优惠方式: 折扣=discount,满减=reduction,会员=vip,包邮=free-shipping,邀请=invite,分享=share
        // 销售方式: 标准=normal,闪购=flash,抢购=rush,团购=group,拼团=sharing,众筹=crowd,拍卖=auction,分期=repayment,1元购=draw
        $priceInfo = [
            "price" => $price,
            "price_min" => 0,
            "coin" => $price,
            "coin_max" => $price,
        ];

        return $priceInfo;
    }

    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 单品ID
		$this->putColumn( 'item_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 商品
		$this->putColumn( 'goods_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 名称
		$this->putColumn( 'name', $this->type("string", ["length"=>200, "index"=>true, "null"=>true]));
		// 参数
		$this->putColumn( 'params', $this->type("text", ["json"=>true, "null"=>true]));
		// 单价
		$this->putColumn( 'price', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 底价
		$this->putColumn( 'price_low', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 进价
		$this->putColumn( 'price_in', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 保价
		$this->putColumn( 'price_val', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 优惠
		$this->putColumn( 'promotion', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 付款方式
		$this->putColumn( 'payment', $this->type("text", ["json"=>true, "null"=>true]));
		// 物流
		$this->putColumn( 'shipping_ids', $this->type("text", ["json"=>true, "null"=>true]));
		// 重量
		$this->putColumn( 'weight', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 体积
		$this->putColumn( 'volume', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 总数
		$this->putColumn( 'sum', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 货运装箱总数
		$this->putColumn( 'shipped_sum', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 可售数量
		$this->putColumn( 'available_sum', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 图片
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 详情
		$this->putColumn( 'content', $this->type("text", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {

		// 格式化: 图片
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('images', $rs ) ) {
			$is_string = is_string($rs["images"]);
			$rs["images"] = $is_string ? [$rs["images"]] : $rs["images"];
			$rs["images"] = !is_array($rs["images"]) ? [] : $rs["images"];
			foreach ($rs["images"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
			if ($is_string) {
				$rs["images"] = current($rs["images"]);
			}
		}


		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"online" => [
		  			"value" => "online",
		  			"name" => "上架",
		  			"style" => "success"
		  		],
		  		"offline" => [
		  			"value" => "offline",
		  			"name" => "下架",
		  			"style" => "danger"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按单品ID查询一条单品记录
	 * @param string $item_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["item_id"],  // 单品ID 
	 *          	  $rs["goods_id"],  // 商品 
	 *                $rs["_map_goods"][$goods_id[n]]["goods_id"], // goods.goods_id
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["params"],  // 参数 
	 *          	  $rs["price"],  // 单价 
	 *          	  $rs["price_low"],  // 底价 
	 *          	  $rs["price_in"],  // 进价 
	 *          	  $rs["price_val"],  // 保价 
	 *          	  $rs["promotion"],  // 优惠 
	 *          	  $rs["payment"],  // 付款方式 
	 *          	  $rs["shipping_ids"],  // 物流 
	 *                $rs["_map_shipping"][$shipping_ids[n]]["shipping_id"], // shipping.shipping_id
	 *          	  $rs["weight"],  // 重量 
	 *          	  $rs["volume"],  // 体积 
	 *          	  $rs["sum"],  // 总数 
	 *          	  $rs["shipped_sum"],  // 货运装箱总数 
	 *          	  $rs["available_sum"],  // 可售数量 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["images"],  // 图片 
	 *          	  $rs["content"],  // 详情 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_goods"][$goods_id[n]]["created_at"], // goods.created_at
	 *                $rs["_map_goods"][$goods_id[n]]["updated_at"], // goods.updated_at
	 *                $rs["_map_goods"][$goods_id[n]]["instance"], // goods.instance
	 *                $rs["_map_goods"][$goods_id[n]]["name"], // goods.name
	 *                $rs["_map_goods"][$goods_id[n]]["slug"], // goods.slug
	 *                $rs["_map_goods"][$goods_id[n]]["tags"], // goods.tags
	 *                $rs["_map_goods"][$goods_id[n]]["category_ids"], // goods.category_ids
	 *                $rs["_map_goods"][$goods_id[n]]["recommend_ids"], // goods.recommend_ids
	 *                $rs["_map_goods"][$goods_id[n]]["summary"], // goods.summary
	 *                $rs["_map_goods"][$goods_id[n]]["cover"], // goods.cover
	 *                $rs["_map_goods"][$goods_id[n]]["images"], // goods.images
	 *                $rs["_map_goods"][$goods_id[n]]["videos"], // goods.videos
	 *                $rs["_map_goods"][$goods_id[n]]["params"], // goods.params
	 *                $rs["_map_goods"][$goods_id[n]]["content"], // goods.content
	 *                $rs["_map_goods"][$goods_id[n]]["content_faq"], // goods.content_faq
	 *                $rs["_map_goods"][$goods_id[n]]["content_serv"], // goods.content_serv
	 *                $rs["_map_goods"][$goods_id[n]]["sku_cnt"], // goods.sku_cnt
	 *                $rs["_map_goods"][$goods_id[n]]["sku_sum"], // goods.sku_sum
	 *                $rs["_map_goods"][$goods_id[n]]["shipped_sum"], // goods.shipped_sum
	 *                $rs["_map_goods"][$goods_id[n]]["available_sum"], // goods.available_sum
	 *                $rs["_map_goods"][$goods_id[n]]["lower_price"], // goods.lower_price
	 *                $rs["_map_goods"][$goods_id[n]]["sale_way"], // goods.sale_way
	 *                $rs["_map_goods"][$goods_id[n]]["opened_at"], // goods.opened_at
	 *                $rs["_map_goods"][$goods_id[n]]["closed_at"], // goods.closed_at
	 *                $rs["_map_goods"][$goods_id[n]]["pay_duration"], // goods.pay_duration
	 *                $rs["_map_goods"][$goods_id[n]]["status"], // goods.status
	 *                $rs["_map_goods"][$goods_id[n]]["events"], // goods.events
	 *                $rs["_map_shipping"][$shipping_ids[n]]["created_at"], // shipping.created_at
	 *                $rs["_map_shipping"][$shipping_ids[n]]["updated_at"], // shipping.updated_at
	 *                $rs["_map_shipping"][$shipping_ids[n]]["company"], // shipping.company
	 *                $rs["_map_shipping"][$shipping_ids[n]]["name"], // shipping.name
	 *                $rs["_map_shipping"][$shipping_ids[n]]["products"], // shipping.products
	 *                $rs["_map_shipping"][$shipping_ids[n]]["scope"], // shipping.scope
	 *                $rs["_map_shipping"][$shipping_ids[n]]["formula"], // shipping.formula
	 *                $rs["_map_shipping"][$shipping_ids[n]]["api"], // shipping.api
	 */
	public function getByItemId( $item_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "item.item_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_item as item", "{none}")->query();
  		$qb->where('item_id', '=', $item_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$goods_ids = []; // 读取 inWhere goods 数据
		$goods_ids = array_merge($goods_ids, is_array($rs["goods_id"]) ? $rs["goods_id"] : [$rs["goods_id"]]);
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_ids"]) ? $rs["shipping_ids"] : [$rs["shipping_ids"]]);

 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$rs["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$rs["_map_shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
		}

		return $rs;
	}

		

	/**
	 * 按单品ID查询一组单品记录
	 * @param array   $item_ids 唯一主键数组 ["$item_id1","$item_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 单品记录MAP {"item_id1":{"key":"value",...}...}
	 */
	public function getInByItemId($item_ids, $select=["item.item_id","goods.name","item.name","item.available_sum","item.created_at","item.updated_at","item.status"], $order=["item.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "item.item_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_item as item", "{none}")->query();
  		$qb->whereIn('item.item_id', $item_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$goods_ids = []; // 读取 inWhere goods 数据
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['item_id']] = $rs;
			
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_id"]) ? $rs["goods_id"] : [$rs["goods_id"]]);
 			// for inWhere shipping
			$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_ids"]) ? $rs["shipping_ids"] : [$rs["shipping_ids"]]);
		}

 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$map["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$map["_map_shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按单品ID保存单品记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByItemId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "item.item_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("item_id", $data, ["item_id"], ['_id', 'item_id']);
		return $this->getByItemId( $rs['item_id'], $select );
	}

	/**
	 * 根据单品ID上传图片。
	 * @param string $item_id 单品ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByItemId($item_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('item_id', $item_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('item_id', ["item_id"=>$item_id, "images"=>$paths] );
		}

		return $fs;
	}


	/**
	 * 添加单品记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["item_id"]) ) { 
			$data["item_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排单品记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 单品记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["item.item_id","goods.name","item.name","item.available_sum","item.created_at","item.updated_at","item.status"], $order=["item.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "item.item_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_item as item", "{none}")->query();
  

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$goods_ids = []; // 读取 inWhere goods 数据
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_id"]) ? $rs["goods_id"] : [$rs["goods_id"]]);
 			// for inWhere shipping
			$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_ids"]) ? $rs["shipping_ids"] : [$rs["shipping_ids"]]);
		}

 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$data["_map_goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$data["_map_shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索单品记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["item.item_id","goods.name","item.name","item.available_sum","item.created_at","item.updated_at","item.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["item_id"] 按单品ID查询 ( = )
	 *			      $query["name"] 按名称查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["price"] 按单价查询 ( > )
	 *			      $query["price"] 按单价查询 ( < )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 单品记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["item_id"],  // 单品ID 
	 *               	["goods_id"],  // 商品 
	 *               	["goods"][$goods_id[n]]["goods_id"], // goods.goods_id
	 *               	["name"],  // 名称 
	 *               	["params"],  // 参数 
	 *               	["price"],  // 单价 
	 *               	["price_low"],  // 底价 
	 *               	["price_in"],  // 进价 
	 *               	["price_val"],  // 保价 
	 *               	["promotion"],  // 优惠 
	 *               	["payment"],  // 付款方式 
	 *               	["shipping_ids"],  // 物流 
	 *               	["shipping"][$shipping_ids[n]]["shipping_id"], // shipping.shipping_id
	 *               	["weight"],  // 重量 
	 *               	["volume"],  // 体积 
	 *               	["sum"],  // 总数 
	 *               	["shipped_sum"],  // 货运装箱总数 
	 *               	["available_sum"],  // 可售数量 
	 *               	["status"],  // 状态 
	 *               	["images"],  // 图片 
	 *               	["content"],  // 详情 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["goods"][$goods_id[n]]["created_at"], // goods.created_at
	 *               	["goods"][$goods_id[n]]["updated_at"], // goods.updated_at
	 *               	["goods"][$goods_id[n]]["instance"], // goods.instance
	 *               	["goods"][$goods_id[n]]["name"], // goods.name
	 *               	["goods"][$goods_id[n]]["slug"], // goods.slug
	 *               	["goods"][$goods_id[n]]["tags"], // goods.tags
	 *               	["goods"][$goods_id[n]]["category_ids"], // goods.category_ids
	 *               	["goods"][$goods_id[n]]["recommend_ids"], // goods.recommend_ids
	 *               	["goods"][$goods_id[n]]["summary"], // goods.summary
	 *               	["goods"][$goods_id[n]]["cover"], // goods.cover
	 *               	["goods"][$goods_id[n]]["images"], // goods.images
	 *               	["goods"][$goods_id[n]]["videos"], // goods.videos
	 *               	["goods"][$goods_id[n]]["params"], // goods.params
	 *               	["goods"][$goods_id[n]]["content"], // goods.content
	 *               	["goods"][$goods_id[n]]["content_faq"], // goods.content_faq
	 *               	["goods"][$goods_id[n]]["content_serv"], // goods.content_serv
	 *               	["goods"][$goods_id[n]]["sku_cnt"], // goods.sku_cnt
	 *               	["goods"][$goods_id[n]]["sku_sum"], // goods.sku_sum
	 *               	["goods"][$goods_id[n]]["shipped_sum"], // goods.shipped_sum
	 *               	["goods"][$goods_id[n]]["available_sum"], // goods.available_sum
	 *               	["goods"][$goods_id[n]]["lower_price"], // goods.lower_price
	 *               	["goods"][$goods_id[n]]["sale_way"], // goods.sale_way
	 *               	["goods"][$goods_id[n]]["opened_at"], // goods.opened_at
	 *               	["goods"][$goods_id[n]]["closed_at"], // goods.closed_at
	 *               	["goods"][$goods_id[n]]["pay_duration"], // goods.pay_duration
	 *               	["goods"][$goods_id[n]]["status"], // goods.status
	 *               	["goods"][$goods_id[n]]["events"], // goods.events
	 *               	["shipping"][$shipping_ids[n]]["created_at"], // shipping.created_at
	 *               	["shipping"][$shipping_ids[n]]["updated_at"], // shipping.updated_at
	 *               	["shipping"][$shipping_ids[n]]["company"], // shipping.company
	 *               	["shipping"][$shipping_ids[n]]["name"], // shipping.name
	 *               	["shipping"][$shipping_ids[n]]["products"], // shipping.products
	 *               	["shipping"][$shipping_ids[n]]["scope"], // shipping.scope
	 *               	["shipping"][$shipping_ids[n]]["formula"], // shipping.formula
	 *               	["shipping"][$shipping_ids[n]]["api"], // shipping.api
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["item.item_id","goods.name","item.name","item.available_sum","item.created_at","item.updated_at","item.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "item.item_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_item as item", "{none}")->query();
  
		// 按关键词查找
		if ( array_key_exists("keywords", $query) && !empty($query["keywords"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("item.item_id", "like", "%{$query['keywords']}%");
				$qb->orWhere("item.name","like", "%{$query['keywords']}%");
			});
		}


		// 按单品ID查询 (=)  
		if ( array_key_exists("item_id", $query) &&!empty($query['item_id']) ) {
			$qb->where("item.item_id", '=', "{$query['item_id']}" );
		}
		  
		// 按名称查询 (=)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("item.name", '=', "{$query['name']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("item.status", '=', "{$query['status']}" );
		}
		  
		// 按单价查询 (>)  
		if ( array_key_exists("price", $query) &&!empty($query['price']) ) {
			$qb->where("item.price", '>', "{$query['price']}" );
		}
		  
		// 按单价查询 (<)  
		if ( array_key_exists("price", $query) &&!empty($query['price']) ) {
			$qb->where("item.price", '<', "{$query['price']}" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("item.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("item.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$items = $qb->select( $select )->pgArray($perpage, ['item._id'], 'page', $page);

 		$goods_ids = []; // 读取 inWhere goods 数据
 		$shipping_ids = []; // 读取 inWhere shipping 数据
		foreach ($items['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere goods
			$goods_ids = array_merge($goods_ids, is_array($rs["goods_id"]) ? $rs["goods_id"] : [$rs["goods_id"]]);
 			// for inWhere shipping
			$shipping_ids = array_merge($shipping_ids, is_array($rs["shipping_ids"]) ? $rs["shipping_ids"] : [$rs["shipping_ids"]]);
		}

 		// 读取 inWhere goods 数据
		if ( !empty($inwhereSelect["goods"]) && method_exists("\\Xpmsns\\Pages\\Model\\Goods", 'getInByGoodsId') ) {
			$goods_ids = array_unique($goods_ids);
			$selectFields = $inwhereSelect["goods"];
			$items["goods"] = (new \Xpmsns\Pages\Model\Goods)->getInByGoodsId($goods_ids, $selectFields);
		}
 		// 读取 inWhere shipping 数据
		if ( !empty($inwhereSelect["shipping"]) && method_exists("\\Xpmsns\\Pages\\Model\\Shipping", 'getInByShippingId') ) {
			$shipping_ids = array_unique($shipping_ids);
			$selectFields = $inwhereSelect["shipping"];
			$items["shipping"] = (new \Xpmsns\Pages\Model\Shipping)->getInByShippingId($shipping_ids, $selectFields);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$items['_sql'] = $qb->getSql();
			$items['query'] = $query;
		}

		return $items;
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
				$select[$idx] = "item." .$select[$idx];
				continue;
			}
			
			// 连接商品 (goods as goods )
			if ( strpos( $fd, "goods." ) === 0 || strpos("goods.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["goods"][] = trim($arr[1]);
				$inwhereSelect["goods"][] = "goods_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "item.goods_id");
				}
			}
			
			// 连接物流 (shipping as shipping )
			if ( strpos( $fd, "shipping." ) === 0 || strpos("shipping.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["shipping"][] = trim($arr[1]);
				$inwhereSelect["shipping"][] = "shipping_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "item.shipping_ids");
				}
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
			"item_id",  // 单品ID
			"goods_id",  // 商品
			"name",  // 名称
			"params",  // 参数
			"price",  // 单价
			"price_low",  // 底价
			"price_in",  // 进价
			"price_val",  // 保价
			"promotion",  // 优惠
			"payment",  // 付款方式
			"shipping_ids",  // 物流
			"weight",  // 重量
			"volume",  // 体积
			"sum",  // 总数
			"shipped_sum",  // 货运装箱总数
			"available_sum",  // 可售数量
			"status",  // 状态
			"images",  // 图片
			"content",  // 详情
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>