<?php

namespace Jeeinn\QfAppBuilder;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Agent
{
    public string $appId;
    public string $appToken;
    public string $baseUri = 'https://qianfan.baidubce.com';
    
    private string $newConversationUri = '/v2/app/conversation';
    private string $uploadFileUri = '/v2/app/conversation/file/upload';
    private string $talkUri = '/v2/app/conversation/runs';
    
    private Client $client;
    private int $queryLengthLimit = 2000;
    
    /**
     * @throws Exception
     */
    public function __construct(string $appId = '', string $appToken = '', string $baseUri = '')
    {
        if (empty($appId)) throw new \Exception('[QfAppBuilder] appId不能为空');
        if (empty($appToken)) throw new \Exception('[QfAppBuilder] appToken不能为空');
        $this->appId = $appId;
        $this->appToken = $appToken;
        $this->baseUri = $baseUri ?: $this->baseUri;
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'http_errors' => false, // 方便获取返回值
            'headers' => [
                'Authorization' => 'Bearer ' . $this->appToken,
            ],
        ]);
    }
    
    /**
     * @description 新建对话，返回对话id
     * @link https://cloud.baidu.com/doc/AppBuilder/s/vlv2ftwfs
     * @author jeeinn 2024/04/29
     * @return string
     * @throws Exception
     */
    public function newConversation(): string
    {
        try {
            $response = $this->client->request('POST', $this->newConversationUri, [
                'json' => ['app_id' => $this->appId],
            ]);
            
            $body = $response->getBody();
            if ($response->getStatusCode() != 200 || empty($body)) {
                throw new \Exception("[Response] {$body}\n[QfAppBuilder] 新建对话ID失败");
            }
            $jsonData = json_decode($body, true);
            if (!isset($jsonData['conversation_id'])) {
                throw new \Exception("[Response] {$body}\n[QfAppBuilder] 新建对话ID失败");
            }
            return $jsonData['conversation_id'];
        } catch (GuzzleException $e) {
            throw new \Exception("[GuzzleException] {$e->getMessage()}");
        } catch (Exception $e) {
            throw new \Exception("[Exception] {$e->getMessage()}");
        }
    }
    
    /**
     * @description 上传文件，返回文件id
     * 注意，传入的路径需包含后缀名称，否则会报错：未知文件类型
     * @link https://cloud.baidu.com/doc/AppBuilder/s/flv2fur67
     * @author jeeinn 2024/04/29
     * @param string $filePath
     * @param string $conversationId
     * @return string
     * @throws Exception
     */
    public function uploadFile(string $filePath, string $conversationId): string
    {
        if (!is_file($filePath)) throw new \Exception('[QfAppBuilder] 请检查传入的文件');
        
        try {
            $response = $this->client->request('POST', $this->uploadFileUri, [
                'multipart' => [
                    [
                        'name' => 'app_id',
                        'contents' => $this->appId,
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'), // 使用流打开文件
                        'filename' => basename($filePath), // 使用文件的原始名称
                        // 'contents' => file_get_contents($filePath),
                        // 'filename' => 'custom_filename.txt',
                    ],
                    [
                        'name' => 'conversation_id',
                        'contents' => $conversationId,
                    ],
                ],
            ]);
            
            $body = $response->getBody();
            if ($response->getStatusCode() != 200 || empty($body)) {
                throw new \Exception("[Response] {$body}\n[QfAppBuilder] 上传文件失败");
            }
            $jsonData = json_decode($body, true);
            if (!isset($jsonData['id'])) {
                throw new \Exception("[Response] {$body}\n[QfAppBuilder] 上传文件失败");
            }
            return $jsonData['id'];
        } catch (GuzzleException $e) {
            throw new \Exception("[GuzzleException] {$e->getMessage()}");
        } catch (Exception $e) {
            throw new \Exception("[Exception] {$e->getMessage()}");
        }
    }
    
    /**
     * @description 对话，返回对话答案
     * @link https://cloud.baidu.com/doc/AppBuilder/s/mlv2fvh79
     * @author jeeinn 2024/04/29
     * @param string $conversationId
     * @param string $query
     * @param string|array $fileId
     * @return string
     * @throws Exception
     */
    public function talk(string $conversationId, string $query, $fileId): string
    {
        $json = [
            'app_id' => $this->appId,
            'query' => mb_substr($query, 0, $this->queryLengthLimit, 'utf-8'),
            'stream' => false,
            'conversation_id' => $conversationId,
        ];
        if ($fileId) {
            $fileIds = is_array($fileId) ? $fileId : [$fileId];
            $json['file_ids'] = $fileIds;
        }
        
        try {
            $response = $this->client->request('POST', $this->talkUri, [
                'json' => $json,
            ]);
            
            $body = $response->getBody();
            if ($response->getStatusCode() != 200 || empty($body)) {
                throw new \Exception("[Response] {$body}\n[QfAppBuilder] 对话异常");
            }
            $jsonData = json_decode($body, true);
            if (!isset($jsonData['conversation_id'])) {
                throw new \Exception("[Response] {$body}\n[QfAppBuilder] 对话异常");
            }
            // print_r($jsonData);
            return $jsonData['answer'];
        } catch (GuzzleException $e) {
            throw new \Exception("[GuzzleException] {$e->getMessage()}");
        } catch (Exception $e) {
            throw new \Exception("[Exception] {$e->getMessage()}");
        }
    }
    
    /**
     * @description 处理流式对话，返回最终对话答案
     * @author jeeinn 2024/04/29
     * @param string $conversationId
     * @param string $query
     * @param mixed $fileId
     * @param callable|null $callback 支持传入回调函数用于处理对话返回的一段段内容
     * @return string
     * @throws Exception
     * @deprecated 暂无法很好的处理流的返回（测试不通过，有乱序问题）
     */
    public function talkStream(string $conversationId, string $query, $fileId = null, callable $callback = null): string
    {
        $json = [
            'app_id' => $this->appId,
            'query' => mb_substr($query, 0, $this->queryLengthLimit, 'utf-8'),
            'stream' => true,
            'conversation_id' => $conversationId,
        ];
        if ($fileId) {
            $fileIds = is_array($fileId) ? $fileId : [$fileId];
            $json['file_ids'] = $fileIds;
        }
        
        try {
            $response = $this->client->request('POST', $this->talkUri, [
                'stream' => true,
                'json' => $json,
            ]);
            
            $body = $response->getBody();
            if (empty($body)) {
                throw new \Exception("[Response] {$body}\n[QfAppBuilder] 流式对话异常");
            }
            
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:30:59Z", "answer": "", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "function_call", "event_id": "0", "event_status": "done", "content_type": "function_call", "outputs": {"text": {"arguments": {}, "component_code": "ChatAgent", "component_name": "聊天助手"}}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:30:59Z", "answer": "", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "1", "event_status": "preparing", "content_type": "status", "outputs": {}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:00Z", "answer": "你好，", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "2", "event_status": "running", "content_type": "text", "outputs": {"text": "你好，"}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:01Z", "answer": "很抱歉，我无法直接分析表格数据，因为您没有提供具体的表格文件或数据。", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "2", "event_status": "running", "content_type": "text", "outputs": {"text": "很抱歉，我无法直接分析表格数据，因为您没有提供具体的表格文件或数据。"}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:03Z", "answer": "为了帮助您进行数据分析并给出统计结果，我需要您上传包含表格数据的Excel或Word文件。", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "2", "event_status": "running", "content_type": "text", "outputs": {"text": "为了帮助您进行数据分析并给出统计结果，我需要您上传包含表格数据的Excel或Word文件。"}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:04Z", "answer": "一旦您上传了文件，我将运用数据分析技术来提取关键数据，并生成有关销售、趋势等的信息。", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "2", "event_status": "running", "content_type": "text", "outputs": {"text": "一旦您上传了文件，我将运用数据分析技术来提取关键数据，并生成有关销售、趋势等的信息。"}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:05Z", "answer": "然后，我会基于分析结果生成清晰、易懂的报告，包括图表、趋势线和关键洞察，以帮助您理解数据并做出决策。", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "2", "event_status": "running", "content_type": "text", "outputs": {"text": "然后，我会基于分析结果生成清晰、易懂的报告，包括图表、趋势线和关键洞察，以帮助您理解数据并做出决策。"}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:05Z", "answer": "\n\n请问您是否有表格数据所在的Excel或Word文件需要上传？", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "2", "event_status": "running", "content_type": "text", "outputs": {"text": "\n\n请问您是否有表格数据所在的Excel或Word文件需要上传？"}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:05Z", "answer": "", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": false, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "2", "event_status": "done", "content_type": "text", "outputs": {"text": ""}}]}
            // data: {"request_id": "3b4648f0-1ee8-4805-8465-d7767c566a2d", "date": "2024-04-29T09:31:05Z", "answer": "", "conversation_id": "0e180ce4-8947-457c-90ff-fb8dfeaba0ff", "message_id": "a9025e6c-47c6-4f2f-ab21-eabda4fa4759", "is_completion": true, "content": [{"event_code": 0, "event_message": "", "event_type": "ChatAgent", "event_id": "3", "event_status": "success", "content_type": "status", "outputs": {}}]}
            $answer = $lastBuffer = '';
            while (!$body->eof()) {
                $buffer = $body->read(1024); // 读取 1KB 数据
                if ($lastBuffer) $buffer .= $lastBuffer;
                // echo "---start---\n$buffer\n---stop---\n";
                $parts = preg_split('/\n/', $buffer); // 使用正则表达式分割
                // print_r($parts);
                $len = count($parts);
                foreach ($parts as $k => $part) {
                    // 处理每个部分
                    $originalPart = $part;
                    $part = trim($part, "\n\r");
                    if (empty($part)) continue;
                    $part = str_replace('data: ', '', $part);
                    $data = json_decode($part, true);
                    if (json_last_error() == JSON_ERROR_NONE) {
                        if (empty($data['answer'])) continue;
                        $content = $data['answer'];
                        $answer .= $content;
                        if (is_callable($callback)) $callback($content);
                        $lastBuffer = '';
                    } else {
                        if ($k == $len - 1) $lastBuffer = $originalPart;
                    }
                }
            }
            
            return $answer;
        } catch (GuzzleException $e) {
            throw new \Exception("[GuzzleException] {$e->getMessage()}");
        } catch (Exception $e) {
            throw new \Exception("[Exception] {$e->getMessage()}");
        }
    }
}