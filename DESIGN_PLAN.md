# Design & Implementation Plan
## Sistem Pendaftaran Event Lari (Laravel, Tripay, Mailketing)

---

## 1. Arsitektur Sistem & Topologi

```
                 +-----------------------------------+
                 |        PENGUNJUNG / PELARI        |
                 +-----------------------------------+
                                   |
         +-------------------------+-------------------------+
         | (Info & Pengumuman)                               | (Klik "Daftar Tiket")
         v                                                   v
+-------------------------------+               +-------------------------------+
|  DOMAIN UTAMA (Static Page)   |               |   SUBDOMAIN SISTEM PENDAFTARAN |
|  https://eventlari.com        |               |   https://tiket.eventlari.com |
|  - HTML / Astro / Next.js     |               |   - Laravel 11.x Application  |
|  - Nginx Static Host / Vercel |               |   - PHP 8.2+ & TailwindCSS    |
+-------------------------------+               +-------------------------------+
                                                                |
              +-------------------------------------------------+----------------------------------+
              |                                                 |                                  |
              v                                                 v                                  v
+-----------------------------+               +---------------------------------+      +------------------------+
|  DATABASE (MySQL 8 / Maria) |               |  REDIS (Cache & Queue Worker)   |      |  FILAMENT ADMIN PANEL  |
|  - Transactions             |               |  - Quota concurrency locking    |      |  - Live Sales KPI      |
|  - Participants & BIB       |               |  - Asynchronous Mailketing jobs |      |  - Rekap Jersey Pabrik |
|  - Jersey Sizes & Audit Log |               |  - Session management           |      |  - Export Data Pelari  |
+-----------------------------+               +---------------------------------+      +------------------------+
              ^                                                 |
              |                                                 |
              +--------------------+                            |
                                   |                            |
                                   v                            v
               +-----------------------------------+  +-----------------------------------+
               |      TRIPAY PAYMENT GATEWAY       |  |      MAILKETING EMAIL API         |
               |  - API: Create Closed Transaction |  |  - REST API / Transactional Mail  |
               |  - Webhook: HMAC SHA256 Callback  |  |  - Dispatch E-Ticket & Invoice    |
               |  - Channels: QRIS, VA, Retail     |  |  - Auto Add to Subscriber List    |
               +-----------------------------------+  +-----------------------------------+
```

---

## 2. Pilihan Teknologi (Technology Stack)

| Lapisan (Layer) | Teknologi yang Digunakan | Alasan & Keunggulan |
|---|---|---|
| **Framework Backend** | **Laravel 11.x (PHP 8.2+)** | Standar industri untuk sistem transaksi yang kokoh, ekosistem queue andal, ORM Eloquent, dan keamanan teruji. |
| **Admin Panel** | **Filament PHP v3** | Sangat cepat dikembangkan (*rapid application development*), antarmuka modern, responsif, dukungan widget analitik, dan export Excel bawaan. |
| **Frontend Registrasi** | **Laravel Blade + Alpine.js + TailwindCSS** | Ringan, *zero-latency*, tidak butuh kompilasi rumit seperti SPA, sangat cepat diakses dari browser mobile (Instagram bio link). |
| **Database** | **MySQL 8.0+ / MariaDB 10.6+** | Mendukung transaksi ACID, JSON indexing, dan penguncian baris (`lockForUpdate`). |
| **Queue & Cache** | **Redis / Database Queue** | Menjamin proses pengiriman email ke Mailketing dan generate QR E-Ticket berjalan di latar belakang (*asynchronous*) tanpa membuat lambat pembeli. |
| **Payment Gateway** | **Tripay API (Closed Payment)** | Mendukung metode pembayaran lengkap di Indonesia (QRIS real-time, VA BCA, Mandiri, BRI, BNI, Alfamart/Indomaret). |
| **Email Gateway** | **Mailketing API (REST v1 / Transactional)** | Layanan email marketing & transaksional andal karya anak bangsa dengan reputasi inbox tinggi dan tarif terjangkau. |
| **Dokumen & PDF** | **Baryvdh/Laravel-DomPDF** & **Simplesoftwareio/simple-qrcode** | Meng-generate tiket PDF dan QR Code unik untuk verifikasi Racepack Collection. |

