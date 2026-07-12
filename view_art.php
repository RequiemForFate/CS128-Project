<?php
session_start();
require_once 'config.php';

$currentUser = $_SESSION['username'] ?? '';
if ($currentUser === '') {
    header('Location: index.php');
    exit();
}

function resolveImagePath($imageName) {
    if (empty($imageName)) return '';
    $candidatePaths = [
        UPLOAD_DIR . $imageName,
        $imageName,
        UPLOAD_DIR . basename($imageName),
    ];
    foreach ($candidatePaths as $path) {
        if ($path !== '' && file_exists($path)) return $path;
    }
    return $candidatePaths[0];
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
$errorMessage = '';

if ($id <= 0) {
    $errorMessage = 'Invalid artwork ID.';
} else {
    // Only fetch if owner matches current user
    $sql = "SELECT * FROM Artdata WHERE ArtID = ? AND owner = ?";
    $veiwstmt = $conn->prepare($sql);
    $veiwstmt->bind_param("is", $id, $currentUser);
    $veiwstmt->execute();
    $result = $veiwstmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        $errorMessage = 'Artwork not found or you do not have permission.';
    } else {
        $imagePath = resolveImagePath($row['image_name'] ?? '');
        $currentID = $row['ArtID'];

        // Previous (only within this user's collection)
        $prevStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE owner = ? AND ArtID < ? ORDER BY ArtID DESC LIMIT 1");
        $prevStmt->bind_param("si", $currentUser, $currentID);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result();
        $prev = $prevResult->fetch_assoc();

        // Next (only within this user's collection)
        $nextStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE owner = ? AND ArtID > ? ORDER BY ArtID ASC LIMIT 1");
        $nextStmt->bind_param("si", $currentUser, $currentID);
        $nextStmt->execute();
        $nextResult = $nextStmt->get_result();
        $next = $nextResult->fetch_assoc();
    }
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
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
    <div class="main-content">
        <div class="container mt-5 position-relative">
            <a href="Artwork.php" class="close-btn">&times;</a>
            <?php if (!$row): ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
                <div class="row align-items-center g-4">
                    <div class="col-md-7">
                        <img
                            src="<?php echo htmlspecialchars($imagePath); ?>"
                            class="big-image"
                            alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                    </div>
                    <div class="col-md-5">
                        <h1><?php echo htmlspecialchars($row['ArtName']); ?></h1>
                        <p class="text-muted">
                            <strong>ID:</strong> <?php echo htmlspecialchars($row['ArtID']); ?>
                        </p>
                        <p><?php echo autolink($row['ArtDes']); ?></p>
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
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>