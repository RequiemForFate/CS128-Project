<?php
session_start();
require_once 'config.php';

$viewUser = isset($_GET['user']) ? trim($_GET['user']) : '';
if ($viewUser === '') {
    header('Location: Artwork.php');
    exit();
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Query for the profile owner (the one being viewed)
$userStmt = $conn->prepare("SELECT name, display_name, profile_picture, gallery_name, banner_image, bio_description, show_gallery_on_bio FROM users WHERE name = ?");
$userStmt->bind_param("s", $viewUser);
$userStmt->execute();
$userResult = $userStmt->get_result();
$profileData = $userResult->fetch_assoc();   // <-- renamed variable
$userStmt->close();

if (!$profileData) {
    header('Location: Artwork.php');
    exit();
}

$isOwner = isset($_SESSION['username']) && $_SESSION['username'] === $viewUser;

// Profile picture
$profileImageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';
if (!empty($profileData['profile_picture']) && file_exists(UPLOAD_DIR . $profileData['profile_picture'])) {
    $profileImageSrc = UPLOAD_DIR . htmlspecialchars($profileData['profile_picture']);
}

// Banner image
$bannerImageSrc = !empty($profileData['banner_image']) && file_exists(UPLOAD_DIR . $profileData['banner_image'])
    ? UPLOAD_DIR . htmlspecialchars($profileData['banner_image'])
    : 'images/banner.jpg';

$galleryName = !empty($profileData['gallery_name']) ? htmlspecialchars($profileData['gallery_name']) : htmlspecialchars($viewUser) . "'s Gallery";
$displayName = !empty($profileData['display_name']) ? htmlspecialchars($profileData['display_name']) : htmlspecialchars($viewUser);

$showGallery = $profileData['show_gallery_on_bio'] ?? 1;
$artworks = [];
if ($showGallery) {
    $artStmt = $conn->prepare("SELECT * FROM Artdata WHERE owner = ? AND isPublic = 1 ORDER BY ArtID DESC");
    $artStmt->bind_param("s", $viewUser);
    $artStmt->execute();
    $artResult = $artStmt->get_result();
    $artworks = $artResult->fetch_all(MYSQLI_ASSOC);
    $artStmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $displayName; ?> - Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css?v=4" rel="stylesheet">
   <style>
    .full-width-container {
        padding: 0;
        margin: 0;
        max-width: 100%;
    }
    .profile-card-wrapper {
        max-width: 600px;
        margin: 0 auto;
    }
    .full-width-card {
        margin-left: 0;
        margin-right: 0;
        border-radius: 0;
        box-shadow: none;
        border-left: none;
        border-right: none;
    }
    .full-width-card .card-body {
        padding: 15px 0;
    }
    @media (max-width: 576px) {
        .full-width-card .card-body {
            padding: 10px 0;
        }
    }
    .gallery-card {
        border-radius: 24px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 20px 45px rgba(15, 30, 65, 0.08);
        padding: 10px;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .gallery-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 24px 55px rgba(15, 30, 65, 0.12);
    }
    .square-link {
        display: block;
        text-decoration: none;
    }
    .square-box {
        position: relative;
        width: 100%;
        padding-top: 100%;
        overflow: hidden;
        border-radius: 12px;
        background: #f0f0f0;
    }
    .square-box img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
        transition: transform 0.2s ease;
    }
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        margin: 15px 0 20px 0;
    }
    .action-buttons .btn {
        min-width: 180px;
        padding: 10px 20px;
        font-size: 1rem;
    }
</style>
</head>
<body>
    <?php include 'banner.php'; ?>
    <?php include 'menu.php'; ?>

    <div class="main-content full-width-container">
        <div class="py-4">
            <!-- Profile Card -->
            <div class="profile-card-wrapper mb-4">
                <div class="card shadow-sm">
                    <img src="<?php echo htmlspecialchars($bannerImageSrc); ?>" 
                        class="card-img-top" 
                        alt="Profile banner" 
                        style="height: 180px; object-fit: cover;">
                    <div class="card-body text-center">
                        <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" 
                            alt="Profile picture" 
                            class="rounded-circle border border-3 border-light shadow" 
                            style="width: 110px; height: 110px; object-fit: cover; margin-top: -65px;">
                        <h3 class="mt-3 mb-1"><?php echo $displayName; ?></h3>
                        <p class="text-muted mb-0"><?php echo $galleryName; ?></p>
                        
                        <?php if (!empty($profileData['bio_description'])): ?>
                            <div class="mt-3 text-start">
                                <?php echo nl2br(htmlspecialchars($profileData['bio_description'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($isOwner): ?>
                            <!-- Edit Profile button – full width, centered -->
                            <a href="profile.php" class="btn btn-primary mt-3 w-100">✏️ Edit Profile</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Gallery Section -->
            <?php if ($showGallery): ?>
                <div class="card full-width-card">
                    <div class="card-body">
                        <h3 class="card-title mb-3 text-center">Public Gallery</h3>
                        
                        <?php if ($isOwner): ?>
                            <!-- Action buttons: side‑by‑side and centered -->
                            <div class="action-buttons">
                                <a href="imageform.php" class="btn btn-success">📤 Upload New Artwork</a>
                                <a href="Archive.php" class="btn btn-secondary">📂 Manage My Archive</a>
                            </div>
                        <?php endif; ?>

                        <?php if (count($artworks) === 0): ?>
                            <p class="text-muted text-center">
                                <?php if ($isOwner): ?>
                                    You haven't shared any public artworks yet. 
                                    <a href="imageform.php">Upload your first piece!</a>
                                <?php else: ?>
                                    This user hasn't shared any public artworks yet.
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 justify-content-center">
                                <?php foreach ($artworks as $row): ?>
                                    <div class="col">
                                        <div class="gallery-card">
                                            <a href="view_art.php?id=<?php echo $row['ArtID']; ?>">
                                                <img src="<?php echo UPLOAD_DIR . htmlspecialchars($row['image_name']); ?>" 
                                                    class="gallery-img" 
                                                    alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center text-muted">
                    <p>This user has chosen not to display their gallery publicly.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
