<?php
$pageTitle = 'Saved Notes';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$db   = getDB();
$user = currentUser();

$notes = $db->prepare("
    SELECT n.*, u.name AS uploader,
           COALESCE((SELECT AVG(stars) FROM ratings WHERE note_id=n.id), 0) AS avg_rating,
           (SELECT stars FROM ratings WHERE note_id=n.id AND user_id=?) AS my_rating
    FROM bookmarks b
    JOIN notes n ON b.note_id = n.id
    JOIN users u ON n.user_id = u.id
    WHERE b.user_id=? AND n.status='approved'
    ORDER BY b.created_at DESC
");
$notes->execute([$user['id'], $user['id']]);
$notes = $notes->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <h1>🔖 Saved Notes</h1>
    <a href="/studyhive/browse.php" class="btn btn-outline">Browse All</a>
</div>

<?php if (empty($notes)): ?>
<div class="empty">
    <div class="icon">🔖</div>
    <h3>No saved notes yet</h3>
    <p>Tap the 🔲 bookmark on any note to save it here.</p>
    <a href="/studyhive/browse.php" class="btn btn-blue" style="margin-top:1.25rem">Browse Notes</a>
</div>
<?php else: ?>
<div class="cards-grid">
<?php foreach ($notes as $i => $note):
    $avgRating = round($note['avg_rating'], 1);
    $ext = strtoupper(pathinfo($note['original_name'], PATHINFO_EXTENSION));
?>
<div class="card" style="animation-delay:<?= $i*0.06 ?>s">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
        <div>
            <span class="card-badge badge-subject"><?= htmlspecialchars($note['subject']) ?></span>
            <span class="card-badge badge-sem">Sem <?= $note['semester'] ?></span>
        </div>
        <button class="bookmark-btn saved" onclick="toggleBookmark(<?= $note['id'] ?>, this); this.closest('.card').style.opacity='0'; setTimeout(()=>this.closest('.card').remove(),300)">🔖</button>
    </div>
    <h3><?= htmlspecialchars($note['title']) ?></h3>
    <p><?= $note['description'] ? htmlspecialchars(mb_substr($note['description'],0,90)).'…' : 'No description.' ?></p>
    <div class="card-meta">
        <span>👤 <?= htmlspecialchars($note['uploader']) ?></span>
        <span>⬇️ <?= $note['downloads'] ?></span>
        <span style="font-weight:700"><?= $ext ?></span>
    </div>
    <div class="card-actions">
        <a href="/studyhive/download.php?id=<?= $note['id'] ?>" class="btn btn-blue btn-sm">⬇️ Download</a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
