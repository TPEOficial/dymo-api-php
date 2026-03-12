<?php

namespace Dymo\Resilience;

use Dymo\Models\{DataVerifierResponse, EmailValidatorResponse, IPValidatorResponse, PhoneValidatorResponse};

/**
 * Generates fallback data when API is unavailable
 */
class FallbackDataGenerator {
    
    /**
     * Generate fallback data for different API endpoints
     */
    public static function generateFallbackData(string $method, $inputData = null): array {
        switch ($method) {
            case 'isValidData':
            case 'isValidDataRaw':
                return self::generateDataValidationAnalysis($inputData);
            case 'isValidEmail':
                return self::generateEmailValidatorResponse($inputData);
            case 'isValidIP':
                return self::generateIPValidatorResponse($inputData);
            case 'isValidPhone':
                return self::generatePhoneValidatorResponse($inputData);
            case 'sendEmail':
                return self::generateEmailStatus();
            case 'getRandom':
                return self::generateSRNGSummary($inputData);
            case 'getPrayerTimes':
                return self::generatePrayerTimes($inputData);
            case 'satinize':
            case 'satinizer':
                return self::generateSatinizedInputAnalysis($inputData);
            case 'isValidPwd':
                return self::generatePasswordValidationResult($inputData);
            default:
                throw new \InvalidArgumentException("Unknown method for fallback: {$method}");
        }
    }

    /**
     * Validate URL using regex
     */
    private static function validateUrl(?string $url): bool {
        if (!$url) return false;
        $urlRegex = '/^https?:\/\/(?:[-\w.])+(?:\:[0-9]+)?(?:\/(?:[\w\/_.])*(?:\?(?:[\w&=%.])*)?(?:\#(?:[\w.])*)?)?$/';
        return preg_match($urlRegex, $url) === 1;
    }

    /**
     * Validate email using regex
     */
    private static function validateEmail(?string $email): bool {
        if (!$email) return false;
        $emailRegex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
        return preg_match($emailRegex, $email) === 1;
    }

    /**
     * Validate domain with TLD validation
     */
    private static function validateDomain(?string $domain): bool {
        if (!$domain) return false;
        $domainRegex = '/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](?:\.[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9])*$/';
        if (preg_match($domainRegex, $domain) !== 1) return false;
        
        // Validate that it has a TLD
        $parts = explode('.', $domain);
        return count($parts) >= 2 && strlen(end($parts)) > 0;
    }

