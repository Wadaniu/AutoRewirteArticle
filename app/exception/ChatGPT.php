<?php

namespace app\exception;

class ChatGPT {
    private $apiUrl;
    private $apiKey;

    public function __construct($apiKey,$apiUrl = 'https://api.openai.com/v1/chat/completions') {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function sendRequest($prompt, $model = 'curie', $maxTokens = 100) {
        $data = array(
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens' => $maxTokens
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

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}