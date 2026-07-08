<?php
session_start();
$conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
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

// Fetch user data
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

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $galleryName = trim($_POST['gallery_name'] ?? '');

    if ($newUsername === '') {
        $message = 'Username is required.';
    } else {
        // Check if username is already taken
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

            // Update database
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
$profileImageSrc = !empty($userData['profile_picture'])
    ? 'images/' . htmlspecialchars($userData['profile_picture'])
    : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';

$bannerImageSrc = !empty($userData['banner_image'])
    ? 'images/' . htmlspecialchars($userData['banner_image'])
    : 'banner.jpg';

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
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <!-- Header Section -->
    <div class="container py-1"> <!-- container start -->
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>
    </div> <!-- end container -->

    <!-- Main Content -->
    <div class="main-content"> <!-- main-content start -->
        <div class="container py-4"> <!-- container start -->
            <!-- Status Message -->
            <?php if ($message !== ''): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Profile Layout -->
            <div class="row g-4"> <!-- row start -->
                <!-- Profile Card -->
                <div class="col-lg-4"> <!-- col start -->
                    <div class="card shadow-sm"> <!-- card start -->
                        <img src="<?php echo htmlspecialchars($bannerImageSrc); ?>" 
                            class="card-img-top" 
                            alt="Profile banner" 
                            style="height: 180px; object-fit: cover;">
                        <div class="card-body text-center"> <!-- card-body start -->
                            <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" 
                                alt="Profile picture" 
                                class="rounded-circle border border-3 border-light shadow" 
                                style="width: 110px; height: 110px; object-fit: cover; margin-top: -65px;">
                            <h3 class="mt-3 mb-1"><?php echo htmlspecialchars($userData['name']); ?></h3>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($galleryName); ?></p>
                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

                <!-- Edit Profile Form -->
                <div class="col-lg-8"> <!-- col start -->
                    <div class="card shadow-sm"> <!-- card start -->
                        <div class="card-body"> <!-- card-body start -->
                            <h3 class="card-title mb-4">Edit Profile</h3>
                            <form method="post" enctype="multipart/form-data">
                                <!-- Username Field -->
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                                </div>

                                <!-- Profile Picture Field -->
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Profile Picture</label>
                                    <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                                </div>

                                <!-- Gallery Name Field -->
                                <div class="mb-3">
                                    <label for="gallery_name" class="form-label">Gallery Name</label>
                                    <input type="text" id="gallery_name" name="gallery_name" class="form-control" value="<?php echo htmlspecialchars($galleryName); ?>">
                                </div>

                                <!-- Banner Image Field -->
                                <div class="mb-4">
                                    <label for="banner_image" class="form-label">Banner Image</label>
                                    <input type="file" id="banner_image" name="banner_image" class="form-control" accept="image/*">
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary btn-lg w-100">Save Profile</button>
                            </form>
                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- end container -->
    </div> <!-- end main-content -->
</body>
</html>

<?php
    $conn->close();
?>