<?php

use Dymo\Models\PrayerTimesByTimezone;
use Dymo\Models\DataVerifierResponse;
use Dymo\Models\PrayerTimesResponse;
use Dymo\Models\IsValidPwdResponse;
use Dymo\Models\InputSanitizerData;
use Dymo\Models\DataVerifierDomain;
use Dymo\Models\UrlEncryptResponse;
use Dymo\Models\DataVerifierPhone;
use Dymo\Models\DataVerifierEmail;
use Dymo\Models\SatinizerIncludes;
use Dymo\Models\IsValidPwdDetails;
use Dymo\Models\SendEmailResponse;
use Dymo\Models\SatinizerResponse;
use Dymo\Models\SatinizerFormats;
use Dymo\Models\PrayerTimesData;
use Dymo\Models\IsValidPwdData;
use Dymo\Models\SRNGResponse;
use Dymo\Models\PrayerTimes;
use Dymo\Models\Validator;
use Dymo\Models\SendEmail;
use Dymo\Models\SRNG;

require_once "exceptions.php";
require_once "response_models.php";

class DymoAPI {
    private $organization;
    private $rootApiKey;
    private $apiKey;
    private $serverEmailConfig;
    private $baseUrl;
    private $errorLogRoute = "./error.log";

    const BASE_URL = "https://api.tpeoficial.com";

    /**
     * Construct a new instance of the Dymo API client.
     *
     * @param array $config The configuration array for the client.
     * @param string $config["organization"] The organization name.
     * @param string $config["root_api_key"] The root API key.
     * @param string $config["api_key"] The API key.
     * @param array $config["server_email_config"] The server email configuration.
     * @param string $config["base_url"] Whether to use the local development server.
     */
    public function __construct($config = []) {
        $this->organization = $config["organization"] ?? null;
        $this->rootApiKey = $config["root_api_key"] ?? null;
        $this->apiKey = $config["api_key"] ?? null;
        $this->serverEmailConfig = $config["server_email_config"] ?? null;
        $this->baseUrl = $config["base_url"] ?? "https://api.tpeoficial.com";

        $this->setBaseUrl($this->baseUrl);
    }

    /**
     * Set the base URL for the Dymo API.
     *
     * If the "baseUrl" parameter is set to true, the base URL will be set to
     * "http://localhost:3050". Otherwise, the base URL will be set to the default
     * value of "https://api.tpeoficial.com".
     *
     * @param string $baseUrl Whether the base URL should be set to the local
     *                    development server.
     */
    private function setBaseUrl(string $baseUrl): void {
        if (preg_match("/^(https:\/\/api\.tpeoficial\.com$|http:\/\/(localhost:\d+|dymoapi:\d+))$/", $baseUrl)) {
            global $BASE_URL;
            $BASE_URL = $baseUrl;
        } else throw new InvalidArgumentException("[Dymo API] Invalid URL. It must be https://api.tpeoficial.com or start with http://localhost or http://dymoapi followed by a port.");
    }

    /**
     * Retrieves a function name from the specified module.
     *
     * This function checks if the specified module exists and is accessible.
     * If the module file does not exist, an exception is thrown.
     * 
     * @param string $moduleName The name of the module to retrieve the function from.
     * @param string $functionName The name of the function to retrieve. Defaults to "main".
     * @return string The name of the function.
     * @throws Exception If the module file cannot be found.
     */
    private function getFunction($moduleName, $functionName = "main"): string {
        if ($moduleName === "private" && $this->apiKey === null && $this->rootApiKey === null) return error_log("Invalid private token.\n", 3, $this->errorLogRoute);
        $modulePath = __DIR__ . "/branches/" . $moduleName . ".php";
        if (!file_exists($modulePath)) throw new Exception("Module not found: " . $modulePath);
        require_once $modulePath;
        return $functionName;
    }

