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
			'\\Xpmsns\\Pages\\Model\\Recommend'  // 推荐
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
		$rec->saveBySlug(["title"=>"今日热文","slug"=>"daily_hotnews", "orderby"=>"view_cnt", "period"=>'weekly', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"今日热评","slug"=>"daily_hotreviews", "orderby"=>"comment_cnt", "period"=>'daily', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"24小时热文","slug"=>"24hours_hotnews", "orderby"=>"view_cnt", "period"=>'24hours', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"24小时热评","slug"=>"24hours_hotreviews", "orderby"=>"comment_cnt", "period"=>'24hours', "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"最新文章","slug"=>"latest", "orderby"=>"publish_time", "type"=>"auto"]);
		$rec->saveBySlug(["title"=>"正文页底部相关推荐","slug"=>"latest", "orderby"=>"publish_time", "type"=>"auto"]);

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
