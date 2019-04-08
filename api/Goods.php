<?php
/**
 * Class Goods 
 * 商品数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-04-09 02:48:43
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                  

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Goods extends Api {

	/**
	 * 商品数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */
    // @KEEP BEGIN
    
    /**
     * 读取商品全量信息(含所属单品)
     * @api /xpmsns/pages/goods/getByGoodsDetail
     * @method GET
     * @param param goods_id 商品ID
     */
    function getGoodsDetail( $query, $data ) {

        $goods_id = $query["goods_id"];
        if ( empty($goods_id) ) {
            throw new Excp("未提供商品ID", 402, ["query"=>$query]);
        }
        // 如果登录，个性化城实现
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];
        
        $goods = new \Xpmsns\Pages\Model\Goods;
        return $goods->getGoodsDetail($goods_id, $user_id);
    }

     /**
     * 搜索商品，返回包含SKU、真实价格、单品信息的商品机构数组
     * @api /xpmsns/pages/goods/searchGoods
     * @method GET
     * @param  @see search()
     */
    function searchGoods( $query, $data ) {
        $goods = new \Xpmsns\Pages\Model\Goods;
        // 读取字段
		$select = empty($query['select']) ? ["goods.goods_id","goods.instance","goods.name","goods.slug","goods.tags","goods.summary","goods.cover","goods.params","goods.sku_cnt","goods.sku_sum","goods.shipped_sum","goods.available_sum","goods.lower_price","goods.status","goods.created_at","goods.updated_at","c.category_id","c.name"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
        }
    
        // 如果登录，个性化城实现
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

		$query['select'] = $select;
        return $goods->searchGoods($query, $user_id);
    }


    // @KEEP END

	/**
	 * 查询一条商品记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["goods.goods_id","goods.instance","goods.name","goods.slug","goods.tags","goods.summary","goods.cover","goods.images","goods.videos","goods.params","goods.content","goods.content_faq","goods.content_serv","goods.sku_cnt","goods.sku_sum","goods.shipped_sum","goods.available_sum","goods.lower_price","goods.sale_way","goods.opened_at","goods.closed_at","goods.pay_duration","goods.status","goods.created_at","goods.updated_at","c.category_id","c.name","r.recommend_id"]
	 * 				 $query['goods_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["goods.goods_id","goods.instance","goods.name","goods.slug","goods.tags","goods.summary","goods.cover","goods.images","goods.videos","goods.params","goods.content","goods.content_faq","goods.content_serv","goods.sku_cnt","goods.sku_sum","goods.shipped_sum","goods.available_sum","goods.lower_price","goods.sale_way","goods.opened_at","goods.closed_at","goods.pay_duration","goods.status","goods.created_at","goods.updated_at","c.category_id","c.name","r.recommend_id"]
	 * 				 $data['goods_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 商品记录 Key Value 结构数据 
	 *               	["goods_id"],  // 商品ID 
	 *               	["instance"],  // 所属机构 
	 *               	["name"],  // 名称 
	 *               	["slug"],  // 别名 
	 *               	["tags"],  // 标签 
	 *               	["category_ids"],  // 类目 
	*               	["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *               	["recommend_ids"],  // 推荐 
	*               	["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
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
	*               	["_map_category"][$category_ids[n]]["created_at"], // category.created_at
	*               	["_map_category"][$category_ids[n]]["updated_at"], // category.updated_at
	*               	["_map_category"][$category_ids[n]]["slug"], // category.slug
	*               	["_map_category"][$category_ids[n]]["project"], // category.project
	*               	["_map_category"][$category_ids[n]]["page"], // category.page
	*               	["_map_category"][$category_ids[n]]["wechat"], // category.wechat
	*               	["_map_category"][$category_ids[n]]["wechat_offset"], // category.wechat_offset
	*               	["_map_category"][$category_ids[n]]["name"], // category.name
	*               	["_map_category"][$category_ids[n]]["fullname"], // category.fullname
	*               	["_map_category"][$category_ids[n]]["link"], // category.link
	*               	["_map_category"][$category_ids[n]]["root_id"], // category.root_id
	*               	["_map_category"][$category_ids[n]]["parent_id"], // category.parent_id
	*               	["_map_category"][$category_ids[n]]["priority"], // category.priority
	*               	["_map_category"][$category_ids[n]]["hidden"], // category.hidden
	*               	["_map_category"][$category_ids[n]]["isnav"], // category.isnav
	*               	["_map_category"][$category_ids[n]]["param"], // category.param
	*               	["_map_category"][$category_ids[n]]["status"], // category.status
	*               	["_map_category"][$category_ids[n]]["issubnav"], // category.issubnav
	*               	["_map_category"][$category_ids[n]]["highlight"], // category.highlight
	*               	["_map_category"][$category_ids[n]]["isfootnav"], // category.isfootnav
	*               	["_map_category"][$category_ids[n]]["isblank"], // category.isblank
	*               	["_map_recommend"][$recommend_ids[n]]["created_at"], // recommend.created_at
	*               	["_map_recommend"][$recommend_ids[n]]["updated_at"], // recommend.updated_at
	*               	["_map_recommend"][$recommend_ids[n]]["title"], // recommend.title
	*               	["_map_recommend"][$recommend_ids[n]]["summary"], // recommend.summary
	*               	["_map_recommend"][$recommend_ids[n]]["icon"], // recommend.icon
	*               	["_map_recommend"][$recommend_ids[n]]["slug"], // recommend.slug
	*               	["_map_recommend"][$recommend_ids[n]]["type"], // recommend.type
	*               	["_map_recommend"][$recommend_ids[n]]["ctype"], // recommend.ctype
	*               	["_map_recommend"][$recommend_ids[n]]["thumb_only"], // recommend.thumb_only
	*               	["_map_recommend"][$recommend_ids[n]]["video_only"], // recommend.video_only
	*               	["_map_recommend"][$recommend_ids[n]]["period"], // recommend.period
	*               	["_map_recommend"][$recommend_ids[n]]["images"], // recommend.images
	*               	["_map_recommend"][$recommend_ids[n]]["tpl_pc"], // recommend.tpl_pc
	*               	["_map_recommend"][$recommend_ids[n]]["tpl_h5"], // recommend.tpl_h5
	*               	["_map_recommend"][$recommend_ids[n]]["tpl_wxapp"], // recommend.tpl_wxapp
	*               	["_map_recommend"][$recommend_ids[n]]["tpl_android"], // recommend.tpl_android
	*               	["_map_recommend"][$recommend_ids[n]]["tpl_ios"], // recommend.tpl_ios
	*               	["_map_recommend"][$recommend_ids[n]]["keywords"], // recommend.keywords
	*               	["_map_recommend"][$recommend_ids[n]]["categories"], // recommend.categories
	*               	["_map_recommend"][$recommend_ids[n]]["articles"], // recommend.articles
	*               	["_map_recommend"][$recommend_ids[n]]["events"], // recommend.events
	*               	["_map_recommend"][$recommend_ids[n]]["albums"], // recommend.albums
	*               	["_map_recommend"][$recommend_ids[n]]["orderby"], // recommend.orderby
	*               	["_map_recommend"][$recommend_ids[n]]["pos"], // recommend.pos
	*               	["_map_recommend"][$recommend_ids[n]]["exclude_articles"], // recommend.exclude_articles
	*               	["_map_recommend"][$recommend_ids[n]]["style"], // recommend.style
	*               	["_map_recommend"][$recommend_ids[n]]["status"], // recommend.status
	*               	["_map_recommend"][$recommend_ids[n]]["bigdata_engine"], // recommend.bigdata_engine
	*               	["_map_recommend"][$recommend_ids[n]]["series"], // recommend.series
	*               	["_map_recommend"][$recommend_ids[n]]["questions"], // recommend.questions
	*               	["_map_recommend"][$recommend_ids[n]]["answers"], // recommend.answers
	*               	["_map_recommend"][$recommend_ids[n]]["goods"], // recommend.goods
	*               	["_map_recommend"][$recommend_ids[n]]["topics"], // recommend.topics
	*               	["_map_recommend"][$recommend_ids[n]]["article_select"], // recommend.article_select
	*               	["_map_recommend"][$recommend_ids[n]]["article_status"], // recommend.article_status
	*               	["_map_recommend"][$recommend_ids[n]]["event_select"], // recommend.event_select
	*               	["_map_recommend"][$recommend_ids[n]]["event_status"], // recommend.event_status
	*               	["_map_recommend"][$recommend_ids[n]]["exclude_events"], // recommend.exclude_events
	*               	["_map_recommend"][$recommend_ids[n]]["album_select"], // recommend.album_select
	*               	["_map_recommend"][$recommend_ids[n]]["album_status"], // recommend.album_status
	*               	["_map_recommend"][$recommend_ids[n]]["exclude_albums"], // recommend.exclude_albums
	*               	["_map_recommend"][$recommend_ids[n]]["question_select"], // recommend.question_select
	*               	["_map_recommend"][$recommend_ids[n]]["question_status"], // recommend.question_status
	*               	["_map_recommend"][$recommend_ids[n]]["exclude_questions"], // recommend.exclude_questions
	*               	["_map_recommend"][$recommend_ids[n]]["answer_select"], // recommend.answer_select
	*               	["_map_recommend"][$recommend_ids[n]]["answer_status"], // recommend.answer_status
	*               	["_map_recommend"][$recommend_ids[n]]["exclude_answers"], // recommend.exclude_answers
	*               	["_map_recommend"][$recommend_ids[n]]["goods_select"], // recommend.goods_select
	*               	["_map_recommend"][$recommend_ids[n]]["goods_status"], // recommend.goods_status
	*               	["_map_recommend"][$recommend_ids[n]]["exclude_goods"], // recommend.exclude_goods
	*               	["_map_recommend"][$recommend_ids[n]]["ttl"], // recommend.ttl
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["goods.goods_id","goods.instance","goods.name","goods.slug","goods.tags","goods.summary","goods.cover","goods.images","goods.videos","goods.params","goods.content","goods.content_faq","goods.content_serv","goods.sku_cnt","goods.sku_sum","goods.shipped_sum","goods.available_sum","goods.lower_price","goods.sale_way","goods.opened_at","goods.closed_at","goods.pay_duration","goods.status","goods.created_at","goods.updated_at","c.category_id","c.name","r.recommend_id"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按商品ID
		if ( !empty($data["goods_id"]) ) {
			
			$keys = explode(',', $data["goods_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Goods;
				return $inst->getInByGoodsId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Goods;
			return $inst->getByGoodsId($data["goods_id"], $select);
		}

		// 按别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Goods;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Goods;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条商品记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['goods_id'] 商品ID
	 *               $data['instance'] 所属机构
	 *               $data['name'] 名称
	 *               $data['slug'] 别名
	 *               $data['tags'] 标签
	 *               $data['category_ids'] 类目
	 *               $data['recommend_ids'] 推荐
	 *               $data['summary'] 简介
	 *               $data['cover'] 主图
	 *               $data['images'] 图片
	 *               $data['videos'] 视频
	 *               $data['params'] 参数表
	 *               $data['content'] 产品详情
	 *               $data['content_faq'] 常见问题
	 *               $data['content_serv'] 售后服务
	 *               $data['sku_cnt'] SKU数量
	 *               $data['sku_sum'] 单品总数
	 *               $data['shipped_sum'] 货运装箱总数
	 *               $data['available_sum'] 可售总数
	 *               $data['lower_price'] 最低单价
	 *               $data['sale_way'] 销售方式
	 *               $data['opened_at'] 开售时间
	 *               $data['closed_at'] 结束时间
	 *               $data['pay_duration'] 付款期限
	 *               $data['status'] 状态
	 *               $data['events'] 事件
	 *
	 * @return array 新增的商品记录  @see get()
	 */
	protected function create( $query, $data ) {
		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}


		$inst = new \Xpmsns\Pages\Model\Goods;
		$rs = $inst->create( $data );
		return $inst->getByGoodsId($rs["goods_id"]);
	}


	/**
	 * 更新一条商品记录
	 * @param  array $query GET 参数
	 * 				 $query['name=goods_id']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['goods_id'] 商品ID
	 *               $data['instance'] 所属机构
	 *               $data['name'] 名称
	 *               $data['slug'] 别名
	 *               $data['tags'] 标签
	 *               $data['category_ids'] 类目
	 *               $data['recommend_ids'] 推荐
	 *               $data['summary'] 简介
	 *               $data['cover'] 主图
	 *               $data['images'] 图片
	 *               $data['videos'] 视频
	 *               $data['params'] 参数表
	 *               $data['content'] 产品详情
	 *               $data['content_faq'] 常见问题
	 *               $data['content_serv'] 售后服务
	 *               $data['sku_cnt'] SKU数量
	 *               $data['sku_sum'] 单品总数
	 *               $data['shipped_sum'] 货运装箱总数
	 *               $data['available_sum'] 可售总数
	 *               $data['lower_price'] 最低单价
	 *               $data['sale_way'] 销售方式
	 *               $data['opened_at'] 开售时间
	 *               $data['closed_at'] 结束时间
	 *               $data['pay_duration'] 付款期限
	 *               $data['status'] 状态
	 *               $data['events'] 事件
	 *
	 * @return array 更新的商品记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		// 按商品ID
		if ( !empty($query["goods_id"]) ) {
			$data = array_merge( $data, ["goods_id"=>$query["goods_id"]] );
			$inst = new \Xpmsns\Pages\Model\Goods;
			$rs = $inst->updateBy("goods_id",$data);
			return $inst->getByGoodsId($rs["goods_id"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条商品记录
	 * @param  array $query GET 参数
	 * 				 $query['goods_id']  按商品ID 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		// 按商品ID
		if ( !empty($query["goods_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Goods;
			$resp = $inst->remove($query['goods_id'], "goods_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索商品记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["goods.goods_id","goods.instance","goods.name","goods.slug","goods.tags","goods.summary","goods.cover","goods.params","goods.sku_cnt","goods.sku_sum","goods.shipped_sum","goods.available_sum","goods.lower_price","goods.status","goods.created_at","goods.updated_at","c.category_id","c.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["goods_id"] 按商品ID查询 ( AND = )
	 *			      $query["goods_ids"] 按商品ID查询 ( AND IN )
	 *			      $query["slug"] 按别名查询 ( AND = )
	 *			      $query["name"] 按名称查询 ( AND = )
	 *			      $query["sku_cnt"] 按SKU数量查询 ( AND = )
	 *			      $query["name"] 按名称查询 ( AND LIKE )
	 *			      $query["sale_way"] 按销售方式查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=goods_id","name=instance","name=name","name=slug","name=tags","name=summary","name=cover","name=params","name=sku_cnt","name=sku_sum","name=shipped_sum","name=available_sum","name=lower_price","name=status","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["goods_id"] 按商品ID查询 ( AND = )
	 *			      $data["goods_ids"] 按商品ID查询 ( AND IN )
	 *			      $data["slug"] 按别名查询 ( AND = )
	 *			      $data["name"] 按名称查询 ( AND = )
	 *			      $data["sku_cnt"] 按SKU数量查询 ( AND = )
	 *			      $data["name"] 按名称查询 ( AND LIKE )
	 *			      $data["sale_way"] 按销售方式查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $data["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
	 *
	 * @return array 商品记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
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
	*               	["recommend"][$recommend_ids[n]]["questions"], // recommend.questions
	*               	["recommend"][$recommend_ids[n]]["answers"], // recommend.answers
	*               	["recommend"][$recommend_ids[n]]["goods"], // recommend.goods
	*               	["recommend"][$recommend_ids[n]]["topics"], // recommend.topics
	*               	["recommend"][$recommend_ids[n]]["article_select"], // recommend.article_select
	*               	["recommend"][$recommend_ids[n]]["article_status"], // recommend.article_status
	*               	["recommend"][$recommend_ids[n]]["event_select"], // recommend.event_select
	*               	["recommend"][$recommend_ids[n]]["event_status"], // recommend.event_status
	*               	["recommend"][$recommend_ids[n]]["exclude_events"], // recommend.exclude_events
	*               	["recommend"][$recommend_ids[n]]["album_select"], // recommend.album_select
	*               	["recommend"][$recommend_ids[n]]["album_status"], // recommend.album_status
	*               	["recommend"][$recommend_ids[n]]["exclude_albums"], // recommend.exclude_albums
	*               	["recommend"][$recommend_ids[n]]["question_select"], // recommend.question_select
	*               	["recommend"][$recommend_ids[n]]["question_status"], // recommend.question_status
	*               	["recommend"][$recommend_ids[n]]["exclude_questions"], // recommend.exclude_questions
	*               	["recommend"][$recommend_ids[n]]["answer_select"], // recommend.answer_select
	*               	["recommend"][$recommend_ids[n]]["answer_status"], // recommend.answer_status
	*               	["recommend"][$recommend_ids[n]]["exclude_answers"], // recommend.exclude_answers
	*               	["recommend"][$recommend_ids[n]]["goods_select"], // recommend.goods_select
	*               	["recommend"][$recommend_ids[n]]["goods_status"], // recommend.goods_status
	*               	["recommend"][$recommend_ids[n]]["exclude_goods"], // recommend.exclude_goods
	*               	["recommend"][$recommend_ids[n]]["ttl"], // recommend.ttl
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["goods.goods_id","goods.instance","goods.name","goods.slug","goods.tags","goods.summary","goods.cover","goods.params","goods.sku_cnt","goods.sku_sum","goods.shipped_sum","goods.available_sum","goods.lower_price","goods.status","goods.created_at","goods.updated_at","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Goods;
		return $inst->search( $data );
	}


}