<?php
/**
 * Class RecommendController
 * 推荐控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-08-14 02:29:02
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class RecommendController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 推荐列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Recommend;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "推荐列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'recommend','search.index');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/select2/i18n/zh-CN.js",
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
		    		'js/plugins/jquery-ui/jquery-ui.min.js',
	        		'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
				],
			'css'=>[
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css"
	 		],
			'crumb' => [
	            "推荐" => APP::R('recommend','index'),
	            "推荐管理" =>'',
	        ]
		];
	}


	/**
	 * 推荐详情表单
	 */
	function detail() {

		$recommend_id = trim($_GET['recommend_id']);
		$action_name = '新建推荐';
		$inst = new \Xpmsns\Pages\Model\Recommend;
		
		if ( !empty($recommend_id) ) {
			$rs = $inst->getByRecommendId($recommend_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['title'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'recommend_id'=>$recommend_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'recommend','form');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/select2/i18n/zh-CN.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",
		    		"js/plugins/summernote/summernote.min.js",
		    		"js/plugins/summernote/lang/summernote-zh-CN.js",
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/summernote/summernote.css",
	 			"js/plugins/summernote/summernote-bs3.min.css"
	 		],

			'crumb' => [
	            "推荐" => APP::R('recommend','index'),
	            "推荐管理" =>APP::R('recommend','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/recommend/index'
	 		]
		];

	}



	/**
	 * 保存推荐
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Recommend;
		$rs = $inst->saveByRecommendId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除推荐
	 * @return [type] [description]
	 */
	function remove(){
		$recommend_id = $_POST['recommend_id'];
		$inst = new \Xpmsns\Pages\Model\Recommend;
		$recommend_ids =$inst->remove( $recommend_id, "recommend_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$recommend_ids'=>$recommend_ids]]);
	}

	/**
	 * 复制推荐
	 * @return
	 */
	function duplicate(){
		$recommend_id = $_GET['recommend_id'];
		$inst = new \Xpmsns\Pages\Model\Recommend;
		$rs = $inst->getByRecommendId( $recommend_id );
		$action_name =  $rs['title'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['recommend_id']);
		unset($rs['slug']);

		// 复制图片
		if ( is_array($rs['icon']) &&  !empty($rs['icon']['local'])) {
			$rs['icon'] = $inst->uploadIcon( $recommend_id, $rs['icon']['local'], true);
		}
		if ( is_array($rs['images'])) {

			$resp = [];
			foreach ($rs['images'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadImagesByRecommendId( $recommend_id, $fs['local'], $idx, true);
			}

			$rs['images'] = $resp;
		}


		$data = [
			'action_name' =>  $action_name,
			'recommend_id'=>$recommend_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'recommend','form');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js"
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css"
	 		],

			'crumb' => [
	            "推荐" => APP::R('recommend','index'),
	            "推荐管理" =>APP::R('recommend','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/recommend/index'
	 		]
		];
	}



}