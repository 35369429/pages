<?php
/**
 * Class OrderController
 * 订单控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 23:30:08
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;

class OrderController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 订单列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Order;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "订单列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'order','search.index');

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
	            "订单" => APP::R('order','index'),
	            "订单管理" =>'',
	        ]
		];
	}


	/**
	 * 订单详情表单
	 */
	function detail() {

		$order_id = trim($_GET['order_id']);
		$action_name = '新建订单';
		$inst = new \Xpmsns\Pages\Model\Order;
		
		if ( !empty($order_id) ) {
			$rs = $inst->getByOrderId($order_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['order_id'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'order_id'=>$order_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'order','form');

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
	            "订单" => APP::R('order','index'),
	            "订单管理" =>APP::R('order','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/order/index'
	 		]
		];

	}



	/**
	 * 保存订单
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Order;
		$rs = $inst->saveByOrderId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除订单
	 * @return [type] [description]
	 */
	function remove(){
		$order_id = $_POST['order_id'];
		$inst = new \Xpmsns\Pages\Model\Order;
		$order_ids =$inst->remove( $order_id, "order_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$order_ids'=>$order_ids]]);
	}

	/**
	 * 复制订单
	 * @return
	 */
	function duplicate(){
		$order_id = $_GET['order_id'];
		$inst = new \Xpmsns\Pages\Model\Order;
		$rs = $inst->getByOrderId( $order_id );
		$action_name =  $rs['order_id'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['order_id']);

		// 复制图片

		$data = [
			'action_name' =>  $action_name,
			'order_id'=>$order_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'order','form');

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
	            "订单" => APP::R('order','index'),
	            "订单管理" =>APP::R('order','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/order/index'
	 		]
		];
	}



}