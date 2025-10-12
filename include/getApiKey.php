<?php
require_once 'encryption.php';

function getApiKey() {
    if (!isset($_COOKIE['api_key'])) {
        error_log("No API key found in cookies");
        return null;
    }
    
    $encryptedKey = $_COOKIE['api_key'];
   // error_log("Encrypted key: " . $encryptedKey);
    $decryptedKey = decryptApiKey($encryptedKey);
   // error_log("Decrypted key: " . $decryptedKey);
    return $decryptedKey;
}

function hasApiKey() {
    return getApiKey() !== null;
} 