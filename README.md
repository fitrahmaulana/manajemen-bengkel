# Manajemen Bengkel

Aplikasi web untuk mengelola operasional bengkel seperti pencatatan servis, stok suku cadang, dan administrasi pelanggan. Dibangun menggunakan [Laravel](https://laravel.com).

## Persyaratan Sistem
- PHP >= 8.2 dengan ekstensi `mbstring`, `openssl`, `pdo`, `xml`, `ctype`, `json`, dan `bcmath`
- [Composer](https://getcomposer.org/)
- Node.js >= 18 dan npm
- Database MySQL/MariaDB atau database lain yang didukung Laravel

## Instalasi
1. Clone repositori ini.
2. Salin file `.env.example` menjadi `.env`.
3. Konfigurasi variabel lingkungan (lihat bagian [Konfigurasi `.env`](#konfigurasi-env)).
4. Jalankan `composer install` untuk mengunduh dependensi PHP.
5. Jalankan `npm install` untuk mengunduh dependensi frontend.
6. Jalankan `php artisan key:generate` untuk membuat application key.
7. Jalankan migrasi database dengan `php artisan migrate --seed`.
8. Jalankan server pengembangan:
   - Backend: `php artisan serve`
   - Frontend asset bundler: `npm run dev`

### Instalasi Cepat (Command Line)
Salin dan jalankan perintah berikut untuk menyiapkan proyek dari awal:

```bash
git clone https://github.com/fitrahmaulana/manajemen-bengkel.git
cd manajemen-bengkel
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan serve
npm run dev
```

## Konfigurasi `.env`
Sesuaikan variabel penting pada file `.env`:

```env
APP_NAME="Manajemen Bengkel"
APP_ENV=local
APP_KEY=base64:...
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bengkel
DB_USERNAME=root
DB_PASSWORD=password
```

Ubah nilai di atas sesuai dengan lingkungan Anda.

## Contoh Penggunaan
Setelah server berjalan, buka `http://localhost:8000` di browser. Contoh alur penggunaan:
1. Masuk ke aplikasi.
2. Tambahkan data kendaraan atau pelanggan.
3. Buat order servis baru dan simpan.

## Menjalankan Tes
- Tes PHP: `./vendor/bin/phpunit` atau `php artisan test`
- Tes JavaScript: `npm test`

## Lisensi
Proyek ini dirilis di bawah lisensi [MIT](LICENSE).

