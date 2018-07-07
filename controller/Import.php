<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Task as Task;
use \Mina\Storage\Local as Storage;
use \Endroid\QrCode\QrCode as Qrcode;
use Endroid\QrCode\LabelAlignment;
use \Endroid\QrCode\ErrorCorrectionLevel;

use andreskrey\Readability\Readability;
use andreskrey\Readability\HTMLParser;
use andreskrey\Readability\Configuration;

class ImportController extends \Xpmse\Loader\Controller {

	private $media = null;
	private $host = null;

	function __construct() {
	}

	/**
	 * 导入表单
	 */
	function form() {

		$cate = new \Xpmsns\pages\Model\Category;
		$cates = $cate->search(["perpage"=>100]);
	
		$data = [
			'home' => Utils::getHome(App::$APP_HOME_LOCATION),
			'url' => $_GET['url'],
			'published'=> $_GET['published'],
			'category' => $cate,
			'cates' =>$cates
		];

		App::render($data,'import','form');

		return [

			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",
		    		"js/plugins/plupload/plupload.full.min.js",
					"js/plugins/plupload/jquery.plupload.queue/jquery.plupload.queue.js"
			],
			
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css"
	 		],

			'crumb' => [
	                "导入数据" => APP::R('article','import'),
	                "导入" => '',
	        ],

