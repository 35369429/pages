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



class ArticleController extends \Xpmse\Loader\Controller {
	
	function __construct() {
	}


	// 图文列表页
	function index() {

		$art = new \Xpmsns\pages\Api\Article;
		$query = $_REQUEST;
		$query['select'] = ['article_id', 'draft.title as draft_title', 'title', 'author', 'category', 'publish_time', 'update_time', 'create_time', 'status', 'draft.status as draft_status'];
		$query['perpage'] = isset($_REQUEST['perpage']) ?  $_REQUEST['perpage'] : 10;
		$query['order'] =  isset($_REQUEST['order']) ?  $_REQUEST['order'] : 'create_time desc';
		$resp  = $art->call('search', $query);



		// echo "<pre>";
		// print_r($query);
		// print_r($resp);
		// echo "</pre>";


		$cate = new \Xpmsns\pages\Model\Category;
		$wechats = $cate->wechat();

		$art = new \Xpmsns\pages\Model\Article;
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
			'article' => new \Xpmsns\pages\Model\Article,
			'wechats' => $wechats
		];

		if ( $_GET['debug'] == 1 ) {
			echo "<pre>";
			print_r($data);
			echo "</pre>";
		}

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


	/**
	 * 采集模块
	 * @return [type] [description]
	 */
	function collect() {
		$cate = new \Xpmsns\pages\Model\Category;
	
		$data = [
			'url' => $_GET['url'],
			'published'=> $_GET['published'],
			'category' => $cate
		];

		App::render($data,'article','collect.widget');
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
	                "图文" => APP::R('article','index'),
	                "文章列表" => APP::R('article','index'),
	                "转采文章" => '',
	        ],

	        'active'=> [
	 			'slug'=>'xpmsns/pages/article/index'
	 		]
		];
	}


	/**
	 * 采集
	 * @return [type] [description]
	 */
	function docollect() {
		$url = $_POST['url'];
		if ( empty($url) ) {
			throw new Excp("请提供目标网页地址", 404, ['post'=>$_POST]);
		}

		$article = new \Xpmsns\pages\Model\Article;
		$article->collect(['url'=>$url, 'category'=>$_POST['category'], 'status'=>$_POST['status']]);

		echo json_encode(['code'=>0, 'message'=>'done']);
	}


	function test(){

		Utils::cliOnly();

		$media = new \Xpmse\Media;
		$path = "/data/stor/public/media/2017/09/24/7d17a1dcd7c0e2b90ae2f7655fd6ff82.ttf";

		$resp = $media->guessTitle( $path, 'application/x-font-ttf');

		Utils::out( $resp );


		// 需要安 ffmpeg
		// apt-get install ffmpeg
		// $ffmpeg = FFMpeg\FFMpeg::create();


		// return;

		// Utils::cliOnly();
		// set_time_limit(0);
		// $art = new  \Xpmsns\pages\Model\Article;
		// $art->downloadFromWechat('wx77e0de6921bacc92', 15);

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

		$article = new \Xpmsns\pages\Model\Article;
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

		$article_id  = $_GET['id'];
		if ( empty($article_id) ) {
			echo "<span class='text-danger'>文章尚未保存</span>";
			return;
		}

		$article = new \Xpmsns\pages\Model\Article;
		$data['pages'] = $article->previewLinks( $article_id);
		App::render($data,'article','preview.popover');
	}


	/**
	 * 访问链接
	 * @return [type] [description]
	 */
	function links() {
		$article_id  = $_GET['id'];
		if ( empty($article_id) ) {
			echo "<span class='text-danger'>未知文章信息</span>";
			return;
		}

		$article = new \Xpmsns\pages\Model\Article;
		if ( $article->isPublished($article_id) === false ) {
			echo "<span class='text-danger'>文章尚未发布</span>";
			return;
		}

		$data['pages'] = $article->links( $article_id );

		if ( empty($data['pages']) ) {
			echo "<span class='text-danger'>未找到匹配页面</span>";
			return;
		}

		$data['wxapp'] = $article->wxapp();
		App::render($data,'article','links.popover');
	}


	/**
	 * 物料清单
	 * @return [type] [description]
	 */
	function materials(){

		$article_id  = $_GET['id'];
		if ( empty($article_id) ) {
			echo "<span class='text-danger'>未知文章信息</span>";
			return;
		}

		$article = new \Xpmsns\pages\Model\Article;
		if ( $article->isPublished($article_id) === false ) {
			echo "<span class='text-danger'>文章尚未发布</span>";
			return;
		}

		$data['images'] = [];
		// $data['images'] = $article->galleryImages($article_id);
		$data['pages'] = $article->links( $article_id );
		$data['wxapp'] = $article->wxapp();

		App::render($data,'article','materials.popover');
	}


	/**
	 * 保存文章
	 * @return 
	 */
	function save()  {
		// sleep(1);
		$article = new \Xpmsns\pages\Model\Article;
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
		$art = new  \Xpmsns\pages\Model\Article;
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
		$art = new \Xpmsns\pages\Model\Article;
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
		if ( $t->isRunning('从微信下载文章', 'xpmsns/pages') ) {
			throw new Excp('下载中，任务尚未完成', 400, ['ids'=>$_POST['ids'], "offset"=>$offset] );	
		}

		$task_id = $t->run('从微信下载文章', [
			"app_name" => "xpmsns/pages",
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
		$art = new \Xpmsns\pages\Model\Article;
		$t = new Task;
		if ( $t->isRunning('从微信下载文章', 'xpmsns/pages') ) {
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
		$article_id = $_POST['id'];
		$mpids = explode(',', $_POST['mpids']);
		$create = isset($_POST['create']) ? $_POST['create'] : null;

		if ( empty($article_id) ) {
			throw new Excp('未知文章信息 (ID=null)', 404, ['article_id'=>$article_id, 'mpids'=>$mpids, 'create'=>$create]);
		}

		if ( count($mpids) == 0 || $_POST['mpids'] == "" ) {
			throw new Excp('请选择至少一个公众号', 404, ['article_id'=>$article_id, 'mpids'=>$mpids, 'create'=>$create]);
		}

		$art = new  \Xpmsns\pages\Model\Article;
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

		$article = new \Xpmsns\pages\Model\Article;
		$rs = $article->unpublished( $data['article_id'] );
		Utils::out( $rs );

	}

	// 文章编辑器
	function editor() {

		$opt = new \Xpmse\Option('xpmsns/pages');
		$options = $opt->getAll();

		$art = new \Xpmsns\pages\Model\Article;
		$article = ['category'=>[], 'tag'=>[]];
		if ( !empty( $_GET['id']) ) {
			$article = $art->load( $_GET['id'] );
		}


		$cate = new \Xpmsns\pages\Model\Category;
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
	 			'slug'=>'xpmsns/pages/article/index'
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