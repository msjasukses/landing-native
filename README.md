# Landing Page Aplikasi + Panel Pengaturan

Portal daftar aplikasi (gaya *Digital Learning Management System*) dengan panel admin
untuk mengatur seluruh isi halaman. PHP murni + SQLite, tanpa dependensi eksternal.

## Menjalankan

1. Taruh folder ini di `C:\laragon\www\landing` (sudah).
2. Jalankan Laragon (Apache + PHP 8.x).
3. Buka:
   - Landing page &rarr; <http://localhost/landing/>
   - Panel pengaturan &rarr; <http://localhost/landing/admin/>

Database `data/landing.sqlite` dibuat otomatis beserta data contoh saat halaman
pertama kali dibuka. Untuk mereset ke data awal, hapus file tersebut.

**Login default:** `admin` / `admin123` &mdash; ganti lewat menu **Akun Admin**.

## Menu pengaturan

| Menu | Isi |
|------|-----|
| **Dashboard** | Ringkasan jumlah aplikasi, grup, fitur, dan pintasan |
| **Pengaturan Umum** | Nama situs, meta description, favicon, judul/sub judul hero, gambar hero + kegelapan overlay, label seksi, warna aksen & warna utama, kotak pencarian, teks footer, kontak |
| **Fitur Header** | Item pada bar putih di bawah hero (ikon, judul, keterangan, urutan, aktif/nonaktif) |
| **Grup Aplikasi** | Instansi/sekolah: nama, tagline, logo, urutan, aktif/nonaktif |
| **Aplikasi** | Kartu aplikasi: grup, nama, deskripsi, URL, ikon (48 pilihan), warna, buka di tab baru, urutan, aktif/nonaktif |
| **Akun Admin** | Nama tampilan, username, ganti password |

Semua daftar punya tombol naik/turun untuk mengatur urutan tampil, tombol status
untuk mengaktifkan/menonaktifkan tanpa menghapus, serta tombol ubah dan hapus.

## Struktur folder

```
landing/
├── index.php              landing page publik
├── admin/                 panel pengaturan (login wajib)
│   ├── _init.php          proteksi login
│   ├── _layout.php        sidebar, topbar, icon & color picker
│   ├── index.php          dashboard
│   ├── settings.php       pengaturan umum
│   ├── features.php       fitur header
│   ├── groups.php         grup aplikasi
│   ├── apps.php           daftar aplikasi
│   ├── account.php        akun admin
│   ├── login.php / logout.php
├── app/
│   ├── bootstrap.php      pemuat tunggal
│   ├── config.php         path & base URL otomatis
│   ├── db.php             skema SQLite + data awal
│   ├── helpers.php        setting, flash, CSRF, upload, urutan
│   └── icons.php          48 ikon SVG inline
├── assets/
│   ├── css/site.css       tampilan landing page
│   ├── css/admin.css      tampilan panel
│   ├── js/site.js         pencarian aplikasi
│   ├── js/admin.js        konfirmasi hapus
│   └── uploads/           gambar hero, logo, favicon
└── data/landing.sqlite    database (jangan diakses publik)
```

## Catatan keamanan

- Form admin dilindungi token CSRF; password disimpan dengan `password_hash()`.
- Unggahan dibatasi 4 MB dan divalidasi berdasarkan MIME type gambar.
- `data/.htaccess` memblokir akses langsung ke database di server Apache.
