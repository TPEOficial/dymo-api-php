<?php

define("BASE_URL", "https://api.tpeoficial.com");

function set_base_url($is_local) {
    if ($is_local) return "http://localhost:3050";
    return BASE_URL;
}