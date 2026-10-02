<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (current_user()) {
    redirect('admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $found = $stmt->fetch();

    if ($found && password_verify($password, $found['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $found['id'];
        redirect('admin/index.php');
    }

    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk &middot; Panel Pengaturan</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/admin.css') ?>">
</head>
<body class="auth">
<form class="auth__card" method="post">
  <?= csrf_field() ?>
  <span class="auth__logo"><?= icon('lock', 22) ?></span>
  <h1>Panel Pengaturan</h1>
  <p class="auth__sub"><?= e(setting('site_name', 'Landing Page')) ?></p>

  <?php if ($error): ?>
    <div class="alert alert--error"><?= icon('x', 16) ?><span><?= e($error) ?></span></div>
  <?php endif; ?>

  <label class="field">
    <span>Username</span>
    <input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
  </label>

  <label class="field">
    <span>Password</span>
    <input type="password" name="password" required>
  </label>

  <button class="btn btn--primary btn--block" type="submit">Masuk</button>
  <p class="auth__back"><a href="<?= e(url('index.php')) ?>">&larr; Kembali ke landing page</a></p>
</form>
</body>
</html>
