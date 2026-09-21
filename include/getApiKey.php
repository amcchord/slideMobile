<?php
// Use the same cookie and decryption as the Recipes host when installed at /mobile.
$sharedHelper = dirname(__DIR__, 2) . '/include/getApiKey.php';
if (is_file($sharedHelper)) {
    require_once $sharedHelper;
} else {
    require_once __DIR__ . '/encryption.php';
    function getApiKey() {
        if (empty($_COOKIE['api_key'])) return null;
        $key = decryptApiKey($_COOKIE['api_key']);
        return is_string($key) && $key !== '' ? $key : null;
    }
    function hasApiKey() { return getApiKey() !== null; }
}
