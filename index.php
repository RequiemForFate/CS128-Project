<?php
session_start();
require_once 'config.php';

// Helper to check if file is video
function isVideoFile($fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv']);
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("DB error");

$searchTerm = '';
$whereClause = "WHERE a.isPublic = 1";

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    $escaped = $conn->real_escape_string($searchTerm);
    $whereClause .= " AND (
        a.ArtName LIKE '%$escaped%'
        OR a.ArtDes LIKE '%$escaped%'
        OR u.name LIKE '%$escaped%'
        OR u.display_name LIKE '%$escaped%'
    )";
}

$sql = "SELECT a.*, u.profile_picture, u.display_name, u.name as username 
        FROM Artdata a 
        JOIN users u ON a.owner = u.name 
        $whereClause 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
$artworks = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Art Portfolio – Public Gallery</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css?v=4" rel="stylesheet">
    <style>
        .gallery-grid .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: 0.2s;
            height: 100%;
            overflow: hidden;
        }
        .gallery-grid .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .card-img-top {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f0f0f0;
        }
        .card-body {
            padding: 16px;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: transparent;
            border-bottom: 1px solid #eee;
        }
        .card-header .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .card-header .username {
            font-weight: bold;
            color: #0d0d0d;
            text-decoration: none;
            font-size: 1rem;
        }
        .card-header .username:hover { text-decoration: underline; }
        .card-header .anon {
            color: #6c757d;
            font-weight: normal;
        }
        .card-header .time {
            margin-left: auto;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .card-text {
            margin-top: 8px;
            white-space: pre-wrap;
            font-size: 0.95rem;
        }
        .card-footer {
            background: transparent;
            border-top: 1px solid #eee;
            padding: 10px 16px;
            font-size: 0.85rem;
        }
        @media (max-width: 767px) {
            .card-img-top { height: 180px; }
        }
    </style>
</head>
<body>
    <?php include 'banner.php'; ?>
    <?php include 'menu.php'; ?>
    <div class="main-content">
        <div class="container py-4">
            <h2 class="text-center mb-4">🎨 Public Gallery</h2><br>

            <!-- Search Form -->
            <div class="row mb-4">
                <div class="col-md-6 mx-auto">
                    <form method="get" class="d-flex" role="search">
                        <input
                            type="text"
                            name="search"
                            class="form-control me-2"
                            placeholder="Search by artwork, artist, or description..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                            aria-label="Search">
                        <button class="btn btn-primary" type="submit">⌕</button>
                        <?php if ($searchTerm !== ''): ?>
                            <a href="index.php" class="btn btn-outline-secondary ms-2">⟳</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Gallery -->
            <?php if (count($artworks) === 0): ?>
                <p class="text-muted text-center">
                    <?php if ($searchTerm !== ''): ?>
                        No public artworks match “<?php echo htmlspecialchars($searchTerm); ?>”.
                    <?php else: ?>
                        No public artworks yet.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 gallery-grid">
                    <?php foreach ($artworks as $art): 
                        $mediaPath = UPLOAD_DIR . htmlspecialchars($art['image_name']);
                        $isVideo = isVideoFile($art['image_name']);
                    ?>
                        <div class="col">
                            <div class="card h-100">
                                <!-- Header: avatar + name + time -->
                                <div class="card-header">
                                    <?php if ($art['isAnonymous']): ?>
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='32' fill='%236c757d'/%3E%3Ccircle cx='32' cy='24' r='14' fill='%23fff'/%3E%3Cellipse cx='32' cy='48' rx='20' ry='16' fill='%23fff'/%3E%3C/svg%3E" class="avatar" alt="Anonymous">
                                        <span class="anon">Anonymous</span>
                                    <?php else: ?>
                                        <?php 
                                            $avatar = (!empty($art['profile_picture']) && file_exists(UPLOAD_DIR . $art['profile_picture']))
                                                ? UPLOAD_DIR . htmlspecialchars($art['profile_picture'])
                                                : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';
                                            $display = !empty($art['display_name']) ? $art['display_name'] : $art['username'];
                                        ?>
                                        <img src="<?php echo htmlspecialchars($avatar); ?>" class="avatar" alt="Avatar">
                                        <a href="bios.php?user=<?php echo urlencode($art['username']); ?>" class="username">
                                            <?php echo htmlspecialchars($display); ?>
                                        </a>
                                    <?php endif; ?>
                                    <span class="time"><?php echo date('M j, Y', strtotime($art['created_at'])); ?></span>
                                </div>

                                <!-- Media -->
                                <a href="view_art_public.php?id=<?php echo $art['ArtID']; ?>">
                                    <?php if ($isVideo): ?>
                                        <video controls style="width:100%; height:220px; object-fit:cover; background:#000;">
                                            <source src="<?php echo $mediaPath; ?>" type="video/<?php echo pathinfo($art['image_name'], PATHINFO_EXTENSION); ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php else: ?>
                                        <img src="<?php echo $mediaPath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($art['ArtName']); ?>">
                                    <?php endif; ?>
                                </a>

                                <!-- Body: name + description -->
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($art['ArtName']); ?></h5>
                                    <p class="card-text"><?php echo nl2br(autolink($art['ArtDes'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>