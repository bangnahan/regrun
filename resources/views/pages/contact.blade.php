@extends('layouts.platform', [
    'title' => 'Kontak Kami - Jelatix Ticketing',
    'metaDescription' => 'Hubungi tim layanan pelanggan resmi Jelatix (jelatix.com). Informasi operasional, WhatsApp CS, alamat kantor, dan email dukungan tiket event lari.'
])

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 text-white py-16 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <span class="inline-block px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 text-xs font-bold border border-orange-500/30 mb-3">
            LAYANAN PELANGGAN & OPERASIONAL
        </span>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight">Hubungi Kami</h1>
        <p class="text-slate-400 text-xs sm:text-sm mt-2">Tim Customer Support Jelatix siap membantu pertanyaan seputar tiket lari, pembayaran Tripay, dan bantuan teknis.</p>
    </div>
</div>

<!-- Contact Content -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 lg:py-16">
    <div class="space-y-8">

        <!-- Top Overview Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-10 shadow-sm">
            <div class="max-w-2xl">
                <h2 class="text-xl font-black text-slate-900 tracking-tight mb-2">Informasi Kontak Resmi Jelatix</h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Sebagai platform penyedia tiket resmi event lari dengan sistem pembayaran terintegrasi <strong>Tripay Payment Gateway</strong>, kami menyediakan kanal komunikasi resmi yang responsif untuk peserta lomba maupun penyelenggara event (Race Organizer).
                </p>
            </div>

            <!-- Grid 4 Kolom Kontak -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-8">
                
                <!-- Card 1: Email Resmi -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-orange-300 transition group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition">
                            📧
                        </div>
                        <h3 class="text-sm font-black text-slate-900 mb-1">Surat Elektronik (Email)</h3>
                        <p class="text-xs text-slate-500 mb-3">Untuk pertanyaan umum, kerjasama race organizer, dan konfirmasi tiket.</p>
                        <div class="text-sm font-bold text-slate-900 font-mono select-all">
                            hi@jelatix.com
                        </div>
                        <div class="text-xs text-slate-500 font-mono mt-0.5">
                            support@jelatix.com
                        </div>
                    </div>
                    <div class="mt-5">
                        <a href="mailto:hi@jelatix.com" class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-600 hover:text-orange-700">
                            Kirim Email Sekarang &rarr;
                        </a>
                    </div>
                </div>

                <!-- Card 2: WhatsApp Customer Service -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-300 transition group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition">
                            💬
                        </div>
                        <h3 class="text-sm font-black text-slate-900 mb-1">WhatsApp Customer Service</h3>
                        <p class="text-xs text-slate-500 mb-3">Respon cepat untuk kendala pembayaran dan verifikasi E-Ticket.</p>
                        <div class="text-sm font-bold text-emerald-700 font-mono select-all">
                            0819-1644-4458
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Senin - Jumat: 09.00 - 17.00 WIB
                        </div>
                    </div>
                    <div class="mt-5">
                        <a href="https://wa.me/6281916444458?text=Halo%20Admin%20Jelatix%2C%20saya%20butuh%20bantuan%20seputar%20tiket%20lari" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:text-emerald-700">
                            Chat via WhatsApp &rarr;
                        </a>
                    </div>
                </div>

                <!-- Card 3: Kantor Operasional -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition">
                            📍
                        </div>
                        <h3 class="text-sm font-black text-slate-900 mb-1">Alamat Kantor Operasional</h3>
                        <p class="text-xs text-slate-500 mb-3">Pusat administrasi dan operasional platform ticketing Jelatix.</p>
                        <address class="text-xs text-slate-700 not-italic leading-relaxed">
                            <strong>Jelatix Ticketing Office</strong><br>
                            Jl Tapah No 22, Kelurahan Tangkerang Barat<br>
                            Kec. Marpoyan Damai<br>
                            Kota Pekanbaru, Riau<br>
                            Indonesia
                        </address>
                    </div>
                    <div class="mt-5 text-xs text-slate-400">
                        *Kunjungan offline memerlukan janji temu
                    </div>
                </div>

                <!-- Card 4: Jam Kerja Layanan -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-purple-300 transition group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition">
                            ⏰
                        </div>
                        <h3 class="text-sm font-black text-slate-900 mb-1">Jam Operasional Layanan</h3>
                        <p class="text-xs text-slate-500 mb-3">Waktu tanggap Customer Support untuk melayani pertanyaan.</p>
                        <div class="text-xs text-slate-700 space-y-1">
                            <div><strong>Senin &ndash; Jumat:</strong> 09.00 &ndash; 17.00 WIB</div>
                            <div><strong>Sabtu & Minggu:</strong> Siaga Khusus Saat Hari Acara Lomba (Race Day)</div>
                            <div><strong>Hari Libur Nasional:</strong> Respon Terbatas</div>
                        </div>
                    </div>
                    <div class="mt-5 text-xs text-emerald-600 font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Sistem Tiketing Online 24/7 Aktif
                    </div>
                </div>

            </div>

        </div>

        <!-- Tripay & Payment Notice Card -->
        <div class="bg-gradient-to-r from-orange-500/10 via-amber-500/5 to-slate-50 rounded-2xl border border-orange-200/80 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row gap-5 items-start sm:items-center justify-between">
                <div>
                    <span class="px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[10px] font-black uppercase tracking-wider">
                        Verifikasi Pembayaran Tripay
                    </span>
                    <h3 class="text-base font-black text-slate-900 mt-2">Ada Kendala pada Pembayaran Tiket?</h3>
                    <p class="text-xs text-slate-600 mt-1 max-w-xl leading-relaxed">
                        Jika Anda sudah menyelesaikan transfer di ATM / M-Banking / scan QRIS namun status invoice belum terupdate otomatis dalam 5 menit, harap hubungi WhatsApp CS kami dengan melampirkan <strong>Nomor Invoice</strong> dan <strong>Bukti Transfer</strong> resmi Anda.
                    </p>
                </div>
                <div class="shrink-0">
                    <a href="https://wa.me/6281916444458?text=Halo%20Admin%2C%20saya%20sudah%20transfer%20tetapi%20status%20invoice%20belum%20berubah" target="_blank" rel="noopener" class="px-5 py-3 rounded-xl bg-orange-600 text-white font-bold text-xs hover:bg-orange-700 transition shadow-sm inline-flex items-center gap-2">
                        <span>💬</span> Laporkan Bukti Bayar
                    </a>
                </div>
            </div>
        </div>

        <!-- Partnership for Race Organizers -->
        <div class="bg-slate-900 text-white rounded-2xl p-8 sm:p-10 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center md:text-left">
                <h3 class="text-lg font-black tracking-tight">Ingin Menggunakan Jelatix untuk Event Lari Anda?</h3>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl">
                    Kami menyediakan sistem multi-step wizard, custom domain acara lari Anda sendiri, integrasi QRIS/VA otomatis, rekap ukuran jersey pabrik, dan sistem scanner Racepack (RPC).
                </p>
            </div>
            <div class="shrink-0 flex items-center gap-3">
                <a href="mailto:hi@jelatix.com?subject=Kerjasama%20Event%20Lari%20-%20Jelatix" class="px-5 py-3 rounded-xl bg-white text-slate-900 font-bold text-xs hover:bg-slate-100 transition shadow-sm">
                    Kerjasama Event &rarr;
                </a>
            </div>
        </div>

        <!-- Legal Navigation Links -->
        <div class="pt-6 border-t border-slate-200 flex flex-wrap gap-4 items-center justify-between text-xs font-bold text-slate-500">
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('page.terms') }}" class="hover:text-orange-600 transition">Syarat & Ketentuan</a>
                <span>&bull;</span>
                <a href="{{ route('page.privacy') }}" class="hover:text-orange-600 transition">Kebijakan Privasi</a>
                <span>&bull;</span>
                <a href="{{ route('page.refund') }}" class="hover:text-orange-600 transition">Kebijakan Pengembalian</a>
            </div>
            <a href="{{ route('register.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                &larr; Ke Beranda Jelatix
            </a>
        </div>

    </div>
</div>
@endsection
