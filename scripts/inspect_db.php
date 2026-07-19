<?php
require_once __DIR__ . '/../db.php';
try {
    $pdo = getDb();
    $stmt = $pdo->query('SELECT id, registration_number, first_name, last_name, email, phone, created_at FROM users ORDER BY id DESC LIMIT 10');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Connected. Last users:\n";
    foreach ($rows as $r) {
        echo sprintf("%s | %s | %s %s | %s | %s\n", $r['id'], $r['registration_number'], $r['first_name'], $r['last_name'], $r['email'], $r['phone']);
    }
    if (empty($rows)) {
        echo "(no users found)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
