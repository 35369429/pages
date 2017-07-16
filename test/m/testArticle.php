<?php
require_once(__DIR__ . '/../env.php');

use \Tuanduimao\Api;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;

echo "\n\Mina\Pages\Model\Article 测试... \n\n\t";

class testArticleModel extends PHPUnit_Framework_TestCase {


	function testLinks() {
		$m = new \Mina\Pages\Model\Article;
		$links = $m->links(1);
		Utils::out( $links );

	}

	
}