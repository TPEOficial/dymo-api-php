<?php

define("BASE_URL", "https://api.tpeoficial.com");

/**
 * Sets the base URL of the API based on the specified flag.
 * @param boolean $is_local Whether to use the local development server.
 * @return string The base URL of the API.
 */
function set_base_url($is_local) {
    if ($is_local) return "http://localhost:3050";
    return BASE_URL;
}