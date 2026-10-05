# Panduan Operasional Multi-Domain di CloudPanel (RegRun Multi-Event Platform)

Dokumen ini menjelaskan langkah demi langkah cara mengoperasikan sistem **RegRun** pada **CloudPanel** untuk mendukung **banyak domain/subdomain sekaligus** dalam satu instalasi Laravel dan database tunggal.

---

## 1. Arsitektur Multi-Domain CloudPanel

```
                                  [ Pengunjung / Peserta ]
                                              │
               ┌──────────────────────────────┼──────────────────────────────┐
               ▼                              ▼                              ▼
      https://tiket.event-a.com      https://marathon-b.id          https://regrun.portal.com
               │                              │                              │
               └──────────────────────────────┼──────────────────────────────┘
                                              ▼
                             [ CloudPanel VPS Server Nginx ]
                       (Site Root: /home/user/htdocs/site/public)
                                              │
                                              ▼ FastCGI (HTTP_HOST)
                               [ Laravel Application Kernel ]
                                              │
                                              ▼
                             [ Middleware: ResolveEventDomain ]
                   Mencocokkan $request->getHost() dengan tabel `event_domains`
                                              │
               ┌──────────────────────────────┼──────────────────────────────┐
               ▼                              ▼                              ▼
         [ Event A Context ]           [ Event B Context ]            [ Default Event ]
         - Kuota & Kategori A          - Kuota & Kategori B           - Portal Utama
         - Branding A (Warna/Logo)     - Branding B (Warna/Logo)      - Master Admin
         - Gateway A (Optional)        - Gateway B (Optional)         - Global Settings
```

---

## 2. Fase 1: Setup Awal Domain Pertama (Domain 1)

1. **Buat Site PHP di CloudPanel**:
   - Buka CloudPanel Dashboard: `https://ip-vps:8443`.
   - Klik **+ Add Site** > **Create a PHP Site**.
   - **Domain Name**: Masukkan domain utama (misal: `regrun.mycompany.com` atau domain event pertama `tiket.nusantararun.com`).
   - **Site User**: Buat user baru (misal: `regrun`).
   - **PHP Version**: Pilih `PHP 8.2` atau `PHP 8.3` (atau sesuai versi yang aktif).
   - Klik **Create**.

2. **Upload / Clone Kode & Konfigurasi `.env`**:
   - Masuk ke terminal VPS via SSH:
     ```bash
     su - regrun
     cd ~/htdocs/tiket.nusantararun.com
     git clone <repo-url> .
     composer install --no-dev --optimize-autoloader
     cp .env.example .env
     php artisan key:generate
     ```
   - Sesuaikan konfigurasi database MySQL di `.env`.
   - Jalankan migrasi:
     ```bash
     php artisan migrate --force
     php artisan db:seed --force
     ```

3. **Install SSL Certificate Pertama**:
   - Di CloudPanel, klik menu **Site** > pilih site Anda > tab **SSL/TLS**.
   - Pilih **New Let's Encrypt Certificate** > klik **Create and Install**.

---

## 3. Fase 2: Menambahkan Domain Ke-2, Ke-3, ... Ke-N

Ketika Anda memiliki event baru dengan domain sendiri (misal: `marathonjakarta.id` atau `reg.balitriathlon.com`):

### Langkah A: Konfigurasi DNS di Registrar Domain
1. Buka DNS Management domain baru (Cloudflare, Niagahoster, Domainesia, dsb).
2. Tambahkan **A Record**:
   - **Type**: `A`
   - **Name**: `@` (atau subdomain seperti `tiket` atau `reg`)
   - **IPv4 Address**: `IP_VPS_CLOUDPANEL_ANDA`
   - **Proxy**: DNS Only (jika menggunakan Cloudflare, disarankan DNS Only agar SSL CloudPanel terbit lancar, atau Full SSL).

### Langkah B: Daftarkan Domain Baru di CloudPanel (Site Alias)
1. Buka CloudPanel > pilih site aplikasi Anda.
2. Buka tab **Domain Names**.
3. Di kolom **New Domain Name**, masukkan domain/subdomain baru:
   - Contoh: `tiket.marathonjakarta.id`
   - Atau: `marathonjakarta.id`
4. Klik **Add Domain**.
   *(Nginx secara otomatis memetakan domain ini ke direktori `/public` aplikasi yang sama!)*
5. Buka tab **SSL/TLS**:
   - Klik **New Let's Encrypt Certificate**.
   - Centang domain utama dan domain-domain alias yang baru ditambahkan.
   - Klik **Create and Install** (Let's Encrypt menerbitkan sertifikat multi-SAN / multi-domain instan).

### Langkah C: Hubungkan Domain ke Event di Panel Admin RegRun
1. Buka Panel Admin RegRun: `https://[domain-anda]/admin/events`.
2. Klik **Edit** pada Event yang bersangkutan.
3. Di bagian **Domain & Branding**:
   - Tambahkan domain: `tiket.marathonjakarta.id`.
   - Centang **Set sebagai Domain Utama (Primary)**.
   - (Opsional) Masukkan Logo URL dan Warna Tema Event.
   - (Opsional) Masukkan Merchant Code & API Key Tripay khusus event tersebut jika panitia menggunakan rekening Tripay tersendiri.
4. Klik **Simpan**.

**Selesai!** Saat pengguna membuka `https://tiket.marathonjakarta.id`, sistem secara otomatis menampilkan katalog tiket, branding, dan alur pendaftaran untuk event tersebut!

---

## 4. Pemeliharaan & Skrip Deploy Otomatis

Setiap kali ada pembaruan kode di GitHub/Git:
1. Buka CloudPanel > tab **Deployment**.
2. Masukkan skrip dari `cloudpanel/cloudpanel-deploy.sh`.
3. Klik **Deploy**.
