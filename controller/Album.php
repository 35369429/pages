<?php
/**
 * Class AlbumController
 * 图集控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-06-30 22:58:55
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class AlbumController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 图集列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Album;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "图集列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'album','search.index');

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
	            "图集" => APP::R('album','index'),
	            "图集管理" =>'',
	        ]
		];
	}


	/**
	 * 图集详情表单
	 */
	function detail() {

		$album_id = trim($_GET['album_id']);
		$action_name = '新建图集';
		$inst = new \Xpmsns\Pages\Model\Album;
		
		if ( !empty($album_id) ) {
			$rs = $inst->getByAlbumId($album_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['title'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'album_id'=>$album_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'album','form');

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
	            "图集" => APP::R('album','index'),
	            "图集管理" =>APP::R('album','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/album/index'
	 		]
		];

	}



	/**
	 * 保存图集
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\Pages\Model\Album;
		$rs = $inst->saveByAlbumId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除图集
	 * @return [type] [description]
	 */
	function remove(){
		$album_id = $_POST['album_id'];
		$inst = new \Xpmsns\Pages\Model\Album;
		$album_ids =$inst->remove( $album_id, "album_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$album_ids'=>$album_ids]]);
	}

	/**
	 * 复制图集
	 * @return
	 */
	function duplicate(){
		$album_id = $_GET['album_id'];
		$inst = new \Xpmsns\Pages\Model\Album;
		$rs = $inst->getByAlbumId( $album_id );
		$action_name =  $rs['title'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['album_id']);
		unset($rs['slug']);

		// 复制图片
		if ( is_array($rs['images'])) {

			$resp = [];
			foreach ($rs['images'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadImagesByAlbumId( $album_id, $fs['local'], $idx, true);
			}

			$rs['images'] = $resp;
		}

		if ( is_array($rs['cover']) &&  !empty($rs['cover']['local'])) {
			$rs['cover'] = $inst->uploadCover( $album_id, $rs['cover']['local'], true);
		}

		$data = [
			'action_name' =>  $action_name,
			'album_id'=>$album_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'album','form');

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
	            "图集" => APP::R('album','index'),
	            "图集管理" =>APP::R('album','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/album/index'
	 		]
		];
	}



}