	        'active'=> [
	 			'slug'=>'xpmsns/pages/article/import'
	 		]
		];
	}


	/**
	 * 大文件上传
	 * @return [type] [description]
	 */
	function upload() {

		// 上传文件
		if (empty($_FILES) || $_FILES['file']['error']) {
			die('{"OK": 0, "info": "Failed to move uploaded file."}');
		}
		 
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
		 
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];
		$fileName = md5($fileName);
		$filePath = "/tmp/$fileName";

		// Open temp file
		$out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
		if ($out) {
			// Read binary input stream and append it to temp file
			$in = @fopen($_FILES['file']['tmp_name'], "rb");
		 
			if ($in) {
				while ($buff = fread($in, 4096))
					fwrite($out, $buff);
			} else
				die('{"OK": 0, "info": "Failed to open input stream."}');
		 
			@fclose($in);
			@fclose($out);
		 
			@unlink($_FILES['file']['tmp_name']);
		} else {
			die('{"OK": 0, "info": "Failed to open output stream."}');
		}
		 
		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
			// Strip the temp .part suffix off
			rename("{$filePath}.part", $filePath);
		}


		
		if ( $_GET['type'] == "wordpress" ) {

			// 创建导入任务
			$t = new Task;
			if ( $t->isRunning('导入WordPress数据数据', 'xpmsns/pages') ) {
				throw new Excp('导入WordPress数据数据，任务尚未完成', 400, [] );	
			}

			$task_id = $t->run('导入WordPress数据数据', [
				"app_name" => "xpmsns/pages",
				"c" => 'import',
				'a' => 'run',
				'data'=> [
					"file" => $filePath,
					"type" =>'wordpress'
				]
			]);

			echo json_encode(["OK"=>1, 'info'=>"导入WordPress数据任务创建成功", "code"=>0, 'message'=>"导入WordPress数据任务创建成功", 'task_id'=>$task_id]);
		
		}


		// Utils::out($_FILES["file"]);
		// @unlink( $filePath ); 
		// die('{"OK": 1, "info": "Upload successful."}');
	}


	/**
	 * 数据入库
	 * @return [type] [description]
	 */
	function run() {
		$file = $_REQUEST['file'];
		$type = $_REQUEST["type"];

		if ( $type == 'wordpress' ) {
			$this->wordpress($file );	
		}
		// @unlink( $file );
		echo json_encode(['code'=>0, 'message'=>'数据导入完毕']);
		return;
	}


	private function wordpress( $file ) {
		libxml_disable_entity_loader(true);
		$xml = str_replace('wp:', 'wp_', file_get_contents($file));
		$xml = str_replace(':encoded', '', $xml);
		$xml_data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$wp_data = json_decode( json_encode($xml_data), true);


		// 检查版本
		if ( $wp_data["@attributes"]['version'] != '2.0' ) {
			throw new Excp('不支持的数据版本', 402, ['version'=>$wp_data["@attributes"]['version']]);
		}

		// 导入Site 
		$site = $this->wpSite( $wp_data['channel']);


		// 导入作者
		if ( array_key_exists('wp_author_id', $wp_data['channel']['wp_author']) ) {
			$wp_data['channel']['wp_author']  = [$wp_data['channel']['wp_author']];
		}
		$au = $this->wpAuthor( $wp_data['channel']['wp_author'] );

		// 导入内容
		if ( array_key_exists('title', $wp_data['channel']['item']) ) {
			$wp_data['channel']['item']  = [$wp_data['channel']['item']];
		}
		$this->wpItem( $wp_data['channel']['item'], $au, $site );

	}

	// 导入 WordPress 站点信息
	private function wpSite( & $channel ) {
		$site = [
			"site_name" => $channel['title'],
			"site_slogen"=> $channel['description'],
			"site_homepage" => $channel['wp_base_site_url'],
			"site_intro"=> $channel['description']
		];

		return $site;
	}

	// 导入 WordPress 作者
	private function wpAuthor( $author ){

		$u = new \Xpmse\User;
		$map = [];
		foreach ($author as $au ) {

			$rs = [
				"mobile" =>(string)(12000000000 + intval($au['wp_author_id'])),
				"email"  => $au['wp_author_email'],
				"name" =>$au['wp_author_display_name'],
				"password" => $au['wp_author_login'] . $au['wp_author_id']
			];

			$map[$au['wp_author_id']] = $this->saveUser( $u, $rs );
		}

		return $map;
	}

	private function wpItem( & $item, & $au, & $st ){

		$art = new \Xpmsns\Pages\Model\Article;

		foreach ($item as $it) {

			if ( $it['wp_post_type'] != 'post') {
				continue;
			}

			$postmeta = [];

			// 处理 PostMedia 
			foreach ($it['wp_postmeta'] as $pm ) {
				$key = $pm['wp_meta_key'];
				try { 
					if ( is_string($pm['wp_meta_value']) ) {
						$val = unserialize($pm['wp_meta_value']); 
					}
				} catch( Exception $e ) { $val = $pm['wp_meta_value']; }
				if ( empty( $val) ) {
					$val = $pm['wp_meta_value'];
				}

				$postmeta[$key] = $val;
			}

			$author = $au[$postmeta['_edit_last']];

			$rs['content'] = $it['content'];
			$rs['title'] = $it['title'];
			$rs['author'] = $author['name'];
			$rs['cover'] = $postmeta['vimg'];
			$rs['summary'] = is_array($it['description']) ? trim($it['description'][0]) : trim($it['description']);
			$rs['publish_date'] = date('Y-m-d', strtotime($it['pubDate']) );
			$rs['publish_time'] = date('H:i:s', strtotime($it['pubDate']) );
			$rs['create_time'] = $it['wp_post_date'];
			$rs['update_time'] = $it['wp_post_date'];
			$rs['status'] = 'pending';
			$rs['outer_id'] = md5('wp_' . $st['site_homepage']) . '_' . $it['wp_post_id'];
			$rs['videos'][0] = $postmeta['td_post_video']['td_video'];
			$rs['view_cnt'] = $postmeta['post_views_count'];
			$rs['category_names'] = $it['category'];
			$rs['user'] = $author['userid'];
			// $it['_mk'] = $postmeta;
			$this->saveArticle($art, $rs );
		}
	}


	/**
	 * 抓取图片
	 * @param  [type] $url [description]
	 * @return [type]      [description]
	 */
	private function fetchImage( $url ) {
		if ( is_null($this->media) ) {
			$this->host = Utils::getHome();  // 页面跟地址
			$this->media = new \Xpmse\Media(['host'=>$this->host]);
		}

		$ext = $this->media->getExt( $url );
		if ( !in_array($ext, ['png', 'jpg', 'gif', 'peg']) ) {
			$ext = 'png';
		}
		try {
			$nimg = $this->media->uploadImage($url, $ext, false);
		} catch( Excp $e ) { $nimg['path'] = null; }

		return $nimg;
	}


	private function saveArticle( & $art,  $rs , $it = null) {
		// if ($rs['title'] != '戊戌年庆贺吕祖圣诞祈福法会吕祖朝科北京白云观') {
		// 	return;
		// }

		// echo "{$rs['title']}... ";

		$videos  = $rs['videos'];
		$art->contentToDelta( $rs );

		// 处理视频
		if ( !empty($videos) && is_array($videos) ) {
			foreach ($videos as $v ) {
				$art->insertVideo($v, $rs );
			}
		}

		// 保存文章
		try {
			$rs = $art->save( $rs );
		} catch( Excp $e  ){}


		// 下载文章图片
		try {
			$rs = $art->downloadImages($rs['article_id'], 'published');
		} catch( Excp $e  ){ echo $e->getMessage(); }

		// echo " article_id={$rs['article_id']} ";
		// echo " videos=".count($rs['videos']);
		// echo " delta=" .count($rs['delta']['ops']);
		// echo " content=" .strlen(json_encode($rs['content'])) ;

		// echo "  \n";
		// 
		return $rs;

	}



	private function saveUser( & $u,  & $rs ) {

		$avatar = $u->genAvatar( $rs['name'] );
		try {
			$resp = $u->create([
				'userid'=> $u->genUserid(),
				'name'=>$rs['name'],
				'avatar'=>$avatar['avatar'],
				'mobile'=>$rs['mobile'],
				'email'=>$rs['email'],
				'isAdmin'=>false,
				'isBoss'=>false,
				'department'=>[1],
				'remark' => "其他系统导入, 初始密码: {$rs['password']}",
				'password'=> password_hash( $rs['password'], PASSWORD_BCRYPT, ['cost'=>12] )
			]);	
		} catch ( Excp $e ) { 
			if (  $e->getCode() == 1062 ) {
				$resp = $u->getBy('mobile', $rs['mobile'] );
			}
		}

		return $resp;
	}


}