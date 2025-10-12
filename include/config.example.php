<?php
/**
 * Slide Mobile Configuration Template
 * 
 * Copy this file to config.php and update with your actual values.
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file: cp config.example.php config.php
 * 2. Generate a secure random 32-character encryption key
 * 3. Replace 'YOUR_SECURE_RANDOM_32_CHAR_KEY' with your generated key
 * 
 * To generate a secure key, you can use:
 * php -r "echo bin2hex(random_bytes(16));"
 * 
 * Or use a password generator to create a 32-character random string with
 * uppercase, lowercase, numbers, and special characters.
 */

// Encryption key for API key storage in cookies
// This should be a secure random 32-character string
define('ENCRYPTION_KEY', 'YOUR_SECURE_RANDOM_32_CHAR_KEY');

