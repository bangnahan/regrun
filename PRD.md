# Product Requirement Document (PRD)
## Sistem Pendaftaran Event Lari Multi-Event (Laravel + Tripay + Mailketing)

---

## 1. Ringkasan Eksekutif & Visi Produk

Sistem ini adalah platform berbasis **Laravel** yang dirancang fleksibel untuk **multi-event lari**. Sistem dapat digunakan berulang kali untuk berbagai event berbeda, baik dengan skema **1 VPS CloudPanel untuk banyak domain event**, maupun **1 instalasi per event**.

Sistem ini berjalan terisolasi pada subdomain pendaftaran (misal: `tiket.eventlari.com`, `reg.marathonjakarta.com`, atau via slug `/event/{slug}`) sedangkan domain utama digunakan untuk halaman statis promosi.

Sistem menangani:
1. **Multi-Event & Multi-Kategori**: Admin dapat membuat dan mengelola banyak event dengan kuota dan harga *Early Bird* masing-masing.
2. **Katalog & Pemilihan Tiket**: Mendukung pembelian banyak tiket sekaligus tanpa batasan kuota per transaksi.
3. **Pengisian Data Peserta Lari**: Mendalam dan lengkap (Nama KTP, NIK, WhatsApp, Email, Golongan Darah, Nama di BIB, Kontak Darurat, dan Ukuran Jersey dari **XS hingga 5XL**).
4. **Checkout & Pembayaran Tripay**: Pemilihan seluruh channel pembayaran Tripay menggunakan **Dropdown Selector** yang ringkas dan efektif.
5. **Otomatisasi Notifikasi Email (Mailketing API)**: Pengiriman invoice & e-ticket instan menggunakan API token Mailketing.
6. **Admin Panel Terintegrasi**: Monitoring transaksi real-time, statistik pendaftaran, serta **Rekapitulasi Ukuran Jersey Pabrik (XS - 5XL)** siap kirim ke vendor garmen/konveksi.
7. **Deployment Siap Pakai di CloudPanel**: Konfigurasi optimal untuk Nginx, PHP 8.2+, MySQL, dan supervisor queue di CloudPanel.

---

## 2. Tujuan & Sasaran (Goals & Objectives)

- **Kecepatan & Kemudahan Pengguna**: Alur registrasi multi-step yang intuitif tanpa registrasi akun yang rumit (guest checkout dengan email pembeli).
- **Akurasi Data Produksi**: Mencegah salah ukuran jersey dan salah data identitas pelari untuk BIB dan asuransi event.
- **Keamanan Transaksi & Anti-Overbooking**: Mengunci kuota tiket saat proses checkout dengan mekanisme *temporary hold/lock* agar tiket tidak terjual melebihi kapasitas (race condition safety).
- **Efisiensi Logistik Panitia**: Panel admin menyediakan rekap matriks ukuran jersey siap kirim ke pabrik konveksi/garmen serta rekap data pelari lengkap.

---

## 3. Arsitektur Domain & Subdomain

```
+--------------------------------------------------------------------------+
|  Domain Utama: https://eventlari.com (Static Landing Page / Jamstack)    |
|  - Hero Banner, Jadwal, Race Category Info, Rute Map, Sponsor, FAQ       |
|  - CTA Button: "Daftar Sekarang" -> Redirect ke Subdomain                |
+--------------------------------------------------------------------------+
                                    |
                                    v (Redirect)
+--------------------------------------------------------------------------+
|  Subdomain: https://tiket.eventlari.com (Laravel Application)            |
|  - Step 1: /tickets (Pilih Kategori & Jumlah Tiket)                      |
|  - Step 2: /register/participants (Input Data Pelari Tiap Tiket)         |
|  - Step 3: /checkout (Data Pembeli & Pilihan Channel Tripay)             |
|  - Step 4: Redirect ke Tripay Checkout / Pembayaran                      |
|  - Webhook: /api/tripay/callback (Update status & Trigger Mailketing)    |
|  - Success/Status: /order/{reference} (Cek Status & Unduh E-Ticket)      |
|  - Admin Panel: /admin (Dashboard, Rekap Jersey Pabrik, Peserta, dll)    |
+--------------------------------------------------------------------------+
```

---

## 4. Alur Pengguna (User Flows)

