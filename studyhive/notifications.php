<?php
$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$db   = getDB();
$user = currentUser();

// Mark all as read
$db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);

$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$notifs->execute([$user['id']]);
$notifs = $notifs->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <h1>🔔 Notifications</h1>
    <span style="color:var(--muted);font-size:.9rem"><?= count($notifs) ?> total</span>
</div>

<?php if (empty($notifs)): ?>
<div class="empty">
    <div class="icon">🔕</div>
    <h3>All clear!</h3>
    <p>No notifications yet.</p>
</div>
<?php else: ?>
<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow-sm);animation:fadeInUp .4s ease">
<?php foreach ($notifs as $n): ?>
<div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
    <div class="notif-dot <?= $n['is_read'] ? 'read' : '' ?>"></div>
    <div style="flex:1">
        <div style="font-size:.9rem;font-weight:<?= !$n['is_read'] ? '700' : '500' ?>;margin-bottom:.2rem">
            <?= htmlspecialchars($n['message']) ?>
        </div>
        <div style="font-size:.76rem;color:var(--muted)">
            <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
        </div>
    </div>
    <?php if ($n['link']): ?>
    <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-blue btn-sm">View →</a>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
