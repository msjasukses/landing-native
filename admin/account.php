<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $current  = (string) ($_POST['current_password'] ?? '');
    $new      = (string) ($_POST['new_password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || $username === '') {
        flash('Nama dan username wajib diisi.', 'error');
    } elseif (!password_verify($current, $user['password'])) {
        flash('Password saat ini salah.', 'error');
    } elseif ($new !== '' && strlen($new) < 6) {
        flash('Password baru minimal 6 karakter.', 'error');
    } elseif ($new !== '' && $new !== $confirm) {
        flash('Konfirmasi password baru tidak cocok.', 'error');
    } else {
        $exists = db()->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $exists->execute([$username, $user['id']]);

        if ((int) $exists->fetchColumn() > 0) {
            flash('Username sudah dipakai.', 'error');
        } else {
            $hash = $new !== '' ? password_hash($new, PASSWORD_DEFAULT) : $user['password'];
            db()->prepare('UPDATE users SET name = ?, username = ?, password = ? WHERE id = ?')
                ->execute([$name, $username, $hash, $user['id']]);
            flash('Akun berhasil diperbarui.');
        }
    }

    redirect('admin/account.php');
}

admin_head('Akun Admin');
?>

<div class="grid-side">
  <section class="card">
    <div class="card__head"><h2><?= icon('user', 18) ?> Data akun</h2></div>
    <form method="post" class="stack">
      <?= csrf_field() ?>

      <label class="field">
        <span>Nama tampilan</span>
        <input type="text" name="name" required value="<?= e($user['name']) ?>">
      </label>
      <label class="field">
        <span>Username</span>
        <input type="text" name="username" required value="<?= e($user['username']) ?>">
      </label>

      <hr class="sep">

      <label class="field">
        <span>Password saat ini <em>(wajib untuk menyimpan)</em></span>
        <input type="password" name="current_password" required autocomplete="current-password">
      </label>
      <div class="grid-2">
        <label class="field">
          <span>Password baru <em>(kosongkan bila tidak diganti)</em></span>
          <input type="password" name="new_password" autocomplete="new-password">
        </label>
        <label class="field">
          <span>Ulangi password baru</span>
          <input type="password" name="confirm_password" autocomplete="new-password">
        </label>
      </div>

      <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan Perubahan</button>
      </div>
    </form>
  </section>

  <section class="card">
    <div class="card__head"><h2><?= icon('shield', 18) ?> Catatan keamanan</h2></div>
    <ul class="notes">
      <li>Ganti password bawaan <code>admin123</code> segera setelah instalasi.</li>
      <li>Data tersimpan di file <code>data/landing.sqlite</code> &mdash; cadangkan berkala.</li>
      <li>Folder <code>data/</code> sebaiknya tidak bisa diakses publik saat dipasang di hosting.</li>
      <li>Gambar unggahan tersimpan di <code>assets/uploads/</code>.</li>
    </ul>
  </section>
</div>

<?php admin_foot(); ?>
