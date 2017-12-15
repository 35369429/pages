<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
// use \Xpmsns\pages\Api\Article;

echo "\n\Xpmsns\pages\Api\Article 测试... \n\n\t";

class testArticleapi extends PHPUnit_Framework_TestCase {


	function testSearch() {
		$api = new \Xpmsns\pages\Api\Article;
		$resp = $api->call('search', [
			"select" => 'title,publish_time,article_id,category,tag',
			"page" => 1,
			"prepage" => 5,
			'category' => '网上门诊',
			'publish_time' => '2017-06-27 01:03:41',
			'orPublish_time' => '2017-06-27 01:03:40'
		]);

		Utils::out( $resp );

	}

	function testGet() {
		$api = new \Xpmsns\pages\Api\Article;
		try {
			$resp = $api->call('get',['articleId'=>31, "select"=>"title,tag,article_id,category"]);
		}catch( Excp $e) {
			Utils::out( $e->toArray());
			return;
		}

		Utils::out( $resp);
	}
	
}