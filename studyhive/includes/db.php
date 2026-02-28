<?php
define('DB_PATH', __DIR__ . '/../studyhive.db');

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
            );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("PRAGMA journal_mode=WAL");
        initDB($db);
    }
    return $db;
}

function initDB($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'student',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        subject TEXT NOT NULL,
        semester TEXT NOT NULL,
        description TEXT,
        filename TEXT NOT NULL,
        original_name TEXT NOT NULL,
        status TEXT DEFAULT 'approved',
        downloads INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        note_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        stars INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(note_id, user_id),
        FOREIGN KEY(note_id) REFERENCES notes(id),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS bookmarks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        note_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(note_id, user_id),
        FOREIGN KEY(note_id) REFERENCES notes(id),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        link TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $check = $db->query("SELECT id FROM users WHERE email='admin@studyhive.com'")->fetch();
    if (!$check) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@studyhive.com', '$hash', 'admin')");
    }
}

function notifyAllStudents($db, $message, $link = '') {
    $users = $db->query("SELECT id FROM users WHERE role='student'")->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)");
    foreach ($users as $uid) {
        $stmt->execute([$uid, $message, $link]);
    }
}

function getUnreadCount($db, $userId) {
    $s = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $s->execute([$userId]);
    return $s->fetchColumn();
}
?>
