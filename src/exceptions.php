<?php

class DymoAPIError extends Exception {
    public function __construct($message) {
        parent::__construct($message);
        $this->message = "[Dymo API] " . $message;
    }
}

class AuthenticationError extends DymoAPIError {}
class RateLimitError extends DymoAPIError {}
class BadRequestError extends DymoAPIError {}
class APIError extends DymoAPIError {}

?>