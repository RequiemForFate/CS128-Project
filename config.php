<?php
<<<<<<< HEAD
define('DB_HOST', 'sql211.infinityfree.com');   // database hosting (unique to each site)
define('DB_USER', 'if0_42372622');                // database username
define('DB_PASS', '007009ixx');             // database password
define('DB_NAME', 'if0_42372622_artport');      // database name
=======
define('DB_HOST', 'localhost');   // database hosting (unique to each site)
define('DB_USER', 'root');                // database username
define('DB_PASS', '');             // database password
define('DB_NAME', 'ArtShopDB');      // database name
>>>>>>> 68861ae35a251836cd24e898e11ae4d5f84d6c87

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
