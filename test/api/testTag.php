<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
// use \Xpmsns\pages\Api\Article;

echo "\n\Xpmsns\pages\Api\Tag 测试... \n\n\t";

class tesTagApi extends PHPUnit_Framework_TestCase {


	function testSearch() {
		$api = new \Xpmsns\pages\Api\Tag;
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

		$api = new \Xpmsns\pages\Api\Tag;
		try {
			$resp = $api->call('get',['name'=>'北京', "select"=>"tag_id,name,name"]);
		}catch( Excp $e) {
			Utils::out( $e->toArray());
			return;
		}

		Utils::out ($resp );
	}
	
}