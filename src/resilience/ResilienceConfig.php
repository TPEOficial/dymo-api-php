<?php

namespace Dymo\Resilience;

/**
 * Configuration for resilience system
 */
class ResilienceConfig {
    public bool $fallbackEnabled = false;
    public int $retryAttempts = 2;  // Number of additional retries
    public int $retryDelay = 1000;  // Base delay in milliseconds

    public function __construct(array $config = []) {
        $this->fallbackEnabled = $config['fallbackEnabled'] ?? false;
        $this->retryAttempts = max(0, $config['retryAttempts'] ?? 2);
        $this->retryDelay = max(0, $config['retryDelay'] ?? 1000);
    }
}