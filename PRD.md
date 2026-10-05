# Product Requirement Document (PRD)
## Sistem Pendaftaran Event Lari Multi-Event & Multi-Domain (CloudPanel + Laravel + Tripay + Mailketing)

---

## 1. Ringkasan Eksekutif & Visi Produk

Sistem ini adalah platform SaaS/Multi-Tenant berbasis **Laravel** yang dirancang fleksibel untuk menyelenggarakan **banyak event lari sekaligus** pada **1 VPS CloudPanel** dengan **banyak domain/subdomain independen**.

Sistem dapat dimulai dari **1 domain awal**, namun memiliki arsitektur yang siap diskalakan ke **puluhan domain event baru** tanpa perlu menduplikasi kode atau membuat database baru (*single codebase, shared database, dynamic domain context*).

Setiap event dapat memiliki:
- Domain atau subdomain khusus sendiri (misal: `tiket.nusantararun.com`, `marathonjakarta.id`, `reg.balitriathlon.com`).
- Identitas visual & branding sendiri (Logo, Favicon, dan Warna Tema).
- Opsi rekening Tripay & Mailketing sendiri (jika organizer ingin uang langsung masuk ke rekening merchant mereka), atau menggunakan rekening bersama default platform.

---

## 2. Tujuan & Sasaran (Goals & Objectives)

- **Skalabilitas Multi-Domain di CloudPanel**: Menjalankan 1 hingga puluhan domain event berbeda di atas 1 instalasi Laravel dan 1 Nginx virtual host alias di CloudPanel.
- **Isolasi Konteks Event**: Pengunjung yang membuka domain Event A hanya akan melihat katalog tiket, logo, dan rincian Event A tanpa tercampur dengan Event B.
- **Keamanan Transaksi & Anti-Overbooking**: Mengunci kuota tiket saat proses checkout dengan mekanisme *pessimistic database locking* (`lockForUpdate()`) agar kuota tiket tidak minus saat lonjakan pembelian bersamaan (*flash sale*).
- **Pemisahan Skema Gateway (Sandbox vs Produksi)**: Pengujian aman dengan simulasi terisolasi di Sandbox dan proteksi pemblokiran simulasi total saat di lingkungan Live Produksi.
- **Efisiensi Logistik Panitia**: Panel admin menyediakan rekap matriks ukuran jersey (XS s/d 5XL) siap kirim ke pabrik konveksi/garmen serta manajemen nomor BIB otomatis maupun tunda.

---

## 3. Arsitektur Multi-Domain CloudPanel (Single Codebase Multi-Tenancy)

### 3.1. Topologi Arsitektur

```
                                      [ Pengunjung / Peserta ]
                                                  │
                 ┌────────────────────────────────┼────────────────────────────────┐
                 ▼                                ▼                                ▼
        https://tiket.event-a.com        https://marathon-b.id            https://portal-utama.com
                 │                                │                                │
                 └────────────────────────────────┼────────────────────────────────┘
                                                  ▼
                               [ CloudPanel Server (Nginx + PHP-FPM) ]
                               Site Root: /home/user/htdocs/app/public
                               Nginx Server Name: $host (Multi-Domain Alias)
                                                  │
                                                  ▼ FastCGI (Passes HTTP_HOST)
                                   [ Laravel Application Kernel ]
                                                  │
                                                  ▼
                                 [ Middleware: ResolveEventDomain ]
                   Mencocokkan $request->getHost() dengan tabel `event_domains`
                                                  │
                 ┌────────────────────────────────┼────────────────────────────────┐
                 ▼                                ▼                                ▼
           [ Event A Context ]              [ Event B Context ]             [ Default Event ]
           - Kategori & Kuota A             - Kategori & Kuota B            - Landing Portal
           - Logo & Warna A                 - Logo & Warna B                - Master Admin
           - Gateway A (Optional)           - Gateway B (Optional)          - Global Tripay
```

### 3.2. Hirarki Resolusi Domain (Domain Resolution Hierarchy)

