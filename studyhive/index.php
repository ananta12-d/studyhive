<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) { header('Location: /studyhive/dashboard.php'); exit; }

$db = getDB();
$totalNotes = $db->query("SELECT COUNT(*) FROM notes WHERE status='approved'")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalDL    = $db->query("SELECT COALESCE(SUM(downloads),0) FROM notes")->fetchColumn();
$recent     = $db->query("SELECT n.*, u.name as uploader,
                           COALESCE((SELECT AVG(stars) FROM ratings WHERE note_id=n.id),0) AS avg_rating
                           FROM notes n JOIN users u ON n.user_id=u.id
                           WHERE n.status='approved' ORDER BY n.created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <div class="hero-badge">🎓 Creative Techno College, Angul</div>
    <h1>Study Smarter<br>Together</h1>
    <p>Upload, discover, and download college notes. Collaborative learning for every semester.</p>
    <div class="hero-btns">
        <a href="/studyhive/register.php" class="btn btn-blue">🚀 Get Started Free</a>
        <a href="/studyhive/login.php" class="btn btn-outline">Login</a>
    </div>
</div>

<div class="stats-row animate-in">
    <div class="stat-card">
        <div class="icon">📄</div>
        <div class="num"><?= $totalNotes ?></div>
        <div class="lbl">Notes Shared</div>
    </div>
    <div class="stat-card">
        <div class="icon">👥</div>
        <div class="num"><?= $totalUsers ?></div>
        <div class="lbl">Students</div>
    </div>
    <div class="stat-card">
        <div class="icon">⬇️</div>
        <div class="num"><?= $totalDL ?></div>
        <div class="lbl">Downloads</div>
    </div>
    <div class="stat-card">
        <div class="icon">📚</div>
        <div class="num">8</div>
        <div class="lbl">Semesters</div>
    </div>
</div>

<?php if ($recent): ?>
<div class="section-title animate-in-2">Recently Uploaded</div>
<div class="cards-grid">
    <?php foreach ($recent as $i => $note): ?>
    <div class="card" style="animation-delay:<?= $i*0.07 ?>s">
        <span class="card-badge badge-subject"><?= htmlspecialchars($note['subject']) ?></span>
        <span class="card-badge badge-sem">Sem <?= $note['semester'] ?></span>
        <h3><?= htmlspecialchars($note['title']) ?></h3>
        <p><?= htmlspecialchars(mb_substr($note['description'] ?? '', 0, 80)) ?>…</p>
        <div class="card-meta">
            <span>👤 <?= htmlspecialchars($note['uploader']) ?></span>
            <span>⬇️ <?= $note['downloads'] ?></span>
        </div>
        <a href="/studyhive/login.php" class="btn btn-blue btn-sm">Login to Download</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
