<?php

if (!defined('CEPIN_APP_TIMEZONE')) {
    define('CEPIN_APP_TIMEZONE', 'America/Sao_Paulo');
}

if (date_default_timezone_get() !== CEPIN_APP_TIMEZONE) {
    date_default_timezone_set(CEPIN_APP_TIMEZONE);
}

ini_set('default_charset', 'UTF-8');

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
