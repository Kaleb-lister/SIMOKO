# SIMOKO — Smart Piggy Bank Monitoring System (Website)

Website monitoring untuk celengan digital cerdas **SIMOKO** berbasis ESP32 (IoT Load Cell HX711 & Push Button).

---

## 🚀 Fitur Utama

- **Dashboard Real-Time**:
  - Saldo aktif buku besar (*ledger*) & target tabungan.
  - *Progress bar* capaian target persentase otomatis.
  - Kartu estimasi hari tercapai (*ETA Calculator*) berbasis rata-rata bergerak 7 hari terakhir.
  - Grafik kombinasi 14 hari terakhir (Chart.js: setoran harian vs akumulasi saldo).
  - Status koneksi alat ESP32 (online/offline) & deteksi selisih saldo alat vs server.
  - Notifikasi transaksi "Perlu Ditinjau" jika ada koin tak dikenal.
- **Riwayat & Buku Besar Transaksi**:
  - Pengelompokan riwayat per tanggal lengkap dengan subtotal harian.
  - Filter rentang waktu: 7 hari, 30 hari, 90 hari.
  - Fitur **Koreksi Nominal** (menjaga jejak nominal awal).
  - Fitur **Batalkan Transaksi** dengan pencatatan alasan pembatalan.
  - Fitur **Tambah Transaksi Manual** dari web.
- **Keamanan & Autentikasi**:
  - 1 Pengguna = 1 Alat (*Single Tenant per Device*).
  - Login menggunakan `username` (bukan email).
  - Kewajiban ganti username & password bawaan pada saat login pertama kali (`must_change_credentials`).
  - Proteksi API IoT dengan `Authorization: Bearer <DEVICE_TOKEN>` (token disimpan sebagai hash SHA-256).
  - Rate limiting API (60 req/menit per alat).

---

## 🛠️ Persyaratan Sistem

- PHP ≥ 8.2 (dengan ekstensi `pdo_sqlite`, `pdo_pgsql`, `pdo_mysql`, `curl`, `mbstring`)
- Composer ≥ 2.x
- Node.js ≥ 18.x & NPM
- Database: SQLite (pengembangan lokal), PostgreSQL / MySQL (produksi / Aiven)

---

## 💻 Panduan Instalasi Lokal

1. **Buka folder proyek**:
   ```bash
   cd simoko
   ```

2. **Salin file environment & generate APP_KEY**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Jalankan Migrasi & Demo Seeder**:
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Seeder membuat 1 user demo (`username: demo`, `password: demo1234`) dengan data transaksi 14 hari terakhir.*

4. **Kompilasi Asset Frontend (Tailwind CSS & Chart.js)**:
   ```bash
   npm install
   npm run build
   ```

5. **Jalankan Server Lokal**:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   Buka di browser: `http://localhost:8000`

---

## 📟 Perintah Artisan CLI untuk Alat ESP32

### 1. Registrasi Alat Baru (*Provisioning*)
```bash
php artisan simoko:provision SIMOKO-0001
```
Perintah ini mencetak kredensial sekali saja:
- `Web Username`: `simoko-0001`
- `Web Password`: password acak 10 karakter (wajib diubah saat login pertama)
- `DEVICE_TOKEN`: token acak 40 karakter untuk dimasukkan ke `secrets.h` firmware ESP32

### 2. Rotasi Token API Alat
```bash
php artisan simoko:rotate-token SIMOKO-0001
```

### 3. Reset Password Pengguna Alat
```bash
php artisan simoko:reset-password SIMOKO-0001
```

---

## 🧪 Pengujian Unit & Fitur

Seluruh 12 skenario pengujian API kontrak dan logika estimasi (ETA) telah dibuat secara otomatis:
```bash
php vendor/phpunit/phpunit/phpunit
```
Hasil: **27 tests, 86 assertions passed (100% OK)**.

Koleksi Postman siap pakai ada di folder:
`postman/SIMOKO.postman_collection.json`

---

## ☁️ Menghubungkan ke Database Aiven (PostgreSQL)

1. Buka [Aiven Console](https://console.aiven.io/) dan buat service **PostgreSQL** baru (free tier / trial).
2. Di halaman **Overview** service Anda, cari bagian **Connection details**:
   - **Host**
   - **Port**
   - **User** (biasanya `avnadmin`)
   - **Password**
   - **Database Name** (biasanya `defaultdb`)
   - **SSL Mode**: `require`
3. Edit file `.env` di proyek ini:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=pg-xxxxxx.aivencloud.com
   DB_PORT=xxxxx
   DB_DATABASE=defaultdb
   DB_USERNAME=avnadmin
   DB_PASSWORD=your_aiven_password
   DB_SSLMODE=require
   ```
4. Jalankan migrasi ke database Aiven:
   ```bash
   php artisan migrate --force
   ```

---

## 🌐 Panduan Deploy ke Vercel

SIMOKO telah disiapkan untuk deploy ke Vercel menggunakan PHP Serverless Runtime (`vercel-php`):

1. File konfigurasi `vercel.json`:
   ```json
   {
     "version": 2,
     "framework": null,
     "functions": {
       "api/index.php": { "runtime": "vercel-php@0.7.4" }
     },
     "routes": [
       { "src": "/build/(.*)", "dest": "/public/build/$1" },
       { "src": "/(.*)", "dest": "/api/index.php" }
     ],
     "env": {
       "APP_ENV": "production",
       "APP_DEBUG": "false",
       "APP_CONFIG_CACHE": "/tmp/config.php",
       "APP_EVENTS_CACHE": "/tmp/events.php",
       "APP_PACKAGES_CACHE": "/tmp/packages.php",
       "APP_ROUTES_CACHE": "/tmp/routes.php",
       "APP_SERVICES_CACHE": "/tmp/services.php",
       "VIEW_COMPILED_PATH": "/tmp",
       "CACHE_STORE": "array",
       "SESSION_DRIVER": "cookie"
     }
   }
   ```
2. Setup Entry Point Vercel `api/index.php`:
   Mengarahkan request ke `public/index.php`.
3. Pasang Environment Variables di Vercel Dashboard:
   - `APP_KEY`
   - `APP_URL` (URL vercel Anda)
   - `DB_CONNECTION=pgsql`
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require`
