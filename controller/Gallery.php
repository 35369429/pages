<?php
use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Tuan as Tuan;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Conf as Conf;
use \Tuanduimao\Task as Task;
use \Mina\Storage\Local as Storage;
use \Endroid\QrCode\QrCode as Qrcode;
use Endroid\QrCode\LabelAlignment;
use \Endroid\QrCode\ErrorCorrectionLevel;
use \Mina\Pages\Model\Gallery;



class GalleryController extends \Tuanduimao\Loader\Controller {
	
	function __construct() {
	}




	/**
	 * 帮助页
	 * @return
	 */
	function help() {

		$data = [
			"gallery" => [
				"bgimage" => "/s/mina/pages/static/defaults/p7.jpg",
				"bgcolor" => "rgba(254,254,254,1)",
				"items" => [
					["text", ["text"=>"示例文字"], ["x"=>350, "y"=>530]]
				]
			]
		];

		App::render($data, 'gallery', 'help' );
	}


	// 首页
	function index(){

		$page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
		$keyword = trim($_GET['keyword']);

		$g = new Gallery();
		$resp = $g->getGallerys($page, ['keyword'=>$keyword]);

		$data['query'] = [
			"keyword"=>$keyword,
			"page" => $pages
		];
		
		$data['gallerys'] = $resp;
		App::render($data, 'gallery', 'search' );

		// echo "<pre>";
		// Utils::out( $data );
		// echo "</pre>";

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
	                "图集" => APP::R('gallery','index'),
	                "图集列表" =>'',
	        ]
		];
	}



	// 批量编辑图集图片
	function table() {
		App::render($data, 'gallery', 'table' );
	}



	// 读取图集中的图片
	function getdata() {

		$maxlen = 20; $columns = []; $data = [ ["示例文字", "/s/mina/pages/static/defaults/p7.jpg", "二维码链接"] ];
		for( $i=0; $i<$maxlen; $i++ ) {
			$name = chr( $i + 65 );
			array_push($columns,["name"=>$name]);
		}
		array_push($columns,["name"=>chr(85), 'readOnly'=>true, "renderer"=>"{{unikey}}"]);

		$resp = [
			"data" => $data,
			"columns" =>$columns,
			"colHeaders" => ["名称"],
			"pagination" => [
				"total" => 100,
				"perpage" => 50,
				"pages" => [1,2,3,4,5,6]
			],
			"image" => [
				"page" => [
					"bgimage" => "/s/mina/pages/static/defaults/p1.jpg",
					"origin" =>1
				],
				"items" => [
					[ "text", ["origin"=>0,"type"=>'vertical', "dir"=>'rtl', "width"=>68,"height"=>168], ["x"=>710, 'y'=>155] ],
					[ "qrcode", ["origin"=>2, "width"=>120], ["x"=>660, "y"=>20]]
				]
			],
			"status" => 'down'
		];

		echo json_encode($resp);
	}

	

	/**
	 * 图集预览页
	 * @return [type] [description]
	 */
	function album() {
		App::render($data, 'gallery', 'album' );
	}
	

	/**
	 * 选定某个图集
	 * @return [type] [description]
	 */
	function select(){
		App::render($data, 'gallery', 'select' );
	}


	/**
	 * 保存 Gallery 信息
	 * @return [type] [description]
	 */
	function save() {

		$json_string = Utils::unescape($_POST['data']);
		$data = json_decode( $json_string, true );

		if ( empty($data['template']) ) {
			throw new Excp("参数错误 (template 格式不正确 )", 402, ['json'=>$json_string]);
		}

		$g = new Gallery();
		$gallery =  $g->editorToGallery( $data['template'] );

		$rs = $g->save( $gallery );

		$resp = [
			"gallery"=>$gallery
		];
		if ( !empty($data['create']) ) {
			$images = $g->editorToImage($data['create']);
			$resp['create'] = $g->createImages( $rs['gallery_id'], $images );
		}

		Utils::out( $resp );

	}



	/**
	 * 显示图集图片
	 * @return [type] [description]
	 */
	function image() {

		$image_id = $_GET['image_id'];
		$media_id = $_GET['media_id'];
		$size = $_GET['size'];
		
		// 转向 media 图片呈现页
		if ( empty($media_id) ) { 

			if ( empty($image_id) ) {
				throw new Excp('参数错误', 402, ['image_id'=>$image_id, 'media_id'=>$media_id]);
			}

			$g = new Gallery();
			$media_id = $g->makeImage( $image_id );
		}

		$url = App::URI("mina", "image", "media", ["media_id"=>$media_id,  'size'=>$size]);
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$url}");

	}



	function test(){
		$g = new Gallery();
		$data = $g->getGallerys(1, ['keyword'=>'山海经']);

		Utils::out( $data );

	}


	/**
	 * 图集编辑器
	 * @return 
	 */
	function editor() {

		App::render($data, 'gallery', 'editor' );


		return [

			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			"js/plugins/masked-inputs/jquery.maskedinput.min.js",
		 			"js/plugins/bootstrap-colorpicker/bootstrap-colorpicker.min.js",

		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",

		    		"js/plugins/photoswipe/photoswipe.min.js", 
		    		"js/plugins/photoswipe/photoswipe-ui-default.min.js",

		    		"js/plugins/jquery-webtable/webtable.full.min.js",
		    		"js/plugins/jquery-webeditor/webeditor.full.min.js",
		    		"js/plugins/jquery-imgeditor/imgeditor.full.min.js",
		    		"js/plugins/jquery-webeditor/panel.full.min.js"
				],

			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css",

	 			"js/plugins/photoswipe/photoswipe.css",
	 			"js/plugins/photoswipe/default-skin/default-skin.css",

	 			"js/plugins/jquery-webtable/webtable.full.min.css?important",
	 			"js/plugins/jquery-webeditor/webeditor.full.min.css?important",
	 			"js/plugins/jquery-imgeditor/imgeditor.full.min.css?important",
	 			"js/plugins/jquery-webeditor/panel.full.min.css?important"
	 		],

			'crumb' => [
	                "图集" => APP::R('gallery','index'),
	                "图集列表" => APP::R('gallery','index'),
	                "制作动态图集" => '',
	        ],

	        'active'=> [
	 			'slug'=>'mina/pages/gallery/index'
	 		]
		];
	}

}