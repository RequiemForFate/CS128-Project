<?php
session_start();
require_once 'config.php';

$viewUser = isset($_GET['user']) ? trim($_GET['user']) : '';
if ($viewUser === '') {
    header('Location: index.php');
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("DB error");

$stmt = $conn->prepare("SELECT name, display_name, profile_picture, gallery_name, banner_image, bio_description, show_gallery_on_bio, social_links FROM users WHERE name = ?");
$stmt->bind_param("s", $viewUser);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) {
    header('Location: index.php');
    exit();
}

$isOwner = isset($_SESSION['username']) && $_SESSION['username'] === $viewUser;
$displayName = !empty($profile['display_name']) ? $profile['display_name'] : $profile['name'];

$profilePic = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';
if (!empty($profile['profile_picture']) && file_exists(UPLOAD_DIR . $profile['profile_picture'])) {
    $profilePic = UPLOAD_DIR . htmlspecialchars($profile['profile_picture']);
}

$banner = (!empty($profile['banner_image']) && file_exists(UPLOAD_DIR . $profile['banner_image']))
    ? UPLOAD_DIR . htmlspecialchars($profile['banner_image'])
    : 'images/banner.jpg';

$showGallery = $profile['show_gallery_on_bio'] ?? 1;

// Decode social links
$socialLinks = [];
if (!empty($profile['social_links'])) {
    $socialLinks = json_decode($profile['social_links'], true);
    if (!is_array($socialLinks)) {
        $socialLinks = [];
    }
}

$artworks = [];
if ($showGallery) {
    $artStmt = $conn->prepare("SELECT * FROM Artdata WHERE owner = ? AND isPublic = 1 AND isAnonymous = 0 ORDER BY created_at DESC");
    $artStmt->bind_param("s", $viewUser);
    $artStmt->execute();
    $artworks = $artStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $artStmt->close();
}
$conn->close();

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
    <title><?php echo htmlspecialchars($displayName); ?> – Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css?v=4" rel="stylesheet">
    <style>
        .profile-avatar { width: 110px; height: 110px; object-fit: cover; margin-top: -65px; }
        .bio-text { white-space: pre-wrap; }
        .gallery-grid .card { border: none; background: #fff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: 0.2s; }
        .gallery-grid .card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .gallery-grid img, .gallery-grid video { height: 200px; object-fit: cover; border-radius: 12px 12px 0 0; width:100%; background:#000; }
        .action-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin: 20px 0; }
        .social-link-btn {
            display: inline-block;
            margin: 4px;
            padding: 6px 16px;
            border-radius: 20px;
            background: #e9ecef;
            color: #0d0d0d;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .social-link-btn:hover {
            background: #2b00ffa3;
            color: #fff;
        }
        .social-links-container {
            margin: 12px 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="main-content">
        <?php include 'banner.php'; ?>
        <div class="container py-4">
            <div class="card shadow-sm mx-auto" style="max-width:600px;">
                <img src="<?php echo htmlspecialchars($banner); ?>" class="card-img-top" style="height:160px; object-fit:cover;">
                <div class="card-body text-center">
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" class="rounded-circle border border-3 border-light shadow profile-avatar">
                    <h3 class="mt-3"><?php echo htmlspecialchars($displayName); ?></h3>
                    <?php if (!empty($profile['bio_description'])): ?>
                        <div class="bio-text text-start"><?php echo nl2br(autoLink($profile['bio_description'])); ?></div>
                    <?php endif; ?>

                    <!-- Social Links -->
                    <?php if (!empty($socialLinks)): ?>
                        <div class="social-links-container">
                            <?php foreach ($socialLinks as $link): ?>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer" class="social-link-btn">
                                    <?php echo htmlspecialchars($link['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isOwner): ?>
                        <a href="profile.php" class="btn btn-primary mt-3 w-100">✏️ Edit Profile</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($showGallery): ?>
                <div class="mt-5">
                    <h3 class="text-center mb-3">Public Gallery</h3>
                    <?php if (count($artworks) === 0): ?>
                        <p class="text-muted text-center">
                            <?php echo $isOwner ? 'You haven’t shared any public artworks yet.' : 'This user has no public artworks.'; ?>
                        </p>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4 gallery-grid">
                            <?php foreach ($artworks as $art): 
                                $isVideo = isVideoFile($art['image_name']);
                                $mediaPath = UPLOAD_DIR . htmlspecialchars($art['image_name']);
                            ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <a href="view_art_public.php?id=<?php echo $art['ArtID']; ?>&owner=<?php echo urlencode($viewUser); ?>">
                                            <?php if ($isVideo): ?>
                                                <video controls style="width:100%; height:200px; object-fit:cover; background:#000;">
                                                    <source src="<?php echo $mediaPath; ?>" type="video/<?php echo pathinfo($art['image_name'], PATHINFO_EXTENSION); ?>">
                                                    Your browser does not support the video tag.
                                                </video>
                                            <?php else: ?>
                                                <img src="<?php echo $mediaPath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($art['ArtName']); ?>">
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted mt-4">This user keeps their gallery private.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>