<?php
if (!defined('CEPIN_APP_TIMEZONE')) {
    define('CEPIN_APP_TIMEZONE', 'America/Sao_Paulo');
}
if (date_default_timezone_get() !== CEPIN_APP_TIMEZONE) {
    date_default_timezone_set(CEPIN_APP_TIMEZONE);
}
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
