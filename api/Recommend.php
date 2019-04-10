<?php
/**
 * Class Recommend 
 * 推荐数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-04-10 16:31:10
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
                                                         

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Recommend extends Api {

	/**
	 * 推荐数据接口
	 */
	function __construct() {
		parent::__construct();
	}

    
    // @KEEP BEGIN

	/**
	 * 自定义函数 读取推荐文章 (即将废弃)
	 */
	protected function getArticles( $query, $data ) {
		
		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		$inst = new \Xpmsns\Pages\Model\Recommend;
		$keywords = !empty($data['keywords']) ?  explode(',',$data['keywords']) : []; 
		$page = !empty($data['page']) ?  $data['page'] : 1; 
		$perpage = !empty($data['perpage']) ?  $data['perpage'] : 20; 
      	$now = !empty($data['now']) ?  $data['now'] : null; 
         

		if ( array_key_exists('slug', $data) && !empty($data['slug']) ) {
			return $inst->getArticlesBySlug( $data['slug'], $keywords, $now, $page, $perpage);
		} else if ( array_key_exists('recommend_id', $data) && !empty($data['recommend_id']) ) {
			return $inst->getArticles( $data['recommend_id'], $keywords, $now, $page, $perpage);
		}

		throw new Excp('错误的查询参数', 402, ['query'=>$query, 'data'=>$data]);
    }
    


	/**
	 * 自定义函数 读取推荐内容
	 */
    protected function getContents( $query, $data ) {
		
		// 支持POST和GET查询
        $data = array_merge( $query, $data );

        $reco = new \Xpmsns\Pages\Model\Recommend;

        // 从缓存中读取数据

        
        // 按推荐别名读取推荐条件
        if ( !empty($data['slug']) ) {
            $rs = $reco->getBySlug( $data['slug'], "recommend.*" );
            $rows[$rs['slug']] = $rs;

        // 按推荐ID读取推荐条件
        } else if( !empty($data['recommend_id'] ) ) {
            $rs = $reco->getByRecommendId( $data['recommend_id'], "recommend.*" );
            $rows[$rs['slug']] = $rs;
        // 按推荐别名读取一组推荐条件 (多个可用 "," 分割)
        } else if (!empty($data['slugs'])) {
            if ( is_string($data['slugs']) ) {
                $data['slugs'] = array_map('trim', explode(",", $data['slugs']));
            }
            $rows = $reco->getInBySlug( $data['slugs'], "recommend.*" );

        // 按推荐ID读取一组推荐条件 (多个可用 "," 分割)
        } else if (!empty($data['recommend_ids'])) {
            if ( is_string($data['recommend_ids']) ) {
                $data['recommend_ids'] = array_map('trim', explode(",", $data['recommend_ids']));
            }
            $rowsByIds = $reco->getInByRecommendId( $data['recommend_ids'], "recommend.*" );
            foreach( $rowsByIds as $rs ) {
                $rows[$rs['slug']] = $rs;
            }
        }

        // Nonthing 
        if ( empty($rows) ) {
            throw new Excp('未找到符合条件的数据', 402, ['query'=>$query, 'data'=>$data]);
        }

        // 提供当前用户信息
        $user = \Xpmsns\User\Model\User::info();
        $responses = [];
        foreach( $rows as $slug => $rs ) {
            $queryContent = array_merge( $data, $rs );
            $queryContent["user"] = $user;
            $responses["{$slug}"] = $reco->contents( $queryContent );

            // 复制推荐字段
            $responses["{$slug}"]["recommend_id"] = $rs["recommend_id"];
            $responses["{$slug}"]["status"] = $rs["status"];
            $responses["{$slug}"]["title"] = $rs["title"];
            $responses["{$slug}"]["summary"] = $rs["summary"];
            $responses["{$slug}"]["icon"] = $rs["icon"];
            $responses["{$slug}"]["slug"] = $rs["slug"];
            $responses["{$slug}"]["type"] = $rs["type"];
            $responses["{$slug}"]["ctype"] = $rs["ctype"];
            $responses["{$slug}"]["thumb_only"] = $rs["thumb_only"];
            $responses["{$slug}"]["video_only"] = $rs["video_only"];
            $responses["{$slug}"]["period"] = $rs["period"];
            $responses["{$slug}"]["ttl"] = $rs["ttl"];
            
        }

        // 只有一条数据， 直接返回 (兼容旧的API格式)
        if ( count( $responses) == 1 )  {
            return current($responses);
        }

        return $responses;
    }
    // @KEEP END


	/**
	 * 查询一条推荐记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["recommend.recommend_id","recommend.title","recommend.slug","recommend.pos","recommend.style","recommend.type","recommend.bigdata_engine","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.series","recommend.orderby","recommend.status","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"]
	 * 				 $query['recommend_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["recommend.recommend_id","recommend.title","recommend.slug","recommend.pos","recommend.style","recommend.type","recommend.bigdata_engine","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.series","recommend.orderby","recommend.status","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"]
	 * 				 $data['recommend_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 推荐记录 Key Value 结构数据 
	 *               	["recommend_id"],  // 推荐ID 
	 *               	["title"],  // 主题 
	 *               	["summary"],  // 简介 
	 *               	["icon"],  // 图标 
	 *               	["slug"],  // 别名 
	 *               	["pos"],  // 呈现位置 
	 *               	["style"],  // 样式代码 
	 *               	["type"],  // 方式 
	 *               	["ctype"],  // 内容类型 
	 *               	["thumb_only"],  // 必须包含主题图片 
	 *               	["video_only"],  // 必须包含视频 
	 *               	["bigdata_engine"],  // 根据用户喜好推荐 
	 *               	["period"],  // 周期 
	 *               	["images"],  // 摘要图片 
	 *               	["tpl_pc"],  // PC端模板 
	 *               	["tpl_h5"],  // 手机端模板 
	 *               	["tpl_wxapp"],  // 小程序模板 
	 *               	["tpl_android"],  // 安卓模板 
	 *               	["tpl_ios"],  // iOS模板 
	 *               	["keywords"],  // 关键词 
	 *               	["series"],  // 指定系列 
	*               	["_map_series"][$series[n]]["series_id"], // series.series_id
	 *               	["categories"],  // 指定栏目 
	*               	["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *               	["topics"],  // 指定话题 
	*               	["_map_topic"][$topics[n]]["topic_id"], // topic.topic_id
	 *               	["article_select"],  // 文章字段 
	 *               	["article_status"],  // 文章状态 
	 *               	["articles"],  // 指定文章 
	*               	["_map_article"][$articles[n]]["article_id"], // article.article_id
	 *               	["exclude_articles"],  // 排除文章 
	*               	["_map_article"][$exclude_articles[n]]["article_id"], // article.article_id
	 *               	["event_select"],  // 活动字段 
	 *               	["event_status"],  // 活动状态 
	 *               	["events"],  // 指定活动 
	*               	["_map_event"][$events[n]]["event_id"], // event.event_id
	 *               	["exclude_events"],  // 排除活动 
	*               	["_map_event"][$exclude_events[n]]["event_id"], // event.event_id
	 *               	["album_select"],  // 图集字段 
	 *               	["album_status"],  // 图集状态 
	 *               	["albums"],  // 指定图集 
	*               	["_map_album"][$albums[n]]["album_id"], // album.album_id
	 *               	["exclude_albums"],  // 排除图集 
	*               	["_map_album"][$exclude_albums[n]]["album_id"], // album.album_id
	 *               	["question_select"],  // 提问字段 
	 *               	["question_status"],  // 提问状态 
	 *               	["questions"],  // 指定提问 
	*               	["_map_question"][$questions[n]]["question_id"], // question.question_id
	 *               	["exclude_questions"],  // 排除提问 
	*               	["_map_question"][$exclude_questions[n]]["question_id"], // question.question_id
	 *               	["answer_select"],  // 回答字段 
	 *               	["answer_status"],  // 回答状态 
	 *               	["answers"],  // 指定回答 
	*               	["_map_answer"][$answers[n]]["answer_id"], // answer.answer_id
	 *               	["exclude_answers"],  // 排除回答 
	*               	["_map_answer"][$exclude_answers[n]]["answer_id"], // answer.answer_id
	 *               	["goods_select"],  // 商品字段 
	 *               	["goods_status"],  // 商品状态 
	 *               	["goods"],  // 指定商品 
	*               	["_map_goods"][$goods[n]]["goods_id"], // goods.goods_id
	 *               	["exclude_goods"],  // 排除商品 
	*               	["_map_goods"][$exclude_goods[n]]["goods_id"], // goods.goods_id
	 *               	["orderby"],  // 排序方式 
	 *               	["ttl"],  // 缓存时间 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["_map_article"][$articles[n]]["created_at"], // article.created_at
	*               	["_map_article"][$articles[n]]["updated_at"], // article.updated_at
	*               	["_map_article"][$articles[n]]["outer_id"], // article.outer_id
	*               	["_map_article"][$articles[n]]["cover"], // article.cover
	*               	["_map_article"][$articles[n]]["thumbs"], // article.thumbs
	*               	["_map_article"][$articles[n]]["images"], // article.images
	*               	["_map_article"][$articles[n]]["videos"], // article.videos
	*               	["_map_article"][$articles[n]]["audios"], // article.audios
	*               	["_map_article"][$articles[n]]["title"], // article.title
	*               	["_map_article"][$articles[n]]["author"], // article.author
	*               	["_map_article"][$articles[n]]["origin"], // article.origin
	*               	["_map_article"][$articles[n]]["origin_url"], // article.origin_url
	*               	["_map_article"][$articles[n]]["summary"], // article.summary
	*               	["_map_article"][$articles[n]]["seo_title"], // article.seo_title
	*               	["_map_article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	*               	["_map_article"][$articles[n]]["seo_summary"], // article.seo_summary
	*               	["_map_article"][$articles[n]]["publish_time"], // article.publish_time
	*               	["_map_article"][$articles[n]]["update_time"], // article.update_time
	*               	["_map_article"][$articles[n]]["create_time"], // article.create_time
	*               	["_map_article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	*               	["_map_article"][$articles[n]]["sync"], // article.sync
	*               	["_map_article"][$articles[n]]["content"], // article.content
	*               	["_map_article"][$articles[n]]["ap_content"], // article.ap_content
	*               	["_map_article"][$articles[n]]["delta"], // article.delta
	*               	["_map_article"][$articles[n]]["param"], // article.param
	*               	["_map_article"][$articles[n]]["stick"], // article.stick
	*               	["_map_article"][$articles[n]]["preview"], // article.preview
	*               	["_map_article"][$articles[n]]["links"], // article.links
	*               	["_map_article"][$articles[n]]["user"], // article.user
	*               	["_map_article"][$articles[n]]["policies"], // article.policies
	*               	["_map_article"][$articles[n]]["status"], // article.status
	*               	["_map_article"][$articles[n]]["keywords"], // article.keywords
	*               	["_map_article"][$articles[n]]["view_cnt"], // article.view_cnt
	*               	["_map_article"][$articles[n]]["like_cnt"], // article.like_cnt
	*               	["_map_article"][$articles[n]]["dislike_cnt"], // article.dislike_cnt
	*               	["_map_article"][$articles[n]]["comment_cnt"], // article.comment_cnt
	*               	["_map_article"][$articles[n]]["series"], // article.series
	*               	["_map_article"][$articles[n]]["user_id"], // article.user_id
	*               	["_map_article"][$articles[n]]["policies_detail"], // article.policies_detail
	*               	["_map_article"][$articles[n]]["agree_cnt"], // article.agree_cnt
	*               	["_map_article"][$articles[n]]["priority"], // article.priority
	*               	["_map_article"][$articles[n]]["coin_view"], // article.coin_view
	*               	["_map_article"][$articles[n]]["money_view"], // article.money_view
	*               	["_map_article"][$articles[n]]["specials"], // article.specials
	*               	["_map_article"][$articles[n]]["history"], // article.history
	*               	["_map_article"][$articles[n]]["policies_comment"], // article.policies_comment
	*               	["_map_article"][$articles[n]]["policies_reward"], // article.policies_reward
	*               	["_map_article"][$articles[n]]["attachments"], // article.attachments
	*               	["_map_article"][$articles[n]]["desktop"], // article.desktop
	*               	["_map_article"][$articles[n]]["mobile"], // article.mobile
	*               	["_map_article"][$articles[n]]["app"], // article.app
	*               	["_map_article"][$articles[n]]["wxapp"], // article.wxapp
	*               	["_map_article"][$articles[n]]["style"], // article.style
	*               	["_map_article"][$exclude_articles[n]]["created_at"], // article.created_at
	*               	["_map_article"][$exclude_articles[n]]["updated_at"], // article.updated_at
	*               	["_map_article"][$exclude_articles[n]]["outer_id"], // article.outer_id
	*               	["_map_article"][$exclude_articles[n]]["cover"], // article.cover
	*               	["_map_article"][$exclude_articles[n]]["thumbs"], // article.thumbs
	*               	["_map_article"][$exclude_articles[n]]["images"], // article.images
	*               	["_map_article"][$exclude_articles[n]]["videos"], // article.videos
	*               	["_map_article"][$exclude_articles[n]]["audios"], // article.audios
	*               	["_map_article"][$exclude_articles[n]]["title"], // article.title
	*               	["_map_article"][$exclude_articles[n]]["author"], // article.author
	*               	["_map_article"][$exclude_articles[n]]["origin"], // article.origin
	*               	["_map_article"][$exclude_articles[n]]["origin_url"], // article.origin_url
	*               	["_map_article"][$exclude_articles[n]]["summary"], // article.summary
	*               	["_map_article"][$exclude_articles[n]]["seo_title"], // article.seo_title
	*               	["_map_article"][$exclude_articles[n]]["seo_keywords"], // article.seo_keywords
	*               	["_map_article"][$exclude_articles[n]]["seo_summary"], // article.seo_summary
	*               	["_map_article"][$exclude_articles[n]]["publish_time"], // article.publish_time
	*               	["_map_article"][$exclude_articles[n]]["update_time"], // article.update_time
	*               	["_map_article"][$exclude_articles[n]]["create_time"], // article.create_time
	*               	["_map_article"][$exclude_articles[n]]["baidulink_time"], // article.baidulink_time
	*               	["_map_article"][$exclude_articles[n]]["sync"], // article.sync
	*               	["_map_article"][$exclude_articles[n]]["content"], // article.content
	*               	["_map_article"][$exclude_articles[n]]["ap_content"], // article.ap_content
	*               	["_map_article"][$exclude_articles[n]]["delta"], // article.delta
	*               	["_map_article"][$exclude_articles[n]]["param"], // article.param
	*               	["_map_article"][$exclude_articles[n]]["stick"], // article.stick
	*               	["_map_article"][$exclude_articles[n]]["preview"], // article.preview
	*               	["_map_article"][$exclude_articles[n]]["links"], // article.links
	*               	["_map_article"][$exclude_articles[n]]["user"], // article.user
	*               	["_map_article"][$exclude_articles[n]]["policies"], // article.policies
	*               	["_map_article"][$exclude_articles[n]]["status"], // article.status
	*               	["_map_article"][$exclude_articles[n]]["keywords"], // article.keywords
	*               	["_map_article"][$exclude_articles[n]]["view_cnt"], // article.view_cnt
	*               	["_map_article"][$exclude_articles[n]]["like_cnt"], // article.like_cnt
	*               	["_map_article"][$exclude_articles[n]]["dislike_cnt"], // article.dislike_cnt
	*               	["_map_article"][$exclude_articles[n]]["comment_cnt"], // article.comment_cnt
	*               	["_map_article"][$exclude_articles[n]]["series"], // article.series
	*               	["_map_article"][$exclude_articles[n]]["user_id"], // article.user_id
	*               	["_map_article"][$exclude_articles[n]]["policies_detail"], // article.policies_detail
	*               	["_map_article"][$exclude_articles[n]]["agree_cnt"], // article.agree_cnt
	*               	["_map_article"][$exclude_articles[n]]["priority"], // article.priority
	*               	["_map_article"][$exclude_articles[n]]["coin_view"], // article.coin_view
	*               	["_map_article"][$exclude_articles[n]]["money_view"], // article.money_view
	*               	["_map_article"][$exclude_articles[n]]["specials"], // article.specials
	*               	["_map_article"][$exclude_articles[n]]["history"], // article.history
	*               	["_map_article"][$exclude_articles[n]]["policies_comment"], // article.policies_comment
	*               	["_map_article"][$exclude_articles[n]]["policies_reward"], // article.policies_reward
	*               	["_map_article"][$exclude_articles[n]]["attachments"], // article.attachments
	*               	["_map_article"][$exclude_articles[n]]["desktop"], // article.desktop
	*               	["_map_article"][$exclude_articles[n]]["mobile"], // article.mobile
	*               	["_map_article"][$exclude_articles[n]]["app"], // article.app
	*               	["_map_article"][$exclude_articles[n]]["wxapp"], // article.wxapp
	*               	["_map_article"][$exclude_articles[n]]["style"], // article.style
	*               	["_map_event"][$events[n]]["created_at"], // event.created_at
	*               	["_map_event"][$events[n]]["updated_at"], // event.updated_at
	*               	["_map_event"][$events[n]]["slug"], // event.slug
	*               	["_map_event"][$events[n]]["name"], // event.name
	*               	["_map_event"][$events[n]]["link"], // event.link
	*               	["_map_event"][$events[n]]["categories"], // event.categories
	*               	["_map_event"][$events[n]]["type"], // event.type
	*               	["_map_event"][$events[n]]["tags"], // event.tags
	*               	["_map_event"][$events[n]]["summary"], // event.summary
	*               	["_map_event"][$events[n]]["cover"], // event.cover
	*               	["_map_event"][$events[n]]["images"], // event.images
	*               	["_map_event"][$events[n]]["begin"], // event.begin
	*               	["_map_event"][$events[n]]["end"], // event.end
	*               	["_map_event"][$events[n]]["area"], // event.area
	*               	["_map_event"][$events[n]]["prov"], // event.prov
	*               	["_map_event"][$events[n]]["city"], // event.city
	*               	["_map_event"][$events[n]]["town"], // event.town
	*               	["_map_event"][$events[n]]["location"], // event.location
	*               	["_map_event"][$events[n]]["price"], // event.price
	*               	["_map_event"][$events[n]]["hosts"], // event.hosts
	*               	["_map_event"][$events[n]]["organizers"], // event.organizers
	*               	["_map_event"][$events[n]]["sponsors"], // event.sponsors
	*               	["_map_event"][$events[n]]["medias"], // event.medias
	*               	["_map_event"][$events[n]]["speakers"], // event.speakers
	*               	["_map_event"][$events[n]]["content"], // event.content
	*               	["_map_event"][$events[n]]["publish_time"], // event.publish_time
	*               	["_map_event"][$events[n]]["view_cnt"], // event.view_cnt
	*               	["_map_event"][$events[n]]["like_cnt"], // event.like_cnt
	*               	["_map_event"][$events[n]]["dislike_cnt"], // event.dislike_cnt
	*               	["_map_event"][$events[n]]["comment_cnt"], // event.comment_cnt
	*               	["_map_event"][$events[n]]["status"], // event.status
	*               	["_map_event"][$events[n]]["title"], // event.title
	*               	["_map_event"][$events[n]]["process_setting"], // event.process_setting
	*               	["_map_event"][$events[n]]["process"], // event.process
	*               	["_map_event"][$events[n]]["bonus"], // event.bonus
	*               	["_map_event"][$events[n]]["prize"], // event.prize
	*               	["_map_event"][$events[n]]["desktop"], // event.desktop
	*               	["_map_event"][$events[n]]["mobile"], // event.mobile
	*               	["_map_event"][$events[n]]["wxapp"], // event.wxapp
	*               	["_map_event"][$events[n]]["app"], // event.app
	*               	["_map_event"][$events[n]]["agree_cnt"], // event.agree_cnt
	*               	["_map_event"][$events[n]]["quota"], // event.quota
	*               	["_map_event"][$events[n]]["user_cnt"], // event.user_cnt
	*               	["_map_event"][$events[n]]["series"], // event.series
	*               	["_map_event"][$events[n]]["deadline"], // event.deadline
	*               	["_map_event"][$events[n]]["report"], // event.report
	*               	["_map_event"][$events[n]]["admin_link"], // event.admin_link
	*               	["_map_event"][$events[n]]["admin_name"], // event.admin_name
	*               	["_map_event"][$exclude_events[n]]["created_at"], // event.created_at
	*               	["_map_event"][$exclude_events[n]]["updated_at"], // event.updated_at
	*               	["_map_event"][$exclude_events[n]]["slug"], // event.slug
	*               	["_map_event"][$exclude_events[n]]["name"], // event.name
	*               	["_map_event"][$exclude_events[n]]["link"], // event.link
	*               	["_map_event"][$exclude_events[n]]["categories"], // event.categories
	*               	["_map_event"][$exclude_events[n]]["type"], // event.type
	*               	["_map_event"][$exclude_events[n]]["tags"], // event.tags
	*               	["_map_event"][$exclude_events[n]]["summary"], // event.summary
	*               	["_map_event"][$exclude_events[n]]["cover"], // event.cover
	*               	["_map_event"][$exclude_events[n]]["images"], // event.images
	*               	["_map_event"][$exclude_events[n]]["begin"], // event.begin
	*               	["_map_event"][$exclude_events[n]]["end"], // event.end
	*               	["_map_event"][$exclude_events[n]]["area"], // event.area
	*               	["_map_event"][$exclude_events[n]]["prov"], // event.prov
	*               	["_map_event"][$exclude_events[n]]["city"], // event.city
	*               	["_map_event"][$exclude_events[n]]["town"], // event.town
	*               	["_map_event"][$exclude_events[n]]["location"], // event.location
	*               	["_map_event"][$exclude_events[n]]["price"], // event.price
	*               	["_map_event"][$exclude_events[n]]["hosts"], // event.hosts
	*               	["_map_event"][$exclude_events[n]]["organizers"], // event.organizers
	*               	["_map_event"][$exclude_events[n]]["sponsors"], // event.sponsors
	*               	["_map_event"][$exclude_events[n]]["medias"], // event.medias
	*               	["_map_event"][$exclude_events[n]]["speakers"], // event.speakers
	*               	["_map_event"][$exclude_events[n]]["content"], // event.content
	*               	["_map_event"][$exclude_events[n]]["publish_time"], // event.publish_time
	*               	["_map_event"][$exclude_events[n]]["view_cnt"], // event.view_cnt
	*               	["_map_event"][$exclude_events[n]]["like_cnt"], // event.like_cnt
	*               	["_map_event"][$exclude_events[n]]["dislike_cnt"], // event.dislike_cnt
	*               	["_map_event"][$exclude_events[n]]["comment_cnt"], // event.comment_cnt
	*               	["_map_event"][$exclude_events[n]]["status"], // event.status
	*               	["_map_event"][$exclude_events[n]]["title"], // event.title
	*               	["_map_event"][$exclude_events[n]]["process_setting"], // event.process_setting
	*               	["_map_event"][$exclude_events[n]]["process"], // event.process
	*               	["_map_event"][$exclude_events[n]]["bonus"], // event.bonus
	*               	["_map_event"][$exclude_events[n]]["prize"], // event.prize
	*               	["_map_event"][$exclude_events[n]]["desktop"], // event.desktop
	*               	["_map_event"][$exclude_events[n]]["mobile"], // event.mobile
	*               	["_map_event"][$exclude_events[n]]["wxapp"], // event.wxapp
	*               	["_map_event"][$exclude_events[n]]["app"], // event.app
	*               	["_map_event"][$exclude_events[n]]["agree_cnt"], // event.agree_cnt
	*               	["_map_event"][$exclude_events[n]]["quota"], // event.quota
	*               	["_map_event"][$exclude_events[n]]["user_cnt"], // event.user_cnt
	*               	["_map_event"][$exclude_events[n]]["series"], // event.series
	*               	["_map_event"][$exclude_events[n]]["deadline"], // event.deadline
	*               	["_map_event"][$exclude_events[n]]["report"], // event.report
	*               	["_map_event"][$exclude_events[n]]["admin_link"], // event.admin_link
	*               	["_map_event"][$exclude_events[n]]["admin_name"], // event.admin_name
	*               	["_map_album"][$albums[n]]["created_at"], // album.created_at
	*               	["_map_album"][$albums[n]]["updated_at"], // album.updated_at
	*               	["_map_album"][$albums[n]]["slug"], // album.slug
	*               	["_map_album"][$albums[n]]["title"], // album.title
	*               	["_map_album"][$albums[n]]["author"], // album.author
	*               	["_map_album"][$albums[n]]["origin"], // album.origin
	*               	["_map_album"][$albums[n]]["origin_url"], // album.origin_url
	*               	["_map_album"][$albums[n]]["link"], // album.link
	*               	["_map_album"][$albums[n]]["categories"], // album.categories
	*               	["_map_album"][$albums[n]]["tags"], // album.tags
	*               	["_map_album"][$albums[n]]["summary"], // album.summary
	*               	["_map_album"][$albums[n]]["images"], // album.images
	*               	["_map_album"][$albums[n]]["cover"], // album.cover
	*               	["_map_album"][$albums[n]]["publish_time"], // album.publish_time
	*               	["_map_album"][$albums[n]]["view_cnt"], // album.view_cnt
	*               	["_map_album"][$albums[n]]["like_cnt"], // album.like_cnt
	*               	["_map_album"][$albums[n]]["dislike_cnt"], // album.dislike_cnt
	*               	["_map_album"][$albums[n]]["comment_cnt"], // album.comment_cnt
	*               	["_map_album"][$albums[n]]["status"], // album.status
	*               	["_map_album"][$albums[n]]["series"], // album.series
	*               	["_map_album"][$exclude_albums[n]]["created_at"], // album.created_at
	*               	["_map_album"][$exclude_albums[n]]["updated_at"], // album.updated_at
	*               	["_map_album"][$exclude_albums[n]]["slug"], // album.slug
	*               	["_map_album"][$exclude_albums[n]]["title"], // album.title
	*               	["_map_album"][$exclude_albums[n]]["author"], // album.author
	*               	["_map_album"][$exclude_albums[n]]["origin"], // album.origin
	*               	["_map_album"][$exclude_albums[n]]["origin_url"], // album.origin_url
	*               	["_map_album"][$exclude_albums[n]]["link"], // album.link
	*               	["_map_album"][$exclude_albums[n]]["categories"], // album.categories
	*               	["_map_album"][$exclude_albums[n]]["tags"], // album.tags
	*               	["_map_album"][$exclude_albums[n]]["summary"], // album.summary
	*               	["_map_album"][$exclude_albums[n]]["images"], // album.images
	*               	["_map_album"][$exclude_albums[n]]["cover"], // album.cover
	*               	["_map_album"][$exclude_albums[n]]["publish_time"], // album.publish_time
	*               	["_map_album"][$exclude_albums[n]]["view_cnt"], // album.view_cnt
	*               	["_map_album"][$exclude_albums[n]]["like_cnt"], // album.like_cnt
	*               	["_map_album"][$exclude_albums[n]]["dislike_cnt"], // album.dislike_cnt
	*               	["_map_album"][$exclude_albums[n]]["comment_cnt"], // album.comment_cnt
	*               	["_map_album"][$exclude_albums[n]]["status"], // album.status
	*               	["_map_album"][$exclude_albums[n]]["series"], // album.series
	*               	["_map_question"][$questions[n]]["created_at"], // question.created_at
	*               	["_map_question"][$questions[n]]["updated_at"], // question.updated_at
	*               	["_map_question"][$questions[n]]["user_id"], // question.user_id
	*               	["_map_question"][$questions[n]]["title"], // question.title
	*               	["_map_question"][$questions[n]]["summary"], // question.summary
	*               	["_map_question"][$questions[n]]["content"], // question.content
	*               	["_map_question"][$questions[n]]["category_ids"], // question.category_ids
	*               	["_map_question"][$questions[n]]["series_ids"], // question.series_ids
	*               	["_map_question"][$questions[n]]["tags"], // question.tags
	*               	["_map_question"][$questions[n]]["view_cnt"], // question.view_cnt
	*               	["_map_question"][$questions[n]]["agree_cnt"], // question.agree_cnt
	*               	["_map_question"][$questions[n]]["answer_cnt"], // question.answer_cnt
	*               	["_map_question"][$questions[n]]["priority"], // question.priority
	*               	["_map_question"][$questions[n]]["status"], // question.status
	*               	["_map_question"][$questions[n]]["publish_time"], // question.publish_time
	*               	["_map_question"][$questions[n]]["coin"], // question.coin
	*               	["_map_question"][$questions[n]]["money"], // question.money
	*               	["_map_question"][$questions[n]]["coin_view"], // question.coin_view
	*               	["_map_question"][$questions[n]]["money_view"], // question.money_view
	*               	["_map_question"][$questions[n]]["policies"], // question.policies
	*               	["_map_question"][$questions[n]]["policies_detail"], // question.policies_detail
	*               	["_map_question"][$questions[n]]["anonymous"], // question.anonymous
	*               	["_map_question"][$questions[n]]["cover"], // question.cover
	*               	["_map_question"][$questions[n]]["history"], // question.history
	*               	["_map_question"][$exclude_questions[n]]["created_at"], // question.created_at
	*               	["_map_question"][$exclude_questions[n]]["updated_at"], // question.updated_at
	*               	["_map_question"][$exclude_questions[n]]["user_id"], // question.user_id
	*               	["_map_question"][$exclude_questions[n]]["title"], // question.title
	*               	["_map_question"][$exclude_questions[n]]["summary"], // question.summary
	*               	["_map_question"][$exclude_questions[n]]["content"], // question.content
	*               	["_map_question"][$exclude_questions[n]]["category_ids"], // question.category_ids
	*               	["_map_question"][$exclude_questions[n]]["series_ids"], // question.series_ids
	*               	["_map_question"][$exclude_questions[n]]["tags"], // question.tags
	*               	["_map_question"][$exclude_questions[n]]["view_cnt"], // question.view_cnt
	*               	["_map_question"][$exclude_questions[n]]["agree_cnt"], // question.agree_cnt
	*               	["_map_question"][$exclude_questions[n]]["answer_cnt"], // question.answer_cnt
	*               	["_map_question"][$exclude_questions[n]]["priority"], // question.priority
	*               	["_map_question"][$exclude_questions[n]]["status"], // question.status
	*               	["_map_question"][$exclude_questions[n]]["publish_time"], // question.publish_time
	*               	["_map_question"][$exclude_questions[n]]["coin"], // question.coin
	*               	["_map_question"][$exclude_questions[n]]["money"], // question.money
	*               	["_map_question"][$exclude_questions[n]]["coin_view"], // question.coin_view
	*               	["_map_question"][$exclude_questions[n]]["money_view"], // question.money_view
	*               	["_map_question"][$exclude_questions[n]]["policies"], // question.policies
	*               	["_map_question"][$exclude_questions[n]]["policies_detail"], // question.policies_detail
	*               	["_map_question"][$exclude_questions[n]]["anonymous"], // question.anonymous
	*               	["_map_question"][$exclude_questions[n]]["cover"], // question.cover
	*               	["_map_question"][$exclude_questions[n]]["history"], // question.history
	*               	["_map_answer"][$answers[n]]["created_at"], // answer.created_at
	*               	["_map_answer"][$answers[n]]["updated_at"], // answer.updated_at
	*               	["_map_answer"][$answers[n]]["question_id"], // answer.question_id
	*               	["_map_answer"][$answers[n]]["user_id"], // answer.user_id
	*               	["_map_answer"][$answers[n]]["content"], // answer.content
	*               	["_map_answer"][$answers[n]]["publish_time"], // answer.publish_time
	*               	["_map_answer"][$answers[n]]["policies"], // answer.policies
	*               	["_map_answer"][$answers[n]]["policies_detail"], // answer.policies_detail
	*               	["_map_answer"][$answers[n]]["priority"], // answer.priority
	*               	["_map_answer"][$answers[n]]["view_cnt"], // answer.view_cnt
	*               	["_map_answer"][$answers[n]]["agree_cnt"], // answer.agree_cnt
	*               	["_map_answer"][$answers[n]]["coin"], // answer.coin
	*               	["_map_answer"][$answers[n]]["money"], // answer.money
	*               	["_map_answer"][$answers[n]]["coin_view"], // answer.coin_view
	*               	["_map_answer"][$answers[n]]["money_view"], // answer.money_view
	*               	["_map_answer"][$answers[n]]["anonymous"], // answer.anonymous
	*               	["_map_answer"][$answers[n]]["accepted"], // answer.accepted
	*               	["_map_answer"][$answers[n]]["status"], // answer.status
	*               	["_map_answer"][$answers[n]]["history"], // answer.history
	*               	["_map_answer"][$answers[n]]["summary"], // answer.summary
	*               	["_map_answer"][$exclude_answers[n]]["created_at"], // answer.created_at
	*               	["_map_answer"][$exclude_answers[n]]["updated_at"], // answer.updated_at
	*               	["_map_answer"][$exclude_answers[n]]["question_id"], // answer.question_id
	*               	["_map_answer"][$exclude_answers[n]]["user_id"], // answer.user_id
	*               	["_map_answer"][$exclude_answers[n]]["content"], // answer.content
	*               	["_map_answer"][$exclude_answers[n]]["publish_time"], // answer.publish_time
	*               	["_map_answer"][$exclude_answers[n]]["policies"], // answer.policies
	*               	["_map_answer"][$exclude_answers[n]]["policies_detail"], // answer.policies_detail
	*               	["_map_answer"][$exclude_answers[n]]["priority"], // answer.priority
	*               	["_map_answer"][$exclude_answers[n]]["view_cnt"], // answer.view_cnt
	*               	["_map_answer"][$exclude_answers[n]]["agree_cnt"], // answer.agree_cnt
	*               	["_map_answer"][$exclude_answers[n]]["coin"], // answer.coin
	*               	["_map_answer"][$exclude_answers[n]]["money"], // answer.money
	*               	["_map_answer"][$exclude_answers[n]]["coin_view"], // answer.coin_view
	*               	["_map_answer"][$exclude_answers[n]]["money_view"], // answer.money_view
	*               	["_map_answer"][$exclude_answers[n]]["anonymous"], // answer.anonymous
	*               	["_map_answer"][$exclude_answers[n]]["accepted"], // answer.accepted
	*               	["_map_answer"][$exclude_answers[n]]["status"], // answer.status
	*               	["_map_answer"][$exclude_answers[n]]["history"], // answer.history
	*               	["_map_answer"][$exclude_answers[n]]["summary"], // answer.summary
	*               	["_map_goods"][$goods[n]]["created_at"], // goods.created_at
	*               	["_map_goods"][$goods[n]]["updated_at"], // goods.updated_at
	*               	["_map_goods"][$goods[n]]["instance"], // goods.instance
	*               	["_map_goods"][$goods[n]]["name"], // goods.name
	*               	["_map_goods"][$goods[n]]["slug"], // goods.slug
	*               	["_map_goods"][$goods[n]]["tags"], // goods.tags
	*               	["_map_goods"][$goods[n]]["category_ids"], // goods.category_ids
	*               	["_map_goods"][$goods[n]]["recommend_ids"], // goods.recommend_ids
	*               	["_map_goods"][$goods[n]]["summary"], // goods.summary
	*               	["_map_goods"][$goods[n]]["cover"], // goods.cover
	*               	["_map_goods"][$goods[n]]["images"], // goods.images
	*               	["_map_goods"][$goods[n]]["videos"], // goods.videos
	*               	["_map_goods"][$goods[n]]["params"], // goods.params
	*               	["_map_goods"][$goods[n]]["content"], // goods.content
	*               	["_map_goods"][$goods[n]]["content_faq"], // goods.content_faq
	*               	["_map_goods"][$goods[n]]["content_serv"], // goods.content_serv
	*               	["_map_goods"][$goods[n]]["sku_cnt"], // goods.sku_cnt
	*               	["_map_goods"][$goods[n]]["sku_sum"], // goods.sku_sum
	*               	["_map_goods"][$goods[n]]["shipped_sum"], // goods.shipped_sum
	*               	["_map_goods"][$goods[n]]["available_sum"], // goods.available_sum
	*               	["_map_goods"][$goods[n]]["lower_price"], // goods.lower_price
	*               	["_map_goods"][$goods[n]]["sale_way"], // goods.sale_way
	*               	["_map_goods"][$goods[n]]["opened_at"], // goods.opened_at
	*               	["_map_goods"][$goods[n]]["closed_at"], // goods.closed_at
	*               	["_map_goods"][$goods[n]]["pay_duration"], // goods.pay_duration
	*               	["_map_goods"][$goods[n]]["status"], // goods.status
	*               	["_map_goods"][$goods[n]]["events"], // goods.events
	*               	["_map_goods"][$goods[n]]["priority"], // goods.priority
	*               	["_map_goods"][$exclude_goods[n]]["created_at"], // goods.created_at
	*               	["_map_goods"][$exclude_goods[n]]["updated_at"], // goods.updated_at
	*               	["_map_goods"][$exclude_goods[n]]["instance"], // goods.instance
	*               	["_map_goods"][$exclude_goods[n]]["name"], // goods.name
	*               	["_map_goods"][$exclude_goods[n]]["slug"], // goods.slug
	*               	["_map_goods"][$exclude_goods[n]]["tags"], // goods.tags
	*               	["_map_goods"][$exclude_goods[n]]["category_ids"], // goods.category_ids
	*               	["_map_goods"][$exclude_goods[n]]["recommend_ids"], // goods.recommend_ids
	*               	["_map_goods"][$exclude_goods[n]]["summary"], // goods.summary
	*               	["_map_goods"][$exclude_goods[n]]["cover"], // goods.cover
	*               	["_map_goods"][$exclude_goods[n]]["images"], // goods.images
	*               	["_map_goods"][$exclude_goods[n]]["videos"], // goods.videos
	*               	["_map_goods"][$exclude_goods[n]]["params"], // goods.params
	*               	["_map_goods"][$exclude_goods[n]]["content"], // goods.content
	*               	["_map_goods"][$exclude_goods[n]]["content_faq"], // goods.content_faq
	*               	["_map_goods"][$exclude_goods[n]]["content_serv"], // goods.content_serv
	*               	["_map_goods"][$exclude_goods[n]]["sku_cnt"], // goods.sku_cnt
	*               	["_map_goods"][$exclude_goods[n]]["sku_sum"], // goods.sku_sum
	*               	["_map_goods"][$exclude_goods[n]]["shipped_sum"], // goods.shipped_sum
	*               	["_map_goods"][$exclude_goods[n]]["available_sum"], // goods.available_sum
	*               	["_map_goods"][$exclude_goods[n]]["lower_price"], // goods.lower_price
	*               	["_map_goods"][$exclude_goods[n]]["sale_way"], // goods.sale_way
	*               	["_map_goods"][$exclude_goods[n]]["opened_at"], // goods.opened_at
	*               	["_map_goods"][$exclude_goods[n]]["closed_at"], // goods.closed_at
	*               	["_map_goods"][$exclude_goods[n]]["pay_duration"], // goods.pay_duration
	*               	["_map_goods"][$exclude_goods[n]]["status"], // goods.status
	*               	["_map_goods"][$exclude_goods[n]]["events"], // goods.events
	*               	["_map_goods"][$exclude_goods[n]]["priority"], // goods.priority
	*               	["_map_series"][$series[n]]["created_at"], // series.created_at
	*               	["_map_series"][$series[n]]["updated_at"], // series.updated_at
	*               	["_map_series"][$series[n]]["name"], // series.name
	*               	["_map_series"][$series[n]]["slug"], // series.slug
	*               	["_map_series"][$series[n]]["category_id"], // series.category_id
	*               	["_map_series"][$series[n]]["summary"], // series.summary
	*               	["_map_series"][$series[n]]["orderby"], // series.orderby
	*               	["_map_series"][$series[n]]["param"], // series.param
	*               	["_map_series"][$series[n]]["status"], // series.status
	*               	["_map_category"][$categories[n]]["created_at"], // category.created_at
	*               	["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["_map_category"][$categories[n]]["slug"], // category.slug
	*               	["_map_category"][$categories[n]]["project"], // category.project
	*               	["_map_category"][$categories[n]]["page"], // category.page
	*               	["_map_category"][$categories[n]]["wechat"], // category.wechat
	*               	["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["_map_category"][$categories[n]]["name"], // category.name
	*               	["_map_category"][$categories[n]]["fullname"], // category.fullname
	*               	["_map_category"][$categories[n]]["link"], // category.link
	*               	["_map_category"][$categories[n]]["root_id"], // category.root_id
	*               	["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["_map_category"][$categories[n]]["priority"], // category.priority
	*               	["_map_category"][$categories[n]]["hidden"], // category.hidden
	*               	["_map_category"][$categories[n]]["isnav"], // category.isnav
	*               	["_map_category"][$categories[n]]["param"], // category.param
	*               	["_map_category"][$categories[n]]["status"], // category.status
	*               	["_map_category"][$categories[n]]["issubnav"], // category.issubnav
	*               	["_map_category"][$categories[n]]["highlight"], // category.highlight
	*               	["_map_category"][$categories[n]]["isfootnav"], // category.isfootnav
	*               	["_map_category"][$categories[n]]["isblank"], // category.isblank
	*               	["_map_topic"][$topics[n]]["created_at"], // topic.created_at
	*               	["_map_topic"][$topics[n]]["updated_at"], // topic.updated_at
	*               	["_map_topic"][$topics[n]]["name"], // topic.name
	*               	["_map_topic"][$topics[n]]["param"], // topic.param
	*               	["_map_topic"][$topics[n]]["article_cnt"], // topic.article_cnt
	*               	["_map_topic"][$topics[n]]["album_cnt"], // topic.album_cnt
	*               	["_map_topic"][$topics[n]]["event_cnt"], // topic.event_cnt
	*               	["_map_topic"][$topics[n]]["goods_cnt"], // topic.goods_cnt
	*               	["_map_topic"][$topics[n]]["question_cnt"], // topic.question_cnt
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["recommend.recommend_id","recommend.title","recommend.slug","recommend.pos","recommend.style","recommend.type","recommend.bigdata_engine","recommend.period","recommend.images","recommend.tpl_pc","recommend.tpl_h5","recommend.tpl_wxapp","recommend.tpl_android","recommend.tpl_ios","recommend.keywords","recommend.series","recommend.orderby","recommend.status","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按推荐ID
		if ( !empty($data["recommend_id"]) ) {
			
			$keys = explode(',', $data["recommend_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Recommend;
				return $inst->getInByRecommendId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Recommend;
			return $inst->getByRecommendId($data["recommend_id"], $select);
		}

		// 按别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Recommend;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Recommend;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索推荐记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["recommend.recommend_id","recommend.title","recommend.pos","recommend.type","recommend.bigdata_engine","recommend.images","recommend.keywords","recommend.series","recommend.orderby","recommend.status","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["recommend_id"] 按推荐ID查询 ( AND = )
	 *			      $query["slug"] 按别名查询 ( AND IN )
	 *			      $query["pos"] 按呈现位置查询 ( AND = )
	 *			      $query["type"] 按方式查询 ( AND = )
	 *			      $query["period"] 按周期查询 ( AND = )
	 *			      $query["title"] 按主题查询 ( AND LIKE )
	 *			      $query["ctype"] 按内容类型查询 ( AND = )
	 *			      $query["thumb_only"] 按必须包含主题图片查询 ( AND = )
	 *			      $query["bigdata_engine"] 按根据用户喜好推荐查询 ( AND = )
	 *			      $query["video_only"] 按必须包含视频查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $query["orderby_updated_at_asc"]  按更新时间 ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=recommend_id","name=title","name=pos","name=type","name=bigdata_engine","name=images","name=keywords","name=series","name=orderby","name=status","name=created_at","name=updated_at","model=%5CXpmsns%5CPages%5CModel%5CArticle&name=article_id&table=article&prefix=xpmsns_pages_&alias=a&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CArticle&name=title&table=article&prefix=xpmsns_pages_&alias=a&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CEvent&name=event_id&table=event&prefix=xpmsns_pages_&alias=evt&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CEvent&name=name&table=event&prefix=xpmsns_pages_&alias=evt&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CAlbum&name=album_id&table=album&prefix=xpmsns_pages_&alias=al&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CAlbum&name=title&table=album&prefix=xpmsns_pages_&alias=al&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=category_id&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere","model=%5CXpmsns%5CPages%5CModel%5CCategory&name=name&table=category&prefix=xpmsns_pages_&alias=c&type=inWhere"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["recommend_id"] 按推荐ID查询 ( AND = )
	 *			      $data["slug"] 按别名查询 ( AND IN )
	 *			      $data["pos"] 按呈现位置查询 ( AND = )
	 *			      $data["type"] 按方式查询 ( AND = )
	 *			      $data["period"] 按周期查询 ( AND = )
	 *			      $data["title"] 按主题查询 ( AND LIKE )
	 *			      $data["ctype"] 按内容类型查询 ( AND = )
	 *			      $data["thumb_only"] 按必须包含主题图片查询 ( AND = )
	 *			      $data["bigdata_engine"] 按根据用户喜好推荐查询 ( AND = )
	 *			      $data["video_only"] 按必须包含视频查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_asc"]  按创建时间 ASC 排序
	 *			      $data["orderby_updated_at_asc"]  按更新时间 ASC 排序
	 *
	 * @return array 推荐记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["recommend_id"],  // 推荐ID 
	 *               	["title"],  // 主题 
	 *               	["summary"],  // 简介 
	 *               	["icon"],  // 图标 
	 *               	["slug"],  // 别名 
	 *               	["pos"],  // 呈现位置 
	 *               	["style"],  // 样式代码 
	 *               	["type"],  // 方式 
	 *               	["ctype"],  // 内容类型 
	 *               	["thumb_only"],  // 必须包含主题图片 
	 *               	["video_only"],  // 必须包含视频 
	 *               	["bigdata_engine"],  // 根据用户喜好推荐 
	 *               	["period"],  // 周期 
	 *               	["images"],  // 摘要图片 
	 *               	["tpl_pc"],  // PC端模板 
	 *               	["tpl_h5"],  // 手机端模板 
	 *               	["tpl_wxapp"],  // 小程序模板 
	 *               	["tpl_android"],  // 安卓模板 
	 *               	["tpl_ios"],  // iOS模板 
	 *               	["keywords"],  // 关键词 
	 *               	["series"],  // 指定系列 
	*               	["series"][$series[n]]["series_id"], // series.series_id
	 *               	["categories"],  // 指定栏目 
	*               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["topics"],  // 指定话题 
	*               	["topic"][$topics[n]]["topic_id"], // topic.topic_id
	 *               	["article_select"],  // 文章字段 
	 *               	["article_status"],  // 文章状态 
	 *               	["articles"],  // 指定文章 
	*               	["article"][$articles[n]]["article_id"], // article.article_id
	 *               	["exclude_articles"],  // 排除文章 
	*               	["article"][$exclude_articles[n]]["article_id"], // article.article_id
	 *               	["event_select"],  // 活动字段 
	 *               	["event_status"],  // 活动状态 
	 *               	["events"],  // 指定活动 
	*               	["event"][$events[n]]["event_id"], // event.event_id
	 *               	["exclude_events"],  // 排除活动 
	*               	["event"][$exclude_events[n]]["event_id"], // event.event_id
	 *               	["album_select"],  // 图集字段 
	 *               	["album_status"],  // 图集状态 
	 *               	["albums"],  // 指定图集 
	*               	["album"][$albums[n]]["album_id"], // album.album_id
	 *               	["exclude_albums"],  // 排除图集 
	*               	["album"][$exclude_albums[n]]["album_id"], // album.album_id
	 *               	["question_select"],  // 提问字段 
	 *               	["question_status"],  // 提问状态 
	 *               	["questions"],  // 指定提问 
	*               	["question"][$questions[n]]["question_id"], // question.question_id
	 *               	["exclude_questions"],  // 排除提问 
	*               	["question"][$exclude_questions[n]]["question_id"], // question.question_id
	 *               	["answer_select"],  // 回答字段 
	 *               	["answer_status"],  // 回答状态 
	 *               	["answers"],  // 指定回答 
	*               	["answer"][$answers[n]]["answer_id"], // answer.answer_id
	 *               	["exclude_answers"],  // 排除回答 
	*               	["answer"][$exclude_answers[n]]["answer_id"], // answer.answer_id
	 *               	["goods_select"],  // 商品字段 
	 *               	["goods_status"],  // 商品状态 
	 *               	["goods"],  // 指定商品 
	*               	["goods"][$goods[n]]["goods_id"], // goods.goods_id
	 *               	["exclude_goods"],  // 排除商品 
	*               	["goods"][$exclude_goods[n]]["goods_id"], // goods.goods_id
	 *               	["orderby"],  // 排序方式 
	 *               	["ttl"],  // 缓存时间 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["article"][$articles[n]]["created_at"], // article.created_at
	*               	["article"][$articles[n]]["updated_at"], // article.updated_at
	*               	["article"][$articles[n]]["outer_id"], // article.outer_id
	*               	["article"][$articles[n]]["cover"], // article.cover
	*               	["article"][$articles[n]]["thumbs"], // article.thumbs
	*               	["article"][$articles[n]]["images"], // article.images
	*               	["article"][$articles[n]]["videos"], // article.videos
	*               	["article"][$articles[n]]["audios"], // article.audios
	*               	["article"][$articles[n]]["title"], // article.title
	*               	["article"][$articles[n]]["author"], // article.author
	*               	["article"][$articles[n]]["origin"], // article.origin
	*               	["article"][$articles[n]]["origin_url"], // article.origin_url
	*               	["article"][$articles[n]]["summary"], // article.summary
	*               	["article"][$articles[n]]["seo_title"], // article.seo_title
	*               	["article"][$articles[n]]["seo_keywords"], // article.seo_keywords
	*               	["article"][$articles[n]]["seo_summary"], // article.seo_summary
	*               	["article"][$articles[n]]["publish_time"], // article.publish_time
	*               	["article"][$articles[n]]["update_time"], // article.update_time
	*               	["article"][$articles[n]]["create_time"], // article.create_time
	*               	["article"][$articles[n]]["baidulink_time"], // article.baidulink_time
	*               	["article"][$articles[n]]["sync"], // article.sync
	*               	["article"][$articles[n]]["content"], // article.content
	*               	["article"][$articles[n]]["ap_content"], // article.ap_content
	*               	["article"][$articles[n]]["delta"], // article.delta
	*               	["article"][$articles[n]]["param"], // article.param
	*               	["article"][$articles[n]]["stick"], // article.stick
	*               	["article"][$articles[n]]["preview"], // article.preview
	*               	["article"][$articles[n]]["links"], // article.links
	*               	["article"][$articles[n]]["user"], // article.user
	*               	["article"][$articles[n]]["policies"], // article.policies
	*               	["article"][$articles[n]]["status"], // article.status
	*               	["article"][$articles[n]]["keywords"], // article.keywords
	*               	["article"][$articles[n]]["view_cnt"], // article.view_cnt
	*               	["article"][$articles[n]]["like_cnt"], // article.like_cnt
	*               	["article"][$articles[n]]["dislike_cnt"], // article.dislike_cnt
	*               	["article"][$articles[n]]["comment_cnt"], // article.comment_cnt
	*               	["article"][$articles[n]]["series"], // article.series
	*               	["article"][$articles[n]]["user_id"], // article.user_id
	*               	["article"][$articles[n]]["policies_detail"], // article.policies_detail
	*               	["article"][$articles[n]]["agree_cnt"], // article.agree_cnt
	*               	["article"][$articles[n]]["priority"], // article.priority
	*               	["article"][$articles[n]]["coin_view"], // article.coin_view
	*               	["article"][$articles[n]]["money_view"], // article.money_view
	*               	["article"][$articles[n]]["specials"], // article.specials
	*               	["article"][$articles[n]]["history"], // article.history
	*               	["article"][$articles[n]]["policies_comment"], // article.policies_comment
	*               	["article"][$articles[n]]["policies_reward"], // article.policies_reward
	*               	["article"][$articles[n]]["attachments"], // article.attachments
	*               	["article"][$articles[n]]["desktop"], // article.desktop
	*               	["article"][$articles[n]]["mobile"], // article.mobile
	*               	["article"][$articles[n]]["app"], // article.app
	*               	["article"][$articles[n]]["wxapp"], // article.wxapp
	*               	["article"][$articles[n]]["style"], // article.style
	*               	["article"][$exclude_articles[n]]["created_at"], // article.created_at
	*               	["article"][$exclude_articles[n]]["updated_at"], // article.updated_at
	*               	["article"][$exclude_articles[n]]["outer_id"], // article.outer_id
	*               	["article"][$exclude_articles[n]]["cover"], // article.cover
	*               	["article"][$exclude_articles[n]]["thumbs"], // article.thumbs
	*               	["article"][$exclude_articles[n]]["images"], // article.images
	*               	["article"][$exclude_articles[n]]["videos"], // article.videos
	*               	["article"][$exclude_articles[n]]["audios"], // article.audios
	*               	["article"][$exclude_articles[n]]["title"], // article.title
	*               	["article"][$exclude_articles[n]]["author"], // article.author
	*               	["article"][$exclude_articles[n]]["origin"], // article.origin
	*               	["article"][$exclude_articles[n]]["origin_url"], // article.origin_url
	*               	["article"][$exclude_articles[n]]["summary"], // article.summary
	*               	["article"][$exclude_articles[n]]["seo_title"], // article.seo_title
	*               	["article"][$exclude_articles[n]]["seo_keywords"], // article.seo_keywords
	*               	["article"][$exclude_articles[n]]["seo_summary"], // article.seo_summary
	*               	["article"][$exclude_articles[n]]["publish_time"], // article.publish_time
	*               	["article"][$exclude_articles[n]]["update_time"], // article.update_time
	*               	["article"][$exclude_articles[n]]["create_time"], // article.create_time
	*               	["article"][$exclude_articles[n]]["baidulink_time"], // article.baidulink_time
	*               	["article"][$exclude_articles[n]]["sync"], // article.sync
	*               	["article"][$exclude_articles[n]]["content"], // article.content
	*               	["article"][$exclude_articles[n]]["ap_content"], // article.ap_content
	*               	["article"][$exclude_articles[n]]["delta"], // article.delta
	*               	["article"][$exclude_articles[n]]["param"], // article.param
	*               	["article"][$exclude_articles[n]]["stick"], // article.stick
	*               	["article"][$exclude_articles[n]]["preview"], // article.preview
	*               	["article"][$exclude_articles[n]]["links"], // article.links
	*               	["article"][$exclude_articles[n]]["user"], // article.user
	*               	["article"][$exclude_articles[n]]["policies"], // article.policies
	*               	["article"][$exclude_articles[n]]["status"], // article.status
	*               	["article"][$exclude_articles[n]]["keywords"], // article.keywords
	*               	["article"][$exclude_articles[n]]["view_cnt"], // article.view_cnt
	*               	["article"][$exclude_articles[n]]["like_cnt"], // article.like_cnt
	*               	["article"][$exclude_articles[n]]["dislike_cnt"], // article.dislike_cnt
	*               	["article"][$exclude_articles[n]]["comment_cnt"], // article.comment_cnt
	*               	["article"][$exclude_articles[n]]["series"], // article.series
	*               	["article"][$exclude_articles[n]]["user_id"], // article.user_id
	*               	["article"][$exclude_articles[n]]["policies_detail"], // article.policies_detail
	*               	["article"][$exclude_articles[n]]["agree_cnt"], // article.agree_cnt
	*               	["article"][$exclude_articles[n]]["priority"], // article.priority
	*               	["article"][$exclude_articles[n]]["coin_view"], // article.coin_view
	*               	["article"][$exclude_articles[n]]["money_view"], // article.money_view
	*               	["article"][$exclude_articles[n]]["specials"], // article.specials
	*               	["article"][$exclude_articles[n]]["history"], // article.history
	*               	["article"][$exclude_articles[n]]["policies_comment"], // article.policies_comment
	*               	["article"][$exclude_articles[n]]["policies_reward"], // article.policies_reward
	*               	["article"][$exclude_articles[n]]["attachments"], // article.attachments
	*               	["article"][$exclude_articles[n]]["desktop"], // article.desktop
	*               	["article"][$exclude_articles[n]]["mobile"], // article.mobile
	*               	["article"][$exclude_articles[n]]["app"], // article.app
	*               	["article"][$exclude_articles[n]]["wxapp"], // article.wxapp
	*               	["article"][$exclude_articles[n]]["style"], // article.style
	*               	["event"][$events[n]]["created_at"], // event.created_at
	*               	["event"][$events[n]]["updated_at"], // event.updated_at
	*               	["event"][$events[n]]["slug"], // event.slug
	*               	["event"][$events[n]]["name"], // event.name
	*               	["event"][$events[n]]["link"], // event.link
	*               	["event"][$events[n]]["categories"], // event.categories
	*               	["event"][$events[n]]["type"], // event.type
	*               	["event"][$events[n]]["tags"], // event.tags
	*               	["event"][$events[n]]["summary"], // event.summary
	*               	["event"][$events[n]]["cover"], // event.cover
	*               	["event"][$events[n]]["images"], // event.images
	*               	["event"][$events[n]]["begin"], // event.begin
	*               	["event"][$events[n]]["end"], // event.end
	*               	["event"][$events[n]]["area"], // event.area
	*               	["event"][$events[n]]["prov"], // event.prov
	*               	["event"][$events[n]]["city"], // event.city
	*               	["event"][$events[n]]["town"], // event.town
	*               	["event"][$events[n]]["location"], // event.location
	*               	["event"][$events[n]]["price"], // event.price
	*               	["event"][$events[n]]["hosts"], // event.hosts
	*               	["event"][$events[n]]["organizers"], // event.organizers
	*               	["event"][$events[n]]["sponsors"], // event.sponsors
	*               	["event"][$events[n]]["medias"], // event.medias
	*               	["event"][$events[n]]["speakers"], // event.speakers
	*               	["event"][$events[n]]["content"], // event.content
	*               	["event"][$events[n]]["publish_time"], // event.publish_time
	*               	["event"][$events[n]]["view_cnt"], // event.view_cnt
	*               	["event"][$events[n]]["like_cnt"], // event.like_cnt
	*               	["event"][$events[n]]["dislike_cnt"], // event.dislike_cnt
	*               	["event"][$events[n]]["comment_cnt"], // event.comment_cnt
	*               	["event"][$events[n]]["status"], // event.status
	*               	["event"][$events[n]]["title"], // event.title
	*               	["event"][$events[n]]["process_setting"], // event.process_setting
	*               	["event"][$events[n]]["process"], // event.process
	*               	["event"][$events[n]]["bonus"], // event.bonus
	*               	["event"][$events[n]]["prize"], // event.prize
	*               	["event"][$events[n]]["desktop"], // event.desktop
	*               	["event"][$events[n]]["mobile"], // event.mobile
	*               	["event"][$events[n]]["wxapp"], // event.wxapp
	*               	["event"][$events[n]]["app"], // event.app
	*               	["event"][$events[n]]["agree_cnt"], // event.agree_cnt
	*               	["event"][$events[n]]["quota"], // event.quota
	*               	["event"][$events[n]]["user_cnt"], // event.user_cnt
	*               	["event"][$events[n]]["series"], // event.series
	*               	["event"][$events[n]]["deadline"], // event.deadline
	*               	["event"][$events[n]]["report"], // event.report
	*               	["event"][$events[n]]["admin_link"], // event.admin_link
	*               	["event"][$events[n]]["admin_name"], // event.admin_name
	*               	["event"][$exclude_events[n]]["created_at"], // event.created_at
	*               	["event"][$exclude_events[n]]["updated_at"], // event.updated_at
	*               	["event"][$exclude_events[n]]["slug"], // event.slug
	*               	["event"][$exclude_events[n]]["name"], // event.name
	*               	["event"][$exclude_events[n]]["link"], // event.link
	*               	["event"][$exclude_events[n]]["categories"], // event.categories
	*               	["event"][$exclude_events[n]]["type"], // event.type
	*               	["event"][$exclude_events[n]]["tags"], // event.tags
	*               	["event"][$exclude_events[n]]["summary"], // event.summary
	*               	["event"][$exclude_events[n]]["cover"], // event.cover
	*               	["event"][$exclude_events[n]]["images"], // event.images
	*               	["event"][$exclude_events[n]]["begin"], // event.begin
	*               	["event"][$exclude_events[n]]["end"], // event.end
	*               	["event"][$exclude_events[n]]["area"], // event.area
	*               	["event"][$exclude_events[n]]["prov"], // event.prov
	*               	["event"][$exclude_events[n]]["city"], // event.city
	*               	["event"][$exclude_events[n]]["town"], // event.town
	*               	["event"][$exclude_events[n]]["location"], // event.location
	*               	["event"][$exclude_events[n]]["price"], // event.price
	*               	["event"][$exclude_events[n]]["hosts"], // event.hosts
	*               	["event"][$exclude_events[n]]["organizers"], // event.organizers
	*               	["event"][$exclude_events[n]]["sponsors"], // event.sponsors
	*               	["event"][$exclude_events[n]]["medias"], // event.medias
	*               	["event"][$exclude_events[n]]["speakers"], // event.speakers
	*               	["event"][$exclude_events[n]]["content"], // event.content
	*               	["event"][$exclude_events[n]]["publish_time"], // event.publish_time
	*               	["event"][$exclude_events[n]]["view_cnt"], // event.view_cnt
	*               	["event"][$exclude_events[n]]["like_cnt"], // event.like_cnt
	*               	["event"][$exclude_events[n]]["dislike_cnt"], // event.dislike_cnt
	*               	["event"][$exclude_events[n]]["comment_cnt"], // event.comment_cnt
	*               	["event"][$exclude_events[n]]["status"], // event.status
	*               	["event"][$exclude_events[n]]["title"], // event.title
	*               	["event"][$exclude_events[n]]["process_setting"], // event.process_setting
	*               	["event"][$exclude_events[n]]["process"], // event.process
	*               	["event"][$exclude_events[n]]["bonus"], // event.bonus
	*               	["event"][$exclude_events[n]]["prize"], // event.prize
	*               	["event"][$exclude_events[n]]["desktop"], // event.desktop
	*               	["event"][$exclude_events[n]]["mobile"], // event.mobile
	*               	["event"][$exclude_events[n]]["wxapp"], // event.wxapp
	*               	["event"][$exclude_events[n]]["app"], // event.app
	*               	["event"][$exclude_events[n]]["agree_cnt"], // event.agree_cnt
	*               	["event"][$exclude_events[n]]["quota"], // event.quota
	*               	["event"][$exclude_events[n]]["user_cnt"], // event.user_cnt
	*               	["event"][$exclude_events[n]]["series"], // event.series
	*               	["event"][$exclude_events[n]]["deadline"], // event.deadline
	*               	["event"][$exclude_events[n]]["report"], // event.report
	*               	["event"][$exclude_events[n]]["admin_link"], // event.admin_link
	*               	["event"][$exclude_events[n]]["admin_name"], // event.admin_name
	*               	["album"][$albums[n]]["created_at"], // album.created_at
	*               	["album"][$albums[n]]["updated_at"], // album.updated_at
	*               	["album"][$albums[n]]["slug"], // album.slug
	*               	["album"][$albums[n]]["title"], // album.title
	*               	["album"][$albums[n]]["author"], // album.author
	*               	["album"][$albums[n]]["origin"], // album.origin
	*               	["album"][$albums[n]]["origin_url"], // album.origin_url
	*               	["album"][$albums[n]]["link"], // album.link
	*               	["album"][$albums[n]]["categories"], // album.categories
	*               	["album"][$albums[n]]["tags"], // album.tags
	*               	["album"][$albums[n]]["summary"], // album.summary
	*               	["album"][$albums[n]]["images"], // album.images
	*               	["album"][$albums[n]]["cover"], // album.cover
	*               	["album"][$albums[n]]["publish_time"], // album.publish_time
	*               	["album"][$albums[n]]["view_cnt"], // album.view_cnt
	*               	["album"][$albums[n]]["like_cnt"], // album.like_cnt
	*               	["album"][$albums[n]]["dislike_cnt"], // album.dislike_cnt
	*               	["album"][$albums[n]]["comment_cnt"], // album.comment_cnt
	*               	["album"][$albums[n]]["status"], // album.status
	*               	["album"][$albums[n]]["series"], // album.series
	*               	["album"][$exclude_albums[n]]["created_at"], // album.created_at
	*               	["album"][$exclude_albums[n]]["updated_at"], // album.updated_at
	*               	["album"][$exclude_albums[n]]["slug"], // album.slug
	*               	["album"][$exclude_albums[n]]["title"], // album.title
	*               	["album"][$exclude_albums[n]]["author"], // album.author
	*               	["album"][$exclude_albums[n]]["origin"], // album.origin
	*               	["album"][$exclude_albums[n]]["origin_url"], // album.origin_url
	*               	["album"][$exclude_albums[n]]["link"], // album.link
	*               	["album"][$exclude_albums[n]]["categories"], // album.categories
	*               	["album"][$exclude_albums[n]]["tags"], // album.tags
	*               	["album"][$exclude_albums[n]]["summary"], // album.summary
	*               	["album"][$exclude_albums[n]]["images"], // album.images
	*               	["album"][$exclude_albums[n]]["cover"], // album.cover
	*               	["album"][$exclude_albums[n]]["publish_time"], // album.publish_time
	*               	["album"][$exclude_albums[n]]["view_cnt"], // album.view_cnt
	*               	["album"][$exclude_albums[n]]["like_cnt"], // album.like_cnt
	*               	["album"][$exclude_albums[n]]["dislike_cnt"], // album.dislike_cnt
	*               	["album"][$exclude_albums[n]]["comment_cnt"], // album.comment_cnt
	*               	["album"][$exclude_albums[n]]["status"], // album.status
	*               	["album"][$exclude_albums[n]]["series"], // album.series
	*               	["question"][$questions[n]]["created_at"], // question.created_at
	*               	["question"][$questions[n]]["updated_at"], // question.updated_at
	*               	["question"][$questions[n]]["user_id"], // question.user_id
	*               	["question"][$questions[n]]["title"], // question.title
	*               	["question"][$questions[n]]["summary"], // question.summary
	*               	["question"][$questions[n]]["content"], // question.content
	*               	["question"][$questions[n]]["category_ids"], // question.category_ids
	*               	["question"][$questions[n]]["series_ids"], // question.series_ids
	*               	["question"][$questions[n]]["tags"], // question.tags
	*               	["question"][$questions[n]]["view_cnt"], // question.view_cnt
	*               	["question"][$questions[n]]["agree_cnt"], // question.agree_cnt
	*               	["question"][$questions[n]]["answer_cnt"], // question.answer_cnt
	*               	["question"][$questions[n]]["priority"], // question.priority
	*               	["question"][$questions[n]]["status"], // question.status
	*               	["question"][$questions[n]]["publish_time"], // question.publish_time
	*               	["question"][$questions[n]]["coin"], // question.coin
	*               	["question"][$questions[n]]["money"], // question.money
	*               	["question"][$questions[n]]["coin_view"], // question.coin_view
	*               	["question"][$questions[n]]["money_view"], // question.money_view
	*               	["question"][$questions[n]]["policies"], // question.policies
	*               	["question"][$questions[n]]["policies_detail"], // question.policies_detail
	*               	["question"][$questions[n]]["anonymous"], // question.anonymous
	*               	["question"][$questions[n]]["cover"], // question.cover
	*               	["question"][$questions[n]]["history"], // question.history
	*               	["question"][$exclude_questions[n]]["created_at"], // question.created_at
	*               	["question"][$exclude_questions[n]]["updated_at"], // question.updated_at
	*               	["question"][$exclude_questions[n]]["user_id"], // question.user_id
	*               	["question"][$exclude_questions[n]]["title"], // question.title
	*               	["question"][$exclude_questions[n]]["summary"], // question.summary
	*               	["question"][$exclude_questions[n]]["content"], // question.content
	*               	["question"][$exclude_questions[n]]["category_ids"], // question.category_ids
	*               	["question"][$exclude_questions[n]]["series_ids"], // question.series_ids
	*               	["question"][$exclude_questions[n]]["tags"], // question.tags
	*               	["question"][$exclude_questions[n]]["view_cnt"], // question.view_cnt
	*               	["question"][$exclude_questions[n]]["agree_cnt"], // question.agree_cnt
	*               	["question"][$exclude_questions[n]]["answer_cnt"], // question.answer_cnt
	*               	["question"][$exclude_questions[n]]["priority"], // question.priority
	*               	["question"][$exclude_questions[n]]["status"], // question.status
	*               	["question"][$exclude_questions[n]]["publish_time"], // question.publish_time
	*               	["question"][$exclude_questions[n]]["coin"], // question.coin
	*               	["question"][$exclude_questions[n]]["money"], // question.money
	*               	["question"][$exclude_questions[n]]["coin_view"], // question.coin_view
	*               	["question"][$exclude_questions[n]]["money_view"], // question.money_view
	*               	["question"][$exclude_questions[n]]["policies"], // question.policies
	*               	["question"][$exclude_questions[n]]["policies_detail"], // question.policies_detail
	*               	["question"][$exclude_questions[n]]["anonymous"], // question.anonymous
	*               	["question"][$exclude_questions[n]]["cover"], // question.cover
	*               	["question"][$exclude_questions[n]]["history"], // question.history
	*               	["answer"][$answers[n]]["created_at"], // answer.created_at
	*               	["answer"][$answers[n]]["updated_at"], // answer.updated_at
	*               	["answer"][$answers[n]]["question_id"], // answer.question_id
	*               	["answer"][$answers[n]]["user_id"], // answer.user_id
	*               	["answer"][$answers[n]]["content"], // answer.content
	*               	["answer"][$answers[n]]["publish_time"], // answer.publish_time
	*               	["answer"][$answers[n]]["policies"], // answer.policies
	*               	["answer"][$answers[n]]["policies_detail"], // answer.policies_detail
	*               	["answer"][$answers[n]]["priority"], // answer.priority
	*               	["answer"][$answers[n]]["view_cnt"], // answer.view_cnt
	*               	["answer"][$answers[n]]["agree_cnt"], // answer.agree_cnt
	*               	["answer"][$answers[n]]["coin"], // answer.coin
	*               	["answer"][$answers[n]]["money"], // answer.money
	*               	["answer"][$answers[n]]["coin_view"], // answer.coin_view
	*               	["answer"][$answers[n]]["money_view"], // answer.money_view
	*               	["answer"][$answers[n]]["anonymous"], // answer.anonymous
	*               	["answer"][$answers[n]]["accepted"], // answer.accepted
	*               	["answer"][$answers[n]]["status"], // answer.status
	*               	["answer"][$answers[n]]["history"], // answer.history
	*               	["answer"][$answers[n]]["summary"], // answer.summary
	*               	["answer"][$exclude_answers[n]]["created_at"], // answer.created_at
	*               	["answer"][$exclude_answers[n]]["updated_at"], // answer.updated_at
	*               	["answer"][$exclude_answers[n]]["question_id"], // answer.question_id
	*               	["answer"][$exclude_answers[n]]["user_id"], // answer.user_id
	*               	["answer"][$exclude_answers[n]]["content"], // answer.content
	*               	["answer"][$exclude_answers[n]]["publish_time"], // answer.publish_time
	*               	["answer"][$exclude_answers[n]]["policies"], // answer.policies
	*               	["answer"][$exclude_answers[n]]["policies_detail"], // answer.policies_detail
	*               	["answer"][$exclude_answers[n]]["priority"], // answer.priority
	*               	["answer"][$exclude_answers[n]]["view_cnt"], // answer.view_cnt
	*               	["answer"][$exclude_answers[n]]["agree_cnt"], // answer.agree_cnt
	*               	["answer"][$exclude_answers[n]]["coin"], // answer.coin
	*               	["answer"][$exclude_answers[n]]["money"], // answer.money
	*               	["answer"][$exclude_answers[n]]["coin_view"], // answer.coin_view
	*               	["answer"][$exclude_answers[n]]["money_view"], // answer.money_view
	*               	["answer"][$exclude_answers[n]]["anonymous"], // answer.anonymous
	*               	["answer"][$exclude_answers[n]]["accepted"], // answer.accepted
	*               	["answer"][$exclude_answers[n]]["status"], // answer.status
	*               	["answer"][$exclude_answers[n]]["history"], // answer.history
	*               	["answer"][$exclude_answers[n]]["summary"], // answer.summary
	*               	["goods"][$goods[n]]["created_at"], // goods.created_at
	*               	["goods"][$goods[n]]["updated_at"], // goods.updated_at
	*               	["goods"][$goods[n]]["instance"], // goods.instance
	*               	["goods"][$goods[n]]["name"], // goods.name
	*               	["goods"][$goods[n]]["slug"], // goods.slug
	*               	["goods"][$goods[n]]["tags"], // goods.tags
	*               	["goods"][$goods[n]]["category_ids"], // goods.category_ids
	*               	["goods"][$goods[n]]["recommend_ids"], // goods.recommend_ids
	*               	["goods"][$goods[n]]["summary"], // goods.summary
	*               	["goods"][$goods[n]]["cover"], // goods.cover
	*               	["goods"][$goods[n]]["images"], // goods.images
	*               	["goods"][$goods[n]]["videos"], // goods.videos
	*               	["goods"][$goods[n]]["params"], // goods.params
	*               	["goods"][$goods[n]]["content"], // goods.content
	*               	["goods"][$goods[n]]["content_faq"], // goods.content_faq
	*               	["goods"][$goods[n]]["content_serv"], // goods.content_serv
	*               	["goods"][$goods[n]]["sku_cnt"], // goods.sku_cnt
	*               	["goods"][$goods[n]]["sku_sum"], // goods.sku_sum
	*               	["goods"][$goods[n]]["shipped_sum"], // goods.shipped_sum
	*               	["goods"][$goods[n]]["available_sum"], // goods.available_sum
	*               	["goods"][$goods[n]]["lower_price"], // goods.lower_price
	*               	["goods"][$goods[n]]["sale_way"], // goods.sale_way
	*               	["goods"][$goods[n]]["opened_at"], // goods.opened_at
	*               	["goods"][$goods[n]]["closed_at"], // goods.closed_at
	*               	["goods"][$goods[n]]["pay_duration"], // goods.pay_duration
	*               	["goods"][$goods[n]]["status"], // goods.status
	*               	["goods"][$goods[n]]["events"], // goods.events
	*               	["goods"][$goods[n]]["priority"], // goods.priority
	*               	["goods"][$exclude_goods[n]]["created_at"], // goods.created_at
	*               	["goods"][$exclude_goods[n]]["updated_at"], // goods.updated_at
	*               	["goods"][$exclude_goods[n]]["instance"], // goods.instance
	*               	["goods"][$exclude_goods[n]]["name"], // goods.name
	*               	["goods"][$exclude_goods[n]]["slug"], // goods.slug
	*               	["goods"][$exclude_goods[n]]["tags"], // goods.tags
	*               	["goods"][$exclude_goods[n]]["category_ids"], // goods.category_ids
	*               	["goods"][$exclude_goods[n]]["recommend_ids"], // goods.recommend_ids
	*               	["goods"][$exclude_goods[n]]["summary"], // goods.summary
	*               	["goods"][$exclude_goods[n]]["cover"], // goods.cover
	*               	["goods"][$exclude_goods[n]]["images"], // goods.images
	*               	["goods"][$exclude_goods[n]]["videos"], // goods.videos
	*               	["goods"][$exclude_goods[n]]["params"], // goods.params
	*               	["goods"][$exclude_goods[n]]["content"], // goods.content
	*               	["goods"][$exclude_goods[n]]["content_faq"], // goods.content_faq
	*               	["goods"][$exclude_goods[n]]["content_serv"], // goods.content_serv
	*               	["goods"][$exclude_goods[n]]["sku_cnt"], // goods.sku_cnt
	*               	["goods"][$exclude_goods[n]]["sku_sum"], // goods.sku_sum
	*               	["goods"][$exclude_goods[n]]["shipped_sum"], // goods.shipped_sum
	*               	["goods"][$exclude_goods[n]]["available_sum"], // goods.available_sum
	*               	["goods"][$exclude_goods[n]]["lower_price"], // goods.lower_price
	*               	["goods"][$exclude_goods[n]]["sale_way"], // goods.sale_way
	*               	["goods"][$exclude_goods[n]]["opened_at"], // goods.opened_at
	*               	["goods"][$exclude_goods[n]]["closed_at"], // goods.closed_at
	*               	["goods"][$exclude_goods[n]]["pay_duration"], // goods.pay_duration
	*               	["goods"][$exclude_goods[n]]["status"], // goods.status
	*               	["goods"][$exclude_goods[n]]["events"], // goods.events
	*               	["goods"][$exclude_goods[n]]["priority"], // goods.priority
	*               	["series"][$series[n]]["created_at"], // series.created_at
	*               	["series"][$series[n]]["updated_at"], // series.updated_at
	*               	["series"][$series[n]]["name"], // series.name
	*               	["series"][$series[n]]["slug"], // series.slug
	*               	["series"][$series[n]]["category_id"], // series.category_id
	*               	["series"][$series[n]]["summary"], // series.summary
	*               	["series"][$series[n]]["orderby"], // series.orderby
	*               	["series"][$series[n]]["param"], // series.param
	*               	["series"][$series[n]]["status"], // series.status
	*               	["category"][$categories[n]]["created_at"], // category.created_at
	*               	["category"][$categories[n]]["updated_at"], // category.updated_at
	*               	["category"][$categories[n]]["slug"], // category.slug
	*               	["category"][$categories[n]]["project"], // category.project
	*               	["category"][$categories[n]]["page"], // category.page
	*               	["category"][$categories[n]]["wechat"], // category.wechat
	*               	["category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	*               	["category"][$categories[n]]["name"], // category.name
	*               	["category"][$categories[n]]["fullname"], // category.fullname
	*               	["category"][$categories[n]]["link"], // category.link
	*               	["category"][$categories[n]]["root_id"], // category.root_id
	*               	["category"][$categories[n]]["parent_id"], // category.parent_id
	*               	["category"][$categories[n]]["priority"], // category.priority
	*               	["category"][$categories[n]]["hidden"], // category.hidden
	*               	["category"][$categories[n]]["isnav"], // category.isnav
	*               	["category"][$categories[n]]["param"], // category.param
	*               	["category"][$categories[n]]["status"], // category.status
	*               	["category"][$categories[n]]["issubnav"], // category.issubnav
	*               	["category"][$categories[n]]["highlight"], // category.highlight
	*               	["category"][$categories[n]]["isfootnav"], // category.isfootnav
	*               	["category"][$categories[n]]["isblank"], // category.isblank
	*               	["topic"][$topics[n]]["created_at"], // topic.created_at
	*               	["topic"][$topics[n]]["updated_at"], // topic.updated_at
	*               	["topic"][$topics[n]]["name"], // topic.name
	*               	["topic"][$topics[n]]["param"], // topic.param
	*               	["topic"][$topics[n]]["article_cnt"], // topic.article_cnt
	*               	["topic"][$topics[n]]["album_cnt"], // topic.album_cnt
	*               	["topic"][$topics[n]]["event_cnt"], // topic.event_cnt
	*               	["topic"][$topics[n]]["goods_cnt"], // topic.goods_cnt
	*               	["topic"][$topics[n]]["question_cnt"], // topic.question_cnt
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["recommend.recommend_id","recommend.title","recommend.pos","recommend.type","recommend.bigdata_engine","recommend.images","recommend.keywords","recommend.series","recommend.orderby","recommend.status","recommend.created_at","recommend.updated_at","a.article_id","a.title","evt.event_id","evt.name","al.album_id","al.title","c.category_id","c.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Recommend;
		return $inst->search( $data );
	}


}