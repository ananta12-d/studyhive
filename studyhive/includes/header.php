<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
$user = currentUser();
$unread = 0;
if (isLoggedIn()) {
    $db = getDB();
    $unread = getUnreadCount($db, $user['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'StudyHive') ?> — StudyHive</title>
    <link rel="stylesheet" href="/studyhive/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar">
    <a href="/studyhive/" class="brand">📚 StudyHive</a>
    <div class="nav-links">
        <?php if (isLoggedIn()): ?>
            <a href="/studyhive/browse.php">Browse</a>
            <a href="/studyhive/upload.php">Upload</a>
            <a href="/studyhive/bookmarks.php">🔖 Saved</a>
            <a href="/studyhive/notifications.php" class="notif-link">
                🔔<?php if ($unread > 0): ?><span class="badge"><?= $unread ?></span><?php endif; ?>
            </a>
            <?php if (isAdmin()): ?>
                <a href="/studyhive/admin/" class="admin-link">⚙️ Admin</a>
            <?php endif; ?>
            <div class="user-menu">
                <span class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></span>
                <span class="user-name"><?= htmlspecialchars(explode(' ',$user['name'])[0]) ?></span>
                <div class="user-dropdown">
                    <a href="/studyhive/dashboard.php">📊 Dashboard</a>
                    <a href="/studyhive/logout.php">🚪 Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="/studyhive/login.php">Login</a>
            <a href="/studyhive/register.php" class="btn-nav-primary">Register</a>
        <?php endif; ?>
    </div>
</nav>
<main class="container">
