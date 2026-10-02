<?php
/**
 * Helper umum: escaping, settings, flash message, CSRF, auth, upload.
 */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* ------------------------------------------------------------------ settings */

function settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }

    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings();

    return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
}

function save_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?)
                   ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
}

/* --------------------------------------------------------------------- flash */

function flash(?string $message = null, string $type = 'success')
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];

        return null;
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

/* ---------------------------------------------------------------------- csrf */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['_token'] ?? '';
    if (!hash_equals(csrf_token(), (string) $token)) {
        http_response_code(419);
        exit('Sesi kedaluwarsa. Silakan muat ulang halaman.');
    }
}

/* ---------------------------------------------------------------------- auth */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }

    return $user ?: null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('admin/login.php');
    }

    return $user;
}

/* -------------------------------------------------------------------- upload */

/**
 * Menyimpan file gambar yang diunggah, mengembalikan nama file atau null.
 */
function handle_upload(string $field, ?string $oldFile = null): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('Gagal mengunggah file (kode ' . $file['error'] . ').', 'error');

        return null;
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        flash('Ukuran file maksimal 4 MB.', 'error');

        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'image/svg+xml' => 'svg',
        'image/x-icon'  => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        flash('Format file tidak didukung (gunakan JPG, PNG, WEBP, GIF, SVG, ICO).', 'error');

        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . DIRECTORY_SEPARATOR . $name)) {
        flash('File gagal disimpan ke server.', 'error');

        return null;
    }

    delete_upload($oldFile);

    return $name;
}

function delete_upload(?string $file): void
{
    if (!$file) {
        return;
    }
    $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($file);
    if (is_file($path)) {
        @unlink($path);
    }
}

function upload_url(?string $file): ?string
{
    if (!$file) {
        return null;
    }

    return url('assets/uploads/' . rawurlencode($file));
}

/* -------------------------------------------------------------------- konten */

function active_groups(): array
{
    $groups = db()->query('SELECT * FROM app_groups WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
    $stmt   = db()->prepare('SELECT * FROM apps WHERE group_id = ? AND is_active = 1 ORDER BY sort_order, id');
    foreach ($groups as &$group) {
        $stmt->execute([$group['id']]);
        $group['apps'] = $stmt->fetchAll();
    }

    return $groups;
}

function active_features(): array
{
    return db()->query('SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
}

function initials(string $text): string
{
    $parts = preg_split('/\s+/', trim($text)) ?: [];
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $out !== '' ? $out : '?';
}

/* ------------------------------------------------------------------ urutan */

/**
 * Menyusun ulang nomor urut agar rapi (1..n) dalam satu lingkup.
 */
function normalize_order(string $table, ?string $scopeCol = null, $scopeVal = null): void
{
    $where = $scopeCol ? "WHERE $scopeCol = ?" : '';
    $stmt  = db()->prepare("SELECT id FROM $table $where ORDER BY sort_order, id");
    $stmt->execute($scopeCol ? [$scopeVal] : []);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $upd = db()->prepare("UPDATE $table SET sort_order = ? WHERE id = ?");
    foreach ($ids as $i => $id) {
        $upd->execute([$i + 1, $id]);
    }
}

/**
 * Menggeser satu baris naik/turun satu posisi.
 */
function move_row(string $table, int $id, string $dir, ?string $scopeCol = null, $scopeVal = null): void
{
    normalize_order($table, $scopeCol, $scopeVal);

    $stmt = db()->prepare("SELECT sort_order FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetchColumn();
    if ($order === false) {
        return;
    }

    $op    = $dir === 'up' ? '<' : '>';
    $sort  = $dir === 'up' ? 'DESC' : 'ASC';
    $where = $scopeCol ? "AND $scopeCol = ?" : '';
    $stmt  = db()->prepare("SELECT id, sort_order FROM $table WHERE sort_order $op ? $where ORDER BY sort_order $sort LIMIT 1");
    $stmt->execute($scopeCol ? [$order, $scopeVal] : [$order]);
    $neighbor = $stmt->fetch();
    if (!$neighbor) {
        return;
    }

    $upd = db()->prepare("UPDATE $table SET sort_order = ? WHERE id = ?");
    $upd->execute([$neighbor['sort_order'], $id]);
    $upd->execute([$order, $neighbor['id']]);
}

/**
 * Membalik status aktif/nonaktif sebuah baris.
 */
function toggle_row(string $table, int $id): void
{
    db()->prepare("UPDATE $table SET is_active = CASE is_active WHEN 1 THEN 0 ELSE 1 END WHERE id = ?")
        ->execute([$id]);
}

function next_order(string $table, ?string $scopeCol = null, $scopeVal = null): int
{
    $where = $scopeCol ? "WHERE $scopeCol = ?" : '';
    $stmt  = db()->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM $table $where");
    $stmt->execute($scopeCol ? [$scopeVal] : []);

    return (int) $stmt->fetchColumn();
}
