<?php
$pageTitle = 'Upload Notes';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$error = $success = '';
$subjects = ['Mathematics','Physics','Chemistry','Computer Science','Electronics','English','Data Structures','DBMS','Operating System','Networks','Software Engineering','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $sem     = trim($_POST['semester'] ?? '');
    $desc    = trim($_POST['description'] ?? '');

    if (!$title || !$subject || !$sem) {
        $error = 'Title, Subject and Semester are required.';
    } elseif (empty($_FILES['file']['name'])) {
        $error = 'Please select a file to upload.';
    } else {
        $file = $_FILES['file'];
        $allowed = ['pdf','doc','docx','ppt','pptx','txt','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = 'File type not allowed. Allowed: ' . implode(', ', $allowed);
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'File too large. Max 10MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newName = uniqid('note_') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                $db   = getDB();
                $user = currentUser();
                $stmt = $db->prepare("INSERT INTO notes (user_id, title, subject, semester, description, filename, original_name) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$user['id'], $title, $subject, $sem, $desc, $newName, $file['name']]);
                $newId = $db->lastInsertId();
                // Notify all students
                notifyAllStudents($db, "📄 New notes uploaded: \"{$title}\" in {$subject}", "/studyhive/browse.php");
                $success = 'Notes uploaded successfully!';
            } else {
                $error = 'Upload failed. Check that the uploads/ folder exists.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <h1>📤 Upload Notes</h1>
    <a href="/studyhive/browse.php" class="btn btn-outline">Browse All Notes</a>
</div>

<div class="upload-card animate-in-2">
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">🎉 <?= htmlspecialchars($success) ?>
        <a href="/studyhive/browse.php" style="margin-left:.5rem;font-weight:700;color:var(--green)">Browse Notes →</a>
    </div>
    <script>document.addEventListener('DOMContentLoaded', () => { setTimeout(launchConfetti, 300); showToast('🎉 Notes uploaded successfully!', 'success', 5000); });</script>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="form-group">
            <label>Note Title *</label>
            <input type="text" name="title" placeholder="e.g. Chapter 3 — Data Structures Complete Notes" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Subject *</label>
                <select name="subject" required>
                    <option value="">— Select Subject —</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s ?>"><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Semester *</label>
                <select name="semester" required>
                    <option value="">— Sem —</option>
                    <?php for ($i=1;$i<=8;$i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="What topics are covered? Which chapters?"></textarea>
        </div>
        <div class="form-group">
            <label>Attach File * (PDF, DOC, DOCX, PPT, Images — max 10MB)</label>
            <div class="file-drop" onclick="document.getElementById('fileInput').click()">
                <input type="file" id="fileInput" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png">
                <div class="file-drop-icon">📂</div>
                <span id="fileLabel">Click or drag a file here</span>
                <div style="font-size:.78rem;color:var(--muted);margin-top:.3rem">PDF, DOC, PPT, Images</div>
            </div>
        </div>
        <button type="submit" class="btn btn-blue btn-full" id="submitBtn">
            📤 Upload Notes
        </button>
    </form>
</div>

<!-- Upload Progress Modal -->
<div id="upload-modal" class="modal">
    <div class="modal-header">
        <h3>⏳ Uploading...</h3>
    </div>
    <div class="modal-body" style="text-align:center;padding:2rem">
        <div style="font-size:3rem;margin-bottom:1rem">📤</div>
        <p>Please wait while your file uploads...</p>
        <div style="margin-top:1.5rem;height:6px;background:var(--border);border-radius:4px;overflow:hidden">
            <div id="progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,var(--blue),var(--purple));border-radius:4px;transition:width .3s"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('fileInput');
    if (fileInput.files.length > 0) {
        openModal('upload-modal');
        let w = 0;
        const bar = document.getElementById('progress-bar');
        const iv = setInterval(() => {
            w = Math.min(w + Math.random() * 15, 90);
            bar.style.width = w + '%';
            if (w >= 90) clearInterval(iv);
        }, 200);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
