# Dokumentasi Konfigurasi (config.php)

Dokumentasi ini menjelaskan struktur, penggunaan, dan cara memodifikasi file [`config.php`](../config.php) pada proyek SITB PubSub Client.

## 📋 Daftar Isi

- [Ringkasan](#ringkasan)
- [Struktur Konfigurasi](#struktur-konfigurasi)
- [Bagian Konfigurasi](#bagian-konfigurasi)
- [Panduan Perubahan Konfigurasi](#panduan-perubahan-konfigurasi)
- [Contoh Konfigurasi untuk Lingkungan Berbeda](#contoh-konfigurasi-untuk-lingkungan-berbeda)
- [Troubleshooting](#troubleshooting)

---

## Ringkasan

File [`config.php`](../config.php:1) adalah file konfigurasi utama yang digunakan oleh seluruh aplikasi untuk mengatur perilaku komponen-komponen berikut:

- **Google Cloud PubSub**: Konfigurasi koneksi ke Google Cloud
- **Consumer**: Pengaturan untuk konsumen pesan
- **Producer**: Pengaturan untuk produsen pesan
- **API**: Konfigurasi koneksi ke API eksternal
- **Database**: Koneksi database MySQL
- **Logging**: Pengaturan log aplikasi
- **CKG**: Konfigurasi spesifik untuk integrasi CKG

File ini dimuat oleh [`lib/Boot.php`](../lib/Boot.php:33) saat inisialisasi aplikasi dan diakses melalui metode `Boot::getConfig()`.

---

## Struktur Konfigurasi

Berikut adalah struktur lengkap dari file [`config.php`](../config.php:1):

```php
return [
    'environment' => 'development',
    'producer_mode' => 'api',
    'google_cloud' => [...],
    'pubsub' => [...],
    'consumer' => [...],
    'producer' => [...],
    'api' => [...],
    'database' => [...],
    'logging' => [...],
    'ckg' => [...]
];
```

---

## Bagian Konfigurasi

### 1. Environment & Producer Mode

```php
'environment' => 'development',
'producer_mode' => 'api', // 'pubsub' or 'api'
```

**Penggunaan:**
- [`environment`](../config.php:4): Menentukan lingkungan aplikasi (development, staging, production) **(HARUS DIUPDATE untuk setiap environment)**
- [`producer_mode`](../config.php:5): Mode operasi producer
  - `pubsub`: Mengirim pesan ke Google Cloud PubSub
  - `api`: Mengirim data langsung ke API eksternal

**Pengaruh pada Kode:**
- Digunakan di [`producer.php`](../producer.php:10) untuk menentukan mode eksekusi
- Menentukan logger yang digunakan (lihat [`lib/Boot.php`](../lib/Boot.php:38-44))

---

### 2. Google Cloud Configuration

```php
'google_cloud' => [
    'project_id' => 'ckg-tb-staging', // <-- BUTUH DIUPDATE
    'credentials_path' => __DIR__ . '/credentials.json',
    'debug' => false,
],
```

**Penggunaan:**
- [`project_id`](../config.php:7): ID proyek Google Cloud **(HARUS DIUPDATE untuk setiap environment)**
- [`credentials_path`](../config.php:8): Path ke file JSON credentials service account
- [`debug`](../config.php:9): Mengaktifkan mode debug Google SDK

**Pengaruh pada Kode:**
- Digunakan di [`lib/PubSub/Client.php`](../lib/PubSub/Client.php) untuk inisialisasi koneksi Google Cloud PubSub

**Cara Update:**
1. Buat service account di Google Cloud Console
2. Download file JSON credentials
3. Simpan di lokasi yang aman (di luar web root) dan update path di `credentials_path`
4. Update `project_id` dengan ID proyek yang sesuai
5. Set `debug` ke `true` untuk development, `false` untuk production

---

### 3. PubSub Configuration

```php
'pubsub' => [
    'default_topic' => 'projects/ckg-tb-staging/topics/CKG-SITB',            // <-- BUTUH DIUPDATE
    'default_subscription' => 'projects/ckg-tb-staging/subscriptions/Dev',   // <-- BUTUH DIUPDATE
    'topics' => [
        'projects/ckg-tb-staging/topics/CKG-SITB' => [
            'subscription' => 'projects/ckg-tb-staging/subscriptions/Dev',
            'message_ordering' => false
        ],
    ]
],
```

**Penggunaan:**
- [`default_topic`](../config.php:12): Topic default untuk PubSub **(HARUS DIUPDATE)**
- [`default_subscription`](../config.php:13): Subscription default untuk consumer **(HARUS DIUPDATE)**
- [`topics`](../config.php:14): Array konfigurasi untuk multiple topics

**Pengaruh pada Kode:**
- Digunakan di [`lib/PubSub/Client.php`](../lib/PubSub/Client.php) untuk menentukan topic dan subscription
- [`consumer.php`](../consumer.php:9) menggunakan subscription untuk mendengarkan pesan
- [`producer.php`](../producer.php:12) menggunakan topic untuk mengirim pesan

**Format Topic dan Subscription:**
```
projects/{PROJECT_ID}/topics/{TOPIC_NAME}
projects/{PROJECT_ID}/subscriptions/{SUBSCRIPTION_NAME}
```

**Cara Update:**
1. Buat topic di Google Cloud PubSub
2. Buat subscription untuk topic tersebut
3. Update `default_topic` dan `default_subscription` dengan path lengkap
4. Tambahkan konfigurasi tambahan di array `topics` jika menggunakan multiple topics

---

### 4. Consumer Configuration

```php
'consumer' => [
    'max_messages_per_pull' => 10,
    'sleep_time_between_pulls' => 5,
    'acknowledge_timeout' => 60, // seconds
    'retry_count' => 3,
    'retry_delay' => 1, // seconds
    'flow_control' => [
        'enabled' => false,
        'max_outstanding_messages' => 1000,
        'max_outstanding_bytes' => 1000000 // 1MB
    ]
],
```

**Penggunaan:**
- [`max_messages_per_pull`](../config.php:22): Jumlah maksimum pesan per pull (1-1000)
- [`sleep_time_between_pulls`](../config.php:23): Waktu tunggu antara pull dalam detik
- [`acknowledge_timeout`](../config.php:24): Timeout untuk acknowledge pesan dalam detik
- [`retry_count`](../config.php:25): Jumlah percobaan ulang jika gagal
- [`retry_delay`](../config.php:26): Delay antara retry dalam detik
- [`flow_control`](../config.php:27): Kontrol aliran pesan
  - [`enabled`](../config.php:28): Mengaktifkan flow control
  - [`max_outstanding_messages`](../config.php:29): Maksimum pesan yang sedang diproses
  - [`max_outstanding_bytes`](../config.php:30): Maksimum byte yang sedang diproses

**Pengaruh pada Kode:**
- Digunakan di [`lib/PubSub/Consumer.php`](../lib/PubSub/Consumer.php:14-28) untuk inisialisasi consumer
- Mengontrol perilaku [`consumer.php`](../consumer.php:19) saat mendengarkan pesan

**Rekomendasi Tuning:**
- **High Throughput**: `max_messages_per_pull: 100`, `sleep_time_between_pulls: 1`
- **Low Latency**: `max_messages_per_pull: 10`, `sleep_time_between_pulls: 2`
- **Resource Constrained**: `max_messages_per_pull: 5`, `sleep_time_between_pulls: 10`, `flow_control.enabled: true`

---

### 5. Producer Configuration

```php
'producer' => [
    'enable_message_ordering' => false,
    'batch_size' => 100,
    'message_attributes' => [
        'source' => 'sitb-pubsub-client',
        'version' => '1.0.0'
    ],
    'compression' => [
        'enabled' => false,
        'algorithm' => 'gzip'
    ]
],
```

**Penggunaan:**
- [`enable_message_ordering`](../config.php:34): Mengaktifkan ordering pesan (membutuhkan ordering key)
- [`batch_size`](../config.php:35): Jumlah pesan per batch
- [`message_attributes`](../config.php:36): Atribut default untuk setiap pesan
- [`compression`](../config.php:40): Konfigurasi kompresi
  - [`enabled`](../config.php:41): Mengaktifkan kompresi pesan
  - [`algorithm`](../config.php:42): Algoritma kompresi (gzip, dll)

**Pengaruh pada Kode:**
- Digunakan di [`lib/PubSub/Producer.php`](../lib/PubSub/Producer.php:12-22) untuk inisialisasi producer
- Mengontrol cara [`producer.php`](../producer.php:12) mengirim pesan

**Rekomendasi Tuning:**
- **High Volume**: `batch_size: 500`, `compression.enabled: true`
- **Message Ordering Required**: `enable_message_ordering: true`, sertakan `ordering_key` di attributes
- **Small Messages**: `compression.enabled: false` (overhead kompresi lebih besar)

---

### 6. API Configuration

```php
'api' => [
    'base_url' => 'https://api-dev.dto.kemkes.go.id/fhir-sirs', // <-- BUTUH DIUPDATE
    'timeout' => 60, // seconds
    'api_key' => 'your_api_key_here', // <-- BUTUH DIUPDATE
    'api_header' => 'X-API-Key:',
    'batch_size' => 100
],
```

**Penggunaan:**
- [`base_url`](../config.php:46): URL dasar API eksternal **(HARUS DIUPDATE untuk setiap environment)**
- [`timeout`](../config.php:47): Timeout request dalam detik
- [`api_key`](../config.php:48): API key untuk autentikasi **(HARUS DIUPDATE)**
- [`api_header`](../config.php:49): Nama header untuk API key
- [`batch_size`](../config.php:50): Jumlah data per batch

**Pengaruh pada Kode:**
- Digunakan di [`lib/Api/Client.php`](../lib/Api/Client.php:15-22) untuk inisialisasi API client
- Digunakan ketika `producer_mode` di-set ke `api`

**Cara Update:**
1. Dapatkan API key dari penyedia API
2. Update `api_key` dengan nilai yang benar
3. Update `base_url` sesuai environment (dev, staging, production)
4. Sesuaikan `timeout` berdasarkan performa API

---

### 7. Database Configuration

```php
'database' => [
    'host' => 'mysql_service',                                  // <-- BUTUH DIUPDATE
    'port' => 3306,                                             // <-- BUTUH DIUPDATE
    'username' => 'xtb',                                        // <-- BUTUH DIUPDATE
    'password' => 'xtb',                                        // <-- BUTUH DIUPDATE
    'database_name' => 'xtb'                                    // <-- BUTUH DIUPDATE
],
```

**Penggunaan:**
- [`host`](../config.php:53): Hostname atau IP database server **(HARUS DIUPDATE)**
- [`port`](../config.php:54): Port database (default MySQL: 3306)
- [`username`](../config.php:55): Username database **(HARUS DIUPDATE)**
- [`password`](../config.php:56): Password database **(HARUS DIUPDATE)**
- [`database_name`](../config.php:57): Nama database **(HARUS DIUPDATE)**

**Pengaruh pada Kode:**
- Digunakan di [`lib/Database/MySQL.php`](../lib/Database/MySQL.php:19) untuk koneksi database
- Digunakan di [`lib/Boot.php`](../lib/Boot.php:117) untuk inisialisasi database
- Digunakan di semua class yang membutuhkan akses database

**Cara Update:**
1. Buat database dan user di MySQL server
2. Berikan hak akses yang sesuai
3. Update konfigurasi sesuai kredensial
4. **Security Note**: Pastikan file config.php memiliki permission yang ketat (600 atau 640) untuk mencegah akses tidak sah

---

### 8. Logging Configuration

```php
'logging' => [
    'level' => 'DEBUG', // DEBUG, INFO, WARNING, ERROR
    'consumer' => '/var/log/sitb-ckg/consumer.log',
    'producer-pubsub' => '/var/log/sitb-ckg/producer-pubsub.log',
    'producer-api' => '/var/log/sitb-ckg/producer-api.log',
],
```

**Penggunaan:**
- [`level`](../config.php:60): Level logging (DEBUG, INFO, WARNING, ERROR)
- [`consumer`](../config.php:61): Path log file untuk consumer
- [`producer-pubsub`](../config.php:62): Path log file untuk producer PubSub
- [`producer-api`](../config.php:63): Path log file untuk producer API

**Pengaruh pada Kode:**
- Digunakan di [`lib/Boot.php`](../lib/Boot.php:49) untuk inisialisasi logger
- Menentukan level detail log yang dicatat
- Menentukan lokasi penyimpanan log

**Level Logging:**
- `DEBUG`: Informasi paling detail untuk debugging
- `INFO`: Informasi umum tentang operasi
- `WARNING`: Peringatan yang tidak menghentikan operasi
- `ERROR`: Error yang membutuhkan perhatian

**Setup Log Directory:**
```bash
# Buat directory log
sudo mkdir -p /var/log/sitb-ckg

# Berikan permission
sudo chown www-data:www-data /var/log/sitb-ckg
sudo chmod 755 /var/log/sitb-ckg
```

---

### 9. CKG Configuration

```php
'ckg' => [
    'table_skrining' => 'ta_skrining',                         // <-- BUTUH DIUPDATE
    'table_laporan_so' => 'lap_tbc_03so',                      // <-- BUTUH DIUPDATE nama tabel laporan SO
    'table_laporan_ro' => 'lap_tbc_03ro',                      // <-- BUTUH DIUPDATE nama tabel laporan RO
    'table_incoming' => 'ckg_pubsub_incoming',
    'table_outgoing' => 'ckg_pubsub_outgoing',
    'marker_field' => 'transactionSource',
    'marker_produce' => 'STATUS-PASIEN-TB',
    'marker_consume' => 'SKRINING-CKG-TB',
]
```

**Penggunaan:**
- [`table_skrining`](../config.php:66): Tabel skrining **(HARUS DIUPDATE sesuai database)**
- [`table_laporan_so`](../config.php:67): Tabel laporan SO **(HARUS DIUPDATE sesuai database)**
- [`table_laporan_ro`](../config.php:68): Tabel laporan RO **(HARUS DIUPDATE sesuai database)**
- [`table_incoming`](../config.php:69): Tabel untuk menyimpan pesan masuk dari PubSub
- [`table_outgoing`](../config.php:70): Tabel untuk menyimpan pesan keluar ke PubSub
- [`marker_field`](../config.php:71): Field marker untuk identifikasi pesan yang dianggap valid berasal dari PubSub (hanya mempedulikan pesan yang mengandung field dengan nama sesuai dengan `marker_field`)
- [`marker_produce`](../config.php:72): Nilai penanda untuk producer ketika mengirim data ke PubSub akan menyertakan field dengan nama `marker_field` dan berisi nilai `marker_produce`. Ini mencegah agar pesan yang dikirim tidak diproses oleh Consumer sendiri.
- [`marker_consume`](../config.php:73): Nilai pendanda untuk consumer ketika menerjemahkan pesan yang data dari PubSub dengan melihat apakah ada field dengan nama `marker_field` dan berisi nilai sesuai `marker_consume`. Ini mencegah agar pesan yang diproses dipastikan bukan berasal dari diri sendiri.

**Pengaruh pada Kode:**
- Digunakan di [`lib/CKG/Receiver.php`](../lib/CKG/Receiver.php) untuk proses konsumsi pesan
- Digunakan di [`lib/CKG/Updater.php`](../lib/CKG/Updater.php) untuk proses produksi pesan
- Mengidentifikasi pesan pubsub yang dikirim dan diterjemahkan berbeda.

> **CATATAN MARKER FIELD**: Tidak boleh mengubah nilai `marker_field`, `marker_produce` dan `marker_consume` tanpa berkoordinasi dengan pengembang sistem yang berinteraksi dengan sistem ini. Harus dipastikan ketiga variabel ini sesuai dan berpasangan dengan sistem CKG yang terhubung.

---

## Panduan Perubahan Konfigurasi

### Step-by-Step Guide

#### 1. Buat File Konfigurasi Terpisah untuk Setiap Environment

Untuk memudahkan manajemen konfigurasi, disarankan membuat file konfigurasi terpisah untuk setiap environment:

**File: `config.development.php`**
```php
<?php

return [
    'environment' => 'development',
    'producer_mode' => 'api',
    'google_cloud' => [
        'project_id' => 'ckg-tb-dev',
        'credentials_path' => __DIR__ . '/credentials-dev.json',
        'debug' => true,
    ],
    'pubsub' => [
        'default_topic' => 'projects/ckg-tb-dev/topics/CKG-SITB',
        'default_subscription' => 'projects/ckg-tb-dev/subscriptions/Dev',
        'topics' => [
            'projects/ckg-tb-dev/topics/CKG-SITB' => [
                'subscription' => 'projects/ckg-tb-dev/subscriptions/Dev',
                'message_ordering' => false
            ],
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
            'max_outstanding_bytes' => 1000000
        ]
    ],
    'producer' => [
        'enable_message_ordering' => false,
        'batch_size' => 100,
        'message_attributes' => [
            'source' => 'sitb-pubsub-client',
            'version' => '1.0.0'
        ],
        'compression' => [
            'enabled' => false,
            'algorithm' => 'gzip'
        ]
    ],
    'api' => [
        'base_url' => 'https://api-dev.dto.kemkes.go.id/fhir-sirs',
        'timeout' => 60,
        'api_key' => 'dev-api-key',
        'api_header' => 'X-API-Key:',
        'batch_size' => 100
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'xtb',
        'password' => 'xtb',
        'database_name' => 'xtb_dev'
    ],
    'logging' => [
        'level' => 'DEBUG',
        'consumer' => '/var/log/sitb-ckg/consumer.log',
        'producer-pubsub' => '/var/log/sitb-ckg/producer-pubsub.log',
        'producer-api' => '/var/log/sitb-ckg/producer-api.log',
    ],
    'ckg' => [
        'table_skrining' => 'ta_skrining',
        'table_laporan_so' => 'lap_tbc_03so',
        'table_laporan_ro' => 'lap_tbc_03ro',
        'table_incoming' => 'ckg_pubsub_incoming',
        'table_outgoing' => 'ckg_pubsub_outgoing',
        'marker_field' => 'transactionSource',
        'marker_produce' => 'STATUS-PASIEN-TB',
        'marker_consume' => 'SKRINING-CKG-TB',
    ]
];
```

**File: `config.production.php`**
```php
<?php

return [
    'environment' => 'production',
    'producer_mode' => 'pubsub',
    'google_cloud' => [
        'project_id' => 'ckg-tb-prod',
        'credentials_path' => __DIR__ . '/credentials-prod.json',
        'debug' => false,
    ],
    'pubsub' => [
        'default_topic' => 'projects/ckg-tb-prod/topics/CKG-SITB',
        'default_subscription' => 'projects/ckg-tb-prod/subscriptions/Prod',
        'topics' => [
            'projects/ckg-tb-prod/topics/CKG-SITB' => [
                'subscription' => 'projects/ckg-tb-prod/subscriptions/Prod',
                'message_ordering' => true
            ],
        ]
    ],
    'consumer' => [
        'max_messages_per_pull' => 100,
        'sleep_time_between_pulls' => 2,
        'acknowledge_timeout' => 120,
        'retry_count' => 5,
        'retry_delay' => 2,
        'flow_control' => [
            'enabled' => true,
            'max_outstanding_messages' => 5000,
            'max_outstanding_bytes' => 10000000
        ]
    ],
    'producer' => [
        'enable_message_ordering' => true,
        'batch_size' => 500,
        'message_attributes' => [
            'source' => 'sitb-pubsub-client',
            'version' => '1.0.0'
        ],
        'compression' => [
            'enabled' => true,
            'algorithm' => 'gzip'
        ]
    ],
    'api' => [
        'base_url' => 'https://api.dto.kemkes.go.id/fhir-sirs',
        'timeout' => 120,
        'api_key' => 'prod-api-key',
        'api_header' => 'X-API-Key:',
        'batch_size' => 500
    ],
    'database' => [
        'host' => 'prod-db-server',
        'port' => 3306,
        'username' => 'xtb_prod',
        'password' => 'secure-password-here',
        'database_name' => 'xtb_prod'
    ],
    'logging' => [
        'level' => 'INFO',
        'consumer' => '/var/log/sitb-ckg/consumer.log',
        'producer-pubsub' => '/var/log/sitb-ckg/producer-pubsub.log',
        'producer-api' => '/var/log/sitb-ckg/producer-api.log',
    ],
    'ckg' => [
        'table_skrining' => 'ta_skrining',
        'table_laporan_so' => 'lap_tbc_03so',
        'table_laporan_ro' => 'lap_tbc_03ro',
        'table_incoming' => 'ckg_pubsub_incoming',
        'table_outgoing' => 'ckg_pubsub_outgoing',
        'marker_field' => 'transactionSource',
        'marker_produce' => 'STATUS-PASIEN-TB',
        'marker_consume' => 'SKRINING-CKG-TB',
    ]
];
```

#### 2. Update File `lib/Boot.php` untuk Mendukung Multiple Config

Modifikasi file [`lib/Boot.php`](../lib/Boot.php) untuk memuat file konfigurasi yang sesuai:

```php
// Di dalam method Boot::init()
// Load configuration
$env = getenv('APP_ENV') ?: 'development';
$configFile = APPDIR . '/config.' . $env . '.php';

if (file_exists($configFile)) {
    self::$config = require $configFile;
} else {
    // Fallback ke config.php default
    self::$config = require APPDIR . '/config.php';
}
```

#### 3. Set Environment Variable untuk Deployment

Untuk deployment, set environment variable `APP_ENV`:

**Linux/Mac (Bash):**
```bash
export APP_ENV=production
```

**Systemd Service:**
```ini
Environment="APP_ENV=production"
```

**Docker Compose:**
```yaml
environment:
  - APP_ENV=production
```

---

## Contoh Konfigurasi untuk Lingkungan Berbeda

Contoh lengkap konfigurasi untuk development dan production dapat dilihat di bagian [Panduan Perubahan Konfigurasi](#panduan-perubahan-konfigurasi) di atas.

### Perbedaan Utama Antara Environment

| Konfigurasi | Development | Production |
|-------------|-------------|------------|
| `environment` | `development` | `production` |
| `producer_mode` | `api` | `pubsub` |
| `google_cloud.project_id` | `ckg-tb-dev` | `ckg-tb-prod` |
| `google_cloud.debug` | `true` | `false` |
| `pubsub.message_ordering` | `false` | `true` |
| `consumer.max_messages_per_pull` | `10` | `100` |
| `consumer.sleep_time_between_pulls` | `5` | `2` |
| `producer.batch_size` | `100` | `500` |
| `producer.compression.enabled` | `false` | `true` |
| `api.base_url` | `https://api-dev.dto.kemkes.go.id/fhir-sirs` | `https://api.dto.kemkes.go.id/fhir-sirs` |
| `logging.level` | `DEBUG` | `INFO` |
| `consumer.flow_control.enabled` | `false` | `true` |

---

## Troubleshooting

### Masalah Umum dan Solusi

#### 1. Error: "Database connection failed"

**Gejala:**
```
Database connection failed: SQLSTATE[HY000] [2002] Connection refused
```

**Solusi:**
- Periksa konfigurasi database di [`config.php`](../config.php:52-58)
- Pastikan MySQL server berjalan: `systemctl status mysql`
- Verifikasi kredensial database
- Cek firewall dan network connectivity

```bash
# Test koneksi database
mysql -h <host> -P <port> -u <username> -p<password> <database_name>
```

#### 2. Error: "Google Cloud authentication failed"

**Gejala:**
```
Could not load the default credentials
```

**Solusi:**
- Pastikan file credentials.json ada dan valid
- Set environment variable `GOOGLE_APPLICATION_CREDENTIALS`
- Verifikasi permissions file credentials: `chmod 600 credentials.json`
- Cek project ID di konfigurasi

```bash
# Test credentials
gcloud auth activate-service-account --key-file=/path/to/credentials.json
gcloud config set project your-project-id
```

#### 3. Error: "Topic not found"

**Gejala:**
```
The requested resource was not found
```

**Solusi:**
- Pastikan topic dan subscription sudah dibuat di Google Cloud
- Verifikasi format path topic: `projects/{PROJECT_ID}/topics/{TOPIC_NAME}`
- Cek IAM permissions untuk service account

```bash
# List topics
gcloud pubsub topics list --project=your-project-id

# Create topic jika belum ada
gcloud pubsub topics create CKG-SITB --project=your-project-id
```

#### 4. Consumer tidak menerima pesan

**Gejala:**
- Consumer berjalan tapi tidak memproses pesan
- Log menunjukkan "No messages pulled"

**Solusi:**
- Periksa konfigurasi `max_messages_per_pull` dan `sleep_time_between_pulls`
- Verifikasi subscription sudah terhubung ke topic yang benar
- Cek apakah ada pesan di topic menggunakan Google Cloud Console
- Pastikan consumer memiliki permission untuk pull dari subscription

#### 5. API request timeout

**Gejala:**
```
cURL Error: Operation timed out
```

**Solusi:**
- Tingkatkan nilai `timeout` di konfigurasi API
- Periksa koneksi network ke API server
- Verifikasi API key valid
- Cek status API server

```bash
# Test koneksi API
curl -X GET https://api.dto.kemkes.go.id/fhir-sirs -H "X-API-Key: your-key"
```

#### 6. Log file tidak terbuat

**Gejala:**
- Error: "Permission denied" saat menulis log
- Log tidak muncul di file

**Solusi:**
- Pastikan directory log ada: `mkdir -p /var/log/sitb-ckg`
- Set permission yang benar: `chown www-data:www-data /var/log/sitb-ckg`
- Berikan write permission: `chmod 755 /var/log/sitb-ckg`

#### 7. Konfigurasi tidak sesuai dengan environment

**Gejala:**
- Aplikasi menggunakan konfigurasi development di production
- Error koneksi ke resource yang salah

**Solusi:**
- Pastikan file konfigurasi yang benar digunakan untuk setiap environment
- Set environment variable `APP_ENV` dengan nilai yang benar
- Verifikasi isi file konfigurasi sebelum deployment
- Gunakan file konfigurasi terpisah untuk setiap environment

```bash
# Cek environment variable
echo $APP_ENV

# Verifikasi file konfigurasi
cat config.php
```

---

## Best Practices

### 1. Security

- **Jangan hardcode sensitive data** seperti password dan API key di config.php untuk production
- Gunakan file konfigurasi terpisah untuk setiap environment (dev, staging, prod)
- Set permission file yang ketat: `chmod 600 credentials.json` dan `chmod 600 config.production.php`
- Jangan commit file config production dan credentials ke version control
- Gunakan file `.gitignore` untuk mengecualikan file sensitif:
  ```
  config.production.php
  config.staging.php
  credentials*.json
  ```

### 2. Environment Management

- Buat file config terpisah untuk setiap environment (config.development.php, config.staging.php, config.production.php)
- Gunakan environment variable `APP_ENV` untuk menentukan file konfigurasi yang akan digunakan
- Dokumentasikan semua perbedaan konfigurasi antar environment
- Validasi konfigurasi saat startup aplikasi
- Buat template konfigurasi untuk memudahkan setup environment baru

### 3. Monitoring & Logging

- Set level logging yang sesuai untuk setiap environment
- Gunakan `INFO` untuk production, `DEBUG` untuk development
- Monitor log file size dan implement log rotation
- Setup alerting untuk error di log

### 4. Performance Tuning

- Sesuaikan `batch_size`, `max_messages_per_pull` berdasarkan load
- Aktifkan kompresi untuk pesan besar
- Gunakan flow control untuk mencegah overload
- Monitor metrics seperti throughput dan latency

### 5. Backup & Recovery

- Backup konfigurasi secara berkala
- Simpan credentials di lokasi yang aman
- Dokumentasikan proses recovery
- Test restore process secara berkala

---

## Referensi

- [Google Cloud PubSub Documentation](https://cloud.google.com/pubsub/docs)
- [PHP Google Cloud Client Library](https://github.com/googleapis/google-cloud-php)
- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)

---

## Changelog Konfigurasi

### Versi 1.1.0
- **BREAKING CHANGE**: Menghapus penggunaan environment variables di config.php
- Semua nilai konfigurasi sekarang langsung ditulis di file config
- Mendukung multiple file konfigurasi per environment (config.{env}.php)
- Diperlukan update ke lib/Boot.php untuk mendukung multiple config files

### Versi 1.0.0
- Initial configuration structure
- Support untuk Google Cloud PubSub
- Support untuk API client
- Support untuk database MySQL

---

**Dokumentasi ini akan terus diperbarui seiring dengan perkembangan proyek.**

---

## Migration Guide: Dari Environment Variables ke Direct Configuration

Jika Anda sebelumnya menggunakan environment variables, berikut adalah langkah-langkah untuk migrasi:

### 1. Backup Konfigurasi Lama

```bash
cp config.php config.php.backup
```

### 2. Update config.php dengan Nilai Langsung

Ganti semua `getenv()` dengan nilai langsung:

**Sebelum:**
```php
'environment' => getenv('APP_ENV') ?: 'development',
'google_cloud' => [
    'project_id' => getenv('GOOGLE_CLOUD_PROJECT') ?: 'ckg-tb-staging',
    'credentials_path' => getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: __DIR__ . '/credentials.json',
    'debug' => getenv('GOOGLE_SDK_PHP_LOGGING') === 'true' ? true : false,
],
'api' => [
    'api_key' => getenv('SITB_API_KEY') ?: 'your_api_key_here',
],
```

**Sesudah:**
```php
'environment' => 'development',
'google_cloud' => [
    'project_id' => 'ckg-tb-staging',
    'credentials_path' => __DIR__ . '/credentials.json',
    'debug' => false,
],
'api' => [
    'api_key' => 'your_api_key_here',
],
```

### 3. Buat File Konfigurasi untuk Setiap Environment

Salin config.php dan sesuaikan untuk setiap environment:

```bash
cp config.php config.development.php
cp config.php config.staging.php
cp config.php config.production.php
```

### 4. Update lib/Boot.php

Modifikasi file [`lib/Boot.php`](../lib/Boot.php:33) untuk mendukung multiple config files:

```php
// Load configuration
$env = getenv('APP_ENV') ?: 'development';
$configFile = APPDIR . '/config.' . $env . '.php';

if (file_exists($configFile)) {
    self::$config = require $configFile;
} else {
    // Fallback ke config.php default
    self::$config = require APPDIR . '/config.php';
}
```

### 5. Test Konfigurasi Baru

```bash
# Test di environment development
export APP_ENV=development
php producer.php --mode=api

# Test di environment production
export APP_ENV=production
php producer.php --mode=pubsub
```

### 6. Update Deployment Scripts

Pastikan deployment scripts men-set environment variable `APP_ENV`:

```bash
# Contoh deployment script
export APP_ENV=production
php producer.php --mode=pubsub --start="2024-01-01 00:00:00" --end="now"
```
