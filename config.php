<?php
define('DB_HOST', 'localhost');   // database hosting (unique to each site)
define('DB_USER', 'root');                // database username
define('DB_PASS', '');             // database password
define('DB_NAME', 'ArtShopDB');      // database name

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
