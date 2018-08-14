<?php
/**
 * Class Siteconf 
 * 站点配置数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-08-14 21:41:57
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
	 * 自定义函数 
	 */


	/**
	 * 查询一条站点配置记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.created_at","site.updated_at"]
	 * 				 $query['site_id']  按查询 (多条用 "," 分割)
	 * 				 $query['site_slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.created_at","site.updated_at"]
	 * 				 $data['site_id']  按查询 (多条用 "," 分割)
	 * 				 $data['site_slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 站点配置记录 Key Value 结构数据 
	 *               	["site_id"],  // 配制ID 
	 *               	["site_slug"],  // 配制别名 
	 *               	["position"],  // 呈现位置 
	 *               	["site_name"],  // 网站名称 
	 *               	["site_slogen"],  // 网站Slogen 
	 *               	["icon"],  // 网站图标 
	 *               	["icon_light"],  // 浅色图标 
	 *               	["icon_dark"],  // 深色图标 
	 *               	["site_intro"],  // 网站简介 
	 *               	["site_homepage"],  // 官网地址 
	 *               	["site_downloadpage"],  // 应用下载地址 
	 *               	["site_logo"],  // 网站LOGO 
	 *               	["site_logo_light"],  // 浅色LOGO 
	 *               	["site_logo_dark"],  // 深色LOGO 
	 *               	["site_no"],  // 网站备案号 
	 *               	["company"],  // 公司名称 
	 *               	["address"],  // 公司地址 
	 *               	["tel"],  // 客服电话 
	 *               	["qq"],  // 客服QQ 
	 *               	["email"],  // 客服邮箱 
	 *               	["se_time"],  //  服务时间 
	 *               	["contact_name"],  // 合作联系人 
	 *               	["contact_email"],  // 合作邮箱 
	 *               	["contact_tel"],  // 合作电话 
	 *               	["contact_qq"],  // 合作QQ 
	 *               	["qr_wxapp"],  // 小程序二维码 
	 *               	["qr_wxpub"],  // 订阅号二维码 
	 *               	["name_wxpub"],  // 订阅号名称 
	 *               	["qr_wxse"],  // 服务号二维码 
	 *               	["name_wxse"],  // 服务号名称 
	 *               	["qr_android"],  // 安卓应用二维码 
	 *               	["qr_ios"],  // 苹果应用二维码 
	 *               	["status"],  // 状态 
	 *               	["user"],  // 操作者 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.created_at","site.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按配制ID
		if ( !empty($data["site_id"]) ) {
			
			$keys = explode(',', $data["site_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Siteconf;
				return $inst->getInBySiteId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Siteconf;
			return $inst->getBySiteId($data["site_id"], $select);
		}

		// 按配制别名
		if ( !empty($data["site_slug"]) ) {
			
			$keys = explode(',', $data["site_slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Siteconf;
				return $inst->getInBySiteSlug($keys, $select);
			}

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
	 *               $data['site_slogen'] 网站Slogen
	 *               $data['icon'] 网站图标
	 *               $data['icon_light'] 浅色图标
	 *               $data['icon_dark'] 深色图标
	 *               $data['site_intro'] 网站简介
	 *               $data['site_homepage'] 官网地址
	 *               $data['site_downloadpage'] 应用下载地址
	 *               $data['site_logo'] 网站LOGO
	 *               $data['site_logo_light'] 浅色LOGO
	 *               $data['site_logo_dark'] 深色LOGO
	 *               $data['site_no'] 网站备案号
	 *               $data['company'] 公司名称
	 *               $data['address'] 公司地址
	 *               $data['tel'] 客服电话
	 *               $data['qq'] 客服QQ
	 *               $data['email'] 客服邮箱
	 *               $data['se_time']  服务时间
	 *               $data['contact_name'] 合作联系人
	 *               $data['contact_email'] 合作邮箱
	 *               $data['contact_tel'] 合作电话
	 *               $data['contact_qq'] 合作QQ
	 *               $data['qr_wxapp'] 小程序二维码
	 *               $data['qr_wxpub'] 订阅号二维码
	 *               $data['name_wxpub'] 订阅号名称
	 *               $data['qr_wxse'] 服务号二维码
	 *               $data['name_wxse'] 服务号名称
	 *               $data['qr_android'] 安卓应用二维码
	 *               $data['qr_ios'] 苹果应用二维码
	 *               $data['status'] 状态
	 *               $data['user'] 操作者
	 *
	 * @return array 新增的站点配置记录  @see get()
	 */
	protected function create( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		if (empty($data['site_id'])) {
			throw new Excp("缺少必填字段配制ID (site_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['site_slug'])) {
			throw new Excp("缺少必填字段配制别名 (site_slug)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\Pages\Model\Siteconf;
		$rs = $inst->create( $data );
		return $inst->getBySiteId($rs["site_id"]);
	}


	/**
	 * 更新一条站点配置记录
	 * @param  array $query GET 参数
	 * 				 $query['name=site_id']  按更新
	 * 				 $query['name=site_slug']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['site_id'] 配制ID
	 *               $data['site_slug'] 配制别名
	 *               $data['position'] 呈现位置
	 *               $data['site_name'] 网站名称
	 *               $data['site_slogen'] 网站Slogen
	 *               $data['icon'] 网站图标
	 *               $data['icon_light'] 浅色图标
	 *               $data['icon_dark'] 深色图标
	 *               $data['site_intro'] 网站简介
	 *               $data['site_homepage'] 官网地址
	 *               $data['site_downloadpage'] 应用下载地址
	 *               $data['site_logo'] 网站LOGO
	 *               $data['site_logo_light'] 浅色LOGO
	 *               $data['site_logo_dark'] 深色LOGO
	 *               $data['site_no'] 网站备案号
	 *               $data['company'] 公司名称
	 *               $data['address'] 公司地址
	 *               $data['tel'] 客服电话
	 *               $data['qq'] 客服QQ
	 *               $data['email'] 客服邮箱
	 *               $data['se_time']  服务时间
	 *               $data['contact_name'] 合作联系人
	 *               $data['contact_email'] 合作邮箱
	 *               $data['contact_tel'] 合作电话
	 *               $data['contact_qq'] 合作QQ
	 *               $data['qr_wxapp'] 小程序二维码
	 *               $data['qr_wxpub'] 订阅号二维码
	 *               $data['name_wxpub'] 订阅号名称
	 *               $data['qr_wxse'] 服务号二维码
	 *               $data['name_wxse'] 服务号名称
	 *               $data['qr_android'] 安卓应用二维码
	 *               $data['qr_ios'] 苹果应用二维码
	 *               $data['status'] 状态
	 *               $data['user'] 操作者
	 *
	 * @return array 更新的站点配置记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		// secret校验，一般用于小程序 & 移动应用
		$this->authSecret($query['_secret']);  

		// 按配制ID
		if ( !empty($query["site_id"]) ) {
			$data = array_merge( $data, ["site_id"=>$query["site_id"]] );
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			$rs = $inst->updateBy("site_id",$data);
			return $inst->getBySiteId($rs["site_id"]);
		}

		// 按配制别名
		if ( !empty($query["site_slug"]) ) {
			$data = array_merge( $data, ["site_slug"=>$query["site_slug"]] );
			$inst = new \Xpmsns\Pages\Model\Siteconf;
			$rs = $inst->updateBy("site_slug",$data);
			return $inst->getBySiteSlug($rs["site_slug"]);
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
	 *         	      $query['select'] 选取字段，默认选择 ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.created_at","site.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["site_id"] 按配制ID查询 ( AND = )
	 *			      $query["site_slug"] 按配制别名查询 ( AND = )
	 *			      $query["site_name"] 按网站名称查询 ( AND LIKE )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["position"] 按呈现位置查询 ( AND = )
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=site_id","name=site_slug","name=position","name=site_name","name=site_slogen","name=icon_light","name=icon_dark","name=site_intro","name=site_homepage","name=site_downloadpage","name=site_logo","name=site_logo_light","name=site_logo_dark","name=site_no","name=company","name=address","name=tel","name=qr_wxapp","name=qr_wxpub","name=qr_wxse","name=qr_android","name=qr_ios","name=status","name=created_at","name=updated_at"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["site_id"] 按配制ID查询 ( AND = )
	 *			      $data["site_slug"] 按配制别名查询 ( AND = )
	 *			      $data["site_name"] 按网站名称查询 ( AND LIKE )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["position"] 按呈现位置查询 ( AND = )
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *
	 * @return array 站点配置记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["site_id"],  // 配制ID 
	 *               	["site_slug"],  // 配制别名 
	 *               	["position"],  // 呈现位置 
	 *               	["site_name"],  // 网站名称 
	 *               	["site_slogen"],  // 网站Slogen 
	 *               	["icon"],  // 网站图标 
	 *               	["icon_light"],  // 浅色图标 
	 *               	["icon_dark"],  // 深色图标 
	 *               	["site_intro"],  // 网站简介 
	 *               	["site_homepage"],  // 官网地址 
	 *               	["site_downloadpage"],  // 应用下载地址 
	 *               	["site_logo"],  // 网站LOGO 
	 *               	["site_logo_light"],  // 浅色LOGO 
	 *               	["site_logo_dark"],  // 深色LOGO 
	 *               	["site_no"],  // 网站备案号 
	 *               	["company"],  // 公司名称 
	 *               	["address"],  // 公司地址 
	 *               	["tel"],  // 客服电话 
	 *               	["qq"],  // 客服QQ 
	 *               	["email"],  // 客服邮箱 
	 *               	["se_time"],  //  服务时间 
	 *               	["contact_name"],  // 合作联系人 
	 *               	["contact_email"],  // 合作邮箱 
	 *               	["contact_tel"],  // 合作电话 
	 *               	["contact_qq"],  // 合作QQ 
	 *               	["qr_wxapp"],  // 小程序二维码 
	 *               	["qr_wxpub"],  // 订阅号二维码 
	 *               	["name_wxpub"],  // 订阅号名称 
	 *               	["qr_wxse"],  // 服务号二维码 
	 *               	["name_wxse"],  // 服务号名称 
	 *               	["qr_android"],  // 安卓应用二维码 
	 *               	["qr_ios"],  // 苹果应用二维码 
	 *               	["status"],  // 状态 
	 *               	["user"],  // 操作者 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.created_at","site.updated_at"] : $data['select'];
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