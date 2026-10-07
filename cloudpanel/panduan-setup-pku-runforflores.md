# Panduan Integrasi Multi-Domain: Pekanbaru Charity Run for Flores
**Domain:** `pku.runforflores.com`  
**Platform:** Jelatix Ticketing (`jelatix.com`)  
**Server:** CloudPanel VPS (`srv2005123`)

---

## 1. Alur Kerja Multi-Domain

```
                    [ Peserta Membuka ]
                 https://pku.runforflores.com
                             │
                             ▼ (DNS A-Record)
                 [ CloudPanel VPS Server ]
              Site: /home/bangnahan/htdocs/jelatix.com
                             │
                             ▼ FastCGI (Host: pku.runforflores.com)
                  [ Laravel Application ]
                             │
                             ▼ Middleware: ResolveEventDomain
                 Cocok dengan Event:
        "Pekanbaru Charity Run for Flores"
                             │
                             ▼
         [ Formulir Tiket Khusus Pekanbaru Charity Run ]
         - Kategori Tiket (5K, 10K, dll)
         - Rekap Jersey, Kuota, & Medali
         - Pembayaran Tripay QRIS & VA Otomatis
```

---

## 2. Langkah 1: Pengaturan DNS di Registrar Domain `runforflores.com`

Buka penyedia DNS tempat domain `runforflores.com` dikelola (Cloudflare, Niagahoster, Domainesia, Rumahweb, dsb):

1. Masuk ke menu **DNS Management / DNS Records**.
2. Tambahkan satu Record baru:
   - **Type**: `A`
   - **Name / Host**: `pku` (atau `pku.runforflores.com`)
   - **IPv4 Address**: `[IP_VPS_ANDA]` (Gunakan alamat IP VPS yang sama dengan `jelatix.com`)
   - **TTL**: Auto / 300 / 3600
   - **Proxy Status (jika di Cloudflare)**: Pilih **DNS Only** (icon awan abu-abu) saat pertama kali agar penerbitan sertifikat SSL Let's Encrypt di CloudPanel berjalan instan tanpa kendala.

*(Alternatif: Anda juga bisa menggunakan Type `CNAME` dengan Name `pku` dan Target `jelatix.com`)*.

---

## 3. Langkah 2: Tambahkan Domain Alias di CloudPanel

1. Buka dashboard CloudPanel Anda di browser:
   ```
   https://[IP_VPS_ANDA]:8443
   ```
2. Masuk ke menu **Sites** > klik site **jelatix.com**.
3. Klik tab **Domain Names**.
4. Di bagian **New Domain Name**, ketikkan:
   ```
   pku.runforflores.com
   ```
5. Klik tombol **Add Domain**.
   > **Catatan:** CloudPanel secara otomatis akan menambahkan domain ini ke Nginx `server_name` sehingga diarahkan langsung ke instalasi Laravel yang sama di `/home/bangnahan/htdocs/jelatix.com/public`.

---

## 4. Langkah 3: Terbitkan Sertifikat SSL (HTTPS) di CloudPanel

1. Masih di halaman site **jelatix.com**, klik tab **SSL/TLS**.
2. Klik tombol **New Let's Encrypt Certificate** (atau **Manage Certificates**).
3. Anda akan melihat daftar domain yang terhubung ke site. Pastikan mencentang:
   - `jelatix.com`
   - `pku.runforflores.com`
4. Klik **Create and Install**.
5. Tunggu beberapa detik hingga status SSL berubah menjadi **Active** (gembok hijau).

---

## 5. Langkah 4: Hubungkan Event di Panel Admin Jelatix

### Opsi A: Melalui Web Panel Admin (Mudah & Visual)
1. Buka browser: `https://jelatix.com/admin/events`
2. Klik tombol **➕ Tambah Event Baru**:
   - **Judul Event**: `Pekanbaru Charity Run for Flores`
   - **Slug**: `pekanbaru-charity-run-for-flores`
   - **Tanggal Lomba**: *Tentukan tanggal pelaksanaan (misal: 2026-12-06)*
   - **Waktu Mulai**: *06:00:00*
   - **Lokasi Venue**: *Contoh: Jl. Diponegoro / Stadion Kaharuddin Nasution, Pekanbaru*
   - **Custom Domain**: `pku.runforflores.com`
   - **Warna Utama (Brand Color)**: *Pilih warna tema acara (contoh: `#ea580c` oranye atau `#0284c7` biru)*
   - **Centang**: `Aktifkan Pendaftaran Event`
   - **Centang**: `Generate Kategori Tiket Default (5K, 10K, 21K)` (agar langsung dibuatkan template tiket)
