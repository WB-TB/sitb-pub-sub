# CKG Service untuk SITB Server
Integrasi data ASIK CKG dari aplikasi SITB melalui Google Pub/Sub dan Restful API.

## Ringkasan

Ini adalah implementasi PHP untuk mengintegrasikan data SIBT dengan Google Cloud Pub/Sub. Implementasi ini mencakup:

- **Producer**: Mengirim pesan ke topik Google Pub/Sub (mode Pub/Sub dan mode API)
- **Consumer**: Berlangganan dan memproses pesan dari langganan Google Pub/Sub
- **Client**: Kelas dasar dengan fungsionalitas umum untuk producer dan consumer

## Fitur

- **Pemrosesan Batch**: Penerbitan dan pemrosesan batch yang efisien
- **Retry Logic**: Mekanisme retry yang dapat dikonfigurasi dengan exponential backoff
- **Logging Komprehensif**: Logging detail untuk debugging dan monitoring
- **Layanan Systemd**: Manajemen layanan otomatis dengan timer

## Instalasi

### Instalasi Cepat

Gunakan skrip instalasi satu baris:

```bash
curl -sS https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/main/scripts/install.sh | sudo bash
```

Skrip ini akan:
- Mengunduh dan mengekstrak repositori ke `/opt/sitb-ckg`
- Menginstal dependensi Composer
- Membuat pengguna dan grup `sitb-ckg`
- Menyiapkan layanan systemd untuk consumer, producer-pubsub, dan producer-api
- Mengonfigurasi izin file dan kepemilikan yang tepat

### Instalasi Manual

1. Instal dependensi:
```bash
composer update
```

2. Siapkan kredensial Google Cloud:
   - Buat akun layanan di Google Cloud Console
   - Unduh file JSON kredensial
   - Letakkan di direktori proyek sebagai `credentials.json`

## Konfigurasi

Edit file `/opt/sitb-ckg/config.php`:

```php
return [
    'google_cloud' => [
        'project_id' => 'your-project-id',
        'credentials_path' => __DIR__ . '/credentials.json',
        'auth_scopes' => [
            'https://www.googleapis.com/auth/pubsub',
            'https://www.googleapis.com/auth/cloud-platform'
        ],
        'debug' => false
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
    'logging' => [
        'level' => 'INFO',
        'file' => __DIR__ . '/pubsub.log'
    ]
];
```

## Manajemen Layanan

Setelah instalasi dan konfigurasi, Anda dapat mengelola layanan menggunakan systemd:

### Layanan Consumer

```bash
# Mulai layanan
sudo systemctl start ckg-consumer.service

# Hentikan layanan
sudo systemctl stop ckg-consumer.service

# Mulai ulang layanan
sudo systemctl restart ckg-consumer.service

# Periksa status
sudo systemctl status ckg-consumer.service

# Lihat log
sudo journalctl -u ckg-consumer.service -f
```

### Layanan Producer Pub/Sub

Layanan ini berjalan sebagai timer yang memicu producer dalam mode Pub/Sub pada interval yang dijadwalkan.

```bash
# Periksa status timer
sudo systemctl status ckg-producer-pubsub.timer

# Mulai timer
sudo systemctl start ckg-producer-pubsub.timer

# Hentikan timer
sudo systemctl stop ckg-producer-pubsub.timer

# Lihat log
sudo journalctl -u ckg-producer-pubsub.timer -f
```

### Layanan Producer API

Layanan ini berjalan sebagai timer yang memicu producer dalam mode API pada interval yang dijadwalkan.

```bash
# Periksa status timer
sudo systemctl status ckg-producer-api.timer

# Mulai timer
sudo systemctl start ckg-producer-api.timer

# Hentikan timer
sudo systemctl stop ckg-producer-api.timer

# Lihat log
sudo journalctl -u ckg-producer-api.timer -f
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
- **Layanan Producer Pub/Sub**: `/var/log/sitb-ckg/producer-pubsub.log`
- **Layanan Producer API**: `/var/log/sitb-ckg/producer-api.log`

Lihat log menggunakan:
```bash
# Log consumer
sudo journalctl -u ckg-consumer.service -f

# Log producer Pub/Sub
sudo journalctl -u ckg-producer-pubsub.timer -f

# Log producer API
sudo journalctl -u ckg-producer-api.timer -f
```

## Pengujian

Jalankan rangkaian tes:

```bash
composer test
```

### Tes Unit

Proyek ini mencakup tes unit untuk semua komponen utama:

- **Tes Producer**: Tes penerbitan pesan, pemrosesan batch, dan kompresi
- **Tes Consumer**: Konsumsi pesan, acknowledgment, dan pengendalian alur
- **Tes Client**: Fungsionalitas dasar dan integrasi Google Cloud

Untuk menjalankan rangkaian tes tertentu:

```bash
# Jalankan tes producer
./vendor/bin/phpunit tests/ProducerTest.php

# Jalankan tes consumer
./vendor/bin/phpunit tests/ConsumerTest.php

# Jalankan tes client
./vendor/bin/phpunit tests/ClientTest.php
```

### Tes Mock

Untuk pengujian tanpa layanan Google Cloud yang sebenarnya, proyek ini mencakup implementasi mock:

```php
// Gunakan client mock untuk pengujian
$mockClient = new MockPubSubClient();
$producer = new \PubSub\Producer($config, $mockClient);
```

## Lisensi

Proyek ini dilisensikan di bawah Lisensi MIT.
