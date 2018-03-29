<?php
/**
 * Class Siteconf 
 * 站点配置数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-29 11:15:52
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                                                                                                                                                                                                                                                                                                   use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;


class Siteconf extends Api {

	/**
	 * 站点配置数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 查询一条站点配置记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段 ["position","status"]
	 * 				 $query['site_id']  按配制ID查询
	 * 				 $query['site_slug']  按配制别名查询
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段 ["position","status"]
	 * 				 $data['site_id']  按配制ID查询
	 * 				 $data['site_slug']  按配制别名查询
	 *
	 * @return array 站点配置记录 Key Value 结构数据 
	 *               { 
	 *               	"site_id"  配制ID ,
	 *               	"site_slug"  配制别名 ,
	 *               	"position"  呈现位置 ,
	 *               	"site_name"  网站名称 ,
	 *               	"site_intro"  网站简介 ,
	 *               	"site_homepage"  官网地址 ,
	 *               	"site_downloadpage"  应用下载地址 ,
	 *               	"site_logo"  网站LOGO ,
	 *               	"site_no"  网站备案号 ,
	 *               	"company"  公司名称 ,
	 *               	"address"  公司地址 ,
	 *               	"tel"  客服电话 ,
	 *               	"qr_wxapp"  小程序二维码 ,
	 *               	"qr_wxpub"  订阅号二维码 ,
	 *               	"qr_wxse"  服务号二维码 ,
	 *               	"qr_android"  安卓应用二维码 ,
	 *               	"qr_ios"  苹果应用二维码 ,
	 *               	"status"  状态 ,
	 *               	"user"  操作者 
	 *               }
	 */
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["position","status"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按配制ID
		if ( !empty($data["site_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			return $inst->getBySiteId($data["site_id"], $select);
		}

		// 按配制别名
		if ( !empty($data["site_slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			return $inst->getBySiteSlug($data["site_slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条站点配置记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['site_id'] 配制ID
	 *               $data['site_slug'] 配制别名
	 *               $data['position'] 呈现位置
	 *               $data['site_name'] 网站名称
	 *               $data['site_intro'] 网站简介
	 *               $data['site_homepage'] 官网地址
	 *               $data['site_downloadpage'] 应用下载地址
	 *               $data['site_logo'] 网站LOGO
	 *               $data['site_no'] 网站备案号
	 *               $data['company'] 公司名称
	 *               $data['address'] 公司地址
	 *               $data['tel'] 客服电话
	 *               $data['qr_wxapp'] 小程序二维码
	 *               $data['qr_wxpub'] 订阅号二维码
	 *               $data['qr_wxse'] 服务号二维码
	 *               $data['qr_android'] 安卓应用二维码
	 *               $data['qr_ios'] 苹果应用二维码
	 *               $data['status'] 状态
	 *               $data['user'] 操作者
	 *
	 * @return array 新增的站点配置记录 Key Value 结构数据 
	 *               {
	 *               	"site_id"  配制ID ,
	 *               	"site_slug"  配制别名 ,
	 *               	"position"  呈现位置 ,
	 *               	"site_name"  网站名称 ,
	 *               	"site_intro"  网站简介 ,
	 *               	"site_homepage"  官网地址 ,
	 *               	"site_downloadpage"  应用下载地址 ,
	 *               	"site_logo"  网站LOGO ,
	 *               	"site_no"  网站备案号 ,
	 *               	"company"  公司名称 ,
	 *               	"address"  公司地址 ,
	 *               	"tel"  客服电话 ,
	 *               	"qr_wxapp"  小程序二维码 ,
	 *               	"qr_wxpub"  订阅号二维码 ,
	 *               	"qr_wxse"  服务号二维码 ,
	 *               	"qr_android"  安卓应用二维码 ,
	 *               	"qr_ios"  苹果应用二维码 ,
	 *               	"status"  状态 ,
	 *               	"user"  操作者 
	 *               }
	 */
	protected function create( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  


		$inst = new \Xpmsns\Pages\Model\Siteconf;
		$rs = $inst->create( $data );
		$inst->format($rs);
		return  $rs;
	}


	/**
	 * 更新一条站点配置记录
	 * @param  array $query GET 参数
	 * 				 $query['site_id']  按配制ID更新
	 * 				 $query['site_slug']  按配制别名更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['site_id'] 配制ID
	 *               $data['site_slug'] 配制别名
	 *               $data['position'] 呈现位置
	 *               $data['site_name'] 网站名称
	 *               $data['site_intro'] 网站简介
	 *               $data['site_homepage'] 官网地址
	 *               $data['site_downloadpage'] 应用下载地址
	 *               $data['site_logo'] 网站LOGO
	 *               $data['site_no'] 网站备案号
	 *               $data['company'] 公司名称
	 *               $data['address'] 公司地址
	 *               $data['tel'] 客服电话
	 *               $data['qr_wxapp'] 小程序二维码
	 *               $data['qr_wxpub'] 订阅号二维码
	 *               $data['qr_wxse'] 服务号二维码
	 *               $data['qr_android'] 安卓应用二维码
	 *               $data['qr_ios'] 苹果应用二维码
	 *               $data['status'] 状态
	 *               $data['user'] 操作者
	 *
	 * @return array 更新的站点配置记录 Key Value 结构数据 
	 *               {
	 *               	"site_id"  配制ID ,
	 *               	"site_slug"  配制别名 ,
	 *               	"position"  呈现位置 ,
	 *               	"site_name"  网站名称 ,
	 *               	"site_intro"  网站简介 ,
	 *               	"site_homepage"  官网地址 ,
	 *               	"site_downloadpage"  应用下载地址 ,
	 *               	"site_logo"  网站LOGO ,
	 *               	"site_no"  网站备案号 ,
	 *               	"company"  公司名称 ,
	 *               	"address"  公司地址 ,
	 *               	"tel"  客服电话 ,
	 *               	"qr_wxapp"  小程序二维码 ,
	 *               	"qr_wxpub"  订阅号二维码 ,
	 *               	"qr_wxse"  服务号二维码 ,
	 *               	"qr_android"  安卓应用二维码 ,
	 *               	"qr_ios"  苹果应用二维码 ,
	 *               	"status"  状态 ,
	 *               	"user"  操作者 
	 *               }
	 */
	protected function update( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按配制ID
		if ( !empty($query["site_id"]) ) {
			$data = array_merge( $data, ["site_id"=>$query["site_id"]] );
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			$rs = $inst->updateBy("site_id",$data);
			$inst->format($rs);
			return $rs;
		}

		// 按配制别名
		if ( !empty($query["site_slug"]) ) {
			$data = array_merge( $data, ["site_slug"=>$query["site_slug"]] );
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			$rs = $inst->updateBy("site_slug",$data);
			$inst->format($rs);
			return $rs;
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条站点配置记录
	 * @param  array $query GET 参数
	 * 				 $query['site_id']  按配制ID 删除
	 * 				 $query['site_slug']  按配制别名 删除
     *
	 * @param  array $data  POST 参数
	 * @return bool 成功返回 ["code"=>0, "message"=>"删除成功"]
	 */
	protected function delete( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按配制ID
		if ( !empty($query["site_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			$resp = $inst->remove($query['site_id'], "site_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		// 按配制别名
		if ( !empty($query["site_slug"]) ) {
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			$resp = $inst->remove($query['site_slug'], "site_slug");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索站点配置记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["site_id","site_slug","position","site_name","site_logo","status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["site_slug"] 按配制别名查询 ( AND = )
	 *			      $query["position"] 按呈现位置查询 ( AND = )
	 *			      $query["site_name"] 按网站名称查询 ( AND LIKE )
	 *			      $query["site_id"] 按配制ID查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["order_pri"]  按配制ID ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["site_id","site_slug","position","site_name","site_logo","status"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["site_slug"] 按配制别名查询 ( AND = )
	 *			      $data["position"] 按呈现位置查询 ( AND = )
	 *			      $data["site_name"] 按网站名称查询 ( AND LIKE )
	 *			      $data["site_id"] 按配制ID查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["order_pri"]  按配制ID ASC 排序
 
	 * @return array 站点配置记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	"site_id"  配制ID ,
	 *               	"site_slug"  配制别名 ,
	 *               	"position"  呈现位置 ,
	 *               	"site_name"  网站名称 ,
	 *               	"site_intro"  网站简介 ,
	 *               	"site_homepage"  官网地址 ,
	 *               	"site_downloadpage"  应用下载地址 ,
	 *               	"site_logo"  网站LOGO ,
	 *               	"site_no"  网站备案号 ,
	 *               	"company"  公司名称 ,
	 *               	"address"  公司地址 ,
	 *               	"tel"  客服电话 ,
	 *               	"qr_wxapp"  小程序二维码 ,
	 *               	"qr_wxpub"  订阅号二维码 ,
	 *               	"qr_wxse"  服务号二维码 ,
	 *               	"qr_android"  安卓应用二维码 ,
	 *               	"qr_ios"  苹果应用二维码 ,
	 *               	"status"  状态 ,
	 *               	"user"  操作者 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["site_id","site_slug","position","site_name","site_logo","status"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Siteconf;
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