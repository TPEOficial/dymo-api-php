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
require_once "responseModels.php";

class DymoAPI
{
    private $organization;
    private $rootApiKey;
    private $apiKey;
    private $serverEmailConfig;
    private $tokensResponse;
    private $lastFetchTime;
    private $local;
    private $baseUrl;

    const BASE_URL = "https://api.tpeoficial.com";

    public function __construct($config = [])
    {
        $this->organization = $config["organization"] ?? null;
        $this->rootApiKey = $config["root_api_key"] ?? null;
        $this->apiKey = $config["api_key"] ?? null;
        $this->serverEmailConfig = $config["server_email_config"] ?? null;
        $this->tokensResponse = null;
        $this->lastFetchTime = null;
        $this->local = $config["local"] ?? false;

        $this->setBaseUrl($this->local);

        if ($this->apiKey) $this->initializeTokens();
    }

    private function setBaseUrl($local)
    {
        $this->baseUrl = $local ? "http://localhost:3050" : self::BASE_URL;
    }

    private function getFunction($moduleName, $functionName = "main")
    {
        if ($moduleName === "private" && $this->apiKey === null) throw new AuthenticationError("Invalid private token.");
        $modulePath = __DIR__ . "/branches/" . $moduleName . ".php";
        if (!file_exists($modulePath)) throw new Exception("Module not found: " . $modulePath);
        require_once $modulePath;
        return $functionName;
    }

    private function initializeTokens()
    {
        $currentTime = new DateTime();
        if ($this->tokensResponse && $this->lastFetchTime && ($currentTime->getTimestamp() - $this->lastFetchTime->getTimestamp()) < 300) return;
        $tokens = [];
        if ($this->rootApiKey) $tokens["root"] = "Bearer " . $this->rootApiKey;
        if ($this->apiKey) $tokens["private"] = "Bearer " . $this->apiKey;
        if (empty($tokens)) return;
        try {
            $response = $this->postRequest("/v1/dvr/tokens", ["organization" => $this->organization, "tokens" => $tokens]);
            if ($this->rootApiKey && !isset($response["root"])) throw new AuthenticationError("Invalid root token.");
            if ($this->apiKey && !isset($response["private"])) throw new AuthenticationError("Invalid private token.");
            $this->tokensResponse = $response;
            $this->lastFetchTime = $currentTime;
        } catch (Exception $e) {
            throw new AuthenticationError("Token validation error: " . $e->getMessage());
        }
    }

    private function postRequest($endpoint, $data)
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function isValidData($data)
    {
        $response = $this->getFunction("private", "is_valid_data")($data);
        if (isset($response["ip"]["as"])) {
            $response["ip"]["_as"] = $response["ip"]["as"];
            $response["ip"]["_class"] = $response["ip"]["class"];
            unset($response["ip"]["as"]);
            unset($response["ip"]["class"]);
        }
        return new DataVerifierResponse(
            new DataVerifierEmail(
                $response['email']['valid'] ?? null,
                $response['email']['fraud'] ?? null,
                $response['email']['freeSubdomain'] ?? null,
                $response['email']['corporate'] ?? null,
                $response['email']['email'] ?? null,
                $response['email']['realUser'] ?? null,
                $response['email']['customTLD'] ?? null,
                $response['email']['domain'] ?? null,
                $response['email']['roleAccount'] ?? null,
                $response['email']['plugins'] ?? null
            ),
            new DataVerifierPhone(
                $response['phone']['valid'] ?? null,
                $response['phone']['fraud'] ?? null,
                $response['phone']['phone'] ?? null,
                $response['phone']['prefix'] ?? null,
                $response['phone']['number'] ?? null,
                $response['phone']['country'] ?? null,
                $response['phone']['plugins'] ?? null
            ),
            new DataVerifierDomain(
                $response['domain']['valid'] ?? null,
                $response['domain']['fraud'] ?? null,
                $response['domain']['domain'] ?? null,
                $response['domain']['plugins'] ?? null
            )
        );
    }

