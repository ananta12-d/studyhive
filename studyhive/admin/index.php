<?php
$pageTitle = 'Admin Panel';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();

// Handle actions
if (isset($_GET['approve'])) {
    $db->prepare("UPDATE notes SET status='approved' WHERE id=?")->execute([intval($_GET['approve'])]);
    header('Location: /studyhive/admin/'); exit;
}
if (isset($_GET['reject'])) {
    $db->prepare("UPDATE notes SET status='rejected' WHERE id=?")->execute([intval($_GET['reject'])]);
    header('Location: /studyhive/admin/'); exit;
}

$notes    = $db->query("SELECT n.*, u.name AS uploader,
                         COALESCE((SELECT AVG(stars) FROM ratings WHERE note_id=n.id),0) AS avg_rating
                         FROM notes n JOIN users u ON n.user_id=u.id ORDER BY n.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$users    = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalDL  = $db->query("SELECT COALESCE(SUM(downloads),0) FROM notes")->fetchColumn();
$approved = count(array_filter($notes, fn($n)=>$n['status']==='approved'));

// Chart data: notes per subject
$subjectData = $db->query("SELECT subject, COUNT(*) as cnt FROM notes WHERE status='approved' GROUP BY subject ORDER BY cnt DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
$subjectLabels = json_encode(array_column($subjectData, 'subject'));
$subjectCounts = json_encode(array_column($subjectData, 'cnt'));

// Chart data: notes per semester
$semData = $db->query("SELECT semester, COUNT(*) as cnt FROM notes WHERE status='approved' GROUP BY semester ORDER BY semester ASC")->fetchAll(PDO::FETCH_ASSOC);
$semLabels = json_encode(array_map(fn($r) => 'Sem '.$r['semester'], $semData));
$semCounts = json_encode(array_column($semData, 'cnt'));

// Chart data: uploads over last 7 days
$daysData = $db->query("SELECT date(created_at) as day, COUNT(*) as cnt FROM notes WHERE created_at >= date('now','-6 days') GROUP BY date(created_at) ORDER BY day ASC")->fetchAll(PDO::FETCH_ASSOC);
$dayLabels = json_encode(array_column($daysData, 'day'));
$dayCounts = json_encode(array_column($daysData, 'cnt'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header animate-in">
    <h1>⚙️ Admin Panel</h1>
    <a href="/studyhive/dashboard.php" class="btn btn-outline">← Back</a>
</div>

<!-- Stats -->
<div class="stats-row animate-in">
    <div class="stat-card"><div class="icon">📄</div><div class="num"><?= count($notes) ?></div><div class="lbl">Total Notes</div></div>
    <div class="stat-card"><div class="icon">✅</div><div class="num"><?= $approved ?></div><div class="lbl">Approved</div></div>
    <div class="stat-card"><div class="icon">👥</div><div class="num"><?= count($users) ?></div><div class="lbl">Users</div></div>
    <div class="stat-card"><div class="icon">⬇️</div><div class="num"><?= $totalDL ?></div><div class="lbl">Downloads</div></div>
</div>

<!-- Charts Row -->
<div class="grid-2 animate-in-2" style="margin-bottom:1.5rem">
    <div class="chart-wrap">
        <h4>📊 Notes by Subject</h4>
        <canvas id="subjectChart" height="200"></canvas>
    </div>
    <div class="chart-wrap">
        <h4>📈 Uploads Last 7 Days</h4>
        <canvas id="trendChart" height="200"></canvas>
    </div>
</div>

<div class="chart-wrap animate-in-3" style="margin-bottom:1.5rem">
    <h4>🎓 Notes by Semester</h4>
    <canvas id="semChart" height="100"></canvas>
</div>

<!-- Notes Table -->
<div class="section-title" style="margin-top:2rem">All Notes</div>
<div class="table-wrap" style="margin-bottom:2rem">
    <table>
        <thead><tr><th>Title</th><th>Subject</th><th>Sem</th><th>By</th><th>⭐ Rating</th><th>⬇️</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($notes as $n): ?>
        <tr>
            <td><strong><?= htmlspecialchars($n['title']) ?></strong></td>
            <td><?= htmlspecialchars($n['subject']) ?></td>
            <td><?= $n['semester'] ?></td>
            <td><?= htmlspecialchars($n['uploader']) ?></td>
            <td><?= $n['avg_rating'] > 0 ? '⭐ '.number_format($n['avg_rating'],1) : '—' ?></td>
            <td><?= $n['downloads'] ?></td>
            <td>
                <?php if ($n['status']==='approved'): ?>
                    <span class="pill pill-green">✔ Approved</span>
                <?php elseif ($n['status']==='rejected'): ?>
                    <span class="pill pill-red">✘ Rejected</span>
                <?php else: ?>
                    <span class="pill pill-yellow">⏳ Pending</span>
                <?php endif; ?>
            </td>
            <td style="white-space:nowrap"><?= date('d M Y', strtotime($n['created_at'])) ?></td>
            <td style="display:flex;gap:.3rem;flex-wrap:wrap;padding:.6rem 1rem">
                <?php if ($n['status']!=='approved'): ?>
                    <a href="?approve=<?= $n['id'] ?>" class="btn btn-green btn-sm" onclick="showToast('Note approved!','success')">✔</a>
                <?php endif; ?>
                <?php if ($n['status']!=='rejected'): ?>
                    <a href="?reject=<?= $n['id'] ?>" class="btn btn-gray btn-sm">✘</a>
                <?php endif; ?>
                <button class="btn btn-red btn-sm" onclick="confirmDelete('/studyhive/delete.php?id=<?= $n['id'] ?>','<?= addslashes(htmlspecialchars($n['title'])) ?>')">🗑</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Users Table -->
<div class="section-title">All Users</div>
<div class="table-wrap">
    <table>
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['role']==='admin' ? '<span class="pill pill-blue">Admin</span>' : '<span class="pill pill-green">Student</span>' ?></td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Confirm Delete Modal -->
<div id="confirm-modal" class="modal">
    <div class="modal-header">
        <h3>⚠️ Confirm Delete</h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body">
        <p id="confirm-msg" style="line-height:1.6"></p>
    </div>
    <div class="modal-footer">
        <button class="btn btn-gray btn-sm" onclick="closeModal()">Cancel</button>
        <a id="confirm-ok" href="#" class="btn btn-red btn-sm">Delete</a>
    </div>
</div>

<script>
const chartColors = ['#2563eb','#7c3aed','#ec4899','#06b6d4','#f59e0b','#22c55e','#ef4444','#f97316'];
const chartColorsAlpha = chartColors.map(c => c + 'cc');

// Subject Doughnut Chart — with glow
new Chart(document.getElementById('subjectChart'), {
    type: 'doughnut',
    data: {
        labels: <?= $subjectLabels ?: '["No Data"]' ?>,
        datasets: [{
            data: <?= $subjectCounts ?: '[1]' ?>,
            backgroundColor: chartColorsAlpha,
            borderColor: '#fff', borderWidth: 3,
            hoverOffset: 18, hoverBorderWidth: 4
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: { position: 'right', labels: { font: { size: 12, weight: '700' }, boxWidth: 14, padding: 16 } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} note${ctx.parsed!==1?'s':''}` } }
        },
        animation: { animateScale: true, animateRotate: true, duration: 1200, easing: 'easeOutBounce' }
    }
});

// Trend Line Chart — gradient fill
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendGrad = trendCtx.createLinearGradient(0, 0, 0, 200);
trendGrad.addColorStop(0, 'rgba(37,99,235,.4)');
trendGrad.addColorStop(1, 'rgba(124,58,237,.02)');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= $dayLabels ?: '["Today"]' ?>,
        datasets: [{
            label: 'Uploads',
            data: <?= $dayCounts ?: '[0]' ?>,
            borderColor: '#2563eb', backgroundColor: trendGrad,
            borderWidth: 3, fill: true, tension: .45,
            pointBackgroundColor: '#fff', pointBorderColor: '#2563eb',
            pointBorderWidth: 2.5, pointRadius: 6, pointHoverRadius: 9,
            pointHoverBackgroundColor: '#7c3aed'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { weight: '700' } }, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } }
        },
        animation: { duration: 1100, easing: 'easeOutQuart' }
    }
});

// Semester Bar Chart — gradient bars
const semCtx = document.getElementById('semChart').getContext('2d');
new Chart(semCtx, {
    type: 'bar',
    data: {
        labels: <?= $semLabels ?: '["No data"]' ?>,
        datasets: [{
            label: 'Notes',
            data: <?= $semCounts ?: '[0]' ?>,
            backgroundColor: chartColorsAlpha,
            borderColor: chartColors,
            borderWidth: 2,
            borderRadius: 10, borderSkipped: false,
            hoverBackgroundColor: chartColors
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { weight: '700' } }, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false }, ticks: { font: { weight: '700', size: 12 } } }
        },
        animation: { duration: 1000, delay: ctx => ctx.dataIndex * 100, easing: 'easeOutBounce' }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
