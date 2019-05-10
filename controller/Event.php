<?php
/**
 * Class EventController
 * 活动控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-05-10 11:02:54
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class EventController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 活动列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Event;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "活动列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'event','search.index');

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
	            "活动" => APP::R('event','index'),
	            "活动管理" =>'',
	        ]
		];
	}


	/**
	 * 活动详情表单
	 */
	function detail() {

		$event_id = trim($_GET['event_id']);
		$action_name = '新建活动';
		$inst = new \Xpmsns\Pages\Model\Event;
		
		if ( !empty($event_id) ) {
			$rs = $inst->getByEventId($event_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['title'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'event_id'=>$event_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'event','form');

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
	            "活动" => APP::R('event','index'),
	            "活动管理" =>APP::R('event','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/event/index'
	 		]
		];

	}



	/**
	 * 保存活动
	 * @return
	 */
	function save() {
        $data = $_POST;
        Utils::JsonFromInput( $data );
		$inst = new \Xpmsns\Pages\Model\Event;
		$rs = $inst->saveByEventId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除活动
	 * @return [type] [description]
	 */
	function remove(){
		$event_id = $_POST['event_id'];
		$inst = new \Xpmsns\Pages\Model\Event;
		$event_ids =$inst->remove( $event_id, "event_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$event_ids'=>$event_ids]]);
	}

	/**
	 * 复制活动
	 * @return
	 */
	function duplicate(){
		$event_id = $_GET['event_id'];
		$inst = new \Xpmsns\Pages\Model\Event;
		$rs = $inst->getByEventId( $event_id );
		$action_name =  $rs['title'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['event_id']);
		unset($rs['slug']);

		// 复制图片
		if ( is_array($rs['cover']) &&  !empty($rs['cover']['local'])) {
			$rs['cover'] = $inst->uploadCoverByEventId( $event_id, $rs['cover']['local'], true);
		}
		if ( is_array($rs['images'])) {

			$resp = [];
			foreach ($rs['images'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadImagesByEventId( $event_id, $fs['local'], $idx, true);
			}

			$rs['images'] = $resp;
		}

		if ( is_array($rs['hosts'])) {

			$resp = [];
			foreach ($rs['hosts'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadHostsByEventId( $event_id, $fs['local'], $idx, true);
			}

			$rs['hosts'] = $resp;
		}

		if ( is_array($rs['organizers'])) {

			$resp = [];
			foreach ($rs['organizers'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadOrganizersByEventId( $event_id, $fs['local'], $idx, true);
			}

			$rs['organizers'] = $resp;
		}

		if ( is_array($rs['sponsors'])) {

			$resp = [];
			foreach ($rs['sponsors'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadSponsorsByEventId( $event_id, $fs['local'], $idx, true);
			}

			$rs['sponsors'] = $resp;
		}

		if ( is_array($rs['medias'])) {

			$resp = [];
			foreach ($rs['medias'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadMediasByEventId( $event_id, $fs['local'], $idx, true);
			}

			$rs['medias'] = $resp;
		}

		if ( is_array($rs['speakers'])) {

			$resp = [];
			foreach ($rs['speakers'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadSpeakersByEventId( $event_id, $fs['local'], $idx, true);
			}

			$rs['speakers'] = $resp;
		}


		$data = [
			'action_name' =>  $action_name,
			'event_id'=>$event_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'event','form');

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
	            "活动" => APP::R('event','index'),
	            "活动管理" =>APP::R('event','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/event/index'
	 		]
		];
	}



}