<?php
session_start();
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentUsername = $_SESSION['username'] ?? '';
if ($currentUsername === '') {
    header('Location: login.php');
    exit();
}

function saveUploadedImage($file, $prefix) {
    if (empty($file['name']) || !is_uploaded_file($file['tmp_name'])) {
        return '';
    }
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return '';
    }
    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = __DIR__ . '/' . UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return '';
    }
    return $filename;
}

function deleteFileIfExists($filePath) {
    if ($filePath && file_exists($filePath)) {
        unlink($filePath);
    }
}

// ---------- ACCOUNT DELETION ----------
if (isset($_POST['delete_account']) && $_POST['delete_account'] === 'confirm') {
    $artStmt = $conn->prepare("SELECT image_name FROM Artdata WHERE owner = ?");
    $artStmt->bind_param("s", $currentUsername);
    $artStmt->execute();
    $artResult = $artStmt->get_result();
    while ($row = $artResult->fetch_assoc()) {
        if (!empty($row['image_name'])) {
            deleteFileIfExists(__DIR__ . '/' . UPLOAD_DIR . $row['image_name']);
        }
    }
    $artStmt->close();

    $delArtStmt = $conn->prepare("DELETE FROM Artdata WHERE owner = ?");
    $delArtStmt->bind_param("s", $currentUsername);
    $delArtStmt->execute();
    $delArtStmt->close();

    $userStmt = $conn->prepare("SELECT profile_picture, banner_image FROM users WHERE name = ?");
    $userStmt->bind_param("s", $currentUsername);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    if ($userRow = $userResult->fetch_assoc()) {
        if (!empty($userRow['profile_picture'])) {
            deleteFileIfExists(__DIR__ . '/' . UPLOAD_DIR . $userRow['profile_picture']);
        }
        if (!empty($userRow['banner_image'])) {
            deleteFileIfExists(__DIR__ . '/' . UPLOAD_DIR . $userRow['banner_image']);
        }
    }
    $userStmt->close();

    $delUserStmt = $conn->prepare("DELETE FROM users WHERE name = ?");
    $delUserStmt->bind_param("s", $currentUsername);
    $delUserStmt->execute();
    $delUserStmt->close();

    session_destroy();
    header('Location: index.php?deleted=1');
    exit();
}
// ----------------------------------------

// Fetch user data (gallery_name removed)
$profileData = [
    'name' => $currentUsername,
    'display_name' => '',
    'profile_picture' => '',
    'banner_image' => '',
    'bio_description' => '',
    'show_gallery_on_bio' => 1,
    'social_links' => ''
];

$stmt = $conn->prepare("SELECT name, display_name, profile_picture, banner_image, bio_description, show_gallery_on_bio, social_links FROM users WHERE name = ?");
$stmt->bind_param("s", $currentUsername);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profileData = [
        'name' => $row['name'] ?? $currentUsername,
        'display_name' => $row['display_name'] ?? '',
        'profile_picture' => $row['profile_picture'] ?? '',
        'banner_image' => $row['banner_image'] ?? '',
        'bio_description' => $row['bio_description'] ?? '',
        'show_gallery_on_bio' => $row['show_gallery_on_bio'] ?? 1,
        'social_links' => $row['social_links'] ?? ''
    ];
}
$stmt->close();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_account'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $newDisplayName = trim($_POST['display_name'] ?? '');
    $bioDescription = trim($_POST['bio_description'] ?? '');
    $showGalleryOnBio = isset($_POST['show_gallery_on_bio']) ? 1 : 0;

    // Process social links
    $socialLinksRaw = trim($_POST['social_links'] ?? '');
    $socialLinks = [];
    if (!empty($socialLinksRaw)) {
        $lines = explode("\n", $socialLinksRaw);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line, 2);
            if (count($parts) === 2) {
                $label = trim($parts[0]);
                $url = trim($parts[1]);
                if (!empty($label) && !empty($url)) {
                    $socialLinks[] = ['label' => $label, 'url' => $url];
                }
            }
        }
    }
    $socialLinksJSON = !empty($socialLinks) ? json_encode($socialLinks) : null;

    // Validate username
    if ($newUsername === '') {
        $message = 'Username is required.';
    } elseif (strpos($newUsername, ' ') !== false) {
        $message = 'Username cannot contain spaces.';
    } else {
        $checkStmt = $conn->prepare("SELECT name FROM users WHERE name = ? AND name != ?");
        $checkStmt->bind_param("ss", $newUsername, $currentUsername);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = 'That username is already taken.';
        } else {
            // File uploads
            $profilePicture = $profileData['profile_picture'];
            $uploadedProfilePicture = saveUploadedImage($_FILES['profile_picture'] ?? [], 'profile');
            if ($uploadedProfilePicture !== '') {
                $profilePicture = $uploadedProfilePicture;
            }

            $bannerImage = $profileData['banner_image'];
            $uploadedBannerImage = saveUploadedImage($_FILES['banner_image'] ?? [], 'banner');
            if ($uploadedBannerImage !== '') {
                $bannerImage = $uploadedBannerImage;
            }

            $conn->begin_transaction();
            try {
                // Update without gallery_name
                $updateStmt = $conn->prepare("UPDATE users SET name = ?, display_name = ?, profile_picture = ?, banner_image = ?, bio_description = ?, show_gallery_on_bio = ?, social_links = ? WHERE name = ?");
                $updateStmt->bind_param("sssssiss", $newUsername, $newDisplayName, $profilePicture, $bannerImage, $bioDescription, $showGalleryOnBio, $socialLinksJSON, $currentUsername);
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update user: " . $updateStmt->error);
                }
                $updateStmt->close();

                if ($newUsername !== $currentUsername) {
                    $updateArtStmt = $conn->prepare("UPDATE Artdata SET owner = ? WHERE owner = ?");
                    $updateArtStmt->bind_param("ss", $newUsername, $currentUsername);
                    if (!$updateArtStmt->execute()) {
                        throw new Exception("Failed to update artwork ownership: " . $updateArtStmt->error);
                    }
                    $updateArtStmt->close();
                }

                $conn->commit();

                $_SESSION['username'] = $newUsername;
                $currentUsername = $newUsername;
                $profileData['name'] = $newUsername;
                $profileData['display_name'] = $newDisplayName;
                $profileData['profile_picture'] = $profilePicture;
                $profileData['banner_image'] = $bannerImage;
                $profileData['bio_description'] = $bioDescription;
                $profileData['show_gallery_on_bio'] = $showGalleryOnBio;
                $profileData['social_links'] = $socialLinksJSON;

                header('Location: bios.php?user=' . urlencode($newUsername));
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $message = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }
}

