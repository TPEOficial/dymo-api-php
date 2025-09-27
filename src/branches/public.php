<?php

require_once "../config.php";
require_once "../exceptions.php";

/**
 * Retrieves prayer times based on the provided latitude and longitude.
 * 
 * @param array $data An associative array containing:
 *                    - lat: The latitude of the location.
 *                    - lon: The longitude of the location.
 * 
 * @return array The prayer times as an associative array.
 * 
 * @throws BadRequestError If latitude or longitude is not provided.
 * @throws APIError If the API request fails or returns a non-200 status code.
 */
function get_prayer_times($data) {
    if (empty($data["lat"]) || empty($data["lon"])) throw new BadRequestError("You must provide a latitude and longitude.");

    $params = [
        "lat" => $data["lat"],
        "lon" => $data["lon"]
    ];

    $url = BASE_URL . "/v1/public/islam/prayertimes?" . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: DymoAPISDK/1.0.0",
        "X-Dymo-SDK-Env: PHP",
        "X-Dymo-SDK-Version: 0.0.28"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) throw new APIError(curl_error($ch));

    curl_close($ch);
    if ($httpCode !== 200) throw new APIError("API request failed with status code: $httpCode");
    return json_decode($response, true);
}

/**
 * DEPRECATED: Use satinize($input) instead.
 * 
 * Sanitizes the input and returns it in various formats.
 *
 * The satinizer API processes the input data and returns a response
 * containing the sanitized input and various format representations.
 *
 * The response includes format details such as ASCII, Bitcoin address,
 * C-like identifier, coordinates, credit card, date, Discord username,
 * DOI, domain, E.164 phone number, email, emoji, Han unification, hashtag,
 * hyphen word break, IPv6, IP, JIRA ticket, MAC address, name, number,
 * PAN from GSTIN, password, port, telephone, text, and semantic version.
 *
 * Additionally, the response includes information on the presence of spaces,
 * SQL and NoSQL keywords, letters, uppercase and lowercase letters, symbols,
 * and digits in the input data.
 *
 * @param array $data An associative array containing the input to sanitize.
 *
 * @return array The response containing sanitized data and formats.
 *
 * @throws BadRequestError If the input is not provided.
 * @throws APIError If the API request fails or returns a non-200 status code.
 */
function satinizer($data) {
    trigger_error("Function satinizer() is deprecated and will be removed in future versions. Use satinize() instead.", E_USER_DEPRECATED);
    if (!isset($data["input"])) throw new BadRequestError("You must specify at least the input.");
    
    $input_value = $data["input"];
    $url = BASE_URL . "/v1/public/inputSatinizer?input=" . urlencode($input_value);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: DymoAPISDK/1.0.0",
        "X-Dymo-SDK-Env: PHP",
        "X-Dymo-SDK-Version: 0.0.28"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) throw new APIError(curl_error($ch));
    
    curl_close($ch);

    if ($httpCode !== 200) throw new APIError("API request failed with status code: $httpCode");

    return json_decode($response, true);
}

/**
 * Sanitizes the input and returns it in various formats.
 *
 * The satinizer API processes the input data and returns a response
 * containing the sanitized input and various format representations.
 *
 * The response includes format details such as ASCII, Bitcoin address,
 * C-like identifier, coordinates, credit card, date, Discord username,
 * DOI, domain, E.164 phone number, email, emoji, Han unification, hashtag,
 * hyphen word break, IPv6, IP, JIRA ticket, MAC address, name, number,
 * PAN from GSTIN, password, port, telephone, text, and semantic version.
 *
 * Additionally, the response includes information on the presence of spaces,
 * SQL and NoSQL keywords, letters, uppercase and lowercase letters, symbols,
 * and digits in the input data.
 *
 * @param string $input The input string to sanitize.
 *
 * @return array The response containing sanitized data and formats.
 *
 * @throws BadRequestError If the input is not provided.
 * @throws APIError If the API request fails or returns a non-200 status code.
 */
