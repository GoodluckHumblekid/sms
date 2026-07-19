<?php
// Simple SQLite DB helper
function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->query("PRAGMA table_info($table)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        if (($col['name'] ?? '') === $column) {
            return true;
        }
    }

    return false;
}

function ensureSchema(PDO $pdo): void
{
    $pdo->exec("PRAGMA foreign_keys = ON");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        registration_number TEXT UNIQUE,
        first_name TEXT,
        second_name TEXT,
        last_name TEXT,
        dob TEXT,
        gender TEXT,
        education_level TEXT,
        email TEXT,
        phone TEXT,
        password_hash TEXT,
        created_at TEXT
    )");

    if (!columnExists($pdo, 'users', 'second_name')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN second_name TEXT');
    }

    if (!columnExists($pdo, 'users', 'education_level')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN education_level TEXT');
    }

    if (!columnExists($pdo, 'users', 'birth_certificate_path')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN birth_certificate_path TEXT');
    }

    if (!columnExists($pdo, 'users', 'id_copy_path')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN id_copy_path TEXT');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS parents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        first_name TEXT,
        last_name TEXT,
        phone TEXT,
        password_hash TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE,
        password_hash TEXT,
        role TEXT,
        created_at TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT,
        second_name TEXT,
        last_name TEXT,
        education_level TEXT,
        subject TEXT,
        email TEXT,
        phone TEXT,
        image_path TEXT,
        created_at TEXT
    )");

    if (!columnExists($pdo, 'teachers', 'second_name')) {
        $pdo->exec('ALTER TABLE teachers ADD COLUMN second_name TEXT');
    }

    if (!columnExists($pdo, 'teachers', 'image_path')) {
        $pdo->exec('ALTER TABLE teachers ADD COLUMN image_path TEXT');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_performance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        number_of_subjects INTEGER,
        grade TEXT,
        total INTEGER,
        average REAL,
        position TEXT,
        remarks TEXT,
        remarks_director TEXT,
        created_at TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type TEXT,
        months TEXT,
        account_number TEXT,
        reference_number TEXT,
        status TEXT,
        created_at TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS subject_performance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        subject TEXT NOT NULL,
        marks INTEGER,
        grade TEXT,
        remarks TEXT,
        created_at TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
}

function getDb(): PDO
{
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $dir . '/sms.sqlite';
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ensureSchema($pdo);

    return $pdo;
}
