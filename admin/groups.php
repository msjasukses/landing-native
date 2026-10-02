<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'save';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id) {
        $stmt = db()->prepare('SELECT logo FROM app_groups WHERE id = ?');
        $stmt->execute([$id]);
        delete_upload((string) $stmt->fetchColumn());
        db()->prepare('DELETE FROM app_groups WHERE id = ?')->execute([$id]);
        flash('Grup beserta aplikasi di dalamnya dihapus.');
    } elseif ($action === 'toggle' && $id) {
        toggle_row('app_groups', $id);
        flash('Status grup diperbarui.');
    } elseif (($action === 'up' || $action === 'down') && $id) {
        move_row('app_groups', $id, $action);
    } else {
        $name    = trim((string) ($_POST['name'] ?? ''));
        $tagline = trim((string) ($_POST['tagline'] ?? ''));
        $active  = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            flash('Nama grup wajib diisi.', 'error');
        } elseif ($id) {
            $stmt = db()->prepare('SELECT logo FROM app_groups WHERE id = ?');
            $stmt->execute([$id]);
            $logo = (string) $stmt->fetchColumn();

            if (!empty($_POST['remove_logo'])) {
                delete_upload($logo);
                $logo = '';
            }
            if ($file = handle_upload('logo', $logo)) {
                $logo = $file;
            }

            db()->prepare('UPDATE app_groups SET name = ?, tagline = ?, logo = ?, is_active = ? WHERE id = ?')
                ->execute([$name, $tagline, $logo, $active, $id]);
            flash('Grup diperbarui.');
        } else {
            $logo = handle_upload('logo') ?? '';
            db()->prepare('INSERT INTO app_groups (name, tagline, logo, is_active, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute([$name, $tagline, $logo, $active, next_order('app_groups')]);
            flash('Grup ditambahkan.');
        }
    }

    redirect('admin/groups.php');
}

$editing = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM app_groups WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$rows = db()->query(
    'SELECT g.*, (SELECT COUNT(*) FROM apps a WHERE a.group_id = g.id) AS app_count
     FROM app_groups g ORDER BY g.sort_order, g.id'
)->fetchAll();

$logoUrl = upload_url($editing['logo'] ?? null);

admin_head('Grup Aplikasi');
?>

<div class="grid-side">
  <section class="card">
    <div class="card__head">
      <h2>Daftar grup / instansi</h2>
      <span class="muted"><?= count($rows) ?> grup</span>
    </div>

    <?php if (!$rows): ?>
      <p class="muted">Belum ada grup. Grup adalah judul besar (mis. nama sekolah) di atas daftar aplikasi.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th style="width:52px">Logo</th><th>Nama</th><th>Tagline</th><th>Aplikasi</th><th>Status</th><th class="ta-r">Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): $logo = upload_url($row['logo']); ?>
          <tr>
            <td>
              <span class="thumb">
                <?php if ($logo): ?><img src="<?= e($logo) ?>" alt=""><?php else: ?><?= e(initials($row['name'])) ?><?php endif; ?>
              </span>
            </td>
            <td><strong><?= e($row['name']) ?></strong></td>
            <td class="muted"><?= e($row['tagline']) ?></td>
            <td><span class="pill"><?= (int) $row['app_count'] ?></span></td>
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
                <form method="post" class="inline"
                      data-confirm="Hapus grup &quot;<?= e($row['name']) ?>&quot; beserta <?= (int) $row['app_count'] ?> aplikasi di dalamnya?">
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
    <div class="card__head"><h2><?= $editing ? 'Ubah grup' : 'Tambah grup' ?></h2></div>
    <form method="post" enctype="multipart/form-data" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

      <label class="field">
        <span>Nama grup / instansi</span>
        <input type="text" name="name" required value="<?= e($editing['name'] ?? '') ?>" placeholder="DEMO">
      </label>
      <label class="field">
        <span>Tagline</span>
        <input type="text" name="tagline" value="<?= e($editing['tagline'] ?? '') ?>"
               placeholder="Sistem Informasi Sekolah Terintegrasi">
      </label>
      <div class="field">
        <span>Logo</span>
        <?php if ($logoUrl): ?>
          <div class="preview preview--sm"><img src="<?= e($logoUrl) ?>" alt="Logo"></div>
          <label class="check"><input type="checkbox" name="remove_logo" value="1"> Hapus logo</label>
        <?php endif; ?>
        <input type="file" name="logo" accept="image/*">
        <small class="muted">Kosongkan untuk memakai inisial nama grup.</small>
      </div>
      <label class="check">
        <input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>>
        Tampilkan di landing page
      </label>

      <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan</button>
        <?php if ($editing): ?>
          <a class="btn" href="<?= e(url('admin/groups.php')) ?>">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </section>
</div>

<?php admin_foot(); ?>
