<?php

require_once "../config.php";
require_once "../exceptions.php";

function is_valid_data($token, $data) {
    if (!array_reduce(["email", "phone", "domain", "creditCard", "ip", "wallet"], function ($carry, $key) use ($data) {
        return $carry || array_key_exists($key, $data); }, false)) throw new BadRequestError("You must provide at least one parameter.");
}

function send_email($token, $data) {
    if (!isset($data["from"])) throw new BadRequestError("You must provide an email address from which the following will be sent.");
    if (!isset($data["to"])) throw new BadRequestError("You must provide an email to be sent to.");
    if (!isset($data["subject"])) throw new BadRequestError("You must provide a subject for the email to be sent.");
    if (!isset($data["html"])) throw new BadRequestError("You must provide HTML.");
    

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, BASE_URL . "/v1/private/sender/sendEmail");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) throw new APIError(curl_error($ch));
    
    curl_close($ch);
    if ($httpCode !== 200) throw new APIError("API request failed with status code: $httpCode");
    return json_decode($response, true);
}

function get_random($token, $data) {
    if (!isset($data["from"])) throw new BadRequestError("You must provide an email address from which the following will be sent.");
    if (!isset($data["to"])) throw new BadRequestError("You must provide an email to be sent to.");
    if (!isset($data["subject"])) throw new BadRequestError("You must provide a subject for the email to be sent.");
    if (!isset($data["html"])) throw new BadRequestError("You must provide HTML.");
    if (!isset($data["min"]) || !isset($data["max"])) throw new BadRequestError("Both 'min' and 'max' parameters must be defined.");
    if ($data["min"] >= $data["max"]) throw new BadRequestError("'min' must be less than 'max'.");
    if ($data["min"] < -1000000000 || $data["min"] > 1000000000) throw new BadRequestError("'min' must be an integer in the interval [-1000000000, 1000000000].");
    if ($data["max"] < -1000000000 || $data["max"] > 1000000000) throw new BadRequestError("'max' must be an integer in the interval [-1000000000, 1000000000].");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, BASE_URL . "/v1/private/srng");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) throw new APIError(curl_error($ch));
    curl_close($ch);
    if ($httpCode !== 200) throw new APIError("API request failed with status code: $httpCode");
    return json_decode($response, true);
}

?>