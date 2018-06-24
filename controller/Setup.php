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
			'\\Xpmsns\\Pages\\Model\\Recommend'
		];
	}


	/**
	 * 初始化默认数据
	 * @return [type] [description]
	 */
	private function defaults_init() {

		// 注册配置
		$option = new \Xpmse\Option('xpmsns/pages');
		$ratio = $option->get("article/image/ratio");
		if ( $ratio == null ) {
			$option->register("图文主题图片比例配置", "article/image/ratio", [
				"cover"=>["width"=>900,"height"=>500, "ratio"=>"9:5"], 
				"topic1"=>["width"=>null,"height"=>null, "ratio"=>"1:1"],
				"topic2"=>["width"=>null,"height"=>null, "ratio"=>"16:9"],
				"topic3"=>["width"=>null,"height"=>null, "ratio"=>"4:3"],
				"topic4"=>["width"=>null,"height"=>null, "ratio"=>"2:3"]
			]);
		}


		// 添加默认分类
		$cate = new \Xpmsns\Pages\Model\Category;
		$cate->saveBySlug(["name"=>"默认","fullname"=>"默认", "slug"=>"default"]);

		// 添加默认配置项
		$site = new \Xpmsns\Pages\Model\Siteconf;
		$site->saveBySiteSlug(["site_slug"=>'global',  'position'=>"全局默认配置"]);
		$site->saveBySiteSlug(["site_slug"=>'pc', 'position'=>"桌面WEB界面"]);
		$site->saveBySiteSlug(["site_slug"=>'h5', 'position'=>"手机H5界面"]);
		$site->saveBySiteSlug(["site_slug"=>'wxapp', 'position'=>"微信小程序"]);
		$site->saveBySiteSlug(["site_slug"=>'android', 'position'=>"安卓客户端"]);
		$site->saveBySiteSlug(["site_slug"=>'ios', 'position'=>"iOS客户端"]);


		// 添加默认推荐项
		$rec = new \Xpmsns\Pages\Model\Recommend;
		$rec->saveBySlug(["title"=>"本周热文","slug"=>"weekly_hotnews", "orderby"=>"view_cnt", "period"=>'weekly', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"本周热评","slug"=>"weekly_hotreviews", "orderby"=>"comment_cnt", "period"=>'weekly', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"本周更新","slug"=>"weekly_news", "orderby"=>"publish_time", "period"=>'weekly', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"7日更新","slug"=>"7days_news", "orderby"=>"publish_time", "period"=>'7days', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"7日热文","slug"=>"7days_hotnews", "orderby"=>"view_cnt", "period"=>'7days', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"7日热评","slug"=>"7days_hotreviews", "orderby"=>"comment_cnt", "period"=>'7days', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"今日热文","slug"=>"daily_hotnews", "orderby"=>"view_cnt", "period"=>'daily', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"今日热评","slug"=>"daily_hotreviews", "orderby"=>"comment_cnt", "period"=>'daily', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"24小时热文","slug"=>"24hours_hotnews", "orderby"=>"view_cnt", "period"=>'24hours', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"24小时热评","slug"=>"24hours_hotreviews", "orderby"=>"comment_cnt", "period"=>'24hours', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"最新文章","slug"=>"latest", "orderby"=>"publish_time", "period"=>"unlimited", "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"最热文章","slug"=>"hotnews", "orderby"=>"publish_time", "period"=>"unlimited",   "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"焦点文章","slug"=>"focus", "orderby"=>"publish_time", "period"=>"unlimited",  "type"=>"auto"]);

		// 正文相关推荐
		$rec->saveBySlug(["title"=>"正文页相关推荐","slug"=>"detail", "orderby"=>"publish_time",  "period"=>"unlimited",  "type"=>"auto"]);

		// 首页相关推荐
		$rec->saveBySlug(["title"=>"首页第一块内容区","slug"=>"section_1", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"首页第二块内容区","slug"=>"section_2", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"首页第三块内容区","slug"=>"section_3", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"首页第四块内容区","slug"=>"section_4", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"首页第五块内容区","slug"=>"section_5", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"首页第六块内容区","slug"=>"section_6", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);

		// 侧边相关推荐
		$rec->saveBySlug(["title"=>"侧边第一块内容区","slug"=>"sidebar_section_1", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"侧边第二块内容区","slug"=>"sidebar_section_2", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"侧边第三块内容区","slug"=>"sidebar_section_3", "orderby"=>"publish_time", "period"=>"7days",  "type"=>"auto"]);
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
