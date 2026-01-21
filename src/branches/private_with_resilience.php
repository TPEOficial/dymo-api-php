<?php

namespace Dymo\Branches;

use Dymo\Resilience\FallbackDataGenerator;

require_once "../config.php";
require_once "../exceptions.php";
require_once "../response_models.php";

/**
 * DEPRECATED: Use is_valid_data_raw($data) instead.
 * 
 * Validate given data using Data Verifier API.
 *
 * This function checks validity of given data and returns an
 * `DataVerifierResponse` object containing validation status,
 * validated data, and detailed information about each validation check.
 *
 * The API will check following data types:
 *
 * - Email
 * - Phone
 * - Domain
 * - Credit Card
 * - IP Address
 * - Wallet
 * - User Agent
 * - IBAN
 *
 * The API will return following information for each data type:
 *
 * - Email:
 *   - `valid`: Whether email is valid.
 *   - `fraud`: Whether email is considered fraudulent.
 *   - `freeSubdomain`: Whether email is a free subdomain.
 *   - `corporate`: Whether email is a corporate email.
 *   - `email`: The validated email address.
 *   - `realUser`: Whether email is a real user.
 *   - `customTLD`: Whether email has a custom top-level domain.
 *   - `domain`: The domain of email.
 *   - `roleAccount`: Whether email is a role account.
 *   - `plugins`: The plugins used to validate email.
 * - Phone:
 *   - `valid`: Whether phone number is valid.
 *   - `fraud`: Whether phone number is considered fraudulent.
 *   - `phone`: The validated phone number.
 *   - `prefix`: The prefix of phone number.
 *   - `number`: The number of phone number.
 *   - `country`: The country of phone number.
 *   - `plugins`: The plugins used to validate phone.
 * - Domain:
 *   - `valid`: Whether domain is valid.
 *   - `fraud`: Whether domain is considered fraudulent.
 *   - `domain`: The validated domain.
 *   - `plugins`: The plugins used to validate domain.
 * - Credit Card:
 *   - `valid`: Whether credit card number is valid.
 *   - `fraud`: Whether credit card is considered fraudulent.
 *   - `type`: The credit card type (e.g., "Visa", "Mastercard").
 *   - `creditCard`: The validated credit card number.
 *   - `plugins`: The plugins used to validate credit card.
 * - IP Address:
 *   - `valid`: Whether IP address is valid.
 *   - `fraud`: Whether IP address is considered fraudulent.
 *   - `ip`: The validated IP address.
 *   - `country`: The country of IP address.
 *   - `plugins`: The plugins used to validate IP address.
 * - Wallet:
 *   - `valid`: Whether wallet address is valid.
 *   - `fraud`: Whether wallet address is considered fraudulent.
 *   - `wallet`: The validated wallet address.
 *   - `type`: The wallet type (e.g., "Bitcoin", "Ethereum").
 *   - `plugins`: The plugins used to validate wallet.
 * - User Agent:
 *   - `valid`: Whether user agent is valid.
 *   - `fraud`: Whether user agent is considered fraudulent.
 *   - `userAgent`: The validated user agent string.
 *   - `bot`: Whether user agent is a bot.
 *   - `device`: Device information extracted from user agent.
 *   - `plugins`: The plugins used to validate user agent.
 * - IBAN:
 *   - `valid`: Whether IBAN is valid.
 *   - `fraud`: Whether IBAN is considered fraudulent.
 *   - `iban`: The validated IBAN.
 *   - `plugins`: The plugins used to validate IBAN.
 *
 * @param string $token The API key for authentication.
 * @param array $data The data to validate.
 *
 * @throws \Dymo\Exceptions\BadRequestError If no parameters provided.
 * @throws \Dymo\Exceptions\AuthenticationError If token is invalid.
 * @throws \Dymo\Exceptions\RateLimitException If rate limit exceeded.
 * @throws \Dymo\Exceptions\NetworkException If network error occurs.
 * @throws \Dymo\Exceptions\ServerException If server error occurs.
 *
 * @return \Dymo\Models\DataVerifierResponse The validation response object.
 */
function is_valid_data($token, $data) {
    trigger_error("Function is_valid_data() is deprecated and will be modified in future versions. Use is_valid_data_raw() instead.", E_USER_DEPRECATED);
    return is_valid_data_raw_with_client($token, $data);
}

