<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'save';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM features WHERE id = ?')->execute([$id]);
        flash('Fitur dihapus.');
    } elseif ($action === 'toggle' && $id) {
        toggle_row('features', $id);
        flash('Status fitur diperbarui.');
    } elseif (($action === 'up' || $action === 'down') && $id) {
        move_row('features', $id, $action);
    } else {
        $data = [
            trim((string) ($_POST['icon'] ?? 'lock')),
            trim((string) ($_POST['title'] ?? '')),
            trim((string) ($_POST['subtitle'] ?? '')),
            isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data[1] === '') {
            flash('Judul fitur wajib diisi.', 'error');
        } elseif ($id) {
            db()->prepare('UPDATE features SET icon = ?, title = ?, subtitle = ?, is_active = ? WHERE id = ?')
                ->execute([...$data, $id]);
            flash('Fitur diperbarui.');
        } else {
            db()->prepare('INSERT INTO features (icon, title, subtitle, is_active, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute([...$data, next_order('features')]);
            flash('Fitur ditambahkan.');
        }
    }

    redirect('admin/features.php');
}

$editing = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM features WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$rows = db()->query('SELECT * FROM features ORDER BY sort_order, id')->fetchAll();

admin_head('Fitur Header');
?>

<div class="grid-side">
  <section class="card">
    <div class="card__head">
      <h2>Daftar fitur</h2>
      <span class="muted"><?= count($rows) ?> item</span>
    </div>

    <?php if (!$rows): ?>
      <p class="muted">Belum ada fitur. Tambahkan lewat form di samping.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th style="width:44px"></th><th>Judul</th><th>Keterangan</th><th>Status</th><th class="ta-r">Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><span class="list__icon ic-slate"><?= icon($row['icon'], 16) ?></span></td>
            <td><strong><?= e($row['title']) ?></strong></td>
            <td class="muted"><?= e($row['subtitle']) ?></td>
            <td>
              <form method="post" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button type="submit" class="badge <?= $row['is_active'] ? 'badge--on' : 'badge--off' ?>">
                  <?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </button>
              </form>
            </td>
            <td class="ta-r">
              <div class="row-actions">
                <form method="post" class="inline"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="up"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="icon-btn" title="Naik"><?= icon('arrow-up', 15) ?></button>
                </form>
                <form method="post" class="inline"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="down"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="icon-btn" title="Turun"><?= icon('arrow-down', 15) ?></button>
                </form>
                <a class="icon-btn" title="Ubah" href="?edit=<?= (int) $row['id'] ?>"><?= icon('edit', 15) ?></a>
                <form method="post" class="inline" data-confirm="Hapus fitur &quot;<?= e($row['title']) ?>&quot;?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="icon-btn icon-btn--danger" title="Hapus"><?= icon('trash', 15) ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card__head"><h2><?= $editing ? 'Ubah fitur' : 'Tambah fitur' ?></h2></div>
    <form method="post" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

      <label class="field">
        <span>Judul</span>
        <input type="text" name="title" required value="<?= e($editing['title'] ?? '') ?>" placeholder="Login Protect">
      </label>
      <label class="field">
        <span>Keterangan</span>
        <input type="text" name="subtitle" value="<?= e($editing['subtitle'] ?? '') ?>" placeholder="Keamanan Login akun">
      </label>
      <div class="field">
        <span>Ikon</span>
        <?= icon_picker('icon', $editing['icon'] ?? 'lock') ?>
      </div>
      <label class="check">
        <input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>>
        Tampilkan di landing page
      </label>

      <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan</button>
        <?php if ($editing): ?>
          <a class="btn" href="<?= e(url('admin/features.php')) ?>">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </section>
</div>

<?php admin_foot(); ?>