---

## 3. Rancang Antarmuka & Alur Pengguna (UI/UX Flow)

### 3.1. Halaman 1: Pemilihan Tiket (`/tickets`)
- **Header**: Banner event, tanggal perlombaan, dan lokasi flag-off.
- **Ticket Cards**:
  - Kartu kategori lari (misal: 5K Fun Run, 10K Open, 21K Half Marathon).
  - Menampilkan: Nama, Jarak, Harga (Rp), Fasilitas (Jersey, Medali Finisher, BIB, Refreshment), dan Kuota Tersisa.
  - Komponen **Quantity Stepper (`-` `[0]` `+`)** pada masing-masing kategori.
  - Pembeli dapat memilih lebih dari 1 tiket pada kategori yang sama atau berbeda sekaligus (cth: 2 tiket 10K dan 1 tiket 5K = Total 3 tiket).
- **Sticky Summary Footer (Khusus Mobile)**:
  - Menampilkan: "3 Tiket Dipilih | Total: Rp 750.000".
  - Tombol **"Lanjutkan ke Data Peserta ->"** (otomatis aktif jika tiket > 0).

### 3.2. Halaman 2: Formulir Pengisian Data Peserta (`/register/participants`)
- Formulir dinamis terbagi menjadi kartu/tab sejumlah total tiket yang dipilih.
  - Cth: *Tiket 1 dari 3: 10K Open*, *Tiket 2 dari 3: 10K Open*, *Tiket 3 dari 3: 5K Fun Run*.
- **Field per Peserta**:
  1. Nama Lengkap (Sesuai KTP/Paspor).
  2. Nomor Identitas (NIK/Paspor).
  3. Jenis Kelamin (Laki-laki / Perempuan) -> *Mempengaruhi rekomendasi ukuran jersey*.
  4. Tanggal Lahir (Validasi batas usia minimum lomba).
  5. Nomor WhatsApp & Email Peserta.
  6. Golongan Darah (A / B / AB / O / Tidak Tahu).
  7. **Pilihan Ukuran Jersey**:
     - Radio pill / dropdown selector: **XS, S, M, L, XL, XXL, 3XL, 4XL, 5XL**.
     - Keterangan detail size chart disediakan di halaman utama event (tersedia tautan bantuan).
  8. **Nama di BIB**: Maksimal 12 Karakter alfanumerik (khusus dicetak pada nomor dada BIB).
  9. Kontak Darurat (Nama Lengkap, Nomor HP, Hubungan).
  10. Catatan Medis Khusus (Alergi, asma, riwayat jantung - opsional).
  11. Komunitas Lari (Opsional).
- **Fitur Convenience UX**:
  - Tombol *"Gunakan Kontak Darurat Peserta 1 untuk Semua"* (menghemat waktu bagi grup/keluarga).
  - Validasi seketika (Client-side validation) sebelum lanjut ke Step 3.

### 3.3. Halaman 3: Ringkasan Pemesanan & Pembayaran (`/checkout`)
- **Bagian Kiri / Atas: Form Data Pembeli (Billing)**:
  - Form ringkas:
    - Nama Pembeli / Pemesan.
    - Email Pembeli (tempat faktur & email konfirmasi dikirimkan).
    - No. WhatsApp Pembeli.
  - Tombol Cepat: *"Saya Peserta 1 (Isi Otomatis)"*.
- **Bagian Pilihan Metode Pembayaran (Dropdown Selector)**:
  - Komponen **Dropdown Selector** yang rapi dan memuat seluruh channel Tripay yang aktif:
    - `QRIS` (ShopeePay, GoPay, OVO, Dana, LinkAja, Mobile Banking)
    - `BCAVA` (BCA Virtual Account)
    - `MANDIRIVA` (Mandiri Virtual Account)
    - `BNIVA` (BNI Virtual Account)
    - `BRIVA` (BRI Virtual Account)
    - `PERMATAVA` (Permata Virtual Account)
    - `BSIVA` (BSI Virtual Account)
    - `ALFAMART` (Gerai Alfamart)
    - `INDOMARET` (Gerai Indomaret)
  - Menampilkan kalkulasi biaya layanan / fee channel Tripay secara transparan.
