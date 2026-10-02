<?php
/**
 * Koneksi SQLite + migrasi + data awal.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }

    $fresh = !file_exists(DB_FILE);
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    migrate($pdo);
    if ($fresh) {
        seed($pdo);
    }

    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id       INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        name     TEXT NOT NULL DEFAULT 'Administrator',
        password TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS features (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        icon       TEXT NOT NULL DEFAULT 'lock',
        title      TEXT NOT NULL,
        subtitle   TEXT NOT NULL DEFAULT '',
        sort_order INTEGER NOT NULL DEFAULT 0,
        is_active  INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_groups (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        name       TEXT NOT NULL,
        tagline    TEXT NOT NULL DEFAULT '',
        logo       TEXT NOT NULL DEFAULT '',
        sort_order INTEGER NOT NULL DEFAULT 0,
        is_active  INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS apps (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        group_id    INTEGER NOT NULL REFERENCES app_groups(id) ON DELETE CASCADE,
        name        TEXT NOT NULL,
        description TEXT NOT NULL DEFAULT '',
        url         TEXT NOT NULL DEFAULT '#',
        icon        TEXT NOT NULL DEFAULT 'grid',
        color       TEXT NOT NULL DEFAULT 'indigo',
        target_blank INTEGER NOT NULL DEFAULT 1,
        sort_order  INTEGER NOT NULL DEFAULT 0,
        is_active   INTEGER NOT NULL DEFAULT 1
    )");
}

function seed(PDO $pdo): void
{
    $settings = [
        'site_name'        => 'Digital Learning Management System',
        'site_tagline'     => 'Sistem Informasi Sekolah Terintegrasi',
        'hero_title'       => 'Digital Learning Management System',
        'hero_subtitle'    => 'Selamat datang di Sistem Manajemen Pembelajaran Digital DEMO.',
        'hero_badge'       => 'Sistem Informasi Sekolah Terintegrasi',
        'hero_image'       => '',
        'hero_overlay'     => '65',
        'section_label'    => 'AVAILABLE APPS',
        'accent_color'     => '#e11d48',
        'primary_color'    => '#4f46e5',
        'show_search'      => '1',
        'footer_text'      => '© ' . date('Y') . ' Digital Learning Management System. All rights reserved.',
        'contact_email'    => 'admin@sekolah.sch.id',
        'contact_phone'    => '',
        'contact_address'  => '',
        'favicon'          => '',
        'meta_description' => 'Portal aplikasi sekolah terintegrasi: data center, presensi, jurnal, dan CBT.',
    ];
    $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    $pdo->prepare('INSERT INTO users (username, name, password) VALUES (?, ?, ?)')
        ->execute(['admin', 'Administrator', password_hash('admin123', PASSWORD_DEFAULT)]);

    $features = [
        ['lock',    'Login Protect',    'Keamanan Login akun',      1],
        ['shield',  'Protected',        'Ujian lebih aman & terjaga', 2],
        ['refresh', 'Data Integration', 'Sinkron data real-time',   3],
    ];
    $stmt = $pdo->prepare('INSERT INTO features (icon, title, subtitle, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($features as $f) {
        $stmt->execute($f);
    }

    $pdo->prepare('INSERT INTO app_groups (name, tagline, logo, sort_order) VALUES (?, ?, ?, ?)')
        ->execute(['DEMO', 'Sistem Informasi Sekolah Terintegrasi', '', 1]);
    $groupId = (int) $pdo->lastInsertId();

    $apps = [
        ['DATA CENTER', 'Pusat Data Sekolah',   '#', 'users',    'indigo', 1],
        ['PRESENSI',    'Manajemen Kehadiran',  '#', 'clock',    'green',  2],
        ['JURNAL',      'Jurnal Pembelajaran',  '#', 'book',     'violet', 3],
        ['CBT',         'Computer Based Test',  '#', 'monitor',  'sky',    4],
    ];
    $stmt = $pdo->prepare('INSERT INTO apps (group_id, name, description, url, icon, color, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($apps as $a) {
        $stmt->execute([$groupId, $a[0], $a[1], $a[2], $a[3], $a[4], $a[5]]);
    }
}