    /**
     * Perform a POST request to the Dymo API.
     *
     * @param string $endpoint The endpoint to call.
     * @param array $data The data to send in the request body.
     * @return array|null The response from the API, decoded from JSON.
     */
    private function postRequest($endpoint, $data): array|null {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Validate the given data using the Data Verifier API.
     *
     * This function checks the validity of the given data and returns an
     * `DataVerifierResponse` object containing the validation status, the
     * validated data, and detailed information about each validation check.
     *
     * The API will check the following data types:
     *
     * - URL
     * - Email
     * - Phone
     * - Domain
     * - Credit Card
     * - IP Address
     * - Wallet
     *
     * The API will return the following information for each data type:
     * - URL:
     *   - `valid`: Whether the domain is valid.
     *   - `fraud`: Whether the domain is considered fraudulent.
     *   - `freeSubdomain`: Whether the domain is a free subdomain.
     *   - `customTLD`: Whether the domain has a custom top-level domain.
     *   - `url`: The validated URL.
     *   - `domain`: The validated domain.
     *   - `plugins`: The plugins used to validate the domain.
     * - Email:
     *   - `valid`: Whether the email is valid.
     *   - `fraud`: Whether the email is considered fraudulent.
     *   - `freeSubdomain`: Whether the email is a free subdomain.
     *   - `corporate`: Whether the email is a corporate email.
     *   - `email`: The validated email address.
     *   - `realUser`: Whether the email is a real user.
     *   - `customTLD`: Whether the email has a custom top-level domain.
     *   - `domain`: The domain of the email.
     *   - `roleAccount`: Whether the email is a role account.
     *   - `plugins`: The plugins used to validate the email.
     * - Phone:
     *   - `valid`: Whether the phone number is valid.
     *   - `fraud`: Whether the phone number is considered fraudulent.
     *   - `phone`: The validated phone number.
     *   - `prefix`: The prefix of the phone number.
     *   - `number`: The number of the phone number.
     *   - `country`: The country of the phone number.
     *   - `plugins`: The plugins used to validate the phone number.
     * - Domain:
     *   - `valid`: Whether the domain is valid.
     *   - `fraud`: Whether the domain is considered fraudulent.
     *   - `freeSubdomain`: Whether the domain is a free subdomain.
     *   - `customTLD`: Whether the domain has a custom top-level domain.
     *   - `domain`: The validated domain.
     *   - `plugins`: The plugins used to validate the domain.
     * - Credit Card:
     *   - `valid`: Whether the credit card is valid.
     *   - `fraud`: Whether the credit card is considered fraudulent.
     *   - `creditCard`: The validated credit card number.
     *   - `plugins`: The plugins used to validate the credit card.
     * - IP Address:
     *   - `valid`: Whether the IP address is valid.
     *   - `fraud`: Whether the IP address is considered fraudulent.
     *   - `ip`: The validated IP address.
     *   - `plugins`: The plugins used to validate the IP address.
     * - Wallet:
     *   - `valid`: Whether the wallet is valid.
     *   - `fraud`: Whether the wallet is considered fraudulent.
     *   - `wallet`: The validated wallet address.
     *   - `plugins`: The plugins used to validate the wallet.
     *
     * @param string $token The API key to validate the data.
     * @param Validator $data The data to validate.
     * @return DataVerifierResponse The response from the API with validation details.
     * 
     * [Documentation](https://docs.tpeoficial.com/docs/dymo-api/private/data-verifier)
     */
    public function isValidData(Validator $data): DataVerifierResponse {
        $response = $this->getFunction("private", "is_valid_data")($data);
        if (isset($response["ip"]["as"])) {
            $response["ip"]["_as"] = $response["ip"]["as"];
            $response["ip"]["_class"] = $response["ip"]["class"];
            unset($response["ip"]["as"]);
            unset($response["ip"]["class"]);
        }
        return new DataVerifierResponse(
            new DataVerifierURL(
                $response["url"]["valid"] ?? null,
                $response["url"]["fraud"] ?? null,
                $response["url"]["freeSubdomain"] ?? null,
                $response["url"]["customTLD"] ?? null,
                $response["url"]["url"] ?? 
                $response["url"]["domain"] ?? null,
                $response["url"]["plugins"] ?? null
            ),
            new DataVerifierEmail(
                $response["email"]["valid"] ?? null,
                $response["email"]["fraud"] ?? null,
                $response["email"]["freeSubdomain"] ?? null,
                $response["email"]["corporate"] ?? null,
                $response["email"]["email"] ?? null,
                $response["email"]["realUser"] ?? null,
                $response["email"]["customTLD"] ?? null,
                $response["email"]["domain"] ?? null,
                $response["email"]["roleAccount"] ?? null,
                $response["email"]["plugins"] ?? null
            ),
            new DataVerifierPhone(
                $response["phone"]["valid"] ?? null,
                $response["phone"]["fraud"] ?? null,
                $response["phone"]["phone"] ?? null,
                $response["phone"]["prefix"] ?? null,
                $response["phone"]["number"] ?? null,
                $response["phone"]["country"] ?? null,
                $response["phone"]["plugins"] ?? null
            ),
            new DataVerifierDomain(
                $response["domain"]["valid"] ?? null,
                $response["domain"]["fraud"] ?? null,
                $response["domain"]["freeSubdomain"] ?? null,
                $response["domain"]["customTLD"] ?? null,
                $response["domain"]["domain"] ?? null,
                $response["domain"]["plugins"] ?? null
            )
        );
    }

    /**
     * Validates an email using the Data Verifier API.
     *
     * This function is a wrapper around the internal API function `is_valid_email`.
     * It simply calls the internal function with the given email and returns
     * the result. All validation logic is handled by the internal function.
     *
     * Deny rules (some are PREMIUM ⚠️):
     * - "FRAUD", "INVALID", "NO_MX_RECORDS" ⚠️, "PROXIED_EMAIL" ⚠️, "FREE_SUBDOMAIN" ⚠️,
     *   "PERSONAL_EMAIL", "CORPORATE_EMAIL", "NO_REPLY_EMAIL", "ROLE_ACCOUNT",
     *   "NO_REACHABLE" ⚠️, "HIGH_RISK_SCORE" ⚠️
     *
     * @param string $email The email address to validate.
     * @param array|null $rules Optional array of deny rules. If not provided, defaults to:
     *                          ["FRAUD", "INVALID", "NO_MX_RECORDS", "NO_REPLY_EMAIL"].
     *
     * @return bool True if the email passes validation, false otherwise.
     *
     * @throws APIException If the token is missing, invalid, or the validation request fails.
     *
     * @example
     * $valid = $client->isValidEmail("user@example.com");
     * $validWithRules = $client->isValidEmail("user@example.com", ["FRAUD", "NO_MX_RECORDS"]);
     *
     * @see https://docs.tpeoficial.com/docs/dymo-api/private/data-verifier
     */
    public function isValidEmail(string $email, ?array $rules = null): bool
    {
        return $this->getFunction("private", "is_valid_email")($email, $rules);
    }

    /**
     * Send an email using the Dymo API.
     *
     * @param SendEmail $data The data to send to the API.
     * @return SendEmailResponse The response from the API.
     * 
     * [Documentation](https://docs.tpeoficial.com/docs/dymo-api/private/sender-send-email/getting-started)
     */
    public function sendEmail(SendEmail $data): SendEmailResponse {
        if (!$this->serverEmailConfig && !$this->rootApiKey) return error_log("You must configure the email client settings.\n", 3, );
        $responseData = $this->getFunction("private", "send_email")(array_merge($data, ["serverEmailConfig" => $this->serverEmailConfig]));

        return new SendEmailResponse(
            $responseData["status"],
            $responseData["error"]
        );
    }

    /**
     * Get a list of cryptographically secure random numbers from the Dymo API.
     *
     * @param SRNG $data The data to send to the API.
     * @return SRNGResponse The response from the API.
     * 
     * [Documentation](https://docs.tpeoficial.com/docs/dymo-api/private/secure-random-number-generator)
     */
    public function getRandom(SRNG $data): SRNGResponse {
        $responseData = $this->getFunction("private", "get_random")(array_merge($data));
        return new SRNGResponse(
            $responseData["values"],
            $responseData["executionTime"]
        );
    }

    /**
     * Extract structured data from plain text using the Textly extraction endpoint.
     *
     * @param Textly $data The input data to send to the API. It should include:
     *                     - 'data': A string of raw text.
     *                     - 'format': An associative array representing the schema to extract.
     * @return ExtractWithTextlyResponse The structured data extracted from the text.
     *
     * @throws Exception If the request fails or the API returns an error.
     *
     * [Documentation](https://docs.tpeoficial.com/docs/dymo-api/private/extract-textly)
     */
    public function extractWithTextly(Textly $data): ExtractWithTextly {
        $responseData = $this->getFunction("private", "extract_with_textly")(array_merge($data));
        return new ExtractWithTextly($responseData);
    }

    /**
     * Get prayer times for a given country and timezone.
     *
     * @param PrayerTimesData $data The data to send to the API.
     * @return PrayerTimesResponse The response from the API.
     * 
     * [Documentation](https://docs.tpeoficial.com/docs/dymo-api/public/prayertimes)
     */
    public function getPrayerTimes(PrayerTimesData $data): PrayerTimesResponse {
        $responseData = $this->getFunction("public", "get_prayer_times")($data);
        $prayerTimesByTimezone = [];
        foreach ($responseData["prayerTimesByTimezone"] as $timezone => $prayerTimes) {
            $prayerTimesByTimezone[] = new PrayerTimesByTimezone(
                $timezone,
                new PrayerTimes(
                    $prayerTimes["coordinates"],
                    $prayerTimes["date"],
                    $prayerTimes["calculationParameters"],
                    $prayerTimes["fajr"],
                    $prayerTimes["sunrise"],
                    $prayerTimes["dhuhr"],
                    $prayerTimes["asr"],
                    $prayerTimes["sunset"],
                    $prayerTimes["maghrib"],
                    $prayerTimes["isha"]
                )
            );
        }
        return new PrayerTimesResponse(
            $responseData["country"],
            $prayerTimesByTimezone
        );
    }

    /**
     * Sanitize input data and return various format representations.
     *
     * This function processes the input data using the satinizer API and returns
     * a `SatinizerResponse` object containing the sanitized input and various
     * format representations. The response includes format details such as ASCII,
     * Bitcoin address, C-like identifier, coordinates, credit card, date, Discord
     * username, DOI, domain, E.164 phone number, email, emoji, Han unification,
     * hashtag, hyphen word break, IPv6, IP, JIRA ticket, MAC address, name,
     * number, PAN from GSTIN, password, port, telephone, text, and semantic version.
     *
     * Additionally, the response includes information on the presence of spaces,
     * SQL and NoSQL keywords, letters, uppercase and lowercase letters, symbols,
     * and digits in the input data.
     *
     * @param InputSanitizerData $data The data to sanitize.
     * @return SatinizerResponse The response containing sanitized data and formats.
     * 
     * [Documentation](https://docs.tpeoficial.com/docs/dymo-api/public/input-satinizer)
     */
    public function satinizer(InputSanitizerData $data): SatinizerResponse {
        $responseData = $this->getFunction("public", "satinizer")($data);
        return new SatinizerResponse(
            $responseData["input"],
            new SatinizerFormats(
                $responseData["formats"]["ascii"],
                $responseData["formats"]["bitcoinAddress"],
                $responseData["formats"]["cLikeIdentifier"],
                $responseData["formats"]["coordinates"],
                $responseData["formats"]["crediCard"],
                $responseData["formats"]["date"],
                $responseData["formats"]["discordUsername"],
                $responseData["formats"]["doi"],
                $responseData["formats"]["domain"],
                $responseData["formats"]["e164Phone"],
                $responseData["formats"]["email"],
                $responseData["formats"]["emoji"],
                $responseData["formats"]["hanUnification"],
                $responseData["formats"]["hashtag"],
                $responseData["formats"]["hyphenWordBreak"],
                $responseData["formats"]["ipv6"],
                $responseData["formats"]["ip"],
                $responseData["formats"]["jiraTicket"],
                $responseData["formats"]["macAddress"],
                $responseData["formats"]["name"],
                $responseData["formats"]["number"],
                $responseData["formats"]["panFromGstin"],
                $responseData["formats"]["password"],
                $responseData["formats"]["port"],
                $responseData["formats"]["tel"],
                $responseData["formats"]["text"],
                $responseData["formats"]["semver"]
            ),
            new SatinizerIncludes(
                $responseData["includes"]["spaces"],
                $responseData["includes"]["hasSql"],
                $responseData["includes"]["hasNoSql"],
                $responseData["includes"]["letters"],
                $responseData["includes"]["uppercase"],
                $responseData["includes"]["lowercase"],
                $responseData["includes"]["symbols"],
                $responseData["includes"]["digits"]
            )
        );
    }

    /**
     * Validate the given password using the Dymo API.
     *
     * This function checks the validity of a password and returns an
     * `IsValidPwdResponse` object containing the validation status, the
     * password, and detailed information about each validation check.
     *
     * @param IsValidPwdData $data The data containing the password to validate.
     * @return IsValidPwdResponse The response from the API with validation details.
     * 
     * [Documentation](https://docs.tpeoficial.com/docs/dymo-api/public/password-validator)
     */
    public function isValidPwd(IsValidPwdData $data): IsValidPwdResponse {
        $responseData = $this->getFunction("public", "is_valid_pwd")($data);
        $details = [];
        foreach ($responseData["details"] as $detail) {
            $details[] = new IsValidPwdDetails(
                $detail["validation"],
                $detail["message"]
            );
        }
        return new IsValidPwdResponse(
            $responseData["valid"],
            $responseData["password"],
            $details
        );
    }

    /**
     * Encrypt a given URL using the Dymo API.
     *
     * This function takes a URL as input and returns an
     * `UrlEncryptResponse` object containing the original URL, the encryption
     * code, and the encrypted URL. The encryption code is a unique identifier
     * that can be used to retrieve the original URL.
     *
     * @param array $data The data containing the URL to encrypt.
     * @return UrlEncryptResponse The response from the API with the encrypted URL.
     */
    public function newUrlEncrypt($data) {
        $responseData = $this->getFunction("public", "new_url_encrypt")($data);
        return new UrlEncryptResponse(
            $responseData["original"],
            $responseData["code"],
            $responseData["encrypt"]
        );
    }
}