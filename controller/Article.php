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



class ArticleController extends \Tuanduimao\Loader\Controller {
	
	function __construct() {
	}


	// 图文列表页
	function index() {

		$art = new \Mina\Pages\Api\Article;
		$query = $_REQUEST;
		$query['select'] = ['article_id', 'draft.title as draft_title', 'title', 'author', 'category', 'publish_time', 'update_time', 'create_time', 'status', 'draft.status as draft_status'];
		$query['perpage'] = isset($_REQUEST['perpage']) ?  $_REQUEST['perpage'] : 10;
		$query['order'] =  isset($_REQUEST['order']) ?  $_REQUEST['order'] : 'create_time desc';
		$resp  = $art->call('search', $query);

		$cate = new \Mina\Pages\Model\Category;
		$wechats = $cate->wechat();

		$art = new \Mina\Pages\Model\Article;
		$rs = $art->getline( "WHERE status=?",["count(*) as cnt"], ['pending']);
		$pending = 0;
		if ( !empty($rs) ){
			$pending = $rs['cnt'];
		}


		$data = [
			'articles' => $resp,
			'query' => $query,
			'category' => $cate,
			'pending' => $pending,
			'article' => new \Mina\Pages\Model\Article,
			'wechats' => $wechats
		];

		App::render($data,'article','search.index');
		
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
	                 "图文" => APP::R('article','index'),
	                 "文章列表" =>'',
	        ]
		];
	}




	function test(){

		// 需要安 ffmpeg
		// apt-get install ffmpeg
		$ffmpeg = FFMpeg\FFMpeg::create();


		return;

		Utils::cliOnly();
		set_time_limit(0);
		$art = new  \Mina\Pages\Model\Article;
		$art->downloadFromWechat('wx77e0de6921bacc92', 15);

		// $art->downloadFromWechat('wx77e0de6921bacc92');

	}


	function testschedule() {

		Utils::cliOnly();
		file_put_contents("/tmp/testschedule", time() . "\n", FILE_APPEND );
		Utils::out(['code'=>0, 'message'=>'testschedule success']);
	}


	function testrun(){
		Utils::cliOnly();
		file_put_contents("/tmp/testrun", time() . "\n", FILE_APPEND );
		Utils::out(['code'=>0, 'message'=>'testrun success']);
	}


	/**
	 * 删除文章
	 * @return [type] [description]
	 */
	function remove(){

		$article_id = $_REQUEST['id'];
		if ( empty($article_id) ) {
			throw new Excp("未知文章( {$article_id})" , 400, ['article_id'=>$article_id]);
		}

		$article = new \Mina\Pages\Model\Article;
		$resp = $article->rm($article_id, 'article_id');

		if ( $resp === false ){
			throw new Excp("删除失败 ( {$article_id})" , 400, ['resp'=>$resp]);
		}

		Utils::out(['code'=>0]);

	}


	/**
	 * 预览链接
	 * @return [type] [description]
	 */
	function previewlinks(){

		$article_id  = intval($_GET['id']);
		if ( empty($article_id) ) {
			echo "<span class='text-danger'>文章尚未保存</span>";
			return;
		}

		$article = new \Mina\Pages\Model\Article;
		$data['pages'] = $article->previewLinks( $article_id);
		App::render($data,'article','preview.popover');
	}


	/**
	 * 访问链接
	 * @return [type] [description]
	 */
	function links() {
		$article_id  = intval($_GET['id']);
		if ( empty($article_id) ) {
			echo "<span class='text-danger'>未知文章信息</span>";
			return;
		}

		$article = new \Mina\Pages\Model\Article;
		if ( $article->isPublished($article_id) === false ) {
			echo "<span class='text-danger'>文章尚未发布</span>";
			return;
		}

		$data['pages'] = $article->links( $article_id );
		App::render($data,'article','links.popover');
	}


	/**
	 * 生成二维码
	 * @return [type] [description]
	 */
	function qr() {

		$code =  !empty($_REQUEST['link']) ? urldecode($_REQUEST['link']) : 'tuanduimao.com';
		$option = $_REQUEST;
		if ( isset( $option['background']) ) {
			$c = explode(',', $option['background']);
			if ( count($c) == 4) {
				$option['background'] = ['r' => $c[0], 'g' => $c[1], 'b' => $c[2], 'a' => $c[3]];
			}
		}

		if ( isset( $option['foreground']) ) {
			$c = explode(',', $option['foreground']);
			if ( count($c) == 4) {
				$option['foreground'] = ['r' => $c[0], 'g' => $c[1], 'b' => $c[2], 'a' => $c[3]];
			}
		}
		$option['size'] = !empty($option['size']) ? $option['size'] : 300;
		$option['padding'] = !empty($option['padding']) ? $option['padding'] : 10;
		$option['background'] = is_array($option['background']) ? $option['background'] : ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0];
		$option['foreground'] = is_array($option['color']) ? $option['color'] : ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
		$option['fontsize'] = !empty($option['fontsize']) ? $option['fontsize'] : 14;
		$option['label'] = isset($option['label']) ? $option['label'] : '扫描二维码';
		$option['font'] = !empty($option['font']) ? $option['font'] : 'LantingQianHei.ttf';
		$logo = !empty($option['logo']) ? $option['logo'] : '';
		$logosize = !empty($option['logosize']) ? $option['logosize'] : 50;


		$qr = new QrCode();
		$qr ->setWriterByName('png')
		    ->setEncoding('UTF-8')
		    ->setText($code)
		    ->setSize($option['size'])
		    ->setMargin( $option['padding'] )
		    ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
		    ->setForegroundColor($option['foreground'])
		    ->setBackgroundColor($option['background'])
		    ->setLabel(
		    	$option['label'], $option['fontsize'],  
		    	Utils::getFontPath(1), 
		    	LabelAlignment::CENTER )
		    ->setValidateResult(false);

		if ( !empty($logo) ) {

			$logoBlob = null;
			if( substr($logo, 0, 4) == 'http' || is_readable($logo) ) {
				$logoBlob = file_get_contents($logo);
			} 

			if ( !empty($logoBlob) ) {
				$logopath = sys_get_temp_dir() . "/" . time() . ".logo";
				file_put_contents($logopath, $logoBlob);
				$qr->setLogoPath( $logopath);
				$qr->setLogoWidth( $logosize );
			}
		}


		header('Content-Type: image/png');
		echo $qr->writeString();

	}


	/**
	 * 生成小程序码
	 * @return [type] [description]
	 */
	function apqr(){
	}


	/**
	 * 保存文章
	 * @return 
	 */
	function save()  {
		// sleep(1);
		$article = new \Mina\Pages\Model\Article;
		$rs = $article->save( json_decode(App::input(),true) );
		Utils::out( $rs );
	}


	/**
	 * 同步文章
	 * @return [type] [description]
	 */
	function realdownfromwechat() {

		Utils::cliOnly();
		set_time_limit(0);
		$ids = explode(',', $_POST['ids']);
		if ( count($ids) == 0 || $_POST['ids'] == "" ) {
			throw new Excp('请选择至少一个公众号', 404, ['article_id'=>$article_id, 'mpids'=>$mpids, 'create'=>$create]);
		}


		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : null;
		$art = new  \Mina\Pages\Model\Article;
		foreach ($ids as $appid) {
			$art->downloadFromWechat($appid, $offset);
		}

		echo json_encode(['download'=>'success']);
	}

	// 抓取文章中的图片
	function realdownloadimages(){
		Utils::cliOnly();
		set_time_limit(0);
		$article_id = $_POST['article_id'];
		$status = $_POST['status'];

		if (empty($article_id)) {
			throw new Excp('未知文章数据', 404, ['article_id'=>$article_id, 'status'=>$status]);
		}
		$art = new \Mina\Pages\Model\Article;
		$rs = $art->downloadImages($article_id, $status );
		echo json_encode([$article_id, $status, $rs]);
	}


	function downfromwechat() {

		$ids = explode(',', $_POST['ids']);
		if ( count($ids) == 0 || $_POST['ids'] == "" ) {
			throw new Excp('请选择至少一个公众号', 404, ['article_id'=>$article_id, 'mpids'=>$mpids, 'create'=>$create]);
		}

		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : null;
		$t = new Task;
		if ( $t->isRunning('从微信下载文章', 'mina/pages') ) {
			throw new Excp('下载中，任务尚未完成', 400, ['ids'=>$_POST['ids'], "offset"=>$offset] );	
		}

		$task_id = $t->run('从微信下载文章', [
			"app_name" => "mina/pages",
			"c" => 'article',
			'a' => 'realdownfromwechat',
			'data'=> [
				"ids" => $_POST['ids'],
				"offset" => $offset
			]
		]);

		echo json_encode(['message'=>"下载任务创建成功", 'task_id'=>$task_id]);
	}


	function downstatus() {
		$art = new \Mina\Pages\Model\Article;
		$t = new Task;
		if ( $t->isRunning('从微信下载文章', 'mina/pages') ) {
			$count  = 1;
		} else {
			$rs = $art->getline( "WHERE status=? ",["count(*) as cnt"], ['pending']);
			$count = 0;
			if ( !empty($rs) ){
				$count = $rs['cnt'];
			}
		}
		echo json_encode(['count' =>$count ]);
	}


	function uptowechat() {
		$article_id = intval($_POST['id']);
		$mpids = explode(',', $_POST['mpids']);
		$create = isset($_POST['create']) ? $_POST['create'] : null;

		if ( empty($article_id) ) {
			throw new Excp('未知文章信息 (ID=null)', 404, ['article_id'=>$article_id, 'mpids'=>$mpids, 'create'=>$create]);
		}

		if ( count($mpids) == 0 || $_POST['mpids'] == "" ) {
			throw new Excp('请选择至少一个公众号', 404, ['article_id'=>$article_id, 'mpids'=>$mpids, 'create'=>$create]);
		}

		$art = new  \Mina\Pages\Model\Article;
		foreach ($mpids as $appid) {
			$art->uploadToWechat($appid, $article_id, $create);
		}
		echo json_encode(['upload'=>'success']);
	}
	


	/**
	 * 取消发布
	 * @return [type] [description]
	 */
	function cancelPublish() {
		// sleep(1);
		$data = json_decode(App::input(),true);
		if ( empty($data['article_id']) ) {
			throw new Excp("未知文章( {$data['article_id']})" , 400, ['data'=>$data]);
		}

		$article = new \Mina\Pages\Model\Article;
		$rs = $article->unpublished( $data['article_id'] );
		Utils::out( $rs );

	}

	// 文章编辑器
	function editor() {

		$opt = new \Tuanduimao\Option('mina/pages');
		$options = $opt->getAll();

		$art = new \Mina\Pages\Model\Article;
		$article = ['category'=>[], 'tag'=>[]];
		if ( !empty( $_GET['id']) ) {
			$article = $art->load( intval($_GET['id']) );
		}


		$cate = new \Mina\Pages\Model\Category;
		$wechats = $cate->wechat();

		$data = [
			'article' => $article,
			'wechats' => $wechats,
			'category' => $cate,
			'options' => $options['map']
		];

		App::render($data, 'article', 'editor' );
		
		return [

			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",

		    		"js/plugins/video-js/video.min.js",
		    		"js/plugins/jquery-webeditor/webeditor.full.min.js",
		    		"js/plugins/jquery-webeditor/panel.full.min.js"
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",

	 			"js/plugins/video-js/video-js.min.css",
	 			"js/plugins/jquery-webeditor/webeditor.full.min.css",
	 			"js/plugins/jquery-webeditor/panel.full.min.css?important"
	 		],

			'crumb' => [
	                "图文" => APP::R('article','index'),
	                "文章列表" => APP::R('article','index'),
	                "编辑文章" => '',
	        ],

	        'active'=> [
	 			'slug'=>'mina/pages/article/index'
	 		]
		];
	}


	private  function _randcate( $max=10 ) {
		$len = rand(0,$max);
		$data = [];
		for( $i=0; $i<$len; $i++ ){
			array_push( $data, rand(0,$max));
		}

		return array_unique($data);
	}

	private  function _randtag( $max=11 ) {
		$tags =['会议', '技术', '快讯', '机器学习', '大数据', '行业', '学术', '网文', 'AI', '人工智能', '无人机','机器人'];
		$len = rand(0,$max);
		$data = [];
		for( $i=0; $i<$len; $i++ ){
			array_push( $data, $tags[rand(0,$max)]);
		}
		return array_unique($data);
	}


	function testdata() {

		Utils::cliOnly();
		$c = App::M('Category');
		$c->runsql("truncate table `{{table}}`");
		$cates = [
			["name"=>"网上门诊", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"中心介绍", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"继续教育", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"医生沙龙", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"疑难病痛", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"诊疗新技术", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"学术交流", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"信息园地", "project"=>"deepblue"],
			["name"=>"新闻公告", "project"=>"deepblue"],
			["name"=>"专家介绍", "project"=>"deepblue"]
		];
		foreach( $cates as $cate ) {
			$c->create($cate);
		}

		$t = App::M('Tag');
		$t->runsql("truncate table `{{table}}`");
		$tags = [
			["name"=>"会议"],
			["name"=>"技术"],
			["name"=>"快讯"],
			["name"=>"行业"],
			["name"=>"学术"]
		];
		foreach( $tags as $tag ) {
			$t->create($tag);
		}

		$status = ['published', 'unpublished'];
		$a = App::M("Article");
		$a->runsql("truncate table `{{table}}`");
		$a->article_draft->runsql("truncate table `{{table}}`");

		$faker = Utils::faker();

		for( $i=0; $i<100; $i++ ) {
			$rs = [
				"title" => $faker->company,
				"category" => $this->_randcate(),
				'author' =>$faker->name,
				'publish_time' => date('Y-m-d H:i:s'),
				'status' => $status[rand(0,1)],
				'tag' => $this->_randtag()
			];

			Utils::out($rs );
			$a->save( $rs );
		}

	}

}