<?php
use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Tuan as Tuan;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Conf as Conf;


class SetupController extends \Tuanduimao\Loader\Controller {
	
	function __construct() {

		$this->models = [
			'\\Mina\\Pages\\Model\\Article', 
			'\\Mina\\Pages\\Model\\Category', 
			'\\Mina\\Pages\\Model\\Tag',
			'\\Mina\\Pages\\Model\\Gallery'
		];
	}


	private  function gallery_init() {

		$g = new \Mina\Pages\Model\Gallery();
		$id = $g->genGalleryId();

		$gallery_id = $g->getVar('gallery_id', "WHERE title=? AND system=1 LIMIT 1", ["文章分享图片"]);

		if ( !empty($gallery_id) ) {
			try {$resp = $g->getGallery($gallery_id); return; } catch(Excp $e) { 
				$g->rm($gallery_id); 
			}
		}

		$id = $g->genGalleryId();

		$article_share = [
			"page" => [
				"id" => $id,
				"title"=>"文章分享图片", 
				"bgimage"=>"/s/mina/pages/static/defaults/804X1280.png", 
				"bgcolor"=>"rgba(254,254,254,1)",
				"origin" => -1
			],
			"items" => [
				[
					"name"=>"qrcode",
					"option"=>[
						"text" => "https://www.minapages.com", "origin"=>3,
						 "width"=>100, "height"=>100, 
						"type"=>'url'], 
					"pos"=> ["x"=>676, "y"=>1149] ],

				[
					"name"=>"image", 
					"option" => [
						"width"=>126, "height"=>30, 
						"src"=>"/s/mina/pages/static/defaults/mp-logo-text.png" ], 
					"pos"=> ["x"=>21, "y"=>1222] ],

				[
					"name"=>"image", 
					"option"=> [
						"width"=>804, "height"=>423, "origin"=>2
						], 
					"pos"=> ["x"=>0, "y"=>0] ],

				[
					"name"=>"text", 
					"option"=>[
						"width"=>644, "height"=>52, "font"=>1, "size"=>48,"origin"=>0,
						"color"=> "rgba(35,35,35,1)", "background"=>"rgba(255,255,255,0)", 
						"type"=>"horizontal" ], 
					"pos"=> ["x"=>80, "y"=>502]],
				[
					"name"=>"text", 
					"option"=>[
						"width"=>644, "height"=>448, "font"=>1, "size"=>36,"origin"=>1,
						"color"=> "rgba(153,153,153,1)", "background"=>"rgba(255,255,255,0)", 
						"type"=>"horizontal" ], 
					"pos"=> ["x"=>80, "y"=>576]]
			]
		];

		$gallery =  $g->editorToGallery( $article_share );
		$gallery['system'] = 1;
		$gallery['param'] = 'article';

		$images = $g->genImageData([
			["A"=>"文章标题", "B"=>"内容题要", "C"=>"/s/mina/pages/static/defaults/950X500.png", "D"=>"https://minapages.com"]
		]);

		$rs = $g->save( $gallery );
		$resp = $g->createImages( $rs['gallery_id'], $images);
		$image_id = current($resp)['data']['image_id'];
		$g->makeImage($image_id);

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


		// 注册配置
		try {

			$option = new \Tuanduimao\Option('mina/pages');
			$option->register("图文主题图片比例配置", "article/image/ratio", [
				"cover"=>["width"=>900,"height"=>500, "ratio"=>1.8], 
				"topic1"=>["width"=>null,"height"=>null, "ratio"=>"1:1"],
				"topic2"=>["width"=>null,"height"=>null, "ratio"=>"16:9"],
				"topic3"=>["width"=>null,"height"=>null, "ratio"=>"4:3"],
				"topic4"=>["width"=>null,"height"=>null, "ratio"=>"2:3"]
			]);
		} catch ( Excp $e ) {
			echo $e->toJSON();
			return;
		}


		// 注册图片分享图集
		try {
			$this->gallery_init();
		}  catch ( Excp $e ) {
			echo $e->toJSON();
			return;
		}

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
			try { $inst->__schema(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		try {
			$option = new \Tuanduimao\Option('mina/pages');
			$option->register("图文主题图片比例配置", "article/image/ratio", [
				"cover"=>["width"=>900,"height"=>500, "ratio"=>1.8], 
				"topic1"=>["width"=>null,"height"=>null, "ratio"=>"1:1"],
				"topic2"=>["width"=>null,"height"=>null, "ratio"=>"16:9"],
				"topic3"=>["width"=>null,"height"=>null, "ratio"=>"4:3"],
				"topic4"=>["width"=>null,"height"=>null, "ratio"=>"2:3"]
			]);
			
		} catch ( Excp $e ) {}

		// 注册图片分享图集
		try {
			$this->gallery_init();
		}  catch ( Excp $e ) {}
		
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
			$option = new \Tuanduimao\Option('mina/pages');
			$option->unregister();
		} catch ( Excp $e ) {}

		echo json_encode('ok');		
	}
}