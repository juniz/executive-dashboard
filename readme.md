# Panduan Deployment Executive Dashboard di aaPanel

Dokumen ini menjelaskan langkah-langkah untuk melakukan deployment aplikasi **Executive Dashboard** (Laravel + Inertia Svelte) pada server yang menggunakan **aaPanel**.

## 1. Persiapan Environment di aaPanel

Sebelum mengupload kode, pastikan server Anda sudah terinstal software berikut melalui menu **App Store** di aaPanel:

*   **Nginx** (Versi terbaru atau stable)
*   **MySQL** (Versi 8.0 disarankan)
*   **PHP 8.2** atau **8.3**
*   **Node.js Version Manager** (Instal Node.js v18 atau v20 LTS)
*   **Redis** (Opsional, untuk caching performa tinggi)

### Konfigurasi PHP
Pastikan extension berikut aktif di pengaturan PHP (App Store > PHP > Setting > Install extensions):
*   `fileinfo`
*   `opcache`
*   `redis` (jika menggunakan Redis)
*   `bcmath`

---

## 2. Membuat Website Baru

1.  Masuk ke menu **Website** > **Add site**.
2.  Masukkan **Domain Name** Anda.
3.  Pilih **PHP Version** (misal PHP-8.2).
4.  Pilih **MySQL** jika ingin membuat database sekaligus.
5.  Klik **Submit**.

---

## 3. Upload & Deploy Source Code

### Opsi A: Menggunakan Git (Disarankan)
1.  Buka terminal di aaPanel atau SSH.
2.  Masuk ke direktori website: `cd /www/wwwroot/nama-domain-anda`
3.  Hapus file default: `rm -rf *`
4.  Clone repository: `git clone https://github.com/username/repo.git .`

### Opsi B: Upload Manual
1.  Compress project Anda (kecuali `node_modules` dan `vendor`).
2.  Upload ke File Manager aaPanel di direktori `/www/wwwroot/nama-domain-anda`.
3.  Extract file tersebut.

---

## 4. Instalasi Dependensi

Jalankan perintah berikut di terminal (SSH) di dalam folder project:

### Install PHP Library
```bash
composer install --no-dev --optimize-autoloader
```

### Konfigurasi Environment
1.  Copy file `.env.example` menjadi `.env`:
    ```bash
    cp .env.example .env
    ```
2.  Edit file `.env` melalui File Manager aaPanel:
    *   `APP_ENV=production`
    *   `APP_DEBUG=false`
    *   `APP_URL=https://nama-domain-anda.com`
    *   Isi konfigurasi `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
3.  Generate Key:
    ```bash
    php artisan key:generate
    ```

### Build Frontend (Vite + Svelte)
```bash
npm install
npm run build
```

---

## 5. Konfigurasi Website di aaPanel

Klik menu **Settings** pada website yang baru dibuat:

### Site Directory
*   **Running directory**: Ubah dari `/` menjadi `/public`.
*   Klik **Save**.

### URL Rewrite
*   Pilih template **laravel**.
*   Pastikan isinya seperti ini:
    ```nginx
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    ```
*   Klik **Save**.

### SSL
*   Masuk ke tab **SSL**.
*   Pilih **Let's Encrypt**.
*   Centang domain Anda dan klik **Apply**.
*   Aktifkan **Force HTTPS**.

---

## 6. Izin Folder (Permissions)

Laravel membutuhkan akses tulis ke beberapa folder. Jalankan perintah ini:
```bash
chown -R www:www /www/wwwroot/nama-domain-anda
chmod -R 775 /www/wwwroot/nama-domain-anda/storage
chmod -R 775 /www/wwwroot/nama-domain-anda/bootstrap/cache
```

---

## 7. Database Migration

Jalankan migrasi untuk membuat tabel di database:
```bash
php artisan migrate --force
```

---

## 8. Konfigurasi Tambahan (Opsional)

### Laravel Scheduler (Cron Job)
Agar fitur otomatis berjalan, tambahkan Cron Job di aaPanel:
1.  Masuk ke menu **Cron**.
2.  Type: **Shell Script**.
3.  Name: `Laravel Scheduler`.
4.  Cycle: **Every Minute**.
5.  Script content:
    ```bash
    php /www/wwwroot/nama-domain-anda/artisan schedule:run >> /dev/null 2>&1
    ```

### 9. Menjalankan SSR (Server-Side Rendering)

Untuk mengaktifkan SSR pada aaPanel, ikuti langkah-langkah berikut:

1.  **Build SSR Bundle**:
    Jalankan perintah ini di terminal:
    ```bash
    npm run build:ssr
    ```
    Ini akan menghasilkan file di folder `bootstrap/ssr`.

2.  **Instal Supervisor**:
    *   Buka **App Store** di aaPanel.
    *   Cari dan instal **Supervisor Manager**.

3.  **Konfigurasi Supervisor**:
    *   Buka **Supervisor Manager** > **Add Daemon**.
    *   **Name**: `laravel-ssr`
    *   **Run User**: `www`
    *   **Run Dir**: `/www/wwwroot/nama-domain-anda`
    *   **Start Command**: `php artisan inertia:start-ssr`
    *   **Processes**: 1
    *   Klik **Confirm**.

4.  **Cek Status**:
    Pastikan statusnya **Running**. Jika ada perubahan kode frontend, Anda harus menjalankan `npm run build:ssr` lagi dan merestart daemon di Supervisor.


### Optimasi Performa
Jalankan perintah ini setelah setiap update kode:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting Umum
*   **500 Internal Server Error**: Cek log di `storage/logs/laravel.log`.
*   **Vite Manifest Not Found**: Pastikan `npm run build` sudah dijalankan dan menghasilkan folder `public/build`.
*   **Permission Denied**: Pastikan user `www` memiliki akses ke folder storage.
