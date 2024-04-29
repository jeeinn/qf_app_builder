<?php

class ParseResponse
{
    /**
     * @description 解析响应
     * @author 谢云伟 2024/04/29
     * @param $response
     * @return void
     * @throws Exception
     */
    public static function parse($response)
    {
        switch ($response->getStatusCode()) {
            case 200:
                break;
            case 400:
                throw new \Exception('请求参数错误');
            case 401:
        }
        $rs = $response->getBody();
        
        // if ($response->getStatusCode() != 200 || empty($response->getBody())) {
        //     throw new \Exception('新建对话ID失败');
        // }
    }
}