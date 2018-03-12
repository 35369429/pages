<?php
namespace Xpmsns\pages\Model; 
define('__NS__', 'Xpmsns\pages\Model'); // 兼容旧版 App::M 方法调用

use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Model as Model;
use \Xpmse\Utils as Utils;
use \Xpmse\Media as Media;

/**
 * 搜索引擎优化数据模型
 */
class Seo {

	private $option = null;

	function __construct( $param=[] ) {
		$this->option = new \Xpmse\Option('xpmsns/pages');
		$this->article = Utils::getTab('article', 'xpmsns_pages_');
		$this->baidulinks = $this->option->get('setting/seo/baidulinks');
		$this->articleurl =  empty($this->baidulinks['link']) ? '/article/{{id}}' : $this->baidulinks['link'];
		$this->home = Utils::getHome();
	}


	// 比对文章信息, 提交到百度
	function submitBaiduLinks() {

		if ( empty($this->baidulinks['token']) ) {
			throw new Excp("无百度链接提交配置选项", 402, $this->baidulinks);
		}

		
		$self = $this; $insert=0; $update=0; $remove=0; $errors = [];
 	
 		// 提交链接
 		$this->getBaiduLinksNeedInsert(function( $article_ids ) use( $self, &  $insert, & $errors ) {
			$resp = $self->createBaiduLinks( $article_ids );
			if ( $resp['code'] ==0 ) {
				$insert = $insert + intval($resp['success']);
			} else {
				array_push( $errors, $resp );
			}
		});

		// 更新链接
		$this->getBaiduLinksNeedUpdate(function( $article_ids ) use( $self, &  $update, & $errors ) {

			$resp = $self->updateBaiduLinks( $article_ids );
			if ( $resp['code'] ==0 ) {
				$insert = $insert + intval($resp['success']);
			} else {
				array_push( $errors, $resp );
			}
		});

		// 删除链接
		$this->getBaiduLinksNeedRemove(function( $article_ids ) use( $self, &  $remove, & $errors ) {
			$resp = $self->removeBaiduLinks( $article_ids );
			if ( $resp['code'] ==0 ) {
				$insert = $insert + intval($resp['success']);
			} else {
				array_push( $errors, $resp );
			}
		});

		return ['insert'=>$insert, 'update'=>$update, 'remove'=>$remove, 'errors'=>$errors];

	}


	/**
	 * 查找 & 遍历需要向百度提交的地址
	 * @param  [type]  $callback [description]
	 * @param  integer $page     [description]
	 * @param  integer $perpage  [description]
	 * @return [type]            [description]
	 */
	function getBaiduLinksNeedInsert( $callback = null,  $page=1, $perpage =200 ) {

		$qb = $this->article->query();
		$qb->whereNull('baidulink_time')
		   ->where('status', '=', 'published')
		;

		$resp = $qb->select("article_id")->pgArray($perpage, ['_id'], 'page', $page);
		$data = $resp['data'];

		if ( empty($data) ) {
			return;
		}

		if ( !is_callable($callback) ) {
			$callback = function( $article_ids  ) {};
		}

		// 回调地址函数
		$callback( array_column($data, 'article_id') );

		$page = intval($page) + 1;
		if ( $page != intval($resp['total']) ) {
			$this->getBaiduLinksNeedInsert( $callback, $page, $perpage );
		}
	}


	/**
	 * 查找 & 遍历需要向百度提交更新的地址
	 * @param  [type]  $callback [description]
	 * @param  integer $page     [description]
	 * @param  integer $perpage  [description]
	 * @return [type]            [description]
	 */
	function getBaiduLinksNeedUpdate( $callback = null,  $page=1, $perpage =200 ) {

		$qb = $this->article->query();
		$qb->whereNotNull('baidulink_time')
		   ->where('status', '=', 'published')
		   ->whereRaw('`baidulink_time` < `updated_at`')
		;

		$resp = $qb->select("article_id")->pgArray($perpage, ['_id'], 'page', $page);
		$data = $resp['data'];

		if ( empty($data) ) {
			return;
		}

		if ( !is_callable($callback) ) {
			$callback = function( $article_ids  ) {};
		}

		// 回调地址函数
		$callback( array_column($data, 'article_id') );

		$page = intval($page) + 1;
		if ( $page != intval($resp['total']) ) {
			$this->getBaiduLinksNeedUpdate( $callback, $page, $perpage );
		}

	}


	/**
	 * 查找 & 遍历需要想百度提交删除的地址
	 * @param  [type]  $callback [description]
	 * @param  integer $page     [description]
	 * @param  integer $perpage  [description]
	 * @return [type]            [description]
	 */
	function getBaiduLinksNeedRemove( $callback = null,  $page=1, $perpage =200 ) {

		$qb = $this->article->query();
		$qb->whereNotNull('baidulink_time')
		   ->whereRaw('`baidulink_time` < `updated_at`')
		   ->whereNotNull('deleted_at')

		;
		$resp = $qb->select("article_id")->pgArray($perpage, ['_id'], 'page', $page);
		$data = $resp['data'];
		if ( empty($data) ) {
			return;
		}

		if ( !is_callable($callback) ) {
			$callback = function( $article_ids  ) {};
		}

		// 回调地址函数
		$callback( array_column($data, 'article_id') );

		$page = intval($page) + 1;
		if ( $page != intval($resp['total']) ) {
			$this->getBaiduLinksNeedRemove( $callback, $page, $perpage );
		}
	}


