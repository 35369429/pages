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

		$hello = App::M('Hello');
	
		try {
			$hello->dropTable();
		}catch( Excp $e) {}

		try  {
			$hello->__schema();
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

		$hello = App::M('Hello');
		try  {
			$hello->__schema();
		}catch ( Excp $e ) {
			echo $e->toJSON();
			return;
		}

		echo json_encode('ok');		
	}

	// 卸载
	function uninstall() {

		$hello = App::M('Hello');
		try {
			$hello->__clear();
		}catch( Excp $e) {}

		echo json_encode('ok');		
	}
}