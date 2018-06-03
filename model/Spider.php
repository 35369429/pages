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
use \Mina\Delta\Render as DeltaRender;

use \Spatie\Browsershot\Browsershot;
use \PHPHtmlParser\Dom;
// use \Readability\Readability;


use andreskrey\Readability\Readability;
use andreskrey\Readability\HTMLParser;
use andreskrey\Readability\Configuration;
use \Exception as Exception;


/**
 * 蜘蛛数据模型
 */
class Spider {

	function __construct( $param=[] ) {
		$this->delta_render = new DeltaRender( $param );
		$this->media = new Media( $param );
		$this->hasCrawled = [];
	}


	/**
	 * 抓取图片
	 * @param  [type] $imgUrl [description]
	 * @return [type]         [description]
	 */
	function crawlImage( $url, & $node ) {

		if ( empty($url) ) {
			return "";
		}

		if ( !empty($this->hasCrawled[$url]) ) {
			return $this->hasCrawled[$url]['path'];
			// return  $url;
		}

		$ext =  $this->media->getExt($url);
		if ( !in_array($img, ['png', 'jpg', 'jpeg', 'gif', 'svg']) ) {
			$ext = 'png';
		}
		try {
			$rs = $this->media->uploadImage($url, $ext);
		} catch( Excp $e) {
			return $url;
		} catch( Exception $e ){
			return $url;
		}

		$newurl = $rs['url'];
		$node->setAttribute('src',  $newurl );
		$node->setAttribute('data-src',  $newurl );
		$node->setAttribute('data-path',  $rs['path'] );
		$node->setAttribute('data-xxxx',  $rs['path'] );
		$this->hasCrawled[$newurl] = $rs;
		return $rs['path'];
	}



	/**
	 * 解析字段
	 * @param  [type] $node [description]
	 * @param  [type] $type [description]
	 * @param  [type] $attr [description]
	 * @return [type]       [description]
	 */
	private function parseField( $node, $type, $attr ) {

		if ( $node->count() == 0 && $type != 'image' ) {
			return "";
		}

		$readability = new Readability(new Configuration());

		switch ($type) {
		
			case 'image':
				$src = $node->getAttribute($attr);
				$val = $this->crawlImage( $src, $node );
				break;
			case 'text' : 
				$val = $node->text;
				break;
			case 'html' : 
				$val = $node->innerHTML;
				break;
			case 'delta':
				$html = $node->innerHTML;			
				$this->delta_render->loadByHTML($html);
				$val = $this->delta_render->delta();
				break;
			case 'html-readability':
				$html = $node->innerHTML;
				$val = $html;

				// try {
				// 	$html = str_replace('section', 'div', $html);
				// 	$readability->parse($html);
				// 	$val =  $readability->getContent();

				// } catch (ParseException $e) {
				// 	// echo sprintf('Error processing text: %s', $e->getMessage);
				// }
				break;
			case 'delta-readability':
				echo 'delta-readability';
				$html = $node->innerHTML;
				// try {
				// 	$readability->parse($html);
				// 	$html =  $readability->getContent();
				// } catch (ParseException $e) {
				// 	// echo sprintf('Error processing text: %s', $e->getMessage);
				// }
				// var_dump($html);

				$this->delta_render->loadByHTML($html);
				$val = $this->delta_render->delta();
				break;
			default:
				$val = $node->text;
				break;
		}

		return $val;
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
			throw new Excp("未找到该网站采集规则", 404, ['url'=>$url, 'json_file'=>$json_file]);
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
			throw new Excp("未找到与地址匹配的采集规则", 404, ['url'=>$url,'config'=>$c,'path'=>$path]);
		}

		// return ['uri'=>$uri, 'rule'=>$rule, 'path'=>$path];

		$html = Browsershot::url($url)
			->setIncludePath('$PATH:/node/bin')
			->noSandbox()
			->bodyHtml();
		;

		// 过滤掉不支持的 tag
		$html = str_replace('section', 'div', $html);
		$dom = new Dom;
		$dom->loadStr($html, []);
		$data = [];
		foreach ($rule as $key => $ru ) {

			$type = 'text'; $attr = null; 
			$params = explode(':', $ru);
			if ( count($params) == 2 ) {
				$type=$params[1];
			}
			$params = explode('|', $params[0]);
			$ru = $params[0];
			if ( count($params) == 2 ) {
				$attr = $params[1];
			}


			if ( $type[0] == '@'  ) {  // 数组
				$type = trim(str_replace('@', '', $type));
				$nodes = $dom->find($ru);
				$from = 0;
				$to = count($nodes);

				// 读取指定长度 @sometype{5}
				if ( preg_match("/\{([0-9]+)\}[ ]*$/", $type, $match) ) {
					
					if ( $match[1] >= $to ) {
						$match[1] = $to;
					}
					$to = $match[1];
					$type = trim(str_replace($match[0], '', $type));
				}


				// 读取指定下标 @sometype{1,5}
				if ( preg_match("/\{([0-9]+),([0-9]+)\}[ ]*$/", $type, $match) ) {
					
					if ( $match[2] >= $to ) {
						$match[2] = $to;
					}

					$from=$match[1];
					$to = $match[2];
					$type = trim(str_replace($match[0], '', $type));
				}

				// 读取指定下标 @sometype[1]
				if ( preg_match("/\[([0-9]+)\][ ]*$/", $type, $match) ) {

					if ( $match[1] >= $to ) {
						continue;
					}

					$from=$match[1];
					$type = trim(str_replace($match[0], '', $type));
					$node = $nodes[$from];
					$data[$key] = $this->parseField($node, $type, $attr );
					continue;

				}

				for( $i=$from; $i<$to; $i++) {
					$node = $nodes[$i];
					$data[$key][] = $this->parseField($node, $type, $attr );
				}

				// echo   "from=$from to=$to \n";
			} else if ( $ru[0] == '{' && substr($ru, -1) == '}') {
				$ru = trim(substr($ru, 1, count($ru)-2));
				$data[$key] = $uri[$ru];

			} else {
				$node= $dom->find($ru);
				$data[$key] = $this->parseField( $node, $type, $attr );
			}
		}

		return $data;
	}
}