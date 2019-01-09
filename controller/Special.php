<?php
/**
 * Class SpecialController
 * 专栏控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-09 13:36:40
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class SpecialController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 专栏列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Special;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "专栏列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'special','search.index');

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
	            "专栏" => APP::R('special','index'),
	            "专栏管理" =>'',
	        ]
		];
	}


	/**
	 * 专栏详情表单
	 */
	function detail() {

		$special_id = trim($_GET['special_id']);
		$action_name = '新建专栏';
		$inst = new \Xpmsns\Pages\Model\Special;
		
		if ( !empty($special_id) ) {
			$rs = $inst->getBySpecialId($special_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['name'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'special_id'=>$special_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'special','form');

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
                    "js/plugins/codemirror/lib/codemirror.js",
                    "js/plugins/codemirror/addon/search/searchcursor.js",
                    "js/plugins/codemirror/addon/search/search.js",
                    "js/plugins/codemirror/addon/dialog/dialog.js",
                    "js/plugins/codemirror/addon/edit/matchbrackets.js",
                    "js/plugins/codemirror/addon/edit/closebrackets.js",
                    "js/plugins/codemirror/addon/comment/comment.js",
                    "js/plugins/codemirror/addon/wrap/hardwrap.js",
                    "js/plugins/codemirror/addon/fold/foldcode.js",
                    "js/plugins/codemirror/addon/fold/brace-fold.js",
                    "js/plugins/codemirror/mode/javascript/javascript.js",
                    "js/plugins/codemirror/mode/shell/shell.js",
                    "js/plugins/codemirror/mode/sql/sql.js",
                    "js/plugins/codemirror/mode/python/python.js",
                    "js/plugins/codemirror/mode/go/go.js",
                    "js/plugins/codemirror/mode/php/php.js",
                    "js/plugins/codemirror/mode/htmlmixed/htmlmixed.js",
                    "js/plugins/codemirror/mode/xml/xml.js",
                    "js/plugins/codemirror/mode/css/css.js",
                    "js/plugins/codemirror/mode/sass/sass.js",
                    "js/plugins/codemirror/mode/vue/vue.js",
                    "js/plugins/codemirror/mode/textile/textile.js",
                    "js/plugins/codemirror/mode/clike/clike.js",
                    "js/plugins/codemirror/mode/markdown/markdown.js",
                    "js/plugins/codemirror/keymap/sublime.js",
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/summernote/summernote.css",
                "js/plugins/summernote/summernote-bs3.min.css",
                "js/plugins/codemirror/lib/codemirror.css",
                "js/plugins/codemirror/addon/fold/foldgutter.css",
                "js/plugins/codemirror/addon/dialog/dialog.css",
                "js/plugins/codemirror/theme/monokai.css",
	 		],

			'crumb' => [
	            "专栏" => APP::R('special','index'),
	            "专栏管理" =>APP::R('special','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/special/index'
	 		]
		];

	}



	/**
	 * 保存专栏
	 * @return
	 */
	function save() {
        $data = $_POST;
        Utils::JsonFromInput( $data );
		$inst = new \Xpmsns\Pages\Model\Special;
		$rs = $inst->saveBySpecialId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除专栏
	 * @return [type] [description]
	 */
	function remove(){
		$special_id = $_POST['special_id'];
		$inst = new \Xpmsns\Pages\Model\Special;
		$special_ids =$inst->remove( $special_id, "special_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$special_ids'=>$special_ids]]);
	}

	/**
	 * 复制专栏
	 * @return
	 */
	function duplicate(){
		$special_id = $_GET['special_id'];
		$inst = new \Xpmsns\Pages\Model\Special;
		$rs = $inst->getBySpecialId( $special_id );
		$action_name =  $rs['name'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['special_id']);
		unset($rs['user_id']);
		unset($rs['name']);
		unset($rs['path']);

		// 复制图片
		if ( is_array($rs['logo'])) {

			$resp = [];
			foreach ($rs['logo'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadLogoBySpecialId( $special_id, $fs['local'], $idx, true);
			}

			$rs['logo'] = $resp;
		}

		if ( is_array($rs['docs'])) {

			$resp = [];
			foreach ($rs['docs'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadDocsBySpecialId( $special_id, $fs['local'], $idx, true);
			}

			$rs['docs'] = $resp;
		}


		$data = [
			'action_name' =>  $action_name,
			'special_id'=>$special_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'special','form');

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
                    "js/plugins/codemirror/lib/codemirror.js",
                    "js/plugins/codemirror/addon/search/searchcursor.js",
                    "js/plugins/codemirror/addon/search/search.js",
                    "js/plugins/codemirror/addon/dialog/dialog.js",
                    "js/plugins/codemirror/addon/edit/matchbrackets.js",
                    "js/plugins/codemirror/addon/edit/closebrackets.js",
                    "js/plugins/codemirror/addon/comment/comment.js",
                    "js/plugins/codemirror/addon/wrap/hardwrap.js",
                    "js/plugins/codemirror/addon/fold/foldcode.js",
                    "js/plugins/codemirror/addon/fold/brace-fold.js",
                    "js/plugins/codemirror/mode/javascript/javascript.js",
                    "js/plugins/codemirror/mode/shell/shell.js",
                    "js/plugins/codemirror/mode/sql/sql.js",
                    "js/plugins/codemirror/mode/python/python.js",
                    "js/plugins/codemirror/mode/go/go.js",
                    "js/plugins/codemirror/mode/php/php.js",
                    "js/plugins/codemirror/mode/htmlmixed/htmlmixed.js",
                    "js/plugins/codemirror/mode/xml/xml.js",
                    "js/plugins/codemirror/mode/css/css.js",
                    "js/plugins/codemirror/mode/sass/sass.js",
                    "js/plugins/codemirror/mode/vue/vue.js",
                    "js/plugins/codemirror/mode/textile/textile.js",
                    "js/plugins/codemirror/mode/clike/clike.js",
                    "js/plugins/codemirror/mode/markdown/markdown.js",
                    "js/plugins/codemirror/keymap/sublime.js",
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/summernote/summernote.css",
                "js/plugins/summernote/summernote-bs3.min.css",
                "js/plugins/codemirror/lib/codemirror.css",
                "js/plugins/codemirror/addon/fold/foldgutter.css",
                "js/plugins/codemirror/addon/dialog/dialog.css",
                "js/plugins/codemirror/theme/monokai.css",
	 		],

			'crumb' => [
	            "专栏" => APP::R('special','index'),
	            "专栏管理" =>APP::R('special','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/special/index'
	 		]
		];
	}



}