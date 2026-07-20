<?php
session_start();
require_once 'config.php';

$currentUser = $_SESSION['username'] ?? '';
if ($currentUser === '') {
    header('Location: login.php');
    exit();
}

<<<<<<< HEAD
function resolveMediaPath($fileName) {
    if (empty($fileName)) return '';
=======
if ($currentUser === '') {
    header('Location: login.php');
    exit();
}

function resolveImagePath($imageName) {
    if (empty($imageName)) return '';
>>>>>>> 68861ae35a251836cd24e898e11ae4d5f84d6c87
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

function isVideoFile($fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv']);
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
<<<<<<< HEAD
    $sql = "SELECT * FROM Artdata WHERE ArtID = ? AND owner = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id, $currentUser);
=======
    // --- Build query based on login status ---
    if ($currentUser !== '') {
        // Logged in: can see own private + all public
        $sql = "SELECT * FROM Artdata
        WHERE ArtID = ?
        AND owner = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id, $currentUser);
    } 
>>>>>>> 68861ae35a251836cd24e898e11ae4d5f84d6c87
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        $errorMessage = 'Artwork not found or you do not own it.';
    } else {
        $mediaPath = resolveMediaPath($row['image_name'] ?? '');
        if (!file_exists($mediaPath)) {
            $mediaPath = 'images/missing-image.png';
        }
        $currentID = $row['ArtID'];

<<<<<<< HEAD
        $prevStmt = $conn->prepare("
            SELECT ArtID FROM Artdata 
            WHERE owner = ? AND ArtID < ? 
            ORDER BY ArtID DESC LIMIT 1
        ");
        $prevStmt->bind_param("si", $currentUser, $currentID);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result();
        $prev = $prevResult->fetch_assoc();

        $nextStmt = $conn->prepare("
            SELECT ArtID FROM Artdata 
            WHERE owner = ? AND ArtID > ? 
            ORDER BY ArtID ASC LIMIT 1
        ");
        $nextStmt->bind_param("si", $currentUser, $currentID);
        $nextStmt->execute();
        $nextResult = $nextStmt->get_result();
        $next = $nextResult->fetch_assoc();
=======
        // --- Previous / Next navigation (respects visibility rules) ---
        if ($currentUser !== '') {
            $prevStmt = $conn->prepare("
                SELECT ArtID FROM Artdata 
                WHERE ArtID < ?
                AND owner = ?
                ORDER BY ArtID DESC
                LIMIT 1
            ");
            $prevStmt->bind_param("is", $currentID, $currentUser);
            $prevStmt->execute();
            $prevResult = $prevStmt->get_result();
            $prev = $prevResult->fetch_assoc();

            $nextStmt = $conn->prepare("
                SELECT ArtID FROM Artdata 
                WHERE ArtID > ?
                AND owner = ?
                ORDER BY ArtID ASC
                LIMIT 1
            ");
            $nextStmt->bind_param("is", $currentID, $currentUser);
            $nextStmt->execute();
            $nextResult = $nextStmt->get_result();
            $next = $nextResult->fetch_assoc();
        } else {
            $prevStmt = $conn->prepare("
                SELECT ArtID FROM Artdata 
                WHERE ArtID < ? AND isPublic = 1 
                ORDER BY ArtID DESC LIMIT 1
            ");
            $prevStmt->bind_param("i", $currentID);
            $prevStmt->execute();
            $prevResult = $prevStmt->get_result();
            $prev = $prevResult->fetch_assoc();

            $nextStmt = $conn->prepare("
                SELECT ArtID FROM Artdata 
                WHERE ArtID > ? AND isPublic = 1 
                ORDER BY ArtID ASC LIMIT 1
            ");
            $nextStmt->bind_param("i", $currentID);
            $nextStmt->execute();
            $nextResult = $nextStmt->get_result();
            $next = $nextResult->fetch_assoc();
        }
>>>>>>> 68861ae35a251836cd24e898e11ae4d5f84d6c87
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
            <a href="Archive.php" class="close-btn">&times;</a>
            <?php if (!$row): ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: 
                $isVideo = isVideoFile($row['image_name']);
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
                            <strong>ID:</strong> <?php echo htmlspecialchars($row['ArtID']); ?><br>
                            <strong>Status:</strong> <?php echo $row['isPublic'] ? 'Public' : 'Private'; ?>
                        </p>
                        <p><?php echo autoLink($row['ArtDes']); ?></p>
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