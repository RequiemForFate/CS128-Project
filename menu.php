<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = !empty($_SESSION['username']) ? $_SESSION['username'] : 'Login';
$profileImageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cpath d="M 8 56 A 24 24 0 0 1 56 56" fill="%23ffffff"/%3E%3C/svg%3E';

if (!empty($username) && $username !== 'Login' && extension_loaded('mysqli')) {
    $menuConn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if (!$menuConn->connect_error) {
        $stmt = $menuConn->prepare("SELECT profile_picture FROM users WHERE name = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (!empty($row['profile_picture'])) {
                    $profileImageSrc = 'images/' . htmlspecialchars($row['profile_picture']);
                }
            }
            $stmt->close();
        }
        $menuConn->close();
    }
}
?>

<!-- The Side Menu -->
<button id="sidebarToggle" class="sidebar-toggle" aria-expanded="true" aria-controls="sidebar">☰ Menu</button>
<div class="sidebar" id="sidebar"> <!-- sidebar start -->
    <a href="profile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" alt="Profile" class="profile-avatar" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22%3E%3Crect width=%2264%22 height=%2264%22 rx=%2232%22 fill=%22%234563ea%22/%3E%3Ccircle cx=%2232%22 cy=%2224%22 r=%2214%22 fill=%22%23ffffff%22/%3E%3Cpath d=%22M 8 56 A 24 24 0 0 1 56 56%22 fill=%22%23ffffff%22/%3E%3C/svg%3E';">
        <span><?php echo htmlspecialchars($username); ?></span>
    </a><br>
    <a href="Artwork.php">Gallery</a><br>
    <a href="imageform.php">Insert Image</a><br>
    <a href="logout.php">Logout</a><br>
</div> <!-- end sidebar -->
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    sidebarToggle.addEventListener('click', () => {
        const isClosed = sidebar.classList.toggle('closed');
        document.body.classList.toggle('sidebar-closed', isClosed);
        sidebarToggle.setAttribute('aria-expanded', String(!isClosed));
        sidebarToggle.textContent = isClosed ? '☰ Menu' : '✕ Close';
    });
</script>