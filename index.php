<?php
require __DIR__ . '/app/bootstrap.php';

$groups   = active_groups();
$features = active_features();
$heroImg  = upload_url(setting('hero_image'));
$favicon  = upload_url(setting('favicon'));
$overlay  = max(0, min(90, (int) setting('hero_overlay', '65'))) / 100;
$showSearch = setting('show_search', '1') === '1';
$totalApps  = array_sum(array_map(static fn ($g) => count($g['apps']), $groups));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_name', 'Landing Page')) ?></title>
<meta name="description" content="<?= e(setting('meta_description')) ?>">
<?php if ($favicon): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= e(url('assets/css/site.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/site.css') ?>">
<style>
  :root {
    --accent: <?= e(setting('accent_color', '#e11d48')) ?>;
    --primary: <?= e(setting('primary_color', '#4f46e5')) ?>;
    --hero-overlay: <?= $overlay ?>;
  }
</style>
</head>
<body>

<header class="hero<?= $heroImg ? '' : ' hero--plain' ?>"
        <?= $heroImg ? 'style="background-image:url(\'' . e($heroImg) . '\')"' : '' ?>>
  <div class="hero__scrim"></div>
  <div class="hero__inner container">
    <h1 class="hero__title"><?= nl2br(e(setting('hero_title', setting('site_name')))) ?></h1>
    <?php if (setting('hero_subtitle')): ?>
      <p class="hero__subtitle"><?= e(setting('hero_subtitle')) ?></p>
    <?php endif; ?>
    <?php if (setting('hero_badge')): ?>
      <p class="hero__badge"><?= e(setting('hero_badge')) ?></p>
    <?php endif; ?>
  </div>
</header>

<?php if ($features): ?>
<section class="features container" aria-label="Keunggulan">
  <div class="features__card">
    <?php foreach ($features as $f): ?>
      <div class="feature">
        <span class="feature__icon"><?= icon($f['icon'], 20) ?></span>
        <div>
          <p class="feature__title"><?= e($f['title']) ?></p>
          <?php if ($f['subtitle']): ?><p class="feature__sub"><?= e($f['subtitle']) ?></p><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<main class="container main">
  <p class="section-label"><?= e(setting('section_label', 'AVAILABLE APPS')) ?></p>

  <?php if ($showSearch && $totalApps > 3): ?>
    <div class="search">
      <?= icon('search', 18) ?>
      <input type="search" id="appSearch" placeholder="Cari aplikasi&hellip;" autocomplete="off"
             aria-label="Cari aplikasi">
    </div>
  <?php endif; ?>

  <?php if (!$groups): ?>
    <p class="empty">Belum ada aplikasi yang ditampilkan. Tambahkan lewat
      <a href="<?= e(url('admin/')) ?>">panel pengaturan</a>.</p>
  <?php endif; ?>

  <?php foreach ($groups as $group): ?>
    <?php $logo = upload_url($group['logo']); ?>
    <section class="group" data-group>
      <div class="group__head">
        <div class="group__logo">
          <?php if ($logo): ?>
            <img src="<?= e($logo) ?>" alt="Logo <?= e($group['name']) ?>">
          <?php else: ?>
            <span class="group__logo-text"><?= e(initials($group['name'])) ?></span>
          <?php endif; ?>
        </div>
        <h2 class="group__name"><?= e($group['name']) ?></h2>
        <?php if ($group['tagline']): ?>
          <p class="group__tagline"><?= e($group['tagline']) ?></p>
        <?php endif; ?>
      </div>

      <?php if (!$group['apps']): ?>
        <p class="empty empty--sm">Belum ada aplikasi pada grup ini.</p>
      <?php else: ?>
      <div class="apps">
        <?php foreach ($group['apps'] as $app): ?>
          <a class="app-card" href="<?= e($app['url'] !== '' ? $app['url'] : '#') ?>"
             <?= $app['target_blank'] ? 'target="_blank" rel="noopener"' : '' ?>
             data-name="<?= e(mb_strtolower($app['name'] . ' ' . $app['description'])) ?>">
            <span class="app-card__icon ic-<?= e($app['color']) ?>"><?= icon($app['icon'], 20) ?></span>
            <span class="app-card__name"><?= e($app['name']) ?></span>
            <?php if ($app['description']): ?>
              <span class="app-card__desc"><?= e($app['description']) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <p class="apps__none" hidden>Aplikasi tidak ditemukan.</p>
    </section>
  <?php endforeach; ?>
</main>

<footer class="footer">
  <div class="container footer__inner">
    <p class="footer__text"><?= e(setting('footer_text')) ?></p>
    <ul class="footer__meta">
      <?php if (setting('contact_email')): ?>
        <li><?= icon('mail', 15) ?><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li>
      <?php endif; ?>
      <?php if (setting('contact_phone')): ?>
        <li><?= icon('phone', 15) ?><span><?= e(setting('contact_phone')) ?></span></li>
      <?php endif; ?>
      <?php if (setting('contact_address')): ?>
        <li><?= icon('map-pin', 15) ?><span><?= e(setting('contact_address')) ?></span></li>
      <?php endif; ?>
      <li><?= icon('settings', 15) ?><a href="<?= e(url('admin/')) ?>">Pengaturan</a></li>
    </ul>
  </div>
</footer>

<script src="<?= e(url('assets/js/site.js')) ?>?v=<?= filemtime(APP_ROOT . '/assets/js/site.js') ?>"></script>
</body>
</html>
