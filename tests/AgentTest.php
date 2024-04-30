<?php

use Jeeinn\QfAppBuilder\Agent;
use Jeeinn\QfAppBuilder\Utils;
use PHPUnit\Framework\TestCase;

final class AgentTest extends TestCase
{
    // todo 执行测试用例时，请替换为真实可用的参数
    public string $appId = 'your app id';
    public string $appToken = 'your app token';
    
    /**
     * @command ./vendor/bin/phpunit --filter testNewConversation tests/
     * @throws Exception
     */
    public function testNewConversation()
    {
        $agent = new Agent($this->appId, $this->appToken);
        $conversationId = $agent->newConversation();
        echo Utils::formatMsg("conversation_id created, conversation_id: {$conversationId}");
        // {"request_id": "f960c160-d89c-4514-855a-508b399393a4", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff"}
        $this->assertNotEmpty($conversationId);
    }
    
    /**
     * @command ./vendor/bin/phpunit --filter testUploadFile tests/
     * @throws Exception
     */
    public function testUploadFile()
    {
        $conversationId = "0e180ce4-8947-457c-90ff-fb8dfeaba0ff";
        
        $agent = new Agent($this->appId, $this->appToken);
        $fileId = $agent->uploadFile(__DIR__ . '/test.xlsx', $conversationId);
        echo Utils::formatMsg("file uploaded, file id: {$fileId}");
        // 0af77ea4-15bf-46b3-b1d3-df1f7264524e
        $this->assertNotEmpty($fileId);
    }
    
    /**
     * @command ./vendor/bin/phpunit --filter testTalk tests/
     * @throws Exception
     */
    public function testTalk()
    {
        $conversationId = "0e180ce4-8947-457c-90ff-fb8dfeaba0ff";
        $query = '你好，请帮我分析已上传的表格数据。并给出统计结果。';
        $fileId = "0af77ea4-15bf-46b3-b1d3-df1f7264524e";
        
        $agent = new Agent($this->appId, $this->appToken);
        $answer = $agent->talk($conversationId, $query, $fileId);
        echo Utils::formatMsg("answer: {$answer}");
        $this->assertNotEmpty($answer);
    }
    
    /**
     * @command ./vendor/bin/phpunit --filter testTalkStream tests/
     * @throws Exception
     */
    public function testTalkStream()
    {
        $conversationId = "0e180ce4-8947-457c-90ff-fb8dfeaba0ff";
        $query = '你好，给我讲个笑话。';
        
        $agent = new Agent($this->appId, $this->appToken);
        // $answer = $agent->talkStream($conversationId, $query, $fileId);
        $answer = $agent->talkStream($conversationId, $query, null, function ($part){
            echo Utils::formatMsg($part);
        });
        echo Utils::formatMsg("answer: {$answer}");
        $this->assertNotEmpty($answer);
    }
    
    
    /**
     * @description 测试完整流程
     * @command ./vendor/bin/phpunit --filter testComplete tests/
     * @throws Exception
     */
    public function testComplete()
    {
        // 创建会话
        $agent = new Agent($this->appId, $this->appToken);
        $conversationId = $agent->newConversation();
        echo Utils::formatMsg("conversation_id created, conversation_id: {$conversationId}");
        $this->assertNotEmpty($conversationId);
        // 上传文件
        $fileId = $agent->uploadFile(__DIR__ . '/test.xlsx', $conversationId);
        echo Utils::formatMsg("file uploaded, file id: {$fileId}");
        $this->assertNotEmpty($fileId);
        // 对话
        $query = "我该如何描述和总结表格中的数据？";
        $answer = $agent->talk($conversationId, $query, $fileId);
        echo Utils::formatMsg("answer: {$answer}");
        $this->assertNotEmpty($answer);
    }
}