Ketika ada request HTTP masuk ke server:
1. **Pemeriksaan Parameter Slug (`/event/{slug}`)**: Jika pengguna membuka URL slug eksplisit, sistem mengambil event berdasarkan `slug`.
2. **Pemeriksaan Domain Alias (`event_domains`)**: Sistem membersihkan `HTTP_HOST` (menghapus port, lowercase, dan trailing slash) lalu mencari kecocokan pada tabel `event_domains` dengan status `is_active = true`.
3. **Pemeriksaan Legacy Custom Domain (`events.custom_domain`)**: Memeriksa kolom `custom_domain` pada tabel `events`.
4. **Fallback Default Event**: Jika domain tidak terdaftar (misal diakses via IP server atau domain testing), sistem otomatis menampilkan event bertanda `is_default = true` atau event aktif pertama.

---

## 4. Alur Pengguna (User Flows)

### 4.1. Alur Pembeli / Peserta (Public Multi-Domain Flow)

#### **Step 1: Pemilihan Tiket (`/`)**
1. Pengguna membuka domain event (misal `tiket.nusantararun.com`).
2. Halaman publik otomatis menampilkan judul event, banner, logo, dan kategori tiket untuk event tersebut.
3. Setiap kategori menampilkan kuota tersisa, harga reguler, dan harga promo Early Bird jika masih berlaku.
4. Pembeli bebas memilih jumlah tiket tanpa batasan per transaksi (bisa membeli untuk rombongan/komunitas).

#### **Step 2: Pengisian Data Peserta Lari (`/register/participants`)**
Sistem menampilkan formulir sebanyak jumlah tiket yang dipilih:
- **Data Peserta**: Nama Lengkap KTP, NIK/Paspor, Gender, Tanggal Lahir, No WhatsApp, Email Peserta.
- **Kebutuhan Medis & Racepack**: Golongan Darah (A, B, AB, O), Ukuran Jersey (**XS, S, M, L, XL, XXL, 3XL, 4XL, 5XL**).
- **Nomor Dada (BIB Name)**: Maksimal 12 karakter alfanumerik khusus dicetak pada nomor BIB.
- **Kontak Darurat**: Nama, Nomor Telepon, dan Hubungan Kontak Darurat.
- Fitur pendukung: Tombol *"Salin Kontak Darurat ke Peserta Lainnya"* untuk mempercepat pengisian.

#### **Step 3: Ringkasan & Pembayaran (`/checkout`)**
1. **Ringkasan Pesanan**: Rincian tiket, subtotal, biaya payment gateway Tripay, dan grand total.
2. **Data Pemesan**: Nama, Email, dan No HP pembeli.
3. **Pilihan Channel Pembayaran (Dropdown Selector Tripay)**:
   - **QRIS**: BCA Mobile, GoPay, OVO, ShopeePay, DANA, LinkAja, dan seluruh m-Banking.
   - **Virtual Account**: BCA, BRI, BNI, Mandiri, Permata, CIMB Niaga, BSI, Danamon.
   - **Convenience Store**: Alfamart, Indomaret.
4. Persetujuan Syarat & Ketentuan pendaftaran event lari.

#### **Step 4: Transaksi Tripay & Redirect**
1. Backend Laravel memproses reservasi kuota dengan `lockForUpdate()`.
2. Backend memanggil API Tripay dengan URL Return dan URL Callback dinamis yang sesuai dengan domain event saat ini (`https://{domain}/order/{invoice}`).
3. Pengguna diarahkan ke instruksi bayar atau halaman kasir Tripay.

#### **Step 5: Webhook & E-Ticket Otomatis**
1. Webhook Tripay diterima di `/api/tripay/callback`.
2. Validasi tanda tangan HMAC-SHA256 dengan `hash_equals()`.
3. Status berubah menjadi `PAID`, kuota berpindah dari `reserved` ke `sold`.
4. Jika opsi `auto_generate_bib` aktif, nomor BIB otomatis di-generate.
5. Email invoice dan Official E-Ticket dengan QR Code dikirim otomatis via Mailketing API.

