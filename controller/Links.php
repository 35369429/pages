<?php
/**
 * Class LinksController
 * 友链控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-30 03:13:28
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

                                                                                                                                                     
use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class LinksController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 友链列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Links;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "友链列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'links','search.index');

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
	            "友链" => APP::R('links','index'),
	            "友链管理" =>'',
	        ]
		];
	}


	/**
	 * 友链详情表单
	 */
	function detail() {

		$links_id = trim($_GET['links_id']);
		$action_name = '新建友链';
		$inst = new \Xpmsns\Pages\Model\Links;
		
		if ( !empty($links_id) ) {
			$rs = $inst->getByLinksId($links_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['name'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'links_id'=>$links_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'links','form');

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
	            "友链" => APP::R('links','index'),
	            "友链管理" =>APP::R('links','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/links/index'
	 		]
		];

	}



	/**
	 * 保存友链
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Links;
		$rs = $inst->save( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除友链
	 * @return [type] [description]
	 */
	function remove(){
		$links_id = $_POST['links_id'];
		$inst = new \Xpmsns\Pages\Model\Links;
		$links_ids =$inst->remove( $links_id, "links_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$links_ids'=>$links_ids]]);
	}

	/**
	 * 复制友链
	 * @return
	 */
	function duplicate(){
		$links_id = $_GET['links_id'];
		$inst = new \Xpmsns\Pages\Model\Links;
		$rs =$inst->getByLinksId( $links_id );
		$action_name =  $rs['name'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['links_id']);
		unset($rs['links_slug']);

		// 复制图片
		if ( is_array($rs['logo']) &&  !empty($rs['logo']['local'])) {
			$rs['logo'] = $inst->uploadLogo( $links_id, $rs['logo']['local'], true);
		}

		$data = [
			'action_name' =>  $action_name,
			'links_id'=>$links_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'links','form');

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
	            "友链" => APP::R('links','index'),
	            "友链管理" =>APP::R('links','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/links/index'
	 		]
		];
	}



}