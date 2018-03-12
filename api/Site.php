<?php

namespace Xpmsns\pages\Api;
use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;
use \Xpmse\Wechat as Wechat;


/**
 * 文章API接口
 */
class Site extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {
		parent::__construct();
	}

	function robots( $query ) {
		$seo = new \Xpmsns\pages\Model\Seo;
		return ["text"=>$seo->getRobots()];
	}


	function sitemap( $query ) {

		$idx = $query['index'];
		$perpage = empty($query['perpage']) ? 200 : intval($query['perpage']);

		$seo = new \Xpmsns\pages\Model\Seo;
		if ( empty($idx)  ){
			$idxs = $seo->getSiteMapIndex($perpage);
			return ['indexes'=>$idxs, 'home'=>Utils::getHome()];
		}

		return $seo->getSiteMap( $idx, $perpage );
	}
}