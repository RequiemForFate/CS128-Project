<?php
session_start();
$conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
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

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return '';
    }

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = __DIR__ . '/images/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return '';
    }

    return $filename;
}

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

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

$profileImageSrc = !empty($userData['profile_picture'])
    ? 'images/' . htmlspecialchars($userData['profile_picture'])
    : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cpath d="M18 50c3-10 11-15 14-15s11 5 14 15" fill="%23ffffff"/%3E%3C/svg%3E';
$bannerImageSrc = !empty($userData['banner_image'])
    ? 'images/' . htmlspecialchars($userData['banner_image'])
    : 'banner.jpg';
$galleryName = $userData['gallery_name'] !== '' ? $userData['gallery_name'] : 'My Gallery';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container py-1">
    <?php include 'banner.php'; ?>
    <?php include 'menu.php'; ?>
</div>
<div class="main-content">
    <div class="container py-4">
        <?php if ($message !== ''): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <img src="<?php echo htmlspecialchars($bannerImageSrc); ?>" class="card-img-top" alt="Profile banner" style="height: 180px; object-fit: cover;">
                    <div class="card-body text-center">
                        <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" alt="Profile picture" class="rounded-circle border border-3 border-light shadow" style="width: 110px; height: 110px; object-fit: cover; margin-top: -55px; background: #fff;">
                        <h3 class="mt-3 mb-1"><?php echo htmlspecialchars($userData['name']); ?></h3>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($galleryName); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title">Edit Profile</h3>
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gallery Name</label>
                                <input type="text" name="gallery_name" class="form-control" value="<?php echo htmlspecialchars($galleryName); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Banner Image</label>
                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>