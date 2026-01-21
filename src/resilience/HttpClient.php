<?php

namespace Dymo\Resilience;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\{RequestOptions, Response};
use GuzzleHttp\Exception\{RequestException, ConnectException, ServerException, TooManyRequestsException};

/**
 * Enhanced HTTP client with resilience capabilities
 */
class HttpClient {
    private GuzzleClient $client;
    private ResilienceManager $resilienceManager;
    private array $defaultHeaders;

    public function __construct(
        string $baseUrl,
        string $authToken = null,
        ResilienceManager $resilienceManager = null,
        array $defaultHeaders = []
    ) {
        $this->resilienceManager = $resilienceManager ?? new ResilienceManager();
        
        $this->defaultHeaders = array_merge([
            'User-Agent' => 'DymoAPISDK/1.0.0',
            'X-Dymo-SDK-Env' => 'PHP',
            'X-Dymo-SDK-Version' => '0.0.36',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ], $defaultHeaders);

        if ($authToken) {
            $this->defaultHeaders['Authorization'] = "Bearer {$authToken}";
        }

        $this->client = new GuzzleClient([
            'base_uri' => $baseUrl,
            'timeout' => 30.0,
            'connect_timeout' => 10.0,
            'http_errors' => false, // Handle errors manually for retry logic
        ]);
    }

    /**
     * Make POST request with resilience
     */
    public function post(string $endpoint, array $data, array $options = []): array {
        return $this->requestWithResilience('POST', $endpoint, $data, $options);
    }

    /**
     * Make GET request with resilience
     */
    public function get(string $endpoint, array $params = [], array $options = []): array {
        $query = empty($params) ? '' : '?' . http_build_query($params);
        return $this->requestWithResilience('GET', $endpoint . $query, null, $options);
    }

    /**
     * Execute HTTP request with resilience
     */
    private function requestWithResilience(string $method, string $endpoint, $data = null, array $options = []): array {
        $requestFunction = function() use ($method, $endpoint, $data, $options) {
            $requestOptions = [
                RequestOptions::HEADERS => $this->defaultHeaders,
                RequestOptions::HTTP_ERRORS => false
            ];

            if ($data !== null) {
                $requestOptions[RequestOptions::JSON] = $data;
            }

            // Merge additional options
            $requestOptions = array_merge($requestOptions, $options);

            try {
                $response = $this->client->request($method, $endpoint, $requestOptions);
                return $this->handleResponse($response);
            } catch (RequestException $e) {
                throw $this->mapException($e);
            }
        };

        try {
            return $this->resilienceManager->executeWithResilience($requestFunction);
        } catch (\Exception $e) {
            throw $this->mapException($e);
        }
    }

    /**
     * Handle successful response
     */
    private function handleResponse(Response $response): array {
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        // Include headers for rate limit tracking
        $result = [
            'data' => json_decode($response->getBody()->getContents(), true),
            'status' => $statusCode,
            'headers' => array_change_key_case($headers, CASE_LOWER)
        ];

        // Handle rate limiting even on successful responses
        if ($statusCode === 429) {
            throw new TooManyRequestsException("Rate limited");
        }

        if ($statusCode >= 400) {
            throw $this->createExceptionFromStatus($statusCode, "HTTP {$statusCode}");
        }

        return $result;
    }

    /**
     * Map Guzzle exceptions to DymoAPI exceptions
     */
    private function mapException(\Exception $e): \Exception {
        if ($e instanceof ConnectException) {
            return new \Dymo\Exceptions\NetworkException($e->getMessage());
        } elseif ($e instanceof TooManyRequestsException) {
            return new \Dymo\Exceptions\RateLimitException($e->getMessage());
        } elseif ($e instanceof ServerException) {
            return new \Dymo\Exceptions\ServerException($e->getMessage());
        } elseif ($e instanceof RequestException) {
            $statusCode = $e->getCode();
            if ($statusCode >= 500) {
                return new \Dymo\Exceptions\ServerException($e->getMessage());
            } elseif ($statusCode === 429) {
                return new \Dymo\Exceptions\RateLimitException($e->getMessage());
            } elseif ($statusCode >= 400) {
                return new \Dymo\Exceptions\BadRequestError($e->getMessage());
            }
            return new \Dymo\Exceptions\APIError($e->getMessage());
        }

        return new \Dymo\Exceptions\APIError($e->getMessage());
    }

    /**
     * Create exception from HTTP status code
     */
    private function createExceptionFromStatus(int $statusCode, string $message): \Exception {
        if ($statusCode >= 500) {
            return new \Dymo\Exceptions\ServerException($message);
        } elseif ($statusCode === 429) {
            return new \Dymo\Exceptions\RateLimitException($message);
        } elseif ($statusCode === 401) {
            return new \Dymo\Exceptions\AuthenticationError($message);
        } elseif ($statusCode >= 400) {
            return new \Dymo\Exceptions\BadRequestError($message);
        }
        return new \Dymo\Exceptions\APIError($message);
    }
}