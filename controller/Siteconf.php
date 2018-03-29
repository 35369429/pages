<?php
/**
 * Class SiteconfController
 * 站点配置控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-29 11:09:18
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
				$action_name =  $rs['position'];
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



	/**
	 * 保存分类
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Siteconf;
		$rs = $inst->save( $data );
		echo json_encode($rs);
	}

	function remove(){
		$site_id = $_POST['site_id'];
		$inst = new \Xpmsns\Pages\Model\Siteconf;
		$site_ids =$c->removeBySiteId( $site_id );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$site_ids'=>$site_ids]]);
	}



}