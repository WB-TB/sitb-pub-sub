# CKG Service untuk SITB Server
Integrasi data ASIK CKG dari aplikasi SITB melalui Google Pub/Sub dan Restful API.

## Ringkasan

Ini adalah implementasi PHP untuk mengintegrasikan data SITB dengan Google Cloud Pub/Sub. Implementasi ini mencakup:

- **Producer**: Mengirim pesan ke CKG Server bersisi status perawatan TB pasien melalui **Google Pub/Sub** atau **Restful API** sesuai pilihan konfigurasi
- **Consumer**: Secara aktif menerima pesan dari **Google Pub/Sub** berisi data Skrining CKG untuk pasien **Terduga TB**
- **Client**: Fungsionalitas dasar untuk memformat, mengirim dan menerima data

## Fitur

- **Pemrosesan Batch**: Penerbitan dan pemrosesan batch yang efisien
- **Retry Logic**: Mekanisme retry yang dapat dikonfigurasi dengan exponential backoff
- **Logging Komprehensif**: Logging detail untuk debugging dan monitoring
- **Layanan Systemd/Initd**: Aplikasi berjalan pada linux server sebagai `background process` yang dikelola menggunakan Systemd atau Initd sesuai `system manager` yang digunakan oleh server. Script instalasi secara otomatis mendeteksi ini untuk menjalankan script yang benar.

## Instalasi CKG Service di SITB Server

### 1. Install menggunakan script

Gunakan skrip berikut untuk melakukan instalasi modul secara cepat:

```bash
curl -sS https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/main/scripts/install.sh | sudo sh
```

Skrip ini akan:
- Mengunduh dan mengekstrak repositori ke `/opt/sitb-ckg`
- Menginstal dependensi Composer
- Membuat pengguna dan grup `sitb-ckg` untuk `background process`
- Menyiapkan layanan systemd atau initd untuk consumer, serta cronjob producer (baik mode `pubsub` atau `api`)
- Mengonfigurasi permission sesuai kebutuhan `background process`

**Opsi CLI Tambahan:**

Untuk instalasi tanpa git (menggunakan download zip):
```bash
curl -sS https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/main/scripts/install.sh | sudo sh -s -- --no-git=yes
```

Untuk instalasi tanpa composer:
```bash
curl -sS https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/main/scripts/install.sh | sudo sh -s -- --no-composer=yes
```

Untuk kombinasi opsi:
```bash
curl -sS https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/main/scripts/install.sh | sudo sh -s -- --no-git=yes --no-composer=no
```

Untuk update instalasi yang sudah ada:
```bash
sudo /opt/sitb-ckg/scripts/install.sh update
```

Atau dengan opsi tambahan:
```bash
sudo /opt/sitb-ckg/scripts/install.sh update --no-git=yes
```

### 2. Siapkan Kredensial Google Cloud
- Minta file `credentials.json` ke administrator SATUSEHAT-PKG
- Unduh file JSON kredensial
- Letakkan file di: `/opt/sitb-ckg/credentials.json`

### 3. Mengupdate Table Skrining SITB
Menambahkan field `ckg_id` di tabel `ta_skrining` SITB

```sql
-- Add ckg_id column to ta_skrining table
ALTER TABLE ta_skrining ADD COLUMN ckg_id varchar(16) DEFAULT NULL;
```

> Silakan gunakan file **[sql/ta_skrining_update.sql](./sql/ta_skrining_update.sql)** untuk dieksekusi di Database Server

### 4. Update file Konfigurasi

**4.1 Copy file konfigurasi**

Salin file konfigurasi contoh ke file konfigurasi aktif:

```bash
cp /opt/sitb-ckg/config.example.php /opt/sitb-ckg/config.php
```

**4.2 Edit parameter konfigurasi**

Untuk dokumentasi lengkap mengenai konfigurasi, silakan lihat file [`docs/CONFIG.md`](./docs/CONFIG.md).

