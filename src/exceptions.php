<?php

namespace Dymo\Exceptions;

class DymoAPIError extends \Exception {
    public function __construct($message, $code = 0, \Throwable $previous = null) {
        parent::__construct("[Dymo API] " . $message, $code, $previous);
    }
}

class AuthenticationError extends DymoAPIError {}
class NetworkException extends DymoAPIError {}
class ServerException extends DymoAPIError {}
class RateLimitException extends DymoAPIError {}
class BadRequestError extends DymoAPIError {}
class APIError extends DymoAPIError {}

?>