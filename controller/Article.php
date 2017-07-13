<?php
use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Tuan as Tuan;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Conf as Conf;
use \Mina\Storage\Local as Storage;


class ArticleController extends \Tuanduimao\Loader\Controller {
	
	function __construct() {
	}


	// 图文列表页
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
			'crumb' => [
	                 "图文" => APP::R('article','index'),
	                 "文章列表" =>'',
	        ]
		];
	}


	/**
	 * 保存文章
	 * @return 
	 */
	function save()  {
		sleep(1);
		// throw new Excp("Error Test", 500, ['hello'=>'world']);

		$article = new \Mina\Pages\Model\Article;
		$rs = $article->save( json_decode(App::input(),true) );
		Utils::out( $rs );

	}


	// 文章编辑器
	function editor() {

		$data = [
			'category' => new \Mina\Pages\Model\Category
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

		    		"js/plugins/jquery-webeditor/webeditor.full.min.js",
		    		"js/plugins/jquery-webeditor/panel.full.min.js"
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",

	 			"js/plugins/jquery-webeditor/webeditor.full.min.css?important",
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




	private function _cover() {

		$image_url = 'https://wss.xpmjs.com/static/photos/loginbg@2x.jpg';

		$stor = new Storage([
			"prefix"=>"/data/stor/public/deepblue",
			"url"=>"/static-file/deepblue",
			"origin"=>"/static-file/deepblue"
		]);

		$info = $stor->upload( "/test/test.jpg", file_get_contents($image_url));
		return $info['url'];
	}


	private  function _paincontent() {
		return '
		<p class="zhengwen" style="width: 100%">
			<h2 style="text-align: center;color:#C00000;">中国疼痛康复快讯</h2>
			<p style="text-align: center;margin-top: 20px;color:#1F497D;">China Pain ＆ Rehabilitation Express</p>
			<p style="text-align: center;">中国疼痛康复产业技术创新战略联盟会讯</p>
			<img src="images/中国疼痛康复快讯images/图片8.png" style="margin-left: 75px;margin-top: 10px;">
			<p style="text-align: center;margin-top: 10px;color:#FFC000;">带状疱疹病毒与带状疱疹后神经痛</p>
			<ul style="width: 100%">
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<strong style="float: left;width: 15%">主办单位</strong>
					<strong style="float:right;width: 80%">中国疼痛康复产业技术创新战略联盟世界疼痛医师协会中国分会北京高新疼痛诊疗产业技术创新战略联盟</strong>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<strong style="float: left;width: 15%">承办单位</strong>
					<strong style="float:right;width: 80%">首都医科大学宣武医院疼痛科</strong>
				</li>
			</ul>
			<h2 style="text-align: center;">《中国疼痛康复快讯》编委会</h2>
			<ul style="width:100%;font-size: 12px;">
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">中文名称：</span>
					<span style="float:right;width: 80%;color:#0070C0">中国疼痛康复快讯</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">原 刊 名：</span>
					<span style="float:right;width: 80%;color:#0070C0">世界疼痛快讯</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">英文名称：</span>
					<span style="float:right;width: 80%;color:#0070C0">China Pain & Rehabilitation Express</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">主 办：</span>
					<span style="float:right;width: 80%;color:#0070C0">中国疼痛康复产业技术创新战略联盟世界</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">承 办：</span>
					<span style="float:right;width: 80%;color:#0070C0">疼痛医师协会中国分会北京高新疼痛诊疗</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">主 编：</span>
					<span style="float:right;width: 80%;color:#0070C0">产业技术创新战略联盟首都医科大学宣武医院疼痛科倪家骧 刘长信</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">执行主编：</span>
					<span style="float:right;width: 80%;color:#0070C0">安建雄 李石良 李全成</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">副 主 编：</span>
					<span style="float:right;width: 80%;color:#0070C0">安罗 健 杨晓秋 鲍红光 王保国 庄志刚 刘 慧靳 平（美国）</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">编委会委员：</span>
					<span style="float:right;width: 80%;color:#0070C0">（按姓氏笔画为序）</span>
				</li>
				<li style="color:#0070C0;margin-top: 12px;">
					马明玉 戈晓东 牛学功 王成纲 王东信 王	雷 王全贵 王天龙 王秀丽 王颖林 艾登斌 卢纪明 田	鸣 史可梅 关 雷 刘广召 刘金虎 刘景岩 刘鲲鹏 刘永彬 刘甬民 刘柱兴 吕	岩 米卫东 曲丕盛 任玉娥 孙 莉 孙永海 朱 岩 陈家骅 陈晓光 陈亚军 杜新如 李昌熙 李成哲 李	娟 李小霞 李玄英 李兴志 李彦平	宋 涛 吴世健 吴信真 肖正权 肖德华 严	敏 杨立强 庞海涛 张国荣 张建成	张莉萍 张 卫 张文祥 武百山 岳剑宁 郭向阳 郭晓丽 郭瑞红 郭瑞宏 郝建 华姚 明 祝胜美 聂发传 钱小峰 索利斌 唐庆国 夏宏盛 夏令杰 徐晨婕 徐惠清 徐铭军 袁建虎 崔志强 黄慈波 黄明勇 康 健 寇立华 梁立双 程 灏 傅建锋 韩如泉 韩雪萍 蒋 进 蒋宗滨 赖光辉 路桂军 翟新利 薛荣亮 鄢建勤
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">编辑部主任：</span>
					<span style="float:right;width: 80%;color:#0070C0">公维义</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">编辑部副主任：</span>
					<span style="float:right;width: 80%;color:#0070C0">唐元章 王小平</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">本期执行主编：</span>
					<span style="float:right;width: 80%;color:#0070C0">公维义</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">本 期 编 辑：</span>
					<span style="float:right;width: 80%;color:#0070C0">王小平 曾塬杰 窦 智 何亮亮 孙东光 李 娜 郝 龙 李小琳 赵 丹 赵燕星 刘海泉</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">本刊联系地址：</span>
					<span style="float:right;width: 80%;color:#0070C0">北京市西城区长椿街 45 号</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">邮	编：</span>
					<span style="float:right;width: 80%;color:#0070C0">100053</span>
				</li>
				<li style="width: 100%;overflow: hidden;margin-top: 16px;">
					<span style="float:left;width: 15%; color:#0070C0">网 络 支 持：</span>
					<span style="float:right;width: 80%;color:#0070C0">http://www.ccwspc.org    <br>  http://www.paincenter.cn</span>
				</li> 
		</p>
		';
	}


	private  function _randcate( $max=9 ) {
		$len = rand(0,$max);
		$data = [];
		for( $i=0; $i<$len; $i++ ){
			array_push( $data, rand(0,$max));
		}

		return array_unique($data);
	}


	function testdata() {

		Utils::cliOnly();
		
		$c = App::M('Category');
		$c->runsql("truncate table `{{table}}`");
		$cates = [
			["name"=>"网上门诊", "project"=>"deepblue",'param'=>"isnav=true"],
			["name"=>"中心介绍", "project"=>"deepblue",'param'=>"isnav=true", "parent_id"=>1],
			["name"=>"继续教育", "project"=>"deepblue",'param'=>"isnav=true", "parent_id"=>2],
			["name"=>"医生沙龙", "project"=>"deepblue",'param'=>"isnav=true", "parent_id"=>1],
			["name"=>"疑难病痛", "project"=>"deepblue",'param'=>"isnav=true", "parent_id"=>2],
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


		$a = App::M("Article");
		$a->runsql("truncate table `{{table}}`");

	}



	function paindata() {

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


		$a = App::M("Article");
		$a->runsql("truncate table `{{table}}`");

		$articles = [];
		for( $i=0; $i<100; $i++ ) {
			array_push( $articles, 
				[
					"title"=>"《中国疼痛康复快讯》2016 第五卷 第 ".rand(0,500)." 期", "author"=>"疼痛网", 
					"summary"=>"中国疼痛康复".rand(0,500)."产业技术创新战略联盟世界疼痛医师协会中国分会北京高新疼痛诊",
					"origin"=>"疼痛网","origin_url"=>"https://www.tuanduimao.com/",
					"content"=> $this->_paincontent(),
					"cover" => $this->_cover(),
					"tag" => ["北京", "展会", "快讯"],
					"category"=> $this->_randcate(count($cates)),
					"publish_time" => date("Y-m-d H:i:s")
				]
			);
		}
		foreach( $articles as $article ) {
			echo "导入{$article['title']} ... ";
			$a->create($article);
			echo "DONE\n";
		}

	}


}