@extends('layouts.platform', [
    'title' => 'Syarat & Ketentuan - Jelatix Ticketing',
    'metaDescription' => 'Syarat dan Ketentuan resmi penggunaan layanan pemesanan tiket event lari di platform Jelatix (jelatix.com) dan ketentuan transaksi pembayaran melalui Tripay.'
])

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 text-white py-16 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <span class="inline-block px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 text-xs font-bold border border-orange-500/30 mb-3">
            DOKUMEN LEGALITAS MERCHANT
        </span>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight">Syarat & Ketentuan Layanan</h1>
        <p class="text-slate-400 text-xs sm:text-sm mt-2">Terakhir Diperbarui: 6 Oktober 2026 &bull; Berlaku untuk seluruh transaksi di platform jelatix.com</p>
    </div>
</div>

<!-- Legal Content -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 lg:py-16">
    <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 shadow-sm space-y-8 text-slate-700 text-sm leading-relaxed">
        
        <!-- Intro Note -->
        <div class="p-4 rounded-xl bg-orange-50 border border-orange-200 text-orange-900 text-xs leading-relaxed">
            <strong>PENTING:</strong> Selamat datang di <strong>Jelatix</strong> (<a href="https://jelatix.com" class="underline font-bold">jelatix.com</a>). Dengan mengakses situs, mendaftar, atau melakukan pembelian tiket event lari melalui platform ini, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh isi Syarat & Ketentuan di bawah ini.
        </div>

        <!-- Pasal 1 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">1.</span> Definisi & Istilah
            </h2>
            <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-600 pl-2">
                <li><strong>"Jelatix" / "Kami"</strong>: Pengelola platform teknologi pemesanan tiket dan registrasi event lari di domain <em>jelatix.com</em> dan subdomain/domain terafiliasi.</li>
                <li><strong>"Penyelenggara" / "Race Organizer"</strong>: Pihak ketiga atau panitia yang menyelenggarakan event lari dan bekerjasama dengan Jelatix untuk pendistribusian tiket.</li>
                <li><strong>"Peserta" / "Pengguna" / "Anda"</strong>: Individu yang mengakses platform, mengisi data diri, dan melakukan pembelian tiket lomba.</li>
                <li><strong>"E-Ticket"</strong>: Bukti sah registrasi digital yang memuat kode unik dan QR Code resmi untuk ditukarkan dengan Racepack di lokasi pengambilan (RPC).</li>
                <li><strong>"Tripay"</strong>: Mitra resmi penyedia payment gateway berizin Bank Indonesia yang memfasilitasi transaksi pembayaran QRIS dan Virtual Account.</li>
            </ul>
        </section>

        <!-- Pasal 2 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">2.</span> Ketentuan Pendaftaran Peserta
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>2.1. Peserta wajib mengisi data diri yang benar, sah, dan akurat (termasuk Nama Lengkap sesuai KTP/Paspor, NIK/Nomor Paspor, Tanggal Lahir, Jenis Kelamin, Ukuran Jersey, dan Kontak Darurat).</p>
                <p>2.2. Jelatix dan Penyelenggara berhak membatalkan keikutsertaan peserta tanpa pengembalian dana apabila ditemukan manipulasi data identitas atau identitas palsu.</p>
                <p>2.3. Peserta wajib memenuhi batasan usia minimum yang ditentukan oleh masing-masing kategori lomba (contoh: 5K min. 10 tahun, 10K min. 14 tahun, 21K min. 17 tahun, 42K min. 18 tahun).</p>
            </div>
        </section>

        <!-- Pasal 3 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">3.</span> Pemesanan, Harga & Pembayaran
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>3.1. Seluruh transaksi di Jelatix menggunakan mata uang resmi <strong>Rupiah (IDR)</strong>.</p>
                <p>3.2. Harga tiket yang tertera adalah harga resmi yang ditetapkan oleh Penyelenggara, dapat berupa harga Normal atau harga Promo / Early Bird selama kuota dan periode berlaku.</p>
                <p>3.3. Transaksi pembayaran diproses secara aman oleh <strong>Tripay Payment Gateway</strong> melalui saluran pembayaran resmi:</p>
                <ul class="list-disc list-inside pl-4 space-y-1">
                    <li>QRIS Nasional (BCA Mobile, GoPay, OVO, ShopeePay, DANA, LinkAja, dsb)</li>
                    <li>Virtual Account Bank (BCA, Mandiri, BRI, BNI, Permata, CIMB, BSI)</li>
                </ul>
                <p>3.4. Batas waktu penyelesaian pembayaran (expired time) adalah <strong>60 hingga 120 menit</strong> sejak invoice diterbitkan. Jika tidak diselesaikan dalam batas waktu tersebut, pesanan otomatis hangus dan kuota tiket dikembalikan ke sistem.</p>
                <p>3.5. Biaya layanan administrasi perbankan/gateway (jika ada) diinformasikan secara transparan pada halaman rincian pembayaran sebelum pembayaran dilakukan.</p>
            </div>
        </section>

        <!-- Pasal 4 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">4.</span> Penerbitan E-Ticket & Penukaran Racepack (RPC)
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>4.1. Setelah pembayaran terverifikasi LUNAS (PAID) oleh Tripay, sistem otomatis menerbitkan invoice resmi dan E-Ticket dengan kode tiket serta QR Code unik ke alamat email terdaftar.</p>
                <p>4.2. E-Ticket wajib ditunjukkan (dalam bentuk digital pada layar smartphone atau hasil cetak) beserta KTP/identitas asli pada saat pengambilan Racepack Collection (RPC) di lokasi yang ditentukan oleh panitia.</p>
                <p>4.3. Setiap QR Code hanya dapat dipindai satu kali untuk pengambilan racepack. Penggandaan atau pemindaian berulang yang tidak sah menjadi tanggung jawab penuh pemilik tiket.</p>
            </div>
        </section>

        <!-- Pasal 5 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">5.</span> Keselamatan Fisik, Medis & Pelepasan Tanggung Jawab
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>5.1. Peserta menyadari sepenuhnya bahwa olahraga lari jarak jauh (running race) membutuhkan kesiapan fisik yang prima dan memiliki risiko cedera fisik, dehidrasi, kelelahan parah, hingga serangan jantung.</p>
                <p>5.2. Peserta bertanggung jawab penuh atas kondisi kesehatannya sendiri dan wajib mengisi riwayat medis serta kontak darurat dengan benar.</p>
                <p>5.3. Jelatix bertindak sebagai penyedia platform teknologi tiketing. Penyelenggaraan teknis lomba, sterilisasi rute, medali, jersey, pos hidrasi, dan medis di lapangan adalah tanggung jawab penuh pihak Penyelenggara Event.</p>
            </div>
        </section>

        <!-- Pasal 6 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">6.</span> Keadaan Kahar (Force Majeure)
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>Apabila event tertunda, dibatalkan, atau rute lomba diubah akibat kejadian di luar kendali wajar (termasuk namun tidak terbatas pada bencana alam, cuaca ekstrem berbahaya, epidemi, kerusuhan, atau instruksi aparat berwenang/pemerintah), kebijakan kelanjutan lomba dan pengembalian dana mengikuti ketentuan resmi yang diumumkan oleh Penyelenggara.</p>
            </div>
        </section>

        <!-- Pasal 7 -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">7.</span> Hukum yang Berlaku & Kontak
            </h2>
            <div class="space-y-2 text-xs text-slate-600">
                <p>Syarat dan Ketentuan ini diatur dan ditafsirkan berdasarkan hukum Negara Kesatuan Republik Indonesia. Setiap perselisihan yang timbul akan diselesaikan secara musyawarah untuk mufakat terlebih dahulu.</p>
                <p class="pt-2">Pertanyaan atau keluhan terkait syarat dan ketentuan ini dapat diajukan kepada:</p>
                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                    <div><strong>Layanan Bantuan Jelatix Ticketing</strong></div>
                    <div>Email: <a href="mailto:hi@jelatix.com" class="text-orange-600 underline">hi@jelatix.com</a></div>
                    <div>WhatsApp: <a href="https://wa.me/6281234567890" class="text-orange-600 underline">0812-3456-7890</a></div>
                </div>
            </div>
        </section>

        <!-- Navigation Buttons -->
        <div class="pt-6 border-t border-slate-100 flex flex-wrap gap-4 items-center justify-between">
            <a href="{{ route('page.privacy') }}" class="text-xs font-bold text-slate-500 hover:text-orange-600 transition flex items-center gap-1.5">
                Kebijakan Privasi &rarr;
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
