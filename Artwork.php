<?php
session_start();

$Artconn = null;
$galleryItems = [];

if (extension_loaded('mysqli')) {
    $Artconn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if (!$Artconn->connect_error) {
        $stmt = $Artconn->prepare("SELECT * FROM Artdata ORDER BY ArtID DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $galleryItems[] = $row;
        }
    }
}

function resolveImagePath($imageName) {
    if (empty($imageName)) {
        return '';
    }

    $candidatePaths = [
        'images/' . $imageName,
        $imageName,
        'images/' . basename($imageName),
    ];

    foreach ($candidatePaths as $path) {
        if ($path !== '' && file_exists($path)) {
            return $path;
        }
    }

    return $candidatePaths[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <!-- Header Section -->
    <div class="py-1">
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="gallery-wrapper">

                <!-- Gallery Grid -->
                <div class="row row-cols-1 row-cols-lg-3 row-cols-md-2 g-4 gallery-row">
                    <?php if (!empty($galleryItems)): ?>
                        <?php foreach ($galleryItems as $row): ?>
                            <?php $imagePath = resolveImagePath($row['image_name'] ?? ''); ?>
                            <div class="col">
                                <div class="card h-100">
                                    <a href="veiw_art.php?id=<?php echo urlencode($row['ArtID']); ?>" class="gallery-link">
                                        <img
                                            src="<?php echo htmlspecialchars($imagePath); ?>"
                                            class="card-img-top"
                                            alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                                    </a>
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['ArtName']); ?></h5>
                                        <a href="veiw_art.php?id=<?php echo urlencode($row['ArtID']); ?>" onclick="window.location.href='veiw_art.php?id=<?php echo urlencode($row['ArtID']); ?>'; return false;" class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">No artwork found yet. Add items from the upload page first.</div>
                        </div>
                    <?php endif; ?>
                </div> <!-- .gallery-row -->
            </div> <!-- .gallery-wrapper -->
        </div> <!-- .container -->
    </div> <!-- .main-content -->

    <?php
        if ($Artconn instanceof mysqli) {
            $Artconn->close();
        }
    ?>
</body>
</html>