---

## 5. Fitur Panel Admin (Admin & Organizer Panel)

### 5.1. Manajemen Multi-Domain & Branding Event
- Tambah domain alias tanpa batas untuk setiap event (misal: `eventlari.com`, `tiket.eventlari.com`, `reg.eventlari.com`).
- Menetapkan salah satu domain sebagai **Domain Utama (Primary)**.
- Kustomisasi branding per event: Logo URL, Favicon, dan Warna Aksen Brand.
- **Event Gateway Credentials Hierarchy**:
  - Organizer dapat memasukkan Merchant Code & API Key Tripay / Mailketing milik mereka sendiri jika ingin hasil penjualan tiket masuk ke rekening tersendiri.
  - Jika dikosongkan, sistem otomatis menggunakan konfigurasi gateway global platform.

### 5.2. Dashboard & Monitoring Real-Time
- Statistik pendapatan bersih & kotor, tiket terjual vs kuota, dan breakdown per kategori lomba.
- Grafik tren penjualan harian dan live feed transaksi pendaftaran.

### 5.3. Manajemen Transaksi
- Filter status transaksi (UNPAID, PAID, EXPIRED, FAILED).
- Pencarian berdasarkan invoice, nama, email, atau nomor HP.
- Kirim ulang email invoice / e-ticket via Mailketing dengan 1 klik.
- Konfirmasi pembayaran manual (VIP / Offline ticket).

### 5.4. Manajemen Data Peserta & Nomor BIB
- Master data seluruh pelari yang telah melunasi tiket.
- Filter berdasarkan kategori lomba, ukuran jersey, gender, dan status pengambilan racepack.
- **Pengaturan Penomoran BIB**:
  - *Mode Otomatis*: BIB langsung diberikan saat transaksi lunas.
  - *Mode Tunda*: BIB ditunda, dan dapat dialokasikan massal oleh panitia menjelang race day.
- Fitur check-in pengambilan Racepack Collection (RPC) dengan pencatatan waktu dan admin validator.
- Export CSV data pelari untuk tim medis, asuransi, dan vendor timing system.

### 5.5. Modul Rekap Produksi Jersey Pabrik (Vendor Konveksi)
- **Tabel Matriks Ukuran Jersey (Pivot Grid)**: Kategori Lari x Ukuran Jersey (XS s/d 5XL).
- Pemisahan data jersey Pria dan Wanita jika ada spesifikasi pola potongan berbeda.
- Export 1-Klik Excel Rekap Jersey format Purchase Order siap kirim ke pabrik konveksi.

### 5.6. Pengaturan Gateway & Email (Global System Settings)
- **Tripay Dual-Profile**: Pemisahan konfigurasi kredensial Sandbox (Testing) dan Live Produksi.
- **Mailketing Integration**: Konfigurasi token API, sender email, dan sender name.
- Fitur simulasi tes kirim email langsung dari panel admin.

---

## 6. Skema Database (Database Schema & Entity Relationship)

