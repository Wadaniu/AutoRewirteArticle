<?php

namespace app\exception;

class KeyPool {
    private $apiKeys;
    private $keyModel;

    public function __construct($apiKeys,$keyModel) {
        $this->apiKeys = $apiKeys;
        $this->keyModel = $keyModel;
    }

    public function getAvailableKey() {
        foreach ($this->apiKeys as $key) {
            return $key;
        }

        return false;
    }

    public function markKeyAsFailed($key) {
        $this->removeKeyFromPool($key);
        $this->recordFailedKeyToDatabase($key);
    }

    private function isKeyAvailable($key): bool
    {
        $chatGPT = new ChatGPT($key);

        $message = "test connection";
        $response = $chatGPT->sendRequest($message);

        if (!isset($response['choices']) || is_null($response)){
            return  false;
        }

        // 这里根据实际情况判断响应是否有效
        return true;
        // 返回 true 表示密钥可用，返回 false 表示密钥不可用
    }

    private function removeKeyFromPool($key) {
        $keyIndex = array_search($key, $this->apiKeys);
        if ($keyIndex !== false) {
            unset($this->apiKeys[$keyIndex]);
        }
    }

    private function recordFailedKeyToDatabase($key) {
        // 在这里添加将失败的密钥记录到数据库的逻辑
        $update = [
            'status' => 0,
            'last_notice'   =>  '连接失败'
        ];
        $this->keyModel->where('key',$key)->save($update);
    }
}