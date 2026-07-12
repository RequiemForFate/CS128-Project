<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the logged‑in username (or empty string if not set)
$username = $_SESSION['username'] ?? '';

// Default avatar (fallback)
$profileImageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';

// If we have a username, try to load the custom profile picture
if (!empty($username) && extension_loaded('mysqli')) {
    $menuConn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$menuConn->connect_error) {
        $profilestmt = $menuConn->prepare("SELECT profile_picture FROM users WHERE name = ?");
        $profilestmt->bind_param("s", $username);
        $profilestmt->execute();
        $profileresult = $profilestmt->get_result();
        if ($profileresult->num_rows > 0) {
            $profilerow = $profileresult->fetch_assoc();
            if (!empty($profilerow['profile_picture'])) {
                $profileImageSrc = UPLOAD_DIR . htmlspecialchars($profilerow['profile_picture']);
            }
        }
        $menuConn->close();
    }
}
?>
<!-- The Side Menu -->
<button id="sidebarToggle" class="sidebar-toggle" aria-expanded="true" aria-controls="sidebar">☰ Menu</button>
<div class="sidebar" id="sidebar">
    <a href="profile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" alt="Profile" class="profile-avatar">
        <span class="username-display"><?php echo htmlspecialchars($username ?: 'Guest'); ?></span>
    </a>
    <br>
    <a href="Artwork.php">Gallery</a><br>
    <a href="imageform.php">Insert Image</a><br>
    <a href="logout.php">Logout</a><br>
</div>
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    sidebar.classList.add('closed');
    document.body.classList.add('sidebar-closed');
    sidebarToggle.setAttribute('aria-expanded', 'false');
    sidebarToggle.textContent = '☰ Menu';

    sidebarToggle.addEventListener('click', () => {
        const isClosed = sidebar.classList.toggle('closed');
        document.body.classList.toggle('sidebar-closed', isClosed);
        sidebarToggle.setAttribute('aria-expanded', String(!isClosed));
        sidebarToggle.textContent = isClosed ? '☰ Menu' : '✕ Close';
    });
</script>