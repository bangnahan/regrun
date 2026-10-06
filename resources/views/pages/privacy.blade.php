@extends('layouts.platform', [
    'title' => 'Kebijakan Privasi - Jelatix Ticketing',
    'metaDescription' => 'Kebijakan Privasi data pengguna platform Jelatix (jelatix.com) sesuai UU Perlindungan Data Pribadi (UU PDP) dan pemrosesan transaksi melalui Tripay Payment Gateway.'
])

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 text-white py-16 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <span class="inline-block px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 text-xs font-bold border border-orange-500/30 mb-3">
            DOKUMEN LEGALITAS MERCHANT
        </span>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight">Kebijakan Privasi (Privacy Policy)</h1>
        <p class="text-slate-400 text-xs sm:text-sm mt-2">Terakhir Diperbarui: 6 Oktober 2026 &bull; Komitmen Perlindungan Data Pribadi di jelatix.com</p>
    </div>
</div>

<!-- Legal Content -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 lg:py-16">
    <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 shadow-sm space-y-8 text-slate-700 text-sm leading-relaxed">
        
        <!-- Intro Note -->
        <div class="p-4 rounded-xl bg-orange-50 border border-orange-200 text-orange-900 text-xs leading-relaxed">
            <strong>KOMITMEN KAMI:</strong> Di <strong>Jelatix</strong> (<a href="https://jelatix.com" class="underline font-bold">jelatix.com</a>), kami sangat menghargai dan melindungi kerahasiaan data pribadi setiap pelari dan pengguna platform. Kebijakan Privasi ini dirancang berdasarkan prinsip kepatuhan <strong>Undang-Undang Republik Indonesia Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP)</strong> serta standar keamanan transaksi pembayaran digital bersama mitra payment gateway kami, <strong>Tripay</strong>.
        </div>

        <!-- Bagian 1 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">1.</span> Informasi dan Data yang Kami Kumpulkan
            </h2>
            <p class="text-xs text-slate-600">Untuk memproses pendaftaran event lari, penerbitan E-Ticket, dan transaksi pembayaran, Jelatix mengumpulkan data yang Anda masukkan secara sukarela saat pendaftaran:</p>
            <div class="space-y-3 text-xs text-slate-600 pl-2">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <strong class="text-slate-900 block mb-1">A. Data Identitas Peserta:</strong>
                    Nama Lengkap (sesuai kartu identitas resmi KTP/SIM/Paspor), Nomor Induk Kependudukan (NIK) atau Nomor Paspor, Tanggal Lahir, Jenis Kelamin, Nomor WhatsApp / Telepon Aktif, dan Alamat Surat Elektronik (Email).
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <strong class="text-slate-900 block mb-1">B. Data Operasional Lari & Racepack:</strong>
                    Kategori lomba yang dipilih (misal: 5K, 10K, Half Marathon, Full Marathon), Ukuran Jersey lari yang diinginkan, dan Nama BIB / Nama Dada (jika disediakan oleh penyelenggara).
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <strong class="text-slate-900 block mb-1">C. Data Kontak Darurat & Medis:</strong>
                    Nama narahubung darurat, hubungan dengan peserta, nomor kontak darurat, serta catatan kondisi medis khusus bila diisi (guna protokol keselamatan tim medis di lapangan).
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <strong class="text-slate-900 block mb-1">D. Data Transaksi & Pembayaran:</strong>
                    Nomor referensi invoice, kanal pembayaran yang dipilih (QRIS, Virtual Account Bank), nilai transaksi, dan riwayat waktu pembayaran. <span class="font-semibold text-orange-600">Perhatian: Jelatix TIDAK pernah menyimpan informasi sensitif seperti nomor PIN perbankan atau CVV kartu Anda. Seluruh transaksi dialihkan dan diproses secara aman oleh Tripay Payment Gateway.</span>
                </div>
            </div>
        </section>

        <!-- Bagian 2 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">2.</span> Tujuan Penggunaan Data Pribadi
            </h2>
            <p class="text-xs text-slate-600">Data pribadi yang dikumpulkan digunakan semata-mata untuk kepentingan penyelenggaraan event lari, antara lain:</p>
            <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-600 pl-2">
                <li>Memvalidasi pemesanan tiket dan menerbitkan E-Ticket digital ber-QR Code unik.</li>
                <li>Meneruskan data rekap ukuran jersey kepada pabrik garmen konveksi resmi dan logistik racepack.</li>
                <li>Pengalokasian Nomor BIB resmi dan sinkronisasi ke sistem timing chip lomba.</li>
                <li>Mengirimkan invoice pembayaran, E-Ticket, dan pengumuman teknis lomba (Race Guide, jadwal RPC) melalui WhatsApp dan Email resmi.</li>
                <li>Menghubungi pihak kontak darurat jika terjadi insiden kecelakaan atau kegawatdaruratan medis pada saat pelaksanaan lomba lari.</li>
                <li>Melakukan verifikasi identitas resmi saat pengambilan paket lomba (Racepack Collection).</li>
            </ul>
        </section>

        <!-- Bagian 3 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">3.</span> Pengungkapan Data kepada Pihak Ketiga
            </h2>
            <p class="text-xs text-slate-600">Jelatix berprinsip menjaga kerahasiaan penuh dan <strong>TIDAK AKAN PERNAH</strong> menjual, menyewakan, atau memperjualbelikan data pribadi pengguna kepada pihak ketiga manapun untuk tujuan pemasaran komersial. Data hanya dapat dibagikan kepada mitra terkait berikut:</p>
            <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-600 pl-2">
                <li><strong>Race Organizer (Penyelenggara Resmi Event):</strong> Panitia pelaksana acara lari yang terikat kontrak kerja sama untuk eksekusi operasional lomba, asuransi peserta, dan tim medis.</li>
                <li><strong>Tripay Payment Gateway (PT Terang Bulan Mandiri / Mitra Berizin BI):</strong> Untuk keperluan verifikasi status setoran pembayaran QRIS dan Virtual Account.</li>
                <li><strong>Aparat Penegak Hukum / Otoritas Pemerintah:</strong> Hanya apabila diwajibkan oleh peraturan perundang-undangan Republik Indonesia yang sah atau perintah pengadilan.</li>
            </ul>
        </section>

        <!-- Bagian 4 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">4.</span> Keamanan dan Retensi Data
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>4.1. Transaksi data pada platform Jelatix dilindungi dengan sertifikat enkripsi standar industri <strong>SSL/TLS (Secure Socket Layer) 256-bit</strong>.</p>
                <p>4.2. Basis data Jelatix disimpan pada infrastruktur cloud server terproteksi dengan kontrol akses ketat, firewall aktif, dan audit keamanan berkala.</p>
                <p>4.3. Data transaksi disimpan selama periode yang diwajibkan oleh ketentuan hukum perpajakan dan perbankan di Indonesia sebelum diarsipkan atau dihapus secara aman.</p>
            </div>
        </section>

        <!-- Bagian 5 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">5.</span> Hak-Hak Pemilik Data Pribadi
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>Sesuai UU Pelindungan Data Pribadi, Anda memiliki hak untuk:</p>
                <ul class="list-disc list-inside space-y-1 pl-2">
                    <li>Mengakses dan memperoleh salinan data pendaftaran yang tersimpan di sistem Jelatix.</li>
                    <li>Mengajukan permohonan koreksi data jika terdapat kesalahan pengetikan nama, NIK, atau nomor kontak, sebelum batas akhir penutupan registrasi dan cetak nomor BIB.</li>
                    <li>Mengajukan pertanyaan terkait pemrosesan data melalui saluran resmi Customer Support kami.</li>
                </ul>
            </div>
        </section>

        <!-- Bagian 6 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">6.</span> Penggunaan Cookies
            </h2>
            <p class="text-xs text-slate-600">
                Platform Jelatix menggunakan <em>session cookies</em> sementara yang bersifat teknis untuk menjaga status sesi formulir pendaftaran, memverifikasi token keamanan CSRF, dan memastikan kelancaran navigasi langkah demi langkah (multi-step wizard). Kami tidak menggunakan cookies pihak ketiga yang melacak riwayat penjelajahan Anda di luar situs kami.
            </p>
        </section>

        <!-- Bagian 7 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">7.</span> Perubahan Kebijakan Privasi
            </h2>
            <p class="text-xs text-slate-600">
                Jelatix berhak untuk memperbarui dokumen Kebijakan Privasi ini sewaktu-waktu guna menyesuaikan dengan perkembangan teknologi, peraturan pemerintah, dan kebijakan payment gateway Tripay. Setiap perubahan akan diumumkan langsung pada halaman ini dengan tanggal pembaruan terbaru.
            </p>
        </section>

        <!-- Bagian 8 -->
        <section class="pt-6 border-t border-slate-200">
            <h2 class="text-base font-black text-slate-900 mb-2">Petugas Perlindungan Data & Kontak</h2>
            <p class="text-xs text-slate-600 leading-relaxed">
                Apabila Anda memiliki pertanyaan, keluhan, atau ingin menggunakan hak Anda terkait data pribadi, silakan hubungi tim Jelatix:
            </p>
            <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 space-y-1.5 font-medium">
                <div>&bull; <strong>Nama Platform:</strong> Jelatix Ticketing (jelatix.com)</div>
                <div>&bull; <strong>Email Privasi:</strong> <a href="mailto:hi@jelatix.com" class="text-orange-600 underline">hi@jelatix.com</a></div>
                <div>&bull; <strong>WhatsApp CS:</strong> 0812-3456-7890 / 0821-4603-9090</div>
                <div>&bull; <strong>Alamat Operasional:</strong> Jl. Raya Utama No. 88, Jakarta Selatan, DKI Jakarta, Indonesia</div>
                <div>&bull; <strong>Jam Layanan:</strong> Senin &ndash; Jumat, 09.00 &ndash; 17.00 WIB</div>
            </div>
        </section>

        <!-- Navigation Buttons -->
        <div class="pt-6 border-t border-slate-100 flex flex-wrap gap-4 items-center justify-between">
            <a href="{{ route('page.terms') }}" class="text-xs font-bold text-slate-500 hover:text-orange-600 transition flex items-center gap-1.5">
                &larr; Lihat Syarat & Ketentuan
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('page.refund') }}" class="text-xs font-bold text-slate-500 hover:text-orange-600 transition">
                    Kebijakan Pengembalian &rarr;
                </a>
                <a href="{{ route('register.index') }}" class="px-5 py-2.5 rounded-xl bg-orange-600 text-white font-bold text-xs hover:bg-orange-700 transition shadow-sm">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
