<?php
/**
 * Class Goods 
 * 商品数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-27 19:50:19
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
                                  
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Goods extends Model {


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
	 * 商品数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
		$this->table('goods'); // 数据表名称 xpmsns_pages_goods
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    
    /**
     * 根据ID读取商品全量信息
     * @param string $goods_id 商品ID
     */
    function getGoodsDetail( $goods_id ) {

        $goods = $this->getByGoodsId( $goods_id );
        if ( empty($goods) ) {
            throw new Excp("商品信息不存在", 404, ["goods_id"=>$goods_id]);
        }

        $it = new Item();
        $items = $it->goodsItems( $goods_id, $goods );
        $goods["items"] = $items;

        $this->countGoods($goods);
        return $goods;
    }


    /**
     * 检索商品并返回商品全量信息
     */
    function searchGoods( $query ) {

        $goods = $this->search( $query );
        if ( empty($goods["data"]) ) {
            return $goods;
        }

        $itemMap =[];
        $goods_ids = array_unique( array_column($goods["data"], "goods_id") );
        if ( !empty($goods_ids) ) {
            // 读取 Items
            $it = new Item();
            $items = $it->query()
                        ->whereIn("goods_id", $goods_ids)
                        ->select([
                            "item_id","goods_id","name","params","price","price_low","price_val","promotion","payment",
                            "shipping_ids","weight","volume","sum","shipped_sum","available_sum","status","images",
                        ])
                        ->get()
                        ->toArray();
            
            foreach( $items as & $rs ) {
                $it->countItem($rs);
                $itemMap["{$rs['goods_id']}"][] = $rs;
            }
        }

        foreach( $goods["data"] as &$rs ) {
            $rs["items"] = $itemMap["{$rs['goods_id']}"];
            $this->countGoods($rs);
        }
        
        return $goods;
    }


    /**
     * 根据商品单品信息, 计算SKU、最低价、商品描述等信息
     * @param array &$goods 商品全量资料(涵盖单品)
     */
    function countGoods( & $goods ) {

        if ( !is_array($goods) ) {
            return;
        }

        if (empty($goods["items"])  || !is_array($goods["items"]) ) {
            $goods["lower_price"] = intval($goods["lower_price"]);
            $goods["lower_price_min"] = 0;
            $goods["lower_coin"] = intval($goods["lower_price"]);
            $goods["lower_coin_max"] = intval($goods["lower_price"]);
            $goods["available_sum"] = intval( $goods["sku_sum"]  ) - intval( $goods["shipped_sum"]  ) ;
            return;
        }

        $items = & $goods["items"];


        // 计算SKU数量
        if ( array_key_exists("sku_cnt", $goods) ) {
            $goods["sku_cnt"] = count( $items );
        }

        // 计算单品总数(SKU合)
        if ( array_key_exists("sku_sum", $goods) ) {
            $itemSum = array_column( $items, "sum");
            $goods["sku_sum"] = array_sum( $itemSum );
        }

        // 计算货运装箱总数()
        if ( array_key_exists("shipped_sum", $goods) ) {
            $itemShippedSum = array_column( $items, "shipped_sum");
            $goods["shipped_sum"] = array_sum( $itemShippedSum );
        }

        // 计算可售总数
        if ( array_key_exists("available_sum", $goods) ) {
            $itemAvailableSum = array_column( $items, "available_sum");
            $goods["available_sum"] = array_sum( $itemAvailableSum );
        }

        // 计算所有单品最低单价
        if ( array_key_exists("lower_price", $goods) ) {

            $price = array_column( $items, "price");
            $goods["lower_price"] = min($price);

            $price_min = array_column( $items, "price_min");
            $goods["lower_price_min"] = min($price_min);
            
            $coin  = array_column( $items, "coin");
            $goods["lower_coin"] = min($coin);

            $coin_max  = array_column( $items, "coin_max");
            $goods["lower_coin_max"] = min($coin_max);

        }

    }


    /**
     * 将指定数量的商品库存设定为运送中
     * @param string $goods_id 商品ID
     * @param string $item_id  单品ID
     * @param int $quantity  数量
     */
    function shipping( $goods_id, $item_id, $quantity ) {
        if ( empty($item_id) ) {
           $this->updateBy("goods_id", [
                "goods_id"=>$goods_id,
                "sku_sum" => 'DB::RAW(sku_sum-'.intval($quantity).')',
                "shipped_sum" => 'DB::RAW(shipped_sum+'.intval($quantity).')',
           ]);
        } else {
            $it = new Item;
            $it->shipping( $item_id, $quantity);
        }
    }

    function cancel( $goods_id, $item_id, $quantity ) {
        if ( empty($item_id) ) {
            $this->updateBy("goods_id", [
                 "goods_id"=>$goods_id,
                 "sku_sum" => 'DB::RAW(sku_sum+'.intval($quantity).')',
                 "shipped_sum" => 'DB::RAW(shipped_sum-'.intval($quantity).')',
            ]);
         } else {
             $it = new Item;
             $it->cancel( $item_id, $quantity);
         }
    }

    function success( $goods_id, $item_id, $quantity ) {
        if ( empty($item_id) ) {
            $this->updateBy("goods_id", [
                 "goods_id"=>$goods_id,
                 "shipped_sum" => 'DB::RAW(shipped_sum-'.intval($quantity).')',
            ]);
         } else {
             $it = new Item;
             $it->success( $item_id, $quantity);
         }
    }


    // @KEEP END

	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 商品ID
		$this->putColumn( 'goods_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>false]));
		// 所属机构
		$this->putColumn( 'instance', $this->type("string", ["length"=>64, "index"=>true, "default"=>"root", "null"=>false]));
		// 名称
		$this->putColumn( 'name', $this->type("string", ["length"=>300, "index"=>true, "null"=>false]));
		// 别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 标签
		$this->putColumn( 'tags', $this->type("string", ["length"=>128, "json"=>true, "null"=>true]));
		// 类目
		$this->putColumn( 'category_ids', $this->type("text", ["json"=>true, "null"=>true]));
		// 推荐
		$this->putColumn( 'recommend_ids', $this->type("text", ["json"=>true, "null"=>true]));
		// 简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>400, "null"=>true]));
		// 主图
		$this->putColumn( 'cover', $this->type("string", ["length"=>800, "json"=>true, "null"=>true]));
		// 图片
		$this->putColumn( 'images', $this->type("text", ["json"=>true, "null"=>true]));
		// 视频
		$this->putColumn( 'videos', $this->type("text", ["json"=>true, "null"=>true]));
		// 参数表
		$this->putColumn( 'params', $this->type("text", ["json"=>true, "null"=>true]));
		// 产品详情
		$this->putColumn( 'content', $this->type("longText", ["null"=>true]));
		// 常见问题
		$this->putColumn( 'content_faq', $this->type("text", ["null"=>true]));
		// 售后服务
		$this->putColumn( 'content_serv', $this->type("text", ["null"=>true]));
		// SKU数量
		$this->putColumn( 'sku_cnt', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 单品总数
		$this->putColumn( 'sku_sum', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 货运装箱总数
		$this->putColumn( 'shipped_sum', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 可售总数
		$this->putColumn( 'available_sum', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 最低单价
		$this->putColumn( 'lower_price', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 销售方式
		$this->putColumn( 'sale_way', $this->type("string", ["length"=>32, "index"=>true, "default"=>"normal", "null"=>true]));
		// 开售时间
		$this->putColumn( 'opened_at', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 结束时间
		$this->putColumn( 'closed_at', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 付款期限
		$this->putColumn( 'pay_duration', $this->type("integer", ["index"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "default"=>"online", "null"=>false]));
		// 事件
		$this->putColumn( 'events', $this->type("text", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {

		// 格式化: 主图
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('cover', $rs ) ) {
			$is_string = is_string($rs["cover"]);
			$rs["cover"] = $is_string ? [$rs["cover"]] : $rs["cover"];
			$rs["cover"] = !is_array($rs["cover"]) ? [] : $rs["cover"];
			foreach ($rs["cover"] as & $file ) {
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
				$rs["cover"] = current($rs["cover"]);
			}
		}

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

		// 格式化: 视频
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('videos', $rs ) ) {
			$is_string = is_string($rs["videos"]);
			$rs["videos"] = $is_string ? [$rs["videos"]] : $rs["videos"];
			$rs["videos"] = !is_array($rs["videos"]) ? [] : $rs["videos"];
			foreach ($rs["videos"] as & $file ) {
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
				$rs["videos"] = current($rs["videos"]);
			}
		}


		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"online" => [
		  			"value" => "online",
		  			"name" => "开启",
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
	 * 按商品ID查询一条商品记录
	 * @param string $goods_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["goods_id"],  // 商品ID 
	 *          	  $rs["instance"],  // 所属机构 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["category_ids"],  // 类目 
	 *                $rs["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *          	  $rs["recommend_ids"],  // 推荐 
	 *                $rs["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 主图 
	 *          	  $rs["images"],  // 图片 
	 *          	  $rs["videos"],  // 视频 
	 *          	  $rs["params"],  // 参数表 
	 *          	  $rs["content"],  // 产品详情 
	 *          	  $rs["content_faq"],  // 常见问题 
	 *          	  $rs["content_serv"],  // 售后服务 
	 *          	  $rs["sku_cnt"],  // SKU数量 
	 *          	  $rs["sku_sum"],  // 单品总数 
	 *          	  $rs["shipped_sum"],  // 货运装箱总数 
	 *          	  $rs["available_sum"],  // 可售总数 
	 *          	  $rs["lower_price"],  // 最低单价 
	 *          	  $rs["sale_way"],  // 销售方式 
	 *          	  $rs["opened_at"],  // 开售时间 
	 *          	  $rs["closed_at"],  // 结束时间 
	 *          	  $rs["pay_duration"],  // 付款期限 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["events"],  // 事件 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$category_ids[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$category_ids[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$category_ids[n]]["project"], // category.project
	 *                $rs["_map_category"][$category_ids[n]]["page"], // category.page
	 *                $rs["_map_category"][$category_ids[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$category_ids[n]]["name"], // category.name
	 *                $rs["_map_category"][$category_ids[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$category_ids[n]]["link"], // category.link
	 *                $rs["_map_category"][$category_ids[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$category_ids[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$category_ids[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$category_ids[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$category_ids[n]]["param"], // category.param
	 *                $rs["_map_category"][$category_ids[n]]["status"], // category.status
	 *                $rs["_map_category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$category_ids[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$category_ids[n]]["isblank"], // category.isblank
	 *                $rs["_map_recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *                $rs["_map_recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *                $rs["_map_recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *                $rs["_map_recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *                $rs["_map_recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *                $rs["_map_recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *                $rs["_map_recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *                $rs["_map_recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *                $rs["_map_recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *                $rs["_map_recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *                $rs["_map_recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *                $rs["_map_recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *                $rs["_map_recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *                $rs["_map_recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *                $rs["_map_recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *                $rs["_map_recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *                $rs["_map_recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *                $rs["_map_recommend"][$recommend_ids[n]]["series"], // recommend.series
	 */
	public function getByGoodsId( $goods_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_goods as goods", "{none}")->query();
  		$qb->where('goods_id', '=', $goods_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$rs["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}

		return $rs;
	}

		

	/**
	 * 按商品ID查询一组商品记录
	 * @param array   $goods_ids 唯一主键数组 ["$goods_id1","$goods_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 商品记录MAP {"goods_id1":{"key":"value",...}...}
	 */
	public function getInByGoodsId($goods_ids, $select=["goods.goods_id","goods.cover","goods.name","goods.slug","c.name","goods.lower_price","goods.available_sum","goods.status","goods.created_at","goods.updated_at"], $order=["goods.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_goods as goods", "{none}")->query();
  		$qb->whereIn('goods.goods_id', $goods_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['goods_id']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$map["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按商品ID保存商品记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByGoodsId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("goods_id", $data, ["goods_id", "slug"], ['_id', 'goods_id']);
		return $this->getByGoodsId( $rs['goods_id'], $select );
	}
	
	/**
	 * 按别名查询一条商品记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["goods_id"],  // 商品ID 
	 *          	  $rs["instance"],  // 所属机构 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["tags"],  // 标签 
	 *          	  $rs["category_ids"],  // 类目 
	 *                $rs["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *          	  $rs["recommend_ids"],  // 推荐 
	 *                $rs["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 主图 
	 *          	  $rs["images"],  // 图片 
	 *          	  $rs["videos"],  // 视频 
	 *          	  $rs["params"],  // 参数表 
	 *          	  $rs["content"],  // 产品详情 
	 *          	  $rs["content_faq"],  // 常见问题 
	 *          	  $rs["content_serv"],  // 售后服务 
	 *          	  $rs["sku_cnt"],  // SKU数量 
	 *          	  $rs["sku_sum"],  // 单品总数 
	 *          	  $rs["shipped_sum"],  // 货运装箱总数 
	 *          	  $rs["available_sum"],  // 可售总数 
	 *          	  $rs["lower_price"],  // 最低单价 
	 *          	  $rs["sale_way"],  // 销售方式 
	 *          	  $rs["opened_at"],  // 开售时间 
	 *          	  $rs["closed_at"],  // 结束时间 
	 *          	  $rs["pay_duration"],  // 付款期限 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["events"],  // 事件 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$category_ids[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$category_ids[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$category_ids[n]]["project"], // category.project
	 *                $rs["_map_category"][$category_ids[n]]["page"], // category.page
	 *                $rs["_map_category"][$category_ids[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$category_ids[n]]["name"], // category.name
	 *                $rs["_map_category"][$category_ids[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$category_ids[n]]["link"], // category.link
	 *                $rs["_map_category"][$category_ids[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$category_ids[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$category_ids[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$category_ids[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$category_ids[n]]["param"], // category.param
	 *                $rs["_map_category"][$category_ids[n]]["status"], // category.status
	 *                $rs["_map_category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$category_ids[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$category_ids[n]]["isblank"], // category.isblank
	 *                $rs["_map_recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *                $rs["_map_recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *                $rs["_map_recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *                $rs["_map_recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *                $rs["_map_recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *                $rs["_map_recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *                $rs["_map_recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *                $rs["_map_recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *                $rs["_map_recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *                $rs["_map_recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *                $rs["_map_recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *                $rs["_map_recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *                $rs["_map_recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *                $rs["_map_recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *                $rs["_map_recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *                $rs["_map_recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *                $rs["_map_recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *                $rs["_map_recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *                $rs["_map_recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *                $rs["_map_recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *                $rs["_map_recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *                $rs["_map_recommend"][$recommend_ids[n]]["series"], // recommend.series
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_goods as goods", "{none}")->query();
  		$qb->where('slug', '=', $slug );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$rs["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}

		return $rs;
	}

	

	/**
	 * 按别名查询一组商品记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 商品记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["goods.goods_id","goods.cover","goods.name","goods.slug","c.name","goods.lower_price","goods.available_sum","goods.status","goods.created_at","goods.updated_at"], $order=["goods.created_at"=>"asc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_goods as goods", "{none}")->query();
  		$qb->whereIn('goods.slug', $slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$map["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}


		return $map;
	}


	/**
	 * 按别名保存商品记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("slug", $data, ["goods_id", "slug"], ['_id', 'goods_id']);
		return $this->getByGoodsId( $rs['goods_id'], $select );
	}

	/**
	 * 根据商品ID上传主图。
	 * @param string $goods_id 商品ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverByGoodsId($goods_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('goods_id', $goods_id, ["cover"]);
		$paths = empty($rs["cover"]) ? [] : $rs["cover"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('goods_id', ["goods_id"=>$goods_id, "cover"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据商品ID上传图片。
	 * @param string $goods_id 商品ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesByGoodsId($goods_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('goods_id', $goods_id, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('goods_id', ["goods_id"=>$goods_id, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据商品ID上传视频。
	 * @param string $goods_id 商品ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadVideosByGoodsId($goods_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('goods_id', $goods_id, ["videos"]);
		$paths = empty($rs["videos"]) ? [] : $rs["videos"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('goods_id', ["goods_id"=>$goods_id, "videos"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据别名上传主图。
	 * @param string $slug 别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["cover"]);
		$paths = empty($rs["cover"]) ? [] : $rs["cover"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "cover"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据别名上传图片。
	 * @param string $slug 别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadImagesBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["images"]);
		$paths = empty($rs["images"]) ? [] : $rs["images"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "images"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据别名上传视频。
	 * @param string $slug 别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadVideosBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["videos"]);
		$paths = empty($rs["videos"]) ? [] : $rs["videos"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "videos"=>$paths] );
		}

		return $fs;
	}


	/**
	 * 添加商品记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["goods_id"]) ) { 
			$data["goods_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排商品记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 商品记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["goods.goods_id","goods.cover","goods.name","goods.slug","c.name","goods.lower_price","goods.available_sum","goods.status","goods.created_at","goods.updated_at"], $order=["goods.created_at"=>"asc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_goods as goods", "{none}")->query();
  

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$data["_map_recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索商品记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["goods.goods_id","goods.cover","goods.name","goods.slug","c.name","goods.lower_price","goods.available_sum","goods.status","goods.created_at","goods.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["goods_id"] 按商品ID查询 ( = )
	 *			      $query["goods_ids"] 按商品IDS查询 ( IN )
	 *			      $query["slug"] 按别名查询 ( = )
	 *			      $query["name"] 按名称查询 ( = )
	 *			      $query["sku_cnt"] 按SKU查询 ( = )
	 *			      $query["name"] 按名称查询 ( LIKE )
	 *			      $query["sale_way"] 按销售方式查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
	 *           
	 * @return array 商品记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["goods_id"],  // 商品ID 
	 *               	["instance"],  // 所属机构 
	 *               	["name"],  // 名称 
	 *               	["slug"],  // 别名 
	 *               	["tags"],  // 标签 
	 *               	["category_ids"],  // 类目 
	 *               	["category"][$category_ids[n]]["category_id"], // category.category_id
	 *               	["recommend_ids"],  // 推荐 
	 *               	["recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *               	["summary"],  // 简介 
	 *               	["cover"],  // 主图 
	 *               	["images"],  // 图片 
	 *               	["videos"],  // 视频 
	 *               	["params"],  // 参数表 
	 *               	["content"],  // 产品详情 
	 *               	["content_faq"],  // 常见问题 
	 *               	["content_serv"],  // 售后服务 
	 *               	["sku_cnt"],  // SKU数量 
	 *               	["sku_sum"],  // 单品总数 
	 *               	["shipped_sum"],  // 货运装箱总数 
	 *               	["available_sum"],  // 可售总数 
	 *               	["lower_price"],  // 最低单价 
	 *               	["sale_way"],  // 销售方式 
	 *               	["opened_at"],  // 开售时间 
	 *               	["closed_at"],  // 结束时间 
	 *               	["pay_duration"],  // 付款期限 
	 *               	["status"],  // 状态 
	 *               	["events"],  // 事件 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["category"][$category_ids[n]]["created_at"], // category.created_at
	 *               	["category"][$category_ids[n]]["updated_at"], // category.updated_at
	 *               	["category"][$category_ids[n]]["slug"], // category.slug
	 *               	["category"][$category_ids[n]]["project"], // category.project
	 *               	["category"][$category_ids[n]]["page"], // category.page
	 *               	["category"][$category_ids[n]]["wechat"], // category.wechat
	 *               	["category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	 *               	["category"][$category_ids[n]]["name"], // category.name
	 *               	["category"][$category_ids[n]]["fullname"], // category.fullname
	 *               	["category"][$category_ids[n]]["link"], // category.link
	 *               	["category"][$category_ids[n]]["root_id"], // category.root_id
	 *               	["category"][$category_ids[n]]["parent_id"], // category.parent_id
	 *               	["category"][$category_ids[n]]["priority"], // category.priority
	 *               	["category"][$category_ids[n]]["hidden"], // category.hidden
	 *               	["category"][$category_ids[n]]["isnav"], // category.isnav
	 *               	["category"][$category_ids[n]]["param"], // category.param
	 *               	["category"][$category_ids[n]]["status"], // category.status
	 *               	["category"][$category_ids[n]]["issubnav"], // category.issubnav
	 *               	["category"][$category_ids[n]]["highlight"], // category.highlight
	 *               	["category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	 *               	["category"][$category_ids[n]]["isblank"], // category.isblank
	 *               	["recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	 *               	["recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	 *               	["recommend"][$recommend_ids[n]]["title"], // recommend.title
	 *               	["recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	 *               	["recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	 *               	["recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	 *               	["recommend"][$recommend_ids[n]]["type"], // recommend.type
	 *               	["recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	 *               	["recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	 *               	["recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	 *               	["recommend"][$recommend_ids[n]]["period"], // recommend.period
	 *               	["recommend"][$recommend_ids[n]]["images"], // recommend.images
	 *               	["recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	 *               	["recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	 *               	["recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	 *               	["recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	 *               	["recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	 *               	["recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	 *               	["recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	 *               	["recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	 *               	["recommend"][$recommend_ids[n]]["events"], // recommend.events
	 *               	["recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	 *               	["recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	 *               	["recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	 *               	["recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	 *               	["recommend"][$recommend_ids[n]]["style"], // recommend.style
	 *               	["recommend"][$recommend_ids[n]]["status"], // recommend.status
	 *               	["recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	 *               	["recommend"][$recommend_ids[n]]["series"], // recommend.series
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["goods.goods_id","goods.cover","goods.name","goods.slug","c.name","goods.lower_price","goods.available_sum","goods.status","goods.created_at","goods.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "goods.goods_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_goods as goods", "{none}")->query();
  
		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("goods.instance", "like", "%{$query['keyword']}%");
				$qb->orWhere("goods.name","like", "%{$query['keyword']}%");
				$qb->orWhere("goods.slug","like", "%{$query['keyword']}%");
			});
		}


		// 按商品ID查询 (=)  
		if ( array_key_exists("goods_id", $query) &&!empty($query['goods_id']) ) {
			$qb->where("goods.goods_id", '=', "{$query['goods_id']}" );
		}
		  
		// 按商品IDS查询 (IN)  
		if ( array_key_exists("goods_ids", $query) &&!empty($query['goods_ids']) ) {
			if ( is_string($query['goods_ids']) ) {
				$query['goods_ids'] = explode(',', $query['goods_ids']);
			}
			$qb->whereIn("goods.goods_id",  $query['goods_ids'] );
		}
		  
		// 按别名查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("goods.slug", '=', "{$query['slug']}" );
		}
		  
		// 按名称查询 (=)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("goods.name", '=', "{$query['name']}" );
		}
		  
		// 按SKU查询 (=)  
		if ( array_key_exists("sku_cnt", $query) &&!empty($query['sku_cnt']) ) {
			$qb->where("goods.sku_cnt", '=', "{$query['sku_cnt']}" );
		}
		  
		// 按名称查询 (LIKE)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("goods.name", 'like', "%{$query['name']}%" );
		}
		  
		// 按销售方式查询 (=)  
		if ( array_key_exists("sale_way", $query) &&!empty($query['sale_way']) ) {
			$qb->where("goods.sale_way", '=', "{$query['sale_way']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("goods.status", '=', "{$query['status']}" );
		}
		  

		// 按创建时间 ASC 排序
		if ( array_key_exists("orderby_created_at_asc", $query) &&!empty($query['orderby_created_at_asc']) ) {
			$qb->orderBy("goods.created_at", "asc");
		}

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("goods.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$goodss = $qb->select( $select )->pgArray($perpage, ['goods._id'], 'page', $page);

 		$category_ids = []; // 读取 inWhere category 数据
 		$recommend_ids = []; // 读取 inWhere recommend 数据
		foreach ($goodss['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["category_ids"]) ? $rs["category_ids"] : [$rs["category_ids"]]);
 			// for inWhere recommend
			$recommend_ids = array_merge($recommend_ids, is_array($rs["recommend_ids"]) ? $rs["recommend_ids"] : [$rs["recommend_ids"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$goodss["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}
 		// 读取 inWhere recommend 数据
		if ( !empty($inwhereSelect["recommend"]) && method_exists("\\Xpmsns\\Pages\\Model\\Recommend", 'getInByRecommendId') ) {
			$recommend_ids = array_unique($recommend_ids);
			$selectFields = $inwhereSelect["recommend"];
			$goodss["recommend"] = (new \Xpmsns\Pages\Model\Recommend)->getInByRecommendId($recommend_ids, $selectFields);
		}
	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$goodss['_sql'] = $qb->getSql();
			$goodss['query'] = $query;
		}

		return $goodss;
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
				$select[$idx] = "goods." .$select[$idx];
				continue;
			}
			
			// 连接栏目 (category as c )
			if ( strpos( $fd, "c." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "category_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "goods.category_ids");
				}
			}
			
			// 连接推荐 (recommend as r )
			if ( strpos( $fd, "r." ) === 0 || strpos("recommend.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["recommend"][] = trim($arr[1]);
				$inwhereSelect["recommend"][] = "recommend_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "goods.recommend_ids");
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
			"goods_id",  // 商品ID
			"instance",  // 所属机构
			"name",  // 名称
			"slug",  // 别名
			"tags",  // 标签
			"category_ids",  // 类目
			"recommend_ids",  // 推荐
			"summary",  // 简介
			"cover",  // 主图
			"images",  // 图片
			"videos",  // 视频
			"params",  // 参数表
			"content",  // 产品详情
			"content_faq",  // 常见问题
			"content_serv",  // 售后服务
			"sku_cnt",  // SKU数量
			"sku_sum",  // 单品总数
			"shipped_sum",  // 货运装箱总数
			"available_sum",  // 可售总数
			"lower_price",  // 最低单价
			"sale_way",  // 销售方式
			"opened_at",  // 开售时间
			"closed_at",  // 结束时间
			"pay_duration",  // 付款期限
			"status",  // 状态
			"events",  // 事件
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>