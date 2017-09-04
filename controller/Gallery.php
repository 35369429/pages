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
			"page" => $page
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



	// Helper: 批量编辑图集图片 
	function table() {
		
		$gallery_id = !empty($_POST['gallery_id']) ? $_POST['gallery_id'] : $_POST['id'];
		$image_id = $_POST['image_id'];
		$page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
		$keyword = trim($_POST['keyword']);



		$data['query'] = [
			"keyword"=>$keyword,
			"page" => $page,
			"image_id" => $image_id,
			"gallery_id" => $gallery_id
		];

		App::render($data, 'gallery', 'table' );
	}



	// Ajax: 读取图集中的图片
	function getdata() {

		$gallery_id = $_GET['gallery_id'];
		$image_id = $_GET['image_id'];
		$page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
		$keyword = trim($_GET['keyword']);

		$query = [
			"keyword"=>$keyword,
			"page" => $page,
			"image_id" => $image_id,
			"gallery_id" => $gallery_id
		];

		$g = new Gallery();

		$resp  = []; $gallery = null;
		

		if ( !empty($gallery_id) ){
			$resp = $g->getImagesData($page, $query, 40);
			$gallery = $g->getGallery($gallery_id);
		}

		if ( empty($resp['data']) ) {
			$resp = $g->emptyImageData();
		}

		$resp['gallery'] = $gallery; 
		$resp['status'] = 'done';
		$resp['query'] =  $query;

		echo json_encode($resp);
	}

	

	/**
	 * Helper: 图集预览页
	 * @return [type] [description]
	 */
	function album() {

		$gallery_id = !empty($_POST['gallery_id']) ? $_POST['gallery_id'] : $_POST['id'];
		$image_id = $_POST['image_id'];
		$page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
		$keyword = trim($_POST['keyword']);

		$g = new Gallery();
		$resp = $g->getImages($page, [
			'gallery_id' => $gallery_id,
			'keyword'=>$keyword]
		);

		$data['query'] = [
			"keyword"=>$keyword,
			"page" => $page,
			"image_id" => $image_id,
			"gallery_id" => $gallery_id
		];

		$data['images'] = $resp;
		$data['gallery'] = $g->getGallery($gallery_id);


		// echo "<pre>";
		// print_r( $gallery );
		// echo "</pre>";

		// return;

		// select count(1) as num from core_media where _id<4 order by _id desc;

		App::render($data, 'gallery', 'album' );

	}
	

	/**
	 * Helper: 选定某个图集
	 * @return [type] [description]
	 */
	function select(){
		App::render($data, 'gallery', 'select' );
	}


	/**
	 * 删除 Gallery
	 * @return [type] [description]
	 */
	function remove() {
		$gallery_id = !empty($_POST['gallery_id']) ? $_POST['gallery_id'] : $_POST['id'];
		$g = new Gallery();
		$resp = $g->rm($gallery_id);
		Utils::out(['message'=>'删除成功']);
	}


	/**
	 * 保存 Gallery 信息 (未处理Update )
	 * @return [type] [description]
	 */
	function save() {

		$json_string = Utils::unescape($_POST['data']);
		$data = json_decode( $json_string, true );

		if ( empty($data['template']) ) {
			throw new Excp("参数错误 (template 格式不正确 )", 402, ['json'=>$json_string]);
		}

		if( json_last_error() !== JSON_ERROR_NONE) {
			throw new Excp('提交数据异常', 500, ['_POST'=>$_POST]);
		}

		// $g = new Gallery();
		// $images = array_merge(
		// 	$g->editorToImage($data['create']), 
		// 	$g->editorToImage($data['update']), 
		// 	$g->editorToImage($data['remove'])
		// );
		// Utils::out( ['images'=>$images, 'data'=>$data] );
		// return;

		$g = new Gallery();
		$gallery =  $g->editorToGallery( $data['template'] );
		$rs = $g->save( $gallery );
		$resp = [
			"gallery"=>$gallery
		];


		// 新增记录
		if ( !empty($data['create']) ) {
			$images = $g->editorToImage($data['create']);
			$resp['create'] = $g->createImages( $rs['gallery_id'], $images );
		}


		// 修改记录
		if ( !empty($data['update']) ) {
			$images = $g->editorToImage($data['update']);
			$resp['update'] = $g->updateImages( $images );
		}

		// 删除记录
		if ( !empty($data['remove']) ) {
			$images = $g->editorToImage($data['remove']);
			$resp['remove'] = $g->removeImages( $images );
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
		
		$g = new Gallery();

		// 转向 media 图片呈现页
		if ( empty($media_id) ) { 

			if ( empty($image_id) ) {
				$url =  App::URI("mina", "image", "error", ["message"=>"图片不存在 ({$image_id})"]);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: {$url}");
				return;
			}

			try {
				$media_id = $g->makeImage( $image_id );
			} catch( Excp $e  ){
				$url =  App::URI("mina", "image", "error", ["message"=>"生成图片出错 (" .$e->getMessage().")"]);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: {$url}");
				return;
			} catch( Exception $e ) {
				$url =  App::URI("mina", "image", "error", ["message"=>"生成图片出错 (" .$e->getMessage().")"]);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: {$url}");
				return;
			}
		}

		$url = $g->media->getImageUrl( $media_id, $size );
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$url}");
	}



	function imagelive() {

		$image_id = $_GET['image_id'];

		$g = new Gallery();
		$image = $g->makeImage( $image_id , true );
		// header("Content-Type: image/png");
		// echo $image;
	}



	function test(){
		Utils::cliOnly();
		$g = new Gallery();
		$data = $g->getGallerys(1, ['keyword'=>'山海经']);

		Utils::out( $data );

	}


	/**
	 * 图集编辑器
	 * @return 
	 */
	function editor() {

		$gallery_id = $_GET['gallery_id'];
		$image_id = $_GET['image_id'];

		$data = [
			'gallery_id' => $gallery_id,
			'image_id' => $image_id,
			'editor' => $_GET['editor']
		];

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
	                "动态图集" => '',
	        ],

	        'active'=> [
	 			'slug'=>'mina/pages/gallery/index'
	 		]
		];
	}

}