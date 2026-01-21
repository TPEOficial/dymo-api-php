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
     * Parses a header value that could be a number or "unlimited".
     * Returns null if the value is "unlimited", null, or invalid.
     */
    private function parseHeaderValue(mixed $value): ?int {
        // Handle non-string/non-numeric types (arrays, objects, null)
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        // Convert to string and normalize
        $strValue = trim((string)$value);
        if ($strValue === '') {
            return null;
        }

        if (strtolower($strValue) === 'unlimited') {
            return null;
        }

        // Check if it's a valid number (after trimming whitespace)
        if (!is_numeric($strValue)) {
            return null;
        }

        $parsed = (int)$strValue;
        // Rate limits can't be negative
        return $parsed >= 0 ? $parsed : null;
    }

    /**
     * Update rate limit information from response headers
     */
    public function updateRateLimit(string $clientId, array $headers): void {
        if (!isset($this->clientLimits[$clientId])) {
            $this->clientLimits[$clientId] = [];
        }

        $limitInfo = &$this->clientLimits[$clientId];

        // Normalize header keys to lowercase
        $headers = array_change_key_case($headers, CASE_LOWER);

        // Get header values
        $limitRequests = $headers['x-ratelimit-limit-requests'] ?? null;
        $remainingRequests = $headers['x-ratelimit-remaining-requests'] ?? null;
        $resetRequests = $headers['x-ratelimit-reset-requests'] ?? null;
        $retryAfter = $headers['retry-after'] ?? null;

        // Only update numeric values if they are valid numbers (not "unlimited")
        $parsedLimit = $this->parseHeaderValue($limitRequests);
        $parsedRemaining = $this->parseHeaderValue($remainingRequests);
        $parsedRetryAfter = $this->parseHeaderValue($retryAfter);

        if ($parsedLimit !== null) {
            $limitInfo['limit'] = $parsedLimit;
        }
        if ($parsedRemaining !== null) {
            $limitInfo['remaining'] = $parsedRemaining;
        }
        // Mark as unlimited if header explicitly says "unlimited"
        if ($remainingRequests !== null && !is_array($remainingRequests) && !is_object($remainingRequests)) {
            if (strtolower(trim((string)$remainingRequests)) === 'unlimited') {
                $limitInfo['isUnlimited'] = true;
            }
        }
        if ($resetRequests !== null && is_string($resetRequests)) {
            $limitInfo['resetTime'] = $resetRequests;
        }
        if ($parsedRetryAfter !== null) {
            $limitInfo['retryAfter'] = $parsedRetryAfter;
        }

        $limitInfo['lastUpdated'] = time();
    }

    /**
     * Check if client is currently rate limited
     */
    public function isRateLimited(string $clientId): bool {
        $limitInfo = $this->clientLimits[$clientId] ?? null;
        if ($limitInfo === null) {
            return false;
        }
        // If marked as unlimited, never rate limited
        if (isset($limitInfo['isUnlimited']) && $limitInfo['isUnlimited'] === true) {
            return false;
        }
        // Only consider rate limited if remaining is explicitly set and is 0 or less
        return isset($limitInfo['remaining']) && $limitInfo['remaining'] <= 0;
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