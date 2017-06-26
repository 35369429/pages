<?php
use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Tuan as Tuan;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Conf as Conf;


class DefaultsController extends \Tuanduimao\Loader\Controller {
	
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
	 			// 'slug' => 'mina/pages/article/index'
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