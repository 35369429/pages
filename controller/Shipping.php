<?php
/**
 * Class ShippingController
 * 物流控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 23:10:19
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;

class ShippingController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 物流列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Shipping;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "物流列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'shipping','search.index');

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
	            "物流" => APP::R('shipping','index'),
	            "物流管理" =>'',
	        ]
		];
	}


	/**
	 * 物流详情表单
	 */
	function detail() {

		$shipping_id = trim($_GET['shipping_id']);
		$action_name = '新建物流';
		$inst = new \Xpmsns\Pages\Model\Shipping;
		
		if ( !empty($shipping_id) ) {
			$rs = $inst->getByShippingId($shipping_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['company'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'shipping_id'=>$shipping_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'shipping','form');

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
	            "物流" => APP::R('shipping','index'),
	            "物流管理" =>APP::R('shipping','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/shipping/index'
	 		]
		];

	}



	/**
	 * 保存物流
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Shipping;
		$rs = $inst->saveByShippingId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除物流
	 * @return [type] [description]
	 */
	function remove(){
		$shipping_id = $_POST['shipping_id'];
		$inst = new \Xpmsns\Pages\Model\Shipping;
		$shipping_ids =$inst->remove( $shipping_id, "shipping_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$shipping_ids'=>$shipping_ids]]);
	}

	/**
	 * 复制物流
	 * @return
	 */
	function duplicate(){
		$shipping_id = $_GET['shipping_id'];
		$inst = new \Xpmsns\Pages\Model\Shipping;
		$rs = $inst->getByShippingId( $shipping_id );
		$action_name =  $rs['company'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['shipping_id']);

		// 复制图片

		$data = [
			'action_name' =>  $action_name,
			'shipping_id'=>$shipping_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'shipping','form');

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
	            "物流" => APP::R('shipping','index'),
	            "物流管理" =>APP::R('shipping','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/shipping/index'
	 		]
		];
	}



}