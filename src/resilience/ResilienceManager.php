<?php

namespace Dymo\Resilience;

use Dymo\Exceptions\{DymoAPIError, NetworkException, ServerException, RateLimitException};

/**
 * Manages resilient HTTP requests with retry logic and fallback support
 */
class ResilienceManager {
    private ResilienceConfig $config;
    private string $clientId;
    private RateLimitTracker $rateLimitTracker;

    public function __construct(ResilienceConfig $config = null, string $clientId = 'default') {
        $this->config = $config ?? new ResilienceConfig();
        $this->clientId = $clientId;
        $this->rateLimitTracker = RateLimitTracker::getInstance();
    }

    public function getConfig(): ResilienceConfig {
        return $this->config;
    }

    public function getClientId(): string {
        return $this->clientId;
    }

    /**
     * Execute HTTP request with resilience capabilities
     */
    public function executeWithResilience(callable $requestFunction, $fallbackData = null) {
        $lastError = null;
        $totalAttempts = 1 + $this->config->retryAttempts; // 1 normal + N retries

        // Clean up expired rate limits periodically
        $this->rateLimitTracker->clearExpiredLimits();

        // Check if client is currently rate limited
        if ($this->rateLimitTracker->isRateLimited($this->clientId)) {
            $retryAfter = $this->rateLimitTracker->getRetryAfter($this->clientId);
            if ($retryAfter) {
                error_log("[Dymo API] Client {$this->clientId} is rate limited. Waiting {$retryAfter} seconds...");
                sleep($retryAfter);
            }
        }

        for ($attempt = 1; $attempt <= $totalAttempts; $attempt++) {
            try {
                $result = $requestFunction();
                
                // If result contains headers (from Guzzle), update rate limit tracking
                if (is_array($result) && isset($result['headers'])) {
                    $this->rateLimitTracker->updateRateLimit($this->clientId, $result['headers']);
                }

                return $result;
            } catch (\Exception $error) {
                $lastError = $error;

                // Handle rate limiting specifically
                if ($error instanceof RateLimitException) {
                    error_log("[Dymo API] Rate limited. Not retrying.");
                    break;
                }

                $shouldRetry = $this->shouldRetry($error);
                $isLastAttempt = $attempt === $totalAttempts;

                if (!$shouldRetry || $isLastAttempt) {
                    if ($this->config->fallbackEnabled && $fallbackData !== null) {
                        error_log("[Dymo API] Request failed after {$attempt} attempts. Using fallback data.");
                        return $fallbackData;
                    }
                    throw $error;
                }

                $delay = $this->calculateDelay($attempt);
                error_log("[Dymo API] Attempt {$attempt} failed. Retrying in {$delay}ms...");
                usleep($delay * 1000); // Convert milliseconds to microseconds
            }
        }

        throw $lastError;
    }

    /**
     * Determine if request should be retried based on error
     */
    private function shouldRetry(\Exception $error): bool {
        // Network errors - retry
        if ($error instanceof NetworkException) {
            return true;
        }

        // Server errors (5xx) - retry
        if ($error instanceof ServerException) {
            return true;
        }

        // Rate limiting (429) - DON'T retry (handled separately)
        if ($error instanceof RateLimitException) {
            return false;
        }

        // Client errors (4xx except 429) - don't retry
        return false;
    }

    /**
     * Calculate exponential backoff delay
     */
    private function calculateDelay(int $attempt): int {
        return $this->config->retryDelay * pow(2, $attempt - 1);
    }
}