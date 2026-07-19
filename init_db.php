<?php
require_once __DIR__ . '/db.php';

try {
    $pdo = getDb();

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        registration_number TEXT UNIQUE,
        first_name TEXT,
        second_name TEXT,
        last_name TEXT,
        dob TEXT,
        gender TEXT,
        email TEXT,
        phone TEXT,
        password_hash TEXT,
        created_at TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        first_name TEXT,
        last_name TEXT,
        phone TEXT,
        password_hash TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    echo "Database initialized at " . __DIR__ . "/data/sms.sqlite\n";
} catch (Exception $e) {
    echo "Failed to initialize database: " . $e->getMessage() . "\n";
    exit(1);
}
