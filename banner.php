<?php
require_once 'config.php';

$bannerImage = 'images/banner.jpg'; // Default banner image

// Fetch banner image if logged in
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            min-height: 120px;
            max-height: 280px;
            object-fit: cover;
            padding: 0;
            border-radius: 15px;
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="banner">
        <img src="<?php echo htmlspecialchars($bannerImage); ?>" class="banner-image" alt="Banner Image">
    </div>
</div>
</body>
</html>