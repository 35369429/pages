<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;


class SettingController extends \Xpmse\Loader\Controller {
	
	function __construct() {
	}



	/**
	 * SEO 首页
	 * @return [type] [description]
	 */
	function seo(){

		App::render($data,'setting','seo');

		return [

			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
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
	                "设置" =>  APP::R('setting','seo'),
	                "SEO设置" => '',
	        ],

	        'active'=> [
	 			'slug'=>'xpmsns/pages/setting/seo'
	 		]
		];
	}


	/**
	 * SEO 设定机器人表单
	 * @return [type] [description]
	 */
	function seoRobots() {
		App::render($data,'setting','seo.robots');
	}



	/**
	 * SEO 百度链接提交日志
	 * @return [type] [description]
	 */
	function seoUpdateLogs(){
		App::render($data,'setting','seo.updatelogs');
	}



	/**
	 * 设置首页
	 * @return [type] [description]
	 */
	function index() {

		$data['message'] = '
		请在 系统 > 微信 > 公众平台 中完成公众号绑定
		<a href="'. App::URI('baas-admin', 'conf', 'index').'"> 立即设置</a>';
		
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
			'crumb' => [
	            "设置" => APP::R('Setting','index'),
	            "选项" =>'',
	        ]
		];
	}


	function hello(){

		return;

		$spider = new \Xpmsns\pages\Model\Spider;
		$data = $spider->crawl( 
			// "https://www.toutiao.com/a6513648077373440515/"
			"https://mp.weixin.qq.com/s?__biz=MzIzMTA0NzI1OQ==&mid=212221252&idx=1&sn=8259879462ea08afb41a4c995aa27061&scene=1&srcid=0930ftVT16lyJjofbBYMsght&from=singlemessage&isappinstalled=0#rd" 
		);

		echo "<div style='padding:40px;'>{$data['content']}</div>";
		// print_r($data);
	}


	function faker() {

		return;
		$this->loadcates();
		echo json_encode(['code'=>0, 'message'=>'数据生成完毕']);
	}


	private function loadcates( $json_file=null ) {
		if ( empty($json_file) ) {
			$json_file = __DIR__ . "/../test/res/category.json";
		}

		if ( !file_exists($json_file) ) {
			throw new Excp("类型文件不存在 ($json_file)",404 );
		}

		$json_text = file_get_contents($json_file);
		$cates = Utils::json_decode($json_text);

		$cate = new \Xpmsns\Pages\Model\Category;
		$cate->runSQL("truncate table {{table}}");

		foreach ($cates as $c ) {
			$c['status'] = 'on';
			$cate->create( $c );
		}

	}

}