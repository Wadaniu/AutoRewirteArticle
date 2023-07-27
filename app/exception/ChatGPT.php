<?php

namespace app\exception;

class ChatGPT {
    private $apiUrl;
    private $apiKey;

    public function __construct($apiKey,$apiUrl = 'https://api.openai.com/v1/chat/completions') {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function sendMessage($message) {
        return $this->sendRequest($message);
    }

    private function sendRequest($message) {
        $data = array(
            'message' => $message,
            'apiKey' => $this->apiKey
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if ($response === false) {
            return false;
        } else {
            $result = json_decode($response, true);
            return $result['message'];
        }
    }
}