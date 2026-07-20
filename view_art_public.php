<?php
session_start();
require_once 'config.php';

$currentUser = $_SESSION['username'] ?? '';

function resolveMediaPath($fileName) {
    if (empty($fileName)) return '';
    $candidatePaths = [
        UPLOAD_DIR . $fileName,
        $fileName,
        UPLOAD_DIR . basename($fileName),
    ];
    foreach ($candidatePaths as $path) {
        if ($path !== '' && file_exists($path)) return $path;
    }
    return $candidatePaths[0];
}

// Helper to check if file is video
function isVideoFile($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv']);
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ownerFilter = isset($_GET['owner']) ? trim($_GET['owner']) : null;
$row = null;
$errorMessage = '';

if ($id <= 0) {
    $errorMessage = 'Invalid artwork ID.';
} else {
    $sql = "SELECT * FROM Artdata WHERE ArtID = ? AND isPublic = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        $errorMessage = 'Artwork not found or it is not public.';
    } else {
        $mediaPath = resolveMediaPath($row['image_name'] ?? '');
        if (!file_exists($mediaPath)) {
            $mediaPath = 'images/missing-image.png';
        }
        $currentID = $row['ArtID'];
        $owner = $row['owner'];

        // Build WHERE clause for navigation
        $whereClause = "isPublic = 1";
        $params = [];
        $types = "";

        if ($ownerFilter !== null && $ownerFilter !== '') {
            $whereClause .= " AND owner = ?";
            $params[] = $ownerFilter;
            $types .= "s";
        }

        // Previous
        $prevSql = "SELECT ArtID FROM Artdata WHERE ArtID < ? AND $whereClause ORDER BY ArtID DESC LIMIT 1";
        $prevStmt = $conn->prepare($prevSql);
        if (!empty($params)) {
            $prevStmt->bind_param("i" . $types, $currentID, ...$params);
        } else {
            $prevStmt->bind_param("i", $currentID);
        }
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result();
        $prev = $prevResult->fetch_assoc();
        $prevStmt->close();

        // Next
        $nextSql = "SELECT ArtID FROM Artdata WHERE ArtID > ? AND $whereClause ORDER BY ArtID ASC LIMIT 1";
        $nextStmt = $conn->prepare($nextSql);
        if (!empty($params)) {
            $nextStmt->bind_param("i" . $types, $currentID, ...$params);
        } else {
            $nextStmt->bind_param("i", $currentID);
        }
        $nextStmt->execute();
        $nextResult = $nextStmt->get_result();
        $next = $nextResult->fetch_assoc();
        $nextStmt->close();
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
            <?php 
                $backLink = ($ownerFilter !== null && $ownerFilter !== '') 
                    ? 'bios.php?user=' . urlencode($ownerFilter) 
                    : 'index.php';
            ?>
            <a href="<?php echo $backLink; ?>" class="close-btn">&times;</a>
            <?php if (!$row): ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: 
                $isVideo = isVideoFile($mediaPath);
            ?>
                <div class="row align-items-center g-4">
                    <div class="col-md-7">
                        <?php if ($isVideo): ?>
                            <video controls class="big-image" style="width:100%; max-height:80vh; background:#000;">
                                <source src="<?php echo htmlspecialchars($mediaPath); ?>" type="video/<?php echo pathinfo($mediaPath, PATHINFO_EXTENSION); ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($mediaPath); ?>" class="big-image" alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <h1><?php echo htmlspecialchars($row['ArtName']); ?></h1>
                        <p class="text-muted">
                            <strong>Artist:</strong> 
                            <?php if ($row['isAnonymous']): ?>
                                Anonymous
                            <?php else: ?>
                                <a href="bios.php?user=<?php echo urlencode($row['owner']); ?>">
                                    <?php 
                                        $artistStmt = $conn->prepare("SELECT display_name, name FROM users WHERE name = ?");
                                        $artistStmt->bind_param("s", $row['owner']);
                                        $artistStmt->execute();
                                        $artistResult = $artistStmt->get_result();
                                        $artist = $artistResult->fetch_assoc();
                                        $artistStmt->close();
                                        $display = !empty($artist['display_name']) ? $artist['display_name'] : $artist['name'];
                                        echo htmlspecialchars($display);
                                    ?>
                                </a>
                            <?php endif; ?>
                        </p>
                        <p><?php echo autoLink($row['ArtDes']); ?></p>
                        <div class="d-flex justify-content-between mt-5 gap-2">
                            <?php if ($prev) { 
                                $prevLink = 'view_art_public.php?id=' . htmlspecialchars($prev['ArtID']);
                                if ($ownerFilter !== null && $ownerFilter !== '') {
                                    $prevLink .= '&owner=' . urlencode($ownerFilter);
                                }
                            ?>
                                <a href="<?php echo $prevLink; ?>" class="btn btn-dark">
                                    ← Previous
                                </a>
                            <?php } else { ?>
                                <div></div>
                            <?php } ?>
                            <?php if ($next) { 
                                $nextLink = 'view_art_public.php?id=' . htmlspecialchars($next['ArtID']);
                                if ($ownerFilter !== null && $ownerFilter !== '') {
                                    $nextLink .= '&owner=' . urlencode($ownerFilter);
                                }
                            ?>
                                <a href="<?php echo $nextLink; ?>" class="btn btn-dark">
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