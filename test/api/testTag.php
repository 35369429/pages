<?php
require_once(__DIR__ . '/env.php');

use \Tuanduimao\Api;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;
// use \Mina\Pages\Api\Article;

echo "\n\Mina\Pages\Api\Tag 测试... \n\n\t";

class tesTagApi extends PHPUnit_Framework_TestCase {


	function testSearch() {
		$api = new \Mina\Pages\Api\Tag;
		try {
			$resp = $api->call('search', [
				"inName" => "北京,学术,快讯,上海",
				"select" => 'tag_id,name',
				"page" => 1,
				"prepage" => 100			
			]);
		} catch ( Excp $e ){
			Utils::out( $e->toArray() );
			return;
		}

		Utils::out( $resp );
	}

	function testGet() {

		$api = new \Mina\Pages\Api\Tag;
		try {
			$resp = $api->call('get',['name'=>'北京', "select"=>"tag_id,name,name"]);
		}catch( Excp $e) {
			Utils::out( $e->toArray());
			return;
		}

		Utils::out ($resp );
	}
	
}