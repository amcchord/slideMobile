<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'include/encryption.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_key'])) {
            setcookie('api_key', '', time() - 3600, '/');
            header('Location: index.php');
            exit;
        }
        
        if (isset($_POST['api_key'])) {
            $apiKey = trim($_POST['api_key']);
            
            // Verify the API key works
            $ch = curl_init();
            if ($ch === false) {
                throw new Exception('Failed to initialize cURL');
            }
            
            curl_setopt($ch, CURLOPT_URL, "https://api.slide.tech/v1/device");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception('API request failed: ' . $error);
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                // Encrypt and store the API key
                if (!function_exists('encryptData')) {
                    throw new Exception('Encryption function not found');
                }
                
                $encryptedKey = encryptData($apiKey);
                if (empty($encryptedKey)) {
                    throw new Exception('Failed to encrypt API key');
                }
                
                $cookieResult = setcookie('api_key', $encryptedKey, [
                    'expires' => time() + (86400 * 365),
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                if (!$cookieResult) {
                    throw new Exception('Failed to set cookie');
                }
                
                header('Location: index.php');
                exit;
            } else {
                header('Location: manual-key-entry.php?error=invalid_key&code=' . $httpCode);
                exit;
            }
        }
    }

    header('Location: manual-key-entry.php?error=missing_key');
    exit;
    
} catch (Exception $e) {
    // Log the error
    error_log('API Key Error: ' . $e->getMessage());
    
    // Redirect with error
    header('Location: manual-key-entry.php?error=system_error&message=' . urlencode($e->getMessage()));
    exit;
} 