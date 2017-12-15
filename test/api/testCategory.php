<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
// use \Xpmsns\pages\Api\Article;

echo "\n\Xpmsns\pages\Api\Category 测试... \n\n\t";

class tesCategoryApi extends PHPUnit_Framework_TestCase {


	function testSearch() {
		$api = new \Xpmsns\pages\Api\Category;
		try {
			$resp = $api->call('search', [
				"select" => 'name',
				"page" => 1,
				"prepage" => 100,
				'status' =>  'on'
			]);
		} catch ( Excp $e ){
			Utils::out( $e->toArray() );
			return;
		}

		Utils::out( $resp );
	}

	function testGet() {
		
		$api = new \Xpmsns\pages\Api\Category;
		try {
			$resp = $api->call('get',['category_id'=>3, "select"=>"category_id,name,fullname,parent_id,priority"]);
		}catch( Excp $e) {
			Utils::out( $e->toArray());
			return;
		}

		Utils::out ($resp );
	}
	
}