    public function sendEmail($data)
    {
        if (!$this->serverEmailConfig) {
            throw new AuthenticationError("You must configure the email client settings.");
        }
        $responseData = $this->getFunction("private", "send_email")(array_merge($data, ["serverEmailConfig" => $this->serverEmailConfig]));

        return new SendEmailResponse(
            $responseData['status'],
            $responseData['error']
        );
    }

    public function getRandom($data)
    {
        $responseData = $this->getFunction("private", "get_random")(array_merge($data));
        return new SRNGResponse(
            $responseData['values'],
            $responseData['executionTime']
        );
    }

    public function getPrayerTimes($data)
    {
        $responseData = $this->getFunction("public", "get_prayer_times")($data);
        $prayerTimesByTimezone = [];
        foreach ($responseData['prayerTimesByTimezone'] as $timezone => $prayerTimes) {
            $prayerTimesByTimezone[] = new PrayerTimesByTimezone(
                $timezone,
                new PrayerTimes(
                    $prayerTimes['coordinates'],
                    $prayerTimes['date'],
                    $prayerTimes['calculationParameters'],
                    $prayerTimes['fajr'],
                    $prayerTimes['sunrise'],
                    $prayerTimes['dhuhr'],
                    $prayerTimes['asr'],
                    $prayerTimes['sunset'],
                    $prayerTimes['maghrib'],
                    $prayerTimes['isha']
                )
            );
        }
        return new PrayerTimesResponse(
            $responseData['country'],
            $prayerTimesByTimezone
        );
    }

    public function satinizer($data)
    {
        $responseData = $this->getFunction("public", "satinizer")($data);
        return new SatinizerResponse(
            $responseData['input'],
            new SatinizerFormats(
                $responseData['formats']['ascii'],
                $responseData['formats']['bitcoinAddress'],
                $responseData['formats']['cLikeIdentifier'],
                $responseData['formats']['coordinates'],
                $responseData['formats']['crediCard'],
                $responseData['formats']['date'],
                $responseData['formats']['discordUsername'],
                $responseData['formats']['doi'],
                $responseData['formats']['domain'],
                $responseData['formats']['e164Phone'],
                $responseData['formats']['email'],
                $responseData['formats']['emoji'],
                $responseData['formats']['hanUnification'],
                $responseData['formats']['hashtag'],
                $responseData['formats']['hyphenWordBreak'],
                $responseData['formats']['ipv6'],
                $responseData['formats']['ip'],
                $responseData['formats']['jiraTicket'],
                $responseData['formats']['macAddress'],
                $responseData['formats']['name'],
                $responseData['formats']['number'],
                $responseData['formats']['panFromGstin'],
                $responseData['formats']['password'],
                $responseData['formats']['port'],
                $responseData['formats']['tel'],
                $responseData['formats']['text'],
                $responseData['formats']['semver']
            ),
            new SatinizerIncludes(
                $responseData['includes']['spaces'],
                $responseData['includes']['hasSql'],
                $responseData['includes']['hasNoSql'],
                $responseData['includes']['letters'],
                $responseData['includes']['uppercase'],
                $responseData['includes']['lowercase'],
                $responseData['includes']['symbols'],
                $responseData['includes']['digits']
            )
        );
    }

    public function isValidPwd($data)
    {
        $responseData = $this->getFunction("public", "is_valid_pwd")($data);
        $details = [];
        foreach ($responseData['details'] as $detail) {
            $details[] = new IsValidPwdDetails(
                $detail['validation'],
                $detail['message']
            );
        }
        return new IsValidPwdResponse(
            $responseData['valid'],
            $responseData['password'],
            $details
        );
    }

    public function newUrlEncrypt($data)
    {
        $responseData = $this->getFunction("public", "new_url_encrypt")($data);
        return new UrlEncryptResponse(
            $responseData['original'],
            $responseData['code'],
            $responseData['encrypt']
        );
    }
}
