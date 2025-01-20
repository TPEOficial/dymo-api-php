<?php

use Dymo\Models\PrayerTimesByTimezone;
use Dymo\Models\DataVerifierResponse;
use Dymo\Models\PrayerTimesResponse;
use Dymo\Models\IsValidPwdResponse;
use Dymo\Models\DataVerifierDomain;
use Dymo\Models\UrlEncryptResponse;
use Dymo\Models\DataVerifierPhone;
use Dymo\Models\DataVerifierEmail;
use Dymo\Models\SatinizerIncludes;
use Dymo\Models\IsValidPwdDetails;
use Dymo\Models\SendEmailResponse;
use Dymo\Models\SatinizerResponse;
use Dymo\Models\SatinizerFormats;
use Dymo\Models\SRNGResponse;
use Dymo\Models\PrayerTimes;

require_once "exceptions.php";
require_once "response_models.php";

class DymoAPI {
    private $organization;
    private $rootApiKey;
    private $apiKey;
    private $serverEmailConfig;
    private $local;
    private $baseUrl;
    private $errorLogRoute = "./error.log";
    private static $tokensResponse = null;
    private static $tokensVerified = false;

    const BASE_URL = "https://api.tpeoficial.com";

    /**
     * Construct a new instance of the Dymo API client.
     *
     * @param array $config The configuration array for the client.
     * @param string $config["organization"] The organization name.
     * @param string $config["root_api_key"] The root API key.
     * @param string $config["api_key"] The API key.
     * @param array $config["server_email_config"] The server email configuration.
     * @param bool $config["local"] Whether to use the local development server.
     */
    public function __construct($config = []) {
        $this->organization = $config["organization"] ?? null;
        $this->rootApiKey = $config["root_api_key"] ?? null;
        $this->apiKey = $config["api_key"] ?? null;
        $this->serverEmailConfig = $config["server_email_config"] ?? null;
        $this->local = $config["local"] ?? false;

        $this->setBaseUrl($this->local);

        if ($this->apiKey) $this->initializeTokens();
    }

    /**
     * Set the base URL for the Dymo API.
     *
     * If the "local" parameter is set to true, the base URL will be set to
     * "http://localhost:3050". Otherwise, the base URL will be set to the default
     * value of "https://api.tpeoficial.com".
     *
     * @param bool $local Whether the base URL should be set to the local
     *                    development server.
     */
    private function setBaseUrl($local) {
        $this->baseUrl = $local ? "http://localhost:3050" : self::BASE_URL;
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
    private function getFunction($moduleName, $functionName = "main") {
        if ($moduleName === "private" && $this->apiKey === null) return error_log("Invalid private token.\n", 3, $this->errorLogRoute);
        $modulePath = __DIR__ . "/branches/" . $moduleName . ".php";
        if (!file_exists($modulePath)) throw new Exception("Module not found: " . $modulePath);
        require_once $modulePath;
        return $functionName;
    }

    /**
     * Initialize tokens for the Dymo API.
     *
     * If the `rootApiKey` or `apiKey` properties are set, this function will
     * validate the tokens by sending a POST request to `/v1/dvr/tokens` with
     * the tokens in the request body. If the tokens are valid, the function will
     * store the validated tokens in the `tokensResponse` property and set the
     * `tokensVerified` property to the current time.
     *
     *
     * The function will not send a request if the tokens have already been
     * validated within the last 5 minutes.
     */
    private function initializeTokens() {
        if (self::$tokensResponse && self::$tokensVerified) return;

        $tokens = [];
        if ($this->rootApiKey) $tokens["root"] = "Bearer " . $this->rootApiKey;
        if ($this->apiKey) $tokens["private"] = "Bearer " . $this->apiKey;
        if (empty($tokens)) return;
        try {
            $response = $this->postRequest("/v1/dvr/tokens", ["tokens" => $tokens]);
            if ($this->rootApiKey && (!isset($response["root"]) || $response["root"] === false)) throw new AuthenticationError("Invalid root token.");
            if ($this->apiKey && (!isset($response["private"]) || $response["private"] === false)) throw new AuthenticationError("Invalid private token.");
            self::$tokensResponse = $response;
            self::$tokensVerified = true;
        } catch (Exception $e) {
            return error_log("Token validation error: " . $e->getMessage() . "\n", 3, $this->errorLogRoute);
        }
    }

    /**
     * Perform a POST request to the Dymo API.
     *
     * @param string $endpoint The endpoint to call.
     * @param array $data The data to send in the request body.
     * @return array The response from the API, decoded from JSON.
     */
    private function postRequest($endpoint, $data) {
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
     * - Email
     * - Phone
     * - Domain
     * - Credit Card
     * - IP Address
     * - Wallet
     *
     * The API will return the following information for each data type:
     *
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
     * @param array $data The data to validate.
     * @return DataVerifierResponse The response from the API with validation details.
     */
    public function isValidData($data) {
        $response = $this->getFunction("private", "is_valid_data")($data);
        if (isset($response["ip"]["as"])) {
            $response["ip"]["_as"] = $response["ip"]["as"];
            $response["ip"]["_class"] = $response["ip"]["class"];
            unset($response["ip"]["as"]);
            unset($response["ip"]["class"]);
        }
        return new DataVerifierResponse(
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
                $response["domain"]["domain"] ?? null,
                $response["domain"]["plugins"] ?? null
            )
        );
    }

    /**
     * Send an email using the Dymo API.
     *
     * @param array $data The data to send to the API.
     * @return SendEmailResponse The response from the API.
     */
    public function sendEmail($data) {
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
     * @param array $data The data to send to the API.
     * @return SRNGResponse The response from the API.
     */
    public function getRandom($data) {
        $responseData = $this->getFunction("private", "get_random")(array_merge($data));
        return new SRNGResponse(
            $responseData["values"],
            $responseData["executionTime"]
        );
    }

    /**
     * Get prayer times for a given country and timezone.
     *
     * @param array $data The data to send to the API.
     * @return PrayerTimesResponse The response from the API.
     */
    public function getPrayerTimes($data) {
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
     * @param array $data The data to sanitize.
     * @return SatinizerResponse The response containing sanitized data and formats.
     */
    public function satinizer($data) {
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
     * @param array $data The data containing the password to validate.
     * @return IsValidPwdResponse The response from the API with validation details.
     */
    public function isValidPwd($data) {
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