<?php
$pageTitle = 'Browse Notes';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$db = getDB();
$user = currentUser();

$notes = $db->query("
    SELECT n.*, u.name AS uploader,
           COALESCE((SELECT AVG(stars) FROM ratings WHERE note_id=n.id), 0) AS avg_rating,
           COALESCE((SELECT COUNT(*) FROM ratings WHERE note_id=n.id), 0) AS rating_count,
           EXISTS(SELECT 1 FROM bookmarks WHERE note_id=n.id AND user_id={$user['id']}) AS bookmarked,
           (SELECT stars FROM ratings WHERE note_id=n.id AND user_id={$user['id']}) AS my_rating
    FROM notes n JOIN users u ON n.user_id=u.id
    WHERE n.status='approved'
    ORDER BY n.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$subjects = array_unique(array_column($notes, 'subject'));
sort($subjects);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <h1>🔍 Browse Notes</h1>
    <a href="/studyhive/upload.php" class="btn btn-blue">+ Upload Notes</a>
</div>

<div class="search-bar animate-in-2">
    <input type="text" id="searchInput" placeholder="🔎 Search title or subject...">
    <select id="subjectFilter">
        <option value="">All Subjects</option>
        <?php foreach ($subjects as $s): ?>
            <option value="<?= strtolower(htmlspecialchars($s)) ?>"><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="semFilter">
        <option value="">All Semesters</option>
        <?php for ($i=1;$i<=8;$i++): ?>
            <option value="<?= $i ?>">Semester <?= $i ?></option>
        <?php endfor; ?>
    </select>
</div>

<?php if (empty($notes)): ?>
<div class="empty">
    <div class="icon">📭</div>
    <h3>No notes yet</h3>
    <p>Be the first to share!</p>
    <a href="/studyhive/upload.php" class="btn btn-blue" style="margin-top:1.25rem">Upload Notes</a>
</div>
<?php else: ?>

<div class="cards-grid" id="notesGrid">
<?php foreach ($notes as $i => $note):
    $avgRating = round($note['avg_rating'], 1);
    $myRating  = intval($note['my_rating'] ?? 0);
    $ext = strtoupper(pathinfo($note['original_name'], PATHINFO_EXTENSION));
    $extColors = ['PDF'=>'#ef4444','DOC'=>'#2563eb','DOCX'=>'#2563eb','PPT'=>'#f59e0b','PPTX'=>'#f59e0b','PNG'=>'#10b981','JPG'=>'#10b981'];
    $extColor = $extColors[$ext] ?? '#64748b';
?>
<div class="card note-card"
     data-title="<?= strtolower(htmlspecialchars($note['title'])) ?>"
     data-subject="<?= strtolower(htmlspecialchars($note['subject'])) ?>"
     data-semester="<?= $note['semester'] ?>"
     style="animation-delay: <?= min($i * 0.06, 0.5) ?>s">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.5rem;">
        <div>
            <span class="card-badge badge-subject"><?= htmlspecialchars($note['subject']) ?></span>
            <span class="card-badge badge-sem">Sem <?= $note['semester'] ?></span>
        </div>
        <button class="bookmark-btn <?= $note['bookmarked'] ? 'saved' : '' ?>"
                onclick="toggleBookmark(<?= $note['id'] ?>, this)"
                title="<?= $note['bookmarked'] ? 'Remove bookmark' : 'Save note' ?>">
            <?= $note['bookmarked'] ? '🔖' : '🔲' ?>
        </button>
    </div>

    <h3><?= htmlspecialchars($note['title']) ?></h3>
    <p><?= $note['description'] ? htmlspecialchars(mb_substr($note['description'], 0, 90)) . '…' : 'No description.' ?></p>

    <!-- Star Rating Display -->
    <div class="rating-display" style="margin-bottom:.7rem;">
        <?php for ($s=1; $s<=5; $s++): ?>
            <span class="star <?= $s <= round($avgRating) ? 'filled' : '' ?>">★</span>
        <?php endfor; ?>
        <span class="score" id="avg-<?= $note['id'] ?>"><?= $avgRating > 0 ? number_format($avgRating,1) : '—' ?></span>
        <span class="count" id="cnt-<?= $note['id'] ?>">(<?= $note['rating_count'] ?>)</span>
    </div>

    <div class="card-meta">
        <span>👤 <?= htmlspecialchars($note['uploader']) ?></span>
        <span>⬇️ <?= $note['downloads'] ?></span>
        <span style="font-weight:700;color:<?= $extColor ?>;"><?= $ext ?></span>
    </div>

    <div class="card-actions">
        <button class="btn btn-blue btn-sm" onclick="openNoteModal(<?= $note['id'] ?>)">👁 View</button>
        <a href="/studyhive/download.php?id=<?= $note['id'] ?>" class="btn btn-outline btn-sm">⬇️ Download</a>
    </div>
</div>
<?php endforeach; ?>
</div>

<div id="noResults" style="display:none">
    <div class="empty"><div class="icon">🔍</div><h3>No results found</h3><p>Try different keywords or filters.</p></div>
</div>

<?php endif; ?>

<!-- NOTE DETAIL MODAL (filled dynamically) -->
<div id="note-modal" class="modal" style="width:520px">
    <div class="modal-header">
        <h3 id="modal-note-title">Note Details</h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body" id="modal-note-body">
        <div style="text-align:center;padding:2rem;color:var(--muted)">Loading…</div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-gray btn-sm" onclick="closeModal()">Close</button>
        <a id="modal-download-btn" href="#" class="btn btn-blue btn-sm">⬇️ Download</a>
    </div>
</div>

<!-- CONFIRM DELETE MODAL -->
<div id="confirm-modal" class="modal">
    <div class="modal-header">
        <h3>⚠️ Confirm Delete</h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body">
        <p id="confirm-msg" style="color:var(--text);line-height:1.6"></p>
    </div>
    <div class="modal-footer">
        <button class="btn btn-gray btn-sm" onclick="closeModal()">Cancel</button>
        <a id="confirm-ok" href="#" class="btn btn-red btn-sm">Delete</a>
    </div>
</div>

<script>
const notesData = <?= json_encode(array_combine(
    array_column($notes, 'id'),
    $notes
), JSON_HEX_TAG) ?>;

function openNoteModal(id) {
    const n = notesData[id];
    if (!n) return;
    document.getElementById('modal-note-title').textContent = n.title;
    document.getElementById('modal-download-btn').href = '/studyhive/download.php?id=' + id;

    const stars = (r, total) => Array.from({length:5}, (_,i) =>
        `<span class="star ${i < Math.round(r) ? 'filled' : ''}" data-value="${i+1}" id="ms-${id}-${i+1}">★</span>`
    ).join('');

    document.getElementById('modal-note-body').innerHTML = `
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.9rem">
            <span class="card-badge badge-subject">${n.subject}</span>
            <span class="card-badge badge-sem">Semester ${n.semester}</span>
        </div>
        <p style="color:var(--muted);font-size:.9rem;line-height:1.65;margin-bottom:1rem">${n.description || 'No description provided.'}</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:1rem">
            <div style="background:var(--gray);border-radius:8px;padding:.7rem">
                <div style="font-size:.73rem;color:var(--muted);margin-bottom:.2rem">UPLOADED BY</div>
                <div style="font-weight:700;font-size:.9rem">👤 ${n.uploader}</div>
            </div>
            <div style="background:var(--gray);border-radius:8px;padding:.7rem">
                <div style="font-size:.73rem;color:var(--muted);margin-bottom:.2rem">DOWNLOADS</div>
                <div style="font-weight:700;font-size:.9rem">⬇️ ${n.downloads}</div>
            </div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:1rem">
            <div style="font-size:.83rem;font-weight:700;margin-bottom:.6rem">Rate this note:</div>
            <div id="stars-${id}" class="stars" style="gap:6px">
                ${stars(n.my_rating || 0)}
            </div>
        </div>`;

    openModal('note-modal');
    // Init star interaction after modal opened
    initStarRating(id, n.my_rating || 0);
    setTimeout(() => {
        document.querySelectorAll(`#stars-${id} .star`).forEach(s => {
            s.classList.toggle('filled', parseInt(s.dataset.value) <= (n.my_rating || 0));
        });
    }, 50);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