	function createBaiduLinks( $article_ids ) {

		if ( empty($article_ids) ) {
			return;
		}
		
		// 测试
		// $article_ids = ['5a7a7215195e6', '5a7a91d2b4fb8'];
		// // $article_ids = ['5a7a7215195e6'];
		// $this->home = 'https://demo.xpmsns.com';

		$api = "http://data.zz.baidu.com/urls?site={$this->home}&token={$this->baidulinks['token']}";
		$urls = [];
		foreach ($article_ids as $id ) {
			array_push($urls, $this->home . str_replace('{{id}}', $id, $this->articleurl));
		}
		
		$resp = Utils::Request('POST', $api, ['data'=>implode("\n",$urls), 'type'=>"text", 'nocheck'=>true]);

		if ( is_array($resp['resp_body']) ) {
			$code = $resp['http_code'];
			$resp = $resp['resp_body'];
			$resp['code'] = $code;
		}

		if ( isset($resp['success']) ) {
		// if ( true ) {
			$resp['code'] = 0;
			$ids = implode("','", $article_ids);
			$limit = count($article_ids);
			$this->article->runsql("UPDATE {{table}} SET baidulink_time=CURRENT_TIMESTAMP(), updated_at=CURRENT_TIMESTAMP() WHERE article_id in ('{$ids}') LIMIT {$limit}");
		}
		
		return $resp;
	}

	function updateBaiduLinks( $article_ids ) {

		if ( empty($article_ids) ) {
			return;
		}

		// 测试
		// $article_ids = ['5a7a7215195e6', '5a7a91d2b4fb8'];
		// // $article_ids = ['5a7a7215195e6'];
		// $this->home = 'https://demo.xpmsns.com';
		
		$api = "http://data.zz.baidu.com/update?site={$this->home}&token={$this->baidulinks['token']}";
		$urls = [];
		foreach ($article_ids as $id ) {
			array_push($urls, $this->home . str_replace('{{id}}', $id, $this->articleurl));
		}		

		$resp = Utils::Request('POST', $api, ['data'=>implode("\n",$urls), 'type'=>"text", 'nocheck'=>true]);


		if ( is_array($resp['resp_body']) ) {
			$code = $resp['http_code'];
			$resp = $resp['resp_body'];
			$resp['code'] = $code;
		}

		if ( isset($resp['success']) ) {
		// if ( true ) {
			$resp['code'] = 0;
			$ids = implode("','", $article_ids);
			$limit = count($article_ids);
			$this->article->runsql("UPDATE {{table}} SET baidulink_time=CURRENT_TIMESTAMP(), updated_at=CURRENT_TIMESTAMP() WHERE article_id in ('{$ids}') LIMIT {$limit}");
		}
		return $resp;

	}


	function removeBaiduLinks( $article_ids ) {

		return true;

		if ( empty($article_ids) ) {
			return;
		}

		$urls = [];
		foreach ($article_ids as $id ) {
			array_push($urls, str_replace('{{id}}', $id, $this->articleurl));
		}
	}


	/**
	 * 读取SiteMap
	 * @return [type] [description]
	 */
	function getSiteMapIndex( $perpage =500 ) {
		$qb = $this->article->query();
		$qb->where('status', '=', 'published')
		   ->orderBy('created_at', 'asc');

		$resp = $qb->select('article_id')->pgArray($perpage, ['_id'], 'page', 1);
		$pages = [];
		for( $i=1; $i<=$resp['last']; $i++ ) {
			array_push($pages, $i);
		}

		return $pages;
	}


	/**
	 * 读取某一页SiteMap
	 * @param  integer $page    [description]
	 * @param  integer $perpage [description]
	 * @return [type]           [description]
	 */
	function getSiteMap( $page = 1, $perpage=500 ) {
		$qb = $this->article->query();
		$qb->where('status', '=', 'published')
		   ->orderBy('created_at', 'asc');
		$resp = $qb->select('article_id', 'updated_at', 'created_at', 'stick')->pgArray($perpage, ['_id'], 'page', $page);
		
		foreach ($resp['data'] as & $rs ) {
			$rs['loc'] = $this->home . str_replace('{{id}}', $rs['article_id'], $this->articleurl);
			$rs['lastmod'] = empty($rs['updated_at']) ? $rs['created_at'] : $rs['updated_at'];
			$rs['lastmod'] = date('Y-m-d', strtotime($rs['lastmod']));
			$rs['changefreq'] = $this->baidulinks['schedule'];
			$rs['priority'] = ( $rs['stick'] == 0 ) ? '0.8' : '1.0';
		}	

		return $resp['data'];
	}


	/**
	 * 读取 Robots 
	 */
	function getRobots() {
		return $this->option->get('setting/seo/robots');
	}


}