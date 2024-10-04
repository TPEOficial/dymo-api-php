<?php

require_once "exceptions.php";
require_once "responseModels.php";

class DymoAPI {
    private $organization;
    private $rootApiKey;
    private $apiKey;
    private $serverEmailConfig;
    private $tokensResponse;
    private $lastFetchTime;
    private $local;
    private $baseUrl;

    const BASE_URL = "https://api.tpeoficial.com";

    public function __construct($config = []) {
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

    private function setBaseUrl($local) {
        $this->baseUrl = $local ? "http://localhost:3050" : self::BASE_URL;
    }

    private function getFunction($moduleName, $functionName = "main") {
        if ($moduleName === "private" && $this->apiKey === null) throw new AuthenticationError("Invalid private token.");
        $modulePath = __DIR__ . "/branches/" . $moduleName . ".php";
        if (!file_exists($modulePath)) throw new Exception("Module not found: " . $modulePath);
        require_once $modulePath;
        return $functionName;
    }

    private function initializeTokens() {
        $currentTime = new DateTime();
        if ($this->tokensResponse && $this->lastFetchTime && ($currentTime->getTimestamp() - $this->lastFetchTime->getTimestamp()) < 300) return;
        $tokens = [];
        if ($this->rootApiKey) $tokens["root"] = "Bearer " . $this->rootApiKey;
        if ($this->apiKey) $tokens["private"] = "Bearer " . $this->apiKey;
        if (empty($tokens)) return;
        try {
            $response = $this->postRequest("/v1/dvr/tokens", ["tokens" => $tokens]);
            if ($this->rootApiKey && !isset($response["root"])) throw new AuthenticationError("Invalid root token.");
            if ($this->apiKey && !isset($response["private"])) throw new AuthenticationError("Invalid private token.");
            $this->tokensResponse = $response;
            $this->lastFetchTime = $currentTime;
        } catch (Exception $e) {
            throw new AuthenticationError("Token validation error: " . $e->getMessage());
        }
    }

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

    public function isValidData($data) {
        $response = $this->getFunction("private", "is_valid_data")($data);
        if (isset($response["ip"]["as"])) {
            $response["ip"]["_as"] = $response["ip"]["as"];
            $response["ip"]["_class"] = $response["ip"]["class"];
            unset($response["ip"]["as"]);
            unset($response["ip"]["class"]);
        }
        return new DataVerifierResponse($response);
    }

    public function sendEmail($data) {
        if (!$this->serverEmailConfig) throw new AuthenticationError("You must configure the email client settings.");
        return new SendEmailResponse($this->getFunction("private", "send_email")(array_merge($data, ["serverEmailConfig" => $this->serverEmailConfig])));
    }

    public function getRandom($data) {
        return new SRNGResponse($this->getFunction("private", "get_random")(array_merge($data)));
    }

    public function getPrayerTimes($data) {
        return new PrayerTimesResponse($this->getFunction("public", "get_prayer_times")($data));
    }

    public function satinizer($data) {
        return new SatinizerResponse($this->getFunction("public", "satinizer")($data));
    }

    public function isValidPwd($data) {
        return new IsValidPwdResponse($this->getFunction("public", "is_valid_pwd")($data));
    }

    public function newUrlEncrypt($data) {
        return new UrlEncryptResponse($this->getFunction("public", "new_url_encrypt")($data));
    }
}