/**
 * Validate given data using Data Verifier API (with resilience support).
 *
 * This function provides the same functionality as is_valid_data() but with
 * enhanced resilience including retry logic, rate limiting handling, and fallback support.
 *
 * @param string $token The API key for authentication.
 * @param array $data The data to validate.
 *
 * @throws \Dymo\Exceptions\BadRequestError If no parameters provided.
 * @throws \Dymo\Exceptions\AuthenticationError If token is invalid.
 * @throws \Dymo\Exceptions\RateLimitException If rate limit exceeded.
 * @throws \Dymo\Exceptions\NetworkException If network error occurs.
 * @throws \Dymo\Exceptions\ServerException If server error occurs.
 *
 * @return \Dymo\Models\DataVerifierResponse The validation response object.
 */
function is_valid_data_raw_with_client($token, $data) {
    if (!array_reduce(["url", "email", "phone", "domain", "creditCard", "ip", "wallet", "userAgent", "iban"], function ($carry, $key) use ($data) { return $carry || array_key_exists($key, $data); }, false)) {
        throw new \Dymo\Exceptions\BadRequestError("You must provide at least one parameter.");
    }

    // For backward compatibility, we'll create a temporary HTTP client
    $resilienceConfig = new \Dymo\Resilience\ResilienceConfig();
    $resilienceManager = new \Dymo\Resilience\ResilienceManager($resilienceConfig, $token);
    $httpClient = new \Dymo\Resilience\HttpClient(BASE_URL, $token, $resilienceManager);
    
    $fallbackData = FallbackDataGenerator::generateFallbackData("isValidData", $data);

    try {
        $result = $httpClient->post("/private/secure/verify", $data);
        
        // Convert to response object with backward compatibility
        $responseData = $result['data'];
        
        // Handle IP object property names that conflict with PHP keywords
        if (isset($responseData['ip']) && isset($responseData['ip']['as'])) {
            $responseData['ip']['_as'] = $responseData['ip']['as'];
            $responseData['ip']['_class'] = $responseData['ip']['class'] ?? '';
            unset($responseData['ip']['as']);
            unset($responseData['ip']['class']);
        }

        return new DataVerifierResponse($responseData);
        
    } catch (\Exception $e) {
        // If fallback is enabled, use it
        if ($resilienceConfig->fallbackEnabled) {
            return new DataVerifierResponse($fallbackData);
        }
        throw $e;
    }
}

/**
 * Wrapper for is_valid_data_raw_with_client for backward compatibility
 */
function is_valid_data_raw($token, $data) {
    return is_valid_data_raw_with_client($token, $data);
}

/**
 * Validates given email address against specified rules.
 *
 * This function validates email using the Data Verifier API and
 * returns boolean indicating whether email passes validation rules.
 *
 * The function checks for:
 * - Email format validity
 * - Fraud detection
 * - MX records (premium feature)
 * - Disposable email detection
 * - Corporate email detection
 * - Custom TLD detection
 *
 * @param string $token The API key for authentication.
 * @param string $email The email address to validate.
 * @param array $rules Validation rules. Array with "deny" key containing list of rules.
 *
 * @throws \Dymo\Exceptions\BadRequestError If email is empty.
 * @throws \Dymo\Exceptions\AuthenticationError If token is invalid.
 * @throws \Dymo\Exceptions\RateLimitException If rate limit exceeded.
 * @throws \Dymo\Exceptions\NetworkException If network error occurs.
 * @throws \Dymo\Exceptions\ServerException If server error occurs.
 *
 * @return bool True if email passes all validation rules, false otherwise.
 */
