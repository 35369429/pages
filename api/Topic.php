<?php
/**
 * Class Topic 
 * 话题数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-27 21:12:54
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\Pages\Api;
             

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Topic extends Api {

	/**
	 * 话题数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条话题记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["topic.topic_id","topic.name","topic.param","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"]
	 * 				 $query['topic_id']  按查询 (多条用 "," 分割)
	 * 				 $query['name']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["topic.topic_id","topic.name","topic.param","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"]
	 * 				 $data['topic_id']  按查询 (多条用 "," 分割)
	 * 				 $data['name']  按查询 (多条用 "," 分割)
	 *
	 * @return array 话题记录 Key Value 结构数据 
	 *               	["topic_id"],  // 话题ID 
	 *               	["name"],  // 名称 
	 *               	["param"],  // 参数 
	 *               	["article_cnt"],  // 文章数 
	 *               	["album_cnt"],  // 图集数 
	 *               	["event_cnt"],  // 活动数 
	 *               	["goods_cnt"],  // 商品数 
	 *               	["question_cnt"],  // 提问数 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["topic.topic_id","topic.name","topic.param","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按话题ID
		if ( !empty($data["topic_id"]) ) {
			
			$keys = explode(',', $data["topic_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Topic;
				return $inst->getInByTopicId($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Topic;
			return $inst->getByTopicId($data["topic_id"], $select);
		}

		// 按名称
		if ( !empty($data["name"]) ) {
			
			$keys = explode(',', $data["name"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\Pages\Model\Topic;
				return $inst->getInByName($keys, $select);
			}

			$inst = new \Xpmsns\Pages\Model\Topic;
			return $inst->getByName($data["name"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索话题记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["topic.topic_id","topic.name","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["topic_id"] 按话题ID查询 ( AND = )
	 *			      $query["name"] 按名称查询 ( AND LIKE )
	 *			      $query["created_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["updated_desc"]  按更新时间倒序 DESC 排序
	 *			      $query["article_desc"]  按文章数量倒序 DESC 排序
	 *			      $query["article_asc"]  按文章数量正序 ASC 排序
	 *			      $query["album_desc"]  按图集数量倒序 DESC 排序
	 *			      $query["album_asc"]  按图集数量正序 ASC 排序
	 *			      $query["event_desc"]  按活动数量倒序 DESC 排序
	 *			      $query["event_asc"]  按活动数量正序 DESC 排序
	 *			      $query["goods_desc"]  按商品数量倒序 DESC 排序
	 *			      $query["goods_asc"]  按商品数量正序 ASC 排序
	 *			      $query["question_desc"]  按提问数量倒序 DESC 排序
	 *			      $query["question_asc"]  按提问数量正序 ASC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=topic_id","name=name","name=article_cnt","name=album_cnt","name=event_cnt","name=goods_cnt","name=question_cnt","name=created_at","name=updated_at"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["topic_id"] 按话题ID查询 ( AND = )
	 *			      $data["name"] 按名称查询 ( AND LIKE )
	 *			      $data["created_desc"]  按创建时间倒序 DESC 排序
	 *			      $data["updated_desc"]  按更新时间倒序 DESC 排序
	 *			      $data["article_desc"]  按文章数量倒序 DESC 排序
	 *			      $data["article_asc"]  按文章数量正序 ASC 排序
	 *			      $data["album_desc"]  按图集数量倒序 DESC 排序
	 *			      $data["album_asc"]  按图集数量正序 ASC 排序
	 *			      $data["event_desc"]  按活动数量倒序 DESC 排序
	 *			      $data["event_asc"]  按活动数量正序 DESC 排序
	 *			      $data["goods_desc"]  按商品数量倒序 DESC 排序
	 *			      $data["goods_asc"]  按商品数量正序 ASC 排序
	 *			      $data["question_desc"]  按提问数量倒序 DESC 排序
	 *			      $data["question_asc"]  按提问数量正序 ASC 排序
	 *
	 * @return array 话题记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["topic_id"],  // 话题ID 
	 *               	["name"],  // 名称 
	 *               	["param"],  // 参数 
	 *               	["article_cnt"],  // 文章数 
	 *               	["album_cnt"],  // 图集数 
	 *               	["event_cnt"],  // 活动数 
	 *               	["goods_cnt"],  // 商品数 
	 *               	["question_cnt"],  // 提问数 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["topic.topic_id","topic.name","topic.article_cnt","topic.album_cnt","topic.event_cnt","topic.goods_cnt","topic.question_cnt","topic.created_at","topic.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\Pages\Model\Topic;
		return $inst->search( $data );
	}


}