Dokumentasi tersebut mencakup:
- Penjelasan detail setiap bagian konfigurasi
- Panduan perubahan konfigurasi untuk setiap environment
- Contoh konfigurasi untuk development, staging, dan production
- Troubleshooting masalah konfigurasi
- Best practices untuk keamanan dan manajemen environment

**Parameter yang wajib di-update:**
- [`google_cloud.project_id`](./config.php:7) - ID proyek Google Cloud
- [`pubsub.default_topic`](./config.php:12) - Topic Google Cloud PubSub
- [`pubsub.default_subscription`](./config.php:13) - Subscription Google Cloud PubSub
- [`api.base_url`](./config.php:46) - URL API eksternal
- [`api.api_key`](./config.php:48) - API key untuk autentikasi
- [`database.host`](./config.php:53) - Hostname database
- [`database.port`](./config.php:54) - Port database
- [`database.username`](./config.php:55) - Username database
- [`database.password`](./config.php:56) - Password database
- [`database.database_name`](./config.php:57) - Nama database
- [`ckg.table_skrining`](./config.php:66) - Nama tabel skrining
- [`ckg.table_laporan_so`](./config.php:67) - Nama tabel laporan SO
- [`ckg.table_laporan_ro`](./config.php:68) - Nama tabel laporan RO

Edit file sesuai dengan environment yang digunakan: `/opt/sitb-ckg/config.php`

### 5. Jalankan Service (Pub/Sub Consumer)
Setelah anda melakukan langkah 1-4 kemudian restart linux service untuk mulai menerima data Pub/Sub Skrining CKG dengan masuk ke terminal server SITB dan jalankan perintah berikut:
```bash
sudo service ckg-consumer restart
```

## Penggunaan CLI

### CLI Producer

Mode Pub/Sub
```bash
php -f /opt/sitb-ckg/producer.php - --mode=pubsub
```

Mode API
```bash
php -f /opt/sitb-ckg/producer.php - --mode=api
```

### CLI Consumer

```bash
php -f /opt/sitb-ckg/consumer.php
```

## Penanganan Kesalahan

Implementasi ini mencakup penanganan kesalahan yang komprehensif dengan:

- Logika retry dengan exponential backoff
- Logging detail dari semua operasi
- Penanganan error Google Cloud API yang graceful

## Monitoring

Semua operasi dicatat ke systemd journal dengan tingkat log yang dapat dikonfigurasi. Log mencakup:

- Status publish/acknowledge pesan
- Statistik pemrosesan dan tingkat keberhasilan
- Detail error dan percobaan retry
- Metrik performa

### Lokasi Log

- **Layanan Consumer**: `/var/log/sitb-ckg/consumer.log`
- **Layanan Producer (API dan Pub/Sub)**: `/var/log/sitb-ckg/producer.log`

## Pengujian

Jalankan rangkaian tes:

```bash
composer test
```


## Konfigurasi Tambahan
### 1. Mengirim Data ke CKG (melalui API atau Pub/Sub sesuai konfigurasi)
Untuk pengiriman data dari SITB ke server CKG menggunakan **Laporan TBC 03 (SO dan RO)** dan tidak membutuhkan perubahan skema database.

Pengiriman dapat dilakukan melalui channel `Google Pub/Sub` atau `API`. Pilihan default jika tidak ada perubahan config adalah **`API`**.

> Data akan dijalankan menggunakan CronJob (Scheduller) setiap hari pada pukul 02.00 (dini hari) untuk mengirim data pada hari sebelumnya secara bertahap (dalam batch berukuran 100 data per batch). Ukuran batch pengiriman bisa diubah pada:
> `/opt/sitb-ckg/config.php`
>
> pada parameter `$config['producer']['batch_size']`
```php
return [
    // ...
    'producer' => [
        // ...
        'batch_size' => 100, // <-- UBAH DISINI
        // ...
    ],
    // ...
]
```


## Lisensi

Proyek ini dilisensikan di bawah Lisensi GPL-3.0.
