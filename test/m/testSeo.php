<?php
require_once(__DIR__ . '/../env.php');
use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;

echo "\n\Xpmsns\pages\Model\Seo 测试... \n\n\t";

class testSeoModel extends PHPUnit_Framework_TestCase {

	// function testSubmitBaiduLinks() {
	// 	$m = new \Xpmsns\pages\Model\Seo;
	// 	$resp = $m->submitBaiduLinks();
	// 	Utils::out( $resp );
	// }

	function testGetRobots() {
		$m = new \Xpmsns\pages\Model\Seo;
		$resp = $m->getRobots();
		Utils::out( $resp );
	}


	function testGetSiteMapIndex(){
		$m = new \Xpmsns\pages\Model\Seo;
		$pages = $m->getSiteMapIndex(1);
		Utils::out( $pages );
	}


	function testGetSiteMap(){
		$m = new \Xpmsns\pages\Model\Seo;
		$pages = $m->getSiteMapIndex(1);
		foreach ($pages as $pg) {

			$urls = $m->getSiteMap($pg, 1);
			Utils::out( $urls );
		}
	}
	
}