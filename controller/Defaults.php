<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;


class DefaultsController extends \Xpmse\Loader\Controller {
	
	function __construct() {
	}

	function index() {
		App::render($data,'web','index');
		
		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
			 		"js/plugins/dropzonejs/dropzone.min.js",
			 		"js/plugins/cropper/cropper.min.js",
		    		'js/plugins/jquery-ui/jquery-ui.min.js',
	        		'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
				],
			'css'=>[
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css"
	 		],
	 		'active' =>[
	 			'c'=>'article',
	 			'a'=>'index'
	 			// 'slug' => 'xpmsns/pages/article/index'
	 		],
			'crumb' => [
	            "返回图文" => APP::R('article','index'),
	            "菜单激活演示" =>'',
	        ]
		];
	}

	function faker() {
		echo "noting";
	}

}