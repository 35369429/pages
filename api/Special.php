<?php
/**
 * Class Special 
 * 专栏数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-09 14:01:52
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                      

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Special extends Api {

	/**
	 * 专栏数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */
    // @KEEP BEGIN


    /**
     * 重新开通专栏申请
     */
    protected function resetMySpecial( $query, $data ) {
        
        // 用户身份验证
		$u = new \Xpmsns\User\Model\User();
		$uinfo = $u->getUserInfo();
		if ( empty($uinfo['user_id']) ) {
			throw new Excp("用户尚未登录", 403,  ['user'=>$uinfo]);
        }
        
        // SaveData 检查专栏是否存在, 存在则更新，不存在则创建
		$sp = new \Xpmsns\Pages\Model\Special();
		$us = $sp->query()->where('user_id', $uinfo['user_id'])->limit(1)->select('special_id')->get()->toArray();
		if ( empty($us) ) { 
            throw new Excp("专栏不存在", 404,["user_id"=>$user_id]);
        }

        $special = $sp->updateBy("special_id",[
            "special_id" => current($us)["special_id"],
            "status" => "reset"
        ]);

        if ( !empty($query["then"] )){
            header("Location: {$query['then']}");
            return;
        }

        // 更新状态
        return $special;
    }
	

	/**
	 * 我的专栏信息更新/开通 API
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function updateMySpecial( $query, $data ) {

		// 用户身份验证
		$u = new \Xpmsns\User\Model\User();
		$uinfo = $u->getUserInfo();
		if ( empty($uinfo['user_id']) ) {
			throw new Excp("用户尚未登录", 403,  ['user'=>$uinfo]);
		}

		// 许可字段清单
		$allowed = ["type", "name", "path", "logo", "category_ids", "summary", "docs"];
		$data = array_filter(
			$data,
			function ($key) use ($allowed) {
				return in_array($key, $allowed);
			},
			ARRAY_FILTER_USE_KEY
		);

		// handle files field
		if ( array_key_exists('logo', $data) && is_string($data['logo']) ) {
			$data['logo'] = json_decode($data['logo'], true);
		}
		if ( array_key_exists('docs', $data) && is_string($data['docs']) ) {
			$data['docs'] = json_decode($data['docs'], true);
		}

		// User ID 
		$data['user_id'] = $uinfo['user_id'];

        // 专栏状态
        $data["status"] = "pending";
		
		// SaveData 检查专栏是否存在, 存在则更新，不存在则创建
		$sp = new \Xpmsns\Pages\Model\Special();
		$us = $sp->query()->where('user_id', $data['user_id'])->limit(1)->select('special_id')->get()->toArray();
		if ( empty($us) ) { 
			$us = $sp->create($data);
		} else { 
			$data["special_id"] = current($us)["special_id"];
			$us = $sp->updateBy("special_id", $data);
		}

		return $us;
	}

    // @KEEP END



	/**
	 * 查询一条专栏记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["special.type","special.name","special.path","special.summary","special.param","special.docs","special.status","special.created_at","special.updated_at","c.category_id","c.name","r.recommend_id","u.user_id","u.name"]
	 * 				 $query['special_id']  按查询 (多条用 "," 分割)
	 * 				 $query['path']  按查询 (多条用 "," 分割)
	 * 				 $query['user_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["special.type","special.name","special.path","special.summary","special.param","special.docs","special.status","special.created_at","special.updated_at","c.category_id","c.name","r.recommend_id","u.user_id","u.name"]
	 * 				 $data['special_id']  按查询 (多条用 "," 分割)
	 * 				 $data['path']  按查询 (多条用 "," 分割)
	 * 				 $data['user_id']  按查询 (多条用 "," 分割)
	 *
	 * @return array 专栏记录 Key Value 结构数据 
	 *               	["special_id"],  // 专栏ID 
	 *               	["user_id"],  // 用户ID 
	*               	["user_user_id"], // user.user_id
	 *               	["type"],  // 专栏类型 
	 *               	["name"],  // 专栏名称 
	 *               	["path"],  // 专栏地址 
	 *               	["logo"],  // 专栏LOGO 
	 *               	["category_ids"],  // 内容类目 
	*               	["_map_category"][$category_ids[n]]["category_id"], // category.category_id
	 *               	["recommend_ids"],  // 推荐内容 
	*               	["_map_recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *               	["summary"],  // 简介 
	 *               	["param"],  // 参数 
	 *               	["docs"],  // 申请材料 
	 *               	["status"],  // 状态 
	 *               	["message"],  // 消息 
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
	*               	["user_created_at"], // user.created_at
	*               	["user_updated_at"], // user.updated_at
	*               	["user_group_id"], // user.group_id
	*               	["user_name"], // user.name
	*               	["user_idno"], // user.idno
	*               	["user_idtype"], // user.idtype
	*               	["user_iddoc"], // user.iddoc
	*               	["user_nickname"], // user.nickname
	*               	["user_sex"], // user.sex
	*               	["user_city"], // user.city
	*               	["user_province"], // user.province
	*               	["user_country"], // user.country
	*               	["user_headimgurl"], // user.headimgurl
	*               	["user_language"], // user.language
	*               	["user_birthday"], // user.birthday
	*               	["user_bio"], // user.bio
	*               	["user_bgimgurl"], // user.bgimgurl
	*               	["user_mobile"], // user.mobile
	*               	["user_mobile_nation"], // user.mobile_nation
	*               	["user_mobile_full"], // user.mobile_full
	*               	["user_email"], // user.email
	*               	["user_contact_name"], // user.contact_name
	*               	["user_contact_tel"], // user.contact_tel
	*               	["user_title"], // user.title
	*               	["user_company"], // user.company
	*               	["user_zip"], // user.zip
	*               	["user_address"], // user.address
	*               	["user_remark"], // user.remark
	*               	["user_tag"], // user.tag
	*               	["user_user_verified"], // user.user_verified
	*               	["user_name_verified"], // user.name_verified
	*               	["user_verify"], // user.verify
	*               	["user_verify_data"], // user.verify_data
	*               	["user_mobile_verified"], // user.mobile_verified
	*               	["user_email_verified"], // user.email_verified
	*               	["user_extra"], // user.extra
	*               	["user_password"], // user.password
	*               	["user_pay_password"], // user.pay_password
	*               	["user_status"], // user.status
	*               	["user_inviter"], // user.inviter
	*               	["user_follower_cnt"], // user.follower_cnt
	*               	["user_following_cnt"], // user.following_cnt
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["special.type","special.name","special.path","special.summary","special.param","special.docs","special.status","special.message","special.created_at","special.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按专栏ID
		if ( !empty($data["special_id"]) ) {
			
			$keys = explode(',', $data["special_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Special;
				return $inst->getInBySpecialId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Special;
			return $inst->getBySpecialId($data["special_id"], $select);
		}

		// 按专栏地址
		if ( !empty($data["path"]) ) {
			
			$keys = explode(',', $data["path"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Special;
				return $inst->getInByPath($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Special;
			return $inst->getByPath($data["path"], $select);
		}

		// 按用户ID
		if ( !empty($data["user_id"]) ) {
			
			$keys = explode(',', $data["user_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Special;
				return $inst->getInByUserId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Special;
			return $inst->getByUserId($data["user_id"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条专栏记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['special_id'] 专栏ID
	 *               $data['user_id'] 用户ID
	 *               $data['type'] 专栏类型
	 *               $data['name'] 专栏名称
	 *               $data['path'] 专栏地址
	 *               $data['logo'] 专栏LOGO
	 *               $data['category_ids'] 内容类目
	 *               $data['recommend_ids'] 推荐内容
	 *               $data['summary'] 简介
	 *               $data['param'] 参数
	 *               $data['docs'] 申请材料
	 *               $data['status'] 状态
	 *               $data['message'] 消息
	 *
	 * @return array 新增的专栏记录  @see get()
	 */
	protected function create( $query, $data ) {



		$inst = new \Xpmsns\Pages\Model\Special;
		$rs = $inst->create( $data );
		return $inst->getBySpecialId($rs["special_id"]);
	}


	/**
	 * 更新一条专栏记录
	 * @param  array $query GET 参数
	 * 				 $query['name=special_id']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['special_id'] 专栏ID
	 *               $data['user_id'] 用户ID
	 *               $data['type'] 专栏类型
	 *               $data['name'] 专栏名称
	 *               $data['path'] 专栏地址
	 *               $data['logo'] 专栏LOGO
	 *               $data['category_ids'] 内容类目
	 *               $data['recommend_ids'] 推荐内容
	 *               $data['summary'] 简介
	 *               $data['param'] 参数
	 *               $data['docs'] 申请材料
	 *               $data['status'] 状态
	 *               $data['message'] 消息
	 *
	 * @return array 更新的专栏记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {


		// 按专栏ID
		if ( !empty($query["special_id"]) ) {
			$data = array_merge( $data, ["special_id"=>$query["special_id"]] );
			$inst = new \Xpmsns\Pages\Model\Special;
			$rs = $inst->updateBy("special_id",$data);
			return $inst->getBySpecialId($rs["special_id"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}




	/**
	 * 根据条件检索专栏记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["special.type","special.name","special.path","special.summary","special.param","special.status","special.created_at","special.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["special_id"] 按专栏ID查询 ( AND = )
	 *			      $query["special_id"] 按专栏ID查询 ( AND IN )
	 *			      $query["param"] 按参数查询 ( AND = )
	 *			      $query["path"] 按专栏地址查询 ( AND = )
	 *			      $query["uname"] 按查询 ( AND LIKE )
	 *			      $query["name"] 按专栏名称查询 ( AND LIKE )
	 *			      $query["type"] 按专栏类型查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=type","name=name","name=path","name=summary","name=param","name=status","name=created_at","name=updated_at"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["special_id"] 按专栏ID查询 ( AND = )
	 *			      $data["special_id"] 按专栏ID查询 ( AND IN )
	 *			      $data["param"] 按参数查询 ( AND = )
	 *			      $data["path"] 按专栏地址查询 ( AND = )
	 *			      $data["uname"] 按查询 ( AND LIKE )
	 *			      $data["name"] 按专栏名称查询 ( AND LIKE )
	 *			      $data["type"] 按专栏类型查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $data["orderby_updated_at_desc"]  按创建时间倒序 DESC 排序
	 *
	 * @return array 专栏记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["special_id"],  // 专栏ID 
	 *               	["user_id"],  // 用户ID 
	*               	["user_user_id"], // user.user_id
	 *               	["type"],  // 专栏类型 
	 *               	["name"],  // 专栏名称 
	 *               	["path"],  // 专栏地址 
	 *               	["logo"],  // 专栏LOGO 
	 *               	["category_ids"],  // 内容类目 
	*               	["category"][$category_ids[n]]["category_id"], // category.category_id
	 *               	["recommend_ids"],  // 推荐内容 
	*               	["recommend"][$recommend_ids[n]]["recommend_id"], // recommend.recommend_id
	 *               	["summary"],  // 简介 
	 *               	["param"],  // 参数 
	 *               	["docs"],  // 申请材料 
	 *               	["status"],  // 状态 
	 *               	["message"],  // 消息 
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
	*               	["user_created_at"], // user.created_at
	*               	["user_updated_at"], // user.updated_at
	*               	["user_group_id"], // user.group_id
	*               	["user_name"], // user.name
	*               	["user_idno"], // user.idno
	*               	["user_idtype"], // user.idtype
	*               	["user_iddoc"], // user.iddoc
	*               	["user_nickname"], // user.nickname
	*               	["user_sex"], // user.sex
	*               	["user_city"], // user.city
	*               	["user_province"], // user.province
	*               	["user_country"], // user.country
	*               	["user_headimgurl"], // user.headimgurl
	*               	["user_language"], // user.language
	*               	["user_birthday"], // user.birthday
	*               	["user_bio"], // user.bio
	*               	["user_bgimgurl"], // user.bgimgurl
	*               	["user_mobile"], // user.mobile
	*               	["user_mobile_nation"], // user.mobile_nation
	*               	["user_mobile_full"], // user.mobile_full
	*               	["user_email"], // user.email
	*               	["user_contact_name"], // user.contact_name
	*               	["user_contact_tel"], // user.contact_tel
	*               	["user_title"], // user.title
	*               	["user_company"], // user.company
	*               	["user_zip"], // user.zip
	*               	["user_address"], // user.address
	*               	["user_remark"], // user.remark
	*               	["user_tag"], // user.tag
	*               	["user_user_verified"], // user.user_verified
	*               	["user_name_verified"], // user.name_verified
	*               	["user_verify"], // user.verify
	*               	["user_verify_data"], // user.verify_data
	*               	["user_mobile_verified"], // user.mobile_verified
	*               	["user_email_verified"], // user.email_verified
	*               	["user_extra"], // user.extra
	*               	["user_password"], // user.password
	*               	["user_pay_password"], // user.pay_password
	*               	["user_status"], // user.status
	*               	["user_inviter"], // user.inviter
	*               	["user_follower_cnt"], // user.follower_cnt
	*               	["user_following_cnt"], // user.following_cnt
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["special.type","special.name","special.path","special.summary","special.param","special.status","special.created_at","special.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Special;
		return $inst->search( $data );
	}


}