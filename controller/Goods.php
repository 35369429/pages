<?php
/**
 * Class GoodsController
 * 商品控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-27 19:50:19
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class GoodsController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 商品列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Goods;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "商品列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'goods','search.index');

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
	            "商品" => APP::R('goods','index'),
	            "商品管理" =>'',
	        ]
		];
	}


	/**
	 * 商品详情表单
	 */
	function detail() {

		$goods_id = trim($_GET['goods_id']);
		$action_name = '新建商品';
		$inst = new \Xpmsns\Pages\Model\Goods;
		
		if ( !empty($goods_id) ) {
			$rs = $inst->getByGoodsId($goods_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['name'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'goods_id'=>$goods_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'goods','form');

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
	            "商品" => APP::R('goods','index'),
	            "商品管理" =>APP::R('goods','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/goods/index'
	 		]
		];

	}



	/**
	 * 保存商品
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Goods;
		$rs = $inst->saveByGoodsId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除商品
	 * @return [type] [description]
	 */
	function remove(){
		$goods_id = $_POST['goods_id'];
		$inst = new \Xpmsns\Pages\Model\Goods;
		$goods_ids =$inst->remove( $goods_id, "goods_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$goods_ids'=>$goods_ids]]);
	}

	/**
	 * 复制商品
	 * @return
	 */
	function duplicate(){
		$goods_id = $_GET['goods_id'];
		$inst = new \Xpmsns\Pages\Model\Goods;
		$rs = $inst->getByGoodsId( $goods_id );
		$action_name =  $rs['name'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['goods_id']);
		unset($rs['slug']);

		// 复制图片
		if ( is_array($rs['cover'])) {

			$resp = [];
			foreach ($rs['cover'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadCoverByGoodsId( $goods_id, $fs['local'], $idx, true);
			}

			$rs['cover'] = $resp;
		}

		if ( is_array($rs['images'])) {

			$resp = [];
			foreach ($rs['images'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadImagesByGoodsId( $goods_id, $fs['local'], $idx, true);
			}

			$rs['images'] = $resp;
		}

		if ( is_array($rs['videos'])) {

			$resp = [];
			foreach ($rs['videos'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadVideosByGoodsId( $goods_id, $fs['local'], $idx, true);
			}

			$rs['videos'] = $resp;
		}


		$data = [
			'action_name' =>  $action_name,
			'goods_id'=>$goods_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'goods','form');

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
	            "商品" => APP::R('goods','index'),
	            "商品管理" =>APP::R('goods','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/goods/index'
	 		]
		];
	}



}