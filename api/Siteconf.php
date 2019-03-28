<?php
/**
 * Class Siteconf 
 * 站点配置数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-28 20:36:47
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
	 *               $query['select']  读取字段, 默认 ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.se_time","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.images","site.attrs","site.params","site.header","site.footer","site.created_at","site.updated_at"]
	 * 				 $query['site_id']  按查询 (多条用 "," 分割)
	 * 				 $query['site_slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.se_time","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.images","site.attrs","site.params","site.header","site.footer","site.created_at","site.updated_at"]
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
	 *               	["images"],  // 自定义图片 
	 *               	["attrs"],  // 自定义属性 
	 *               	["params"],  // 自定义参数 
	 *               	["header"],  // 头部脚本 
	 *               	["footer"],  // 网站尾部 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.se_time","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.images","site.attrs","site.params","site.header","site.footer","site.created_at","site.updated_at"] : $data['select'];
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
	 * 根据条件检索站点配置记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.params","site.header","site.footer","site.created_at","site.updated_at"]
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
	 *         	      $data['select'] 选取字段，默认选择 ["name=site_id","name=site_slug","name=position","name=site_name","name=site_slogen","name=icon","name=icon_light","name=icon_dark","name=site_intro","name=site_homepage","name=site_downloadpage","name=site_logo","name=site_logo_light","name=site_logo_dark","name=site_no","name=company","name=address","name=tel","name=qr_wxapp","name=qr_wxpub","name=qr_wxse","name=qr_android","name=qr_ios","name=status","name=params","name=header","name=footer","name=created_at","name=updated_at"]
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
	 *               	["images"],  // 自定义图片 
	 *               	["attrs"],  // 自定义属性 
	 *               	["params"],  // 自定义参数 
	 *               	["header"],  // 头部脚本 
	 *               	["footer"],  // 网站尾部 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["site.site_id","site.site_slug","site.position","site.site_name","site.site_slogen","site.icon","site.icon_light","site.icon_dark","site.site_intro","site.site_homepage","site.site_downloadpage","site.site_logo","site.site_logo_light","site.site_logo_dark","site.site_no","site.company","site.address","site.tel","site.qr_wxapp","site.qr_wxpub","site.qr_wxse","site.qr_android","site.qr_ios","site.status","site.params","site.header","site.footer","site.created_at","site.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Siteconf;
		return $inst->search( $data );
	}


}