<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;


class CategoryController extends \Xpmse\Loader\Controller {
	
	function __construct() {
	}


	/**
	 * 分类列表检索
	 * @return [type] [description]
	 */
	function index() {	

		$query = $_GET;
		$query['order'] = !empty($query['order']) ? trim($query['order']) : 'priority';
		$query['perpage'] = !empty($query['perpage']) ? intval($query['perpage']) : 10;

		$c = new \Xpmsns\Pages\Model\Category;
		$cates = $c->search($query);

		$data = [
			'query' => $query,
			'cates' => $cates,
			'c' => $c
		];

		if ( $_GET['debug'] == 1 ) {
			echo "<pre>";
			print_r( $data );
			echo "</pre>";
		}
		App::render($data,'category','search.index');
		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
		    		'js/plugins/jquery-ui/jquery-ui.min.js',
	        		'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
				],
			'css'=>[
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css"
	 		],
			'crumb' => [
	            "分类" => APP::R('category','index'),
	            "分类管理" =>'',
	        ]
		];
	}


	/**
	 * 分类编辑
	 * @return
	 */
	function edit() {

		$category_id = $_GET['category_id'];
		$parent_id = $_GET['parent_id'];
		$action_name = '添加分类';
		$c = new \Xpmsns\Pages\Model\Category;
		$cates = $c->search(['perpage'=>100]);
		if ( !empty($category_id) ) {
			$ca = $c->getById($category_id);
			if ( !empty($ca) ) {
				$action_name =  $ca['name'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'category_id'=>$category_id,
			'parent_id' => $parent_id,
			'cates' => $cates,
			'ca' => $ca,
			'c' => $c
		];

		App::render($data,'category','edit');
		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js"
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css"
	 		],

			'crumb' => [
	            "分类" => APP::R('category','index'),
	            "管理分类" => APP::R('category','index'),
	            "$action_name" => ""
	        ],

	        'active'=> [
	 			'slug'=>'xpmsns/pages/category/index'
	 		]
		];

	}


	function faker() {

		$data = ["女装","男装","内衣","鞋靴","箱包","配件","童装玩具","孕产","用品","家电","数码","手机","美妆","洗护","保健品","珠宝","眼镜","手表","运动","户外","乐器","游戏","动漫","影视","美食","生鲜","零食","鲜花","宠物","农资","房产","装修","建材","家具","家饰","家纺","汽车","二手车","用品","办公","DIY","五金电子","百货","餐厨","家庭保健","学习","卡券","本地服务"];
		
		$c = new \Xpmsns\Pages\Model\Category;
		$c->runSQL("DELETE FROM {{table}} WHERE `parent_id` IS NOT NULL");
		$cates = $c->search();
		$roots = $cids = array_column($cates['data'], 'category_id');
		$rootmap = [];

		foreach ($data as $cname ) {
			$idx = array_rand( $cids );
			$cid = $cids[$idx];
			$dt = [
				"name" => $cname,
				"fullname" => $cname,
				"parent_id" => $cid,
			];

			$root_id = $cid;
			if ( !in_array($cid, $roots) ) {
				$root_id = $rootmap[$cid];
			}

			$dt['root_id'] = $root_id;
			$rs = $c->create($dt);
			$rootmap[$rs['category_id']] = $root_id;

			array_push($cids, $rs['category_id']);
			echo "{$cname}({$rs['category_id']}) 已添加 \n";
		}

	}

}