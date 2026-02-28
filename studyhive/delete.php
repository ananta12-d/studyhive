<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$id   = intval($_GET['id'] ?? 0);
$user = currentUser();
$db   = getDB();

$stmt = $db->prepare("SELECT * FROM notes WHERE id=?");
$stmt->execute([$id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

if ($note && ($note['user_id'] == $user['id'] || isAdmin())) {
    // Delete file
    $filePath = __DIR__ . '/uploads/' . $note['filename'];
    if (file_exists($filePath)) unlink($filePath);

    $db->prepare("DELETE FROM notes WHERE id=?")->execute([$id]);
}

header('Location: ' . (isAdmin() ? '/studyhive/admin/' : '/studyhive/dashboard.php'));
exit;
