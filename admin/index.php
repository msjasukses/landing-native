<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$stats = [
    'apps'          => (int) db()->query('SELECT COUNT(*) FROM apps')->fetchColumn(),
    'apps_active'   => (int) db()->query('SELECT COUNT(*) FROM apps WHERE is_active = 1')->fetchColumn(),
    'groups'        => (int) db()->query('SELECT COUNT(*) FROM app_groups')->fetchColumn(),
    'groups_active' => (int) db()->query('SELECT COUNT(*) FROM app_groups WHERE is_active = 1')->fetchColumn(),
    'features'      => (int) db()->query('SELECT COUNT(*) FROM features WHERE is_active = 1')->fetchColumn(),
];

$recent = db()->query(
    'SELECT a.*, g.name AS group_name FROM apps a
     JOIN app_groups g ON g.id = a.group_id
     ORDER BY a.id DESC LIMIT 6'
)->fetchAll();

admin_head('Dashboard');
?>

<div class="stat-grid">
  <div class="stat">
    <span class="stat__icon ic-indigo"><?= icon('grid', 20) ?></span>
    <div>
      <p class="stat__num"><?= $stats['apps'] ?></p>
      <p class="stat__label">Aplikasi terdaftar</p>
      <p class="stat__note"><?= $stats['apps_active'] ?> tampil di landing page</p>
    </div>
  </div>
  <div class="stat">
    <span class="stat__icon ic-violet"><?= icon('layers', 20) ?></span>
    <div>
      <p class="stat__num"><?= $stats['groups'] ?></p>
      <p class="stat__label">Grup / instansi</p>
      <p class="stat__note"><?= $stats['groups_active'] ?> aktif</p>
    </div>
  </div>
  <div class="stat">
    <span class="stat__icon ic-green"><?= icon('shield', 20) ?></span>
    <div>
      <p class="stat__num"><?= $stats['features'] ?></p>
      <p class="stat__label">Fitur header aktif</p>
      <p class="stat__note">Tampil pada bar putih di bawah hero</p>
    </div>
  </div>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card__head"><h2>Aplikasi terbaru</h2>
      <a class="btn btn--sm" href="<?= e(url('admin/apps.php')) ?>"><?= icon('plus', 15) ?> Kelola</a>
    </div>
    <?php if (!$recent): ?>
      <p class="muted">Belum ada aplikasi.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($recent as $app): ?>
          <li>
            <span class="list__icon ic-<?= e($app['color']) ?>"><?= icon($app['icon'], 16) ?></span>
            <div class="list__body">
              <strong><?= e($app['name']) ?></strong>
              <small><?= e($app['group_name']) ?><?= $app['description'] ? ' &middot; ' . e($app['description']) : '' ?></small>
            </div>
            <span class="badge <?= $app['is_active'] ? 'badge--on' : 'badge--off' ?>">
              <?= $app['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card__head"><h2>Pintasan</h2></div>
    <div class="shortcuts">
      <a class="shortcut" href="<?= e(url('admin/settings.php')) ?>">
        <span class="ic-indigo"><?= icon('sliders', 18) ?></span>
        <div><strong>Pengaturan Umum</strong><small>Judul, hero, warna, kontak</small></div>
      </a>
      <a class="shortcut" href="<?= e(url('admin/apps.php')) ?>">
        <span class="ic-sky"><?= icon('grid', 18) ?></span>
        <div><strong>Aplikasi</strong><small>Tambah / ubah menu aplikasi</small></div>
      </a>
      <a class="shortcut" href="<?= e(url('admin/groups.php')) ?>">
        <span class="ic-violet"><?= icon('layers', 18) ?></span>
        <div><strong>Grup Aplikasi</strong><small>Instansi & logo</small></div>
      </a>
      <a class="shortcut" href="<?= e(url('index.php')) ?>" target="_blank">
        <span class="ic-green"><?= icon('external', 18) ?></span>
        <div><strong>Lihat Landing Page</strong><small>Buka tampilan publik</small></div>
      </a>
    </div>
  </section>
</div>

<?php admin_foot(); ?>
