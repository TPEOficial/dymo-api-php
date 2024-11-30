<?php

require_once "../config.php";
require_once "../exceptions.php";

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
function is_valid_data($token, $data) {
    if (!array_reduce(["email", "phone", "domain", "creditCard", "ip", "wallet"], function ($carry, $key) use ($data) { return $carry || array_key_exists($key, $data); }, false)) throw new BadRequestError("You must provide at least one parameter.");
    try {
        $ch = curl_init(BASE_URL . "/v1/private/secure/verify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $token"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) throw new BadRequestError(curl_error($ch));

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) throw new BadRequestError("Error: HTTP $httpCode");

        return json_decode($response, true);

    } catch (BadRequestError $e) {
        throw new InternalServerError($e->getMessage());
    }
}

/**
 * Sends an email using the provided data through the email sender API.
 *
 * This function requires the following data parameters:
 * 
 * - `from`: The email address from which the email will be sent.
 * - `to`: The recipient email address.
 * - `subject`: The subject of the email.
 * - `html`: The HTML content of the email.
 * - `options`: Specifications at the time of sending the email.
 * - `attachments`: An optional array of attachments, where each attachment can
 *    specify either `path` or `content`, but not both. Each attachment can also
 *    have a `filename` and `cid`.
 * 
 * Attachments must not exceed a total size of 40 MB.
 *
 * @param string $token The API token for authorization.
 * @param array $data An associative array containing the email data and optional attachments.
 * @return array The response from the API, decoded from JSON.
 * @throws BadRequestError If required data parameters are missing or invalid, or if attachments exceed size limit.
 * @throws APIError If the API request fails or encounters an error.
 */
function send_email($token, $data) {
    if (!isset($data["from"])) throw new BadRequestError("You must provide an email address from which the following will be sent.");
    if (!isset($data["to"])) throw new BadRequestError("You must provide an email to be sent to.");
    if (!isset($data["subject"])) throw new BadRequestError("You must provide a subject for the email to be sent.");
    if (!isset($data["html"])) throw new BadRequestError("You must provide HTML.");
    
    if (isset($data["attachments"]) && is_array($data["attachments"])) {
        $totalSize = 0;
        $processedAttachments = [];
        foreach ($data["attachments"] as $attachment) {
            if ((isset($attachment["path"]) && isset($attachment["content"])) || (!isset($attachment["path"]) && !isset($attachment["content"]))) throw new BadRequestError("You must provide either 'path' or 'content', not both.");
            if (isset($attachment["path"])) {
                $fileContent = file_get_contents($attachment["path"]);
                if ($fileContent === false) throw new BadRequestError("Unable to read the file at " . $attachment["path"]);
                $sizeInBytes = strlen($fileContent);
            } else if (isset($attachment["content"])) {
                $fileContent = $attachment["content"];
                $sizeInBytes = strlen($fileContent);
            }
            $totalSize += $sizeInBytes;
            if ($totalSize > 40 * 1024 * 1024) throw new BadRequestError("Attachments exceed the maximum allowed size of 40 MB.");
            $processedAttachments[] = [
                "filename" => $attachment["filename"] ?? basename($attachment["path"] ?? ""),
                "content" => base64_encode($fileContent),
                "cid" => $attachment["cid"] ?? null
            ];
        }
        $data["attachments"] = $processedAttachments;
    }

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

/**
 * Generates a list of cryptographically secure random numbers between the given
 * minimum and maximum values (inclusive) and sends an email with the list to the
 * specified email address.
 *
 * This function requires the following data parameters:
 * 
 * - `from`: The email address from which the email will be sent.
 * - `to`: The recipient email address.
 * - `subject`: The subject of the email.
 * - `html`: The HTML content of the email.
 * - `min`: The minimum value of the range (inclusive) from which to generate random numbers.
 * - `max`: The maximum value of the range (inclusive) from which to generate random numbers.
 *
 * @param string $token The API token for authorization.
 * @param array $data An associative array containing the email data, min, and max.
 * @return array The response from the API, decoded from JSON.
 * @throws BadRequestError If required data parameters are missing or invalid.
 * @throws APIError If the API request fails or encounters an error.
 */
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