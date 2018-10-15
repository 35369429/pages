<?php
/**
 * Class Expert 
 * 专栏数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-10-15 18:58:12
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
               

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Expert extends Api {

	/**
	 * 专栏数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条专栏记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["expert.expert_id","expert.type","expert.name","expert.path","expert.summary","expert.param","expert.docs","expert.status","expert.created_at","expert.updated_at","c.category_id","c.name","u.user_id","u.name"]
	 * 				 $query['expert_id']  按查询 (多条用 "," 分割)
	 * 				 $query['path']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["expert.expert_id","expert.type","expert.name","expert.path","expert.summary","expert.param","expert.docs","expert.status","expert.created_at","expert.updated_at","c.category_id","c.name","u.user_id","u.name"]
	 * 				 $data['expert_id']  按查询 (多条用 "," 分割)
	 * 				 $data['path']  按查询 (多条用 "," 分割)
	 *
	 * @return array 专栏记录 Key Value 结构数据 
	 *               	["expert_id"],  // 专栏ID 
	 *               	["user_id"],  // 用户ID 
	*               	["u_user_id"], // user.user_id
	 *               	["type"],  // 专栏类型 
	 *               	["name"],  // 专栏名称 
	 *               	["path"],  // 专栏地址 
	 *               	["category_ids"],  // 专注领域 
	*               	["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *               	["summary"],  // 简介 
	 *               	["param"],  // 参数 
	 *               	["docs"],  // 证明材料 
	 *               	["status"],  // 状态 
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
	*               	["u_created_at"], // user.created_at
	*               	["u_updated_at"], // user.updated_at
	*               	["u_group_id"], // user.group_id
	*               	["u_name"], // user.name
	*               	["u_idno"], // user.idno
	*               	["u_iddoc"], // user.iddoc
	*               	["u_nickname"], // user.nickname
	*               	["u_sex"], // user.sex
	*               	["u_city"], // user.city
	*               	["u_province"], // user.province
	*               	["u_country"], // user.country
	*               	["u_headimgurl"], // user.headimgurl
	*               	["u_language"], // user.language
	*               	["u_birthday"], // user.birthday
	*               	["u_mobile"], // user.mobile
	*               	["u_mobile_nation"], // user.mobile_nation
	*               	["u_mobile_full"], // user.mobile_full
	*               	["u_email"], // user.email
	*               	["u_contact_name"], // user.contact_name
	*               	["u_contact_tel"], // user.contact_tel
	*               	["u_title"], // user.title
	*               	["u_company"], // user.company
	*               	["u_zip"], // user.zip
	*               	["u_address"], // user.address
	*               	["u_remark"], // user.remark
	*               	["u_tag"], // user.tag
	*               	["u_user_verified"], // user.user_verified
	*               	["u_name_verified"], // user.name_verified
	*               	["u_verify"], // user.verify
	*               	["u_verify_data"], // user.verify_data
	*               	["u_mobile_verified"], // user.mobile_verified
	*               	["u_email_verified"], // user.email_verified
	*               	["u_extra"], // user.extra
	*               	["u_password"], // user.password
	*               	["u_pay_password"], // user.pay_password
	*               	["u_status"], // user.status
	*               	["u_bio"], // user.bio
	*               	["u_bgimgurl"], // user.bgimgurl
	*               	["u_idtype"], // user.idtype
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["expert.expert_id","expert.type","expert.name","expert.path","expert.summary","expert.param","expert.docs","expert.status","expert.created_at","expert.updated_at","c.category_id","c.name","u.user_id","u.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按专栏ID
		if ( !empty($data["expert_id"]) ) {
			
			$keys = explode(',', $data["expert_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Expert;
				return $inst->getInByExpertId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Expert;
			return $inst->getByExpertId($data["expert_id"], $select);
		}

		// 按专栏地址
		if ( !empty($data["path"]) ) {
			
			$keys = explode(',', $data["path"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Expert;
				return $inst->getInByPath($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Expert;
			return $inst->getByPath($data["path"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条专栏记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['expert_id'] 专栏ID
	 *               $data['user_id'] 用户ID
	 *               $data['type'] 专栏类型
	 *               $data['name'] 专栏名称
	 *               $data['path'] 专栏地址
	 *               $data['category_ids'] 专注领域
	 *               $data['summary'] 简介
	 *               $data['param'] 参数
	 *               $data['docs'] 证明材料
	 *               $data['status'] 状态
	 *
	 * @return array 新增的专栏记录  @see get()
	 */
	protected function create( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}


		$inst = new \Xpmsns\Pages\Model\Expert;
		$rs = $inst->create( $data );
		return $inst->getByExpertId($rs["expert_id"]);
	}


	/**
	 * 更新一条专栏记录
	 * @param  array $query GET 参数
	 * 				 $query['name=expert_id']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['expert_id'] 专栏ID
	 *               $data['user_id'] 用户ID
	 *               $data['type'] 专栏类型
	 *               $data['name'] 专栏名称
	 *               $data['path'] 专栏地址
	 *               $data['category_ids'] 专注领域
	 *               $data['summary'] 简介
	 *               $data['param'] 参数
	 *               $data['docs'] 证明材料
	 *               $data['status'] 状态
	 *
	 * @return array 更新的专栏记录 @see get()
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

		// 按专栏ID
		if ( !empty($query["expert_id"]) ) {
			$data = array_merge( $data, ["expert_id"=>$query["expert_id"]] );
			$inst = new \Xpmsns\Pages\Model\Expert;
			$rs = $inst->updateBy("expert_id",$data);
			return $inst->getByExpertId($rs["expert_id"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 删除一条专栏记录
	 * @param  array $query GET 参数
	 * 				 $query['expert_id']  按专栏ID 删除
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

		// 按专栏ID
		if ( !empty($query["expert_id"]) ) {
			$inst = new \Xpmsns\Pages\Model\Expert;
			$resp = $inst->remove($query['expert_id'], "expert_id");
			if ( $resp ) {
				return ["code"=>0, "message"=>"删除成功"];
			}
			throw new Excp("删除失败", 500, ['query'=>$query, 'data'=>$data, 'response'=>$resp]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


	/**
	 * 根据条件检索专栏记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["expert.expert_id","expert.type","expert.name","expert.path","expert.summary","expert.param","expert.status","expert.created_at","expert.updated_at","c.category_id","c.name","u.user_id","u.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["expert_id"] 按专栏ID查询 ( AND = )
	 *			      $query["expert_id"] 按专栏ID查询 ( AND IN )
	 *			      $query["param"] 按参数查询 ( AND = )
	 *			      $query["path"] 按专栏地址查询 ( AND = )
	 *			      $query["uname"] 按查询 ( AND LIKE )
	 *			      $query["name"] 按专栏名称查询 ( AND LIKE )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=expert_id","name=type","name=name","name=path","name=summary","name=param","name=status","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CUser%5CModel%5CUser&name=user_id&table=user&prefix=xpmsns_user_&alias=u&type=leftJoin","model=%5CXpmsns%5CUser%5CModel%5CUser&name=name&table=user&prefix=xpmsns_user_&alias=u&type=leftJoin"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["expert_id"] 按专栏ID查询 ( AND = )
	 *			      $data["expert_id"] 按专栏ID查询 ( AND IN )
	 *			      $data["param"] 按参数查询 ( AND = )
	 *			      $data["path"] 按专栏地址查询 ( AND = )
	 *			      $data["uname"] 按查询 ( AND LIKE )
	 *			      $data["name"] 按专栏名称查询 ( AND LIKE )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $data["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
	 *
	 * @return array 专栏记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["expert_id"],  // 专栏ID 
	 *               	["user_id"],  // 用户ID 
	*               	["u_user_id"], // user.user_id
	 *               	["type"],  // 专栏类型 
	 *               	["name"],  // 专栏名称 
	 *               	["path"],  // 专栏地址 
	 *               	["category_ids"],  // 专注领域 
	*               	["category"][$category_ids[n]]["category_id"], // category.category_id
	 *               	["summary"],  // 简介 
	 *               	["param"],  // 参数 
	 *               	["docs"],  // 证明材料 
	 *               	["status"],  // 状态 
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
	*               	["u_created_at"], // user.created_at
	*               	["u_updated_at"], // user.updated_at
	*               	["u_group_id"], // user.group_id
	*               	["u_name"], // user.name
	*               	["u_idno"], // user.idno
	*               	["u_iddoc"], // user.iddoc
	*               	["u_nickname"], // user.nickname
	*               	["u_sex"], // user.sex
	*               	["u_city"], // user.city
	*               	["u_province"], // user.province
	*               	["u_country"], // user.country
	*               	["u_headimgurl"], // user.headimgurl
	*               	["u_language"], // user.language
	*               	["u_birthday"], // user.birthday
	*               	["u_mobile"], // user.mobile
	*               	["u_mobile_nation"], // user.mobile_nation
	*               	["u_mobile_full"], // user.mobile_full
	*               	["u_email"], // user.email
	*               	["u_contact_name"], // user.contact_name
	*               	["u_contact_tel"], // user.contact_tel
	*               	["u_title"], // user.title
	*               	["u_company"], // user.company
	*               	["u_zip"], // user.zip
	*               	["u_address"], // user.address
	*               	["u_remark"], // user.remark
	*               	["u_tag"], // user.tag
	*               	["u_user_verified"], // user.user_verified
	*               	["u_name_verified"], // user.name_verified
	*               	["u_verify"], // user.verify
	*               	["u_verify_data"], // user.verify_data
	*               	["u_mobile_verified"], // user.mobile_verified
	*               	["u_email_verified"], // user.email_verified
	*               	["u_extra"], // user.extra
	*               	["u_password"], // user.password
	*               	["u_pay_password"], // user.pay_password
	*               	["u_status"], // user.status
	*               	["u_bio"], // user.bio
	*               	["u_bgimgurl"], // user.bgimgurl
	*               	["u_idtype"], // user.idtype
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["expert.expert_id","expert.type","expert.name","expert.path","expert.summary","expert.param","expert.status","expert.created_at","expert.updated_at","c.category_id","c.name","u.user_id","u.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Expert;
		return $inst->search( $data );
	}


}