function is_valid_email($token, $email, $rules = ["deny" => ["FRAUD", "INVALID", "NO_MX_RECORDS", "NO_REPLY_EMAIL"]]) {
    if (empty($email)) throw new \Dymo\Exceptions\BadRequestError("Email cannot be empty.");

    $defaultRules = ["deny" => ["FRAUD", "INVALID", "NO_MX_RECORDS", "NO_REPLY_EMAIL"]];
    $rulesToUse = isset($rules) && is_array($rules) ? array_merge($defaultRules, $rules) : $defaultRules;

    $resilienceManager = new \Dymo\Resilience\ResilienceManager(new \Dymo\Resilience\ResilienceConfig(), $token);
    $httpClient = new \Dymo\Resilience\HttpClient(BASE_URL, $token, $resilienceManager);
    
    $fallbackData = FallbackDataGenerator::generateFallbackData("isValidEmail", $email);

    try {
        $result = $httpClient->post("/private/secure/verify", [
            'email' => $email,
            'plugins' => getEmailPlugins($rulesToUse)
        ]);
        
        $response = $result['data'];
        return $response['allow'];
        
    } catch (\Exception $e) {
        if ($resilienceManager->getConfig()->fallbackEnabled) {
            return $fallbackData['allow'];
        }
        throw $e;
    }
}

/**
 * Validates given IP address against specified rules.
 *
 * This function validates IP using the Data Verifier API and
 * returns boolean indicating whether IP passes validation rules.
 *
 * The function checks for:
 * - IP format validity (IPv4/IPv6)
 * - Fraud detection
 * - Tor network detection (premium feature)
 * - Country-based blocking
 * - Proxy/VPN detection
 *
 * @param string $token The API key for authentication.
 * @param string $ip The IP address to validate.
 * @param array $rules Validation rules. Array with "deny" key containing list of rules.
 *
 * @throws \Dymo\Exceptions\BadRequestError If IP is empty.
 * @throws \Dymo\Exceptions\AuthenticationError If token is invalid.
 * @throws \Dymo\Exceptions\RateLimitException If rate limit exceeded.
 * @throws \Dymo\Exceptions\NetworkException If network error occurs.
 * @throws \Dymo\Exceptions\ServerException If server error occurs.
 *
 * @return bool True if IP passes all validation rules, false otherwise.
 */
function is_valid_ip($token, $ip, $rules = ["deny" => ["FRAUD", "INVALID", "TOR_NETWORK"]]) {
    if (empty($ip)) throw new \Dymo\Exceptions\BadRequestError("IP cannot be empty.");

    $defaultRules = ["deny" => ["FRAUD", "INVALID", "TOR_NETWORK"]];
    $rulesToUse = isset($rules) && is_array($rules) ? array_merge($defaultRules, $rules) : $defaultRules;

    $resilienceManager = new \Dymo\Resilience\ResilienceManager(new \Dymo\Resilience\ResilienceConfig(), $token);
    $httpClient = new \Dymo\Resilience\HttpClient(BASE_URL, $token, $resilienceManager);
    
    $fallbackData = FallbackDataGenerator::generateFallbackData("isValidIP", $ip);

    try {
        $result = $httpClient->post("/private/secure/verify", ['ip' => $ip]);
        $response = $result['data'];
        return $response['allow'];
        
    } catch (\Exception $e) {
        if ($resilienceManager->getConfig()->fallbackEnabled) {
            return $fallbackData['allow'];
        }
        throw $e;
    }
}

/**
 * Validates given phone number against specified rules.
 *
 * This function validates phone using the Data Verifier API and
 * returns boolean indicating whether phone passes validation rules.
 *
 * The function checks for:
 * - Phone format validity (international format)
 * - Fraud detection
 * - Risk scoring (premium feature)
 * - Carrier validation
 * - Country validation
 *
 * @param string $token The API key for authentication.
 * @param string $phone The phone number to validate.
 * @param array $rules Validation rules. Array with "deny" key containing list of rules.
 *
 * @throws \Dymo\Exceptions\BadRequestError If phone is empty.
 * @throws \Dymo\Exceptions\AuthenticationError If token is invalid.
 * @throws \Dymo\Exceptions\RateLimitException If rate limit exceeded.
 * @throws \Dymo\Exceptions\NetworkException If network error occurs.
 * @throws \Dymo\Exceptions\ServerException If server error occurs.
 *
 * @return bool True if phone passes all validation rules, false otherwise.
 */
