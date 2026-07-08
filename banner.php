<?php

$bannerImage = 'images/banner.jpg'; // Default banner image
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $bannerconn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if (!$bannerconn->connect_error) {
        $stmt = $bannerconn->prepare("SELECT banner_image FROM users WHERE name = ?");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['banner_image'])) {
                $bannerImage = 'images/' . htmlspecialchars($row['banner_image']);
            }
        }
        $bannerconn->close();
    }
}

$galleryName = 'gallery'; // Default gallery name
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $galleryconn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if (!$galleryconn->connect_error) {
        $stmt = $galleryconn->prepare("SELECT gallery_name FROM users WHERE name = ?");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
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
</head>

<style>
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
    }

    .logo-big {
        width: 60px;
        height: auto;
    }
    .banner h2 {
        margin-top: -60px;
        color: #000;
        text-shadow: 0 1px 2px rgba(255,255,255,0.6);
    }
</style>
<body>
<div class="main-content"> <!-- main-content start -->
<div class="banner"> <!-- banner start -->
    <img src="<?php echo htmlspecialchars($bannerImage); ?>" class="banner-image" alt="Banner Image">
    <div class="container alert-info text-center py-2"> <!-- container start -->
        <h2><?php echo htmlspecialchars($galleryName); ?></h2>
    </div> <!-- end container -->
</div> <!-- end banner -->
</div> <!-- end main-content -->
</body>
</html>