- **Bagian Kanan / Bawah: Ringkasan Biaya**:
  - Subtotal Tiket (otomatis menerapkan harga Early Bird jika tanggal berlaku).
  - Biaya Layanan Payment Gateway Tripay.
  - **Grand Total Pembayaran**.
  - Checkbox: *"Saya telah membaca dan menyetujui Syarat & Ketentuan Peserta Lari"*.
  - Tombol: **"Bayar Sekarang (Tripay)"**.

### 3.4. Halaman 4: Transaksi & Pembayaran Tripay
- Sistem membuat transaksi *Closed Payment* ke API Tripay:
  - `POST https://tripay.co.id/api/transaction/create`
  - Parameter: `method`, `amount`, `customer_name`, `customer_email`, `customer_phone`, `order_items`, `signature`.
- **Dua Skenario UX**:
  1. *Opsi A (Direct Tripay Hosted Checkout)*: Pengguna langsung di-redirect ke `checkout_url` Tripay.
  2. *Opsi B (Native Payment Instructions)*: Pengguna tetap di subdomain pada URL `/order/{reference}` yang menampilkan QRIS langsung atau Nomor VA Tripay + tombol salin dan timer countdown 60 menit.

### 3.5. Halaman 5: Webhook & Konfirmasi Sukses
- Tripay menembakkan Webhook JSON ke `/api/tripay/callback`.
- Sistem memvalidasi `X-Callback-Signature` dengan `private_key` HMAC SHA256.
- Bila status `PAID`:
  1. Transaksi diubah ke `PAID`.
  2. Alokasikan Nomor BIB resmi untuk tiap peserta.
  3. Generate QR Code unik untuk tiap peserta.
  4. Dispatch Job antrean: `SendMailketingInvoiceJob` (ke Pembeli) dan `SendMailketingTicketJob` (ke masing-masing Peserta).
- Halaman Sukses menampilkan:
  - Indikator LUNAS (Centang Hijau).
  - Nomor Tiket dan E-Ticket Card untuk setiap peserta.
  - Tombol **"Unduh E-Ticket (PDF)"**.
  - Tautan *"Petunjuk Pengambilan Racepack (RPC)"*.

---

## 4. Rancang Panel Admin (Filament v3)

Panel admin dapat diakses di `/admin` dengan modul-modul berikut:

### 4.1. Dashboard Utama
- **Stats Overview Widget**:
  - Total Revenue (Rp) bersih.
  - Total Tiket Terjual (Unit) & Persentase Kuota.
  - Transaksi Sukses vs Pending vs Expired.
- **Chart Widget**:
  - Grafik tren pendaftaran harian (Line Chart).
  - Proporsi peserta per kategori lari (Doughnut Chart: 5K vs 10K vs 21K).

### 4.2. Halaman Khusus: Rekap Produksi Jersey Pabrik (`/admin/rekap-jersey`)
Fitur unggulan untuk manajemen logistik dan vendor konveksi:
- **Tabel Matriks Pivot (Category vs Size)**:

| Kategori Lari | XS | S | M | L | XL | XXL | 3XL | Total Jersey |
|---|---|---|---|---|---|---|---|---|
| **5K Fun Run** | 12 | 45 | 98 | 110 | 60 | 18 | 5 | **348 pcs** |
| **10K Race** | 8 | 50 | 120 | 140 | 85 | 24 | 7 | **434 pcs** |
| **21K Half** | 4 | 30 | 75 | 90 | 45 | 12 | 2 | **258 pcs** |
| **TOTAL KESELURUHAN** | **24** | **125** | **293** | **340** | **190** | **54** | **14** | **1.040 pcs** |

- **Fitur Tambahan Modul Jersey**:
  - Filter berdasarkan rentang tanggal pendaftaran (berguna jika produksi jersey dibagi menjadi *Batch 1* dan *Batch 2*).
  - Tombol **"Download Rekap Pabrik (Excel)"**: Menghasilkan file `.xlsx` dengan format resmi Purchase Order konveksi, dilengkapi lembar rincian per gender dan nama di BIB jika jersey dicetak nama peserta.