3. Klik **Simpan Event**.
4. Di daftar event, klik **Kelola Tiket / Kategori** pada event tersebut untuk menyesuaikan harga tiket (misal 5K Amal: Rp 150.000, 10K: Rp 200.000) dan kuota peserta.

---

### Opsi B: Melalui Terminal Server (Instan via Tinker)
Jika Anda sedang membuka SSH terminal VPS, Anda dapat langsung menjalankannya dengan satu baris perintah:

```bash
cd ~/htdocs/jelatix.com
php artisan tinker --execute="
\$event = \App\Models\Event::updateOrCreate(
    ['slug' => 'pku-run-for-flores'],
    [
        'title' => 'Pekanbaru Charity Run for Flores',
        'race_date' => '2026-12-06',
        'race_start_time' => '06:00:00',
        'venue_name' => 'Arena CFD Jl. Diponegoro / Gajah Mada',
        'venue_address' => 'Kota Pekanbaru, Riau',
        'rpc_start_date' => '2026-12-04',
        'rpc_end_date' => '2026-12-05',
        'rpc_location' => 'Kantor Jelatix, Jl Tapah No 22, Pekanbaru',
        'custom_domain' => 'pku.runforflores.com',
        'description' => 'Event lari amal Pekanbaru Charity Run for Flores, menggalang solidaritas dan donasi kemanusiaan untuk saudara-saudara kita di Flores NTT.',
        'is_active' => true,
        'auto_generate_bib' => true,
    ]
);

\App\Models\EventDomain::updateOrCreate(
    ['domain' => 'pku.runforflores.com'],
    ['event_id' => \$event->id, 'is_primary' => true, 'is_active' => true]
);

// Tambahkan Kategori Tiket Amal
\App\Models\TicketCategory::updateOrCreate(
    ['event_id' => \$event->id, 'code' => '5K-CHARITY'],
    [
        'name' => '5K Charity Run',
        'description' => 'Termasuk: Jersey Dryfit, Medali Finisher, BIB, Refreshment, dan Donasi Flores.',
        'price' => 175000,
        'early_bird_price' => 150000,
        'early_bird_end_date' => now()->addDays(20),
        'quota' => 500,
        'min_age' => 10,
        'sort_order' => 1,
    ]
);

\App\Models\TicketCategory::updateOrCreate(
    ['event_id' => \$event->id, 'code' => '10K-CHARITY'],
    [
        'name' => '10K Charity Run',
        'description' => 'Termasuk: Jersey Dryfit, Medali Finisher, BIB Timing Chip, Refreshment, dan Donasi Flores.',
        'price' => 250000,
        'early_bird_price' => 220000,
        'early_bird_end_date' => now()->addDays(20),
        'quota' => 300,
        'min_age' => 14,
        'sort_order' => 2,
    ]
);

echo 'Event & Domain pku.runforflores.com berhasil dibuat dengan ID: ' . \$event->id . PHP_EOL;
"
```

---

## 6. Uji Coba & Hasil

1. Buka browser dan akses:
   ```
   https://pku.runforflores.com
   ```
   👉 **Hasil:** Sistem langsung membuka halaman registrasi resmi khusus **Pekanbaru Charity Run for Flores**, lengkap dengan pilihan kategori tiket 5K & 10K, formulir data diri peserta, pilihan ukuran jersey lari, dan checkout Tripay QRIS/VA.

2. Buka browser dan akses portal utama:
   ```
   https://jelatix.com
   ```
   👉 **Hasil:** Event **Pekanbaru Charity Run for Flores** otomatis muncul di katalog event aktif Jelatix. Tombol "Daftar Event" pada kartu tersebut secara otomatis mengarahkan pengunjung ke `https://pku.runforflores.com`.

3. Manajemen Terpusat di Admin:
   - Rekap ukuran jersey peserta pabrik untuk event Flores langsung terpisah dan dapat diunduh per event di menu **Rekap Jersey Pabrik**.
   - Nomor BIB dan data peserta dapat diakses di menu **Data Pelari (BIB)**.
