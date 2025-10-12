<?php
// Load configuration (contains ENCRYPTION_KEY)
require_once __DIR__ . '/config.php';


function encryptApiKey($apiKey) {
    $key = getEncryptionKey();
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($apiKey, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptApiKey($encryptedData) {
    $data = base64_decode($encryptedData);
    if ($data === false) {
        return false;
    }

    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    if (strlen($data) < $ivLength) {
        return false;
    }

    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);

    return openssl_decrypt(
        $encrypted,
        'aes-256-cbc',
        getEncryptionKey(),
        OPENSSL_RAW_DATA,
        $iv
    );
}

function getEncryptionKey() {
    // $keyFile = __DIR__ . '/encryption.key';
    // if (!file_exists($keyFile)) {
    //     $key = random_bytes(32);
    //     file_put_contents($keyFile, $key);
    //     return $key;
    // }
    // return file_get_contents($keyFile);

    // This should be changed to a secure random value in production
    return ENCRYPTION_KEY;
} 




/**
 * Encrypts data using AES-256-CBC
 */
function encryptData($data) {
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt(
        $data,
        'aes-256-cbc',
        ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv
    );
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data that was encrypted using encryptData()
 */
function decryptData($encryptedData) {
    $data = base64_decode($encryptedData);
    if ($data === false) {
        return false;
    }

    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    if (strlen($data) < $ivLength) {
        return false;
    }

    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);

    return openssl_decrypt(
        $encrypted,
        'aes-256-cbc',
        ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv
    );
} 