// Prepare image sources
if (!empty($profileData['profile_picture']) && file_exists(__DIR__ . '/' . UPLOAD_DIR . $profileData['profile_picture'])) {
    $profileImageSrc = UPLOAD_DIR . htmlspecialchars($profileData['profile_picture']);
} else {
    $profileImageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';
}

$bannerImageSrc = !empty($profileData['banner_image'])
    ? UPLOAD_DIR . htmlspecialchars($profileData['banner_image'])
    : 'images/banner.jpg';

$displayName = $profileData['display_name'];
$bioDesc = $profileData['bio_description'];
$showGallery = $profileData['show_gallery_on_bio'];

// Clean the bio for editing (remove HTML tags and decode entities)
$bioDescPlain = html_entity_decode(strip_tags($bioDesc), ENT_QUOTES, 'UTF-8');

// For the profile card preview, we want clickable links (autoLink)
$bioDisplay = autoLink($bioDesc);

$socialLinksDisplay = '';
if (!empty($profileData['social_links'])) {
    $links = json_decode($profileData['social_links'], true);
    if (is_array($links)) {
        $lines = [];
        foreach ($links as $link) {
            $lines[] = $link['label'] . '|' . $link['url'];
        }
        $socialLinksDisplay = implode("\n", $lines);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css?v=3" rel="stylesheet">
</head>
<body>
    <div class="container py-1">
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>
    </div>
    <div class="main-content">
        <div class="container py-4">
            <!-- rest of profile.php -->
            <?php if ($message !== ''): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="row g-4">
                <!-- Profile Card -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <img src="<?php echo htmlspecialchars($bannerImageSrc); ?>" class="card-img-top" alt="Banner" style="height: 180px; object-fit: cover;">
                        <div class="card-body text-center">
                            <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" alt="Profile" class="rounded-circle border border-3 border-light shadow" style="width: 110px; height: 110px; object-fit: cover; margin-top: -65px;">
                            <h3 class="mt-3 mb-1"><?php echo htmlspecialchars($profileData['name']); ?></h3>
                            <?php if (!empty($displayName)): ?>
                                <p class="text-muted">Display name: <?php echo htmlspecialchars($displayName); ?></p>
                            <?php endif; ?>
                            <!-- Gallery name removed -->
                            <?php if (!empty($bioDesc)): ?>
                                <div class="mt-2 text-start"><?php echo nl2br($bioDisplay); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Edit Profile</h3>
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username (no spaces)</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($profileData['name']); ?>" required>
                                    <small class="text-muted">Spaces are not allowed.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Display Name</label>
                                    <input type="text" id="display_name" name="display_name" class="form-control" value="<?php echo htmlspecialchars($displayName); ?>" placeholder="Your public name">
                                    <small class="text-muted">This name will appear on your public profile.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="bio_description" class="form-label">Bio / Description</label>
                                    <textarea id="bio_description" name="bio_description" class="form-control" rows="4" placeholder="Tell people about yourself..."><?php echo htmlspecialchars($bioDescPlain); ?></textarea>
                                </div>

                                <!-- Social Links -->
                                <div class="mb-3">
                                    <label for="social_links" class="form-label">Social / Contact Links</label>
                                    <textarea id="social_links" name="social_links" class="form-control" rows="4" placeholder="One per line: Label|URL&#10;Example: Twitter|https://twitter.com/username"><?php echo htmlspecialchars($socialLinksDisplay); ?></textarea>
                                    <small class="text-muted">Format: Label|URL (one per line). These will appear as clickable buttons on your public profile.</small>
                                </div>

                                <!-- Gallery name field removed -->

                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Profile Picture</label>
                                    <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                                </div>
                                <div class="mb-4">
                                    <label for="banner_image" class="form-label">Banner Image</label>
                                    <input type="file" id="banner_image" name="banner_image" class="form-control" accept="image/*">
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="show_gallery_on_bio" id="show_gallery_on_bio" value="1" <?php echo $showGallery ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_gallery_on_bio">Show public gallery on my bio page</label>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100">Save Profile</button>
                            </form>

                            <hr class="my-4">
                            <div class="text-center">
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone. All your artwork and data will be permanently removed.')">
                                    <input type="hidden" name="delete_account" value="confirm">
                                    <button type="submit" class="btn btn-danger btn-lg w-100">🗑️ Delete My Account</button>
                                </form>
                                <p class="text-muted small mt-2">This will remove all your artworks and profile data.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>