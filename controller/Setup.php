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