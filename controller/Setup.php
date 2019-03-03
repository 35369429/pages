<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;


class SetupController extends \Xpmse\Loader\Controller {
	
	function __construct() {

		$this->models = [
			'\\Xpmsns\\Pages\\Model\\Article', 
			'\\Xpmsns\\Pages\\Model\\Category', 
			'\\Xpmsns\\Pages\\Model\\Tag',
			'\\Xpmsns\\Pages\\Model\\Gallery',
			'\\Xpmsns\\Pages\\Model\\Adv',
			'\\Xpmsns\\Pages\\Model\\Links',
			'\\Xpmsns\\Pages\\Model\\Siteconf',
			'\\Xpmsns\\Pages\\Model\\Recommend',
            '\\Xpmsns\\Pages\\Model\\Event',
            '\\Xpmsns\\Pages\\Model\\UserEvent',
			'\\Xpmsns\\Pages\\Model\\Album',
			'\\Xpmsns\\Pages\\Model\\Series',
            '\\Xpmsns\\Pages\\Model\\Special',
            '\\Xpmsns\\Pages\\Model\\Goods',
            '\\Xpmsns\\Pages\\Model\\Item',
            '\\Xpmsns\\Pages\\Model\\Order',
            '\\Xpmsns\\Pages\\Model\\Shipping',
            '\\Xpmsns\\Pages\\Model\\Topic',
		];
	}