function is_valid_phone($token, $phone, $rules = ["deny" => ["FRAUD", "INVALID"]]) {
    if (empty($phone)) throw new \Dymo\Exceptions\BadRequestError("Phone cannot be empty.");

    $defaultRules = ["deny" => ["FRAUD", "INVALID"]];
    $rulesToUse = isset($rules) && is_array($rules) ? array_merge($defaultRules, $rules) : $defaultRules;

    $resilienceManager = new \Dymo\Resilience\ResilienceManager(new \Dymo\Resilience\ResilienceConfig(), $token);
    $httpClient = new \Dymo\Resilience\HttpClient(BASE_URL, $token, $resilienceManager);
    
    $fallbackData = FallbackDataGenerator::generateFallbackData("isValidPhone", $phone);

    try {
        $result = $httpClient->post("/private/secure/verify", ['phone' => $phone]);
        $response = $result['data'];
        return $response['allow'];
        
    } catch (\Exception $e) {
        if ($resilienceManager->getConfig()->fallbackEnabled) {
            return $fallbackData['allow'];
        }
        throw $e;
    }
}

/**
 * Send email using Dymo API with resilience support.
 *
 * This function sends email through Dymo API using provided configuration.
 * Supports both HTML and React email content.
 *
 * @param string $token The API key for authentication.
 * @param array $data Email configuration including 'from', 'to', 'subject', and content.
 *
 * @throws \Dymo\Exceptions\BadRequestError If required fields are missing.
 * @throws \Dymo\Exceptions\AuthenticationError If token is invalid.
 * @throws \Dymo\Exceptions\RateLimitException If rate limit exceeded.
 * @throws \Dymo\Exceptions\NetworkException If network error occurs.
 * @throws \Dymo\Exceptions\ServerException If server error occurs.
 *
 * @return \Dymo\Models\SendEmailResponse Email sending status and details.
 */
function send_email($token, $data) {
    $required = ['from', 'to', 'subject'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new \Dymo\Exceptions\BadRequestError("Field '{$field}' is required.");
        }
    }

    $resilienceManager = new \Dymo\Resilience\ResilienceManager(new \Dymo\Resilience\ResilienceConfig(), $token);
    $httpClient = new \Dymo\Resilience\HttpClient(BASE_URL, $token, $resilienceManager);
    
    $fallbackData = FallbackDataGenerator::generateFallbackData("sendEmail", $data);

    try {
        $result = $httpClient->post("/private/sender/sendEmail", $data);
        return new SendEmailResponse($result['data']);
        
    } catch (\Exception $e) {
        if ($resilienceManager->getConfig()->fallbackEnabled) {
            return new SendEmailResponse($fallbackData);
        }
        throw $e;
    }
}

/**
 * Generate secure random numbers using Dymo API with resilience support.
 *
 * @param string $token The API key for authentication.
 * @param array $data Random number generation parameters.
 *
 * @throws \Dymo\Exceptions\BadRequestError If parameters are invalid.
 * @throws \Dymo\Exceptions\AuthenticationError If token is invalid.
 * @throws \Dymo\Exceptions\RateLimitException If rate limit exceeded.
 * @throws \Dymo\Exceptions\NetworkException If network error occurs.
 * @throws \Dymo\Exceptions\ServerException If server error occurs.
 *
 * @return \Dymo\Models\SRNGResponse Generated random numbers and execution time.
 */
function get_random($token, $data) {
    if (empty($data['min']) || empty($data['max'])) {
        throw new \Dymo\Exceptions\BadRequestError("Both 'min' and 'max' parameters are required.");
    }

    $resilienceManager = new \Dymo\Resilience\ResilienceManager(new \Dymo\Resilience\ResilienceConfig(), $token);
    $httpClient = new \Dymo\Resilience\HttpClient(BASE_URL, $token, $resilienceManager);
    
    $fallbackData = FallbackDataGenerator::generateFallbackData("getRandom", $data);

    try {
        $result = $httpClient->post("/private/srng", $data);
        return new SRNGResponse($result['data']);
        
    } catch (\Exception $e) {
        if ($resilienceManager->getConfig()->fallbackEnabled) {
            return new SRNGResponse($fallbackData);
        }
        throw $e;
    }
}

/**
 * Helper function to determine email plugins based on rules
 */
function getEmailPlugins($rules): array {
    return [
        in_array("NO_MX_RECORDS", $rules['deny']) ? "mxRecords" : null,
        in_array("NO_REACHABLE", $rules['deny']) ? "reachable" : null,
        in_array("HIGH_RISK_SCORE", $rules['deny']) ? "riskScore" : null,
        in_array("NO_GRAVATAR", $rules['deny']) ? "gravatar" : null
    ];
}

?>