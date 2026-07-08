<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = !empty($_SESSION['username']) ? $_SESSION['username'] : 'Login';
$profileImageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cpath d="M18 50c3-10 11-15 14-15s11 5 14 15" fill="%23ffffff"/%3E%3C/svg%3E';

if (!empty($username) && $username !== 'Login' && extension_loaded('mysqli')) {
    $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE name = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['profile_picture'])) {
                $profileImageSrc = 'images/' . htmlspecialchars($row['profile_picture']);
            }
        }
        $conn->close();
    }
}
?>

<!-- The Side Menu -->
<button id="sidebarToggle" class="sidebar-toggle" aria-expanded="true" aria-controls="sidebar">☰ Menu</button>
<div class="sidebar" id="sidebar">
    <a href="profile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" alt="Profile" class="profile-avatar">
        <span><?php echo htmlspecialchars($username); ?></span>
    </a><br>
    <a href="Artwork.php">Gallery</a><br>
    <a href="imageform.php">Insert Image</a><br>
    <a href="logout.php">Logout</a><br>
</div>
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