### 4.3. Resource Manajemen Transaksi (`TransactionResource`)
- Tabel transaksi lengkap dengan badge warna (Paid: Hijau, Unpaid: Kuning, Expired: Abu-abu).
- Kolom: No Invoice, Nama Pembeli, Email, Metode Bayar, Grand Total, Status, Tanggal.
- Aksi Cepat:
  - **Lihat Detail**: Modal rincian breakdown dan daftar peserta di dalamnya.
  - **Kirim Ulang Email**: Memicu pengiriman ulang via Mailketing jika email peserta salah/tidak terkirim.
  - **Sinkronisasi Status Tripay**: Memeriksa status langsung ke server Tripay.

### 4.4. Resource Data Peserta (`ParticipantResource`)
- Seluruh peserta lomba dalam satu tabel searchable.
- Filter: Kategori Lari, Ukuran Jersey, Status Pengambilan Racepack (Sudah/Belum), Golongan Darah.
- Fitur **Racepack Collection Check-in**:
  - Kolom toggle *"Racepack Sudah Diambil"* beserta tanggal & nama staf pengambil.
  - Scanner QR Code berbasis kamera web di browser admin/kru.
- Export Data:
  - Export data untuk asuransi & medis lomba (NIK, Nama, Usia, Golongan Darah, Kontak Darurat).
  - Export data untuk *Timing System* (RFID Chip bib vendor).

### 4.5. Pengaturan Sistem (`SystemSettings`)
- Pengaturan integrasi:
  - Tripay: Mode (Sandbox/Live), Merchant Code, API Key, Private Key, Biaya Admin (Customer/Merchant).
  - Mailketing: API Token, Base URL, List ID, Sender Name.

---

## 5. Integrasi API Eksternal

### 5.1. Alur Integrasi Tripay (Closed Payment)
1. **Request Pembuatan Transaksi**:
   - Endpoint: `POST https://tripay.co.id/api/transaction/create`
   - Headers: `Authorization: Bearer {API_KEY}`
   - Signature: `hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey)`
2. **Penanganan Webhook (Callback)**:
   - Endpoint: `POST /api/tripay/callback`
   - Verifikasi Header: `X-Callback-Signature === hash_hmac('sha256', $jsonRawPayload, $privateKey)`
   - Cek `status === 'PAID'` -> Eksekusi update database dalam `DB::transaction`.
   - Idempotency check: Jika transaksi sudah `PAID`, langsung kembalikan respons `{"success": true}` tanpa memproses ulang.

### 5.2. Alur Integrasi Mailketing
1. **Trigger Email Invoice**:
   - Dikirimkan ke email `buyer_email` sesaat setelah status menjadi `PAID`.
   - Menyertakan rincian pembayaran, nomor invoice, dan ringkasan daftar peserta yang didaftarkan.
2. **Trigger Email E-Ticket per Peserta**:
   - Dikirimkan ke masing-masing `participants.email`.
   - Berisi attachment PDF E-Ticket resmi atau gambar QR Code unik untuk penukaran Racepack.
   - Panggilan API Mailketing dibungkus dalam Laravel Job (`ShouldQueue`) via Redis agar proses pembayaran terasa instan di sisi pengguna.

---

## 6. Konfigurasi Deployment & Subdomain

### 6.1. Pengaturan DNS
- Domain Utama: `eventlari.com` -> `A Record` ke IP Web Server Landing Page (atau CNAME ke Vercel/Cloudflare).
- Subdomain: `tiket.eventlari.com` -> `A Record` ke IP VPS Laravel (`regrun`).

### 6.2. Konfigurasi Deployment di VPS CloudPanel
Sistem ini dioptimalkan untuk berjalan di **CloudPanel** (Nginx + PHP 8.2+ + MySQL):

1. **Membuat Site Baru di CloudPanel**:
   - Jenis Site: **PHP Site**
   - Domain: `tiket.eventlari.com` (atau subdomain event lainnya)
   - App: **Generic PHP** atau **Laravel**
   - PHP Version: **PHP 8.2** atau **PHP 8.3**
   - Site User: cth `regrun-user`
