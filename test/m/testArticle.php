<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;

echo "\n\Xpmsns\pages\Model\Article 测试... \n\n\t";

class testArticleModel extends PHPUnit_Framework_TestCase {


	function testLinks() {
		$m = new \Xpmsns\pages\Model\Article;
		$links = $m->links(1);
		Utils::out( $links );

	}

	
}