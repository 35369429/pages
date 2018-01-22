<?php
namespace Xpmsns\pages\Model; 
define('__NS__', 'Xpmsns\pages\Model'); // 兼容旧版 App::M 方法调用

use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Model as Model;
use \Xpmse\Utils as Utils;
use \Spatie\Browsershot\Browsershot;
use PHPHtmlParser\Dom;



/**
 * 蜘蛛数据模型
 */
class Spider {

	function __construct( $param=[] ) {
	}


	/**
	 * 抓取图片
	 * @param  [type] $imgUrl [description]
	 * @return [type]         [description]
	 */
	function crawlImage( $imgUrl, $node ) {
		$url =  $imgUrl;
		$node->setAttribute('src', $url );
		$node->setAttribute('data-src', $url );
		return  $imgUrl;
	}


	/**
	 * 抓取
	 * @param  [type] $url [description]
	 * @return [type]      [description]
	 */
	function crawl( $url ) {


		// URL Parser
		$uri = parse_url($url);
		$uri['url'] = $url;
		$json_file = __DIR__ . "/spider/{$uri['host']}.json";
		if (  !is_readable($json_file) ) {
			throw new Excp("未找到该网站采集规则", 404, ['url'=>$url]);
		}

		$path = str_replace("{$uri['scheme']}://{$uri['host']}", '', $url);
		$json_text = file_get_contents($json_file);
		$c = Utils::json_decode( $json_text );

		foreach ($c['find'] as $reg => $key ) {
			if ( preg_match($reg, $path, $match) ) {
				$rule = $c[$key];
				break;
			}
		}

		if ( empty($rule) ) {
			throw new Excp("未找到与地址匹配的采集规则", 404, ['url'=>$url,'config'=>$c]);
		}

		// return ['uri'=>$uri, 'rule'=>$rule, 'path'=>$path];

		$html = Browsershot::url($url)
			->setIncludePath('$PATH:/node/bin')
			->noSandbox()
			->bodyHtml();
		;

		$dom = new Dom;
		$dom->loadStr($html, []);

		// echo "<pre>";
		$data = [];
		foreach ($rule as $key => $ru ) {

			$type= 'text';
			$attrRu = explode(':', $ru);
			if ( count($attrRu) == 2 ) {
				$type= $attrRu[1];
			}

			$attr = explode('|', $attrRu[0]);

			if ( count($attr) == 1 ) {
				$attr[1] = 'name';
			}


			if ( $ru[0] == '@'  ) {  // 数组
				
				$ru = trim(str_replace('@', '', $attr[0]));
				// echo "@| $ru \n";
				$nodes = $dom->find($ru);
				$data[$key] = [];
				foreach ($nodes as $node) {
					try {
						if ( $type == 'text' ) {
							$value = $node->text;
						} else if ( $type == 'html') {
							$value = $node->innerHTML;
						} else {
							$value = $node->getAttribute($attr[1]);
						}

						if ( $type == "image" ) {
							$value = $this->crawlImage($value, $node );
						}

						$data[$key][] = $value;

					} catch( Exception $e ) {}
					
				}


			} else if ( $ru[0] == '{' && substr($ru, -1) == '}') {
				
				$ru = trim(substr($ru, 1, count($ru)-2));
				$data[$key] = $uri[$ru];
				// echo "{}| $ru \n";

			} else {
				// echo "$ru \n";
				$ru = trim($ru);
				$node = $dom->find($attr[0]);

				try {
					if ( $type == 'text' ) {
						$value = $node->text;
					} else if ( $type == 'html') {
						$value = $node->innerHTML;
					} else {
						$value = $node->getAttribute($attr[1]);
					}
					if ( $type == "image" ) {
						$value = $this->crawlImage($value);
					}


				} catch( Exception $e ) {}

				$data[$key] = $value;
			}
		}


		return $data;
		print_r($data);

		// echo htmlspecialchars($html);

		exit;





		$imgs = $dom->find('img');
		$title = $dom->find('title') ;
		$spans = $dom->find('.article-sub > span');

		echo $title->text;
		echo $spans[0]->text;
		echo $spans[1]->text;

		foreach ($imgs as $img) {
			echo "IMG: " . $img->getAttribute('src') . "\n";
		}


		// echo  $page->find('#post-user');
		// exit;
		return [
			// "uri"=>$uri,
			// "query"=>$query,
			"url"=>$url,
			"html" => $html
			// "title"=>$page->find('#activity-name')->text(),
			// "author"=>$page->find('#post-user')->text(),
			// "publish_time"=>$page->find('#post-date')->text()
			// "body" => $page->find('body')->text()
		];
	}
}