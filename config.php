<?php
define('DB_HOST', '127.0.0.1');   // Use IP to avoid IPv6 issues
define('DB_USER', 'root');
define('DB_PASS', '');           // Set your password if you have one
define('DB_NAME', 'artshopDB');

// Upload directory
define('UPLOAD_DIR', 'images/');

// error reporting 
error_reporting(E_ALL);
ini_set('display_errors', 1);

function autoLink($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // find URLs (http, https, ftp)
    $pattern = '~(https?://[^\s<]+)~i';
    $replace = '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>';
    return preg_replace($pattern, $replace, $text);
}
?>