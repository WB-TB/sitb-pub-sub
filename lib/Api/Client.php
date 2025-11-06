<?php

namespace Api;

use Exception;

class Client
{
    private $baseUrl;
    private $timeout;
    private $apiKey;
    private $apiHeader;
    private $logger;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/') . '/';
        $this->timeout = $config['timeout'] ?? 60;
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiHeader = $config['api_header'] ?? 'X-API-Key:';
        $this->logger = \Boot::getLogger();
    }

    /**
     * Send GET request to API
     *
     * @param string $endpoint API endpoint
     * @param array $query Query parameters
     * @return array Response data
     * @throws Exception
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            $url = $this->baseUrl . ltrim($endpoint, '/');
            
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HTTPHEADER => $this->buildHeaders(),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("cURL Error: " . $error);
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Decode Error: " . json_last_error_msg());
            }

            $this->logger->info("GET Request to {$url}", [
                'http_code' => $httpCode,
                'response' => $data
            ]);

            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'http_code' => $httpCode,
                'data' => $data
            ];

        } catch (Exception $e) {
            $this->logger->error("GET Request Failed: " . $e->getMessage(), [
                'endpoint' => $endpoint,
                'query' => $query
            ]);
            throw $e;
        }
    }

    /**
     * Send POST request to API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws Exception
     */
    public function post(string $endpoint, array $data): array
    {
        try {
            $url = $this->baseUrl . ltrim($endpoint, '/');
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $this->buildHeaders(['Content-Type: application/json']),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("cURL Error: " . $error);
            }

            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Decode Error: " . json_last_error_msg());
            }

            $this->logger->info("POST Request to {$url}", [
                'http_code' => $httpCode,
                'request_data' => $data,
                'response' => $responseData
            ]);

            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'http_code' => $httpCode,
                'data' => $responseData
            ];

        } catch (Exception $e) {
            $this->logger->error("POST Request Failed: " . $e->getMessage(), [
                'endpoint' => $endpoint,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Build HTTP headers for requests
     *
     * @param array $additionalHeaders Additional headers to include
     * @return array HTTP headers
     */
    private function buildHeaders(array $additionalHeaders = []): array
    {
        $headers = [
            'User-Agent: CKG-API-Client/1.0',
            'Accept: application/json',
        ];

        if (!empty($this->apiKey)) {
            $headers[] = $this->apiHeader . ' ' . $this->apiKey;
        }

        return array_merge($headers, $additionalHeaders);
    }
}