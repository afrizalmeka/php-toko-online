# TokoKu — Toko Online Sederhana

Aplikasi toko online berbasis PHP dan SQLite untuk keperluan pembelajaran pengujian perangkat lunak.

## Fitur
- Login & registrasi pengguna
- Daftar produk dengan pencarian
- Keranjang belanja
- Checkout dan riwayat pesanan
- Panel admin (kelola produk & pesanan)

## Cara Menjalankan

### Prasyarat
- PHP 8.x (terinstall di komputer)
- Ekstensi PHP: `pdo_sqlite` (biasanya sudah aktif secara default)

### Langkah Instalasi

1. **Clone atau unduh** folder `php-toko-online` ke komputer Anda.

2. **Masuk ke folder project:**
   ```bash
   cd php-toko-online
   ```

3. **Jalankan server PHP bawaan:**
   ```bash
   php -S localhost:8000
   ```

4. **Buka browser** dan akses:
   ```
   http://localhost:8000
   ```

5. Database SQLite akan **dibuat otomatis** saat pertama kali halaman diakses. Tidak perlu konfigurasi database tambahan.

## Akun Demo

| Role  | Email                | Password  |
|-------|----------------------|-----------|
| Admin | admin@tokoku.com     | admin123  |
| User  | budi@mail.com        | user123   |

## Struktur Folder

```
php-toko-online/
├── index.php          # Halaman utama (daftar produk)
├── login.php          # Halaman login
├── register.php       # Halaman registrasi
├── cart.php           # Keranjang belanja
├── checkout.php       # Proses checkout
├── orders.php         # Riwayat pesanan
├── order_detail.php   # Detail pesanan
├── admin.php          # Panel admin
├── logout.php         # Proses logout
├── config.php         # Konfigurasi database
├── css/style.css      # Stylesheet
├── php/               # File logika PHP
└── database/
    └── init.php       # Inisialisasi database otomatis
```

## Catatan untuk Mahasiswa

Aplikasi ini adalah **versi latihan** yang mengandung bug untuk keperluan praktikum pengujian perangkat lunak. Temukan dan perbaiki bug yang ada sebagai bagian dari tugas praktikum.
