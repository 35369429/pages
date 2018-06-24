<?php
/**
 * Class SiteconfController
 * 站点配置控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-06-24 11:29:11
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class SiteconfController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 站点配置列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Siteconf;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "站点配置列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'siteconf','search.index');

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
	            "站点配置" => APP::R('siteconf','index'),
	            "站点配置管理" =>'',
	        ]
		];
	}


	/**
	 * 站点配置详情表单
	 */
	function detail() {

		$site_id = trim($_GET['site_id']);
		$action_name = '新建站点配置';
		$inst = new \Xpmsns\Pages\Model\Siteconf;
		
		if ( !empty($site_id) ) {
			$rs = $inst->getBySiteId($site_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['site_slug'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'site_id'=>$site_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'siteconf','form');

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
	            "站点配置" => APP::R('siteconf','index'),
	            "站点配置管理" =>APP::R('siteconf','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/siteconf/index'
	 		]
		];

	}



	/**
	 * 保存站点配置
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Siteconf;
		$rs = $inst->saveBySiteId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除站点配置
	 * @return [type] [description]
	 */
	function remove(){
		$site_id = $_POST['site_id'];
		$inst = new \Xpmsns\Pages\Model\Siteconf;
		$site_ids =$inst->remove( $site_id, "site_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$site_ids'=>$site_ids]]);
	}

	/**
	 * 复制站点配置
	 * @return
	 */
	function duplicate(){
		$site_id = $_GET['site_id'];
		$inst = new \Xpmsns\Pages\Model\Siteconf;
		$rs = $inst->getBySiteId( $site_id );
		$action_name =  $rs['site_slug'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['site_id']);
		unset($rs['site_slug']);

		// 复制图片
		if ( is_array($rs['site_logo']) &&  !empty($rs['site_logo']['local'])) {
			$rs['site_logo'] = $inst->uploadSitelogo( $site_id, $rs['site_logo']['local'], true);
		}
		if ( is_array($rs['qr_wxapp']) &&  !empty($rs['qr_wxapp']['local'])) {
			$rs['qr_wxapp'] = $inst->uploadQrwxapp( $site_id, $rs['qr_wxapp']['local'], true);
		}
		if ( is_array($rs['qr_wxpub']) &&  !empty($rs['qr_wxpub']['local'])) {
			$rs['qr_wxpub'] = $inst->uploadQrwxpub( $site_id, $rs['qr_wxpub']['local'], true);
		}
		if ( is_array($rs['qr_wxse']) &&  !empty($rs['qr_wxse']['local'])) {
			$rs['qr_wxse'] = $inst->uploadQrwxse( $site_id, $rs['qr_wxse']['local'], true);
		}
		if ( is_array($rs['qr_android']) &&  !empty($rs['qr_android']['local'])) {
			$rs['qr_android'] = $inst->uploadQrandroid( $site_id, $rs['qr_android']['local'], true);
		}
		if ( is_array($rs['qr_ios']) &&  !empty($rs['qr_ios']['local'])) {
			$rs['qr_ios'] = $inst->uploadQrios( $site_id, $rs['qr_ios']['local'], true);
		}

		$data = [
			'action_name' =>  $action_name,
			'site_id'=>$site_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'siteconf','form');

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
	            "站点配置" => APP::R('siteconf','index'),
	            "站点配置管理" =>APP::R('siteconf','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/siteconf/index'
	 		]
		];
	}



}