function satinize($input) {
    if (!$input) throw new BadRequestError("You must specify the input.");
    $url = BASE_URL . "/v1/public/inputSatinizer?input=" . urlencode($input);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: DymoAPISDK/1.0.0",
        "X-Dymo-SDK-Env: PHP",
        "X-Dymo-SDK-Version: 0.0.28"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) throw new APIError(curl_error($ch));
    
    curl_close($ch);

    if ($httpCode !== 200) throw new APIError("API request failed with status code: $httpCode");

    return json_decode($response, true);
}

/**
 * Checks if the provided password is valid given the parameters.
 * @param string $data["password"] The password to be checked.
 * @param string $data["email"] The email to be checked against.
 * @param array|string $data["bannedWords"] A list of banned words.
 * @param int $data["min"] The minimum length of the password.
 * @param int $data["max"] The maximum length of the password.
 * @return object The result of the check.
 * @throws BadRequestError If the parameters are invalid.
 * @throws APIError If the API request failed.
 */
function is_valid_pwd($data) {
    if (empty($data["password"])) throw new BadRequestError("You must specify at least the password.");

    $params = [
        "password" => urlencode($data["password"])
    ];

    if (!empty($data["email"])) {
        if (!preg_match("/^[a-zA-Z0-9._\-+]+@?[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/", $data["email"])) throw new BadRequestError("If you provide an email address it must be valid.");
        $params["email"] = urlencode($data["email"]);
    }

    if (!empty($data["bannedWords"])) {
        $banned_words = $data["bannedWords"];
        if (is_string($banned_words)) {
            $banned_words = explode(",", trim($banned_words, "[]"));
            $banned_words = array_map('trim', $banned_words);
        }
        if (!is_array($banned_words) || count($banned_words) > 10) throw new BadRequestError("If you provide a list of banned words; the list may not exceed 10 words and must be of array type.");
        if (count($banned_words) !== count(array_unique($banned_words))) throw new BadRequestError("If you provide a list of banned words; all elements must be non-repeated strings.");
        $params["bannedWords"] = implode(",", array_map('urlencode', $banned_words));
    }

    if (isset($data["min"])) {
        $min_length = $data["min"];
        if (!is_int($min_length) || $min_length < 8 || $min_length > 32) throw new BadRequestError("If you provide a minimum it must be valid.");
        $params["min"] = $min_length;
    }

    if (isset($data["max"])) {
        $max_length = $data["max"];
        if (!is_int($max_length) || $max_length < 32 || $max_length > 100) throw new BadRequestError("If you provide a maximum it must be valid.");
        $params["max"] = $max_length;
    }

    $url = BASE_URL . "/v1/public/validPwd?" . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: DymoAPISDK/1.0.0",
        "X-Dymo-SDK-Env: PHP",
        "X-Dymo-SDK-Version: 0.0.28"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) throw new APIError(curl_error($ch));

    curl_close($ch);
    if ($httpCode !== 200) throw new APIError("API request failed with status code: $httpCode");
    return json_decode($response, true);
}

/**
 * Encrypts a given URL into an encrypted link that will be used to shorten the provided URL.
 * @param string $url The URL to be encrypted.
 * @return object The encrypted URL.
 * @throws BadRequestError If the provided URL is invalid.
 * @throws APIError If the API request failed.
 */
function new_url_encrypt($url) {
    if (empty($url) || !(strpos($url, "https://") === 0 || strpos($url, "http://") === 0)) throw new BadRequestError("You must provide a valid url.");

    $url = BASE_URL . "/v1/public/url-encrypt?url=" . urlencode($url);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: DymoAPISDK/1.0.0",
        "X-Dymo-SDK-Env: PHP",
        "X-Dymo-SDK-Version: 0.0.28"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) throw new APIError(curl_error($ch));

    curl_close($ch);
    if ($httpCode !== 200) throw new APIError("API request failed with status code: $httpCode");
    return json_decode($response, true);
}

?>