    /**
     * Validate credit card using Luhn algorithm
     */
    private static function validateCreditCard($creditCard): bool {
        if (!$creditCard) return false;
        
        $cardNumber = is_string($creditCard) ? $creditCard : ($creditCard['pan'] ?? '');
        if (!$cardNumber) return false;
            
        // Basic regex validation
        $cardRegex = '/^\d{13,19}$/';
        if (preg_match($cardRegex, str_replace(' ', '', $cardNumber)) !== 1) return false;
        
        // Luhn algorithm
        $digits = str_split(str_replace(' ', '', $cardNumber));
        $total = 0;
        $isEven = false;
        
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $digit = (int)$digits[$i];
            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) $digit -= 9;
            }
            $total += $digit;
            $isEven = !$isEven;
        }
            
        return $total % 10 === 0;
    }

    /**
     * Validate IPv4 and IPv6 addresses
     */
    private static function validateIP(?string $ip): bool {
        if (!$ip) return false;
        
        // IPv4 regex
        $ipv4Regex = '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';
        // IPv6 regex (simplified)
        $ipv6Regex = '/^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/';
        
        return preg_match($ipv4Regex, $ip) === 1 || preg_match($ipv6Regex, $ip) === 1;
    }

    /**
     * Validate international phone numbers
     */
    private static function validatePhone($phone): bool {
        if (!$phone) return false;
        
        $phoneNumber = is_string($phone) ? $phone : ($phone['phone'] ?? '');
        if (!$phoneNumber) return false;
            
        $phoneRegex = '/^\+?[1-9]\d{1,14}$/';
        return preg_match($phoneRegex, preg_replace('/[^\d+]/', '', $phoneNumber)) === 1;
    }

    /**
     * Validate Bitcoin and Ethereum addresses
     */
    private static function validateWallet(?string $wallet): bool {
        if (!$wallet) return false;
        
        // Bitcoin address regex
        $bitcoinRegex = '/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/';
        // Ethereum address regex
        $ethereumRegex = '/^0x[a-fA-F0-9]{40}$/';
        
        return preg_match($bitcoinRegex, $wallet) === 1 || preg_match($ethereumRegex, $wallet) === 1;
    }

    /**
     * Validate IBAN using regex
     */
    private static function validateIBAN(?string $iban): bool {
        if (!$iban) return false;
        
        $ibanRegex = '/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/';
        return preg_match($ibanRegex, str_replace(' ', '', strtoupper($iban))) === 1;
    }

    /**
     * Extract domain from URL
     */
    private static function extractDomain(?string $url): string {
        if (!$url) return "";
        $parts = parse_url($url);
        return $parts['host'] ?? "";
    }

    /**
     * Generate fallback data for data validation analysis
     */
    private static function generateDataValidationAnalysis($inputData): array {
        $url = $inputData['url'] ?? null;
        $domain = $inputData['domain'] ?? null;
        $creditCard = $inputData['creditCard'] ?? null;
        $ip = $inputData['ip'] ?? null;
        $wallet = $inputData['wallet'] ?? null;
        $userAgent = $inputData['userAgent'] ?? null;
        $iban = $inputData['iban'] ?? null;
        
        return [
            "url" => [
                "valid" => self::validateUrl($url),
                "fraud" => false,
                "freeSubdomain" => false,
                "customTLD" => false,
                "url" => $url ?? "",
                "domain" => self::extractDomain($url),
                "plugins" => self::generateDefaultPlugins()
            ],
            "email" => self::generateEmailDataAnalysis($inputData['email'] ?? null),
            "phone" => self::generatePhoneDataAnalysis($inputData['phone'] ?? null),
            "domain" => [
                "valid" => self::validateDomain($domain),
                "fraud" => false,
                "freeSubdomain" => false,
                "customTLD" => false,
                "domain" => $domain ?? "",
                "plugins" => self::generateDefaultPlugins()
            ],
            "creditCard" => [
                "valid" => self::validateCreditCard($creditCard),
                "fraud" => false,
                "test" => false,
                "type" => "unknown",
                "creditCard" => is_string($creditCard) ? $creditCard : ($creditCard['pan'] ?? ''),
                "plugins" => ["blocklist" => false, "riskScore" => 0]
            ],
            "ip" => self::generateIPDataAnalysis($ip),
            "wallet" => [
                "valid" => self::validateWallet($wallet),
                "fraud" => false,
                "wallet" => $wallet ?? "",
                "type" => "unknown",
                "plugins" => ["blocklist" => false, "riskScore" => 0, "torNetwork" => false]
            ],
            "userAgent" => [
                "valid" => true,
                "fraud" => false,
                "userAgent" => $userAgent ?? "",
                "bot" => true,
                "device" => ["type" => "unknown", "brand" => "unknown"],
                "plugins" => ["blocklist" => false, "riskScore" => 0]
            ],
            "iban" => [
                "valid" => self::validateIBAN($iban),
                "fraud" => false,
                "iban" => $iban ?? "",
                "plugins" => ["blocklist" => false, "riskScore" => 0]
            ]
        ];
    }

    private static function generateEmailDataAnalysis($email): array {
        return [
            "valid" => self::validateEmail($email),
            "fraud" => false,
            "proxiedEmail" => false,
            "freeSubdomain" => false,
            "corporate" => false,
            "email" => $email ?? "",
            "realUser" => "",
            "didYouMean" => null,
            "noReply" => false,
            "customTLD" => false,
            "domain" => "",
            "roleAccount" => false,
            "plugins" => self::generateEmailPlugins()
        ];
    }

    private static function generatePhoneDataAnalysis($phone): array {
        $phoneNumber = is_string($phone) ? $phone : ($phone['phone'] ?? '');
        $isValid = self::validatePhone($phone);
        
        return [
            "valid" => $isValid,
            "fraud" => false,
            "phone" => $phoneNumber,
            "prefix" => "",
            "number" => "",
            "lineType" => "Unknown",
            "carrierInfo" => [
                "carrierName" => "",
                "accuracy" => 0,
                "carrierCountry" => "",
                "carrierCountryCode" => ""
            ],
            "country" => "",
            "countryCode" => "",
            "plugins" => ["blocklist" => false, "riskScore" => 0, "spam" => false]
        ];
    }

    private static function generateIPDataAnalysis($ip): array {
        $isValid = self::validateIP($ip);
        
        return [
            "valid" => $isValid,
            "type" => $isValid ? "IPv4" : "Invalid",
            "class" => $isValid ? "A" : "Unknown",
            "fraud" => false,
            "ip" => $ip ?? "",
            "continent" => "",
            "continentCode" => "",
            "country" => "",
            "countryCode" => "",
            "region" => "",
            "regionName" => "",
            "city" => "",
            "district" => "",
            "zipCode" => "",
            "lat" => 0,
            "lon" => 0,
            "timezone" => "",
            "offset" => 0,
            "currency" => "",
            "isp" => "",
            "org" => "",
            "as" => "",
            "asname" => "",
            "mobile" => false,
            "vpn" => false,
            "proxy" => false,
            "hosting" => false,
            "plugins" => ["blocklist" => false, "riskScore" => 0, "spam" => false]
        ];
    }

    private static function generateEmailValidatorResponse($email): array {
        return [
            "email" => $email ?? '',
            "allow" => self::validateEmail($email),
            "reasons" => self::validateEmail($email) ? [] : ["INVALID"],
            "response" => self::generateEmailDataAnalysis($email)
        ];
    }

    private static function generateIPValidatorResponse($ip): array {
        return [
            "ip" => $ip ?? '',
            "allow" => self::validateIP($ip),
            "reasons" => self::validateIP($ip) ? [] : ["INVALID"],
            "response" => self::generateIPDataAnalysis($ip)
        ];
    }

    private static function generatePhoneValidatorResponse($phone): array {
        $phoneNumber = is_string($phone) ? $phone : ($phone['phone'] ?? '');
        
        return [
            "phone" => $phoneNumber,
            "allow" => self::validatePhone($phone),
            "reasons" => self::validatePhone($phone) ? [] : ["INVALID"],
            "response" => self::generatePhoneDataAnalysis($phone)
        ];
    }

    private static function generateEmailStatus(): array {
        return [
            "status" => false,
            "error" => "API unavailable - using fallback response"
        ];
    }

    private static function generateSRNGSummary($inputData): array {
        $quantity = $inputData['quantity'] ?? 1;
        $values = [];
        for ($i = 0; $i < $quantity; $i++) {
            $values[] = ["integer" => 0, "float" => 0.0];
        }
        
        return [
            "values" => $values,
            "executionTime" => 0
        ];
    }

    private static function generatePrayerTimes($inputData): array {
        return [
            "error" => "API unavailable - using fallback response"
        ];
    }

    private static function generateSatinizedInputAnalysis($inputData): array {
        return [
            "input" => $inputData['input'] ?? '',
            "formats" => [
                "ascii" => false,
                "bitcoinAddress" => false,
                "cLikeIdentifier" => false,
                "coordinates" => false,
                "crediCard" => false,
                "date" => false,
                "discordUsername" => false,
                "doi" => false,
                "domain" => false,
                "e164Phone" => false,
                "email" => false,
                "emoji" => false,
                "hanUnification" => false,
                "hashtag" => false,
                "hyphenWordBreak" => false,
                "ipv6" => false,
                "ip" => false,
                "jiraTicket" => false,
                "macAddress" => false,
                "name" => false,
                "number" => false,
                "panFromGstin" => false,
                "password" => false,
                "port" => false,
                "tel" => false,
                "text" => false,
                "semver" => false,
                "ssn" => false,
                "uuid" => false,
                "url" => false,
                "urlSlug" => false,
                "username" => false
            ],
            "includes" => [
                "spaces" => false,
                "hasSql" => false,
                "hasNoSql" => false,
                "letters" => false,
                "uppercase" => false,
                "lowercase" => false,
                "symbols" => false,
                "digits" => false
            ]
        ];
    }

    private static function generatePasswordValidationResult($inputData): array {
        return [
            "valid" => false,
            "password" => $inputData['password'] ?? '',
            "details" => [
                [
                    "validation" => "length",
                    "message" => "API unavailable - using fallback response"
                ]
            ]
        ];
    }

    private static function generateDefaultPlugins(): array {
        return [
            "blocklist" => false,
            "compromiseDetector" => false,
            "mxRecords" => [],
            "nsfw" => false,
            "reputation" => "unknown",
            "riskScore" => 0,
            "spam" => false,
            "torNetwork" => false,
            "typosquatting" => 0,
            "urlShortener" => false
        ];
    }

    private static function generateEmailPlugins(): array {
        return [
            "blocklist" => false,
            "compromiseDetector" => false,
            "mxRecords" => [],
            "nsfw" => false,
            "reputation" => "unknown",
            "riskScore" => 0,
            "socialFootprint" => [
                "totalChecked" => 0,
                "platforms" => []
            ],
            "spam" => false,
            "torNetwork" => false,
            "typosquatting" => 0,
            "urlShortener" => false,
            "gravatarUrl" => null
        ];
    }
}