<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
// use \Xpmsns\pages\Api\Article;

echo "\n\Xpmsns\pages\Api\Article 测试... \n\n\t";

class testArticleapi extends PHPUnit_Framework_TestCase {

    // 读取用户
    protected function &getUser() {
        static $value = null;
        return $value;
    }

    protected function &getInviter() {
        static $value = null;
        return $value;
    }


    protected function &getArticle() {
        static $value = null;
        return $value;
    }
    
    protected function &getArticles() {
        static $value = null;
        return $value;
    }

    protected function out( $message ) {
        fwrite(STDOUT,$message );
    }

    function testCreateUser() {

        $this->out( "\n创建单元测试用户.....");
        $u = new \Xpmsns\User\Model\User;
       
        $inviter = &$this->getInviter();
        $inviter = $u->create([
            "mobile" =>  "131" . time(),
            "password" => "1111111"
        ]);
        
        sleep(1);

        $user = &$this->getUser();
        $user = $u->create([
            "mobile" =>  "131" . time(),
            "inviter" => $inviter["user_id"],
            "password" => "1111111"
        ]);

       
        $this->out( "完成\n");
    }


    function testArticle() {

        $this->out( "\n创建测试文章.....");
        $art = new \Xpmsns\Pages\Model\Article;
        $article = &$this->getArticle();
        $now = date("Y-m-d H:i:s");
        $article = $art->save([
            "title" => "单元测试创建文章 {$now}",
            "content" => "<p><b>单元测试文章：</b> 单元测试创建文章 {$now}</p>"
        ]);
      
        $this->out( "完成\n");
    }

    function testArticles() {

        $this->out( "\n创建测试文章.....");
        $art = new \Xpmsns\Pages\Model\Article;
        $articles = &$this->getArticles();

        for( $i=0; $i<5; $i++ ) {
            $now = date("Y-m-d H:i:s");
            $articles[$i] = $art->save([
                "title" => "单元测试创建文章 {$now}",
                "content" => "<p><b>单元测试文章：</b> 单元测试创建文章 {$now}</p>"
            ]);
        }
        $this->out( "完成\n");
    }


    // 测试用户登录之后，并且接受阅读文章任务
	function testGetAfterAcceptReadingTask() {

        $articles = &$this->getArticles();
        $this->out( "\n\n测试登录并接受阅读文章任务后访问文章 (".count($articles).")\n-------\n");

        // 模拟用户登录
        $u = new \Xpmsns\User\Model\User;
        $user = &$this->getUser();
        $user_id = $user["user_id"];
        $u->loginSetSession( $user_id );

        // 领取任务 (阅读文章任务)
        $ut = new \Xpmsns\User\Model\UserTask;
        $ut->acceptBySlug("article-reading", $user_id);
        $task = $ut->getByTaskSlugAndUserId("article-reading", $user_id );
        $quantity = $task["quantity"];
        
       
        // 访问API 
        $api = new \Xpmsns\pages\Api\Article;
        
        // 验证任务信息
        for( $i=0; $i<count($quantity); $i++ ) {

            $article = $articles[$i];
            if ( empty($article) ) {
                continue;
            }

            $coinBefore = $u->getCoin( $user_id );
            $resp = $api->call('get',['articleId'=>$article["article_id"], "select"=>"title,article_id"]);
            $this->assertTrue( $resp["article_id"] == $article["article_id"]);
            $this->assertTrue( $resp["title"] == $article["title"]);
            usleep(5100000);
            $duration = $api->call('leave',['articleId'=>$article["article_id"]]);
            $this->assertTrue( $duration >= 5);
            usleep(1100000);
            $coinAfter =  $u->getCoin( $user_id );
            $this->out( 
                "阅读文章: {$resp["title"]} ".  
                " 停留时长: {$duration} ". 
                " 积分余额: {$coinBefore} -> {$coinAfter} (+". ($coinAfter - $coinBefore) .  ")\n"
            );

            // 验证进程
            $task = $ut->getByTaskSlugAndUserId("article-reading", $user_id );
            $this->assertTrue($task["usertask"]["process"] ==  $i+1 );
            $this->assertTrue(($coinAfter - $coinBefore) == $quantity[$i] );
        }

        $this->assertTrue($task["usertask"]["status"] == "completed" );
    }


    // 测试用户登录之后访问
	function testGetAfterLogin() {

        $article = &$this->getArticle();
        $this->out( "\n\n测试登录之后访问文章 ( ID:#{$article["article_id"]} )\n-------\n");

        // 模拟用户登录
        $u = new \Xpmsns\User\Model\User;
        $user = &$this->getUser();
        $user_id = $user["user_id"];
        $u->loginSetSession( $user_id );
       
        // 访问API 
		$api = new \Xpmsns\pages\Api\Article;
        $resp = $api->call('get',['articleId'=>$article["article_id"], "select"=>"title,article_id"]);
        $this->assertTrue( $resp["article_id"] == $article["article_id"]);
        $this->assertTrue( $resp["title"] == $article["title"]);
        usleep(1100000);
        $duration = $api->call('leave',['articleId'=>$article["article_id"]]);
        $this->assertTrue( $duration >= 1);

        // 验证计数器是否增加

    }



     // 清除测试数据
     function testClean(){

        $user = &$this->getUser();
        $user_id = $user["user_id"];

        $inviter = &$this->getInviter();
        $inviter_id = $inviter["user_id"];

        $article = &$this->getArticle();
        $article_id = $article["article_id"];


        $articles = &$this->getArticles();

        $this->out( "\n清除测试数据.....");
        sleep(2);

        // 清除单元测试数据
        $u = new \Xpmsns\User\Model\User;
        $art = new \Xpmsns\Pages\Model\Article;
        $ut = new \Xpmsns\User\Model\UserTask;
        $u->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        $u->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$inviter_id]);
        $art->runSql("DELETE FROM {{table}} WHERE `article_id`=?", false, [$article_id]);
        $ut->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);

        foreach($articles as $article ) {
            $art->runSql("DELETE FROM {{table}} WHERE `article_id`=?", false, [$article["article_id"]]);
        }

        $this->out( "完成\n");
    }
	
}