<?php
/**
 * Class SiteconfController
 * 站点配置控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-28 20:36:48
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
        Utils::JsonFromInput( $data );
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
		if ( is_array($rs['icon']) &&  !empty($rs['icon']['local'])) {
			$rs['icon'] = $inst->uploadIconBySiteId( $site_id, $rs['icon']['local'], true);
		}
		if ( is_array($rs['icon_light']) &&  !empty($rs['icon_light']['local'])) {
			$rs['icon_light'] = $inst->uploadIconlightBySiteId( $site_id, $rs['icon_light']['local'], true);
		}
		if ( is_array($rs['icon_dark']) &&  !empty($rs['icon_dark']['local'])) {
			$rs['icon_dark'] = $inst->uploadIcondarkBySiteId( $site_id, $rs['icon_dark']['local'], true);
		}
		if ( is_array($rs['site_logo']) &&  !empty($rs['site_logo']['local'])) {
			$rs['site_logo'] = $inst->uploadSitelogoBySiteId( $site_id, $rs['site_logo']['local'], true);
		}
		if ( is_array($rs['site_logo_light']) &&  !empty($rs['site_logo_light']['local'])) {
			$rs['site_logo_light'] = $inst->uploadSitelogolightBySiteId( $site_id, $rs['site_logo_light']['local'], true);
		}
		if ( is_array($rs['site_logo_dark']) &&  !empty($rs['site_logo_dark']['local'])) {
			$rs['site_logo_dark'] = $inst->uploadSitelogodarkBySiteId( $site_id, $rs['site_logo_dark']['local'], true);
		}
		if ( is_array($rs['qr_wxapp']) &&  !empty($rs['qr_wxapp']['local'])) {
			$rs['qr_wxapp'] = $inst->uploadQrwxappBySiteId( $site_id, $rs['qr_wxapp']['local'], true);
		}
		if ( is_array($rs['qr_wxpub']) &&  !empty($rs['qr_wxpub']['local'])) {
			$rs['qr_wxpub'] = $inst->uploadQrwxpubBySiteId( $site_id, $rs['qr_wxpub']['local'], true);
		}
		if ( is_array($rs['qr_wxse']) &&  !empty($rs['qr_wxse']['local'])) {
			$rs['qr_wxse'] = $inst->uploadQrwxseBySiteId( $site_id, $rs['qr_wxse']['local'], true);
		}
		if ( is_array($rs['qr_android']) &&  !empty($rs['qr_android']['local'])) {
			$rs['qr_android'] = $inst->uploadQrandroidBySiteId( $site_id, $rs['qr_android']['local'], true);
		}
		if ( is_array($rs['qr_ios']) &&  !empty($rs['qr_ios']['local'])) {
			$rs['qr_ios'] = $inst->uploadQriosBySiteId( $site_id, $rs['qr_ios']['local'], true);
		}
		if ( is_array($rs['images'])) {

			$resp = [];
			foreach ($rs['images'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadImagesBySiteId( $site_id, $fs['local'], $idx, true);
			}

			$rs['images'] = $resp;
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