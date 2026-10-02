<?php
/**
 * Kerangka tampilan panel admin (sidebar + topbar).
 */

function admin_menu(): array
{
    return [
        ['file' => 'index.php',    'label' => 'Dashboard',      'icon' => 'home'],
        ['file' => 'settings.php', 'label' => 'Pengaturan Umum', 'icon' => 'sliders'],
        ['file' => 'features.php', 'label' => 'Fitur Header',    'icon' => 'shield'],
        ['file' => 'groups.php',   'label' => 'Grup Aplikasi',   'icon' => 'layers'],
        ['file' => 'apps.php',     'label' => 'Aplikasi',        'icon' => 'grid'],
        ['file' => 'account.php',  'label' => 'Akun Admin',      'icon' => 'user'],
    ];
}

function admin_head(string $title): void
{
    $current = basename($_SERVER['SCRIPT_NAME']);
    $user    = current_user();
    $flash   = flash();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> &middot; Panel Pengaturan</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/admin.css') ?>">
</head>
<body>
<input type="checkbox" id="navToggle" class="nav-toggle" hidden>

<aside class="sidebar">
  <div class="sidebar__brand">
    <span class="sidebar__logo"><?= icon('layers', 20) ?></span>
    <div>
      <strong>Panel Pengaturan</strong>
      <small><?= e(setting('site_name', 'Landing Page')) ?></small>
    </div>
  </div>

  <nav class="sidebar__nav">
    <?php foreach (admin_menu() as $item): ?>
      <a href="<?= e(url('admin/' . $item['file'])) ?>"
         class="nav-item<?= $current === $item['file'] ? ' is-active' : '' ?>">
        <?= icon($item['icon'], 18) ?><span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar__foot">
    <a href="<?= e(url('index.php')) ?>" target="_blank" class="nav-item">
      <?= icon('external', 18) ?><span>Lihat Landing Page</span>
    </a>
    <a href="<?= e(url('admin/logout.php')) ?>" class="nav-item nav-item--danger">
      <?= icon('logout', 18) ?><span>Keluar</span>
    </a>
  </div>
</aside>

<div class="shell">
  <header class="topbar">
    <label for="navToggle" class="topbar__burger" aria-label="Menu"><?= icon('menu', 20) ?></label>
    <h1 class="topbar__title"><?= e($title) ?></h1>
    <div class="topbar__user">
      <span class="avatar"><?= e(initials($user['name'] ?? 'A')) ?></span>
      <div>
        <strong><?= e($user['name'] ?? '') ?></strong>
        <small>@<?= e($user['username'] ?? '') ?></small>
      </div>
    </div>
  </header>

  <main class="content">
    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>">
        <?= icon($flash['type'] === 'error' ? 'x' : 'check', 16) ?>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>
    <?php
}

function admin_foot(): void
{
    ?>
  </main>
</div>

<label for="navToggle" class="nav-backdrop"></label>
<script src="<?= e(url('assets/js/admin.js')) ?>?v=<?= filemtime(APP_ROOT . '/assets/js/admin.js') ?>"></script>
</body>
</html>
    <?php
}

/**
 * Grid pemilih ikon berbentuk radio button.
 */
function icon_picker(string $name, string $selected): string
{
    $html = '<div class="picker picker--icon">';
    foreach (array_keys(icon_list()) as $key) {
        $checked = $key === $selected ? ' checked' : '';
        $html .= '<label class="picker__opt" title="' . e($key) . '">'
            . '<input type="radio" name="' . e($name) . '" value="' . e($key) . '"' . $checked . '>'
            . '<span>' . icon($key, 18) . '</span></label>';
    }

    return $html . '</div>';
}

/**
 * Pemilih warna kartu aplikasi.
 */
function color_picker(string $name, string $selected): string
{
    $html = '<div class="picker picker--color">';
    foreach (color_list() as $color) {
        $checked = $color === $selected ? ' checked' : '';
        $html .= '<label class="picker__opt" title="' . e($color) . '">'
            . '<input type="radio" name="' . e($name) . '" value="' . e($color) . '"' . $checked . '>'
            . '<span class="ic-' . e($color) . '">' . icon('check', 16) . '</span></label>';
    }

    return $html . '</div>';
}