### 6.1. Tabel `events`
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment |
| `title` | VARCHAR(200) | Nama resmi event lari |
| `slug` | VARCHAR(100) (Unique) | URL slug identifier |
| `description` | TEXT | Informasi dan ketentuan event |
| `venue_name` | VARCHAR(200) | Lokasi venue race |
| `venue_address` | TEXT | Alamat lengkap venue |
| `race_date` | DATE | Tanggal pelaksanaan lomba |
| `race_start_time` | TIME | Jam mulai (flag-off) |
| `rpc_start_date` | DATE | Tanggal mulai Racepack Collection |
| `rpc_end_date` | DATE | Tanggal akhir Racepack Collection |
| `rpc_location` | TEXT | Lokasi booth pengambilan RPC |
| `banner_image` | VARCHAR(255) | URL banner promosi event |
| `logo_url` | VARCHAR(255) | URL logo khusus event |
| `primary_color` | VARCHAR(20) | Hex warna tema brand (default: `#ea580c`) |
| `secondary_color` | VARCHAR(20) | Hex warna sekunder (default: `#0f172a`) |
| `custom_domain` | VARCHAR(150) (Index) | Domain utama event (shorthand) |
| `auto_generate_bib`| BOOLEAN | Kebijakan generate BIB (True: otomatis, False: tunda) |
| `is_active` | BOOLEAN | Status buka/tutup registrasi |
| `is_default` | BOOLEAN | Penanda event utama platform |
| `tripay_merchant_code` | VARCHAR(50) (Nullable) | Override merchant code khusus event |
| `tripay_api_key` | TEXT (Nullable) | Override API Key Tripay khusus event |
| `tripay_private_key` | TEXT (Nullable) | Override Private Key Tripay khusus event |
| `mailketing_api_token` | TEXT (Nullable) | Override API Token Mailketing organizer |
| `mailketing_sender_email` | VARCHAR(150) (Nullable) | Override sender email organizer |
| `mailketing_sender_name` | VARCHAR(150) (Nullable) | Override sender name organizer |

### 6.2. Tabel `event_domains` (Multi-Domain CloudPanel Support)
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment |
| `event_id` | BIGINT (FK) | Relasi ke `events.id` (cascade delete) |
| `domain` | VARCHAR(150) (Unique) | Host domain terdaftar (contoh: `tiket.nusantararun.com`) |
| `is_primary` | BOOLEAN | Menandai apakah ini domain utama event |
| `is_active` | BOOLEAN | Menandai apakah domain aktif diarahkan |
| `ssl_verified_at`| TIMESTAMP (Nullable) | Waktu verifikasi sertifikat SSL di CloudPanel |

### 6.3. Tabel Pendukung Lainnya
- `ticket_categories`: Kategori lomba (5K, 10K, Half Marathon), harga, early bird, kuota, terjual, dan reserved.
- `jersey_sizes`: Master ukuran jersey XS hingga 5XL dengan panduan ukuran dada dan panjang badan.
- `transactions`: Data pesanan, invoice unik, buyer contact, total nominal, fee, status, channel Tripay.
- `transaction_items`: Rincian kategori dan kuantitas per transaksi.
- `participants`: Data detail pelari, nomor identitas, golongan darah, nomor BIB, kode tiket, QR code hash, dan status RPC.
- `system_settings`: Konfigurasi global default (Tripay mode sandbox/prod, Mailketing token).
- `payment_logs` & `email_logs`: Log audit komunikasi webhook dan email.

---

## 7. Persyaratan Non-Fungsional & Infrastruktur CloudPanel

1. **Konfigurasi CloudPanel Nginx**:
   - Single site root mengarah ke `/home/{user}/htdocs/{site}/public`.
   - Konfigurasi `server_name` menerima domain-domain alias yang didaftarkan pada CloudPanel.
   - FastCGI parameters meneruskan header `HTTP_HOST`, `HTTPS`, dan `HTTP_X_FORWARDED_FOR` ke kernel Laravel.
2. **Sertifikat SSL Let's Encrypt Otomatis**:
   - CloudPanel mendukung penambahan domain alias pada satu site dan otomatis menerbitkan multi-domain Let's Encrypt certificate dengan perpanjangan otomatis.
3. **Queue Worker & Antrean Asinkron**:
   - Supervisor worker di CloudPanel menjalankan `php artisan queue:work` untuk pengiriman email Mailketing berkecepatan tinggi tanpa membebani thread HTTP checkout.
4. **Pencegahan Denial-of-Inventory & Bot**:
   - Rate limiting 20 req/menit pada rute checkout pembayaran.
   - Pelepasan kuota instan (`decrement('reserved_count')`) jika koneksi Tripay gagal.
   - Pemblokiran fitur simulasi bayar total pada mode produksi (HTTP 403).
5. **Responsivitas Mobile (Mobile-First)**:
   - Antarmuka pendaftaran dirancang responsif dan ringan, ideal diakses langsung dari tautan bio Instagram/media sosial via smartphone.
