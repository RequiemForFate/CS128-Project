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

/**
 * Save uploaded image (unchanged)
 */
function saveUploadedImage($file, $prefix) {
    if (empty($file['name']) || !is_uploaded_file($file['tmp_name'])) {
        return '';
    }
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
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

/**
 * Delete a file if it exists (used for cleanup)
 */
function deleteFileIfExists($filePath) {
    if ($filePath && file_exists($filePath)) {
        unlink($filePath);
    }
}

// ---------- ACCOUNT DELETION ----------
if (isset($_POST['delete_account']) && $_POST['delete_account'] === 'confirm') {
    // 1. Get all user's artworks to delete their images
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

    // 2. Delete all artwork records for this user
    $delArtStmt = $conn->prepare("DELETE FROM Artdata WHERE owner = ?");
    $delArtStmt->bind_param("s", $currentUsername);
    $delArtStmt->execute();
    $delArtStmt->close();

    // 3. Delete user's profile picture and banner images
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

    // 4. Delete the user record
    $delUserStmt = $conn->prepare("DELETE FROM users WHERE name = ?");
    $delUserStmt->bind_param("s", $currentUsername);
    $delUserStmt->execute();
    $delUserStmt->close();

    // 5. Destroy session and redirect
    session_destroy();
    header('Location: index.php?deleted=1');
    exit();
}
// ----------------------------------------

// Fetch user data (same as before)
$userData = [
    'name' => $currentUsername,
    'profile_picture' => '',
    'gallery_name' => '',
    'banner_image' => ''
];

$stmt = $conn->prepare("SELECT name, profile_picture, gallery_name, banner_image FROM users WHERE name = ?");
$stmt->bind_param("s", $currentUsername);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userData = [
        'name' => $row['name'] ?? $currentUsername,
        'profile_picture' => $row['profile_picture'] ?? '',
        'gallery_name' => $row['gallery_name'] ?? '',
        'banner_image' => $row['banner_image'] ?? ''
    ];
}
$stmt->close();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_account'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $galleryName = trim($_POST['gallery_name'] ?? '');

    if ($newUsername === '') {
        $message = 'Username is required.';
    } else {
        $checkStmt = $conn->prepare("SELECT name FROM users WHERE name = ? AND name != ?");
        $checkStmt->bind_param("ss", $newUsername, $currentUsername);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = 'That username is already taken.';
        } else {
            // Handle file uploads
            $profilePicture = $userData['profile_picture'];
            $uploadedProfilePicture = saveUploadedImage($_FILES['profile_picture'] ?? [], 'profile');
            if ($uploadedProfilePicture !== '') {
                $profilePicture = $uploadedProfilePicture;
            }

            $bannerImage = $userData['banner_image'];
            $uploadedBannerImage = saveUploadedImage($_FILES['banner_image'] ?? [], 'banner');
            if ($uploadedBannerImage !== '') {
                $bannerImage = $uploadedBannerImage;
            }

            $updateStmt = $conn->prepare("UPDATE users SET name = ?, profile_picture = ?, gallery_name = ?, banner_image = ? WHERE name = ?");
            $updateStmt->bind_param("sssss", $newUsername, $profilePicture, $galleryName, $bannerImage, $currentUsername);
            if ($updateStmt->execute()) {
                $_SESSION['username'] = $newUsername;
                $currentUsername = $newUsername;
                $userData['name'] = $newUsername;
                $userData['profile_picture'] = $profilePicture;
                $userData['gallery_name'] = $galleryName;
                $userData['banner_image'] = $bannerImage;
                $message = 'Profile updated successfully.';
            } else {
                $message = 'Unable to update your profile.';
            }
        }
    }
}

// Prepare image sources
if (!empty($userData['profile_picture']) && file_exists(__DIR__ . '/' . UPLOAD_DIR . $userData['profile_picture'])) {
    $profileImageSrc = UPLOAD_DIR . htmlspecialchars($userData['profile_picture']);
} else {
    $profileImageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';
}

$bannerImageSrc = !empty($userData['banner_image'])
    ? UPLOAD_DIR . htmlspecialchars($userData['banner_image'])
    : 'images/banner.jpg';

$galleryName = $userData['gallery_name'] !== '' ? $userData['gallery_name'] : 'My Gallery';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
            <?php if ($message !== ''): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="row g-4">
                <!-- Profile Card (left) -->
                <div class="col-lg-4">
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
                            <h3 class="mt-3 mb-1"><?php echo htmlspecialchars($userData['name']); ?></h3>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($galleryName); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form (right) -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Edit Profile</h3>
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Profile Picture</label>
                                    <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                                </div>
                                <div class="mb-3">
                                    <label for="gallery_name" class="form-label">Gallery Name</label>
                                    <input type="text" id="gallery_name" name="gallery_name" class="form-control" value="<?php echo htmlspecialchars($galleryName); ?>">
                                </div>
                                <div class="mb-4">
                                    <label for="banner_image" class="form-label">Banner Image</label>
                                    <input type="file" id="banner_image" name="banner_image" class="form-control" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100">Save Profile</button>
                            </form>

                            <!-- ===== DELETE ACCOUNT SECTION ===== -->
                            <hr class="my-4">
                            <div class="text-center">
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone. All your artwork and data will be permanently removed.')">
                                    <input type="hidden" name="delete_account" value="confirm">
                                    <button type="submit" class="btn btn-danger btn-lg w-100">
                                          Delete My Account
                                    </button>
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