	/**
	 * 初始化默认数据
	 * @return [type] [description]
	 */
	private function defaults_init() {

		// 注册配置
		$option = new \Xpmse\Option('xpmsns/pages');
		$ratio = $option->get("article/image/ratio"); // 图文主题图片比例配置
		if ( $ratio == null ) {
			$option->register("图文主题图片比例配置", "article/image/ratio", [
				"cover"=>["width"=>900,"height"=>500, "ratio"=>"9:5"], 
				"topic1"=>["width"=>null,"height"=>null, "ratio"=>"1:1"],
				"topic2"=>["width"=>null,"height"=>null, "ratio"=>"16:9"],
				"topic3"=>["width"=>null,"height"=>null, "ratio"=>"4:3"],
				"topic4"=>["width"=>null,"height"=>null, "ratio"=>"2:3"]
			]);
		}

		$nplapi = $option->get("article/npl/api"); // 自然语言处理引擎
		if ( $nplapi == null ) {
			$option->register("自然语言处理引擎", "article/npl/api", [
				"engine"=>"baidu", 
				"config"=>["appid"=>null,"apikey"=>null, "secretkey"=>null]
			]);
        }
        
        // 用户发表文章策略
        $audit_policies = $option->get("article/ugc/policies");
		if ( $audit_policies == null ) {
			$option->register("用户发表文章策略", "article/ugc/policies", [
                "create"=> "audit-all",
                "update"=> "not-allowed",
                "value-allowed" => [
                    "not-allowed" => "不允许发表",
                    "all" => "允许用户和专栏作者发表文章",
                    "contribute-only" => "允许用户投稿",
                    "special-only" => "允许专栏作者发表文章",
                    "audit-all" => "允许用户和专栏作者发表文章,提交内容更需要审核",
                    "audit-contribute-only" => "允许用户投稿,提交内容更需要审核",
                    "audit-special-only" => "允许专栏作者发表文章,提交内容更需要审核",
                ]
            ]);
        }
        

		// 添加默认分类
		$categories = [
			["slug"=>"default", "name"=>"资讯", "fullname"=>"资讯", "isnav"=>1 ],
			["slug"=>"video", "name"=>"视频", "fullname"=>"视频", "isnav"=>1, "link"=>"DB::RAW(CONCAT('/video/list/',`category_id`, '.html'))"],
            ["slug"=>"album", "name"=>"图片", "fullname"=>"图片", "isnav"=>1, "link"=>"DB::RAW(CONCAT('/album/list/',`category_id`, '.html'))"],
            ["slug"=>"event", "name"=>"活动", "fullname"=>"活动", "isnav"=>1, "link"=>"/event/index.html"]
		];

		$cate = new \Xpmsns\Pages\Model\Category;
		foreach ($categories as $c ) {
			$rs = $cate->getBy('slug', $c['slug']);
			if ( !empty($rs) ) {
				continue;
			}
			try {
				$cate->saveBySlug($c);
			} catch( Excp $e ){}
		}

		// 添加默认配置项
		$site = new \Xpmsns\Pages\Model\Siteconf;
		$site->saveBySiteSlug(["site_slug"=>'global',  'position'=>"全局默认配置"]);
		$site->saveBySiteSlug(["site_slug"=>'pc', 'position'=>"桌面WEB界面"]);
		$site->saveBySiteSlug(["site_slug"=>'h5', 'position'=>"手机H5界面"]);
		$site->saveBySiteSlug(["site_slug"=>'wxapp', 'position'=>"微信小程序"]);
		$site->saveBySiteSlug(["site_slug"=>'android', 'position'=>"安卓客户端"]);
		$site->saveBySiteSlug(["site_slug"=>'ios', 'position'=>"iOS客户端"]);

		// 添加默认推荐项
		$recommends = [
			["title"=>"本周热文","slug"=>"weekly_hotnews", "orderby"=>"view_cnt", "period"=>'weekly', "type"=>"auto"],
			["title"=>"本周热评","slug"=>"weekly_hotreviews", "orderby"=>"comment_cnt", "period"=>'weekly', "type"=>"auto"],
			["title"=>"本周更新","slug"=>"weekly_news", "orderby"=>"publish_time", "period"=>'weekly', "type"=>"auto"],
			["title"=>"7日更新","slug"=>"7days_news", "orderby"=>"publish_time", "period"=>'7days', "type"=>"auto"],
			["title"=>"7日热文","slug"=>"7days_hotnews", "orderby"=>"view_cnt", "period"=>'7days', "type"=>"auto"],
			["title"=>"7日热评","slug"=>"7days_hotreviews", "orderby"=>"comment_cnt", "period"=>'7days', "type"=>"auto"],
			["title"=>"今日热文","slug"=>"daily_hotnews", "orderby"=>"view_cnt", "period"=>'daily', "type"=>"auto"],
			["title"=>"今日热评","slug"=>"daily_hotreviews", "orderby"=>"comment_cnt", "period"=>'daily', "type"=>"auto"],
			["title"=>"24小时热文","slug"=>"24hours_hotnews", "orderby"=>"view_cnt", "period"=>'24hours', "type"=>"auto"],
			["title"=>"24小时热评","slug"=>"24hours_hotreviews", "orderby"=>"comment_cnt", "period"=>'24hours', "type"=>"auto"],
			["title"=>"最新文章","slug"=>"latest", "pos"=>"index_sidebar", "orderby"=>"publish_time", "period"=>"unlimited", "type"=>"auto"],
			["title"=>"最热文章","slug"=>"hotnews", "orderby"=>"publish_time", "period"=>"unlimited",   "type"=>"auto"],
			["title"=>"焦点文章","slug"=>"focus", "pos"=>"index_focus",  "thumb_only"=>1, "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"今日主题","slug"=>"topic", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"新闻快讯","slug"=>"quicknews", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],

			// 正文相关推荐
			["title"=>"正文页相关推荐","slug"=>"detail", "pos"=>"detail_sidebar", "orderby"=>"publish_time",  "period"=>"unlimited",  "type"=>"auto"],

			// 首页相关推荐
			["title"=>"首页S1","slug"=>"section_1","pos"=>"index", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"首页S2","slug"=>"section_2","pos"=>"index", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"首页S3","slug"=>"section_3","pos"=>"index", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"首页S4","slug"=>"section_4","pos"=>"index", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"首页S5","slug"=>"section_5","pos"=>"index", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"首页S6","slug"=>"section_6","pos"=>"index", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],

			// 视频相关推荐
			["title"=>"焦点视频","slug"=>"video_focus", "pos"=>"video_focus", "thumb_only"=>1, "video_only"=>1, "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"视频S1","slug"=>"video_s1","pos"=>"video", "thumb_only"=>1, "video_only"=>1,  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"视频S2","slug"=>"video_s2","pos"=>"video", "thumb_only"=>1, "video_only"=>1,  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"视频详情系列(优选)","slug"=>"video_series","pos"=>"video_detail", "thumb_only"=>1, "video_only"=>1,  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"视频详情最新(次选)","slug"=>"video_latest","pos"=>"video_detail", "thumb_only"=>1, "video_only"=>1,  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"猜你喜欢","slug"=>"video_relation","pos"=>"video_detail", "thumb_only"=>1, "video_only"=>1,  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],

			// 图集相关推荐
			["title"=>"焦点图集","slug"=>"album_focus", "pos"=>"album_focus", "ctype"=>"album","orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"图集S1","slug"=>"album_s1","pos"=>"album", "thumb_only"=>1, "ctype"=>"album",  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"图集S2","slug"=>"album_s2","pos"=>"album", "thumb_only"=>1, "ctype"=>"album",  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"图集详情系列(优选)","slug"=>"album_series","pos"=>"album_detail", "thumb_only"=>1, "ctype"=>"album",  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"图集详情最新(次选)","slug"=>"album_latest","pos"=>"album_detail", "thumb_only"=>1, "ctype"=>"album",  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"猜你喜欢","slug"=>"album_relation","pos"=>"album_detail", "thumb_only"=>1, "ctype"=>"album",  "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],

			// 侧边相关推荐
			["title"=>"侧边S1","slug"=>"sidebar_section_1", "pos"=>"index_sidebar", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"侧边S2","slug"=>"sidebar_section_2", "pos"=>"index_sidebar", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"侧边S3","slug"=>"sidebar_section_3", "pos"=>"index_sidebar", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"侧边S4","slug"=>"sidebar_section_4", "pos"=>"index_sidebar", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"],

			// 底部帮助推荐
			["title"=>"帮助中心","slug"=>"_help", "orderby"=>"publish_time",  "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"关于我们","slug"=>"_about", "orderby"=>"publish_time",  "period"=>"unlimited",  "type"=>"auto"],
			["title"=>"常见问题","slug"=>"_faq", "orderby"=>"publish_time",  "period"=>"unlimited",  "type"=>"auto"]
		];
		$rec = new \Xpmsns\Pages\Model\Recommend;
		foreach ($recommends as $r ) {
			try {
				$rec->create($r);
			} catch( Excp $e ){
				// 1062
				// echo $e->getCode();
				// Duplicate entry ....
				// echo $e->getMessage();
				// unset( $r['title'] );
				// $rec->saveBySlug($r);
			}
		}
	}


	function install() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__clear(); } catch( Excp $e) {echo $e->toJSON(); return;}
            try { $inst->__schema(); } catch( Excp $e) {echo $e->toJSON(); return;}
            try { $inst->__defaults(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		// 初始化默认配置
		try { $this->defaults_init(); }  catch ( Excp $e ) { echo $e->toJSON(); return;}
	
		echo json_encode('ok');
	}


	function upgrade(){
		echo json_encode('ok');	
	}

	function repair() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
            try { $inst->__schema(); } catch( Excp $e) { echo $e->toJSON(); return;}
            try { $inst->__defaults(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		// 初始化默认配置
		try { $this->defaults_init(); }  catch ( Excp $e ) { echo $e->toJSON(); return;}
		echo json_encode('ok');		
	}


	// 卸载
	function uninstall() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__clear(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		try {
			$option = new \Xpmse\Option('xpmsns/pages');
			$option->unregister();
		} catch ( Excp $e ) {}

		echo json_encode('ok');		
	}
}
