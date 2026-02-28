<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';
$db = getDB();
$user = currentUser();

switch ($action) {
    case 'rate':
        $noteId = intval($body['note_id'] ?? 0);
        $stars  = intval($body['stars'] ?? 0);
        if ($noteId < 1 || $stars < 1 || $stars > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        // Check note exists
        $note = $db->prepare("SELECT id FROM notes WHERE id=? AND status='approved'");
        $note->execute([$noteId]);
        if (!$note->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Note not found']);
            exit;
        }
        $stmt = $db->prepare("INSERT INTO ratings (note_id, user_id, stars) VALUES (?,?,?)
                               ON CONFLICT(note_id, user_id) DO UPDATE SET stars=excluded.stars, created_at=CURRENT_TIMESTAMP");
        $stmt->execute([$noteId, $user['id'], $stars]);

        $avgRow = $db->prepare("SELECT AVG(stars) as avg, COUNT(*) as cnt FROM ratings WHERE note_id=?");
        $avgRow->execute([$noteId]);
        $avgData = $avgRow->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'avg' => $avgData['avg'], 'count' => $avgData['cnt']]);
        break;

    case 'bookmark':
        $noteId = intval($body['note_id'] ?? 0);
        // Check if already bookmarked
        $check = $db->prepare("SELECT id FROM bookmarks WHERE note_id=? AND user_id=?");
        $check->execute([$noteId, $user['id']]);
        $exists = $check->fetch();
        if ($exists) {
            $db->prepare("DELETE FROM bookmarks WHERE note_id=? AND user_id=?")->execute([$noteId, $user['id']]);
            echo json_encode(['success' => true, 'bookmarked' => false]);
        } else {
            $db->prepare("INSERT INTO bookmarks (note_id, user_id) VALUES (?,?)")->execute([$noteId, $user['id']]);
            echo json_encode(['success' => true, 'bookmarked' => true]);
        }
        break;

    case 'mark_read':
        $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
