<?php
require_once(__DIR__ . '/../env.php');

use \Tuanduimao\Api;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;
// use \Mina\Pages\Api\Article;

echo "\n\Mina\Pages\Api\Article 测试... \n\n\t";

class testArticleapi extends PHPUnit_Framework_TestCase {


	function testSearch() {
		$api = new \Mina\Pages\Api\Article;
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
		$api = new \Mina\Pages\Api\Article;
		try {
			$resp = $api->call('get',['articleId'=>31, "select"=>"title,tag,article_id,category"]);
		}catch( Excp $e) {
			Utils::out( $e->toArray());
			return;
		}

		Utils::out( $resp);
	}
	
}