<?php

namespace app\exception;

class ChatGPT {
    private $apiUrl;
    private $apiKey;

    public function __construct($apiKey,$apiUrl = 'https://api.openai.com/v1/chat/completions') {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function sendRequest($message, $model = 'gpt-3.5-turbo') {
        $data = array(
            'model' => $model,
            'messages' => [
                [
                    'role'  => "system",
                    'content'   =>  "You're a senior sports editor."
                ],
                [
                    'role'  => "user",
                    'content'   =>  $message
                ]
            ],
            "temperature" => 0.8
        );

        $headers = array(
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CAINFO, 'ca/cacert.pem'); // 替换成正确的cacert.pem文件路径

        $response = curl_exec($ch);
        if ($response === false) {
            return curl_error($ch);
        }
        curl_close($ch);

        return json_decode($response, true);
    }
}