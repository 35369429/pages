<?php
/**
 * Class Adv 
 * 广告数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-30 01:16:05
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                                                                                                                                                                                                                                            use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;


class Adv extends Api {

	/**
	 * 广告数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 查询一条广告记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段 ["adv_id","link","images","size","position_no","expired","status"]
	 * 				 $query['adv_id']  按广告ID查询
	 * 				 $query['adv_slug']  按广告别名查询
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段 ["adv_id","link","images","size","position_no","expired","status"]
	 * 				 $data['adv_id']  按广告ID查询
	 * 				 $data['adv_slug']  按广告别名查询
	 *
	 * @return array 广告记录 Key Value 结构数据 
	 *               { 
	 *               	"adv_id"  广告ID ,
	 *               	"adv_slug"  广告别名 ,
	 *               	"name"  名称 ,
	 *               	"intro"  文案 ,
	 *               	"link"  链接 ,
	 *               	"images"  广告图片(多图) ,
	 *               	"cover"  封面图片 ,
	 *               	"terms"  服务协议 ,
	 *               	"size"  尺寸 ,
	 *               	"position_name"  位置名称 ,
	 *               	"position_no"  位置编号 ,
	 *               	"expired"  有效期 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"paystatus"  支付状态 ,
	 *               	"price"  单价 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 ,
	 *               	"keyword"  关键词 
	 *               }
	 */
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["adv_id","link","images","size","position_no","expired","status"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按广告ID
		if ( !empty($data["adv_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Adv;
			return $inst->getByAdvId($data["adv_id"], $select);
		}

		// 按广告别名
		if ( !empty($data["adv_slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Adv;
			return $inst->getByAdvSlug($data["adv_slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条广告记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['adv_id'] 广告ID
	 *               $data['adv_slug'] 广告别名
	 *               $data['name'] 名称
	 *               $data['intro'] 文案
	 *               $data['link'] 链接
	 *               $data['images'] 广告图片(多图)
	 *               $data['cover'] 封面图片
	 *               $data['terms'] 服务协议
	 *               $data['size'] 尺寸
	 *               $data['position_name'] 位置名称
	 *               $data['position_no'] 位置编号
	 *               $data['expired'] 有效期
	 *               $data['pageview'] 点击量
	 *               $data['status'] 状态
	 *               $data['paystatus'] 支付状态
	 *               $data['price'] 单价
	 *               $data['priority'] 优先级
	 *               $data['user'] 操作者
	 *               $data['keyword'] 关键词
	 *
	 * @return array 新增的广告记录 Key Value 结构数据 
	 *               {
	 *               	"adv_id"  广告ID ,
	 *               	"adv_slug"  广告别名 ,
	 *               	"name"  名称 ,
	 *               	"intro"  文案 ,
	 *               	"link"  链接 ,
	 *               	"images"  广告图片(多图) ,
	 *               	"cover"  封面图片 ,
	 *               	"terms"  服务协议 ,
	 *               	"size"  尺寸 ,
	 *               	"position_name"  位置名称 ,
	 *               	"position_no"  位置编号 ,
	 *               	"expired"  有效期 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"paystatus"  支付状态 ,
	 *               	"price"  单价 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 ,
	 *               	"keyword"  关键词 
	 *               }
	 */
	protected function create( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		if (empty($data['name'])) {
			throw new Excp("缺少必填字段名称 (name)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['position_name'])) {
			throw new Excp("缺少必填字段位置名称 (position_name)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['position_no'])) {
			throw new Excp("缺少必填字段位置编号 (position_no)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\Pages\Model\Adv;
		$rs = $inst->create( $data );
		$inst->format($rs);
		return  $rs;
	}


	/**
	 * 更新一条广告记录
	 * @param  array $query GET 参数
	 * 				 $query['adv_id']  按广告ID更新
	 * 				 $query['adv_slug']  按广告别名更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['adv_id'] 广告ID
	 *               $data['adv_slug'] 广告别名
	 *               $data['name'] 名称
	 *               $data['intro'] 文案
	 *               $data['link'] 链接
	 *               $data['images'] 广告图片(多图)
	 *               $data['cover'] 封面图片
	 *               $data['terms'] 服务协议
	 *               $data['size'] 尺寸
	 *               $data['position_name'] 位置名称
	 *               $data['position_no'] 位置编号
	 *               $data['expired'] 有效期
	 *               $data['pageview'] 点击量
	 *               $data['status'] 状态
	 *               $data['paystatus'] 支付状态
	 *               $data['price'] 单价
	 *               $data['priority'] 优先级
	 *               $data['user'] 操作者
	 *               $data['keyword'] 关键词
	 *
	 * @return array 更新的广告记录 Key Value 结构数据 
	 *               {
	 *               	"adv_id"  广告ID ,
	 *               	"adv_slug"  广告别名 ,
	 *               	"name"  名称 ,
	 *               	"intro"  文案 ,
	 *               	"link"  链接 ,
	 *               	"images"  广告图片(多图) ,
	 *               	"cover"  封面图片 ,
	 *               	"terms"  服务协议 ,
	 *               	"size"  尺寸 ,
	 *               	"position_name"  位置名称 ,
	 *               	"position_no"  位置编号 ,
	 *               	"expired"  有效期 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"paystatus"  支付状态 ,
	 *               	"price"  单价 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 ,
	 *               	"keyword"  关键词 
	 *               }
	 */
	protected function update( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按广告ID
		if ( !empty($query["adv_id"]) ) {
			$data = array_merge( $data, ["adv_id"=>$query["adv_id"]] );
			$inst = new \Xpmsns\Pages\Model\Adv;
			$rs = $inst->updateBy("adv_id",$data);
			$inst->format($rs);
			return $rs;
		}

		// 按广告别名
		if ( !empty($query["adv_slug"]) ) {
			$data = array_merge( $data, ["adv_slug"=>$query["adv_slug"]] );
			$inst = new \Xpmsns\Pages\Model\Adv;
			$rs = $inst->updateBy("adv_slug",$data);
			$inst->format($rs);
			return $rs;
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条广告记录
	 * @param  array $query GET 参数
	 * 				 $query['adv_id']  按广告ID 删除
	 * 				 $query['adv_slug']  按广告别名 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按广告ID
		if ( !empty($query["adv_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Adv;
			$resp = $inst->remove($query['adv_id'], "adv_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		// 按广告别名
		if ( !empty($query["adv_slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Adv;
			$resp = $inst->remove($query['adv_slug'], "adv_slug");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索广告记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["adv_id","adv_slug","name","intro","link","images","size","position_no","status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["position_no"] 按位置编号查询 ( AND = )
	 *			      $query["pnos"] 按位置编号查询 ( AND IN )
	 *			      $query["slug"] 按广告别名查询 ( AND = )
	 *			      $query["adv_ids"] 按广告ID查询 ( AND IN )
	 *			      $query["name"] 按名称查询 ( AND LIKE )
	 *			      $query["paystatus"] 按支付状态查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["order_pri"]  按优先级 ASC 排序
	 *			      $query["orderby_expired_desc"]  按有效期 DESC 排序
	 *			      $query["orderby_expired_asc"]  按有效期 ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["adv_id","adv_slug","name","intro","link","images","size","position_no","status"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["position_no"] 按位置编号查询 ( AND = )
	 *			      $data["pnos"] 按位置编号查询 ( AND IN )
	 *			      $data["slug"] 按广告别名查询 ( AND = )
	 *			      $data["adv_ids"] 按广告ID查询 ( AND IN )
	 *			      $data["name"] 按名称查询 ( AND LIKE )
	 *			      $data["paystatus"] 按支付状态查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["order_pri"]  按优先级 ASC 排序
	 *			      $data["orderby_expired_desc"]  按有效期 DESC 排序
	 *			      $data["orderby_expired_asc"]  按有效期 ASC 排序
 
	 * @return array 广告记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	"adv_id"  广告ID ,
	 *               	"adv_slug"  广告别名 ,
	 *               	"name"  名称 ,
	 *               	"intro"  文案 ,
	 *               	"link"  链接 ,
	 *               	"images"  广告图片(多图) ,
	 *               	"cover"  封面图片 ,
	 *               	"terms"  服务协议 ,
	 *               	"size"  尺寸 ,
	 *               	"position_name"  位置名称 ,
	 *               	"position_no"  位置编号 ,
	 *               	"expired"  有效期 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"paystatus"  支付状态 ,
	 *               	"price"  单价 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 ,
	 *               	"keyword"  关键词 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["adv_id","adv_slug","name","intro","link","images","size","position_no","status"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Adv;
		return $inst->search( $data );
	}

	/**
	 * 文件上传接口 (上传控件名称 docs)
	 * @param  array $query [description]
	 *               $query["private"]  上传文件为私有文件
	 * @param  [type] $data  [description]
	 * @return array 文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	protected function upload( $query, $data, $files ) {
		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		$fname = $files['docs']['tmp_name'];
		if ( $query['private'] ) {
			$media = new \Xpmse\Media(["host" => Utils::getHome(), 'private'=>true]);
		} else {
			$media = new \Xpmse\Media(["host" => Utils::getHome()]);
		}
		$ext = $media->getExt($fname);
		$rs = $media->uploadFile($fname, $ext);
		return $rs;
	}

}