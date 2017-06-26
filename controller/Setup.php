<?php
use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Tuan as Tuan;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Conf as Conf;


class SetupController extends \Tuanduimao\Loader\Controller {
	
	function __construct() {
	}


	function install() {
	
		try {
			App::M('Article')->dropTable();
			App::M('Category')->dropTable();
			App::M('Tag')->dropTable();
		}catch( Excp $e) {}

		try  {
			App::M('Article')->__schema();
			App::M('Category')->__schema();
			App::M('Tag')->__schema();
		}catch ( Excp $e ) {
			echo $e->toJSON();
			return;
		}

		echo json_encode('ok');
	}


	function upgrade(){
		echo json_encode('ok');	
	}

	function repair() {

		try  {
			App::M('Article')->__schema();
			App::M('Category')->__schema();
			App::M('Tag')->__schema();
		}catch ( Excp $e ) {
			echo $e->toJSON();
			return;
		}

		echo json_encode('ok');		
	}

	// 卸载
	function uninstall() {

		try {
			App::M('Article')->__clear();
			App::M('Category')->__clear();
			App::M('Tag')->__clear();
		}catch( Excp $e) {}

		echo json_encode('ok');		
	}
}