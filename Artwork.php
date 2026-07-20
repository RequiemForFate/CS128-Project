<?php
session_start();
require_once 'config.php';

$currentUser = $_SESSION['username'] ?? '';
if ($currentUser === '') {
    header('Location: login.php');
    exit();
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$searchTerm = '';
$whereClause = "WHERE owner = ?";
$params = [$currentUser];
$types = "s";

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    if (is_numeric($searchTerm)) {
        $whereClause .= " AND ArtID = ?";
        $params[] = intval($searchTerm);
        $types .= "i";
    } else {
        $searchTermEscaped = $conn->real_escape_string($searchTerm);
        $whereClause .= " AND ArtName LIKE ?";
        $params[] = "%$searchTermEscaped%";
        $types .= "s";
    }
}

// Helper to check if file is video
function isVideoFile($fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv']);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Gallery</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link href="style.css?v=3" rel="stylesheet">
    </head>
    <body>
        <div class="py-1">
            <?php include 'banner.php'; ?>
            <?php include 'menu.php'; ?>
            <?php
                $sql = "SELECT * FROM Artdata $whereClause ORDER BY ArtID DESC";
                $artstmt = $conn->prepare($sql);
                $artstmt->bind_param($types, ...$params);
                $artstmt->execute();
                $result = $artstmt->get_result();
            ?>
            <div class="main-content">
                <div class="container py-4">

                    <div class="row mb-4">
                        <div class="col-md-6 mx-auto">
                            <form method="get" class="d-flex" role="search">
                                <input
                                    type="text"
                                    name="search"
                                    class="form-control me-2"
                                    placeholder="Search by Name or ID..."
                                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                                    aria-label="Search">
                                <button class="btn btn-primary" type="submit">⌕</button>
                                <?php if ($searchTerm !== ''): ?>
                                    <a href="Artwork.php" class="btn btn-outline-secondary ms-2">⟳</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <?php if ($result->num_rows === 0): ?>
                        <div class="alert alert-info text-center">
                            <?php if ($searchTerm !== ''): ?>
                                No artwork found matching “<?php echo htmlspecialchars($searchTerm); ?>”.
                            <?php else: ?>
                                You haven't uploaded any artwork yet.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="gallery row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                            <?php while($row = mysqli_fetch_assoc($result)) {
                                $badge = $row['isPublic'] ? '<span class="badge bg-success">Public</span>' : '<span class="badge bg-secondary">Private</span>';
                                $mediaPath = UPLOAD_DIR . htmlspecialchars($row['image_name']);
                                $isVideo = isVideoFile($row['image_name']);
                            ?>
                            <div class="gallery-card">
                                <a href="view_art.php?id=<?php echo $row['ArtID']; ?>">
                                    <?php if ($isVideo): ?>
                                        <video controls style="width:100%; height:260px; object-fit:cover; background:#000; border-radius:12px;">
                                            <source src="<?php echo $mediaPath; ?>" type="video/<?php echo pathinfo($row['image_name'], PATHINFO_EXTENSION); ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php else: ?>
                                        <img src="<?php echo $mediaPath; ?>" class="gallery-img" alt="<?php echo htmlspecialchars($row['ArtName'] ?: 'Artwork'); ?>">
                                    <?php endif; ?>
                                </a>
                                <div class="p-2">
                                    <span><?php echo htmlspecialchars($row['ArtName']); ?></span>
                                    <?php echo $badge; ?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php
        $artstmt->close();
        $conn->close();
    ?>
    </body>
</html>