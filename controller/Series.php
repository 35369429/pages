<?php
/**
 * Class SeriesController
 * 系列控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-08-19 18:26:52
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;

class SeriesController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 系列列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Series;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "系列列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'series','search.index');

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
	            "系列" => APP::R('series','index'),
	            "系列管理" =>'',
	        ]
		];
	}


	/**
	 * 系列详情表单
	 */
	function detail() {

		$series_id = trim($_GET['series_id']);
		$action_name = '新建系列';
		$inst = new \Xpmsns\Pages\Model\Series;
		
		if ( !empty($series_id) ) {
			$rs = $inst->getBySeriesId($series_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['name'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'series_id'=>$series_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'series','form');

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
	            "系列" => APP::R('series','index'),
	            "系列管理" =>APP::R('series','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/series/index'
	 		]
		];

	}



	/**
	 * 保存系列
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Series;
		$rs = $inst->saveBySeriesId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除系列
	 * @return [type] [description]
	 */
	function remove(){
		$series_id = $_POST['series_id'];
		$inst = new \Xpmsns\Pages\Model\Series;
		$series_ids =$inst->remove( $series_id, "series_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$series_ids'=>$series_ids]]);
	}

	/**
	 * 复制系列
	 * @return
	 */
	function duplicate(){
		$series_id = $_GET['series_id'];
		$inst = new \Xpmsns\Pages\Model\Series;
		$rs = $inst->getBySeriesId( $series_id );
		$action_name =  $rs['name'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['series_id']);
		unset($rs['slug']);

		// 复制图片

		$data = [
			'action_name' =>  $action_name,
			'series_id'=>$series_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'series','form');

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
	            "系列" => APP::R('series','index'),
	            "系列管理" =>APP::R('series','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/series/index'
	 		]
		];
	}



}