# CKG Service untuk SITB Server
Integrasi data ASIK CKG dari aplikasi SITB melalui Google Pub/Sub dan Restful API.

## Ringkasan

Ini adalah implementasi PHP untuk mengintegrasikan data SIBT dengan Google Cloud Pub/Sub. Implementasi ini mencakup:

- **Producer**: Mengirim pesan ke CKG Server bersisi status perawatan TB pasien melalui topik Google Pub/Sub atau API sesuai pilihan konfigurasi
- **Consumer**: Secara aktif menerima pesan dari Google Pub/Sub berisi data Skrining CKG untuk pasien **Terduga TB**
- **Client**: Kelas dasar dengan fungsionalitas umum untuk producer dan consumer

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
- Membuat pengguna dan grup `sitb-ckg`
- Menyiapkan layanan systemd atau initd untuk consumer, serta cronjob producer (baik mode `pubsub` atau `api`)
- Mengonfigurasi izin file dan kepemilikan yang tepat

### 2. Siapkan Kredensial Google Cloud
- Minta file `credentials.json` ke administrator SATUSEHAT-PKG
- Unduh file JSON kredensial
- Letakkan file di `/opt/sitb-ckg/credentials.json`

### 3. Siapkan database untuk penyimpanan sementara pesan Pub/Sub
#### 3.1 Menerima Pesan masuk dari Pub/Sub (sebagai Consumer)
Dibutuhkan 2 tabel sementara untuk memastikan pesan yang masuk tidak diproses berkali-kali. 

> Pesan di dalam tabel akan di **hapus setiap hari** (menggunakan CronJob/Scheduller bawaan dari service ini) untuk mencegah penumpukan yang tidak diperlukan

**Buat Tabel `ckg_pubsub_incoming`**
```sql
CREATE TABLE `ckg_pubsub_incoming` (
  `id` varchar(100) NOT NULL COMMENT 'Message ID from Pub/Sub',
  `data` TEXT NOT NULL COMMENT 'Message data in JSON format',
  `received_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Message received timestamp',
  `processed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Message received timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_received_at` (`received_at`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB COMMENT='Pub/Sub Incoming Messages Table';
```

**Buat Tabel `ckg_pubsub_outgoing`**
```sql
CREATE TABLE `ckg_pubsub_outgoing` (
  `terduga_id` varchar(100) NOT NULL COMMENT 'Message ID from Pub/Sub',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record create timestamp',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB COMMENT='API Outgoing Messages Table';
```

> Anda juga bisa menggunakan file **[sql/pubsub_temp.sql](./sql/pubsub_temp.sql)** untuk dieksekusi di Database Server

#### 3.2 Mengupdae Table Skrining SITB
Menambahkan field `ckg_id` di tabel `ta_skrining` SITB

```sql
-- Add ckg_id column to ta_skrining table
ALTER TABLE ta_skrining ADD COLUMN ckg_id varchar(16) DEFAULT NULL;
```

> Silakan gunakan file **[sql/ta_skrining_update.sql](./sql/ta_skrining_update.sql)** untuk dieksekusi di Database Server

### 4. Update file Konfigurasi

Edit file `/opt/sitb-ckg/config.php`:

```php
return [
    'environment' => getenv('APP_ENV') ?: 'development',
    'producer_mode' => 'api', // 'pubsub' or 'api'
    'google_cloud' => [
        'project_id' => 'your-project-id',
        'credentials_path' => __DIR__ . '/credentials.json',
        'debug' => getenv('GOOGLE_SDK_PHP_LOGGING') === 'true' ? true : false,
    ],
    'pubsub' => [
        'default_topic' => 'test-topic',
        'default_subscription' => 'test-subscription',
        'topics' => [
            'test-topic' => [
                'subscription' => 'test-subscription',
                'message_ordering' => false
            ]
        ]
    ],
    'consumer' => [
        'max_messages_per_pull' => 10,
        'sleep_time_between_pulls' => 5,
        'acknowledge_timeout' => 60,
        'retry_count' => 3,
        'retry_delay' => 1,
        'flow_control' => [
            'enabled' => false,
            'max_outstanding_messages' => 1000,
            'max_outstanding_bytes' => 1000000 // 1MB
        ]
    ],
    'producer' => [
        'enable_message_ordering' => false,
        'batch_size' => 100,
        'message_attributes' => [
            'source' => 'php-pubsub-client',
            'version' => '1.0.0',
            'environment' => 'development'
        ],
        'compression' => [
            'enabled' => false,
            'algorithm' => 'gzip'
        ]
    ],
    'api' => [
        'base_url' => 'https://api-dev.dto.kemkes.go.id/fhir-sirs',
        'timeout' => 60, // seconds
        'api_key' => getenv('SITB_API_KEY') ?: 'your_api_key_here',
        'api_header' => 'X-API-Key:',
        'batch_size' => 100
    ],
    'logging' => [
        'level' => 'DEBUG', // DEBUG, INFO, WARNING, ERROR
        'consumer' => '/var/log/sitb-ckg/consumer.log',
        'producer-pubsub' => '/var/log/sitb-ckg/producer-pubsub.log',
        'producer-api' => '/var/log/sitb-ckg/producer-api.log',
    ],
    'ckg' => [
        'table_skrining' => 'ta_skrining',
        'table_laporan_so' => 'lap_tbc_03so',
        'table_laporan_ro' => 'lap_tbc_03ro',
        'table_incoming' => 'tmp_ckg_incoming',
        'table_outgoing' => 'tmp_ckg_outgoing',
        'table_processed' => 'tmp_ckg_processed',
        'marker_field' => 'transactionSource',
        'marker_produce' => 'STATUS-PASIEN-TB',
		'marker_consume' => 'SKRINING-CKG-TB',
    ]
];
```
Pastikan parameter berikut di-set dengan benar:
- google_cloud.project_id
- pubsub.default_subscription
- pubsub.default_topic
- api.base_url
- api.api_key
- Database connection settings

### 5. Restart Linux Service (Pub/Sub Consumer)
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

> Data akan dijalankan menggunakan CronJob (Scheduller) setiap hari pada pukul 02.00 (dini hari) untuk mengirim data pada hari sebelumnya secara bertahap (dalam batch berukuran 100 data per batch). Ukuran batch pengiriman bisa diubah pada `/opt/sitb-ckg/config.php` pada parameter `$config['producer']['batch_size']`
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

Proyek ini dilisensikan di bawah Lisensi MIT.
