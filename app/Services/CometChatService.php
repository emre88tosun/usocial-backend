<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CometChatService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.cometchat.base_url');
        $this->apiKey = config('services.cometchat.api_key');
    }

    public function get(string $endpoint, array $query = []): array
    {
        $response = Http::withHeaders(['apiKey' => "{$this->apiKey}", 'Content-Type' => 'application/json', "Accept" => "application/json"])
            ->get($this->baseUrl . $endpoint, $query);

        return $response->json();
    }

    public function post(string $endpoint, array $data = [], ?string $onBehalfOf = null): array
    {
        $headers = ['apiKey' => "{$this->apiKey}", 'Content-Type' => 'application/json', "Accept" => "application/json"];
        if (!is_null($onBehalfOf)) {
            $headers['onBehalfOf'] = $onBehalfOf;
        }
        $response = Http::withHeaders($headers)->post($this->baseUrl . $endpoint, $data);

        return $response->json();
    }

    public function delete(string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders(['apiKey' => "{$this->apiKey}", 'Content-Type' => 'application/json', "Accept" => "application/json"])->delete($this->baseUrl . $endpoint, $params);

        return $response->json();
    }

    public function handleError($response): void
    {
        if ($response->failed()) {
            // Log or throw exception
            throw new \Exception('API request failed: ' . $response->body());
        }
    }
}