### 4.1. Alur Pembeli / Peserta (Public Web Flow)

#### **Step 1: Pemilihan Tiket (`/tickets`)**
1. Pengguna melihat daftar kategori tiket yang tersedia (misal: 5K Fun Run, 10K, 21K Half Marathon).
2. Setiap kategori menampilkan:
   - Nama Kategori & Deskripsi singkat.
   - Jarak tempuh, benefit (Jersey, Medali, BIB, Goodie Bag, Refreshment).
   - Harga per tiket.
   - Status kuota (Tersedia, Sisa X Tiket, atau Habis/Sold Out).
3. Pengguna dapat memilih jumlah tiket (Quantity Selector) pada satu atau beberapa kategori sekaligus (misal: 2 tiket 10K + 1 tiket 5K).
4. **Fleksibilitas Jumlah Tiket**: Pembeli dapat memilih jumlah tiket berapapun tanpa batasan kuota per transaksi (bebas membeli tiket kolektif untuk rombongan/komunitas).
5. Tombol *"Lanjut Pengisian Data Peserta"*.

#### **Step 2: Pengisian Data Tiket / Peserta (`/register/participants`)**
Sistem menampilkan formulir sebanyak jumlah tiket yang dipilih pada Step 1. Jika pengguna memilih 3 tiket, maka muncul 3 blok form peserta:
- **Data Peserta Tiap Tiket**:
  1. **Kategori Tiket**: (Read-only sesuai yang dipilih, misal: Tiket #1 - 10K)
  2. **Nama Lengkap**: (Sesuai KTP/SIM/Paspor - untuk sertifikat & asuransi)
  3. **Nomor Identitas (NIK/KTP/Passport)**: (Wajib, validasi format)
  4. **Jenis Kelamin**: (Laki-laki / Perempuan)
  5. **Tanggal Lahir**: (Penting untuk kategori umur & verifikasi kelayakan medis)
  6. **Nomor WhatsApp**: (Format +62 / 08xx untuk konfirmasi darurat)
  7. **Email Peserta**: (Untuk pengiriman e-ticket personal)
  8. **Golongan Darah**: (A / B / AB / O / Tidak Tahu - kebutuhan medis saat race)
  9. **Ukuran Jersey**: (Pilihan lengkap: **XS, S, M, L, XL, XXL, 3XL, 4XL, 5XL**. Informasi detail size chart disediakan di halaman domain utama event)
  10. **Nama di Nomor Dada (BIB Name)**: (Maksimal 12 karakter alfanumerik - khusus dicetak pada nomor dada BIB, bukan pada jersey)
  11. **Nama Kontak Darurat & Nomor HP**: (Wajib, bukan nomor peserta sendiri)
  12. **Hubungan Kontak Darurat**: (Orang tua, Suami/Istri, Saudara, Teman)
  13. **Riwayat Penyakit / Catatan Medis**: (Opsional, asma, jantung, alergi obat, dll)
  14. **Nama Komunitas Lari / Klub**: (Opsional)
- Fitur pendukung: Tombol *"Salin Kontak Darurat ke Peserta Lainnya"* untuk mempercepat pengisian.
- Validasi data di sisi client & server sebelum melanjutkan.

#### **Step 3: Ringkasan & Pembayaran (`/checkout`)**
1. **Ringkasan Pesanan (Order Summary)**:
   - Rincian kategori tiket, jumlah, dan subtotal harga (otomatis menerapkan harga Early Bird jika tanggal aktif).
   - Biaya layanan / Fee Payment Gateway Tripay.
   - Total Pembayaran Akhir.
2. **Data Pemesan / Penanggung Jawab Pembayaran**:
   - Hanya memerlukan 3 kolom sederhana:
     - **Nama Lengkap Pembeli**
     - **Email Pembeli** (Email tujuan pengiriman invoice utama & bukti pembayaran)
     - **Nomor WhatsApp Pembeli**
   - Checkbox shortcut: *"Gunakan data Peserta 1 sebagai Pembeli"*.
3. **Pilihan Metode Pembayaran (Dropdown Selector Tripay)**:
   - Menggunakan komponen **Dropdown Selector** yang bersih, ringkas, dan mengelompokkan semua channel Tripay:
     - **QRIS**: Seluruh e-wallet (GoPay, OVO, ShopeePay, Dana, LinkAja) & seluruh aplikasi Mobile Banking.
     - **Virtual Account**: BCA, Mandiri, BNI, BRI, Permata, CIMB Niaga, BSI, Muamalat.
     - **Gerai Retail**: Alfamart, Indomaret.
4. **Persetujuan Syarat & Ketentuan**: Checkbox *"Saya menyetujui syarat & ketentuan event lari dan menyatakan data yang diisi adalah benar dan sehat secara medis"*.
5. Tombol *"Bayar Sekarang"*.

#### **Step 4: Eksekusi Pembayaran Tripay & Redirect**
1. Backend Laravel membuat order di Tripay via API (Closed Payment).
2. Tiket dikunci (*reserved*) dengan masa berlaku (misal: 30-60 menit tergantung expired channel Tripay).
3. Pengguna langsung di-redirect ke halaman pembayaran Tripay (Tripay Checkout Page) atau halaman instruksi bayar yang memuat kode bayar / QRIS / VA beserta timer hitung mundur.

#### **Step 5: Webhook, Sukses & Pengiriman E-Ticket**
1. Tripay mengirimkan notifikasi Webhook ke endpoint Laravel saat pembayaran lunas (`PAID`).
2. Backend memverifikasi tanda tangan digital (HMAC Signature Tripay).
3. Status transaksi diubah menjadi `PAID`.
4. Sistem otomatis mengalokasikan / meng-generate:
   - Nomor Invoice Unik (misal: `INV-RUN-202610-00123`).
   - Kode Booking / E-Ticket unik dan QR Code untuk tiap peserta (untuk Racepack Collection).
5. **Integrasi Mailketing**:
   - Sistem menembakkan API Mailketing untuk mengirimkan:
     - Email konfirmasi pembayaran & invoice ke email Pembeli.
     - Email tiket digital resmi (E-Ticket) berisikan QR Code, rincian BIB, dan panduan Racepack Collection ke email masing-masing peserta.
6. Halaman Sukses (`/order/{reference}`): Pengguna dapat melihat status sukses, mengunduh file PDF e-ticket, dan ada tombol "Kirim ke WhatsApp".

---

## 5. Fitur Panel Admin (Admin & Organizer Panel)

Panel admin diperuntukkan bagi panitia pelaksana event dengan hak akses berjenjang (Super Admin, Finance, Logistik/Racepack).

### 5.1. Dashboard Utama
- **Metrik Utama (Stat Cards)**:
  - Total Pendapatan Bersih & Kotor (Rp).
  - Total Tiket Terjual vs Total Kuota Keseluruhan (% keterisian).
  - Jumlah Transaksi Berhasil (`PAID`), Menunggu Bayar (`UNPAID`), dan Kedaluwarsa (`EXPIRED`).
  - Total Peserta terdaftar berdasarkan kategori (5K, 10K, dll).
- **Grafik Tren Penjualan Harian**: Line chart transaksi sukses per hari.
- **Aktivitas Transaksi Terkini**: Live feed pendaftaran masuk.

### 5.2. Manajemen Transaksi
- Daftar seluruh transaksi dengan filter:
  - Status (Pending, Paid, Expired, Failed).
  - Metode Pembayaran (BCA VA, QRIS, dll).
  - Rentang Tanggal Transaksi.
- Pencarian cepat: Berdasarkan Nomor Invoice, Nama Pembeli, Email, atau No HP.
- Detail Transaksi: Menampilkan data pembayaran Tripay, log payload webhook, dan daftar peserta dalam transaksi tersebut.
- Aksi Admin:
  - Tombol **Kirim Ulang Email E-Ticket** via Mailketing.
  - Tombol **Cek Status Pembayaran Manual** ke API Tripay (jika webhook tertunda).
  - Tombol **Batalkan Transaksi / Set Paid Manual** (khusus Super Admin untuk tiket VIP/sponsor offline).

### 5.3. Manajemen Data Peserta (Master Data Pelari)
- Tabel lengkap seluruh peserta dari semua transaksi yang berstatus `PAID`.
- Filter berdasarkan: Kategori Lari (5K, 10K, 21K), Ukuran Jersey, Status Pengambilan Racepack (Belum/Sudah Diambil), Jenis Kelamin.
- Pencarian berdasarkan: Nama Peserta, Nomor BIB, NIK, No WhatsApp, atau Kode Tiket.
- Fitur Edit Data Terbatas: Perubahan nama atau ejaan (dengan log audit).
- Fitur Assign / Generate BIB Number:
  - Otomatis berurutan berdasarkan kategori (misal: 10K diawali `10001`, 5K diawali `50001`).
  - Manual import nomor BIB dari vendor timing system.
- Export Data:
  - Export CSV / Excel data pelari untuk tim medis & asuransi (Nama, NIK, Tgl Lahir, Golongan Darah, Kontak Darurat).
  - Export data timing system (BIB, Nama, Kategori, Gender).

### 5.4. Modul Rekap Produksi Jersey Pabrik (Khusus Logistik/Vendor Garmen)
Modul prioritas tinggi untuk memastikan data jersey akurat sebelum deadline cetak garmen:
- **Tabel Matriks Ukuran Jersey (Pivot Grid)**:
  Baris: Kategori Lari (5K, 10K, 21K, dll).
  Kolom: Ukuran (XS, S, M, L, XL, XXL, 3XL, Total).
- **Rekap Gender & Ukuran**: Pemisahan data jersey Pria dan Wanita jika ada spesifikasi potongan jersey berbeda (*Men/Women Cut*).
- **Status Cut-off Produksi**: Indikator peserta yang mendaftar sebelum tanggal batas jersey (*Early batch*) vs pendaftaran reguler.
- **Export Khusus Vendor Pabrik**:
  - Tombol 1-klik: **"Export Rekap Jersey Pabrik (Excel)"** dengan format rapi berisi:
    1. Sheet 1: Rangkuman Jumlah per Ukuran (Purchase Order format untuk pabrik).
    2. Sheet 2: Daftar Detail Peserta + Ukuran Jersey + Kategori.
    3. Sheet 3: Breakdown per Gender & Kategori.

### 5.5. Manajemen Kategori & Tiket Event
- Kelola nama kategori lari, harga, kuota maksimal, tanggal buka dan tutup registrasi.
- Status aktif/non-aktifkan penjualan tiket sewaktu-waktu.
- Early Bird vs Regular Pricing (otomatis berganti harga berdasarkan kuota atau tanggal).

### 5.6. Pengaturan Sistem (Settings)
- **Konfigurasi Tripay**:
  - Mode: Sandbox / Production.
  - API Key, Private Key, Merchant Code.
  - Pilihan pembebanan fee (dibebankan ke merchant atau customer).
- **Konfigurasi Mailketing**:
  - API Token, Sender Name, Sender Email.
  - Pemilihan List ID campaign di Mailketing.
  - Template Email editor (Invoice & E-Ticket HTML).
- **Event Info & Ketentuan**:
  - Tanggal pelaksanaan race, lokasi, peta rute, tautan size chart jersey.

---

## 6. Persyaratan Non-Fungsional (Non-Functional Requirements)

1. **Keandalan & Ketahanan Lonjakan Trafik (Flash Sale / Early Bird Rush)**:
   - Sistem antrean (Queue Worker Laravel) untuk pengiriman email Mailketing agar proses checkout tidak lambat (*non-blocking I/O*).
   - Redis Cache untuk menyimpan pembacaan kuota tiket.
   - Database Locking (`SELECT ... FOR UPDATE` atau Redis Distributed Lock) untuk mencegah kuota tiket minus jika dibeli bersamaan pada detik yang sama.
2. **Keamanan Data (Security & Compliance)**:
   - Validasi HMAC SHA-256 pada Webhook Tripay untuk mencegah spoofing pembayaran palsu.
   - Enkripsi data sensitif (NIK).
   - Perlindungan CSRF di semua form publik.
   - Rate limiting pada form checkout untuk mencegah bot spam.
3. **Penyimpanan & Logging**:
   - Logging lengkap setiap payload request dan response dari Tripay maupun Mailketing untuk kebutuhan audit dan debugging.
4. **Responsivitas Mobile (Mobile-First)**:
   - 80%+ pendaftaran event lari dilakukan melalui smartphone (link dari bio Instagram). Tampilan step registrasi wajib sangat ramah layar sentuh (mobile-friendly).
