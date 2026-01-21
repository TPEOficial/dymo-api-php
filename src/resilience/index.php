<?php

namespace Dymo\Resilience;

// Auto-loader for resilience classes
require_once __DIR__ . '/ResilienceConfig.php';
require_once __DIR__ . '/RateLimitTracker.php';
require_once __DIR__ . '/ResilienceManager.php';
require_once __DIR__ . '/FallbackDataGenerator.php';
require_once __DIR__ . '/HttpClient.php';

?>