<?php

use GuzzleHttp\Exception\GuzzleException;
use Jeeinn\QfAppBuilder\Agent;
use Jeeinn\QfAppBuilder\Utils;
use PHPUnit\Framework\TestCase;

final class AgentTest extends TestCase
{
    public string $appId = '369e0d11-7989-4d77-9b01-7b42e7171570';
    public string $appToken = 'bce-v3/ALTAK-BjSeeRUVZOvqcjbpGLZRW/9a2b220ed50184067ba4f342dcbb94544c596d26';
    
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
}