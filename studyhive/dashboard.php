<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$db   = getDB();
$user = currentUser();

$myNotes = $db->prepare("SELECT * FROM notes WHERE user_id=? ORDER BY created_at DESC");
$myNotes->execute([$user['id']]);
$myNotes = $myNotes->fetchAll(PDO::FETCH_ASSOC);

$totalDownloads = $db->prepare("SELECT COALESCE(SUM(downloads),0) FROM notes WHERE user_id=?");
$totalDownloads->execute([$user['id']]);
$totalDownloads = $totalDownloads->fetchColumn();

$bookmarkCount = $db->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id=?");
$bookmarkCount->execute([$user['id']]);
$bookmarkCount = $bookmarkCount->fetchColumn();

$allNotes = $db->query("SELECT COUNT(*) FROM notes WHERE status='approved'")->fetchColumn();
$unread   = getUnreadCount($db, $user['id']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <div>
        <div style="font-size:.85rem;color:var(--muted);margin-bottom:.25rem">Welcome back,</div>
        <h1>👋 <?= htmlspecialchars($user['name']) ?></h1>
    </div>
    <a href="/studyhive/upload.php" class="btn btn-blue">+ Upload Notes</a>
</div>

<div class="stats-row animate-in-2">
    <div class="stat-card">
        <div class="icon">📄</div>
        <div class="num"><?= count($myNotes) ?></div>
        <div class="lbl">My Uploads</div>
    </div>
    <div class="stat-card">
        <div class="icon">⬇️</div>
        <div class="num"><?= $totalDownloads ?></div>
        <div class="lbl">My Downloads</div>
    </div>
    <div class="stat-card">
        <div class="icon">🔖</div>
        <div class="num"><?= $bookmarkCount ?></div>
        <div class="lbl">Bookmarks</div>
    </div>
    <div class="stat-card">
        <div class="icon">📚</div>
        <div class="num"><?= $allNotes ?></div>
        <div class="lbl">Total Notes</div>
    </div>
</div>

<?php if ($unread > 0): ?>
<div class="alert alert-info animate-in-3">
    🔔 You have <strong><?= $unread ?> unread notification<?= $unread > 1 ? 's' : '' ?></strong>.
    <a href="/studyhive/notifications.php" style="margin-left:.5rem;font-weight:700;color:var(--blue)">View →</a>
</div>
<?php endif; ?>

<div class="section-title animate-in-3">My Uploaded Notes</div>

<?php if (empty($myNotes)): ?>
<div class="empty">
    <div class="icon">📭</div>
    <h3>No uploads yet</h3>
    <p>Share your notes with your classmates!</p>
    <a href="/studyhive/upload.php" class="btn btn-blue" style="margin-top:1.25rem">Upload Your First Note</a>
</div>
<?php else: ?>
<div class="table-wrap animate-in-3">
    <table>
        <thead>
            <tr><th>Title</th><th>Subject</th><th>Sem</th><th>⬇️</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($myNotes as $n): ?>
        <tr>
            <td><strong><?= htmlspecialchars($n['title']) ?></strong></td>
            <td><?= htmlspecialchars($n['subject']) ?></td>
            <td><?= $n['semester'] ?></td>
            <td><?= $n['downloads'] ?></td>
            <td>
                <?php if ($n['status']==='approved'): ?>
                    <span class="pill pill-green">✔ Approved</span>
                <?php else: ?>
                    <span class="pill pill-yellow">⏳ Pending</span>
                <?php endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($n['created_at'])) ?></td>
            <td style="display:flex;gap:.4rem">
                <a href="/studyhive/download.php?id=<?= $n['id'] ?>" class="btn btn-blue btn-sm">⬇️</a>
                <button class="btn btn-red btn-sm" onclick="confirmDelete('/studyhive/delete.php?id=<?= $n['id'] ?>','<?= addslashes(htmlspecialchars($n['title'])) ?>')">🗑</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Confirm Delete Modal -->
<div id="confirm-modal" class="modal">
    <div class="modal-header">
        <h3>⚠️ Confirm Delete</h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body">
        <p id="confirm-msg" style="line-height:1.6;color:var(--text)"></p>
    </div>
    <div class="modal-footer">
        <button class="btn btn-gray btn-sm" onclick="closeModal()">Cancel</button>
        <a id="confirm-ok" href="#" class="btn btn-red btn-sm">Yes, Delete</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
