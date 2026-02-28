<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /studyhive/browse.php'); exit; }

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM notes WHERE id=? AND status='approved'");
$stmt->execute([$id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$note) { header('Location: /studyhive/browse.php'); exit; }

$filePath = __DIR__ . '/uploads/' . $note['filename'];
if (!file_exists($filePath)) {
    die('File not found on server.');
}

// Increment download count
$db->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id=?")->execute([$id]);

// Serve file
$mime = mime_content_type($filePath);
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $note['original_name'] . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
