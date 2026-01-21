<?php

namespace Dymo\Resilience;

/**
 * Manages rate limiting information per client
 */
class RateLimitTracker {
    private static ?RateLimitTracker $instance = null;
    private array $clientLimits = [];

    public static function getInstance(): RateLimitTracker {
        if (self::$instance === null) {
            self::$instance = new RateLimitTracker();
        }
        return self::$instance;
    }

    /**
     * Update rate limit information from response headers
     */
    public function updateRateLimit(string $clientId, array $headers): void {
        if (!isset($this->clientLimits[$clientId])) {
            $this->clientLimits[$clientId] = [];
        }

        $limitInfo = &$this->clientLimits[$clientId];

        // Update rate limit headers
        if (isset($headers['x-ratelimit-limit-requests'])) {
            $limitInfo['limit'] = (int)$headers['x-ratelimit-limit-requests'];
        }
        if (isset($headers['x-ratelimit-remaining-requests'])) {
            $limitInfo['remaining'] = (int)$headers['x-ratelimit-remaining-requests'];
        }
        if (isset($headers['x-ratelimit-reset-requests'])) {
            $limitInfo['resetTime'] = $headers['x-ratelimit-reset-requests'];
        }
        if (isset($headers['retry-after'])) {
            $limitInfo['retryAfter'] = (int)$headers['retry-after'];
        }

        $limitInfo['lastUpdated'] = time();
    }

    /**
     * Check if client is currently rate limited
     */
    public function isRateLimited(string $clientId): bool {
        $limitInfo = $this->clientLimits[$clientId] ?? null;
        return $limitInfo !== null && isset($limitInfo['remaining']) && $limitInfo['remaining'] <= 0;
    }

    /**
     * Get retry-after time for client
     */
    public function getRetryAfter(string $clientId): ?int {
        return $this->clientLimits[$clientId]['retryAfter'] ?? null;
    }

    /**
     * Clean up expired rate limit information (older than 5 minutes)
     */
    public function clearExpiredLimits(): void {
        $now = time();
        foreach ($this->clientLimits as $clientId => $limitInfo) {
            if (isset($limitInfo['lastUpdated']) && ($now - $limitInfo['lastUpdated']) > 300) {
                unset($this->clientLimits[$clientId]);
            }
        }
    }
}