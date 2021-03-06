<?php
/**
 * Class Event 
 * 活动数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-05-10 11:02:53
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                                          

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Event extends Api {

	/**
	 * 活动数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN

     /**
     * 已报名用户
     * @method POST /_api/xpmsns/pages/event/getEnteredUsers
     */
    function getEnteredUsers($query, $data ) {

        $event_id = $query["event_id"];
        if ( empty($event_id) ) {
            throw new Excp("请提供报名的活动ID", 402, ["query"=>$query, "data"=>$data]);
        }

        if ( empty($query["select"]) ) {
            $query["select"]  = [
                "user.nickname","user.name","user.company","user.headimgurl", "user.user_id",
                "userevent.signin_at", "userevent.status"
            ];
        }

        unset($query["event_id"]);
        $evt = new \Xpmsns\Pages\Model\Event;
        return $evt->getEnteredUsers( $event_id, $query);
    }


    /**
     * 活动报名
     * @method POST /_api/xpmsns/pages/event/entry
     */
    function enter($query, $data ) {

        $event_id = $data["event_id"];
        if ( empty($event_id) ) {
            throw new Excp("请提供报名的活动ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $evt = new \Xpmsns\Pages\Model\Event;
        return $evt->enter( $event_id, $user_id );
    }


    /**
	 * 创建一个新用户并报名
	 * @param  array  $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]		[description]
	 */
	protected function signup( $query=[], $data=[] ) {

        $event_id = $data["event_id"];
        if ( empty($event_id) ) {
            throw new Excp("请提供报名的活动ID", 402, ["query"=>$query, "data"=>$data]);
        }

		// return ["message"=>"注册成功", "code"=>0 ]; // 快速测试
		
		$this->authVcode();

		$opt =  new \Xpmse\Option("xpmsns/user");
		$options = $opt->getAll();
		$map = $options['map'];		

		// 校验手机号码
		if ( empty($data['mobile']) ) {
			throw new Excp("手机号码格式不正确", 402, ['data'=>$data, 'errorlist'=>[['mobile'=>'手机号码格式不正确']]]);
		}

		$data['mobile_nation'] = !empty($data['nation']) ? $data['nation'] : '86';

        $u = new \Xpmsns\User\Model\User();
        $mobile = $data["mobile"];
        $nation  = !empty($data['nation']) ? $data['nation'] : '86';
        $password = $data['password'];

		// 校验短信验证码
		if( $map['user/sms/on'] == 1) {
            $data['mobile_verified'] = true;
            $smscode = $data['smscode'];
			if ( $u->verifySMSCode($mobile, $smscode, $nation) === false) {
				throw new Excp("短信验证码不正确", 402, ['data'=>$data, 'errorlist'=>[['smscode'=>'短信验证码不正确']]]);
			}
		}

		// 检查密码
		if ( isset($data['repassword']) ) { 
			if ( $data['password'] != $data['repassword'] ) {
				throw new Excp("两次输入的密码不一致", 402, ['data'=>$data, 'errorlist'=>[['repassword'=>'两次输入的密码不一致']]]);
			}
		}
		if ( empty($data['password']) ) {
			throw new Excp("请输入登录密码", 402, ['data'=>$data, 'errorlist'=>[['password'=>'请输入登录密码']]]);
		}

		// Group
		$g = new \Xpmsns\User\Model\Group();
		if ( isset( $data['group_slug']) ) { 	
			$slug = $data['group_slug'];
			$rs = $g->getBySlug($slug);
			$data['group_id'] = $rs['group_id'];
		}  

		if ( empty($data['group_id']) ) {
			$slug = $map['user/default/group'];
			$rs = $g->getBySlug($slug);
			$data['group_id'] = $rs['group_id'];
        }

        
        // 邀请注册
        $inviter = $u->getInviter();
        if ( !empty($inviter) ) {
            $data["inviter"] = $inviter["user_id"];
        }

		// 数据入库
		try {
			$resp = $u->create($data);
		} catch(Excp $e ){
			if ( $e->getCode() == '1062') {
                
                // Autologin
                $u->login($mobile, $password, $nation);
                $resp = $u->getUserInfo();
				// throw new Excp("手机号 +{$data['mobile_nation']} {$data['mobile']} 已被注册", 402, ['data'=>$data, 'errorlist'=>[['mobile'=>'手机号码已被注册']]]);
			} else {
                throw $e;
            }
        }
        
        if ( !empty($resp) ) {
            try {  // 触发用户注册行为
                $resp["inviter"] = $inviter;
                \Xpmsns\User\Model\Behavior::trigger("xpmsns/user/user/signup", $resp);
            }catch(Excp $e) { $e->log(); }
        }

        $user_id = $resp["user_id"];

        // 自动登录
        $u->loginSetSession($user_id);

        // 活动报名
        $evt = new \Xpmsns\Pages\Model\Event;
        $evt->enter( $event_id, $user_id );

		return ["message"=>"注册成功", "code"=>0 ];
	}


    /**
     * 取消活动报名
     * @method POST /_api/xpmsns/pages/event/entry
     */
    function cancelEnter($query, $data ) {

        $event_id = $data["event_id"];
        if ( empty($event_id) ) {
            throw new Excp("请提供报名的活动ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $evt = new \Xpmsns\Pages\Model\Event;
        return $evt->cancelEnter( $event_id, $user_id );
    }


    /**
     * 附加用户信息读取
     */
    protected function get( $query, $data ) {

        // 读取用户信息
        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];

		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["event.event_id","event.slug","event.title","event.link","event.categories","event.series","event.type","event.tags","event.summary","event.cover","event.images","event.begin","event.end","event.process_setting","event.process","event.area","event.prov","event.city","event.town","event.location","event.price","event.bonus","event.prize","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.content","event.publish_time","event.view_cnt","event.like_cnt","event.agree_cnt","event.dislike_cnt","event.comment_cnt","event.status","event.created_at","event.updated_at","category.category_id","category.slug","category.name","series.series_id","series.name","series.slug"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按活动ID
		if ( !empty($data["event_id"]) ) {
			
			$keys = explode(',', $data["event_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Event;
				return $inst->getInByEventId($keys, $select);
			}

            $inst = new \Xpmsns\Pages\Model\Event;
            $rs = $inst->getByEventId($data["event_id"], $select);
            if ( !empty($user_id) && !empty($rs) ) {
                $row = [$rs];
                $inst->withEnter( $row, $user_id);
                return current( $row );
            }
			return $rs;
		}

		// 按别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Event;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Event;
            $rs = $inst->getBySlug($data["slug"], $select);
            if ( !empty($user_id) && !empty($rs) ) {
                $row = [$rs];
                $inst->withEnter( $row, $user_id );
                return current( $row );
            }
			return $rs;
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}


    /**
     * 检索数据
     */
    protected function search( $query, $data ) {

        // 读取用户信息
        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];

		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["event.event_id","event.slug","event.title","event.link","event.categories","event.series","event.type","event.tags","event.summary","event.cover","event.images","event.begin","event.end","event.process_setting","event.process","event.area","event.prov","event.city","event.town","event.location","event.price","event.bonus","event.prize","event.hosts","event.organizers","event.sponsors","event.medias","event.speakers","event.publish_time","event.view_cnt","event.like_cnt","event.dislike_cnt","event.comment_cnt","event.created_at","event.updated_at","category.category_id","category.slug","category.name","series.series_id","series.name","series.slug"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Event;
        $response = $inst->search( $data );
        if ( !empty($user_id) && $response["total"] > 0 ) {
            $inst->withEnter( $response["data"], $user_id );
        }
        return $response;
    }


    /**
     * 读取已报名活动
     */
    protected function userEvents( $query, $data ) {

        // 读取用户信息
        $user = \Xpmsns\User\Model\User::info();
        $user_id = $user["user_id"];

        if ( empty($user_id) ){
            throw new Excp("用户尚未登录", 404, ["query"=>$query]);
        }

        // 支持POST和GET查询
		$data = array_merge( $query, $data );

        $inst = new \Xpmsns\Pages\Model\Event;
        $response = $inst->getUserEvents( $user_id, $data );
        return $response;
    }
    
    // @KEEP END








}