<?php
session_start();
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

$conn = mysqli_connect("localhost", "root", "", "ArtShopDB", 3306);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $errorMessage = 'Invalid artwork ID.';
    $row = null;
} else {
    $sql = "SELECT * FROM Artdata WHERE ArtID = ?";
    $veiwstmt = $conn->prepare($sql);
    $veiwstmt->bind_param("i", $id);
    $veiwstmt->execute();
    $result = $veiwstmt->get_result();
    $row = $result->fetch_assoc();
}

if (!$row) {
    $errorMessage = $errorMessage ?? 'Artwork not found.';
    $imagePath = '';
    $prev = null;
    $next = null;
} else {
    $imagePath = resolveImagePath($row['image_name'] ?? '');

    // Get previous and next artwork
    $currentID = $row['ArtID'];

    $prevStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE ArtID < ? ORDER BY ArtID DESC LIMIT 1");
    $prevStmt->bind_param("i", $currentID);
    $prevStmt->execute();
    $prevResult = $prevStmt->get_result();
    $prev = $prevResult->fetch_assoc();

    $nextStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE ArtID > ? ORDER BY ArtID ASC LIMIT 1");
    $nextStmt->bind_param("i", $currentID);
    $nextStmt->execute();
    $nextResult = $nextStmt->get_result();
    $next = $nextResult->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($row['ArtName'] ?? 'Artwork Preview'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Header Section -->
    <div class="container py-1">
        <?php include 'banner.php'; ?>
        <?php
        $veiwstmt = $conn->prepare($sql);
        $veiwstmt->bind_param("i", $id);
        $veiwstmt->execute();
        $result = $veiwstmt->get_result();
        $row = $result->fetch_assoc();
        ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-5 position-relative">
            <!-- Close Button -->
            <a href="Artwork.php" class="close-btn">&times;</a>

            <!-- Artwork Display -->
    <?php if (!$row): ?>
        <div class="alert alert-warning">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php else: ?>
        <div class="row align-items-center g-4">
            <!-- Image Section -->
            <div class="col-md-7">
                <img
                    src="<?php echo htmlspecialchars($imagePath); ?>"
                    class="big-image"
                    alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
            </div>

            <!-- Information Section -->
            <div class="col-md-5">
                <h1><?php echo htmlspecialchars($row['ArtName']); ?></h1>
                
                <p class="text-muted">
                    <strong>Artwork ID:</strong><br>
                    <?php echo htmlspecialchars($row['ArtID']); ?>
                </p>
                
                <p>
                    <?php echo htmlspecialchars($row['ArtDes']); ?>
                </p>
                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-5 gap-2">
                        <?php if ($prev) { ?>
                            <a href="view_art.php?id=<?php echo htmlspecialchars($prev['ArtID']); ?>" class="btn btn-dark">
                                ← Previous
                            </a>
                        <?php } else { ?>
                            <div></div>
                        <?php } ?>

                        <?php if ($next) { ?>
                            <a href="view_art.php?id=<?php echo htmlspecialchars($next['ArtID']); ?>" class="btn btn-dark">
                                Next →
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div> <!-- .row -->
        <?php endif; ?>
        </div> <!-- .container -->
    </div> <!-- .main-content -->
</body>
</html>

<?php
    $conn->close();
?>