2. **Pengaturan Root Directory (Vhost)**:
   - Path Root: `/home/regrun-user/htdocs/tiket.eventlari.com/public`
3. **Database di CloudPanel**:
   - Buat Database MySQL & User di menu *Databases* CloudPanel.
   - Salin DB Name, DB User, DB Password ke `.env`.
4. **Environment Variables (.env)**:
   ```env
   APP_NAME="RegRun Multi-Event"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tiket.eventlari.com

   # Tripay Sandbox Configuration
   TRIPAY_API_KEY="DEV-ef2bKOHNkSJqCVWJ85wTIKYOYXm4m40Q7Gfioc5N"
   TRIPAY_PRIVATE_KEY="f85W1-PuaJ2-J3i96-XoBzw-WBtLf"
   TRIPAY_MERCHANT_CODE="T39430"
   TRIPAY_SANDBOX=true

   # Mailketing API Configuration
   MAILKETING_API_TOKEN="308b31d3313311776744479fa8fd7eb3"
   MAILKETING_SENDER_EMAIL="hi@jelatik.com"
   MAILKETING_SENDER_NAME="Registrasi Event Lari"
   ```
5. **Cron Job CloudPanel**:
   Tambahkan cron job di menu *Cron Jobs* CloudPanel:
   - Schedule: `* * * * *` (Setiap menit)
   - Command: `php /home/regrun-user/htdocs/tiket.eventlari.com/artisan schedule:run >> /dev/null 2>&1`
6. **Queue Worker (Background Process)**:
   Untuk memproses antrean email Mailketing tanpa membebani web request, tambahkan Systemd service di VPS:
   ```ini
   [Unit]
   Description=Laravel Queue Worker (RegRun)
   After=network.target

   [Service]
   User=regrun-user
   Group=regrun-user
   Restart=always
   ExecStart=/usr/bin/php /home/regrun-user/htdocs/tiket.eventlari.com/artisan queue:work --sleep=3 --tries=3

   [Install]
   WantedBy=multi-user.target
   ```

---

## 7. Rencana Tahapan Eksekusi (Implementation Roadmap)

1. **Fase 1: Inisialisasi Proyek & Database**:
   - Inisialisasi Laravel 11, instalasi Filament v3, dan konfigurasi database MySQL/Redis.
   - Buat file migration & model lengkap sesuai `DATABASE_SCHEMA.md`.
   - Seeder master data (Kategori tiket, master size jersey pabrik, akun admin awal).
2. **Fase 2: Alur Pendaftaran Multi-Step (Frontend)**:
   - Buat tampilan Step 1 (Pilih tiket & jumlah dengan Alpine.js quantity selector).
   - Buat tampilan Step 2 (Form data peserta dinamis sesuai jumlah tiket).
   - Buat tampilan Step 3 (Checkout ringkas & pilih channel Tripay).
3. **Fase 3: Integrasi Tripay Payment Gateway**:
   - Implementasi `TripayService` (Create Closed Transaction, get payment channels, fee calculation).
   - Implementasi endpoint Webhook Callback dengan verifikasi signature HMAC SHA-256.
   - Penanganan status kadaluwarsa (auto release reserved quota).
4. **Fase 4: Integrasi Mailketing & E-Ticket Generator**:
   - Implementasi `MailketingService` untuk pengiriman email transaksional.
   - Generate E-Ticket PDF dengan QR Code unik via dompdf.
   - Konfigurasi Laravel Queue Worker untuk pengiriman asinkron.
5. **Fase 5: Panel Admin & Modul Rekap Jersey Pabrik**:
   - Setup Filament Resource: Transaksi, Peserta, Kategori Tiket.
   - Buat Halaman Khusus: Rekapitulasi Jersey Pabrik (Pivot Matrix) & Export Excel.
   - Fitur scan QR Code untuk kru penukaran racepack di lokasi.
6. **Fase 6: Pengujian & Peluncuran (Go-Live)**:
   - Stress testing simulasi pendaftaran serentak (concurrency lock).
   - Uji coba transaksi Sandbox Tripay & pengiriman email Mailketing.
   - Deployment ke VPS dan pointing subdomain DNS.
