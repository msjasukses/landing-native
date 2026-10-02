<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$textFields = [
    'site_name', 'site_tagline', 'meta_description',
    'hero_title', 'hero_subtitle', 'hero_badge', 'hero_overlay',
    'section_label', 'accent_color', 'primary_color',
    'footer_text', 'contact_email', 'contact_phone', 'contact_address',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    foreach ($textFields as $field) {
        save_setting($field, trim((string) ($_POST[$field] ?? '')));
    }
    save_setting('show_search', isset($_POST['show_search']) ? '1' : '0');

    if (!empty($_POST['remove_hero'])) {
        delete_upload(setting('hero_image'));
        save_setting('hero_image', '');
    }
    if (!empty($_POST['remove_favicon'])) {
        delete_upload(setting('favicon'));
        save_setting('favicon', '');
    }

    if ($file = handle_upload('hero_image', setting('hero_image'))) {
        save_setting('hero_image', $file);
    }
    if ($file = handle_upload('favicon', setting('favicon'))) {
        save_setting('favicon', $file);
    }

    if (!flash()) {
        flash('Pengaturan berhasil disimpan.');
    }
    redirect('admin/settings.php');
}

$heroImg = upload_url(setting('hero_image'));
$favicon = upload_url(setting('favicon'));

admin_head('Pengaturan Umum');
?>

<form method="post" enctype="multipart/form-data" class="stack">
  <?= csrf_field() ?>

  <section class="card">
    <div class="card__head"><h2><?= icon('globe', 18) ?> Identitas Situs</h2></div>
    <div class="grid-2">
      <label class="field">
        <span>Nama situs <em>(judul tab browser)</em></span>
        <input type="text" name="site_name" value="<?= e(setting('site_name')) ?>" required>
      </label>
      <label class="field">
        <span>Tagline</span>
        <input type="text" name="site_tagline" value="<?= e(setting('site_tagline')) ?>">
      </label>
    </div>
    <label class="field">
      <span>Deskripsi meta <em>(SEO)</em></span>
      <textarea name="meta_description" rows="2"><?= e(setting('meta_description')) ?></textarea>
    </label>
    <div class="grid-2">
      <div class="field">
        <span>Favicon</span>
        <?php if ($favicon): ?>
          <div class="preview preview--sm"><img src="<?= e($favicon) ?>" alt="Favicon"></div>
          <label class="check"><input type="checkbox" name="remove_favicon" value="1"> Hapus favicon</label>
        <?php endif; ?>
        <input type="file" name="favicon" accept="image/*">
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card__head"><h2><?= icon('image', 18) ?> Hero / Banner Atas</h2></div>
    <label class="field">
      <span>Judul hero <em>(tekan Enter untuk pindah baris)</em></span>
      <textarea name="hero_title" rows="2"><?= e(setting('hero_title')) ?></textarea>
    </label>
    <label class="field">
      <span>Sub judul</span>
      <textarea name="hero_subtitle" rows="2"><?= e(setting('hero_subtitle')) ?></textarea>
    </label>
    <div class="grid-2">
      <label class="field">
        <span>Teks penegas (bold kecil)</span>
        <input type="text" name="hero_badge" value="<?= e(setting('hero_badge')) ?>">
      </label>
      <label class="field">
        <span>Kegelapan overlay: <output id="overlayOut"><?= e(setting('hero_overlay', '65')) ?></output>%</span>
        <input type="range" name="hero_overlay" min="0" max="90" step="5"
               value="<?= e(setting('hero_overlay', '65')) ?>" oninput="overlayOut.value = this.value">
      </label>
    </div>
    <div class="field">
      <span>Gambar latar hero <em>(disarankan 1920&times;600 px)</em></span>
      <?php if ($heroImg): ?>
        <div class="preview"><img src="<?= e($heroImg) ?>" alt="Hero"></div>
        <label class="check"><input type="checkbox" name="remove_hero" value="1"> Hapus gambar hero</label>
      <?php else: ?>
        <p class="muted">Belum ada gambar &mdash; hero memakai gradasi bawaan.</p>
      <?php endif; ?>
      <input type="file" name="hero_image" accept="image/*">
    </div>
  </section>

  <section class="card">
    <div class="card__head"><h2><?= icon('sliders', 18) ?> Tampilan Daftar Aplikasi</h2></div>
    <div class="grid-2">
      <label class="field">
        <span>Label seksi</span>
        <input type="text" name="section_label" value="<?= e(setting('section_label')) ?>">
      </label>
      <label class="field">
        <span>Warna label seksi</span>
        <input type="color" name="accent_color" value="<?= e(setting('accent_color', '#e11d48')) ?>">
      </label>
      <label class="field">
        <span>Warna utama <em>(aksen tautan & inisial logo)</em></span>
        <input type="color" name="primary_color" value="<?= e(setting('primary_color', '#4f46e5')) ?>">
      </label>
      <div class="field">
        <span>Kotak pencarian</span>
        <label class="check">
          <input type="checkbox" name="show_search" value="1" <?= setting('show_search', '1') === '1' ? 'checked' : '' ?>>
          Tampilkan pencarian bila aplikasi lebih dari 3
        </label>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card__head"><h2><?= icon('mail', 18) ?> Footer &amp; Kontak</h2></div>
    <label class="field">
      <span>Teks footer</span>
      <input type="text" name="footer_text" value="<?= e(setting('footer_text')) ?>">
    </label>
    <div class="grid-2">
      <label class="field">
        <span>Email</span>
        <input type="email" name="contact_email" value="<?= e(setting('contact_email')) ?>">
      </label>
      <label class="field">
        <span>Telepon</span>
        <input type="text" name="contact_phone" value="<?= e(setting('contact_phone')) ?>">
      </label>
    </div>
    <label class="field">
      <span>Alamat</span>
      <input type="text" name="contact_address" value="<?= e(setting('contact_address')) ?>">
    </label>
  </section>

  <div class="form-actions form-actions--sticky">
    <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan Pengaturan</button>
    <a class="btn" href="<?= e(url('index.php')) ?>" target="_blank"><?= icon('external', 16) ?> Pratinjau</a>
  </div>
</form>

<?php admin_foot(); ?>
