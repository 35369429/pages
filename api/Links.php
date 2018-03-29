<?php
/**
 * Class Links 
 * 友链数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-29 11:17:05
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                                                                                                                                     use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;


class Links extends Api {

	/**
	 * 友链数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 查询一条友链记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段 ["links_id","links_slug","name","summary","link","logo","size","position","pageview","status","priority"]
	 * 				 $query['links_id']  按友链ID查询
	 * 				 $query['links_slug']  按友链别名查询
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段 ["links_id","links_slug","name","summary","link","logo","size","position","pageview","status","priority"]
	 * 				 $data['links_id']  按友链ID查询
	 * 				 $data['links_slug']  按友链别名查询
	 *
	 * @return array 友链记录 Key Value 结构数据 
	 *               { 
	 *               	"links_id"  友链ID ,
	 *               	"links_slug"  友链别名 ,
	 *               	"name"  名称 ,
	 *               	"summary"  摘要 ,
	 *               	"link"  链接 ,
	 *               	"logo"  LOGO ,
	 *               	"size"  尺寸 ,
	 *               	"position"  呈现位置 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 
	 *               }
	 */
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["links_id","links_slug","name","summary","link","logo","size","position","pageview","status","priority"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按友链ID
		if ( !empty($data["links_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Links;
			return $inst->getByLinksId($data["links_id"], $select);
		}

		// 按友链别名
		if ( !empty($data["links_slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Links;
			return $inst->getByLinksSlug($data["links_slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条友链记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['links_id'] 友链ID
	 *               $data['links_slug'] 友链别名
	 *               $data['name'] 名称
	 *               $data['summary'] 摘要
	 *               $data['link'] 链接
	 *               $data['logo'] LOGO
	 *               $data['size'] 尺寸
	 *               $data['position'] 呈现位置
	 *               $data['pageview'] 点击量
	 *               $data['status'] 状态
	 *               $data['priority'] 优先级
	 *               $data['user'] 操作者
	 *
	 * @return array 新增的友链记录 Key Value 结构数据 
	 *               {
	 *               	"links_id"  友链ID ,
	 *               	"links_slug"  友链别名 ,
	 *               	"name"  名称 ,
	 *               	"summary"  摘要 ,
	 *               	"link"  链接 ,
	 *               	"logo"  LOGO ,
	 *               	"size"  尺寸 ,
	 *               	"position"  呈现位置 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 
	 *               }
	 */
	protected function create( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  


		$inst = new \Xpmsns\Pages\Model\Links;
		$rs = $inst->create( $data );
		$inst->format($rs);
		return  $rs;
	}


	/**
	 * 更新一条友链记录
	 * @param  array $query GET 参数
	 * 				 $query['links_id']  按友链ID更新
	 * 				 $query['links_slug']  按友链别名更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['links_id'] 友链ID
	 *               $data['links_slug'] 友链别名
	 *               $data['name'] 名称
	 *               $data['summary'] 摘要
	 *               $data['link'] 链接
	 *               $data['logo'] LOGO
	 *               $data['size'] 尺寸
	 *               $data['position'] 呈现位置
	 *               $data['pageview'] 点击量
	 *               $data['status'] 状态
	 *               $data['priority'] 优先级
	 *               $data['user'] 操作者
	 *
	 * @return array 更新的友链记录 Key Value 结构数据 
	 *               {
	 *               	"links_id"  友链ID ,
	 *               	"links_slug"  友链别名 ,
	 *               	"name"  名称 ,
	 *               	"summary"  摘要 ,
	 *               	"link"  链接 ,
	 *               	"logo"  LOGO ,
	 *               	"size"  尺寸 ,
	 *               	"position"  呈现位置 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 
	 *               }
	 */
	protected function update( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按友链ID
		if ( !empty($query["links_id"]) ) {
			$data = array_merge( $data, ["links_id"=>$query["links_id"]] );
			$inst = new \Xpmsns\Pages\Model\Links;
			$rs = $inst->updateBy("links_id",$data);
			$inst->format($rs);
			return $rs;
		}

		// 按友链别名
		if ( !empty($query["links_slug"]) ) {
			$data = array_merge( $data, ["links_slug"=>$query["links_slug"]] );
			$inst = new \Xpmsns\Pages\Model\Links;
			$rs = $inst->updateBy("links_slug",$data);
			$inst->format($rs);
			return $rs;
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条友链记录
	 * @param  array $query GET 参数
	 * 				 $query['links_id']  按友链ID 删除
	 * 				 $query['links_slug']  按友链别名 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按友链ID
		if ( !empty($query["links_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Links;
			$resp = $inst->remove($query['links_id'], "links_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		// 按友链别名
		if ( !empty($query["links_slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Links;
			$resp = $inst->remove($query['links_slug'], "links_slug");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索友链记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["links_id","links_slug","name","summary","link","logo","size","position","pageview","status","priority"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["name"] 按名称查询 ( AND LIKE )
	 *			      $query["links_slug"] 按友链别名查询 ( AND = )
	 *			      $query["position"] 按呈现位置查询 ( AND = )
	 *			      $query["priority"] 按优先级查询 ( AND > )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["order_pri"]  按优先级 ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["links_id","links_slug","name","summary","link","logo","size","position","pageview","status","priority"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["name"] 按名称查询 ( AND LIKE )
	 *			      $data["links_slug"] 按友链别名查询 ( AND = )
	 *			      $data["position"] 按呈现位置查询 ( AND = )
	 *			      $data["priority"] 按优先级查询 ( AND > )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["order_pri"]  按优先级 ASC 排序
 
	 * @return array 友链记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	"links_id"  友链ID ,
	 *               	"links_slug"  友链别名 ,
	 *               	"name"  名称 ,
	 *               	"summary"  摘要 ,
	 *               	"link"  链接 ,
	 *               	"logo"  LOGO ,
	 *               	"size"  尺寸 ,
	 *               	"position"  呈现位置 ,
	 *               	"pageview"  点击量 ,
	 *               	"status"  状态 ,
	 *               	"priority"  优先级 ,
	 *               	"user"  操作者 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["links_id","links_slug","name","summary","link","logo","size","position","pageview","status","priority"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Links;
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