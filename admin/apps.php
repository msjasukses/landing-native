<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$groups = db()->query('SELECT * FROM app_groups ORDER BY sort_order, id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'save';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM apps WHERE id = ?')->execute([$id]);
        flash('Aplikasi dihapus.');
    } elseif ($action === 'toggle' && $id) {
        toggle_row('apps', $id);
        flash('Status aplikasi diperbarui.');
    } elseif (($action === 'up' || $action === 'down') && $id) {
        $stmt = db()->prepare('SELECT group_id FROM apps WHERE id = ?');
        $stmt->execute([$id]);
        move_row('apps', $id, $action, 'group_id', (int) $stmt->fetchColumn());
    } else {
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $name    = trim((string) ($_POST['name'] ?? ''));
        $desc    = trim((string) ($_POST['description'] ?? ''));
        $urlVal  = trim((string) ($_POST['url'] ?? ''));
        $iconVal = trim((string) ($_POST['icon'] ?? 'grid'));
        $color   = trim((string) ($_POST['color'] ?? 'indigo'));
        $blank   = isset($_POST['target_blank']) ? 1 : 0;
        $active  = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || !$groupId) {
            flash('Nama aplikasi dan grup wajib diisi.', 'error');
        } else {
            if ($urlVal === '') {
                $urlVal = '#';
            }
            if ($id) {
                db()->prepare('UPDATE apps SET group_id = ?, name = ?, description = ?, url = ?, icon = ?, color = ?, target_blank = ?, is_active = ? WHERE id = ?')
                    ->execute([$groupId, $name, $desc, $urlVal, $iconVal, $color, $blank, $active, $id]);
                flash('Aplikasi diperbarui.');
            } else {
                db()->prepare('INSERT INTO apps (group_id, name, description, url, icon, color, target_blank, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$groupId, $name, $desc, $urlVal, $iconVal, $color, $blank, $active, next_order('apps', 'group_id', $groupId)]);
                flash('Aplikasi ditambahkan.');
            }
        }
    }

    redirect('admin/apps.php' . (!empty($_POST['return_group']) ? '?group=' . (int) $_POST['return_group'] : ''));
}

$editing = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM apps WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$filter = isset($_GET['group']) ? (int) $_GET['group'] : 0;
$sql    = 'SELECT a.*, g.name AS group_name FROM apps a JOIN app_groups g ON g.id = a.group_id';
$params = [];
if ($filter) {
    $sql .= ' WHERE a.group_id = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY g.sort_order, g.id, a.sort_order, a.id';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

admin_head('Aplikasi');
?>

<?php if (!$groups): ?>
  <div class="alert alert--error">
    <?= icon('x', 16) ?>
    <span>Belum ada grup aplikasi. Buat grup dulu di menu <a href="<?= e(url('admin/groups.php')) ?>">Grup Aplikasi</a>.</span>
  </div>
<?php else: ?>

<div class="grid-side">
  <section class="card">
    <div class="card__head">
      <h2>Daftar aplikasi</h2>
      <form method="get" class="inline">
        <select name="group" onchange="this.form.submit()" class="select select--sm">
          <option value="0">Semua grup</option>
          <?php foreach ($groups as $g): ?>
            <option value="<?= (int) $g['id'] ?>" <?= $filter === (int) $g['id'] ? 'selected' : '' ?>>
              <?= e($g['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <?php if (!$rows): ?>
      <p class="muted">Belum ada aplikasi pada tampilan ini.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th style="width:44px"></th><th>Aplikasi</th><th>Grup</th><th>URL</th><th>Status</th><th class="ta-r">Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><span class="list__icon ic-<?= e($row['color']) ?>"><?= icon($row['icon'], 16) ?></span></td>
            <td>
              <strong><?= e($row['name']) ?></strong>
              <?php if ($row['description']): ?><small class="muted db"><?= e($row['description']) ?></small><?php endif; ?>
            </td>
            <td class="muted"><?= e($row['group_name']) ?></td>
            <td class="muted trunc"><?= e($row['url']) ?></td>
            <td>
              <form method="post" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <input type="hidden" name="return_group" value="<?= $filter ?>">
                <button type="submit" class="badge <?= $row['is_active'] ? 'badge--on' : 'badge--off' ?>">
                  <?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </button>
              </form>
            </td>
            <td class="ta-r">
              <div class="row-actions">
                <form method="post" class="inline"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="up"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <input type="hidden" name="return_group" value="<?= $filter ?>">
                  <button class="icon-btn" title="Naik"><?= icon('arrow-up', 15) ?></button>
                </form>
                <form method="post" class="inline"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="down"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <input type="hidden" name="return_group" value="<?= $filter ?>">
                  <button class="icon-btn" title="Turun"><?= icon('arrow-down', 15) ?></button>
                </form>
                <a class="icon-btn" title="Ubah" href="?edit=<?= (int) $row['id'] ?><?= $filter ? '&group=' . $filter : '' ?>"><?= icon('edit', 15) ?></a>
                <form method="post" class="inline" data-confirm="Hapus aplikasi &quot;<?= e($row['name']) ?>&quot;?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <input type="hidden" name="return_group" value="<?= $filter ?>">
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
    <div class="card__head"><h2><?= $editing ? 'Ubah aplikasi' : 'Tambah aplikasi' ?></h2></div>
    <form method="post" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
      <input type="hidden" name="return_group" value="<?= $filter ?>">

      <label class="field">
        <span>Grup</span>
        <select name="group_id" class="select" required>
          <?php foreach ($groups as $g): ?>
            <option value="<?= (int) $g['id'] ?>"
              <?= (int) ($editing['group_id'] ?? $filter) === (int) $g['id'] ? 'selected' : '' ?>>
              <?= e($g['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Nama aplikasi</span>
        <input type="text" name="name" required value="<?= e($editing['name'] ?? '') ?>" placeholder="DATA CENTER">
      </label>
      <label class="field">
        <span>Deskripsi singkat</span>
        <input type="text" name="description" value="<?= e($editing['description'] ?? '') ?>" placeholder="Pusat Data Sekolah">
      </label>
      <label class="field">
        <span>URL tujuan</span>
        <input type="text" name="url" value="<?= e($editing['url'] ?? '') ?>" placeholder="https://datacenter.sekolah.sch.id">
      </label>
      <div class="field">
        <span>Ikon</span>
        <?= icon_picker('icon', $editing['icon'] ?? 'grid') ?>
      </div>
      <div class="field">
        <span>Warna ikon</span>
        <?= color_picker('color', $editing['color'] ?? 'indigo') ?>
      </div>
      <label class="check">
        <input type="checkbox" name="target_blank" value="1" <?= ($editing['target_blank'] ?? 1) ? 'checked' : '' ?>>
        Buka di tab baru
      </label>
      <label class="check">
        <input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>>
        Tampilkan di landing page
      </label>

      <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan</button>
        <?php if ($editing): ?>
          <a class="btn" href="<?= e(url('admin/apps.php' . ($filter ? '?group=' . $filter : ''))) ?>">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </section>
</div>

<?php endif; ?>

<?php admin_foot(); ?>
