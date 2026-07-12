<?php
require_once 'config.php';

$bannerImage = 'images/banner.jpg'; // Default banner image
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $bannerconn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$bannerconn->connect_error) {
        $bannerstmt = $bannerconn->prepare("SELECT banner_image FROM users WHERE name = ?");
        $bannerstmt->bind_param("s", $_SESSION['username']);
        $bannerstmt->execute();
        $bannerresult = $bannerstmt->get_result();
        if ($bannerresult->num_rows > 0) {
            $bannerrow = $bannerresult->fetch_assoc();
            if (!empty($bannerrow['banner_image'])) {
                $bannerImage = UPLOAD_DIR . htmlspecialchars($bannerrow['banner_image']);
            }
        }
        $bannerconn->close();
    }
}

$galleryName = 'gallery'; // Default gallery name
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $galleryconn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$galleryconn->connect_error) {
        $gallerystmt = $galleryconn->prepare("SELECT gallery_name FROM users WHERE name = ?");
        $gallerystmt->bind_param("s", $_SESSION['username']);
        $gallerystmt->execute();
        $galleryresult = $gallerystmt->get_result();
        if ($galleryresult->num_rows > 0) {
            $row = $galleryresult->fetch_assoc();
            if (!empty($row['gallery_name'])) {
                $galleryName = htmlspecialchars($row['gallery_name']);
            }
        }
        $galleryconn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .logo-big {
            width: 60px;
            height: auto;
        }
        .banner {
            width: 100%;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .banner-image {
            display: block;
            width: 100%;
            height: auto;
            min-height: 180px;
            max-height: 280px;
            object-fit: cover;
            padding: 0;
            border-radius: 15px;
        }
        .banner h2 {
            margin-top: 0;
            color: #000;
            text-shadow: 0 1px 2px rgba(255,255,255,0.6);
        }
        .banner-text {
            background-color: #ffffff;
            padding: 15px 20px;    
            border-radius: 15px;
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="banner">
        <img src="<?php echo htmlspecialchars($bannerImage); ?>" class="banner-image" alt="Banner Image">
        <div class="container alert-info text-center py-2">
            <h2 class="banner-text"><?php echo htmlspecialchars($galleryName); ?></h2>
        </div>
    </div>
</div>
</body>
</html>